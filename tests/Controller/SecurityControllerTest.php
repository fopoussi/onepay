<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use App\Entity\OnepayUser;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;

class SecurityControllerTest extends WebTestCase
{
    private $client;
    private $entityManager;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->entityManager = self::getContainer()->get(EntityManagerInterface::class);
    }

    public function testInscription(): void
    {
        $userData = [
            'email' => 'test@example.com',
            'password' => 'password123',
            'firstName' => 'Test',
            'lastName' => 'User',
            'phoneNumber' => '237612345678'
        ];

        $this->client->request(
            'POST',
            '/api/register',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($userData)
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);
        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('token', $response);
    }

    public function testConnexion(): void
    {
        // Créer un utilisateur de test
        $user = new OnepayUser();
        $user->setEmail('test@example.com');
        $user->setPassword(password_hash('password123', PASSWORD_BCRYPT));
        $user->setFirstName('Test');
        $user->setLastName('User');
        $user->setPhoneNumber('237612345678');
        $user->setRoles(['ROLE_USER']);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $loginData = [
            'username' => 'test@example.com',
            'password' => 'password123'
        ];

        $this->client->request(
            'POST',
            '/api/login',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($loginData)
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('token', $response);
    }

    public function testConnexionEchouee(): void
    {
        $loginData = [
            'username' => 'test@example.com',
            'password' => 'mauvaisMotDePasse'
        ];

        $this->client->request(
            'POST',
            '/api/login',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($loginData)
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function testConnexionGoogle(): void
    {
        $this->client->request('GET', '/connect/google');
        
        $this->assertResponseStatusCodeSame(Response::HTTP_FOUND);
        $this->assertResponseRedirects();
    }

    public function testDeconnexion(): void
    {
        // Créer et connecter un utilisateur
        $user = new OnepayUser();
        $user->setEmail('test@example.com');
        $user->setPassword(password_hash('password123', PASSWORD_BCRYPT));
        $user->setRoles(['ROLE_USER']);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $this->client->loginUser($user);

        $this->client->request('POST', '/api/logout');
        
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }

    public function testReinitialisationMotDePasse(): void
    {
        // Test de demande de réinitialisation
        $this->client->request(
            'POST',
            '/api/reset-password/request',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['email' => 'test@example.com'])
        );

        $this->assertResponseIsSuccessful();
    }

    public function testValidationEmail(): void
    {
        $user = new OnepayUser();
        $user->setEmail('test@example.com');
        $user->setPassword(password_hash('password123', PASSWORD_BCRYPT));
        $user->setRoles(['ROLE_USER']);
        $user->setConfirmationToken('token123');

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $this->client->request('GET', '/api/verify/email/token123');
        
        $this->assertResponseIsSuccessful();
    }

    public function testValidationEmailTokenInvalide(): void
    {
        $this->client->request('GET', '/api/verify/email/token_invalide');
        
        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testReinitialisationMotDePasseTokenInvalide(): void
    {
        $this->client->request(
            'POST',
            '/api/reset-password/reset',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'token' => 'token_invalide',
                'password' => 'nouveauPassword123'
            ])
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testInscriptionDonneesInvalides(): void
    {
        $userData = [
            'email' => 'email_invalide',
            'password' => '123', // Mot de passe trop court
            'firstName' => '',
            'lastName' => '',
            'phoneNumber' => '123' // Numéro invalide
        ];

        $this->client->request(
            'POST',
            '/api/register',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($userData)
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('violations', $response);
    }

    public function testConnexionCompteNonVerifie(): void
    {
        $user = new OnepayUser();
        $user->setEmail('nonverifie@example.com');
        $user->setPassword(password_hash('password123', PASSWORD_BCRYPT));
        $user->setFirstName('Test');
        $user->setLastName('User');
        $user->setPhoneNumber('237612345678');
        $user->setRoles(['ROLE_USER']);
        $user->setIsVerified(false);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $loginData = [
            'username' => 'nonverifie@example.com',
            'password' => 'password123'
        ];

        $this->client->request(
            'POST',
            '/api/login',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($loginData)
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertStringContainsString('compte non vérifié', $response['message']);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        
        // Nettoyer la base de données
        $this->entityManager->createQuery('DELETE FROM App\Entity\OnepayUser')->execute();
        $this->entityManager->createQuery('DELETE FROM App\Entity\OnepayLoginHistory')->execute();
    }
}
