<?php
declare(strict_types=1);

/**
 * Baslayout – mörkt tema, neon, partikel-nät i bakgrunden.
 * Kräver helpers: e(), csp_nonce()
 */
function layout(string $title, string $content): string
{
    $nonce = e(csp_nonce());

    $html  = '<!doctype html><html lang="sv"><head>';
    $html .= '<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">';
    $html .= '<title>' . e($title) . '</title>';
    $html .= '<style>
:root{--neon:#75f7c8;--bg:#0a0f14;--panel:#0f1620;--ink:#e6f7ff;--glow:rgba(117,247,200,.35);--link:#8be3a3}
*{box-sizing:border-box}
html,body{height:100%}
body{margin:0;background:var(--bg);color:var(--ink);font:16px/1.55 system-ui,-apple-system,"Segoe UI",Roboto,Ubuntu,Helvetica,Arial,sans-serif;overflow-x:hidden}
a{color:var(--link);text-decoration:none}
.wrap{max-width:1100px;margin:0 auto;padding:16px}
header{padding:24px 16px 0}
.nav{display:flex;gap:18px;align-items:center}
.brand{font-weight:800;letter-spacing:.4px}
.pill{padding:8px 12px;border-radius:999px;border:1px solid transparent}
.pill:hover{border-color:var(--glow)}
.hero{margin:16px auto;background:
  linear-gradient(180deg,rgba(117,247,200,.08),rgba(117,247,200,.02)),var(--panel);
  border:1px solid var(--glow);border-radius:16px;padding:28px;
  box-shadow:0 0 24px rgba(117,247,200,.18)}
.hero h1{margin:0 0 8px;font-weight:900;letter-spacing:1.2px;
  font-size:clamp(26px,4.5vw,40px);color:var(--neon);text-shadow:0 0 10px var(--neon)}
.btn{display:inline-block;margin-top:10px;padding:10px 14px;border-radius:12px;
  background:var(--link);color:#07130c;font-weight:700}
.card{background:
  linear-gradient(180deg,rgba(117,247,200,.08),rgba(117,247,200,.02)),var(--panel);
  border:1px solid var(--glow);border-radius:14px;padding:22px;box-shadow:0 0 22px rgba(117,247,200,.15)}
label{display:block;margin:.35rem 0 .25rem}
input,textarea{width:100%;padding:.65rem;border-radius:10px;background:#0c1219;color:var(--ink);border:1px solid #222b33}
.submit{margin-top:.6rem;background:#8be3a3;color:#0b0b0d;border:none;padding:.6rem 1rem;border-radius:10px;cursor:pointer}
footer{opacity:.75;text-align:center;margin:28px 0 40px}
canvas#bg{position:fixed;inset:0;z-index:-1}
hr{border:0;border-top:1px solid #1f2630;margin:12px 0}
    </style>';
    $html .= '</head><body>';

    // Bakgrund
    $html .= '<canvas id="bg"></canvas>';

    // Header + nav
    $html .= '<header><div class="wrap nav">'
           .   '<div class="brand">WebApp</div>'
           .   '<a class="pill" href="/">Hem</a>'
           .   '<a class="pill" href="/contact">Kontakt</a>'
           .   '<a class="pill" href="/messages">Meddelanden</a>'
           .   '<a class="pill" href="/health">Health</a>'
           . '</div></header>';

    // Innehåll
    $html .= '<main class="wrap">' . $content . '</main>';

    // Footer – ändra texten här om du vill
    $html .= '<footer>© WebApp</footer>';

    // Partikel-nät (Matrix-inspirerat) – ren JS, inga PHP-taggar inuti
    $html .= '<script nonce="' . $nonce . '">
(function(){
  const c=document.getElementById("bg"),ctx=c.getContext("2d");
  let W=0,H=0,N=120,L=120, P=[];
  function resize(){
    W=c.width=innerWidth; H=c.height=innerHeight;
    P=[...Array(N)].map(()=>({x:Math.random()*W,y:Math.random()*H,vx:(Math.random()-.5)*0.7,vy:(Math.random()-.5)*0.7}));
  }
  function tick(){
    ctx.clearRect(0,0,W,H);
    // points
    ctx.fillStyle="rgba(117,247,200,0.95)";
    for(const p of P){
      p.x+=p.vx; p.y+=p.vy;
      if(p.x<0||p.x>W) p.vx*=-1;
      if(p.y<0||p.y>H) p.vy*=-1;
      ctx.beginPath(); ctx.arc(p.x,p.y,1.6,0,6.283); ctx.fill();
    }
    // links
    for(let i=0;i<P.length;i++){
      for(let j=i+1;j<P.length;j++){
        const a=P[i], b=P[j], d=Math.hypot(a.x-b.x,a.y-b.y);
        if(d<L){
          ctx.strokeStyle="rgba(117,247,200,"+(1-d/L)+")";
          ctx.beginPath(); ctx.moveTo(a.x,a.y); ctx.lineTo(b.x,b.y); ctx.stroke();
        }
      }
    }
    requestAnimationFrame(tick);
  }
  addEventListener("resize",resize);
  resize(); tick();
})();
    </script>';

    $html .= '</body></html>';
    return $html;
}

/** Hero-sektion för startsidan */
function hero(string $title, string $subtitle, string $ctaHref = "/contact", string $ctaText = "Skriv ett meddelande"): string
{
    return '<section class="hero"><h1>'.e($title).'</h1><p>'.e($subtitle).'</p>'
         . '<a class="btn" href="'.e($ctaHref).'">'.e($ctaText).'</a></section>';
}

/** Kontaktformulär */
function contact_form(?string $error = null): string
{
    $err = $error ? '<p style="color:#ff8080;margin:0 0 .5rem">'.e($error).'</p>' : '';
    return $err
         . '<div class="card"><form method="post" action="/contact" style="display:grid;gap:.75rem">'
         .   '<div><label>Namn</label><input name="name" maxlength="120" autocomplete="name"></div>'
         .   '<div><label>Meddelande</label><textarea name="message" rows="4" maxlength="1000"></textarea></div>'
         .   csrf_field()
         .   '<button class="submit">Skicka</button>'
         . '</form></div>';
}

/** Tackvy efter submit */
function success_view(string $name, string $message): string
{
    return '<div class="card"><h2>Tack!</h2>'
         . '<p><strong>Namn:</strong> '.e($name).'</p>'
         . '<p><strong>Meddelande:</strong><br><code style="white-space:pre-wrap">'.e($message).'</code></p>'
         . '<p><a href="/messages">Visa alla meddelanden</a></p></div>';
}

/** Lista sparade meddelanden */
function messages_list(array $rows): string
{
    if (!$rows) return '<div class="card"><p>Inga meddelanden sparade ännu.</p></div>';

    $tz = app_timezone();
    $out = '<div class="card">';
    foreach ($rows as $r) {
        $iso = (string)($r['ts'] ?? '');
        $ts  = $iso;
        try {
            $dt = (new DateTimeImmutable($iso))->setTimezone($tz);
            $ts = $dt->format('Y-m-d H:i:s');
        } catch (Throwable $e) {}

        $name = e((string)($r['name'] ?? ''));
        $msg  = nl2br(e((string)($r['message'] ?? '')));
        $out .= '<div style="padding:.75rem 0;border-bottom:1px solid #1f2630">'
              .   '<div style="opacity:.8;font-size:.9rem">'.$ts.'</div>'
              .   '<div><strong>'.$name.'</strong></div>'
              .   '<div style="white-space:pre-wrap">'.$msg.'</div>'
              . '</div>';
    }
    $out .= '</div>';
    return $out;
}
