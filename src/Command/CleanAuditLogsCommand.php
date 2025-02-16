<?php

namespace App\Command;

use App\Repository\OnepayAuditLogRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Psr\Log\LoggerInterface;

#[AsCommand(
    name: 'onepay:clean-audit-logs',
    description: 'Nettoie les anciens logs d\'audit',
)]
class CleanAuditLogsCommand extends Command
{
    public function __construct(
        private readonly OnepayAuditLogRepository $auditLogRepository,
        private readonly LoggerInterface $logger
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption(
                'days',
                'd',
                InputOption::VALUE_OPTIONAL,
                'Nombre de jours de rétention des logs',
                90
            )
            ->addOption(
                'dry-run',
                null,
                InputOption::VALUE_NONE,
                'Exécution à blanc (sans suppression)'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $dryRun = $input->getOption('dry-run');
        $days = (int) $input->getOption('days');

        $io->title('Nettoyage des logs d\'audit');
        $io->text(sprintf('Suppression des logs plus vieux que %d jours', $days));

        if ($dryRun) {
            $io->note('Mode dry-run activé - Aucune suppression ne sera effectuée');
        }

        try {
            // Calcul de la date limite
            $before = new \DateTime("-{$days} days");

            // Récupération des statistiques avant nettoyage
            $statsBefore = $this->auditLogRepository->getPerformanceStats();
            
            if (!$dryRun) {
                // Suppression des logs
                $deletedCount = $this->auditLogRepository->cleanOldLogs($before);

                $this->logger->info('Nettoyage des logs d\'audit effectué', [
                    'deleted_count' => $deletedCount,
                    'retention_days' => $days
                ]);

                // Récupération des statistiques après nettoyage
                $statsAfter = $this->auditLogRepository->getPerformanceStats();

                $io->success(sprintf(
                    '%d logs d\'audit supprimés avec succès',
                    $deletedCount
                ));

                // Affichage des statistiques
                $io->table(
                    ['Métrique', 'Avant', 'Après'],
                    [
                        ['Nombre total de logs', $statsBefore['total_count'], $statsAfter['total_count']],
                        ['Taux de succès', 
                         sprintf('%.2f%%', $statsBefore['success_rate']),
                         sprintf('%.2f%%', $statsAfter['success_rate'])
                        ],
                        ['Nombre d\'erreurs', $statsBefore['error_count'], $statsAfter['error_count']]
                    ]
                );
            } else {
                // En mode dry-run, on compte juste les logs qui seraient supprimés
                $logsToDelete = $this->auditLogRepository->createQueryBuilder('a')
                    ->select('COUNT(a.id)')
                    ->where('a.createdAt < :before')
                    ->setParameter('before', $before)
                    ->getQuery()
                    ->getSingleScalarResult();

                $io->info(sprintf(
                    '%d logs seraient supprimés en mode normal',
                    $logsToDelete
                ));
            }

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->logger->error('Erreur lors du nettoyage des logs d\'audit', [
                'error' => $e->getMessage()
            ]);

            $io->error(sprintf(
                'Une erreur est survenue lors du nettoyage : %s',
                $e->getMessage()
            ));

            return Command::FAILURE;
        }
    }
}
