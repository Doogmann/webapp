<?php

// Baslayout med neon/dark-theme + canvas-bakgrund (CSP-nonce skyddar inline-scriptet)
function layout(string $title, string $content): string {
    $nonce = e(csp_nonce());
    return '<!doctype html><html lang="sv"><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">'
    . '<title>'.e($title).'</title>'
    . '<style>
        :root{--neon:#00f0ff;--bg:#0a0a0a;--card:#0d1117;--text:#e7fbff;--glow:rgba(0,240,255,.45);--link:#9be7b0}
        *{box-sizing:border-box} html,body{height:100%}
        body{margin:0;font:16px/1.6 system-ui,-apple-system,Segoe UI,Roboto,Ubuntu,Helvetica,Arial,sans-serif;background:var(--bg);color:var(--text);overflow-x:hidden}
        a{color:var(--link);text-decoration:none}
        header{padding:24px 16px 0}
        .wrap{max-width:1100px;margin:0 auto;padding:16px}
        .nav{display:flex;gap:16px;align-items:center;justify-content:flex-start}
        .brand{font-weight:800;letter-spacing:.4px}
        .pill{padding:8px 12px;border:1px solid transparent;border-radius:999px}
        .pill:hover{border-color:var(--glow)}
        .hero{margin:16px auto 12px;background:linear-gradient(180deg,rgba(0,240,255,.08),rgba(0,240,255,.02)),var(--card);
              border:1px solid var(--glow);border-radius:16px;padding:28px;box-shadow:0 0 24px rgba(0,240,255,.18)}
        .hero h1{margin:0 0 8px;font-weight:800;letter-spacing:1.2px;font-size:clamp(28px,4.5vw,40px);color:var(--neon);text-shadow:0 0 10px var(--neon)}
        .btn{display:inline-block;margin-top:10px;padding:10px 14px;border-radius:12px;background:var(--link);color:#06140a;font-weight:700}
        .card{background:linear-gradient(180deg,rgba(0,240,255,.08),rgba(0,240,255,.02)),var(--card);
              border:1px solid var(--glow);border-radius:14px;padding:22px;box-shadow:0 0 22px rgba(0,240,255,.15)}
        label{display:block;margin:.3rem 0 .25rem}
        input,textarea{width:100%;padding:.65rem;border-radius:10px;background:#0e0e11;color:var(--text);border:1px solid #272b33}
        .submit{margin-top:.6rem;background:#8be3a3;color:#0b0b0d;border:none;padding:.6rem 1rem;border-radius:10px;cursor:pointer}
        footer{opacity:.75;text-align:center;margin:28px 0 40px}
        canvas#bg{position:fixed;inset:0;z-index:-1}
        hr{border:0;border-top:1px solid #222;margin:12px 0}
      </style>'
    . '<canvas id="bg"></canvas>'
    . '<header><div class="wrap nav">'
      . '<div class="brand">WebApp</div>'
      . '<a class="pill" href="/">Hem</a>'
      . '<a class="pill" href="/contact">Kontakt</a>'
      . '<a class="pill" href="/messages">Meddelanden</a>'
      . '<a class="pill" href="/health">Health</a>'
    . '</div></header>'
    . '<main class="wrap">'.$content.'</main>'
    . '<footer>Mirsad Karangja - © WebApp</footer>'
    . '<script nonce="'.$nonce.'">
        (() => {
  const c=document.getElementById("bg"),x=c.getContext("2d");let W=0,H=0,raf=0;
  function R(){W=c.width=innerWidth;H=c.height=innerHeight;}
  addEventListener("resize",R); R();
  const N=398, S=0.44;
  let P=Array.from({length:N},()=>({x:Math.random()*W,y:Math.random()*H,vx:(Math.random()-.5)*S,vy:(Math.random()-.5)*S}));
  function D(){x.clearRect(0,0,W,H); const col=getComputedStyle(document.documentElement).getPropertyValue("--neon").trim()||"#8000ff";
    x.fillStyle=col; for(const n of P){n.x+=n.vx;n.y+=n.vy;if(n.x<0||n.x>W)n.vx*=-1;if(n.y<0||n.y>H)n.vy*=-1; x.beginPath(); x.arc(n.x,n.y,2,0,7); x.fill();}
    for(let i=0;i<P.length;i++)for(let j=i+1;j<P.length;j++){const a=P[i],b=P[j],d=Math.hypot(a.x-b.x,a.y-b.y);
      if(d<120){x.strokeStyle=`rgba(0,240,255,${1-d/120})`;x.beginPath();x.moveTo(a.x,a.y);x.lineTo(b.x,b.y);x.stroke();}}
    raf=requestAnimationFrame(D);} D();
})();
      </script>'
    . '</html>';
}

// Hero-sektion för startsidan
function hero(string $title, string $subtitle, string $ctaHref = "/contact", string $ctaText = "Skriv ett meddelande"): string {
    return '<section class="hero"><h1>'.e($title).'</h1><p>'.e($subtitle).'</p>'
         . '<a class="btn" href="'.e($ctaHref).'">'.e($ctaText).'</a></section>';
}

// Kontaktformulär (innehåll – läggs i .card i index.php)
function contact_form(?string $error = null): string {
    $err = $error ? '<p style="color:#ff8080;margin:0 0 .5rem 0">'.e($error).'</p>' : '';
    return $err
         . '<form method="post" action="/contact" style="display:grid;gap:.75rem;">'
         . '<div><label>Namn</label><input name="name" maxlength="120"></div>'
         . '<div><label>Meddelande</label><textarea name="message" rows="4" maxlength="1000"></textarea></div>'
         . csrf_field()
         . '<button class="submit">Skicka</button>'
         . '</form>';
}

function success_view(string $name, string $message): string {
    return '<h2>Tack!</h2>'
         . '<p><strong>Namn:</strong> '.e($name).'</p>'
         . '<p><strong>Meddelande:</strong><br><code>'.e($message).'</code></p>'
         . '<p><a href="/messages">Visa alla meddelanden</a></p>';
}

function messages_list(array $rows): string {
    if (!$rows) return '<p>Inga meddelanden sparade ännu.</p>';
    $tz = app_timezone();
    $out = '';
    foreach ($rows as $r) {
        $iso = (string)($r['ts'] ?? '');
        $ts  = $iso;
        try { $dt = (new DateTimeImmutable($iso))->setTimezone($tz); $ts = $dt->format('Y-m-d H:i:s'); } catch (Throwable $e) {}
        $name = e((string)($r['name'] ?? ''));
        $msg  = nl2br(e((string)($r['message'] ?? '')));
        $out .= '<div style="padding:.75rem 0;border-bottom:1px solid #222">'
              .   '<div style="opacity:.8;font-size:.9rem">'.$ts.'</div>'
              .   '<div><strong>'.$name.'</strong></div>'
              .   '<div style="white-space:pre-wrap">'.$msg.'</div>'
              . '</div>';
    }
    return $out;
}
