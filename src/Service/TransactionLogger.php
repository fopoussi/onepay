<?php

namespace App\Service;

use Psr\Log\LoggerInterface;

class TransactionLogger
{
    private $logger;
    private $logFile;

    public function __construct(LoggerInterface $logger, string $logFile)
    {
        $this->logger = $logger;
        $this->logFile = $logFile;

        // Assurez-vous que le répertoire de logs existe
        $logDirectory = dirname($this->logFile);
        if (!is_dir($logDirectory)) {
            mkdir($logDirectory, 0777, true);
        }
    }

    public function logTransaction(string $action, array $details): void
    {
        // Journalisation via le logger Symfony
        $this->logger->info('Transaction {action}: {details}', [
            'action' => $action,
            'details' => json_encode($details),
        ]);

        // Journalisation dans un fichier spécifique
        $logMessage = sprintf(
            "[%s] Transaction %s: %s\n",
            date('Y-m-d H:i:s'),
            $action,
            json_encode($details)
        );
        file_put_contents($this->logFile, $logMessage, FILE_APPEND);
    }
}