<?php
declare(strict_types=1);

// === Prod-vänliga inställningar ===
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
ini_set('display_errors', '0');
ini_set('log_errors', '1');
ini_set('error_log', '/proc/self/fd/2');

ini_set('session.cookie_httponly', '1');
ini_set('session.cookie_secure', '1');     // Azure kör HTTPS
ini_set('session.cookie_samesite', 'Lax');
if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }

// === Helpers ===
function e(string $s): string {
    return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

// CSRF
if (empty($_SESSION['csrf'])) { $_SESSION['csrf'] = bin2hex(random_bytes(32)); }
function csrf_field(): string { return '<input type="hidden" name="csrf" value="'.$_SESSION['csrf'].'">'; }
function check_csrf($t): bool { return isset($_SESSION['csrf']) && hash_equals($_SESSION['csrf'], (string)$t); }

// Tidszon
function app_timezone(): DateTimeZone {
    $tz = getenv('APP_TZ') ?: getenv('TZ') ?: 'Europe/Stockholm';
    try { return new DateTimeZone($tz); } catch (Throwable $e) { return new DateTimeZone('UTC'); }
}

// CSP-nonce + säkerhetsheaders
$GLOBALS['CSP_NONCE'] = base64_encode(random_bytes(16));
$cspNonce = $GLOBALS['CSP_NONCE'];

header("Content-Security-Policy: "
    // bara egen origin
  . "default-src 'self'; "
    // bilder + inline CSS (enklare styling)
  . "img-src 'self' data:; style-src 'self' 'unsafe-inline'; "
    // endast script med korrekt nonce får köras (canvas-bakgrunden)
  . "script-src 'self' 'nonce-$cspNonce'; "
  . "object-src 'none'; base-uri 'none'; frame-ancestors 'none'"
);
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('Permissions-Policy: geolocation=(), microphone=(), camera=()');
header('Cross-Origin-Opener-Policy: same-origin');
// X-XSS-Protection avstängd (för att inte trigga gamla filter)
header('X-XSS-Protection: 0');

// Exponera nonce till views
function csp_nonce(): string { return (string)($GLOBALS['CSP_NONCE'] ?? ''); }

// === Router helper ===
function path(): string {
    return strtok((string)($_SERVER['REQUEST_URI'] ?? '/'), '?') ?: '/';
}

// === Lagring av meddelanden (JSON Lines) ===
function message_store_path(): string {
    $path = getenv('MESSAGE_STORE');
    if (!$path) $path = __DIR__ . '/../storage/messages.jsonl'; // fallback lokalt
    return $path;
}

function ensure_storage_dir(): void {
    $dir = dirname(message_store_path());
    if (!is_dir($dir)) {
        @mkdir($dir, 0777, true);
        @chmod($dir, 0777);
    }
}

function save_message(string $name, string $message, string $ip): bool {
    ensure_storage_dir();
    $row = [
        'ts'      => (new DateTimeImmutable('now', new DateTimeZone('UTC')))->format(DateTimeInterface::ATOM),
        'name'    => $name,
        'message' => $message,
        'ip'      => $ip,
        'ua'      => (string)($_SERVER['HTTP_USER_AGENT'] ?? '')
    ];
    $json = json_encode($row, JSON_UNESCAPED_UNICODE);
    if ($json === false) return false;
    return (bool)@file_put_contents(message_store_path(), $json . PHP_EOL, FILE_APPEND | LOCK_EX);
}

function load_messages(int $limit = 200): array {
    $f = message_store_path();
    if (!is_file($f)) return [];
    $lines = @file($f, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
    $out = [];
    foreach (array_reverse($lines) as $line) {
        $row = json_decode($line, true);
        if (is_array($row)) $out[] = $row;
        if (count($out) >= $limit) break;
    }
    return $out;
}
