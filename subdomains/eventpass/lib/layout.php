<?php
function page_open(string $title, string $extra=''): void {
    $u=user();
    $fl=$_SESSION['flash']??[]; unset($_SESSION['flash']);
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?=h($title)?> — EventPass</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
<style>
:root{
  --bg:#0d0117;--bg2:#160826;--bg3:#1e0f30;--card:#1a0d2e;--border:#3b1f5e;
  --p:#a855f7;--p2:#9333ea;--p3:#2d1060;
  --acc:#f97316;--acc2:#ea580c;
  --text:#f3f0ff;--text2:#c4b5fd;--text3:#7c5cbf;
  --green:#34d399;--red:#f87171;--amber:#fbbf24;
  --nav:#0d0117;
  --radius:14px;--shadow:0 4px 24px rgba(168,85,247,.12);
}
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
body{font-family:'Inter',sans-serif;background:var(--bg);color:var(--text);min-height:100vh;font-size:15px}
a{color:var(--p);text-decoration:none}a:hover{color:var(--p2)}
/* NAV */
.nav{background:rgba(13,1,23,.95);backdrop-filter:blur(12px);border-bottom:1px solid var(--border);padding:0 28px;position:sticky;top:0;z-index:100;box-shadow:0 2px 20px rgba(168,85,247,.15)}
.nav-inner{max-width:1200px;margin:0 auto;display:flex;align-items:center;height:60px;gap:20px}
.logo{font-size:1.3rem;font-weight:900;letter-spacing:-.4px;display:flex;align-items:center;gap:8px}
.logo .lp{color:var(--p)}.logo .la{color:var(--acc)}
.nav-links{display:flex;gap:4px;flex:1;margin-left:12px}
.nav-link{color:#94a3b8;font-size:.83rem;font-weight:500;padding:6px 12px;border-radius:8px;transition:.15s}
.nav-link:hover{background:rgba(168,85,247,.12);color:var(--text)}
.nav-actions{display:flex;align-items:center;gap:8px;margin-left:auto}
/* BUTTONS */
.btn{display:inline-flex;align-items:center;justify-content:center;gap:6px;font-weight:700;cursor:pointer;border:none;border-radius:10px;padding:9px 20px;transition:.18s;font-size:.85rem;font-family:inherit;text-decoration:none!important}
.btn-primary{background:linear-gradient(135deg,var(--p),var(--acc));color:#fff;box-shadow:0 4px 16px rgba(168,85,247,.3)}.btn-primary:hover{opacity:.9;transform:translateY(-1px)}
.btn-secondary{background:rgba(168,85,247,.1);color:var(--text2);border:1px solid var(--border)}.btn-secondary:hover{background:rgba(168,85,247,.2)}
.btn-outline{background:transparent;border:1.5px solid var(--p);color:var(--p)}.btn-outline:hover{background:var(--p3)}
.btn-danger{background:rgba(239,68,68,.15);color:var(--red);border:1px solid rgba(239,68,68,.3)}.btn-danger:hover{background:rgba(239,68,68,.25)}
.btn-sm{padding:6px 14px;font-size:.78rem;border-radius:8px}
.btn-lg{padding:12px 28px;font-size:.95rem}
/* FLASH */
.flash-wrap{max-width:1200px;margin:12px auto 0;padding:0 20px}
.flash{padding:11px 16px;border-radius:10px;font-size:.84rem;font-weight:500;margin-bottom:8px;display:flex;align-items:center;gap:8px}
.flash-success{background:rgba(52,211,153,.12);border:1px solid rgba(52,211,153,.3);color:var(--green)}
.flash-error{background:rgba(248,113,113,.1);border:1px solid rgba(248,113,113,.3);color:var(--red)}
.flash-info{background:rgba(168,85,247,.1);border:1px solid var(--border);color:var(--text2)}
/* LAYOUT */
.wrap{max-width:1200px;margin:0 auto;padding:28px 20px 60px}
/* EVENT CARD */
.event-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:20px;margin-bottom:28px}
.event-card{background:var(--card);border:1px solid var(--border);border-radius:var(--radius);overflow:hidden;box-shadow:var(--shadow);transition:.2s}
.event-card:hover{transform:translateY(-3px);box-shadow:0 8px 32px rgba(168,85,247,.2)}
.event-emoji{background:linear-gradient(135deg,var(--bg3),var(--p3));display:flex;align-items:center;justify-content:center;font-size:3.5rem;padding:28px;aspect-ratio:2/1}
.event-body{padding:16px}
.event-title{font-weight:800;font-size:.9rem;line-height:1.35;margin-bottom:6px}
.event-meta{font-size:.74rem;color:var(--text3);margin-bottom:10px;line-height:1.5}
.event-footer{display:flex;align-items:center;justify-content:space-between;padding-top:10px;border-top:1px solid var(--border)}
.event-price{font-size:1rem;font-weight:800;color:var(--acc)}
/* CATEGORY PILLS */
.cat-pills{display:flex;gap:8px;flex-wrap:wrap;margin-bottom:22px}
.cat-pill{padding:6px 14px;border-radius:20px;font-size:.78rem;font-weight:600;border:1.5px solid var(--border);color:var(--text3);cursor:pointer;transition:.15s;background:transparent}
.cat-pill:hover,.cat-pill.active{background:var(--p3);border-color:var(--p);color:var(--p)}
/* TICKET CARD */
.ticket-card{background:var(--card);border:1px solid var(--border);border-radius:var(--radius);padding:20px;margin-bottom:16px;box-shadow:var(--shadow);display:flex;gap:20px;align-items:center}
.ticket-emoji{font-size:2.8rem;width:70px;text-align:center;flex-shrink:0}
.ticket-info{flex:1}
.ticket-title{font-weight:800;font-size:.95rem;margin-bottom:4px}
.ticket-meta{font-size:.78rem;color:var(--text3);line-height:1.7}
.ticket-qr{background:var(--bg3);border:1px solid var(--border);border-radius:8px;padding:10px;font-size:1.8rem;text-align:center;width:60px;height:60px;display:flex;align-items:center;justify-content:center}
/* BADGE */
.badge{display:inline-flex;align-items:center;padding:3px 9px;border-radius:5px;font-size:.7rem;font-weight:700}
.badge-green{background:rgba(52,211,153,.15);color:var(--green);border:1px solid rgba(52,211,153,.25)}
.badge-red{background:rgba(248,113,113,.12);color:var(--red);border:1px solid rgba(248,113,113,.25)}
.badge-amber{background:rgba(251,191,36,.12);color:var(--amber);border:1px solid rgba(251,191,36,.25)}
.badge-sold{background:rgba(239,68,68,.15);color:#f87171;border:1px solid rgba(239,68,68,.3)}
/* CARD */
.card{background:var(--card);border:1px solid var(--border);border-radius:var(--radius);box-shadow:var(--shadow)}
.card-pad{padding:22px}
/* FORMS */
.form-group{display:flex;flex-direction:column;gap:5px;margin-bottom:16px}
.form-label{font-size:.73rem;font-weight:700;color:var(--text2);text-transform:uppercase;letter-spacing:.4px}
.form-control{background:var(--bg3);border:1.5px solid var(--border);color:var(--text);border-radius:9px;padding:10px 14px;transition:.15s;outline:none;width:100%;font-family:inherit;font-size:.9rem}
.form-control:focus{border-color:var(--p);box-shadow:0 0 0 3px rgba(168,85,247,.15)}
/* FLAG */
.flag-box{background:linear-gradient(135deg,#064e3b,#065f46);border:2px solid #059669;border-radius:14px;padding:24px 28px;margin-bottom:20px}
.flag-box h3{color:#34d399;font-size:1rem;font-weight:700;margin-bottom:10px}
.flag-val{font-family:monospace;font-size:1.2rem;font-weight:800;color:#6ee7b7;background:#052e1c;border:1px solid #065f46;padding:8px 18px;border-radius:7px;display:inline-block;margin-top:6px;letter-spacing:2px}
/* WALLET */
.wallet-bar{background:linear-gradient(135deg,var(--p3),var(--bg3));border:1px solid var(--border);border-radius:12px;padding:16px 20px;margin-bottom:22px;display:flex;align-items:center;justify-content:space-between}
/* FOOTER */
.footer{background:rgba(13,1,23,.98);border-top:1px solid var(--border);padding:20px;text-align:center;color:var(--text3);font-size:.78rem}
@media(max-width:900px){.event-grid{grid-template-columns:repeat(2,1fr)}}
@media(max-width:600px){.event-grid{grid-template-columns:1fr}.ticket-card{flex-direction:column}}
</style>
<?=$extra?>
</head>
<body>
<nav class="nav">
  <div class="nav-inner">
    <a href="<?=url()?>" class="logo">🎫 <span class="lp">Event</span><span class="la">Pass</span></a>
    <?php if($u=user()):?>
    <div class="nav-links">
      <a href="<?=url()?>" class="nav-link">🏠 Events</a>
      <a href="<?=url('bookings')?>" class="nav-link">🎟️ My Bookings</a>
    </div>
    <?php endif;?>
    <div class="nav-actions">
      <?php if($u=user()):?>
        <div style="background:var(--p3);border:1px solid var(--border);border-radius:8px;padding:5px 12px;font-size:.78rem;color:var(--text2)">
          💰 <?=inr_paise($u['wallet_paise'])?>
        </div>
        <form method="POST" action="<?=url('logout')?>" style="margin:0">
          <input type="hidden" name="_csrf" value="<?=h(csrf_token())?>">
          <button class="btn btn-secondary btn-sm">Sign Out</button>
        </form>
      <?php else:?>
        <a href="<?=url('login')?>" class="btn btn-primary btn-sm">Sign In</a>
      <?php endif;?>
    </div>
  </div>
</nav>
<?php if($fl):?>
<div class="flash-wrap"><?php foreach($fl as[$t,$m]):?><div class="flash flash-<?=h($t)?>"><?=h($m)?></div><?php endforeach;?></div>
<?php endif;?>
<?php
}

function page_close(): void {?>
<footer class="footer">
  <div style="font-size:1.1rem;font-weight:900;margin-bottom:6px">🎫 <span style="color:#a855f7">Event</span><span style="color:#f97316">Pass</span></div>
  <div>Your Seat. Your Story. · Book Live Experiences Across India</div>
  <div style="margin-top:8px;font-size:.68rem">🔒 Authorized Cybersecurity Training Lab · For Educational Use Only</div>
</footer>
</body></html>
<?php
}
