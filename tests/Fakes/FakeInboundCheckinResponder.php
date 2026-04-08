<?php

namespace Tests\Fakes;

use App\DataTransferObjects\InboundMessageData;
use App\DataTransferObjects\InboundProcessingResult;
use App\Services\InboundCheckinResponder;
use App\Services\PostmarkMailer;

class FakeInboundCheckinResponder extends InboundCheckinResponder
{
    public array $sent = [];

    public function __construct()
    {
        parent::__construct(new class extends PostmarkMailer {
            public function __construct()
            {
            }

            public function isConfigured(): bool
            {
                return false;
            }
        });
    }

    public function send(InboundMessageData $inbound, InboundProcessingResult $result): void
    {
        $this->sent[] = [
            'to' => $inbound->from,
            'recognized' => $result->recognized,
            'message_id' => $result->message->id,
        ];
    }
}
