<?php

namespace App\Repository;

use App\Entity\OnepayAuditLog;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<OnepayAuditLog>
 */
class OnepayAuditLogRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, OnepayAuditLog::class);
    }

    /**
     * Trouve les logs d'audit pour une période donnée
     */
    public function findByDateRange(\DateTimeInterface $start, \DateTimeInterface $end): array
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.createdAt BETWEEN :start AND :end')
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->orderBy('a.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les logs d'audit pour un utilisateur
     */
    public function findByUser(int $userId, int $limit = 50): array
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.userId = :userId')
            ->setParameter('userId', $userId)
            ->orderBy('a.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les logs d'erreurs
     */
    public function findErrors(\DateTimeInterface $since = null, int $limit = 50): array
    {
        $qb = $this->createQueryBuilder('a')
            ->andWhere('a.success = :success')
            ->setParameter('success', false);

        if ($since) {
            $qb->andWhere('a.createdAt >= :since')
               ->setParameter('since', $since);
        }

        return $qb->orderBy('a.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les logs par type de message
     */
    public function findByMessageClass(string $messageClass, int $limit = 50): array
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.messageClass = :messageClass')
            ->setParameter('messageClass', $messageClass)
            ->orderBy('a.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Calcule les statistiques de performance
     */
    public function getPerformanceStats(\DateTimeInterface $since = null): array
    {
        $qb = $this->createQueryBuilder('a')
            ->select('
                COUNT(a.id) as total_count,
                AVG(a.duration) as avg_duration,
                MAX(a.duration) as max_duration,
                MIN(a.duration) as min_duration,
                SUM(CASE WHEN a.success = true THEN 1 ELSE 0 END) as success_count,
                SUM(CASE WHEN a.success = false THEN 1 ELSE 0 END) as error_count
            ');

        if ($since) {
            $qb->andWhere('a.createdAt >= :since')
               ->setParameter('since', $since);
        }

        $result = $qb->getQuery()->getSingleResult();

        // Calcul du taux de succès
        $totalCount = (int)$result['total_count'];
        $successCount = (int)$result['success_count'];
        
        return [
            'total_count' => $totalCount,
            'avg_duration_ms' => round((float)$result['avg_duration'], 2),
            'max_duration_ms' => round((float)$result['max_duration'], 2),
            'min_duration_ms' => round((float)$result['min_duration'], 2),
            'success_rate' => $totalCount > 0 ? ($successCount / $totalCount) * 100 : 0,
            'error_count' => (int)$result['error_count']
        ];
    }

    /**
     * Nettoie les anciens logs d'audit
     */
    public function cleanOldLogs(\DateTimeInterface $before): int
    {
        return $this->createQueryBuilder('a')
            ->delete()
            ->where('a.createdAt < :before')
            ->setParameter('before', $before)
            ->getQuery()
            ->execute();
    }
}
