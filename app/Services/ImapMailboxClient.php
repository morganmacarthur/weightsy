<?php

namespace App\Services;

use App\Contracts\InboundMailbox;
use App\DataTransferObjects\InboundMailboxMessage;
use RuntimeException;

class ImapMailboxClient implements InboundMailbox
{
    private $stream = null;

    private int $tagCounter = 0;

    public function __construct(
        private readonly InboundEmailParser $parser,
    ) {
    }

    public function unreadMessages(int $limit = 25): array
    {
        $this->connect();

        try {
            $this->selectMailbox();
            $uids = array_slice($this->searchUnread(), 0, $limit);
            $messages = [];

            foreach ($uids as $uid) {
                $headers = $this->fetchLiteral($uid, 'BODY.PEEK[HEADER]');
                $body = $this->fetchLiteral($uid, 'BODY.PEEK[TEXT]');
                $messages[] = $this->parser->parse($uid, $headers, $body);
            }

            return $messages;
        } finally {
            $this->disconnect();
        }
    }

    public function markProcessed(InboundMailboxMessage $message, bool $delete): void
    {
        $this->connect();

        try {
            $this->selectMailbox();
            $this->storeFlags($message->uid, ['\\Seen']);

            if ($delete) {
                $this->storeFlags($message->uid, ['\\Deleted']);
                $this->command('EXPUNGE');
            }
        } finally {
            $this->disconnect();
        }
    }

    private function connect(): void
    {
        if (is_resource($this->stream)) {
            return;
        }

        $host = config('weightsy.imap.host');
        $port = (int) config('weightsy.imap.port', 993);
        $encryption = config('weightsy.imap.encryption', 'ssl');
        $remote = sprintf('%s://%s:%d', $encryption === 'ssl' ? 'ssl' : 'tcp', $host, $port);

        $stream = @stream_socket_client($remote, $errorCode, $errorMessage, 15);

        if (! is_resource($stream)) {
            throw new RuntimeException("Unable to connect to IMAP server: {$errorMessage} ({$errorCode})");
        }

        stream_set_timeout($stream, 15);
        $this->stream = $stream;
        $this->readLine();

        $this->command('LOGIN '.$this->quote((string) config('weightsy.imap.username')).' '.$this->quote((string) config('weightsy.imap.password')));
    }

    private function disconnect(): void
    {
        if (! is_resource($this->stream)) {
            return;
        }

        try {
            $this->command('LOGOUT');
        } catch (\Throwable) {
        }

        fclose($this->stream);
        $this->stream = null;
    }

    private function selectMailbox(): void
    {
        $this->command('SELECT '.$this->quote((string) config('weightsy.imap.mailbox', 'INBOX')));
    }

    /**
     * @return list<string>
     */
    private function searchUnread(): array
    {
        $response = $this->command('UID SEARCH UNSEEN');

        foreach ($response as $chunk) {
            if (is_string($chunk) && preg_match('/^\* SEARCH(.*)$/m', $chunk, $matches) === 1) {
                $ids = preg_split('/\s+/', trim($matches[1])) ?: [];
                return array_values(array_filter($ids, fn ($id) => $id !== ''));
            }
        }

        return [];
    }

    private function fetchLiteral(string $uid, string $section): string
    {
        $response = $this->command(sprintf('UID FETCH %s (%s)', $uid, $section));

        foreach ($response as $index => $chunk) {
            if (is_string($chunk) && preg_match('/\{(\d+)\}\r?\n$/', $chunk) === 1) {
                return (string) ($response[$index + 1] ?? '');
            }
        }

        return '';
    }

    private function storeFlags(string $uid, array $flags): void
    {
        $this->command(sprintf('UID STORE %s +FLAGS.SILENT (%s)', $uid, implode(' ', $flags)));
    }

    /**
     * @return list<string>
     */
    private function command(string $command): array
    {
        if (! is_resource($this->stream)) {
            throw new RuntimeException('IMAP connection is not open.');
        }

        $tag = 'A'.str_pad((string) ++$this->tagCounter, 4, '0', STR_PAD_LEFT);
        fwrite($this->stream, $tag.' '.$command."\r\n");

        return $this->readResponse($tag);
    }

    /**
     * @return list<string>
     */
    private function readResponse(string $tag): array
    {
        $chunks = [];

        while (is_resource($this->stream) && ! feof($this->stream)) {
            $line = $this->readLine();
            $chunks[] = $line;

            if (preg_match('/\{(\d+)\}\r?\n$/', $line, $matches) === 1) {
                $chunks[] = $this->readBytes((int) $matches[1]);
                continue;
            }

            if (str_starts_with($line, $tag.' ')) {
                if (! str_contains($line, 'OK')) {
                    throw new RuntimeException('IMAP command failed: '.trim($line));
                }

                break;
            }
        }

        return $chunks;
    }

    private function readLine(): string
    {
        $line = fgets($this->stream);

        if ($line === false) {
            throw new RuntimeException('Unexpected end of IMAP stream.');
        }

        return $line;
    }

    private function readBytes(int $bytes): string
    {
        $buffer = '';

        while (strlen($buffer) < $bytes) {
            $chunk = fread($this->stream, $bytes - strlen($buffer));

            if ($chunk === false || $chunk === '') {
                throw new RuntimeException('Unable to read IMAP literal payload.');
            }

            $buffer .= $chunk;
        }

        return $buffer;
    }

    private function quote(string $value): string
    {
        return '"'.addcslashes($value, "\\\"").'"';
    }
}
