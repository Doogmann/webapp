<?php
declare(strict_types=1);
require __DIR__.'/../src/bootstrap.php';
require __DIR__.'/../src/views.php';

$path = strtok((string)($_SERVER['REQUEST_URI'] ?? '/'), '?') ?: '/';

if ($path === '/health') {
    header('Content-Type: text/plain; charset=utf-8'); echo 'OK'; exit;
}

if ($path === '/contact' && ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    if (!check_csrf($_POST['csrf'] ?? '')) {
        http_response_code(400);
        echo layout('Kontakt', contact_form('Ogiltig CSRF-token.')); exit;
    }
    $name = trim((string)($_POST['name'] ?? ''));
    $message = trim((string)($_POST['message'] ?? ''));

    // enkel validering
    if ($name === '' || $message === '') {
        echo layout('Kontakt', contact_form('Fyll i både namn och meddelande.')); exit;
    }
    if (mb_strlen($name) > 120 || mb_strlen($message) > 1000) {
        echo layout('Kontakt', contact_form('För långa fält.')); exit;
    }

    $ip = (string)($_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? 'unknown');

    if (!save_message($name, $message, $ip)) {
        http_response_code(500);
        echo layout('Kontakt', contact_form('Kunde inte spara just nu. Försök igen senare.')); exit;
    }

    echo layout('Tack', success_view($name, $message)); exit;
}

if ($path === '/contact') {
    echo layout('Kontakt', contact_form()); exit;
}

if ($path === '/messages') {
    $rows = load_messages(200);
    echo layout('Meddelanden', messages_list($rows)); exit;
}

// Home
$home = '<section style="background:#151518;padding:2rem;border-radius:16px;">'
      . '<h1>Hej från min PHP-app i Docker! 🚀</h1>'
      . '<p>PHP-FPM + Nginx + Docker + Azure Web App for Containers</p>'
      . '<p><a href="/contact" style="color:#9be7b0;">Skriv ett meddelande</a></p>'
      . '</section>';
echo layout('Hem', $home);
