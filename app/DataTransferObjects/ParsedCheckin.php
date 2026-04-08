<?php

namespace App\DataTransferObjects;

class ParsedCheckin
{
    public function __construct(
        public readonly string $metricType,
        public readonly ?string $valueDecimal,
        public readonly ?int $systolic,
        public readonly ?int $diastolic,
        public readonly string $normalizedDisplay,
    ) {
    }
}
