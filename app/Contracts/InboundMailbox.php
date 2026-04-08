<?php

namespace App\Contracts;

use App\DataTransferObjects\InboundMailboxMessage;

interface InboundMailbox
{
    /**
     * @return list<InboundMailboxMessage>
     */
    public function unreadMessages(int $limit = 25): array;

    public function markProcessed(InboundMailboxMessage $message, bool $delete): void;
}
