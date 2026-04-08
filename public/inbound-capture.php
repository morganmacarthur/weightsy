<?php

declare(strict_types=1);

$logDirectory = __DIR__.'/../storage/logs';
$logPath = $logDirectory.'/inbound-capture.log';

if (! is_dir($logDirectory)) {
    mkdir($logDirectory, 0777, true);
}

$headers = function_exists('getallheaders') ? getallheaders() : [];
$rawBody = file_get_contents('php://input');

$files = [];

foreach ($_FILES as $key => $file) {
    $files[$key] = [
        'name' => $file['name'] ?? null,
        'type' => $file['type'] ?? null,
        'size' => $file['size'] ?? null,
        'error' => $file['error'] ?? null,
    ];
}

$payload = [
    'captured_at_utc' => gmdate('c'),
    'method' => $_SERVER['REQUEST_METHOD'] ?? 'UNKNOWN',
    'request_uri' => $_SERVER['REQUEST_URI'] ?? null,
    'remote_addr' => $_SERVER['REMOTE_ADDR'] ?? null,
    'content_type' => $_SERVER['CONTENT_TYPE'] ?? null,
    'content_length' => $_SERVER['CONTENT_LENGTH'] ?? null,
    'query' => $_GET,
    'post' => $_POST,
    'files' => $files,
    'headers' => $headers,
    'raw_body' => $rawBody,
];

$entry = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
file_put_contents($logPath, $entry.PHP_EOL.str_repeat('=', 80).PHP_EOL, FILE_APPEND);

header('Content-Type: application/json');
http_response_code(200);

echo json_encode([
    'status' => 'captured',
    'log_path' => $logPath,
    'captured_at_utc' => $payload['captured_at_utc'],
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
