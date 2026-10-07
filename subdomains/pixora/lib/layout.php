<?php
function page_open(string $title, string $extra=''): void {
    $u=user();
    $fl=$_SESSION['flash']??[]; unset($_SESSION['flash']);
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?=h($title)?> — Pixora</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
<style>
:root{
  --bg:#faf5ff;--bg2:#fff;--bg3:#f3e8ff;--card:#fff;--border:#e9d5ff;
  --p:#7c3aed;--p2:#6d28d9;--p3:#ddd6fe;
  --text:#0f0a1e;--text2:#3b0764;--text3:#a78bfa;
  --green:#059669;--red:#dc2626;--amber:#d97706;
  --nav:#0f0a1e;
  --radius:14px;--shadow:0 2px 16px rgba(124,58,237,.08);
}
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
body{font-family:'Inter',sans-serif;background:var(--bg);color:var(--text);min-height:100vh;font-size:15px}
a{color:var(--p);text-decoration:none}a:hover{color:var(--p2)}
/* NAV */
.nav{background:var(--nav);border-bottom:1px solid rgba(255,255,255,.06);padding:0 28px;position:sticky;top:0;z-index:100;box-shadow:0 2px 20px rgba(0,0,0,.3)}
.nav-inner{max-width:1100px;margin:0 auto;display:flex;align-items:center;height:58px;gap:20px}
.logo{font-size:1.35rem;font-weight:900;background:linear-gradient(135deg,#a78bfa,#f472b6);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;letter-spacing:-.5px}
.nav-links{display:flex;gap:4px;flex:1;margin-left:16px}
.nav-link{color:#94a3b8;font-size:.84rem;font-weight:500;padding:6px 12px;border-radius:8px;transition:.15s;display:flex;align-items:center;gap:5px}
.nav-link:hover{background:rgba(255,255,255,.06);color:#e2e8f0}
.nav-actions{display:flex;align-items:center;gap:8px;margin-left:auto}
/* BUTTONS */
.btn{display:inline-flex;align-items:center;justify-content:center;gap:6px;font-weight:600;cursor:pointer;border:none;border-radius:10px;padding:8px 18px;transition:.18s;font-size:.84rem;font-family:inherit;text-decoration:none!important}
.btn-primary{background:linear-gradient(135deg,var(--p),#9333ea);color:#fff}
.btn-primary:hover{opacity:.9;transform:translateY(-1px);box-shadow:0 4px 16px rgba(124,58,237,.35)}
.btn-secondary{background:rgba(255,255,255,.06);color:#e2e8f0;border:1px solid rgba(255,255,255,.12)}
.btn-secondary:hover{background:rgba(255,255,255,.12)}
.btn-outline{background:transparent;border:1.5px solid var(--p);color:var(--p)}
.btn-outline:hover{background:var(--bg3)}
.btn-light{background:#fff;color:var(--text);border:1px solid var(--border)}
.btn-light:hover{border-color:var(--p);color:var(--p)}
.btn-danger{background:#fee2e2;color:#991b1b;border:1px solid #fecaca}
.btn-sm{padding:5px 12px;font-size:.78rem;border-radius:7px}
.btn-lg{padding:11px 24px;font-size:.95rem}
/* FLASH */
.flash-wrap{max-width:1100px;margin:14px auto 0;padding:0 20px}
.flash{padding:11px 16px;border-radius:10px;font-size:.84rem;font-weight:500;margin-bottom:8px;display:flex;align-items:center;gap:8px}
.flash-success{background:#d1fae5;border:1px solid #6ee7b7;color:#065f46}
.flash-error{background:#fee2e2;border:1px solid #fca5a5;color:#991b1b}
.flash-info{background:#ede9fe;border:1px solid #c4b5fd;color:#4c1d95}
.flash-warning{background:#fef3c7;border:1px solid #fcd34d;color:#92400e}
/* LAYOUT */
.wrap{max-width:1100px;margin:0 auto;padding:28px 20px 60px}
.feed-layout{display:grid;grid-template-columns:1fr 340px;gap:28px;align-items:start}
/* CARD */
.card{background:var(--card);border:1px solid var(--border);border-radius:var(--radius);box-shadow:var(--shadow)}
.card-pad{padding:22px}
/* AVATAR */
.avatar{display:inline-flex;align-items:center;justify-content:center;border-radius:50%;font-size:2rem;flex-shrink:0;background:linear-gradient(135deg,var(--bg3),#fff);border:3px solid var(--border)}
.avatar-sm{width:40px;height:40px;font-size:1.1rem;border-width:2px}
.avatar-md{width:56px;height:56px;font-size:1.6rem}
.avatar-lg{width:90px;height:90px;font-size:2.8rem;border-width:4px}
.avatar-xl{width:120px;height:120px;font-size:3.6rem;border-width:4px}
/* POST CARD */
.post-card{background:#fff;border:1px solid var(--border);border-radius:var(--radius);box-shadow:var(--shadow);margin-bottom:16px;overflow:hidden}
.post-header{display:flex;align-items:center;gap:12px;padding:16px 18px 12px}
.post-meta{flex:1}
.post-username{font-weight:700;font-size:.9rem;color:var(--text)}
.post-handle{font-size:.75rem;color:var(--text3);margin-top:1px}
.post-image{background:linear-gradient(135deg,var(--bg3),#fce7f3);display:flex;align-items:center;justify-content:center;font-size:4rem;padding:32px;aspect-ratio:4/3}
.post-body{padding:14px 18px}
.post-text{font-size:.875rem;line-height:1.65;color:var(--text2)}
.post-actions{display:flex;align-items:center;gap:16px;padding:10px 18px 14px;border-top:1px solid #f9fafb}
.post-action{display:flex;align-items:center;gap:5px;font-size:.78rem;font-weight:600;color:var(--text3);cursor:pointer;transition:.15s;background:none;border:none;font-family:inherit}
.post-action:hover{color:var(--p)}
/* PROFILE HEADER */
.profile-header{background:#fff;border:1px solid var(--border);border-radius:var(--radius);box-shadow:var(--shadow);overflow:hidden}
.profile-cover{height:120px;background:linear-gradient(135deg,#7c3aed,#9333ea,#ec4899)}
.profile-info{padding:0 24px 20px}
.profile-avatar-wrap{margin-top:-48px;margin-bottom:12px}
.profile-stats{display:flex;gap:28px;margin-top:14px}
.stat-item{text-align:center}
.stat-num{font-size:1.1rem;font-weight:800}
.stat-lbl{font-size:.7rem;color:var(--text3);font-weight:500;text-transform:uppercase;letter-spacing:.4px}
/* AVATAR PICKER */
.avatar-grid{display:grid;grid-template-columns:repeat(8,1fr);gap:8px}
.avatar-opt{width:100%;aspect-ratio:1;display:flex;align-items:center;justify-content:center;font-size:1.5rem;border-radius:10px;border:2.5px solid transparent;cursor:pointer;background:var(--bg3);transition:.15s}
.avatar-opt:hover,.avatar-opt.selected{border-color:var(--p);background:#ede9fe}
/* FORMS */
.form-group{display:flex;flex-direction:column;gap:5px;margin-bottom:16px}
.form-label{font-size:.75rem;font-weight:700;color:var(--text);text-transform:uppercase;letter-spacing:.4px}
.form-control{background:#fafafa;border:1.5px solid var(--border);color:var(--text);border-radius:9px;padding:10px 14px;transition:.15s;outline:none;width:100%;font-family:inherit;font-size:.9rem}
.form-control:focus{border-color:var(--p);background:#fff;box-shadow:0 0 0 3px rgba(124,58,237,.1)}
/* FLAG */
.flag-box{background:linear-gradient(135deg,#064e3b,#065f46);border:2px solid #059669;border-radius:14px;padding:24px 28px;margin-bottom:20px}
.flag-box h3{color:#34d399;font-size:1rem;font-weight:700;margin-bottom:10px}
.flag-val{font-family:monospace;font-size:1.25rem;font-weight:800;color:#6ee7b7;background:#052e1c;border:1px solid #065f46;padding:8px 18px;border-radius:7px;display:inline-block;margin-top:6px;letter-spacing:2px}
/* EXPLORE */
.user-row{display:flex;align-items:center;gap:12px;padding:12px 16px;border-bottom:1px solid #f9fafb}
.user-row:last-child{border-bottom:none}
/* MISC */
.tag{display:inline-flex;align-items:center;padding:3px 9px;border-radius:5px;font-size:.7rem;font-weight:700}
.tag-purple{background:#ede9fe;color:#5b21b6}
.page-title{font-size:1.3rem;font-weight:800;letter-spacing:-.3px;margin-bottom:4px}
/* FOOTER */
.footer{background:var(--nav);padding:20px;text-align:center;color:#4b5563;font-size:.78rem}
@media(max-width:760px){.feed-layout{grid-template-columns:1fr}.avatar-grid{grid-template-columns:repeat(6,1fr)}}
</style>
<?=$extra?>
</head>
<body>
<nav class="nav">
  <div class="nav-inner">
    <a href="<?=url()?>" class="logo">✦ Pixora</a>
    <?php if($u):?>
    <div class="nav-links">
      <a href="<?=url()?>" class="nav-link">🏠 Home</a>
      <a href="<?=url('explore')?>" class="nav-link">🔍 Explore</a>
      <a href="<?=url('profile/'.$u['id'])?>" class="nav-link">👤 Profile</a>
    </div>
    <?php endif;?>
    <div class="nav-actions">
      <?php if($u):?>
        <div class="avatar avatar-sm"><?=h($u['avatar'])?></div>
        <a href="<?=url('settings')?>" class="btn btn-secondary btn-sm">⚙️ Settings</a>
        <form method="POST" action="<?=url('logout')?>" style="margin:0">
          <input type="hidden" name="_csrf" value="<?=h(csrf_token())?>">
          <button class="btn btn-secondary btn-sm">Sign Out</button>
        </form>
      <?php else:?>
        <a href="<?=url('login')?>" class="btn btn-secondary btn-sm">Sign In</a>
        <a href="<?=url('register')?>" class="btn btn-primary btn-sm">Join Pixora</a>
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
  <div style="background:linear-gradient(135deg,#a78bfa,#f472b6);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;font-size:1.1rem;font-weight:900;margin-bottom:6px">✦ Pixora</div>
  <div>Share Your World · Connect with Creators · Express Yourself</div>
  <div style="margin-top:8px;font-size:.68rem">🔒 Authorized Cybersecurity Training Lab · For Educational Use Only</div>
</footer>
</body></html>
<?php
}

function render_post(array $p): void {
    ?>
<div class="post-card">
  <div class="post-header">
    <a href="<?=url("profile/{$p['user_id']}")?>">
      <div class="avatar avatar-md"><?=h($p['avatar'])?></div>
    </a>
    <div class="post-meta">
      <div class="post-username"><?=h($p['name'])?></div>
      <div class="post-handle">@<?=h($p['username'])?> · <?=time_ago($p['created_at'])?></div>
    </div>
  </div>
  <div class="post-image"><?=h($p['image_emoji'])?></div>
  <div class="post-body"><div class="post-text"><?=h($p['content'])?></div></div>
  <div class="post-actions">
    <button class="post-action">❤️ <?=number_format($p['likes'])?></button>
    <button class="post-action">💬 Comment</button>
    <button class="post-action">🔁 Share</button>
  </div>
</div>
<?php
}
