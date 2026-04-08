<?php

namespace Tests\Unit;

use App\Services\InboundEmailParser;
use Tests\TestCase;

class InboundEmailParserTest extends TestCase
{
    public function test_it_parses_a_simple_plain_text_email(): void
    {
        $parser = app(InboundEmailParser::class);

        $message = $parser->parse(
            uid: '42',
            rawHeaders: implode("\r\n", [
                'From: Morgan <morgan@example.com>',
                'Subject: Daily check-in',
                'Date: Sat, 04 Apr 2026 09:30:00 -0700',
                'Content-Type: text/plain; charset=UTF-8',
            ]),
            rawBody: "123\r\n",
        );

        $this->assertSame('42', $message->uid);
        $this->assertSame('morgan@example.com', $message->from);
        $this->assertSame('Daily check-in', $message->subject);
        $this->assertSame('123', $message->text);
        $this->assertSame('2026-04-04T09:30:00-07:00', $message->receivedAt?->toIso8601String());
    }

    public function test_it_prefers_the_text_plain_part_in_multipart_messages(): void
    {
        $parser = app(InboundEmailParser::class);

        $message = $parser->parse(
            uid: '77',
            rawHeaders: implode("\r\n", [
                'From: 5551234567@vtext.com',
                'Subject: =?UTF-8?Q?Check-in?=',
                'Content-Type: multipart/alternative; boundary="abc123"',
            ]),
            rawBody: implode("\r\n", [
                '--abc123',
                'Content-Type: text/plain; charset=UTF-8',
                '',
                '120/70',
                '--abc123',
                'Content-Type: text/html; charset=UTF-8',
                '',
                '<p>120/70</p>',
                '--abc123--',
            ]),
        );

        $this->assertSame('5551234567@vtext.com', $message->from);
        $this->assertSame('Check-in', $message->subject);
        $this->assertSame('120/70', $message->text);
    }
}
