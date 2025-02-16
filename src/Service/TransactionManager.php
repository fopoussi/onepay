<?php

namespace App\Service;

use App\Entity\Transaction;
use App\Entity\OnepayFailedTransaction;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Cache\CacheInterface;

class TransactionManager
{
    // Constantes pour les limites de transaction
    private const TRANSACTION_MIN_AMOUNT = 500;
    private const TRANSACTION_MAX_AMOUNT = 500000;
    private const DAILY_LIMIT = 2000000;
    private const MONTHLY_LIMIT = 10000000;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly LoggerInterface $logger,
        private readonly CacheInterface $cache
    ) {}

    public function calculateFees(Transaction $transaction): float
    {
        $amount = $transaction->getAmount();

        // Calcul des frais selon les règles définies
        $fees = match(true) {
            $amount < 5000 => 100,
            $amount <= 20000 => 200,
            default => $amount * 0.01 // 1% pour les montants > 20,000
        };

        $transaction->setFees($fees);
        return $fees;
    }

    public function validateTransaction(Transaction $transaction): bool
    {
        try {
            // Validation du montant
            if (!$this->validateAmount($transaction)) {
                throw new \Exception('Montant invalide');
            }

            // Validation des numéros de téléphone
            if (!$this->validatePhoneNumbers($transaction)) {
                throw new \Exception('Numéro de téléphone invalide');
            }

            // Validation des limites
            if (!$this->validateLimits($transaction)) {
                throw new \Exception('Limite de transaction dépassée');
            }

            // Validation du compte source
            if (!$this->validateSourceAccount($transaction)) {
                throw new \Exception('Compte source invalide ou solde insuffisant');
            }

            return true;
        } catch (\Exception $e) {
            $this->logger->error('Erreur de validation de transaction', [
                'error' => $e->getMessage(),
                'transaction' => $transaction->getId()
            ]);
            return false;
        }
    }

    private function validateAmount(Transaction $transaction): bool
    {
        $amount = $transaction->getAmount();
        return $amount >= self::TRANSACTION_MIN_AMOUNT && 
               $amount <= self::TRANSACTION_MAX_AMOUNT;
    }

    private function validatePhoneNumbers(Transaction $transaction): bool
    {
        $recipientNumber = $transaction->getRecipientNumber();
        
        // Validation du format (6XXXXXXXX)
        if (!preg_match('/^6[2,5-9][0-9]{7}$/', $recipientNumber)) {
            return false;
        }

        // Validation de l'opérateur selon le préfixe
        $prefix = substr($recipientNumber, 1, 1);
        $operator = $transaction->getOperator();

        return match($prefix) {
            '5', '7', '8' => $operator === 'MTN',
            '9', '6' => $operator === 'ORANGE',
            '2' => $operator === 'CAMTEL',
            default => false
        };
    }

    private function validateLimits(Transaction $transaction): bool
    {
        $userId = $transaction->getUser()->getId();
        $amount = $transaction->getAmount();

        // Vérification de la limite journalière
        $dailyTotal = $this->getDailyTransactionsTotal($userId);
        if ($dailyTotal + $amount > self::DAILY_LIMIT) {
            return false;
        }

        // Vérification de la limite mensuelle
        $monthlyTotal = $this->getMonthlyTransactionsTotal($userId);
        if ($monthlyTotal + $amount > self::MONTHLY_LIMIT) {
            return false;
        }

        return true;
    }

    private function validateSourceAccount(Transaction $transaction): bool
    {
        $sourceAccount = $transaction->getSourceAccount();
        if (!$sourceAccount || !$sourceAccount->isVerified()) {
            return false;
        }

        $totalAmount = $transaction->getAmount() + $this->calculateFees($transaction);
        return $sourceAccount->getBalance() >= $totalAmount;
    }

    private function getDailyTransactionsTotal(int $userId): float
    {
        $cacheKey = sprintf('daily_transactions_%d_%s', $userId, date('Y-m-d'));
        
        return $this->cache->get($cacheKey, function() use ($userId) {
            $qb = $this->entityManager->createQueryBuilder();
            return $qb->select('SUM(t.amount)')
                ->from(Transaction::class, 't')
                ->where('t.user = :userId')
                ->andWhere('t.createdAt >= :today')
                ->andWhere('t.status = :status')
                ->setParameter('userId', $userId)
                ->setParameter('today', new \DateTime('today'))
                ->setParameter('status', 'COMPLETED')
                ->getQuery()
                ->getSingleScalarResult() ?? 0;
        });
    }

    private function getMonthlyTransactionsTotal(int $userId): float
    {
        $cacheKey = sprintf('monthly_transactions_%d_%s', $userId, date('Y-m'));
        
        return $this->cache->get($cacheKey, function() use ($userId) {
            $qb = $this->entityManager->createQueryBuilder();
            return $qb->select('SUM(t.amount)')
                ->from(Transaction::class, 't')
                ->where('t.user = :userId')
                ->andWhere('t.createdAt >= :firstDayOfMonth')
                ->andWhere('t.status = :status')
                ->setParameter('userId', $userId)
                ->setParameter('firstDayOfMonth', new \DateTime('first day of this month'))
                ->setParameter('status', 'COMPLETED')
                ->getQuery()
                ->getSingleScalarResult() ?? 0;
        });
    }

    public function processTransaction(Transaction $transaction): void
    {
        try {
            // Mise à jour du statut
            $transaction->setStatus('COMPLETED');
            $transaction->setCompletedAt(new \DateTimeImmutable());
            
            // Mise à jour des soldes
            $sourceAccount = $transaction->getSourceAccount();
            $totalAmount = $transaction->getAmount() + $transaction->getFees();
            $sourceAccount->setBalance($sourceAccount->getBalance() - $totalAmount);
            
            // Enregistrement des modifications
            $this->entityManager->persist($transaction);
            $this->entityManager->persist($sourceAccount);
            $this->entityManager->flush();

            // Invalidation du cache des totaux
            $userId = $transaction->getUser()->getId();
            $this->cache->delete(sprintf('daily_transactions_%d_%s', $userId, date('Y-m-d')));
            $this->cache->delete(sprintf('monthly_transactions_%d_%s', $userId, date('Y-m')));

            $this->logger->info('Transaction traitée avec succès', [
                'transactionId' => $transaction->getId(),
                'amount' => $transaction->getAmount(),
                'type' => $transaction->getType()
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Erreur lors du traitement de la transaction', [
                'error' => $e->getMessage(),
                'transaction' => $transaction->getId()
            ]);
            throw $e;
        }
    }

    public function handleFailure(Transaction $transaction, string $reason): void
    {
        try {
            // Mise à jour du statut de la transaction
            $transaction->setStatus('FAILED');
            
            // Création d'une entrée dans les transactions échouées
            $failedTransaction = new OnepayFailedTransaction();
            $failedTransaction->setTransaction($transaction);
            $failedTransaction->setReason($reason);
            $failedTransaction->setFailedAt(new \DateTimeImmutable());
            
            // Enregistrement des modifications
            $this->entityManager->persist($transaction);
            $this->entityManager->persist($failedTransaction);
            $this->entityManager->flush();

            $this->logger->error('Transaction échouée', [
                'transactionId' => $transaction->getId(),
                'reason' => $reason
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Erreur lors du traitement de l\'échec de la transaction', [
                'error' => $e->getMessage(),
                'transaction' => $transaction->getId()
            ]);
            throw $e;
        }
    }

    public function updateStatus(Transaction $transaction, string $status): void
    {
        try {
            $oldStatus = $transaction->getStatus();
            $transaction->setStatus($status);
            
            // Ajout de l'historique des statuts
            $statusHistory = $transaction->getStatusHistory() ?? [];
            $statusHistory[] = [
                'status' => $status,
                'timestamp' => (new \DateTimeImmutable())->format('c'),
                'previousStatus' => $oldStatus
            ];
            $transaction->setStatusHistory($statusHistory);
            
            if ($status === 'COMPLETED') {
                $transaction->setCompletedAt(new \DateTimeImmutable());
            }
            
            $this->entityManager->persist($transaction);
            $this->entityManager->flush();

            $this->logger->info('Statut de la transaction mis à jour', [
                'transactionId' => $transaction->getId(),
                'oldStatus' => $oldStatus,
                'newStatus' => $status
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de la mise à jour du statut', [
                'error' => $e->getMessage(),
                'transaction' => $transaction->getId()
            ]);
            throw $e;
        }
    }
}
