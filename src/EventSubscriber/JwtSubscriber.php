<?php

namespace App\EventSubscriber;

use App\Entity\OnepayLoginHistory;
use App\Entity\OnepayUser;
use App\Service\SecurityLogger;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Event\AuthenticationSuccessEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Event\AuthenticationFailureEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class JwtSubscriber implements EventSubscriberInterface
{
    private EntityManagerInterface $entityManager;
    private SecurityLogger $securityLogger;

    public function __construct(
        EntityManagerInterface $entityManager,
        SecurityLogger $securityLogger
    ) {
        $this->entityManager = $entityManager;
        $this->securityLogger = $securityLogger;
    }

    public function onAuthenticationSuccess(AuthenticationSuccessEvent $event): void
    {
        $user = $event->getUser();
        $request = $event->getRequest();
        
        // Vérifier si l'utilisateur est une instance de OnepayUser
        if (!$user instanceof OnepayUser) {
            return;
        }

        try {
            // Créer un historique de connexion
            $loginHistory = new OnepayLoginHistory();
            $loginHistory->setUser($user);
            $loginHistory->setLoginDate(new \DateTime());
            $loginHistory->setIpAddress($request->getClientIp());
            $loginHistory->setUserAgent($request->headers->get('User-Agent'));
            $loginHistory->setIsSuccessful(true);
            
            $this->entityManager->persist($loginHistory);
            $this->entityManager->flush();

            // Logger l'événement de sécurité
            $this->securityLogger->logTentativeConnexion(
                $request,
                $user,
                true,
                [
                    'user_id' => $user->getId(),
                    'email' => $user->getEmail(),
                    'roles' => $user->getRoles()
                ]
            );
        } catch (\Exception $e) {
            // Logger l'erreur mais continuer
            $this->securityLogger->logEvenementSecurite(
                'Erreur lors de l\'enregistrement de l\'historique de connexion',
                ['error' => $e->getMessage()],
                'error'
            );
        }

        // Ajouter les données de l'utilisateur à la réponse
        $data = $event->getData();
        $data['user'] = [
            'email' => $user->getEmail(),
            'firstName' => $user->getFirstName(),
            'lastName' => $user->getLastName(),
            'phoneNumber' => $user->getPhoneNumber(),
            'roles' => $user->getRoles(),
            'isVerified' => $user->isVerified()
        ];

        $event->setData($data);
    }

    private function verifierTentativesMultiples(string $ip, string $credentials): void
    {
        $recentFailedAttempts = $this->entityManager->getRepository(OnepayLoginHistory::class)
            ->createQueryBuilder('h')
            ->where('h.ipAddress = :ip')
            ->andWhere('h.isSuccessful = :success')
            ->andWhere('h.loginAt >= :timeLimit')
            ->setParameters([
                'ip' => $ip,
                'success' => false,
                'timeLimit' => new \DateTime('-15 minutes')
            ])
            ->getQuery()
            ->getResult();

        if (count($recentFailedAttempts) >= 5) {
            $this->securityLogger->logAlerteSecurite(
                'Détection de tentatives multiples de connexion échouées',
                [
                    'ip' => $ip,
                    'credentials' => $credentials,
                    'nombre_tentatives' => count($recentFailedAttempts),
                    'periode' => '15 minutes'
                ]
            );
        }
    }

    public function onAuthenticationFailure(AuthenticationFailureEvent $event): void
    {
        $request = $event->getRequest();
        $exception = $event->getException();

        // Logger l'échec d'authentification
        $this->securityLogger->logTentativeConnexion(
            $request,
            null,
            false,
            [
                'erreur' => $exception->getMessage(),
                'type_erreur' => get_class($exception)
            ]
        );

        try {
            // Créer l'historique de connexion échouée
            $loginHistory = new OnepayLoginHistory();
            $loginHistory->setLoginDate(new \DateTime());
            $loginHistory->setIpAddress($request->getClientIp());
            $loginHistory->setUserAgent($request->headers->get('User-Agent'));
            $loginHistory->setIsSuccessful(false);
            $loginHistory->setFailureReason($exception->getMessage());

            $this->entityManager->persist($loginHistory);
            $this->entityManager->flush();

            // Vérifier les tentatives multiples
            $this->verifierTentativesMultiples($request->getClientIp(), $request->get('email', 'inconnu'));
        } catch (\Exception $e) {
            $this->securityLogger->logEvenementSecurite(
                'Erreur lors de l\'enregistrement de l\'échec de connexion',
                ['error' => $e->getMessage()],
                'error'
            );
        }
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'lexik_jwt_authentication.on_authentication_success' => 'onAuthenticationSuccess',
            'lexik_jwt_authentication.on_authentication_failure' => 'onAuthenticationFailure'
        ];
    }
}
