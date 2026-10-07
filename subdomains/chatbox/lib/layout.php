<?php
function page_open(string $title, string $extra=''): void {
    $u=user();
    $fl=$_SESSION['flash']??[]; unset($_SESSION['flash']);
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?=h($title)?> — ChatBox</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<style>
:root{
  --bg:#f0f2f5;--bg2:#fff;--bg3:#e9ebf0;--card:#fff;--border:#d1d5db;
  --p:#2563eb;--p2:#1d4ed8;--p3:#dbeafe;
  --me-bg:#2563eb;--me-text:#fff;
  --them-bg:#fff;--them-text:#111827;
  --text:#111827;--text2:#374151;--text3:#6b7280;
  --green:#10b981;--red:#ef4444;--amber:#f59e0b;
  --nav:#1f2937;
  --radius:12px;--shadow:0 1px 6px rgba(0,0,0,.08);
  --online:#10b981;
}
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
body{font-family:'Inter',sans-serif;background:var(--bg);color:var(--text);min-height:100vh;font-size:15px}
a{color:var(--p);text-decoration:none}
/* NAV */
.nav{background:var(--nav);padding:0 20px;position:sticky;top:0;z-index:100;box-shadow:0 2px 10px rgba(0,0,0,.2)}
.nav-inner{max-width:1400px;margin:0 auto;display:flex;align-items:center;height:54px;gap:16px}
.logo{font-size:1.2rem;font-weight:800;color:#fff;display:flex;align-items:center;gap:7px}
.logo span{color:#60a5fa}
.nav-actions{display:flex;align-items:center;gap:8px;margin-left:auto}
/* LAYOUT */
.chat-shell{display:flex;height:calc(100vh - 54px);max-width:1400px;margin:0 auto}
/* SIDEBAR */
.chat-sidebar{width:300px;background:#fff;border-right:1px solid var(--border);display:flex;flex-direction:column;flex-shrink:0}
.sidebar-header{padding:14px 16px;border-bottom:1px solid var(--border);font-weight:800;font-size:.95rem;display:flex;align-items:center;justify-content:space-between}
.search-box{padding:10px 14px;border-bottom:1px solid var(--border)}
.search-input{width:100%;background:var(--bg3);border:none;border-radius:20px;padding:8px 14px;font-size:.84rem;outline:none;color:var(--text)}
.conv-list{flex:1;overflow-y:auto}
.conv-item{display:flex;align-items:center;gap:12px;padding:12px 14px;cursor:pointer;border-bottom:1px solid #f9fafb;transition:.1s}
.conv-item:hover,.conv-item.active{background:var(--p3)}
.conv-avatar{position:relative;flex-shrink:0}
.avatar-circle{width:44px;height:44px;border-radius:50%;background:linear-gradient(135deg,var(--p3),#fff);border:2px solid var(--border);display:flex;align-items:center;justify-content:center;font-size:1.3rem}
.status-dot{position:absolute;bottom:1px;right:1px;width:11px;height:11px;border-radius:50%;border:2px solid #fff}
.status-online{background:var(--online)}
.status-offline{background:#9ca3af}
.status-away{background:var(--amber)}
.conv-info{flex:1;min-width:0}
.conv-name{font-weight:600;font-size:.875rem;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.conv-preview{font-size:.75rem;color:var(--text3);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;margin-top:2px}
.conv-time{font-size:.68rem;color:var(--text3);flex-shrink:0}
/* CHAT MAIN */
.chat-main{flex:1;display:flex;flex-direction:column;overflow:hidden}
.chat-header{background:#fff;border-bottom:1px solid var(--border);padding:12px 20px;display:flex;align-items:center;gap:12px}
.chat-header-name{font-weight:700;font-size:.95rem}
.chat-header-status{font-size:.75rem;color:var(--text3)}
.chat-messages{flex:1;overflow-y:auto;padding:20px;display:flex;flex-direction:column;gap:14px;background:var(--bg)}
.msg-row{display:flex;gap:10px;align-items:flex-end}
.msg-row.me{flex-direction:row-reverse}
.msg-avatar{width:34px;height:34px;border-radius:50%;background:var(--bg3);border:1.5px solid var(--border);display:flex;align-items:center;justify-content:center;font-size:1rem;flex-shrink:0}
.msg-bubble{max-width:68%;padding:10px 14px;border-radius:16px;font-size:.875rem;line-height:1.5;position:relative}
.msg-bubble.them{background:var(--them-bg);color:var(--them-text);border-bottom-left-radius:4px;box-shadow:var(--shadow)}
.msg-bubble.me{background:var(--me-bg);color:var(--me-text);border-bottom-right-radius:4px}
.msg-time{font-size:.65rem;opacity:.65;margin-top:4px;display:block}
.chat-input-bar{background:#fff;border-top:1px solid var(--border);padding:12px 20px;display:flex;align-items:center;gap:10px}
.msg-input{flex:1;background:var(--bg3);border:none;border-radius:22px;padding:10px 18px;font-size:.9rem;outline:none;font-family:inherit;color:var(--text)}
/* BUTTONS */
.btn{display:inline-flex;align-items:center;justify-content:center;gap:6px;font-weight:600;cursor:pointer;border:none;border-radius:9px;padding:8px 18px;transition:.18s;font-size:.84rem;font-family:inherit;text-decoration:none!important}
.btn-primary{background:var(--p);color:#fff}.btn-primary:hover{background:var(--p2)}
.btn-secondary{background:rgba(255,255,255,.1);color:#e2e8f0;border:1px solid rgba(255,255,255,.15)}.btn-secondary:hover{background:rgba(255,255,255,.18)}
.btn-icon{background:var(--p);color:#fff;width:40px;height:40px;border-radius:50%;display:flex;align-items:center;justify-content:center;cursor:pointer;border:none;flex-shrink:0;transition:.15s}.btn-icon:hover{background:var(--p2)}
.btn-sm{padding:5px 12px;font-size:.78rem;border-radius:7px}
.btn-lg{padding:11px 24px;font-size:.95rem}
/* FLASH */
.flash-wrap{padding:10px 20px;background:rgba(37,99,235,.06);border-bottom:1px solid var(--border)}
.flash{padding:10px 16px;border-radius:8px;font-size:.84rem;font-weight:500;margin-bottom:4px;display:flex;align-items:center;gap:8px}
.flash-success{background:#d1fae5;border:1px solid #6ee7b7;color:#065f46}
.flash-error{background:#fee2e2;border:1px solid #fca5a5;color:#991b1b}
/* FLAG */
.flag-box{background:linear-gradient(135deg,#064e3b,#065f46);border:2px solid #059669;border-radius:12px;padding:20px 24px;margin:12px}
.flag-box h3{color:#34d399;font-size:.95rem;font-weight:700;margin-bottom:8px}
.flag-val{font-family:monospace;font-size:1.1rem;font-weight:800;color:#6ee7b7;background:#052e1c;border:1px solid #065f46;padding:7px 16px;border-radius:6px;display:inline-block;margin-top:5px;letter-spacing:2px}
/* FORMS */
.form-group{display:flex;flex-direction:column;gap:4px;margin-bottom:14px}
.form-label{font-size:.72rem;font-weight:700;color:var(--text);text-transform:uppercase;letter-spacing:.4px}
.form-control{background:#fafafa;border:1.5px solid var(--border);color:var(--text);border-radius:8px;padding:9px 13px;outline:none;width:100%;font-family:inherit;font-size:.9rem;transition:.15s}
.form-control:focus{border-color:var(--p);box-shadow:0 0 0 3px rgba(37,99,235,.1)}
/* EMPTY STATE */
.chat-empty{flex:1;display:flex;flex-direction:column;align-items:center;justify-content:center;color:var(--text3);background:var(--bg)}
/* FOOTER */
.footer{background:var(--nav);padding:16px;text-align:center;color:#4b5563;font-size:.76rem}
@media(max-width:700px){.chat-sidebar{display:none}}
</style>
<?=$extra?>
</head>
<body>
<nav class="nav">
  <div class="nav-inner">
    <a href="<?=url()?>" class="logo">💬 <span>Chat</span>Box</a>
    <div class="nav-actions">
      <?php if($u=user()):?>
        <div style="font-size:.8rem;color:#94a3b8;display:flex;align-items:center;gap:6px">
          <div style="width:8px;height:8px;border-radius:50%;background:var(--online)"></div>
          <?=h($u['name'])?>
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
<?php if($fl): echo '<div class="flash-wrap">'; foreach($fl as[$t,$m]): echo '<div class="flash flash-'.h($t).'">'.h($m).'</div>'; endforeach; echo '</div>'; endif;?>
<?php
}

function page_close(): void {?>
</body></html>
<?php
}
