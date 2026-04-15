<?php

namespace App\Services;

use App\Models\ContactPoint;
use App\Models\Message;
use App\Models\User;

class OutboundMessageLogger
{
    public function log(
        ?User $user,
        ?ContactPoint $contactPoint,
        string $channel,
        string $provider,
        string $to,
        string $subject,
        string $body,
        array $metadata = [],
        ?string $externalId = null,
        ?string $inReplyTo = null,
    ): Message {
        $message = Message::query()->create([
            'user_id' => $user?->id,
            'contact_point_id' => $contactPoint?->id,
            'direction' => 'outbound',
            'channel' => $channel,
            'provider' => $provider,
            'external_id' => $externalId,
            'in_reply_to' => $inReplyTo,
            'subject' => $subject,
            'body_text' => $body,
            'parsed_status' => 'sent',
            'sent_at' => now(),
            'processed_at' => now(),
            'metadata' => $metadata,
        ]);

        if ($contactPoint !== null) {
            $contactPoint->update([
                'last_outbound_at' => $message->sent_at,
            ]);
        }

        return $message;
    }
}
