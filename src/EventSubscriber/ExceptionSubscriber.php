<?php

namespace App\EventSubscriber;

use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;

class ExceptionSubscriber implements EventSubscriberInterface
{
    private LoggerInterface $erreurLogger;
    private string $environment;

    public function __construct(LoggerInterface $erreurLogger, string $environment)
    {
        $this->erreurLogger = $erreurLogger;
        $this->environment = $environment;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::EXCEPTION => ['onKernelException', 0],
        ];
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();
        $request = $event->getRequest();

        // Contexte de base pour tous les types d'erreurs
        $contexte = [
            'url' => $request->getUri(),
            'methode' => $request->getMethod(),
            'ip' => $request->getClientIp(),
            'user_agent' => $request->headers->get('User-Agent'),
            'classe_exception' => get_class($exception),
            'fichier' => $exception->getFile(),
            'ligne' => $exception->getLine(),
            'timestamp' => (new \DateTime())->format('Y-m-d H:i:s'),
        ];

        // Ajouter la trace si on est en environnement de dev
        if ($this->environment === 'dev') {
            $contexte['trace'] = $exception->getTraceAsString();
        }

        // Gérer différemment selon le type d'exception
        if ($exception instanceof HttpExceptionInterface) {
            $this->handleHttpException($exception, $contexte);
        } elseif ($exception instanceof AuthenticationException) {
            $this->handleAuthenticationException($exception, $contexte);
        } else {
            $this->handleGenericException($exception, $contexte);
        }
    }

    private function handleHttpException(HttpExceptionInterface $exception, array $contexte): void
    {
        $contexte['status_code'] = $exception->getStatusCode();
        $contexte['headers'] = $exception->getHeaders();

        if ($exception->getStatusCode() >= 500) {
            $this->erreurLogger->error(
                'Erreur serveur: ' . $exception->getMessage(),
                $contexte
            );
        } else {
            $this->erreurLogger->warning(
                'Erreur client: ' . $exception->getMessage(),
                $contexte
            );
        }
    }

    private function handleAuthenticationException(AuthenticationException $exception, array $contexte): void
    {
        $this->erreurLogger->warning(
            'Erreur d\'authentification: ' . $exception->getMessage(),
            $contexte
        );
    }

    private function handleGenericException(\Throwable $exception, array $contexte): void
    {
        $this->erreurLogger->critical(
            'Erreur système: ' . $exception->getMessage(),
            $contexte
        );
    }
}
