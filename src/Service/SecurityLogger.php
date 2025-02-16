<?php

namespace App\Service;

use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\User\UserInterface;

class SecurityLogger
{
    private LoggerInterface $securiteLogger;

    public function __construct(LoggerInterface $securiteLogger)
    {
        $this->securiteLogger = $securiteLogger;
    }

    public function logTentativeConnexion(Request $request, ?UserInterface $user, bool $succes, array $contexteSupplementaire = []): void
    {
        $contexte = [
            'ip' => $request->getClientIp(),
            'user_agent' => $request->headers->get('User-Agent'),
            'utilisateur' => $user ? $user->getUserIdentifier() : 'inconnu',
            'succes' => $succes,
            'timestamp' => (new \DateTime())->format('Y-m-d H:i:s'),
            'methode' => $request->getMethod(),
            'url' => $request->getRequestUri(),
        ];

        $contexte = array_merge($contexte, $contexteSupplementaire);

        if ($succes) {
            $this->securiteLogger->info('Connexion réussie', $contexte);
        } else {
            $this->securiteLogger->warning('Tentative de connexion échouée', $contexte);
            
            // Vérifier les tentatives multiples
            $this->verifierTentativesMultiples($request->getClientIp(), $user ? $user->getUserIdentifier() : null);
        }
    }

    public function logEvenementSecurite(string $message, array $contexte = [], string $niveau = 'info'): void
    {
        $contexteEnrichi = array_merge($contexte, [
            'timestamp' => (new \DateTime())->format('Y-m-d H:i:s'),
        ]);

        switch ($niveau) {
            case 'error':
                $this->securiteLogger->error($message, $contexteEnrichi);
                break;
            case 'warning':
                $this->securiteLogger->warning($message, $contexteEnrichi);
                break;
            case 'critical':
                $this->securiteLogger->critical($message, $contexteEnrichi);
                break;
            default:
                $this->securiteLogger->info($message, $contexteEnrichi);
        }
    }

    public function logAlerteSecurite(string $message, array $contexte = []): void
    {
        $contexteEnrichi = array_merge($contexte, [
            'timestamp' => (new \DateTime())->format('Y-m-d H:i:s'),
            'type' => 'alerte_securite'
        ]);

        $this->securiteLogger->alert($message, $contexteEnrichi);
    }

    private function verifierTentativesMultiples(string $ip, ?string $utilisateur): void
    {
        // TODO: Implémenter la logique de vérification des tentatives multiples
        // Cette méthode pourrait être enrichie pour:
        // - Vérifier le nombre de tentatives récentes depuis la même IP
        // - Détecter les patterns suspects
        // - Déclencher des alertes si nécessaire
    }
}
