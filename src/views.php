<?php

// ================= Layout (neon/dark + matrix-partiklar) =================
function layout(string $title, string $content): string {
    $nonce  = e(csp_nonce());
    $footer = 'Mirsad Karangja - © WebApp';

    $html = <<<'HTML'
<!doctype html>
<html lang="sv">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>{{TITLE}}</title>
  <style>
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
  </style>
</head>
<body>
  <canvas id="bg"></canvas>

  <header><div class="wrap nav">
    <div class="brand">WebApp</div>
    <a class="pill" href="/">Hem</a>
    <a class="pill" href="/contact">Kontakt</a>
    <a class="pill" href="/messages">Meddelanden</a>
    <a class="pill" href="/health">Health</a>
  </div></header>

  <main class="wrap">
    <div class="card">{{CONTENT}}</div>
  </main>

  <footer class="wrap">{{FOOTER}}</footer>

  <script nonce="{{NONCE}}">
    // Matrix-inspirerade partiklar (punkter + tunna länkar)
    const c=document.getElementById('bg'),ctx=c.getContext('2d');
    let W=0,H=0,P=[],N=120,LINK=120;

    function resize(){
      W=c.width=innerWidth; H=c.height=innerHeight;
      P=Array.from({length:N},()=>({x:Math.random()*W,y:Math.random()*H,vx:(Math.random()-.5)*.7,vy:(Math.random()-.5)*.7}));
    }
    function tick(){
      ctx.clearRect(0,0,W,H);
      ctx.fillStyle='rgba(110,231,183,0.9)';
      for(const p of P){
        p.x+=p.vx; p.y+=p.vy;
        if(p.x<0||p.x>W) p.vx*=-1;
        if(p.y<0||p.y>H) p.vy*=-1;
        ctx.beginPath(); ctx.arc(p.x,p.y,1.8,0,Math.PI*2); ctx.fill();
      }
      for(let i=0;i<P.length;i++){
        for(let j=i+1;j<P.length;j++){
          const a=P[i],b=P[j],d=Math.hypot(a.x-b.x,a.y-b.y);
          if(d<LINK){
            ctx.strokeStyle=`rgba(110,231,183,${1-d/LINK})`;
            ctx.beginPath(); ctx.moveTo(a.x,a.y); ctx.lineTo(b.x,b.y); ctx.stroke();
          }
        }
      }
      requestAnimationFrame(tick);
    }
    addEventListener('resize', resize); resize(); tick();
  </script>
</body>
</html>
HTML;

    return strtr($html, [
        '{{TITLE}}'   => e($title),
        '{{CONTENT}}' => $content,
        '{{FOOTER}}'  => e($footer),
        '{{NONCE}}'   => $nonce,
    ]);
}

// =================== Komponenter/sektioner =============================
function hero(string $title, string $subtitle, string $ctaHref = "/contact", string $ctaText = "Skriv ett meddelande"): string {
    return '<section class="hero"><h1>'.e($title).'</h1><p>'.e($subtitle).'</p>'
         . '<a class="btn" href="'.e($ctaHref).'">'.e($ctaText).'</a></section>';
}

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
