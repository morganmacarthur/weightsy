<?php

namespace App\Services;

use App\DataTransferObjects\InboundMailboxMessage;
use Carbon\CarbonImmutable;

class InboundEmailParser
{
    public function parse(string $uid, string $rawHeaders, string $rawBody): InboundMailboxMessage
    {
        $headers = $this->parseHeaders($rawHeaders);
        $from = $this->extractAddress($headers['from'] ?? '') ?? '';
        $subject = $this->decodeHeaderValue($headers['subject'] ?? null);
        $receivedAt = isset($headers['date']) ? CarbonImmutable::parse($headers['date']) : null;
        $text = trim($this->extractTextBody($headers, $rawBody));

        return new InboundMailboxMessage(
            uid: $uid,
            from: $from,
            subject: $subject,
            text: $text,
            receivedAt: $receivedAt,
            rawHeaders: $rawHeaders,
            rawBody: $rawBody,
        );
    }

    public function parseHeaders(string $rawHeaders): array
    {
        $lines = preg_split("/\r\n|\n|\r/", $rawHeaders) ?: [];
        $headers = [];
        $current = null;

        foreach ($lines as $line) {
            if ($line === '') {
                continue;
            }

            if (preg_match('/^\s+/', $line) === 1 && $current !== null) {
                $headers[$current] .= ' '.trim($line);
                continue;
            }

            [$name, $value] = array_pad(explode(':', $line, 2), 2, null);

            if ($value === null) {
                continue;
            }

            $current = strtolower(trim($name));
            $headers[$current] = trim($value);
        }

        return $headers;
    }

    private function extractTextBody(array $headers, string $rawBody): string
    {
        $contentType = strtolower($headers['content-type'] ?? 'text/plain');
        $encoding = strtolower($headers['content-transfer-encoding'] ?? '');

        if (str_starts_with($contentType, 'multipart/')) {
            $boundary = $this->extractBoundary($contentType);

            if ($boundary !== null) {
                $parts = preg_split('/--'.preg_quote($boundary, '/').'(?:--)?\s*/', $rawBody) ?: [];
                $fallback = '';

                foreach ($parts as $part) {
                    $part = ltrim($part);

                    if ($part === '') {
                        continue;
                    }

                    [$partHeadersRaw, $partBody] = preg_split("/\r\n\r\n|\n\n|\r\r/", $part, 2) + [null, ''];
                    if ($partHeadersRaw === null) {
                        continue;
                    }

                    $partHeaders = $this->parseHeaders($partHeadersRaw);
                    $partContentType = strtolower($partHeaders['content-type'] ?? 'text/plain');
                    $decodedBody = $this->decodeBody($partBody, strtolower($partHeaders['content-transfer-encoding'] ?? ''), $partContentType);

                    if (str_starts_with($partContentType, 'text/plain')) {
                        return $decodedBody;
                    }

                    if ($fallback === '' && str_starts_with($partContentType, 'text/html')) {
                        $fallback = trim(strip_tags($decodedBody));
                    }
                }

                return $fallback;
            }
        }

        return $this->decodeBody($rawBody, $encoding, $contentType);
    }

    private function decodeBody(string $body, string $encoding, string $contentType): string
    {
        $decoded = match ($encoding) {
            'base64' => base64_decode($body, true) ?: $body,
            'quoted-printable' => quoted_printable_decode($body),
            default => $body,
        };

        $charset = $this->extractCharset($contentType);

        if ($charset !== null && strtoupper($charset) !== 'UTF-8') {
            $converted = @mb_convert_encoding($decoded, 'UTF-8', $charset);
            if ($converted !== false) {
                $decoded = $converted;
            }
        }

        if (str_starts_with($contentType, 'text/html')) {
            return trim(strip_tags($decoded));
        }

        return trim($decoded);
    }

    private function extractBoundary(string $contentType): ?string
    {
        if (preg_match('/boundary="?([^";]+)"?/i', $contentType, $matches) !== 1) {
            return null;
        }

        return $matches[1];
    }

    private function extractCharset(string $contentType): ?string
    {
        if (preg_match('/charset="?([^";]+)"?/i', $contentType, $matches) !== 1) {
            return null;
        }

        return strtoupper($matches[1]);
    }

    private function extractAddress(?string $fromHeader): ?string
    {
        if ($fromHeader === null) {
            return null;
        }

        $decoded = $this->decodeHeaderValue($fromHeader) ?? $fromHeader;

        if (preg_match('/<([^>]+)>/', $decoded, $matches) === 1) {
            return trim($matches[1]);
        }

        if (preg_match('/[A-Z0-9._%+\-]+@[A-Z0-9.\-]+\.[A-Z]{2,}/i', $decoded, $matches) === 1) {
            return trim($matches[0]);
        }

        return trim($decoded) !== '' ? trim($decoded) : null;
    }

    private function decodeHeaderValue(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $decoded = @iconv_mime_decode($value, ICONV_MIME_DECODE_CONTINUE_ON_ERROR, 'UTF-8');

        if ($decoded !== false) {
            return trim($decoded);
        }

        return trim(mb_decode_mimeheader($value));
    }
}
