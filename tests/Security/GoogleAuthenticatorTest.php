<?php

namespace App\Tests\Security;

use App\Entity\OnepayUser;
use App\Security\GoogleAuthenticator;
use Doctrine\ORM\EntityManagerInterface;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use KnpU\OAuth2ClientBundle\Client\OAuth2Client;
use League\OAuth2\Client\Provider\GoogleUser;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;

class GoogleAuthenticatorTest extends TestCase
{
    private $clientRegistry;
    private $entityManager;
    private $router;
    private $googleClient;
    private $authenticator;

    protected function setUp(): void
    {
        $this->clientRegistry = $this->createMock(ClientRegistry::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->router = $this->createMock(RouterInterface::class);
        $this->googleClient = $this->createMock(OAuth2Client::class);
        
        $this->authenticator = new GoogleAuthenticator(
            $this->clientRegistry,
            $this->entityManager,
            $this->router
        );
    }

    public function testSupports(): void
    {
        $request = new Request([], [], ['_route' => 'connect_google_check']);
        
        $this->assertTrue($this->authenticator->supports($request));
        
        $request = new Request([], [], ['_route' => 'other_route']);
        $this->assertFalse($this->authenticator->supports($request));
    }

    public function testAuthenticate(): void
    {
        // Créer une requête mock
        $request = new Request();
        $session = $this->createMock(SessionInterface::class);
        $request->setSession($session);

        // Configurer le mock de GoogleClient
        $this->clientRegistry->expects($this->once())
            ->method('getClient')
            ->with('google')
            ->willReturn($this->googleClient);

        // Créer un mock GoogleUser
        $googleUser = $this->createMock(GoogleUser::class);
        $googleUser->expects($this->any())
            ->method('getEmail')
            ->willReturn('test@gmail.com');
        $googleUser->expects($this->any())
            ->method('getName')
            ->willReturn('Test User');

        // Configurer le client pour retourner le GoogleUser
        $this->googleClient->expects($this->once())
            ->method('fetchUserFromToken')
            ->willReturn($googleUser);

        // Configurer l'EntityManager pour rechercher l'utilisateur existant
        $existingUser = new OnepayUser();
        $existingUser->setEmail('test@gmail.com');
        
        $this->entityManager->expects($this->once())
            ->method('getRepository')
            ->willReturnSelf();
            
        $this->entityManager->expects($this->once())
            ->method('findOneBy')
            ->with(['email' => 'test@gmail.com'])
            ->willReturn($existingUser);

        // Exécuter l'authentification
        $passport = $this->authenticator->authenticate($request);
        
        // Vérifier le résultat
        $this->assertInstanceOf(UserBadge::class, $passport->getBadge(UserBadge::class));
    }

    public function testAuthenticateNewUser(): void
    {
        // Créer une requête mock
        $request = new Request();
        $session = $this->createMock(SessionInterface::class);
        $request->setSession($session);

        // Configurer le mock de GoogleClient
        $this->clientRegistry->expects($this->once())
            ->method('getClient')
            ->with('google')
            ->willReturn($this->googleClient);

        // Créer un mock GoogleUser
        $googleUser = $this->createMock(GoogleUser::class);
        $googleUser->expects($this->any())
            ->method('getEmail')
            ->willReturn('newuser@gmail.com');
        $googleUser->expects($this->any())
            ->method('getName')
            ->willReturn('New User');

        // Configurer le client pour retourner le GoogleUser
        $this->googleClient->expects($this->once())
            ->method('fetchUserFromToken')
            ->willReturn($googleUser);

        // Configurer l'EntityManager pour un nouvel utilisateur
        $this->entityManager->expects($this->once())
            ->method('getRepository')
            ->willReturnSelf();
            
        $this->entityManager->expects($this->once())
            ->method('findOneBy')
            ->with(['email' => 'newuser@gmail.com'])
            ->willReturn(null);

        // Vérifier que persist et flush sont appelés pour le nouvel utilisateur
        $this->entityManager->expects($this->once())
            ->method('persist')
            ->with($this->isInstanceOf(OnepayUser::class));
            
        $this->entityManager->expects($this->once())
            ->method('flush');

        // Exécuter l'authentification
        $passport = $this->authenticator->authenticate($request);
        
        // Vérifier le résultat
        $this->assertInstanceOf(UserBadge::class, $passport->getBadge(UserBadge::class));
    }

    public function testOnAuthenticationFailure(): void
    {
        $request = new Request();
        $exception = new AuthenticationException('Test failure');

        $response = $this->authenticator->onAuthenticationFailure($request, $exception);
        
        $this->assertEquals(401, $response->getStatusCode());
        $this->assertStringContainsString('Authentication failed', $response->getContent());
    }

    public function testOnAuthenticationSuccess(): void
    {
        $request = new Request();
        $token = $this->createMock('Symfony\Component\Security\Core\Authentication\Token\TokenInterface');
        $firewallName = 'main';

        $this->router->expects($this->once())
            ->method('generate')
            ->with('app_homepage')
            ->willReturn('/');

        $response = $this->authenticator->onAuthenticationSuccess($request, $token, $firewallName);
        
        $this->assertEquals(302, $response->getStatusCode());
        $this->assertEquals('/', $response->getTargetUrl());
    }

    public function testAuthenticateAvecErreurGoogle(): void
    {
        $request = new Request();
        $session = $this->createMock(SessionInterface::class);
        $request->setSession($session);

        $this->clientRegistry->expects($this->once())
            ->method('getClient')
            ->with('google')
            ->willThrow(new \League\OAuth2\Client\Provider\Exception\IdentityProviderException(
                'Erreur d\'authentification Google',
                0,
                []
            ));

        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage('Erreur d\'authentification Google');

        $this->authenticator->authenticate($request);
    }

    public function testAuthenticateAvecUtilisateurExistantMaisNonGoogle(): void
    {
        $request = new Request();
        $session = $this->createMock(SessionInterface::class);
        $request->setSession($session);

        // Configuration du mock GoogleClient
        $this->clientRegistry->expects($this->once())
            ->method('getClient')
            ->with('google')
            ->willReturn($this->googleClient);

        // Mock GoogleUser
        $googleUser = $this->createMock(GoogleUser::class);
        $googleUser->expects($this->any())
            ->method('getEmail')
            ->willReturn('existant@example.com');
        $googleUser->expects($this->any())
            ->method('getName')
            ->willReturn('Utilisateur Existant');

        // Configuration du client
        $this->googleClient->expects($this->once())
            ->method('fetchUserFromToken')
            ->willReturn($googleUser);

        // Simuler un utilisateur existant non-Google
        $existingUser = new OnepayUser();
        $existingUser->setEmail('existant@example.com');
        $existingUser->setGoogleId(null); // Pas d'ID Google

        $this->entityManager->expects($this->once())
            ->method('getRepository')
            ->willReturnSelf();
            
        $this->entityManager->expects($this->once())
            ->method('findOneBy')
            ->with(['email' => 'existant@example.com'])
            ->willReturn($existingUser);

        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage('Compte existant non lié à Google');

        $this->authenticator->authenticate($request);
    }

    public function testStart(): void
    {
        $request = new Request();
        
        $this->router->expects($this->once())
            ->method('generate')
            ->with('connect_google')
            ->willReturn('/connect/google');

        $response = $this->authenticator->start($request);
        
        $this->assertInstanceOf(\Symfony\Component\HttpFoundation\RedirectResponse::class, $response);
        $this->assertEquals('/connect/google', $response->getTargetUrl());
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->clientRegistry = null;
        $this->entityManager = null;
        $this->router = null;
        $this->googleClient = null;
        $this->authenticator = null;
    }
}
