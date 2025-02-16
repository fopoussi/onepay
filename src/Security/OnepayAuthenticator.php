<?php

namespace App\Security;

use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use KnpU\OAuth2ClientBundle\Security\Authenticator\OAuth2Authenticator;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;

class OnepayAuthenticator extends OAuth2Authenticator implements AuthenticationEntryPointInterface
{
    private ClientRegistry $clientRegistry;
    private RouterInterface $router;

    public function __construct(ClientRegistry $clientRegistry, RouterInterface $router)
    {
        $this->clientRegistry = $clientRegistry;
        $this->router = $router;
    }

    /**
     * Détermine si cet authentificateur doit prendre en charge la requête actuelle.
     */
    public function supports(Request $request): ?bool
    {
        // Authentifie uniquement les requêtes vers la route spécifiée
        return $request->attributes->get('_route') === 'connect_onepay_check';
    }

    /**
     * Authentifie l'utilisateur en utilisant le jeton d'accès obtenu.
     */
    public function authenticate(Request $request): Passport
    {
        $client = $this->clientRegistry->getClient('onepay');
        $accessToken = $this->fetchAccessToken($client);

        return new SelfValidatingPassport(
            new UserBadge($accessToken->getToken(), function () use ($accessToken, $client) {
                $onepayUser = $client->fetchUserFromToken($accessToken);

                // Logique pour créer ou récupérer l'utilisateur depuis votre base de données
                // Exemple : Utiliser $onepayUser->getEmail() pour retrouver un utilisateur existant
                return $onepayUser;
            })
        );
    }

    /**
     * Actions à effectuer après une authentification réussie.
     */
    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        // Redirige l'utilisateur après succès
        return new RedirectResponse($this->router->generate('app_dashboard'));
    }

    /**
     * Actions à effectuer en cas d'échec d'authentification.
     */
    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        return new Response('Échec de l\'authentification avec Onepay.', Response::HTTP_FORBIDDEN);
    }

    /**
     * Redirige les utilisateurs non authentifiés vers la page de connexion.
     */
    public function start(Request $request, AuthenticationException $authException = null): Response
    {
             // Redirige les utilisateurs non authentifiés vers la page de connexion OAuth2
        return new RedirectResponse($this->router->generate('connect_onepay'));
    }
}
