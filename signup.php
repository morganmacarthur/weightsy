<?php

declare(strict_types=1);

/**
 * weightsy signup endpoint (privacy-first draft)
 *
 * Stores only hashed identifiers and optional preference fields.
 * Always returns a generic response to avoid account enumeration patterns.
 */

const SIGNUP_DB_PATH = __DIR__ . '/signup.sqlite';
const HASH_PEPPER_ENV = 'WEIGHTSY_HASH_PEPPER';
const FALLBACK_HASH_PEPPER = 'weightsy-dev-pepper-change-me';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    renderMessagePage('Method not allowed.');
    exit;
}

$email = normalizeEmail((string) ($_POST['email'] ?? ''));
$phone = normalizePhone((string) ($_POST['phone'] ?? ''));
$dietStyle = normalizeOptionalText((string) ($_POST['diet'] ?? ''));
$timezone = normalizeOptionalText((string) ($_POST['timezone'] ?? ''));

$emailHash = $email !== '' ? hashIdentifier($email) : null;
$phoneHash = $phone !== '' ? hashIdentifier($phone) : null;

if ($emailHash !== null || $phoneHash !== null) {
    $db = openSignupDb();
    ensureSignupSchema($db);
    insertSignup(
        $db,
        emailHash: $emailHash,
        phoneHash: $phoneHash,
        dietStyle: $dietStyle,
        timezone: $timezone,
        ipHash: hashIdentifier((string) ($_SERVER['REMOTE_ADDR'] ?? '')),
        userAgentHash: hashIdentifier((string) ($_SERVER['HTTP_USER_AGENT'] ?? '')),
        sourcePath: (string) ($_SERVER['HTTP_REFERER'] ?? ''),
    );
}

renderMessagePage('Thanks. If your details were submitted, you are on the early access list.');

function openSignupDb(): SQLite3
{
    $db = new SQLite3(SIGNUP_DB_PATH);
    $db->exec('PRAGMA journal_mode=WAL;');
    $db->exec('PRAGMA foreign_keys=ON;');
    return $db;
}

function ensureSignupSchema(SQLite3 $db): void
{
    $sql = <<<SQL
CREATE TABLE IF NOT EXISTS signup_requests (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  email_hash TEXT,
  phone_hash TEXT,
  diet_style TEXT,
  timezone TEXT,
  ip_hash TEXT,
  user_agent_hash TEXT,
  source_path TEXT,
  created_at INTEGER NOT NULL
);
CREATE INDEX IF NOT EXISTS idx_signup_created_at ON signup_requests(created_at DESC);
CREATE INDEX IF NOT EXISTS idx_signup_email_hash ON signup_requests(email_hash);
CREATE INDEX IF NOT EXISTS idx_signup_phone_hash ON signup_requests(phone_hash);
SQL;
    $db->exec($sql);
}

function insertSignup(
    SQLite3 $db,
    ?string $emailHash,
    ?string $phoneHash,
    string $dietStyle,
    string $timezone,
    string $ipHash,
    string $userAgentHash,
    string $sourcePath
): void {
    $stmt = $db->prepare(
        'INSERT INTO signup_requests (email_hash, phone_hash, diet_style, timezone, ip_hash, user_agent_hash, source_path, created_at)
         VALUES (:email_hash, :phone_hash, :diet_style, :timezone, :ip_hash, :user_agent_hash, :source_path, :created_at)'
    );
    if (!$stmt instanceof SQLite3Stmt) {
        return;
    }
    $stmt->bindValue(':email_hash', $emailHash, $emailHash === null ? SQLITE3_NULL : SQLITE3_TEXT);
    $stmt->bindValue(':phone_hash', $phoneHash, $phoneHash === null ? SQLITE3_NULL : SQLITE3_TEXT);
    $stmt->bindValue(':diet_style', $dietStyle, SQLITE3_TEXT);
    $stmt->bindValue(':timezone', $timezone, SQLITE3_TEXT);
    $stmt->bindValue(':ip_hash', $ipHash, SQLITE3_TEXT);
    $stmt->bindValue(':user_agent_hash', $userAgentHash, SQLITE3_TEXT);
    $stmt->bindValue(':source_path', mb_substr($sourcePath, 0, 255), SQLITE3_TEXT);
    $stmt->bindValue(':created_at', time(), SQLITE3_INTEGER);
    $stmt->execute();
}

function hashIdentifier(string $value): string
{
    $pepper = getenv(HASH_PEPPER_ENV);
    if (!is_string($pepper) || $pepper === '') {
        $pepper = FALLBACK_HASH_PEPPER;
    }
    return hash_hmac('sha256', $value, $pepper);
}

function normalizeEmail(string $email): string
{
    $email = strtolower(trim($email));
    if ($email === '' || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
        return '';
    }
    return $email;
}

function normalizePhone(string $phone): string
{
    $phone = trim($phone);
    if ($phone === '') {
        return '';
    }
    $phone = preg_replace('/[^0-9+]/', '', $phone) ?? '';
    if ($phone === '' || strlen($phone) < 8) {
        return '';
    }
    return $phone;
}

function normalizeOptionalText(string $value): string
{
    $value = trim($value);
    if ($value === '') {
        return '';
    }
    return mb_substr($value, 0, 80);
}

function renderMessagePage(string $message): void
{
    $safe = htmlspecialchars($message, ENT_QUOTES, 'UTF-8');
    echo '<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">';
    echo '<title>weightsy signup</title>';
    echo '<style>body{margin:0;background:#0b0b0d;color:#eef1f7;font-family:ui-sans-serif,-apple-system,Segoe UI,Roboto,Arial,sans-serif}.wrap{max-width:760px;margin:0 auto;padding:24px}a{color:#9feecf}.panel{background:#131318;border:1px solid #2a2d36;border-radius:14px;padding:20px}</style>';
    echo '</head><body><div class="wrap"><div class="panel"><p>' . $safe . '</p><p><a href="/">Return home</a></p></div></div></body></html>';
}

