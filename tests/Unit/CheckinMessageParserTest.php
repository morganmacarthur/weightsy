<?php

namespace Tests\Unit;

use App\Services\CheckinMessageParser;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class CheckinMessageParserTest extends TestCase
{
    #[DataProvider('validMessages')]
    public function test_it_parses_supported_messages(string $message, array $expected): void
    {
        $parser = app(CheckinMessageParser::class);
        $parsed = $parser->parse($message);

        $this->assertNotNull($parsed);
        $this->assertSame($expected['metric_type'], $parsed->metricType);
        $this->assertSame($expected['value_decimal'], $parsed->valueDecimal);
        $this->assertSame($expected['systolic'], $parsed->systolic);
        $this->assertSame($expected['diastolic'], $parsed->diastolic);
    }

    public static function validMessages(): array
    {
        return [
            'weight' => ['123', [
                'metric_type' => 'weight',
                'value_decimal' => '123.00',
                'systolic' => null,
                'diastolic' => null,
            ]],
            'blood pressure' => ['120/70', [
                'metric_type' => 'blood_pressure',
                'value_decimal' => null,
                'systolic' => 120,
                'diastolic' => 70,
            ]],
            'body fat' => ['14.0%', [
                'metric_type' => 'body_fat',
                'value_decimal' => '14.00',
                'systolic' => null,
                'diastolic' => null,
            ]],
        ];
    }

    public function test_it_rejects_unsupported_messages(): void
    {
        $parser = app(CheckinMessageParser::class);

        $this->assertNull($parser->parse('today felt strong'));
    }
}
