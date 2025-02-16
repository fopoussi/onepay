<?php

namespace App\Security;

use App\Entity\OnepayUser;
use Doctrine\ORM\EntityManagerInterface;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use KnpU\OAuth2ClientBundle\Security\Authenticator\OAuth2Authenticator;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;

class GoogleAuthenticator extends OAuth2Authenticator implements AuthenticationEntryPointInterface
{
    private $clientRegistry;
    private $entityManager;
    private $jwtManager;

    public function __construct(
        ClientRegistry $clientRegistry,
        EntityManagerInterface $entityManager,
        JWTTokenManagerInterface $jwtManager
    ) {
        $this->clientRegistry = $clientRegistry;
        $this->entityManager = $entityManager;
        $this->jwtManager = $jwtManager;
    }

    public function supports(Request $request): ?bool
    {
        return $request->attributes->get('_route') === 'connect_google_check';
    }

    public function authenticate(Request $request): Passport
    {
        $client = $this->clientRegistry->getClient('google');
        $accessToken = $this->fetchAccessToken($client);

        return new SelfValidatingPassport(
            new UserBadge($accessToken->getToken(), function() use ($accessToken, $client) {
                /** @var \League\OAuth2\Client\Provider\GoogleUser $googleUser */
                $googleUser = $client->fetchUserFromToken($accessToken);

                $existingUser = $this->entityManager->getRepository(OnepayUser::class)
                    ->findOneBy(['googleId' => $googleUser->getId()]);

                if (!$existingUser) {
                    $existingUser = $this->entityManager->getRepository(OnepayUser::class)
                        ->findOneBy(['email' => $googleUser->getEmail()]);

                    if (!$existingUser) {
                        $existingUser = new OnepayUser();
                        $existingUser->setEmail($googleUser->getEmail());
                        $existingUser->setName($googleUser->getName());
                        $existingUser->setPhoneNumber(''); // À remplir plus tard
                        $existingUser->setCreatedAt(new \DateTime());
                    }

                    $existingUser->setGoogleId($googleUser->getId());
                    $existingUser->setAvatar($googleUser->getAvatar());
                    
                    $this->entityManager->persist($existingUser);
                    $this->entityManager->flush();
                }

                return $existingUser;
            })
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        $user = $token->getUser();
        $jwtToken = $this->jwtManager->create($user);

        // Rediriger vers une page avec le token JWT
        return new RedirectResponse('/auth-success?token=' . $jwtToken);
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        $message = strtr($exception->getMessageKey(), $exception->getMessageData());

        return new Response('Échec de l\'authentification: ' . $message, Response::HTTP_UNAUTHORIZED);
    }

    public function start(Request $request, AuthenticationException $authException = null): Response
    {
        return new RedirectResponse(
            '/connect/google',
            Response::HTTP_TEMPORARY_REDIRECT
        );
    }
}
