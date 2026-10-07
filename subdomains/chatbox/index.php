<?php
require_once __DIR__.'/lib/bootstrap.php';
require_once __DIR__.'/lib/layout.php';

$req=$_SERVER['REQUEST_URI'];
$rel='/'.ltrim(substr(parse_url($req,PHP_URL_PATH),strlen(BASE)),'/');
if($rel!=='/'&&str_ends_with($rel,'/')) $rel=rtrim($rel,'/');
$method=$_SERVER['REQUEST_METHOD'];

match(true){
    $rel==='/login'        &&$method==='POST' => action_login(),
    $rel==='/logout'       &&$method==='POST' => action_logout(),
    $rel==='/messages/send'&&$method==='POST' => action_send_message(),
    $rel==='/install'      &&$method==='POST' => action_install(),

    $rel==='/'                                              => page_inbox(),
    (bool)preg_match('#^/inbox/thread/(\d+)$#',$rel,$m)    => page_thread((int)$m[1]),
    $rel==='/login'                                         => page_login(),
    $rel==='/install'                                       => page_install(),
    default                                                 => page_404(),
};

/* ══════════════════════════════════════════════════════════
   HELPER: render inbox sidebar
══════════════════════════════════════════════════════════ */
function render_sidebar(int $active_thread=0): void {
    $u=user(); if(!$u) return;

    // Get threads where THIS user is a participant
    $threads=db()->prepare("
        SELECT t.id, t.is_group, t.group_name,
               m.body AS last_msg, m.sent_at AS last_time,
               u2.name AS other_name, u2.avatar AS other_avatar, u2.status AS other_status
        FROM threads t
        JOIN thread_participants tp ON tp.thread_id=t.id AND tp.user_id=?
        JOIN thread_participants tp2 ON tp2.thread_id=t.id AND tp2.user_id!=?
        JOIN users u2 ON u2.id=tp2.user_id
        LEFT JOIN messages m ON m.thread_id=t.id AND m.id=(SELECT MAX(id) FROM messages WHERE thread_id=t.id)
        ORDER BY COALESCE(m.sent_at,t.created_at) DESC");
    $threads->execute([$u['id'],$u['id']]); $threads=$threads->fetchAll();
    ?>
<aside class="chat-sidebar">
  <div class="sidebar-header">
    💬 Messages
    <span style="font-size:.72rem;color:var(--text3);font-weight:400"><?=count($threads)?> chats</span>
  </div>
  <div class="search-box"><input class="search-input" placeholder="🔍 Search messages..." readonly></div>
  <div class="conv-list">
    <?php foreach($threads as $t):?>
    <a href="<?=url("inbox/thread/{$t['id']}")?>" class="conv-item <?=$t['id']===$active_thread?'active':''?>">
      <div class="conv-avatar">
        <div class="avatar-circle"><?=h($t['other_avatar'])?></div>
        <div class="status-dot status-<?=h($t['other_status'])?>"></div>
      </div>
      <div class="conv-info">
        <div class="conv-name"><?=h($t['other_name'])?></div>
        <div class="conv-preview"><?=$t['last_msg']?h(mb_substr($t['last_msg'],0,40)):'Start chatting...'?></div>
      </div>
      <div class="conv-time"><?=$t['last_time']?time_ago($t['last_time']):''?></div>
    </a>
    <?php endforeach;?>
  </div>

  <div style="padding:12px 14px;border-top:1px solid var(--border);font-size:.74rem;color:var(--text3)">
    💡 <strong>Lab Hint:</strong> Your thread is ID 3. Browse <code style="background:var(--bg3);padding:1px 4px;border-radius:3px">/inbox/thread/1</code> to test access control.
  </div>
</aside>
<?php
}

/* ══════════════════════════════════════════════════════════
   PAGE: INBOX (shows sidebar + empty state)
══════════════════════════════════════════════════════════ */
function page_inbox(): void {
    require_login();
    page_open('Inbox');?>
<div class="chat-shell">
  <?php render_sidebar();?>
  <div class="chat-main">
    <div class="chat-empty">
      <div style="font-size:3rem;margin-bottom:16px">💬</div>
      <div style="font-size:1.1rem;font-weight:700;margin-bottom:8px">Select a conversation</div>
      <div style="font-size:.875rem">Choose a chat from the sidebar to start messaging</div>
    </div>
  </div>
</div>
<?php page_close();
}

/* ══════════════════════════════════════════════════════════
   PAGE: THREAD VIEW (VULNERABLE)
══════════════════════════════════════════════════════════ */
function page_thread(int $tid): void {
    $u=require_login();

    // Check if user is actually a participant (used for flag/warning only — NOT for access control)
    $is_participant=(bool)db()->prepare("SELECT COUNT(*) FROM thread_participants WHERE thread_id=? AND user_id=?")->execute([$tid,$u['id']])&&
        (int)db()->prepare("SELECT COUNT(*) FROM thread_participants WHERE thread_id=? AND user_id=?")->execute([$tid,$u['id']])!==0;
    // Fix the above — do a proper check
    $part_check=db()->prepare("SELECT COUNT(*) FROM thread_participants WHERE thread_id=? AND user_id=?");
    $part_check->execute([$tid,$u['id']]);
    $is_participant=(int)$part_check->fetchColumn()>0;

    // Get thread info
    $thread=db()->prepare("SELECT * FROM threads WHERE id=?"); $thread->execute([$tid]); $thread=$thread->fetch();
    if(!$thread){flash('error','Conversation not found.');redirect('');}

    /*
     * INTENTIONAL VULNERABILITY: IDOR via URL path parameter
     * The query below fetches all messages in the thread by thread_id only.
     * It does NOT join thread_participants or check session uid is a participant.
     *
     * Secure fix: add a subquery or JOIN to verify participation:
     *   WHERE m.thread_id=?
     *   AND EXISTS (SELECT 1 FROM thread_participants WHERE thread_id=? AND user_id=?)
     * binding [$tid, $tid, $u['id']]
     */
    $messages=db()->prepare("
        SELECT m.*, u.name AS sender_name, u.avatar AS sender_avatar
        FROM messages m
        JOIN users u ON u.id=m.sender_id
        WHERE m.thread_id=?
        ORDER BY m.sent_at ASC");
    $messages->execute([$tid]); $messages=$messages->fetchAll();

    // Track unauthorized access for flag reveal
    if(!$is_participant){
        db()->prepare("UPDATE threads SET snooped_by=? WHERE id=? AND snooped_by IS NULL")->execute([$u['id'],$tid]);
    }

    // Get other participants (for header display)
    $others=db()->prepare("SELECT u.* FROM users u JOIN thread_participants tp ON tp.user_id=u.id WHERE tp.thread_id=? AND u.id!=?");
    $others->execute([$tid,$u['id']]); $others=$others->fetchAll();
    $other_name=$others?implode(', ',array_column($others,'name')):'Unknown';
    $other_avatar=$others?$others[0]['avatar']:'👤';
    $other_status=$others?$others[0]['status']:'offline';

    page_open(h($other_name));?>
<div class="chat-shell">
  <?php render_sidebar($tid);?>
  <div class="chat-main">
    <?php if(!$is_participant):?>
    <div class="flag-box">
      <h3>🚩 IDOR — Unauthorized Private Conversation Access!</h3>
      <p style="color:#6ee7b7;font-size:.8rem;margin-bottom:10px">
        You read a private message thread by navigating to <code style="background:#052e1c;padding:2px 5px;border-radius:4px">/inbox/thread/<?=$tid?></code>.<br><br>
        The thread view handler fetches all messages using only <code style="background:#052e1c;padding:2px 5px;border-radius:4px">WHERE thread_id=?</code> — it never checks if <code style="background:#052e1c;padding:2px 5px;border-radius:4px">$_SESSION['uid']</code> is in <code style="background:#052e1c;padding:2px 5px;border-radius:4px">thread_participants</code>.<br><br>
        You read confidential messages between <strong><?=h($other_name)?></strong> and their contact.
      </p>
      <div style="font-size:.75rem;color:#34d399;margin-bottom:5px">Your Flag:</div>
      <div class="flag-val"><?=LAB_FLAG?></div>
      <div style="margin-top:10px;font-size:.72rem;color:#6ee7b7">
        <strong>Fix:</strong> Add <code style="background:#052e1c;padding:2px 4px;border-radius:3px">AND EXISTS (SELECT 1 FROM thread_participants WHERE thread_id=? AND user_id=?)</code> to the message query.
      </div>
    </div>
    <?php endif;?>

    <div class="chat-header">
      <div class="avatar-circle" style="width:38px;height:38px;font-size:1.1rem"><?=h($other_avatar)?></div>
      <div>
        <div class="chat-header-name"><?=h($other_name)?></div>
        <div class="chat-header-status" style="color:<?=$other_status==='online'?'var(--green)':'var(--text3)'?>">
          <?=$other_status==='online'?'● Online':($other_status==='away'?'● Away':'● Offline')?>
        </div>
      </div>
      <?php if(!$is_participant):?>
      <span style="margin-left:auto;font-size:.72rem;background:rgba(239,68,68,.12);color:#f87171;border:1px solid rgba(239,68,68,.25);padding:4px 10px;border-radius:5px;font-weight:700">⚠️ NOT YOUR CONVERSATION</span>
      <?php endif;?>
    </div>

    <div class="chat-messages">
      <?php foreach($messages as $msg):
        $is_me=$msg['sender_id']==$u['id'];?>
      <div class="msg-row <?=$is_me?'me':''?>">
        <?php if(!$is_me):?><div class="msg-avatar"><?=h($msg['sender_avatar'])?></div><?php endif;?>
        <div class="msg-bubble <?=$is_me?'me':'them'?>">
          <?php if(!$is_me):?><div style="font-size:.7rem;font-weight:700;margin-bottom:4px;opacity:.8"><?=h($msg['sender_name'])?></div><?php endif;?>
          <?=h($msg['body'])?>
          <span class="msg-time"><?=time_ago($msg['sent_at'])?></span>
        </div>
      </div>
      <?php endforeach;?>
    </div>

    <div class="chat-input-bar">
      <form method="POST" action="<?=url('messages/send')?>" style="display:flex;width:100%;gap:10px;align-items:center">
        <input type="hidden" name="_csrf" value="<?=h(csrf_token())?>">
        <input type="hidden" name="thread_id" value="<?=$tid?>">
        <input class="msg-input" type="text" name="body" placeholder="<?=$is_participant?'Type a message...':'(Read-only — you are not a participant)'?>" <?=$is_participant?'':'disabled'?>>
        <button type="submit" class="btn-icon" <?=$is_participant?'':'disabled'?>>➤</button>
      </form>
    </div>
  </div>
</div>
<?php page_close();
}

/* ══════════════════════════════════════════════════════════
   AUTH PAGES
══════════════════════════════════════════════════════════ */
function page_login(): void {
    if(user()) redirect('');
    page_open('Sign In');?>
<div style="min-height:100vh;display:flex;align-items:center;justify-content:center;background:var(--bg);padding:20px">
  <div style="width:100%;max-width:380px">
    <div style="text-align:center;margin-bottom:24px">
      <div style="font-size:2.5rem;margin-bottom:8px">💬</div>
      <div style="font-size:1.3rem;font-weight:800;color:var(--p)">ChatBox</div>
      <div style="font-size:.875rem;color:var(--text3);margin-top:5px">Private messaging, simple and secure.</div>
    </div>
    <div style="background:#fff;border:1px solid var(--border);border-radius:var(--radius);padding:24px;box-shadow:var(--shadow)">
      <form method="POST" action="<?=url('login')?>">
        <input type="hidden" name="_csrf" value="<?=h(csrf_token())?>">
        <div class="form-group"><label class="form-label">Email</label><input type="email" name="email" class="form-control" required autofocus></div>
        <div class="form-group"><label class="form-label">Password</label><input type="password" name="password" class="form-control" required></div>
        <button class="btn btn-primary btn-lg" style="width:100%">Sign In</button>
      </form>
      <div style="margin-top:14px;padding:12px;background:var(--bg3);border-radius:8px;font-size:.75rem;color:var(--text2)">
        <strong style="color:var(--p)">Demo Accounts:</strong><br>
        student@chatbox.lab / student123 <span style="color:var(--text3)">(your account — thread 3)</span><br>
        alice@chatbox.app / alice123 <span style="color:var(--text3)">(alice — private threads 1 &amp; 2)</span><br>
        bob@chatbox.app / bob123 <span style="color:var(--text3)">(bob — thread 1)</span>
      </div>
    </div>
  </div>
</div>
<?php page_close();
}

function page_install(): void {
    page_open('Reset Lab');?>
<div style="min-height:100vh;display:flex;align-items:center;justify-content:center;padding:20px">
  <div style="background:#fff;border:1px solid var(--border);border-radius:var(--radius);padding:24px;max-width:380px;text-align:center">
    <div style="font-size:2rem;margin-bottom:10px">⚗️</div>
    <div style="font-weight:700;margin-bottom:8px">Reset Lab Database</div>
    <form method="POST"><button class="btn btn-danger btn-lg" style="width:100%">Reset Database</button></form>
  </div>
</div>
<?php page_close();
}

function page_404(): void {
    http_response_code(404); page_open('Not Found');
    echo '<div style="text-align:center;padding:80px;min-height:80vh;display:flex;align-items:center;justify-content:center;flex-direction:column"><div style="font-size:2.5rem;margin-bottom:10px">🔍</div><div>Page Not Found</div><a href="'.url().'" class="btn btn-primary" style="margin-top:20px">Go Home</a></div>';
    page_close();
}

/* ══════════════════════════════════════════════════════════
   ACTIONS
══════════════════════════════════════════════════════════ */
function action_login(): void {
    verify_csrf();
    $email=trim($_POST['email']??''); $pass=$_POST['password']??'';
    $st=db()->prepare("SELECT * FROM users WHERE email=?"); $st->execute([$email]); $u=$st->fetch();
    if(!$u||!password_verify($pass,$u['password_hash'])){flash('error','Invalid email or password.');redirect('login');}
    session_regenerate_id(true); $_SESSION['uid']=$u['id'];
    redirect('');
}

function action_logout(): void {
    verify_csrf(); session_destroy(); header('Location:'.url('login')); exit;
}

function action_send_message(): void {
    $u=require_login(); verify_csrf();
    $tid=(int)($_POST['thread_id']??0); $body=trim($_POST['body']??'');
    if(!$body||!$tid){redirect("inbox/thread/$tid");}
    // Only allow sending in own threads
    $part=db()->prepare("SELECT COUNT(*) FROM thread_participants WHERE thread_id=? AND user_id=?"); $part->execute([$tid,$u['id']]);
    if(!(int)$part->fetchColumn()){flash('error','Cannot send to this thread.');redirect("inbox/thread/$tid");}
    db()->prepare("INSERT INTO messages(thread_id,sender_id,body) VALUES(?,?,?)")->execute([$tid,$u['id'],$body]);
    redirect("inbox/thread/$tid");
}

function action_install(): void {
    install_schema(db(),true); redirect('login');
}
