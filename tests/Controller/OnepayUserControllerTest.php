<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use App\Entity\OnepayUser;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;

class OnepayUserControllerTest extends WebTestCase
{
    private $client;
    private $entityManager;
    private $testUser;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->entityManager = self::getContainer()->get(EntityManagerInterface::class);

        // Créer un utilisateur de test
        $this->testUser = new OnepayUser();
        $this->testUser->setEmail('test@example.com');
        $this->testUser->setPassword(password_hash('password123', PASSWORD_BCRYPT));
        $this->testUser->setFirstName('Test');
        $this->testUser->setLastName('User');
        $this->testUser->setPhoneNumber('237612345678');
        $this->testUser->setRoles(['ROLE_USER']);

        $this->entityManager->persist($this->testUser);
        $this->entityManager->flush();

        // Connecter l'utilisateur
        $this->client->loginUser($this->testUser);
    }

    public function testObtenirProfil(): void
    {
        $this->client->request(
            'GET',
            '/api/users/profile',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json']
        );

        $this->assertResponseIsSuccessful();
        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('test@example.com', $response['email']);
        $this->assertEquals('Test', $response['firstName']);
    }

    public function testMettreAJourProfil(): void
    {
        $updateData = [
            'firstName' => 'Updated',
            'lastName' => 'Name',
            'phoneNumber' => '237687654321'
        ];

        $this->client->request(
            'PUT',
            '/api/users/profile',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($updateData)
        );

        $this->assertResponseIsSuccessful();
        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('Updated', $response['firstName']);
        $this->assertEquals('Name', $response['lastName']);
    }

    public function testChangerMotDePasse(): void
    {
        $passwordData = [
            'currentPassword' => 'password123',
            'newPassword' => 'newPassword123'
        ];

        $this->client->request(
            'POST',
            '/api/users/change-password',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($passwordData)
        );

        $this->assertResponseIsSuccessful();
    }

    public function testObtenirHistoriqueConnexions(): void
    {
        $this->client->request(
            'GET',
            '/api/users/login-history',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json']
        );

        $this->assertResponseIsSuccessful();
        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertIsArray($response);
    }

    public function testObtenirNotifications(): void
    {
        $this->client->request(
            'GET',
            '/api/users/notifications',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json']
        );

        $this->assertResponseIsSuccessful();
        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertIsArray($response);
    }

    public function testMarquerNotificationCommeLue(): void
    {
        // Créer une notification de test si nécessaire
        $this->client->request(
            'POST',
            '/api/users/notifications/1/read',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json']
        );

        $this->assertResponseIsSuccessful();
    }

    public function testVerifierAccesNonAutorise(): void
    {
        // Créer un autre utilisateur
        $otherUser = new OnepayUser();
        $otherUser->setEmail('other@example.com');
        $otherUser->setPassword(password_hash('password123', PASSWORD_BCRYPT));
        $otherUser->setRoles(['ROLE_USER']);

        $this->entityManager->persist($otherUser);
        $this->entityManager->flush();

        // Essayer d'accéder au profil d'un autre utilisateur
        $this->client->request(
            'GET',
            '/api/users/' . $otherUser->getId(),
            [],
            [],
            ['CONTENT_TYPE' => 'application/json']
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testRechercherUtilisateurs(): void
    {
        // Créer un utilisateur admin pour le test
        $adminUser = new OnepayUser();
        $adminUser->setEmail('admin@example.com');
        $adminUser->setPassword(password_hash('admin123', PASSWORD_BCRYPT));
        $adminUser->setRoles(['ROLE_ADMIN']);

        $this->entityManager->persist($adminUser);
        $this->entityManager->flush();

        $this->client->loginUser($adminUser);

        $this->client->request(
            'GET',
            '/api/users/search?query=test',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json']
        );

        $this->assertResponseIsSuccessful();
        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertIsArray($response);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        // Nettoyer la base de données après chaque test
        $this->entityManager->createQuery('DELETE FROM App\Entity\OnepayUser')->execute();
    }
}
