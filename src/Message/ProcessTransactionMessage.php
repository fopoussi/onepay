<?php

namespace App\Message;

class ProcessTransactionMessage
{
    public function __construct(
        private readonly string $transactionId,
        private readonly string $action,
        private readonly array $metadata = []
    ) {}

    public function getTransactionId(): string
    {
        return $this->transactionId;
    }

    public function getAction(): string
    {
        return $this->action;
    }

    public function getMetadata(): array
    {
        return $this->metadata;
    }
}
