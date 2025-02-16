<?php

namespace App\Service;

use App\Entity\Transaction;
use App\Entity\OnepayNotification;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Psr\Log\LoggerInterface;

class NotificationService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly MessageBusInterface $messageBus,
        private readonly LoggerInterface $logger
    ) {}

    public function sendTransactionNotification(Transaction $transaction): void
    {
        try {
            $notification = new OnepayNotification();
            $notification->setUser($transaction->getUser());
            $notification->setType('TRANSACTION_' . $transaction->getStatus());
            
            // Construction du message selon le type de transaction
            $message = match($transaction->getType()) {
                'MONEY_TRANSFER' => $this->buildTransferMessage($transaction),
                'CREDIT_PURCHASE' => $this->buildAirtimeMessage($transaction),
                default => $this->buildDefaultMessage($transaction)
            };
            
            $notification->setMessage($message);
            $notification->setData([
                'transactionId' => $transaction->getId(),
                'amount' => $transaction->getAmount(),
                'type' => $transaction->getType(),
                'status' => $transaction->getStatus(),
                'reference' => $transaction->getReference()
            ]);

            // Enregistrement de la notification
            $this->entityManager->persist($notification);
            $this->entityManager->flush();

            // Envoi asynchrone de la notification
            $this->messageBus->dispatch(new SendNotificationMessage($notification->getId()));

            $this->logger->info('Notification créée pour la transaction', [
                'transactionId' => $transaction->getId(),
                'notificationId' => $notification->getId(),
                'type' => $notification->getType()
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de la création de la notification', [
                'error' => $e->getMessage(),
                'transactionId' => $transaction->getId()
            ]);
        }
    }

    private function buildTransferMessage(Transaction $transaction): string
    {
        $amount = number_format($transaction->getAmount(), 0, ',', ' ');
        return match($transaction->getStatus()) {
            'COMPLETED' => sprintf(
                'Transfert de %s FCFA effectué vers le %s avec succès.',
                $amount,
                $transaction->getRecipientNumber()
            ),
            'FAILED' => sprintf(
                'Échec du transfert de %s FCFA vers le %s.',
                $amount,
                $transaction->getRecipientNumber()
            ),
            'PENDING' => sprintf(
                'Transfert de %s FCFA vers le %s en cours de traitement.',
                $amount,
                $transaction->getRecipientNumber()
            ),
            default => sprintf(
                'Mise à jour du transfert de %s FCFA vers le %s : %s',
                $amount,
                $transaction->getRecipientNumber(),
                $transaction->getStatus()
            )
        };
    }

    private function buildAirtimeMessage(Transaction $transaction): string
    {
        $amount = number_format($transaction->getAmount(), 0, ',', ' ');
        return match($transaction->getStatus()) {
            'COMPLETED' => sprintf(
                'Achat de crédit de %s FCFA effectué pour le %s avec succès.',
                $amount,
                $transaction->getRecipientNumber()
            ),
            'FAILED' => sprintf(
                'Échec de l\'achat de crédit de %s FCFA pour le %s.',
                $amount,
                $transaction->getRecipientNumber()
            ),
            'PENDING' => sprintf(
                'Achat de crédit de %s FCFA pour le %s en cours de traitement.',
                $amount,
                $transaction->getRecipientNumber()
            ),
            default => sprintf(
                'Mise à jour de l\'achat de crédit de %s FCFA pour le %s : %s',
                $amount,
                $transaction->getRecipientNumber(),
                $transaction->getStatus()
            )
        };
    }

    private function buildDefaultMessage(Transaction $transaction): string
    {
        $amount = number_format($transaction->getAmount(), 0, ',', ' ');
        return sprintf(
            'Transaction de %s FCFA - Statut : %s',
            $amount,
            $transaction->getStatus()
        );
    }
}

/**
 * Message pour l'envoi asynchrone des notifications
 */
class SendNotificationMessage
{
    public function __construct(
        public readonly string $notificationId
    ) {}
}
