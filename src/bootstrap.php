<?php
declare(strict_types=1);

// Kör som "prod" i Azure
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
ini_set('display_errors', '0');
ini_set('log_errors', '1');
ini_set('error_log', '/proc/self/fd/2');

ini_set('session.cookie_httponly', '1');
ini_set('session.cookie_secure', '1');      // Azure kör https→ok
ini_set('session.cookie_samesite', 'Lax');
session_start();

// Liten helper för säker HTML-escaping
function e(string $v): string {
    return htmlspecialchars($v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

// CSRF
if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
}
function csrf_field(): string {
    return '<input type="hidden" name="_token" value="'.e($_SESSION['csrf']).'">';
}
function csrf_ok(): bool {
    return isset($_POST['_token']) && hash_equals($_SESSION['csrf'], (string)($_POST['_token'] ?? ''));
}

// Security headers (CSP via PHP så vi kan justera enkelt vid behov)
$nonce = base64_encode(random_bytes(16));
header("Content-Security-Policy: default-src 'self'; img-src 'self' data:; script-src 'self'; style-src 'self' 'unsafe-inline'; object-src 'none'; base-uri 'none'; frame-ancestors 'none'");
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('Permissions-Policy: geolocation=(), microphone=(), camera=()');
header('Cross-Origin-Opener-Policy: same-origin');
header('X-Frame-Options: DENY'); // backup till CSP
header('X-XSS-Protection: 0');   // moderna browsers, undvik gamla filters

// Enkel router-helper
function path(): string {
    return strtok((string)($_SERVER['REQUEST_URI'] ?? '/'), '?') ?: '/';
}
