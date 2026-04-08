<?php

namespace App\Console\Commands;

use App\Contracts\InboundMailbox;
use App\DataTransferObjects\InboundMessageData;
use App\Services\ContactChannelGuesser;
use App\Services\InboundCheckinProcessor;
use App\Services\InboundCheckinResponder;
use App\Services\InboundMessageClassifier;
use Illuminate\Console\Command;
use Throwable;

class PollImapInbox extends Command
{
    protected $signature = 'weightsy:imap:poll {--limit=25 : Maximum unread messages to process} {--delete : Mark processed messages for deletion}';

    protected $description = 'Poll the IMAP inbox for unread Weightsy check-ins';

    public function handle(
        InboundMailbox $mailbox,
        ContactChannelGuesser $channelGuesser,
        InboundMessageClassifier $classifier,
        InboundCheckinProcessor $processor,
        InboundCheckinResponder $responder,
    ): int {
        $limit = (int) $this->option('limit');
        $delete = (bool) ($this->option('delete') || config('weightsy.imap.delete_after_processing'));
        $processed = 0;

        $messages = $mailbox->unreadMessages($limit);

        foreach ($messages as $message) {
            try {
                if ($classifier->shouldSkip($message)) {
                    $mailbox->markProcessed($message, $delete);
                    $this->line(sprintf('Skipped UID %s from %s', $message->uid, $message->from));
                    continue;
                }

                $inbound = new InboundMessageData(
                    externalId: $message->uid,
                    from: $message->from,
                    channel: $channelGuesser->guess($message->from),
                    subject: $message->subject,
                    text: $message->text,
                    receivedAt: $message->receivedAt,
                    provider: 'imap',
                    metadata: [
                        'imap_uid' => $message->uid,
                        'raw_headers' => $message->rawHeaders,
                    ],
                );

                $result = $processor->process($inbound);
                $responder->send($inbound, $result);
                $mailbox->markProcessed($message, $delete);
                $processed++;

                $this->line(sprintf(
                    'Processed UID %s from %s (%s)',
                    $message->uid,
                    $message->from,
                    $result->recognized ? ($result->parsedCheckin?->normalizedDisplay ?? 'recorded') : 'unrecognized'
                ));
            } catch (Throwable $throwable) {
                report($throwable);
                $this->error(sprintf('Failed to process UID %s: %s', $message->uid, $throwable->getMessage()));
            }
        }

        $this->info("Processed {$processed} inbox message(s).");

        return self::SUCCESS;
    }
}
