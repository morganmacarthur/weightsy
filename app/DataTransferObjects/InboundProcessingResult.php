<?php

namespace App\DataTransferObjects;

use App\Models\Checkin;
use App\Models\Message;
use App\Models\User;

class InboundProcessingResult
{
    public function __construct(
        public readonly bool $recognized,
        public readonly bool $createdUser,
        public readonly ?User $user,
        public readonly ?Checkin $checkin,
        public readonly Message $message,
        public readonly ?ParsedCheckin $parsedCheckin,
        public readonly bool $duplicate = false,
    ) {}
}
