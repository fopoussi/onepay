<?php

namespace App\MessageHandler;

use App\Message\ProcessTransactionMessage;
use App\Entity\Transaction;
use App\Service\TransactionManager;
use App\Service\MobileMoneyService;
use App\Service\OrangeMoneyService;
use App\Service\MtnMomoService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;
use Symfony\Component\Messenger\Exception\UnrecoverableMessageHandlingException;

class ProcessTransactionMessageHandler implements MessageHandlerInterface
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly TransactionManager $transactionManager,
        private readonly OrangeMoneyService $orangeMoneyService,
        private readonly MtnMomoService $mtnMomoService,
        private readonly LoggerInterface $logger
    ) {}

    public function __invoke(ProcessTransactionMessage $message): void
    {
        $this->logger->info('Traitement de la transaction', [
            'transactionId' => $message->getTransactionId(),
            'action' => $message->getAction()
        ]);

        try {
            $transaction = $this->entityManager->getRepository(Transaction::class)
                ->find($message->getTransactionId());

            if (!$transaction) {
                throw new UnrecoverableMessageHandlingException(
                    sprintf('Transaction %s non trouvée', $message->getTransactionId())
                );
            }

            // Sélection du service approprié selon l'opérateur
            $service = $this->getServiceForTransaction($transaction);

            // Traitement selon l'action
            match ($message->getAction()) {
                'PROCESS' => $this->processTransaction($transaction, $service),
                'VERIFY' => $this->verifyTransaction($transaction, $service),
                'CANCEL' => $this->cancelTransaction($transaction),
                default => throw new UnrecoverableMessageHandlingException(
                    sprintf('Action %s non supportée', $message->getAction())
                )
            };

            $this->entityManager->flush();

        } catch (\Exception $e) {
            $this->logger->error('Erreur lors du traitement de la transaction', [
                'error' => $e->getMessage(),
                'transactionId' => $message->getTransactionId()
            ]);

            if (!$e instanceof UnrecoverableMessageHandlingException) {
                throw $e; // Permet le retry
            }
        }
    }

    private function getServiceForTransaction(Transaction $transaction): MobileMoneyService
    {
        return match ($transaction->getSourceAccount()->getProvider()) {
            'ORANGE_MONEY' => $this->orangeMoneyService,
            'MTN_MOMO' => $this->mtnMomoService,
            default => throw new UnrecoverableMessageHandlingException(
                sprintf('Provider %s non supporté', $transaction->getSourceAccount()->getProvider())
            )
        };
    }

    private function processTransaction(Transaction $transaction, MobileMoneyService $service): void
    {
        // Vérification des limites et du solde
        if (!$this->transactionManager->validateTransaction($transaction)) {
            throw new UnrecoverableMessageHandlingException('Transaction invalide');
        }

        // Traitement selon le type de transaction
        $success = match ($transaction->getType()) {
            'MONEY_TRANSFER' => $service->transferMoney($transaction),
            'CREDIT_PURCHASE' => $service->purchaseAirtime($transaction),
            default => throw new UnrecoverableMessageHandlingException(
                sprintf('Type de transaction %s non supporté', $transaction->getType())
            )
        };

        if (!$success) {
            throw new \Exception('Échec du traitement de la transaction');
        }
    }

    private function verifyTransaction(Transaction $transaction, MobileMoneyService $service): void
    {
        $status = $service->checkPaymentStatus($transaction->getReference());
        
        if ($status['status'] === 'COMPLETED') {
            $this->transactionManager->processTransaction($transaction);
        } elseif ($status['status'] === 'FAILED') {
            $this->transactionManager->handleFailure($transaction, $status['message'] ?? 'Échec de la transaction');
        }
        // Si PENDING, on laisse la transaction en attente pour une prochaine vérification
    }

    private function cancelTransaction(Transaction $transaction): void
    {
        if ($transaction->getStatus() !== 'PENDING') {
            throw new UnrecoverableMessageHandlingException(
                'Seules les transactions en attente peuvent être annulées'
            );
        }

        $this->transactionManager->handleFailure($transaction, 'Transaction annulée par le système');
    }
}
