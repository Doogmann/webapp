<?php

function layout(string $title, string $content): string {
    return '<!doctype html><meta charset="utf-8"><title>'.e($title).'</title>'
        . '<link rel="preconnect" href="/" />'
        . '<body style="background:#0b0b0d;color:#e6e6e6;font:16px/1.5 system-ui;margin:0;">'
        . '<header style="max-width:1100px;margin:1.25rem auto;padding:0 1rem;">'
        . '<nav style="display:flex;gap:1rem;"><strong>WebApp</strong> '
        . '<a href="/" style="color:#9be7b0;">Hem</a>'
        . '<a href="/contact" style="color:#9be7b0;">Kontakt</a>'
        . '<a href="/messages" style="color:#9be7b0;">Meddelanden</a>'
        . '<a href="/health" style="color:#9be7b0;">Health</a>'
        . '</nav></header>'
        . '<main style="max-width:1100px;margin:1rem auto;padding:0 1rem;">'.$content.'</main>'
        . '<footer style="max-width:1100px;margin:2rem auto;padding:0 1rem;opacity:.7;">© WebApp</footer>'
        . '</body>';
}

function contact_form(?string $error = null): string {
    $err = $error ? '<p style="color:#ff8080;">'.e($error).'</p>' : '';
    return '<section style="background:#151518;padding:2rem;border-radius:16px;">'
        . '<h2>Kontakt</h2>'.$err
        . '<form method="post" action="/contact" style="display:grid;gap:.75rem;">'
        . '<label>Namn</label><input name="name" maxlength="120" style="padding:.6rem;border-radius:8px;background:#0e0e11;color:#e6e6e6;border:1px solid #2a2a2f;">'
        . '<label>Meddelande</label><textarea name="message" rows="4" maxlength="1000" style="padding:.6rem;border-radius:8px;background:#0e0e11;color:#e6e6e6;border:1px solid #2a2a2f;"></textarea>'
        . csrf_field()
        . '<button style="margin-top:.5rem;background:#8be3a3;color:#0b0b0d;border:none;padding:.6rem 1rem;border-radius:10px;cursor:pointer;">Skicka</button>'
        . '</form></section>';
}

function success_view(string $name, string $message): string {
    return '<section style="background:#151518;padding:2rem;border-radius:16px;">'
        . '<h2>Tack!</h2>'
        . '<p><strong>Namn:</strong> '.e($name).'</p>'
        . '<p><strong>Meddelande:</strong> <code>'.e($message).'</code></p>'
        . '<p><a href="/messages" style="color:#9be7b0;">Visa alla meddelanden</a></p>'
        . '</section>';
}

function messages_list(array $rows): string {
    if (!$rows) return '<p>Inga meddelanden sparade ännu.</p>';

    $items = '';
    $tz = app_timezone();

    foreach ($rows as $r) {
        $iso = (string)($r['ts'] ?? '');
        // Snygg format + lokal tidszon
        $tsOut = $iso;
        if ($iso !== '') {
            try {
                $dt = new DateTimeImmutable($iso);
                $dt = $dt->setTimezone($tz);
                $tsOut = $dt->format('Y-m-d H:i:s'); // ex: 2025-09-26 09:40:44
            } catch (Throwable $e) {
                $tsOut = e($iso); // visa original om något går fel
            }
        }

        $name = e((string)($r['name'] ?? ''));
        $msg  = nl2br(e((string)($r['message'] ?? '')));

        $items .= '<li style="padding:.75rem 0;border-bottom:1px solid #222;">'
                . '<div style="opacity:.8;font-size:.9rem;">'.$tsOut.'</div>'
                . '<div><strong>'.$name.'</strong></div>'
                . '<div style="white-space:pre-wrap">'.$msg.'</div>'
                . '</li>';
    }

    return '<section style="background:#151518;padding:2rem;border-radius:16px;">'
         . '<h2>Meddelanden</h2>'
         . '<ul style="list-style:none;padding:0;margin:0;">'.$items.'</ul>'
         . '</section>';
}
