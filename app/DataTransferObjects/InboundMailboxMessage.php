<?php

namespace App\DataTransferObjects;

use Carbon\CarbonImmutable;

class InboundMailboxMessage
{
    public function __construct(
        public readonly string $uid,
        public readonly string $from,
        public readonly ?string $subject,
        public readonly string $text,
        public readonly ?CarbonImmutable $receivedAt,
        public readonly string $rawHeaders,
        public readonly string $rawBody,
    ) {
    }
}
