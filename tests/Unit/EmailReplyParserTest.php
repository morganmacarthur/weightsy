<?php

namespace Tests\Unit;

use App\Services\EmailReplyParser;
use Tests\TestCase;

class EmailReplyParserTest extends TestCase
{
    public function test_it_returns_the_first_authored_line_before_a_quoted_reply(): void
    {
        $parser = new EmailReplyParser();

        $candidate = $parser->extractCheckinCandidate("345\n\nOn Mon, Apr 13, 2026 at 5:32 AM Weightsy <update@weightsy.com> wrote:\n> Time for today's Weightsy check-in.");

        $this->assertSame('345', $candidate);
    }

    public function test_it_returns_the_full_message_when_no_quote_boundary_exists(): void
    {
        $parser = new EmailReplyParser();

        $candidate = $parser->extractCheckinCandidate("120/70\n");

        $this->assertSame('120/70', $candidate);
    }
}
