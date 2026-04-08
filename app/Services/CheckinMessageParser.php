<?php

namespace App\Services;

use App\DataTransferObjects\ParsedCheckin;

class CheckinMessageParser
{
    public function parse(string $message): ?ParsedCheckin
    {
        $normalized = trim($message);

        if ($normalized === '') {
            return null;
        }

        if (preg_match('/^(?<systolic>\d{2,3})\s*\/\s*(?<diastolic>\d{2,3})$/', $normalized, $matches) === 1) {
            return new ParsedCheckin(
                metricType: 'blood_pressure',
                valueDecimal: null,
                systolic: (int) $matches['systolic'],
                diastolic: (int) $matches['diastolic'],
                normalizedDisplay: sprintf('%d/%d', $matches['systolic'], $matches['diastolic']),
            );
        }

        if (preg_match('/^(?<bodyFat>\d{1,2}(?:\.\d+)?)\s*%$/', $normalized, $matches) === 1) {
            $value = number_format((float) $matches['bodyFat'], 2, '.', '');

            return new ParsedCheckin(
                metricType: 'body_fat',
                valueDecimal: $value,
                systolic: null,
                diastolic: null,
                normalizedDisplay: sprintf('%s%%', rtrim(rtrim($value, '0'), '.')),
            );
        }

        if (preg_match('/^(?<weight>\d{2,3}(?:\.\d+)?)$/', $normalized, $matches) === 1) {
            $value = number_format((float) $matches['weight'], 2, '.', '');

            return new ParsedCheckin(
                metricType: 'weight',
                valueDecimal: $value,
                systolic: null,
                diastolic: null,
                normalizedDisplay: rtrim(rtrim($value, '0'), '.'),
            );
        }

        return null;
    }
}
