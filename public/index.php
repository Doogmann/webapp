<?php
declare(strict_types=1);
require __DIR__.'/../src/bootstrap.php';
require __DIR__.'/../src/views.php';

$p = path();

if ($p === '/health') { header('Content-Type:text/plain; charset=utf-8'); echo 'OK'; exit; }

if ($p === '/contact' && ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    if (!check_csrf($_POST['csrf'] ?? '')) {
        http_response_code(400);
        echo layout('Kontakt', '<section class="card">'.contact_form('Ogiltig CSRF-token.').'</section>'); exit;
    }
    $name = trim((string)($_POST['name'] ?? ''));
    $message = trim((string)($_POST['message'] ?? ''));
    if ($name === '' || $message === '' || mb_strlen($name) > 120 || mb_strlen($message) > 1000) {
        echo layout('Kontakt', '<section class="card">'.contact_form('Fyll i både namn och meddelande (max 120/1000).').'</section>'); exit;
    }
    $ip = (string)($_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? 'unknown');
    if (!save_message($name, $message, $ip)) {
        http_response_code(500);
        echo layout('Kontakt', '<section class="card">'.contact_form('Kunde inte spara just nu. Försök igen senare.').'</section>'); exit;
    }
    echo layout('Tack', '<section class="card">'.success_view($name, $message).'</section>'); exit;
}

if ($p === '/contact') {
    echo layout('Kontakt', '<section class="card"><h2>Kontakt</h2>'.contact_form().'</section>'); exit;
}

if ($p === '/messages') {
    echo layout('Meddelanden', '<section class="card"><h2>Meddelanden</h2>'.messages_list(load_messages(200)).'</section>'); exit;
}

// Hem
echo layout('Hem', hero(
    'Hej från min PHP-app i Docker! 🚀',
    'PHP-FPM + Nginx · Docker · Azure Web App for Containers',
    '/contact', 'Skriv ett meddelande'
));
