<?php

namespace Tests\Fakes;

use App\Contracts\InboundMailbox;
use App\DataTransferObjects\InboundMailboxMessage;

class FakeInboundMailbox implements InboundMailbox
{
    /**
     * @param  list<InboundMailboxMessage>  $messages
     */
    public function __construct(
        private array $messages = [],
        public array $marked = [],
    ) {
    }

    public function unreadMessages(int $limit = 25): array
    {
        return array_slice($this->messages, 0, $limit);
    }

    public function markProcessed(InboundMailboxMessage $message, bool $delete): void
    {
        $this->marked[] = [
            'uid' => $message->uid,
            'delete' => $delete,
        ];
    }
}
