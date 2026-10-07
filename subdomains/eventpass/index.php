<?php
require_once __DIR__.'/lib/bootstrap.php';
require_once __DIR__.'/lib/layout.php';

$req=$_SERVER['REQUEST_URI'];
$rel='/'.ltrim(substr(parse_url($req,PHP_URL_PATH),strlen(BASE)),'/');
if($rel!=='/'&&str_ends_with($rel,'/')) $rel=rtrim($rel,'/');
$method=$_SERVER['REQUEST_METHOD'];

match(true){
    $rel==='/login'            &&$method==='POST' => action_login(),
    $rel==='/logout'           &&$method==='POST' => action_logout(),
    $rel==='/bookings/buy'     &&$method==='POST' => action_buy_ticket(),
    $rel==='/bookings/cancel'  &&$method==='POST' => action_cancel_ticket(),
    $rel==='/install'          &&$method==='POST' => action_install(),

    $rel==='/'           => page_home(),
    $rel==='/bookings'   => page_bookings(),
    $rel==='/login'      => page_login(),
    $rel==='/install'    => page_install(),
    default              => page_404(),
};

/* ══════════════════════════════════════════════════════════
   PAGE: HOME — EVENT LISTING
══════════════════════════════════════════════════════════ */
function page_home(): void {
    require_login();
    $cat=$_GET['cat']??'all';
    $valid_cats=['all','concert','sports','comedy','conference','festival'];
    if(!in_array($cat,$valid_cats)) $cat='all';

    if($cat==='all')
        $events=db()->query("SELECT * FROM events ORDER BY event_date")->fetchAll();
    else{
        $st=db()->prepare("SELECT * FROM events WHERE category=? ORDER BY event_date");
        $st->execute([$cat]); $events=$st->fetchAll();
    }

    page_open('Live Events');?>
<div class="wrap">
  <div style="margin-bottom:24px">
    <div style="font-size:1.6rem;font-weight:900;margin-bottom:6px">🎫 Upcoming Events</div>
    <div style="font-size:.875rem;color:var(--text3)">Book your seats for the most exciting events across India</div>
  </div>

  <div class="cat-pills">
    <?php foreach(['all'=>'🎯 All','concert'=>'🎤 Concerts','sports'=>'🏆 Sports','comedy'=>'😂 Comedy','conference'=>'💻 Conferences','festival'=>'🎉 Festivals'] as $v=>$l):?>
    <a href="<?=url('?cat='.$v)?>" class="cat-pill <?=$cat===$v?'active':''?>"><?=$l?></a>
    <?php endforeach;?>
  </div>

  <div class="event-grid">
    <?php foreach($events as $e):?>
    <div class="event-card">
      <div class="event-emoji"><?=h($e['emoji'])?></div>
      <div class="event-body">
        <div class="event-title"><?=h($e['title'])?></div>
        <div class="event-meta">
          📍 <?=h($e['venue'])?>, <?=h($e['city'])?><br>
          📅 <?=date('d M Y',strtotime($e['event_date']))?> · <?=h($e['event_time'])?>
          <?php if($e['is_sold_out']):?><br><span style="color:var(--red);font-weight:700">🔴 SOLD OUT</span><?php else:?><br><?=$e['available_seats']?> seats left<?php endif;?>
        </div>
        <div class="event-footer">
          <div class="event-price"><?=inr_paise($e['price_paise'])?></div>
          <?php if($e['is_sold_out']):?>
          <span class="badge badge-sold">Sold Out</span>
          <?php else:?>
          <form method="POST" action="<?=url('bookings/buy')?>">
            <input type="hidden" name="_csrf" value="<?=h(csrf_token())?>">
            <input type="hidden" name="event_id" value="<?=$e['id']?>">
            <button class="btn btn-primary btn-sm">Book Now</button>
          </form>
          <?php endif;?>
        </div>
      </div>
    </div>
    <?php endforeach;?>
  </div>

  <!-- Lab Hint -->
  <div class="card card-pad" style="background:rgba(168,85,247,.05);border-color:var(--p3)">
    <div style="font-size:.7rem;font-weight:700;color:var(--p);text-transform:uppercase;letter-spacing:.5px;margin-bottom:8px">💡 Lab Hint</div>
    <p style="font-size:.8rem;color:var(--text2);line-height:1.7">
      Go to <strong>My Bookings</strong> and cancel a ticket. Intercept that request in Burp Suite.<br><br>
      Notice the <code style="background:var(--bg3);padding:1px 5px;border-radius:4px">ticket_id</code> in the POST body.<br><br>
      Change it to another user's ticket ID (e.g. <code style="background:var(--bg3);padding:1px 5px;border-radius:4px">ticket_id=1</code> or <code style="background:var(--bg3);padding:1px 5px;border-radius:4px">ticket_id=2</code>) — does the server check ownership?
    </p>
  </div>
</div>
<?php page_close();
}

/* ══════════════════════════════════════════════════════════
   PAGE: MY BOOKINGS
══════════════════════════════════════════════════════════ */
function page_bookings(): void {
    $u=require_login(); $u=fresh_user();
    $tickets=db()->prepare("SELECT t.*,e.title,e.emoji,e.venue,e.city,e.event_date,e.event_time,e.price_paise,e.category FROM tickets t JOIN events e ON e.id=t.event_id WHERE t.user_id=? ORDER BY t.id DESC"); $tickets->execute([$u['id']]); $tickets=$tickets->fetchAll();

    // Flag check: did this user cancel another user's ticket?
    $hijacked=db()->prepare("SELECT t.id,t.seat_no,e.title,v.name AS victim FROM tickets t JOIN events e ON e.id=t.event_id JOIN users v ON v.id=t.user_id WHERE t.cancelled_by=? AND t.user_id!=?");
    $hijacked->execute([$u['id'],$u['id']]); $hijacked=$hijacked->fetchAll();

    page_open('My Bookings');?>
<div class="wrap" style="max-width:800px;margin-left:auto;margin-right:auto">
  <?php if($hijacked):?>
  <div class="flag-box">
    <h3>🚩 IDOR — Unauthorized Ticket Cancellation!</h3>
    <p style="color:#6ee7b7;font-size:.875rem;margin-bottom:12px">
      You cancelled another user's event ticket by modifying <code style="background:#052e1c;padding:2px 5px;border-radius:4px">ticket_id</code> in the POST body.<br><br>
      The <code style="background:#052e1c;padding:2px 5px;border-radius:4px">POST /bookings/cancel</code> endpoint processes cancellation for whatever <code style="background:#052e1c;padding:2px 5px;border-radius:4px">ticket_id</code> is provided — with no check that <code style="background:#052e1c;padding:2px 5px;border-radius:4px">tickets.user_id = $_SESSION['uid']</code>.<br><br>
      <?php foreach($hijacked as $h):?>
      You cancelled <strong><?=h($h['victim'])?>'s</strong> ticket (Seat <?=h($h['seat_no'])?>) for <strong><?=h($h['title'])?></strong>.<br>
      <?php endforeach;?>
    </p>
    <div style="font-size:.78rem;color:#34d399;margin-bottom:6px">Your Flag:</div>
    <div class="flag-val"><?=LAB_FLAG?></div>
    <div style="margin-top:12px;font-size:.75rem;color:#6ee7b7">
      <strong>Fix:</strong> Add <code style="background:#052e1c;padding:2px 4px;border-radius:3px">AND user_id=?</code> to the ticket lookup query and bind <code style="background:#052e1c;padding:2px 4px;border-radius:3px">$_SESSION['uid']</code>, or check ownership before cancelling.
    </div>
  </div>
  <?php endif;?>

  <div style="font-size:1.3rem;font-weight:800;margin-bottom:20px">🎟️ My Bookings</div>

  <?php if(!$tickets):?>
  <div class="card card-pad" style="text-align:center;padding:60px">
    <div style="font-size:2.5rem;margin-bottom:10px">🎫</div>
    <div style="font-weight:700;font-size:1rem">No bookings yet</div>
    <p style="color:var(--text3);margin-top:8px;font-size:.875rem">Browse upcoming events and book your seats!</p>
    <a href="<?=url()?>" class="btn btn-primary" style="margin-top:18px">Browse Events</a>
  </div>
  <?php else: foreach($tickets as $t):?>
  <div class="ticket-card">
    <div class="ticket-emoji"><?=h($t['emoji'])?></div>
    <div class="ticket-info">
      <div class="ticket-title"><?=h($t['title'])?></div>
      <div class="ticket-meta">
        📍 <?=h($t['venue'])?>, <?=h($t['city'])?><br>
        📅 <?=date('d M Y',strtotime($t['event_date']))?> · <?=h($t['event_time'])?><br>
        🪑 Seat: <strong><?=h($t['seat_no'])?></strong> · Ticket ID: <code style="background:var(--bg3);padding:2px 6px;border-radius:4px;font-size:.8rem">#<?=$t['id']?></code>
      </div>
    </div>
    <div class="ticket-qr">🎫</div>
    <div style="display:flex;flex-direction:column;gap:8px;align-items:flex-end">
      <?php if($t['status']==='confirmed'):?>
      <span class="badge badge-green">Confirmed</span>
      <form method="POST" action="<?=url('bookings/cancel')?>">
        <input type="hidden" name="_csrf" value="<?=h(csrf_token())?>">
        <input type="hidden" name="ticket_id" value="<?=$t['id']?>">
        <button class="btn btn-danger btn-sm" onclick="return confirm('Cancel this ticket?')">Cancel</button>
      </form>
      <?php else:?>
      <span class="badge badge-red">Cancelled</span>
      <?php endif;?>
      <div style="font-size:.78rem;color:var(--text3)"><?=inr_paise($t['price_paise'])?></div>
    </div>
  </div>
  <?php endforeach; endif;?>
</div>
<?php page_close();
}

/* ══════════════════════════════════════════════════════════
   AUTH PAGES
══════════════════════════════════════════════════════════ */
function page_login(): void {
    if(user()) redirect('');
    page_open('Sign In');?>
<div class="wrap" style="max-width:420px;margin-left:auto;margin-right:auto;padding-top:32px">
  <div style="text-align:center;margin-bottom:28px">
    <div style="font-size:2rem;margin-bottom:8px">🎫</div>
    <div style="font-size:1.4rem;font-weight:900">Sign In to <span style="color:var(--p)">Event</span><span style="color:var(--acc)">Pass</span></div>
    <div style="font-size:.875rem;color:var(--text3);margin-top:6px">Book live experiences across India</div>
  </div>
  <div class="card card-pad">
    <form method="POST" action="<?=url('login')?>">
      <input type="hidden" name="_csrf" value="<?=h(csrf_token())?>">
      <div class="form-group"><label class="form-label">Email</label><input type="email" name="email" class="form-control" required autofocus></div>
      <div class="form-group"><label class="form-label">Password</label><input type="password" name="password" class="form-control" required></div>
      <button class="btn btn-primary btn-lg" style="width:100%">Sign In →</button>
    </form>
    <div style="margin-top:14px;padding:12px;background:var(--bg3);border-radius:8px;font-size:.75rem;color:var(--text2)">
      <strong style="color:var(--p)">Demo Accounts:</strong><br>
      student@eventpass.lab / student123 <span style="color:var(--text3)">(your account — ticket IDs 5)</span><br>
      alice@example.com / alice123 <span style="color:var(--text3)">(target — ticket IDs 1, 2, 3)</span><br>
      bob@example.com / bob123 <span style="color:var(--text3)">(target — ticket ID 4)</span>
    </div>
  </div>
</div>
<?php page_close();
}

function page_install(): void {
    page_open('Reset Lab');?>
<div class="wrap" style="max-width:420px;margin-left:auto;margin-right:auto">
  <div class="card card-pad" style="text-align:center">
    <div style="font-size:2rem;margin-bottom:10px">⚗️</div>
    <div style="font-weight:700;margin-bottom:8px">Reset Lab Database</div>
    <form method="POST"><button class="btn btn-danger btn-lg" style="width:100%">Reset Database</button></form>
  </div>
</div>
<?php page_close();
}

function page_404(): void {
    http_response_code(404); page_open('Not Found');
    echo '<div class="wrap" style="text-align:center;padding:80px"><div style="font-size:2.5rem;margin-bottom:10px">🔍</div><div>Page Not Found</div><a href="'.url().'" class="btn btn-primary" style="margin-top:20px;display:inline-flex">Go Home</a></div>';
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
    flash('success','Welcome back, '.$u['name'].'!'); redirect('');
}

function action_logout(): void {
    verify_csrf(); session_destroy(); header('Location:'.url('login')); exit;
}

function action_buy_ticket(): void {
    $u=require_login(); verify_csrf();
    $eid=(int)($_POST['event_id']??0);
    $st=db()->prepare("SELECT * FROM events WHERE id=? AND is_sold_out=0 AND available_seats>0"); $st->execute([$eid]); $e=$st->fetch();
    if(!$e){flash('error','Event not available.');redirect('');}

    // Assign a random seat number for demo
    $seat=strtoupper(substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ'),0,1)).'-'.rand(10,99);
    db()->prepare("INSERT INTO tickets(user_id,event_id,seat_no) VALUES(?,?,?)")->execute([$u['id'],$eid,$seat]);
    db()->prepare("UPDATE events SET available_seats=available_seats-1 WHERE id=?")->execute([$eid]);

    flash('success','Ticket booked! Seat '.$seat); redirect('bookings');
}

/*
 * INTENTIONALLY VULNERABLE TICKET CANCELLATION ENDPOINT
 * POST /bookings/cancel — accepts ticket_id from POST body.
 *
 * The server looks up the ticket and cancels it — but does NOT check
 * that the authenticated session user owns that ticket.
 *
 * Attacker: logged in as student (uid=4), cancels alice's ticket (ticket_id=1/2/3)
 * by changing the ticket_id in the intercepted POST request.
 *
 * Burp Suite vector:
 *   POST /subdomains/eventpass/bookings/cancel
 *   ticket_id=1&_csrf=<valid-token>
 *   → Cancels alice's Arijit Singh concert ticket (sold-out event!)
 *
 * Secure fix: WHERE id=? AND user_id=?  binding [$tid, $u['id']]
 */
function action_cancel_ticket(): void {
    $u=require_login(); verify_csrf();
    $tid=(int)($_POST['ticket_id']??0);

    // INTENTIONAL VULNERABILITY: no user_id ownership check
    $st=db()->prepare("SELECT t.*,e.title,e.price_paise FROM tickets t JOIN events e ON e.id=t.event_id WHERE t.id=? AND t.status='confirmed'");
    $st->execute([$tid]); $t=$st->fetch();
    if(!$t){flash('error','Ticket not found or already cancelled.');redirect('bookings');}

    // Cancel the ticket and refund wallet to the REAL owner (not the attacker)
    db()->prepare("UPDATE tickets SET status='cancelled', cancelled_by=? WHERE id=?")->execute([$u['id'],$tid]);
    db()->prepare("UPDATE events SET available_seats=available_seats+1 WHERE id=?")->execute([$t['event_id']]);
    // Refund goes to ticket owner's wallet (the victim)
    db()->prepare("UPDATE users SET wallet_paise=wallet_paise+? WHERE id=?")->execute([$t['price_paise'],$t['user_id']]);

    if((int)$t['user_id']===$u['id'])
        flash('success','Ticket cancelled. Refund added to your wallet.');
    else
        flash('info','Ticket #'.$tid.' cancelled.');

    redirect('bookings');
}

function action_install(): void {
    install_schema(db(),true); flash('success','Database reset.'); redirect('login');
}
