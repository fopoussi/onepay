<?php

namespace App\Tests\Service;

use App\Service\TransactionLogger;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Filesystem\Filesystem;

class TransactionLoggerTest extends TestCase
{
    private $logger;
    private $logFile;
    private $filesystem;

    protected function setUp(): void
    {
        // Créer un mock pour LoggerInterface
        $this->logger = $this->createMock(LoggerInterface::class);

        // Définir un fichier de log temporaire
        $this->logFile = sys_get_temp_dir() . '/transaction_logs/test.log';

        // Supprimer le fichier de log s'il existe déjà
        $this->filesystem = new Filesystem();
        if ($this->filesystem->exists($this->logFile)) {
            $this->filesystem->remove($this->logFile);
        }

        // Créer le répertoire de logs si nécessaire
        $this->filesystem->mkdir(dirname($this->logFile));
    }

    protected function tearDown(): void
    {
        // Nettoyer le fichier de log après chaque test
        if ($this->filesystem->exists($this->logFile)) {
            $this->filesystem->remove($this->logFile);
        }
    }

    public function testLogTransaction(): void
    {
        // Données de test
        $action = 'create';
        $details = ['id' => 1, 'amount' => 1000, 'status' => 'pending'];

        // Configurer le mock pour LoggerInterface
        $this->logger
            ->expects($this->once())
            ->method('info')
            ->with(
                'Transaction {action}: {details}',
                ['action' => $action, 'details' => json_encode($details)]
            );

        // Instancier le service TransactionLogger
        $transactionLogger = new TransactionLogger($this->logger, $this->logFile);

        // Appeler la méthode à tester
        $transactionLogger->logTransaction($action, $details);

        // Vérifier que le fichier de log a été créé
        $this->assertFileExists($this->logFile);

        // Vérifier le contenu du fichier de log
        $logContent = file_get_contents($this->logFile);
        $expectedLogMessage = sprintf(
            "[%s] Transaction %s: %s\n",
            date('Y-m-d H:i:s'),
            $action,
            json_encode($details)
        );
        $this->assertStringContainsString($expectedLogMessage, $logContent);
    }

    public function testLogTransactionWithInvalidDirectory(): void
    {
        // Définir un fichier de log dans un répertoire non accessible
        $invalidLogFile = '/invalid_directory/test.log';

        // Configurer le mock pour LoggerInterface
        $this->logger
            ->expects($this->once())
            ->method('info')
            ->with(
                'Transaction {action}: {details}',
                ['action' => 'create', 'details' => json_encode(['id' => 1])]
            );

        // Instancier le service TransactionLogger
        $transactionLogger = new TransactionLogger($this->logger, $invalidLogFile);

        // Appeler la méthode à tester
        $transactionLogger->logTransaction('create', ['id' => 1]);

        // Vérifier que le fichier de log n'a pas été créé
        $this->assertFileDoesNotExist($invalidLogFile);
    }
}