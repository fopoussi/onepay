<?php

namespace App\MessageHandler;

use App\Message\SyncBalanceMessage;
use App\Entity\MobileMoneyAccount;
use App\Service\OrangeMoneyService;
use App\Service\MtnMomoService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;
use Symfony\Component\Messenger\Exception\UnrecoverableMessageHandlingException;
use Symfony\Contracts\Cache\CacheInterface;

class SyncBalanceMessageHandler implements MessageHandlerInterface
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly OrangeMoneyService $orangeMoneyService,
        private readonly MtnMomoService $mtnMomoService,
        private readonly CacheInterface $cache,
        private readonly LoggerInterface $logger
    ) {}

    public function __invoke(SyncBalanceMessage $message): void
    {
        $this->logger->info('Synchronisation du solde', [
            'accountId' => $message->getAccountId(),
            'provider' => $message->getProvider()
        ]);

        try {
            $account = $this->entityManager->getRepository(MobileMoneyAccount::class)
                ->find($message->getAccountId());

            if (!$account) {
                throw new UnrecoverableMessageHandlingException(
                    sprintf('Compte Mobile Money %s non trouvé', $message->getAccountId())
                );
            }

            // Sélection du service approprié selon le provider
            $service = $this->getServiceForProvider($message->getProvider());

            // Récupération du solde
            $balance = $service->getBalance($account);

            // Mise à jour du compte
            $account->setBalance($balance);
            $account->setLastSync(new \DateTimeImmutable());

            // Invalidation du cache
            $this->invalidateBalanceCache($account);

            $this->entityManager->flush();

            $this->logger->info('Solde synchronisé avec succès', [
                'accountId' => $message->getAccountId(),
                'provider' => $message->getProvider(),
                'balance' => $balance
            ]);

        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de la synchronisation du solde', [
                'error' => $e->getMessage(),
                'accountId' => $message->getAccountId(),
                'provider' => $message->getProvider()
            ]);

            if (!$e instanceof UnrecoverableMessageHandlingException) {
                throw $e; // Permet le retry
            }
        }
    }

    private function getServiceForProvider(string $provider): OrangeMoneyService|MtnMomoService
    {
        return match ($provider) {
            'ORANGE_MONEY' => $this->orangeMoneyService,
            'MTN_MOMO' => $this->mtnMomoService,
            default => throw new UnrecoverableMessageHandlingException(
                sprintf('Provider %s non supporté', $provider)
            )
        };
    }

    private function invalidateBalanceCache(MobileMoneyAccount $account): void
    {
        $cacheKeys = [
            sprintf('%s_balance_%s', strtolower($account->getProvider()), $account->getNumber()),
            sprintf('account_balance_%s', $account->getId())
        ];

        foreach ($cacheKeys as $key) {
            $this->cache->delete($key);
        }
    }
}
