<?php

namespace App\Service;

use App\Entity\MobileMoneyAccount;
use App\Entity\Transaction;
use App\Service\TransactionManager;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class MtnMomoService implements MobileMoneyService
{
    public function __construct(
        private readonly TransactionManager $transactionManager,
        private readonly NotificationService $notificationService,
        private readonly LoggerInterface $logger,
        private readonly CacheInterface $cache,
        #[Autowire('%env(MTN_API_KEY)%')]
        private readonly string $apiKey,
        #[Autowire('%env(MTN_API_SECRET)%')]
        private readonly string $apiSecret,
        #[Autowire('%env(MTN_SUBSCRIPTION_KEY)%')]
        private readonly string $subscriptionKey
    ) {}

    public function verifyAccount(MobileMoneyAccount $account): bool
    {
        try {
            $this->logger->info('Vérification du compte MTN MoMo', [
                'number' => $account->getNumber()
            ]);

            // TODO: Implémenter l'appel à l'API MTN MoMo pour vérifier le compte
            // Simulation pour le moment
            $isValid = preg_match('/^6[578][0-9]{7}$/', $account->getNumber()) === 1;

            if ($isValid) {
                $account->setIsVerified(true);
            }

            return $isValid;
        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de la vérification du compte MTN MoMo', [
                'error' => $e->getMessage(),
                'number' => $account->getNumber()
            ]);
            return false;
        }
    }

    public function getBalance(MobileMoneyAccount $account): float
    {
        $cacheKey = sprintf('mtn_momo_balance_%s', $account->getNumber());

        return $this->cache->get($cacheKey, function() use ($account) {
            try {
                $this->logger->info('Récupération du solde MTN MoMo', [
                    'number' => $account->getNumber()
                ]);

                // TODO: Implémenter l'appel à l'API MTN MoMo pour récupérer le solde
                // Simulation pour le moment
                $balance = random_int(1000, 100000);

                $account->setBalance($balance);
                $account->setLastSync(new \DateTimeImmutable());

                return $balance;
            } catch (\Exception $e) {
                $this->logger->error('Erreur lors de la récupération du solde MTN MoMo', [
                    'error' => $e->getMessage(),
                    'number' => $account->getNumber()
                ]);
                return $account->getBalance() ?? 0;
            }
        }, 300); // Cache for 5 minutes
    }

    public function initiatePayment(Transaction $transaction): string
    {
        try {
            $this->logger->info('Initiation du paiement MTN MoMo', [
                'amount' => $transaction->getAmount(),
                'type' => $transaction->getType()
            ]);

            // Validation des limites et du solde
            if (!$this->transactionManager->validateTransaction($transaction)) {
                throw new \Exception('Transaction invalide');
            }

            // TODO: Implémenter l'appel à l'API MTN MoMo pour initier le paiement
            // Simulation pour le moment
            $reference = uniqid('MTN_', true);
            $transaction->setReference($reference);
            $transaction->setOperatorReference(null);

            return $reference;
        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de l\'initiation du paiement MTN MoMo', [
                'error' => $e->getMessage(),
                'transaction' => $transaction->getId()
            ]);
            throw $e;
        }
    }

    public function checkPaymentStatus(string $reference): array
    {
        try {
            $this->logger->info('Vérification du statut du paiement MTN MoMo', [
                'reference' => $reference
            ]);

            // TODO: Implémenter l'appel à l'API MTN MoMo pour vérifier le statut
            // Simulation pour le moment
            return [
                'status' => 'COMPLETED',
                'message' => 'Transaction réussie',
                'operatorReference' => 'MTN_' . uniqid(),
                'completedAt' => new \DateTimeImmutable()
            ];
        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de la vérification du statut MTN MoMo', [
                'error' => $e->getMessage(),
                'reference' => $reference
            ]);
            throw $e;
        }
    }

    public function purchaseAirtime(Transaction $transaction): bool
    {
        try {
            $this->logger->info('Achat de crédit MTN MoMo', [
                'amount' => $transaction->getAmount(),
                'recipient' => $transaction->getRecipientNumber()
            ]);

            if (!$this->transactionManager->validateTransaction($transaction)) {
                throw new \Exception('Transaction invalide');
            }

            // TODO: Implémenter l'appel à l'API MTN MoMo pour l'achat de crédit
            // Simulation pour le moment
            $success = true;

            if ($success) {
                $this->transactionManager->processTransaction($transaction);
                $this->notificationService->sendTransactionNotification($transaction);
            }

            return $success;
        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de l\'achat de crédit MTN MoMo', [
                'error' => $e->getMessage(),
                'transaction' => $transaction->getId()
            ]);
            $this->transactionManager->handleFailure($transaction, $e->getMessage());
            return false;
        }
    }

    public function transferMoney(Transaction $transaction): bool
    {
        try {
            $this->logger->info('Transfert d\'argent MTN MoMo', [
                'amount' => $transaction->getAmount(),
                'recipient' => $transaction->getRecipientNumber()
            ]);

            if (!$this->transactionManager->validateTransaction($transaction)) {
                throw new \Exception('Transaction invalide');
            }

            // TODO: Implémenter l'appel à l'API MTN MoMo pour le transfert
            // Simulation pour le moment
            $success = true;

            if ($success) {
                $this->transactionManager->processTransaction($transaction);
                $this->notificationService->sendTransactionNotification($transaction);
            }

            return $success;
        } catch (\Exception $e) {
            $this->logger->error('Erreur lors du transfert MTN MoMo', [
                'error' => $e->getMessage(),
                'transaction' => $transaction->getId()
            ]);
            $this->transactionManager->handleFailure($transaction, $e->getMessage());
            return false;
        }
    }
}
