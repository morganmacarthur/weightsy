<?php

namespace App\Services;

use Illuminate\Http\Client\Factory as HttpFactory;
use RuntimeException;

class PostmarkMailer
{
    public function __construct(
        private readonly HttpFactory $http,
    ) {
    }

    public function isConfigured(): bool
    {
        return filled(config('weightsy.postmark.token')) && filled(config('weightsy.postmark.from'));
    }

    public function send(string $to, string $subject, string $textBody): array
    {
        if (! $this->isConfigured()) {
            throw new RuntimeException('Postmark is not configured.');
        }

        $response = $this->http
            ->withHeaders([
                'X-Postmark-Server-Token' => (string) config('weightsy.postmark.token'),
            ])
            ->acceptJson()
            ->post('https://api.postmarkapp.com/email', [
                'From' => config('weightsy.postmark.from'),
                'To' => $to,
                'Subject' => $subject,
                'TextBody' => $textBody,
                'MessageStream' => 'outbound',
            ]);

        if ($response->failed()) {
            throw new RuntimeException('Postmark send failed: '.$response->body());
        }

        return $response->json();
    }
}
