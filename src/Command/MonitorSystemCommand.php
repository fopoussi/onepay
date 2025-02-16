<?php

namespace App\Command;

use App\Service\MonitoringService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Helper\Table;

#[AsCommand(
    name: 'onepay:monitor-system',
    description: 'Surveille les métriques système en temps réel',
)]
class MonitorSystemCommand extends Command
{
    public function __construct(
        private readonly MonitoringService $monitoringService
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption(
                'interval',
                'i',
                InputOption::VALUE_OPTIONAL,
                'Intervalle de rafraîchissement en secondes',
                60
            )
            ->addOption(
                'watch',
                'w',
                InputOption::VALUE_NONE,
                'Mode surveillance continue'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $interval = (int) $input->getOption('interval');
        $watch = $input->getOption('watch');

        $io->title('Surveillance du système OnePay');

        do {
            // Effacement de l'écran en mode watch
            if ($watch) {
                $output->write("\033[2J\033[H");
            }

            try {
                $metrics = $this->monitoringService->collectMetrics();
                $this->displayMetrics($output, $metrics);

                if ($watch) {
                    $io->note(sprintf(
                        'Dernière mise à jour : %s - Actualisation dans %d secondes',
                        (new \DateTime())->format('Y-m-d H:i:s'),
                        $interval
                    ));
                    sleep($interval);
                }
            } catch (\Exception $e) {
                $io->error(sprintf('Erreur : %s', $e->getMessage()));
                return Command::FAILURE;
            }
        } while ($watch);

        return Command::SUCCESS;
    }

    private function displayMetrics(OutputInterface $output, array $metrics): void
    {
        // Métriques des transactions
        $this->displayTransactionMetrics($output, $metrics['transactions']);

        // Métriques de performance
        $this->displayPerformanceMetrics($output, $metrics['performance']);

        // Métriques d'erreurs
        $this->displayErrorMetrics($output, $metrics['errors']);

        // Métriques système
        $this->displaySystemMetrics($output, $metrics['system']);
    }

    private function displayTransactionMetrics(OutputInterface $output, array $metrics): void
    {
        $output->writeln('');
        $output->writeln('<info>Métriques des Transactions</info>');
        
        $table = new Table($output);
        $table
            ->setHeaders(['Métrique', 'Valeur'])
            ->setRows([
                ['Nombre total', $metrics['total_count']],
                ['Transactions réussies', $metrics['success_count']],
                ['Taux de succès', sprintf('%.2f%%', $metrics['success_rate'])],
                ['Montant moyen', sprintf('%.2f FCFA', $metrics['average_amount'])],
                ['Volume total', sprintf('%.2f FCFA', $metrics['total_volume'])]
            ]);
        $table->render();
    }

    private function displayPerformanceMetrics(OutputInterface $output, array $metrics): void
    {
        $output->writeln('');
        $output->writeln('<info>Métriques de Performance</info>');
        
        $table = new Table($output);
        $table
            ->setHeaders(['Métrique', 'Valeur'])
            ->setRows([
                ['Temps de réponse moyen', sprintf('%.2f ms', $metrics['average_response_time'])],
                ['Temps de réponse max', sprintf('%.2f ms', $metrics['max_response_time'])],
                ['Taux de succès', sprintf('%.2f%%', $metrics['success_rate'])],
                ['Nombre d\'erreurs', $metrics['error_count']]
            ]);
        $table->render();
    }

    private function displayErrorMetrics(OutputInterface $output, array $metrics): void
    {
        $output->writeln('');
        $output->writeln('<info>Métriques d\'Erreurs</info>');
        
        // Erreurs par type
        $table = new Table($output);
        $table->setHeaders(['Type d\'erreur', 'Nombre']);
        foreach ($metrics['errors_by_type'] as $type => $count) {
            $table->addRow([$type, $count]);
        }
        $table->render();

        // Erreurs par opérateur
        $output->writeln('');
        $table = new Table($output);
        $table->setHeaders(['Opérateur', 'Nombre d\'erreurs']);
        foreach ($metrics['errors_by_operator'] as $operator => $count) {
            $table->addRow([$operator, $count]);
        }
        $table->render();
    }

    private function displaySystemMetrics(OutputInterface $output, array $metrics): void
    {
        $output->writeln('');
        $output->writeln('<info>Métriques Système</info>');
        
        $table = new Table($output);
        $table
            ->setHeaders(['Composant', 'Statut'])
            ->setRows([
                ['Redis', $metrics['redis_status'] ? '<fg=green>OK</>' : '<fg=red>KO</>'],
                ['Base de données', $metrics['database_status'] ? '<fg=green>OK</>' : '<fg=red>KO</>'],
                ['Mémoire utilisée', $this->formatBytes($metrics['memory_usage'])],
                ['Charge système', sprintf('%.2f, %.2f, %.2f', ...$metrics['system_load'])]
            ]);
        $table->render();

        // Taille des files d'attente
        $output->writeln('');
        $output->writeln('<info>Files d\'attente</info>');
        $table = new Table($output);
        $table->setHeaders(['File', 'Taille']);
        foreach ($metrics['queue_size'] as $queue => $size) {
            $table->addRow([$queue, $size]);
        }
        $table->render();
    }

    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        
        return sprintf('%.2f %s', $bytes / pow(1024, $pow), $units[$pow]);
    }
}
