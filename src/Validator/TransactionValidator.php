<?php

namespace App\Validator;

use App\Entity\Transaction;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\Cache\CacheInterface;

class TransactionValidator extends ConstraintValidator
{
    private const DAILY_LIMIT = 2000000;   // 2 millions FCFA par jour
    private const MONTHLY_LIMIT = 10000000; // 10 millions FCFA par mois
    private const MIN_AMOUNT = 500;        // 500 FCFA minimum
    private const MAX_AMOUNT = 500000;     // 500,000 FCFA maximum

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly CacheInterface $cache
    ) {}

    public function validate($transaction, Constraint $constraint): array
    {
        if (!$transaction instanceof Transaction) {
            throw new UnexpectedTypeException($transaction, Transaction::class);
        }

        $violations = [];

        // Validation du montant
        $amountViolations = $this->validateAmount($transaction);
        if (!empty($amountViolations)) {
            $violations = array_merge($violations, $amountViolations);
        }

        // Validation des numéros de téléphone
        $phoneViolations = $this->validatePhoneNumbers($transaction);
        if (!empty($phoneViolations)) {
            $violations = array_merge($violations, $phoneViolations);
        }

        // Validation des limites
        $limitViolations = $this->validateLimits($transaction);
        if (!empty($limitViolations)) {
            $violations = array_merge($violations, $limitViolations);
        }

        // Validation du compte source
        $accountViolations = $this->validateSourceAccount($transaction);
        if (!empty($accountViolations)) {
            $violations = array_merge($violations, $accountViolations);
        }

        return $violations;
    }

    private function validateAmount(Transaction $transaction): array
    {
        $violations = [];
        $amount = $transaction->getAmount();

        if ($amount < self::MIN_AMOUNT) {
            $violations[] = [
                'code' => 'AMOUNT_TOO_LOW',
                'message' => sprintf('Le montant minimum est de %s FCFA', self::MIN_AMOUNT),
                'parameters' => ['min' => self::MIN_AMOUNT]
            ];
        }

        if ($amount > self::MAX_AMOUNT) {
            $violations[] = [
                'code' => 'AMOUNT_TOO_HIGH',
                'message' => sprintf('Le montant maximum est de %s FCFA', self::MAX_AMOUNT),
                'parameters' => ['max' => self::MAX_AMOUNT]
            ];
        }

        return $violations;
    }

    private function validatePhoneNumbers(Transaction $transaction): array
    {
        $violations = [];
        $recipientNumber = $transaction->getRecipientNumber();

        // Validation du format (6XXXXXXXX)
        if (!preg_match('/^6[2,5-9][0-9]{7}$/', $recipientNumber)) {
            $violations[] = [
                'code' => 'INVALID_PHONE_FORMAT',
                'message' => 'Le numéro doit commencer par 6 et avoir 9 chiffres',
                'parameters' => ['number' => $recipientNumber]
            ];
            return $violations;
        }

        // Validation de l'opérateur selon le préfixe
        $prefix = substr($recipientNumber, 1, 1);
        $operator = $transaction->getOperator();
        $isValidOperator = match($prefix) {
            '5', '7', '8' => $operator === 'MTN',
            '9', '6' => $operator === 'ORANGE',
            '2' => $operator === 'CAMTEL',
            default => false
        };

        if (!$isValidOperator) {
            $violations[] = [
                'code' => 'INVALID_OPERATOR',
                'message' => 'L\'opérateur ne correspond pas au numéro de téléphone',
                'parameters' => [
                    'number' => $recipientNumber,
                    'operator' => $operator
                ]
            ];
        }

        return $violations;
    }

    private function validateLimits(Transaction $transaction): array
    {
        $violations = [];
        $userId = $transaction->getUser()->getId();
        $amount = $transaction->getAmount();

        // Vérification de la limite journalière
        $dailyTotal = $this->getDailyTransactionsTotal($userId);
        if ($dailyTotal + $amount > self::DAILY_LIMIT) {
            $violations[] = [
                'code' => 'DAILY_LIMIT_EXCEEDED',
                'message' => sprintf('Limite journalière de %s FCFA dépassée', self::DAILY_LIMIT),
                'parameters' => [
                    'limit' => self::DAILY_LIMIT,
                    'current' => $dailyTotal,
                    'requested' => $amount
                ]
            ];
        }

        // Vérification de la limite mensuelle
        $monthlyTotal = $this->getMonthlyTransactionsTotal($userId);
        if ($monthlyTotal + $amount > self::MONTHLY_LIMIT) {
            $violations[] = [
                'code' => 'MONTHLY_LIMIT_EXCEEDED',
                'message' => sprintf('Limite mensuelle de %s FCFA dépassée', self::MONTHLY_LIMIT),
                'parameters' => [
                    'limit' => self::MONTHLY_LIMIT,
                    'current' => $monthlyTotal,
                    'requested' => $amount
                ]
            ];
        }

        return $violations;
    }

    private function validateSourceAccount(Transaction $transaction): array
    {
        $violations = [];
        $sourceAccount = $transaction->getSourceAccount();

        if (!$sourceAccount) {
            $violations[] = [
                'code' => 'MISSING_SOURCE_ACCOUNT',
                'message' => 'Compte source non spécifié'
            ];
            return $violations;
        }

        if (!$sourceAccount->isVerified()) {
            $violations[] = [
                'code' => 'UNVERIFIED_ACCOUNT',
                'message' => 'Le compte source n\'est pas vérifié'
            ];
        }

        $totalAmount = $transaction->getAmount() + $transaction->getFees();
        if ($sourceAccount->getBalance() < $totalAmount) {
            $violations[] = [
                'code' => 'INSUFFICIENT_BALANCE',
                'message' => 'Solde insuffisant',
                'parameters' => [
                    'required' => $totalAmount,
                    'available' => $sourceAccount->getBalance()
                ]
            ];
        }

        return $violations;
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
}
