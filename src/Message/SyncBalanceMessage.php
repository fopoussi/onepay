<?php

namespace App\Message;

class SyncBalanceMessage
{
    public function __construct(
        private readonly string $accountId,
        private readonly string $provider, // ORANGE_MONEY or MTN_MOMO
        private readonly array $context = []
    ) {}

    public function getAccountId(): string
    {
        return $this->accountId;
    }

    public function getProvider(): string
    {
        return $this->provider;
    }

    public function getContext(): array
    {
        return $this->context;
    }
}
