<?php

namespace App\DataTransferObjects;

use Carbon\CarbonImmutable;

class InboundMessageData
{
    public function __construct(
        public readonly ?string $externalId,
        public readonly string $from,
        public readonly string $channel,
        public readonly ?string $subject,
        public readonly string $text,
        public readonly ?CarbonImmutable $receivedAt,
        public readonly string $provider,
        public readonly array $metadata = [],
    ) {
    }
}
