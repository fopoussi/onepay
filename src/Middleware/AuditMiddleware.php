<?php

namespace App\Middleware;

use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Middleware\MiddlewareInterface;
use Symfony\Component\Messenger\Middleware\StackInterface;

class AuditMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly LoggerInterface $logger
    ) {}

    public function handle(Envelope $envelope, StackInterface $stack): Envelope
    {
        $message = $envelope->getMessage();
        $context = $this->prepareAuditContext($envelope);

        // Logging avant traitement
        $this->logger->info('Début du traitement du message', $context);

        try {
            // Traitement du message
            $envelope = $stack->next()->handle($envelope, $stack);

            // Logging après traitement réussi
            $this->logger->info('Message traité avec succès', $context);

            // Enregistrement de l'audit
            $this->saveAuditLog($message, $context, true);

            return $envelope;
        } catch (\Throwable $e) {
            // Logging de l'erreur
            $this->logger->error('Erreur lors du traitement du message', array_merge($context, [
                'error' => $e->getMessage()
            ]));

            // Enregistrement de l'audit avec l'erreur
            $this->saveAuditLog($message, $context, false, $e->getMessage());

            throw $e;
        }
    }

    private function prepareAuditContext(Envelope $envelope): array
    {
        $message = $envelope->getMessage();
        return [
            'message_id' => spl_object_hash($message),
            'message_class' => get_class($message),
            'timestamp' => (new \DateTime())->format('c')
        ];
    }

    private function saveAuditLog(object $message, array $context, bool $success, ?string $error = null): void
    {
        try {
            $auditLog = new \App\Entity\OnepayAuditLog();
            $auditLog->setMessageClass(get_class($message));
            $auditLog->setMessageData(json_encode($context));
            $auditLog->setSuccess($success);
            $auditLog->setError($error);
            $auditLog->setCreatedAt(new \DateTimeImmutable());

            $this->entityManager->persist($auditLog);
            $this->entityManager->flush();
        } catch (\Throwable $e) {
            $this->logger->error('Erreur lors de l\'enregistrement de l\'audit', [
                'error' => $e->getMessage()
            ]);
        }
    }
}
