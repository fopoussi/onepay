<?php

namespace App\Service;

use App\Repository\OnepayAuditLogRepository;
use App\Repository\OnepayTransactionRepository;
use App\Repository\OnepayFailedTransactionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Cache\Adapter\RedisAdapter;
use Symfony\Contracts\Cache\CacheInterface;

class MonitoringService
{
    private const ALERT_THRESHOLDS = [
        'error_rate' => 10.0,        // Taux d'erreur > 10%
        'response_time' => 2000.0,   // Temps de réponse > 2s
        'balance_low' => 100000.0,   // Solde système < 100,000 FCFA
        'fraud_attempts' => 5        // > 5 tentatives suspectes en 1h
    ];

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly OnepayAuditLogRepository $auditLogRepository,
        private readonly OnepayTransactionRepository $transactionRepository,
        private readonly OnepayFailedTransactionRepository $failedTransactionRepository,
        private readonly CacheInterface $cache,
        private readonly LoggerInterface $logger,
        private readonly NotificationService $notificationService
    ) {}

    /**
     * Collecte les métriques du système
     */
    public function collectMetrics(): array
    {
        $since = new \DateTime('-1 hour');
        
        try {
            $metrics = [
                'transactions' => $this->getTransactionMetrics($since),
                'performance' => $this->getPerformanceMetrics($since),
                'errors' => $this->getErrorMetrics($since),
                'system' => $this->getSystemMetrics()
            ];

            // Mise en cache des métriques
            $this->cache->set('system_metrics', $metrics, 300); // TTL 5 minutes

            // Vérification des alertes
            $this->checkAlerts($metrics);

            return $metrics;
        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de la collecte des métriques', [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Métriques des transactions
     */
    private function getTransactionMetrics(\DateTime $since): array
    {
        $transactions = $this->transactionRepository->findSince($since);
        $total = count($transactions);
        $successful = 0;
        $amounts = [];

        foreach ($transactions as $transaction) {
            if ($transaction->getStatus() === 'COMPLETED') {
                $successful++;
                $amounts[] = $transaction->getAmount();
            }
        }

        return [
            'total_count' => $total,
            'success_count' => $successful,
            'success_rate' => $total > 0 ? ($successful / $total) * 100 : 0,
            'average_amount' => !empty($amounts) ? array_sum($amounts) / count($amounts) : 0,
            'total_volume' => array_sum($amounts)
        ];
    }

    /**
     * Métriques de performance
     */
    private function getPerformanceMetrics(\DateTime $since): array
    {
        $stats = $this->auditLogRepository->getPerformanceStats($since);

        return [
            'average_response_time' => $stats['avg_duration_ms'],
            'max_response_time' => $stats['max_duration_ms'],
            'success_rate' => $stats['success_rate'],
            'error_count' => $stats['error_count']
        ];
    }

    /**
     * Métriques d'erreurs
     */
    private function getErrorMetrics(\DateTime $since): array
    {
        $failedTransactions = $this->failedTransactionRepository->findSince($since);
        $errorsByType = [];
        $errorsByOperator = [];

        foreach ($failedTransactions as $failed) {
            $type = $failed->getTransaction()->getType();
            $operator = $failed->getTransaction()->getOperator();
            
            $errorsByType[$type] = ($errorsByType[$type] ?? 0) + 1;
            $errorsByOperator[$operator] = ($errorsByOperator[$operator] ?? 0) + 1;
        }

        return [
            'total_errors' => count($failedTransactions),
            'errors_by_type' => $errorsByType,
            'errors_by_operator' => $errorsByOperator
        ];
    }

    /**
     * Métriques système
     */
    private function getSystemMetrics(): array
    {
        return [
            'redis_status' => $this->checkRedisStatus(),
            'database_status' => $this->checkDatabaseStatus(),
            'queue_size' => $this->getQueueSize(),
            'memory_usage' => memory_get_usage(true),
            'system_load' => sys_getloadavg()
        ];
    }

    /**
     * Vérifie le statut de Redis
     */
    private function checkRedisStatus(): bool
    {
        try {
            if ($this->cache instanceof RedisAdapter) {
                $redis = $this->cache->getRedis();
                return $redis->ping() === true;
            }
            return false;
        } catch (\Exception $e) {
            $this->logger->error('Erreur de connexion Redis', [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Vérifie le statut de la base de données
     */
    private function checkDatabaseStatus(): bool
    {
        try {
            $this->entityManager->getConnection()->connect();
            return $this->entityManager->getConnection()->isConnected();
        } catch (\Exception $e) {
            $this->logger->error('Erreur de connexion à la base de données', [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Récupère la taille des files d'attente
     */
    private function getQueueSize(): array
    {
        try {
            return [
                'transactions' => $this->cache->get('queue_size_transactions', fn() => 0),
                'notifications' => $this->cache->get('queue_size_notifications', fn() => 0),
                'balance_sync' => $this->cache->get('queue_size_balance_sync', fn() => 0)
            ];
        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de la récupération de la taille des files d\'attente', [
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * Vérifie les seuils d'alerte
     */
    private function checkAlerts(array $metrics): void
    {
        $alerts = [];

        // Vérification du taux d'erreur
        if ($metrics['transactions']['success_rate'] < (100 - self::ALERT_THRESHOLDS['error_rate'])) {
            $alerts[] = [
                'type' => 'ERROR_RATE_HIGH',
                'message' => sprintf(
                    'Taux d\'erreur élevé : %.2f%%',
                    100 - $metrics['transactions']['success_rate']
                )
            ];
        }

        // Vérification du temps de réponse
        if ($metrics['performance']['average_response_time'] > self::ALERT_THRESHOLDS['response_time']) {
            $alerts[] = [
                'type' => 'RESPONSE_TIME_HIGH',
                'message' => sprintf(
                    'Temps de réponse moyen élevé : %.2fms',
                    $metrics['performance']['average_response_time']
                )
            ];
        }

        // Envoi des alertes si nécessaire
        if (!empty($alerts)) {
            foreach ($alerts as $alert) {
                $this->logger->alert($alert['message'], [
                    'type' => $alert['type'],
                    'metrics' => $metrics
                ]);

                // TODO: Implémenter l'envoi d'alertes (email, SMS, etc.)
            }
        }
    }
}
