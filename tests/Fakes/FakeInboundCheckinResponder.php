<?php

namespace Tests\Fakes;

use App\DataTransferObjects\InboundMessageData;
use App\DataTransferObjects\InboundProcessingResult;
use App\Services\InboundCheckinResponder;
use App\Services\OutboundMessageLogger;

class FakeInboundCheckinResponder extends InboundCheckinResponder
{
    public array $sent = [];

    public function __construct()
    {
        parent::__construct(new OutboundMessageLogger);
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
