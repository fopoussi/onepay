namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use App\Entity\OnepayTransaction;
use App\Entity\OnepayUser;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;

class OnepayTransactionControllerTest extends WebTestCase
{
    private $client;
    private $entityManager;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->entityManager = self::getContainer()->get(EntityManagerInterface::class);
    }

    // Test pour récupérer toutes les transactions (ROLE_USER requis)
    public function testGetTransactions(): void
    {
        // Simuler un utilisateur avec le rôle ROLE_USER
        $user = new OnepayUser();
        $user->setEmail('user@example.com');
        $user->setRoles(['ROLE_USER']);
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $this->client->loginUser($user);

        $this->client->request('GET', '/api/onepay_transactions');
        $this->assertResponseIsSuccessful();
        $this->assertJson($this->client->getResponse()->getContent());
    }

    // Test pour créer une nouvelle transaction (ROLE_ADMIN requis)
    public function testCreateTransaction(): void
    {
        // Simuler un utilisateur avec le rôle ROLE_ADMIN
        $adminUser = new OnepayUser();
        $adminUser->setEmail('admin@example.com');
        $adminUser->setRoles(['ROLE_ADMIN']);
        $this->entityManager->persist($adminUser);
        $this->entityManager->flush();

        $this->client->loginUser($adminUser);

        $data = [
            'transaction_type' => 'credit',
            'amount' => 1000,
            'status' => 'pending',
            'from_operator' => 'MTN',
            'to_operator' => 'Orange',
            'receiver_phone' => '237123456789',
            'operator' => 'MTN',
            'phone_number' => '237987654321',
        ];

        $this->client->request(
            'POST',
            '/api/onepay_transactions',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($data)
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED); // 201 = Created
        $this->assertJson($this->client->getResponse()->getContent());
    }

    // Test pour récupérer une transaction spécifique (ROLE_USER requis)
    public function testGetTransaction(): void
    {
        // Simuler un utilisateur avec le rôle ROLE_USER
        $user = new OnepayUser();
        $user->setEmail('user@example.com');
        $user->setRoles(['ROLE_USER']);
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $this->client->loginUser($user);

        // Créer une transaction de test
        $transaction = new OnepayTransaction();
        $transaction->setTransactionType('credit');
        $transaction->setAmount(1000);
        $transaction->setStatus('pending');

        $this->entityManager->persist($transaction);
        $this->entityManager->flush();

        $this->client->request('GET', '/api/onepay_transactions/' . $transaction->getId());
        $this->assertResponseIsSuccessful();
        $this->assertJson($this->client->getResponse()->getContent());
    }

    // Test pour mettre à jour une transaction (ROLE_ADMIN requis)
    public function testUpdateTransaction(): void
    {
        // Simuler un utilisateur avec le rôle ROLE_ADMIN
        $adminUser = new OnepayUser();
        $adminUser->setEmail('admin@example.com');
        $adminUser->setRoles(['ROLE_ADMIN']);
        $this->entityManager->persist($adminUser);
        $this->entityManager->flush();

        $this->client->loginUser($adminUser);

        // Créer une transaction de test
        $transaction = new OnepayTransaction();
        $transaction->setTransactionType('credit');
        $transaction->setAmount(1000);
        $transaction->setStatus('pending');

        $this->entityManager->persist($transaction);
        $this->entityManager->flush();

        $data = [
            'amount' => 1500,
            'status' => 'completed',
        ];

        $this->client->request(
            'PUT',
            '/api/onepay_transactions/' . $transaction->getId(),
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($data)
        );

        $this->assertResponseIsSuccessful();
        $this->assertJson($this->client->getResponse()->getContent());
    }

    // Test pour supprimer une transaction (ROLE_ADMIN requis)
    public function testDeleteTransaction(): void
    {
        // Simuler un utilisateur avec le rôle ROLE_ADMIN
        $adminUser = new OnepayUser();
        $adminUser->setEmail('admin@example.com');
        $adminUser->setRoles(['ROLE_ADMIN']);
        $this->entityManager->persist($adminUser);
        $this->entityManager->flush();

        $this->client->loginUser($adminUser);

        // Créer une transaction de test
        $transaction = new OnepayTransaction();
        $transaction->setTransactionType('credit');
        $transaction->setAmount(1000);
        $transaction->setStatus('pending');

        $this->entityManager->persist($transaction);
        $this->entityManager->flush();

        $this->client->request('DELETE', '/api/onepay_transactions/' . $transaction->getId());
        $this->assertResponseStatusCodeSame(Response::HTTP_NO_CONTENT); // 204 = No Content
    }

    // Test pour récupérer les transactions d'un utilisateur spécifique (ROLE_USER requis)
    public function testGetTransactionsByUser(): void
    {
        // Simuler un utilisateur avec le rôle ROLE_USER
        $user = new OnepayUser();
        $user->setEmail('user@example.com');
        $user->setRoles(['ROLE_USER']);
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $this->client->loginUser($user);

        $this->client->request('GET', '/api/onepay_transactions/user/' . $user->getId());
        $this->assertResponseIsSuccessful();
        $this->assertJson($this->client->getResponse()->getContent());
    }

    // Test pour filtrer les transactions (ROLE_USER requis)
    public function testFilterTransactions(): void
    {
        // Simuler un utilisateur avec le rôle ROLE_USER
        $user = new OnepayUser();
        $user->setEmail('user@example.com');
        $user->setRoles(['ROLE_USER']);
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $this->client->loginUser($user);

        $this->client->request('GET', '/api/onepay_transactions/filter?status=pending');
        $this->assertResponseIsSuccessful();
        $this->assertJson($this->client->getResponse()->getContent());
    }

    // Test pour obtenir le solde d'un utilisateur (ROLE_USER requis)
    public function testGetUserBalance(): void
    {
        // Simuler un utilisateur avec le rôle ROLE_USER
        $user = new OnepayUser();
        $user->setEmail('user@example.com');
        $user->setRoles(['ROLE_USER']);
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $this->client->loginUser($user);

        $this->client->request('GET', '/api/onepay_transactions/user/' . $user->getId() . '/balance');
        $this->assertResponseIsSuccessful();
        $this->assertJson($this->client->getResponse()->getContent());
    }

    // Test pour vérifier l'accès refusé (ROLE_USER essayant d'accéder à une route ROLE_ADMIN)
    public function testAccessDenied(): void
    {
        // Simuler un utilisateur avec le rôle ROLE_USER
        $user = new OnepayUser();
        $user->setEmail('user@example.com');
        $user->setRoles(['ROLE_USER']);
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $this->client->loginUser($user);

        // Tenter de créer une transaction (nécessite ROLE_ADMIN)
        $data = [
            'transaction_type' => 'credit',
            'amount' => 1000,
            'status' => 'pending',
        ];

        $this->client->request(
            'POST',
            '/api/onepay_transactions',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($data)
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN); // 403 = Forbidden
    }
}