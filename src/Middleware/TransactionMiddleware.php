<?php

namespace App\Middleware;

use App\Message\ProcessTransactionMessage;
use App\Message\SyncBalanceMessage;
use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Middleware\MiddlewareInterface;
use Symfony\Component\Messenger\Middleware\StackInterface;
use Symfony\Component\Messenger\Stamp\ReceivedStamp;

class TransactionMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly Connection $connection,
        private readonly LoggerInterface $logger
    ) {}

    public function handle(Envelope $envelope, StackInterface $stack): Envelope
    {
        $message = $envelope->getMessage();

        // On ne gère que les messages liés aux transactions
        if (!$this->shouldHandleMessage($message)) {
            return $stack->next()->handle($envelope, $stack);
        }

        // Si le message a déjà été reçu, on ne démarre pas de nouvelle transaction
        if ($envelope->last(ReceivedStamp::class)) {
            return $stack->next()->handle($envelope, $stack);
        }

        // Démarrage de la transaction
        $this->connection->beginTransaction();

        try {
            $this->logger->info('Démarrage de la transaction DB', [
                'message_class' => get_class($message)
            ]);

            // Traitement du message
            $envelope = $stack->next()->handle($envelope, $stack);

            // Si tout s'est bien passé, on commit
            $this->connection->commit();
            $this->logger->info('Transaction DB validée', [
                'message_class' => get_class($message)
            ]);

            return $envelope;
        } catch (\Throwable $e) {
            // En cas d'erreur, on rollback
            if ($this->connection->isTransactionActive()) {
                $this->connection->rollBack();
            }

            $this->logger->error('Erreur lors de la transaction DB - Rollback effectué', [
                'message_class' => get_class($message),
                'error' => $e->getMessage()
            ]);

            throw $e;
        }
    }

    private function shouldHandleMessage(object $message): bool
    {
        return $message instanceof ProcessTransactionMessage ||
               $message instanceof SyncBalanceMessage;
    }
}
