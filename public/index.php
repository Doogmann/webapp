<?php
declare(strict_types=1);
require __DIR__.'/../src/bootstrap.php';
require __DIR__.'/../src/views.php';

$path = path();

if ($path === '/health') { header('Content-Type:text/plain'); echo 'OK'; exit; }

if ($path === '/contact' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_ok()) {
        http_response_code(419);
        layout('CSRF-fel', '<div class="card"><h2>Ogiltig förfrågan</h2><p>Försök igen.</p></div>');
        exit;
    }
    $name = trim((string)($_POST['name'] ?? ''));
    $msg  = trim((string)($_POST['message'] ?? ''));
    if ($name === '' || $msg === '') {
        layout('Kontakt', '<div class="card"><h2>Kontakt</h2><p>Fyll i namn och meddelande.</p></div>');
        exit;
    }
    $summary = '<div class="card"><h2>Tack!</h2><p>Vi hör av oss, '.e($name).'.</p><p class="muted">Meddelande:</p><pre>'.e($msg).'</pre></div>';
    layout('Tack', $summary);
    exit;
}

if ($path === '/contact' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    ob_start(); ?>
    <div class="card">
      <h2>Kontakt</h2>
      <form method="post" action="/contact" autocomplete="off">
        <?= csrf_field(); ?>
        <label>Namn</label>
        <input name="name" minlength="2" maxlength="80" required>
        <label style="margin-top:10px">Meddelande</label>
        <textarea name="message" rows="4" minlength="3" maxlength="1000" required></textarea>
        <div style="margin-top:12px">
          <button class="btn" type="submit">Skicka</button>
        </div>
      </form>
    </div>
    <?php layout('Kontakt', ob_get_clean()); exit;
}

if ($path === '/') {
    $hero = <<<HTML
    <div class="card">
      <h1 style="margin:0 0 6px 0">Hej från min PHP-app i Docker! 🚀</h1>
      <p class="muted">PHP-FPM + Nginx · Docker · Azure Web App for Containers</p>
      <p><a class="btn" href="/contact">Skriv ett meddelande</a></p>
    </div>
HTML;
    layout('Hem', $hero); exit;
}

// (valfritt) säkrad phpinfo – aktivera via APP_DEBUG_INFO=1 i miljön
if ($path === '/info' && getenv('APP_DEBUG_INFO') === '1') { phpinfo(); exit; }

// 404
http_response_code(404);
layout('Saknas', '<div class="card"><h2>404</h2><p>Sidan finns inte.</p></div>');
