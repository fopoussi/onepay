<?php

namespace App\Message;

class SendNotificationMessage
{
    public function __construct(
        private readonly string $notificationId
    ) {}

    public function getNotificationId(): string
    {
        return $this->notificationId;
    }
}
