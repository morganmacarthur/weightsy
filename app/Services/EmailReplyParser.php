<?php

namespace App\Services;

class EmailReplyParser
{
    public function extractCheckinCandidate(string $text): string
    {
        $normalized = str_replace(["\r\n", "\r"], "\n", trim($text));

        if ($normalized === '') {
            return '';
        }

        $lines = explode("\n", $normalized);
        $replyLines = [];

        foreach ($lines as $line) {
            $trimmed = trim($line);

            if ($this->isQuotedReplyBoundary($trimmed) || $this->isQuotedLine($trimmed)) {
                break;
            }

            if ($this->isSignatureBoundary($trimmed)) {
                break;
            }

            $replyLines[] = $line;
        }

        $replyText = trim(implode("\n", $replyLines));

        if ($replyText === '') {
            return '';
        }

        foreach (preg_split('/\n+/', $replyText) ?: [] as $line) {
            $candidate = trim($line);

            if ($candidate === '') {
                continue;
            }

            return $candidate;
        }

        return '';
    }

    private function isQuotedReplyBoundary(string $line): bool
    {
        if ($line === '') {
            return false;
        }

        return preg_match('/^(On .+wrote:|From:\s|Sent:\s|Subject:\s|To:\s|---+ ?Original Message ?---+)$/i', $line) === 1;
    }

    private function isQuotedLine(string $line): bool
    {
        return str_starts_with($line, '>');
    }

    private function isSignatureBoundary(string $line): bool
    {
        return in_array($line, ['--', '__'], true);
    }
}
