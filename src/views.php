<?php
function layout(string $title, string $content): void {
    $app = e(getenv('APP_NAME') ?: 'WebApp');
    echo <<<HTML
<!doctype html>
<html lang="sv">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>{$title} – {$app}</title>
<style>
  :root { --bg:#0b0b0b; --fg:#e6e6e6; --accent:#73e2a7; --muted:#9aa0a6; }
  *{box-sizing:border-box} body{margin:0;background:var(--bg);color:var(--fg);font:16px/1.5 system-ui,Segoe UI,Roboto,Helvetica,Arial}
  header{padding:20px;border-bottom:1px solid #222;}
  .wrap{max-width:900px;margin:0 auto;padding:24px}
  nav a{color:var(--fg);text-decoration:none;margin-right:16px}
  nav a:hover{color:var(--accent)}
  .card{background:#121212;border:1px solid #1e1e1e;border-radius:16px;padding:22px;box-shadow:0 4px 16px rgba(0,0,0,.25)}
  .btn{background:var(--accent);color:#000;border:0;border-radius:10px;padding:10px 14px;font-weight:600;cursor:pointer}
  input,textarea{width:100%;background:#0f0f0f;color:var(--fg);border:1px solid #1e1e1e;border-radius:10px;padding:10px}
  .muted{color:var(--muted)}
  footer{padding:24px;border-top:1px solid #222;color:var(--muted)}
</style>
</head>
<body>
  <header class="wrap">
    <nav>
      <strong>{$app}</strong>
      <a href="/">Hem</a>
      <a href="/contact">Kontakt</a>
      <a href="/health">Health</a>
    </nav>
  </header>
  <main class="wrap">
    {$content}
  </main>
  <footer class="wrap">
    <span class="muted">© {$app}</span>
  </footer>
</body>
</html>
HTML;
}
