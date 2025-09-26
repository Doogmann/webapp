<?php
declare(strict_types=1);

// Sessions + security headers (du har redan detta i din fil)
session_set_cookie_params(['secure'=>true,'httponly'=>true,'samesite'=>'Lax']);
if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }
header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");
header("Referrer-Policy: strict-origin-when-cross-origin");
header("Permissions-Policy: geolocation=()");
header("Content-Security-Policy: default-src 'self'; img-src 'self' data:; style-src 'self' 'unsafe-inline'");

// CSRF helpers (du har troligen redan liknande)
if (empty($_SESSION['csrf'])) { $_SESSION['csrf'] = bin2hex(random_bytes(32)); }
function csrf_field(): string { return '<input type="hidden" name="csrf" value="'.$_SESSION['csrf'].'">'; }
function check_csrf($t): bool { return isset($_SESSION['csrf']) && hash_equals($_SESSION['csrf'], (string)$t); }

// ---- Storage helpers ----

// Var filen ska ligga: env-variabel eller fallback till ./storage/messages.json
function message_store_path(): string {
    $path = getenv('MESSAGE_STORE');
    if (!$path) {
        $path = __DIR__ . '/../storage/messages.jsonl';
    }
    return $path;
}

// Säkert spara en post (append JSON + newline, med fil-lås)
function save_message(string $name, string $message, string $ip): bool {
    $dir = dirname(message_store_path());
    if (!is_dir($dir)) {
        @mkdir($dir, 0775, true);
    }
    $row = [
        'ts'       => (new DateTimeImmutable('now', new DateTimeZone('UTC')))->format(DateTimeInterface::ATOM),
        'name'     => $name,
        'message'  => $message,
        'ip'       => $ip,
        'ua'       => (string)($_SERVER['HTTP_USER_AGENT'] ?? '')
    ];
    $json = json_encode($row, JSON_UNESCAPED_UNICODE);
    if ($json === false) return false;
    return (bool) @file_put_contents(message_store_path(), $json.PHP_EOL, FILE_APPEND | LOCK_EX);
}

// Läs de senaste N posterna (senaste först)
function load_messages(int $limit = 100): array {
    $file = message_store_path();
    if (!is_file($file)) return [];
    $lines = @file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if (!$lines) return [];
    $out = [];
    foreach (array_reverse($lines) as $line) {
        $row = json_decode($line, true);
        if (is_array($row)) { $out[] = $row; }
        if (count($out) >= $limit) break;
    }
    return $out;
}

// Enkel sanering (ytterligare validering sker i controller)
function e(string $s): string {
    return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

// Välj appens tidszon via env, fallback till Europe/Stockholm
function app_timezone(): DateTimeZone {
    $tz = getenv('APP_TZ') ?: getenv('TZ') ?: 'Europe/Stockholm';
    try { return new DateTimeZone($tz); }
    catch (Throwable $e) { return new DateTimeZone('UTC'); }
}
