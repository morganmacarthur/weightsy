<?php

namespace App\Console\Commands;

use App\Services\PostmarkMailer;
use Illuminate\Console\Command;

class SendPostmarkTest extends Command
{
    protected $signature = 'weightsy:postmark:test {to : Recipient email address}';

    protected $description = 'Send a one-off Postmark test email';

    public function handle(PostmarkMailer $postmarkMailer): int
    {
        $to = (string) $this->argument('to');

        $response = $postmarkMailer->send(
            $to,
            'Weightsy Postmark test',
            "This is a Weightsy Postmark test email.\n\nIf this arrived, the first-contact sender path is working."
        );

        $this->info('Sent Postmark test email to '.$to);
        $this->line('MessageID: '.($response['MessageID'] ?? 'unknown'));
        $this->line('SubmittedAt: '.($response['SubmittedAt'] ?? 'unknown'));

        return self::SUCCESS;
    }
}
