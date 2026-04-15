<?php

namespace App\Http\Controllers;

use App\DataTransferObjects\InboundMessageData;
use App\Services\ContactChannelGuesser;
use App\Services\InboundCheckinProcessor;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InboundCheckinController extends Controller
{
    public function __construct(
        private readonly InboundCheckinProcessor $processor,
        private readonly ContactChannelGuesser $channelGuesser,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'from' => ['required', 'string', 'max:255'],
            'channel' => ['nullable', 'in:email,mms'],
            'subject' => ['nullable', 'string', 'max:255'],
            'text' => ['nullable', 'string', 'max:1000'],
            'received_at' => ['nullable', 'date'],
            'provider' => ['nullable', 'string', 'max:50'],
            'external_id' => ['nullable', 'string', 'max:255'],
        ]);

        $inbound = new InboundMessageData(
            externalId: $validated['external_id'] ?? null,
            from: $validated['from'],
            channel: $validated['channel'] ?? $this->channelGuesser->guess($validated['from']),
            subject: $validated['subject'] ?? null,
            text: trim((string) ($validated['text'] ?? $validated['subject'] ?? '')),
            receivedAt: isset($validated['received_at']) ? CarbonImmutable::parse($validated['received_at']) : null,
            provider: $validated['provider'] ?? 'self_hosted',
            metadata: [],
        );

        $result = $this->processor->process($inbound);

        if (! $result->recognized) {
            return response()->json([
                'status' => 'unrecognized',
                'message' => 'Supported formats are weight like 123, blood pressure like 120/70, or body fat like 14.0%.',
                'message_id' => $result->message->id,
                'duplicate' => $result->duplicate,
            ], 422);
        }

        return response()->json([
            'status' => 'recorded',
            'created_user' => $result->createdUser,
            'metric_type' => $result->checkin?->metric_type,
            'normalized_input' => $result->parsedCheckin?->normalizedDisplay,
            'user_id' => $result->user?->id,
            'checkin_id' => $result->checkin?->id,
            'message_id' => $result->message->id,
            'duplicate' => $result->duplicate,
        ], $result->createdUser ? 201 : 200);
    }
}
