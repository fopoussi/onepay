<?php

namespace App\Tests\EventSubscriber;

use App\Entity\OnepayUser;
use App\EventSubscriber\JwtSubscriber;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Event\AuthenticationSuccessEvent;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\User\UserInterface;

class JwtSubscriberTest extends TestCase
{
    private $entityManager;
    private $subscriber;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->subscriber = new JwtSubscriber($this->entityManager);
    }

    public function testOnAuthenticationSuccess(): void
    {
        // Créer un utilisateur fictif
        $user = new OnepayUser();
        $user->setEmail('test@example.com');
        $user->setFirstName('Test');
        $user->setLastName('User');
        $user->setPhoneNumber('237612345678');

        // Créer un événement de succès d'authentification
        $data = ['token' => 'fake_jwt_token'];
        $event = new AuthenticationSuccessEvent($data, $user, new Response());

        // Configurer le comportement attendu de l'EntityManager
        $this->entityManager->expects($this->once())
            ->method('persist')
            ->with($this->isInstanceOf(\App\Entity\OnepayLoginHistory::class));

        $this->entityManager->expects($this->once())
            ->method('flush');

        // Exécuter l'événement
        $this->subscriber->onAuthenticationSuccess($event);

        // Vérifier que les données de l'utilisateur ont été ajoutées
        $eventData = $event->getData();
        $this->assertArrayHasKey('user', $eventData);
        $this->assertEquals('test@example.com', $eventData['user']['email']);
        $this->assertEquals('Test', $eventData['user']['firstName']);
        $this->assertEquals('User', $eventData['user']['lastName']);
    }

    public function testOnAuthenticationSuccessWithInvalidUser(): void
    {
        // Créer un mock d'utilisateur qui n'est pas un OnepayUser
        $invalidUser = $this->createMock(UserInterface::class);

        // Créer un événement avec l'utilisateur invalide
        $data = ['token' => 'fake_jwt_token'];
        $event = new AuthenticationSuccessEvent($data, $invalidUser, new Response());

        // L'EntityManager ne devrait pas être appelé
        $this->entityManager->expects($this->never())
            ->method('persist');

        $this->entityManager->expects($this->never())
            ->method('flush');

        // Exécuter l'événement
        $this->subscriber->onAuthenticationSuccess($event);

        // Vérifier que les données originales sont inchangées
        $eventData = $event->getData();
        $this->assertArrayNotHasKey('user', $eventData);
        $this->assertEquals(['token' => 'fake_jwt_token'], $eventData);
    }

    public function testGetSubscribedEvents(): void
    {
        $events = JwtSubscriber::getSubscribedEvents();
        
        $this->assertIsArray($events);
        $this->assertArrayHasKey('lexik_jwt_authentication.on_authentication_success', $events);
        $this->assertEquals('onAuthenticationSuccess', $events['lexik_jwt_authentication.on_authentication_success']);
    }

    public function testOnAuthenticationSuccessAvecDonneesSupplementaires(): void
    {
        // Créer un utilisateur avec des données supplémentaires
        $user = new OnepayUser();
        $user->setEmail('test@example.com');
        $user->setFirstName('Test');
        $user->setLastName('User');
        $user->setPhoneNumber('237612345678');
        $user->setRoles(['ROLE_USER', 'ROLE_ADMIN']);
        $user->setIsVerified(true);

        // Créer un événement avec des données supplémentaires
        $data = [
            'token' => 'fake_jwt_token',
            'refresh_token' => 'fake_refresh_token',
            'extra' => 'some_extra_data'
        ];
        $event = new AuthenticationSuccessEvent($data, $user, new Response());

        // Configurer l'EntityManager
        $this->entityManager->expects($this->once())
            ->method('persist')
            ->with($this->callback(function ($loginHistory) {
                return $loginHistory instanceof \App\Entity\OnepayLoginHistory
                    && $loginHistory->getUser() instanceof OnepayUser;
            }));

        $this->entityManager->expects($this->once())
            ->method('flush');

        // Exécuter l'événement
        $this->subscriber->onAuthenticationSuccess($event);

        // Vérifier les données de l'utilisateur
        $eventData = $event->getData();
        $this->assertArrayHasKey('user', $eventData);
        $this->assertEquals('test@example.com', $eventData['user']['email']);
        $this->assertEquals('Test', $eventData['user']['firstName']);
        $this->assertEquals('User', $eventData['user']['lastName']);
        $this->assertEquals('237612345678', $eventData['user']['phoneNumber']);
        $this->assertEquals(['ROLE_USER', 'ROLE_ADMIN'], $eventData['user']['roles']);
        $this->assertTrue($eventData['user']['isVerified']);
        
        // Vérifier que les données originales sont préservées
        $this->assertEquals('fake_jwt_token', $eventData['token']);
        $this->assertEquals('fake_refresh_token', $eventData['refresh_token']);
        $this->assertEquals('some_extra_data', $eventData['extra']);
    }

    public function testOnAuthenticationSuccessAvecErreurPersistance(): void
    {
        // Créer un utilisateur de test
        $user = new OnepayUser();
        $user->setEmail('test@example.com');
        $user->setFirstName('Test');
        $user->setLastName('User');

        // Créer un événement
        $data = ['token' => 'fake_jwt_token'];
        $event = new AuthenticationSuccessEvent($data, $user, new Response());

        // Simuler une erreur lors de la persistance
        $this->entityManager->expects($this->once())
            ->method('persist')
            ->willThrowException(new \Exception('Erreur de persistance'));

        // L'erreur ne devrait pas affecter la réponse
        $this->subscriber->onAuthenticationSuccess($event);

        // Vérifier que les données de l'utilisateur sont toujours ajoutées
        $eventData = $event->getData();
        $this->assertArrayHasKey('user', $eventData);
        $this->assertEquals('test@example.com', $eventData['user']['email']);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->entityManager = null;
        $this->subscriber = null;
    }
}
