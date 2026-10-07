<?php
require_once __DIR__.'/lib/bootstrap.php';
require_once __DIR__.'/lib/layout.php';

$req=$_SERVER['REQUEST_URI'];
$rel='/'.ltrim(substr(parse_url($req,PHP_URL_PATH),strlen(BASE)),'/');
if($rel!=='/'&&str_ends_with($rel,'/')) $rel=rtrim($rel,'/');
$method=$_SERVER['REQUEST_METHOD'];

match(true){
    /* POST actions first */
    $rel==='/login'           &&$method==='POST' => action_login(),
    $rel==='/register'        &&$method==='POST' => action_register(),
    $rel==='/logout'          &&$method==='POST' => action_logout(),
    $rel==='/account/upgrade' &&$method==='POST' => action_upgrade(),
    $rel==='/order/place'     &&$method==='POST' => action_order_place(),
    $rel==='/profile/update'  &&$method==='POST' => action_profile_update(),
    $rel==='/install'         &&$method==='POST' => action_install(),

    /* GET pages */
    $rel==='/'                                              => page_home(),
    $rel==='/products'                                      => page_home(),
    (bool)preg_match('#^/category/([a-z0-9_-]+)$#',$rel,$m)=> page_category($m[1]),
    (bool)preg_match('#^/gig/(\d+)$#',$rel,$m)             => page_gig((int)$m[1]),
    (bool)preg_match('#^/seller/(\d+)$#',$rel,$m)          => page_seller((int)$m[1]),
    (bool)preg_match('#^/order/(\d+)$#',$rel,$m)           => page_order((int)$m[1]),
    $rel==='/dashboard'                                     => page_dashboard(),
    $rel==='/analytics'                                     => page_analytics(),
    $rel==='/upgrade'                                       => page_upgrade(),
    $rel==='/upgrade/checkout'                              => page_upgrade_checkout(),
    $rel==='/orders'                                        => page_orders(),
    $rel==='/profile'                                       => page_profile(),
    $rel==='/login'                                         => page_login(),
    $rel==='/register'                                      => page_register(),
    $rel==='/admin'                                         => page_admin(),
    $rel==='/install'                                       => page_install(),
    default                                                 => page_404(),
};

/* ══════════════════════════════════════════════════════════
   HELPER: fetch gigs with seller info
══════════════════════════════════════════════════════════ */
function fetch_gigs(string $where='1=1', array $params=[], int $limit=50): array {
    $st=db()->prepare("
        SELECT g.*, c.slug AS cat_slug, c.name AS cat_name, c.icon AS cat_icon,
               u.name AS seller_name, u.avatar_color, u.plan AS seller_plan
        FROM gigs g
        JOIN categories c ON c.id=g.category_id
        JOIN users u ON u.id=g.seller_id
        WHERE g.active=1 AND ($where)
        ORDER BY g.featured DESC, g.rating DESC, g.review_count DESC
        LIMIT $limit");
    $st->execute($params); return $st->fetchAll();
}

/* ══════════════════════════════════════════════════════════
   PAGE: HOME
══════════════════════════════════════════════════════════ */
function page_home(): void {
    $cats=db()->query("SELECT * FROM categories ORDER BY sort_order")->fetchAll();
    $featured=fetch_gigs('g.featured=1',[], 8);
    $all=fetch_gigs('1=1',[],12);
    page_open('Find the Perfect Freelancer');
    ?>
<div class="hero">
  <div class="hero-title">Find the Perfect Freelancer<br>for Any Project</div>
  <p class="hero-sub">Connect with expert freelancers in Design, Tech, Writing & more. Get work done fast.</p>
  <div class="hero-search">
    <input type="text" placeholder="Search for any service..." id="hs">
    <button onclick="location.href='<?=url('products')?>?q='+document.getElementById('hs').value">Search</button>
  </div>
</div>
<div class="cat-strip">
  <?php foreach($cats as $c):?>
  <a href="<?=url("category/{$c['slug']}")?>" class="cat-pill"><?=$c['icon']?> <?=h($c['name'])?></a>
  <?php endforeach;?>
</div>
<div class="main">
  <?php if($featured):?>
  <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px">
    <div><div class="page-title">⭐ Featured Services</div><div class="section-sub">Hand-picked top sellers</div></div>
  </div>
  <div class="gig-grid" style="margin-bottom:40px">
    <?php foreach($featured as $g) gig_card($g);?>
  </div>
  <?php endif;?>
  <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px">
    <div><div class="page-title">🔥 Popular Services</div><div class="section-sub">Trending this week</div></div>
  </div>
  <div class="gig-grid"><?php foreach($all as $g) gig_card($g);?></div>
</div>
<?php page_close();
}

/* ══════════════════════════════════════════════════════════
   PAGE: CATEGORY
══════════════════════════════════════════════════════════ */
function page_category(string $slug): void {
    $st=db()->prepare("SELECT * FROM categories WHERE slug=?"); $st->execute([$slug]); $cat=$st->fetch();
    if(!$cat){page_404();return;}
    $gigs=fetch_gigs('g.category_id=?',[$cat['id']]);
    page_open($cat['name']);
    echo '<div class="main">';
    echo '<div style="margin-bottom:24px"><div class="page-title">'.$cat['icon'].' '.h($cat['name']).'</div><div class="section-sub">'.count($gigs).' services available</div></div>';
    echo '<div class="gig-grid">'; foreach($gigs as $g) gig_card($g); echo '</div></div>';
    page_close();
}

/* ══════════════════════════════════════════════════════════
   PAGE: GIG DETAIL
══════════════════════════════════════════════════════════ */
function page_gig(int $id): void {
    $gigs=fetch_gigs('g.id=?',[$id],1); $g=$gigs[0]??null;
    if(!$g){page_404();return;}
    page_open(h($g['title']));
    ?>
<div class="main" style="display:grid;grid-template-columns:1fr 320px;gap:28px;align-items:start">
  <div>
    <div style="background:<?=($bg=['design'=>'#fdf2f8','tech'=>'#f0fdfa','writing'=>'#fffbeb','marketing'=>'#eff6ff','video'=>'#faf5ff','audio'=>'#f0fdf4','business'=>'#f9fafb'])[$g['cat_slug']]??'#f9fafb';?>;border-radius:14px;aspect-ratio:16/9;display:flex;align-items:center;justify-content:center;font-size:5rem;margin-bottom:20px">
      <?=(['design'=>'🎨','tech'=>'💻','writing'=>'✍️','marketing'=>'📈','video'=>'🎬','audio'=>'🎵','business'=>'💼'])[$g['cat_slug']]??'📋'?>
    </div>
    <div style="font-size:.75rem;color:var(--t);font-weight:700;text-transform:uppercase;letter-spacing:.6px;margin-bottom:6px"><?=h($g['cat_name'])?></div>
    <h1 style="font-size:1.5rem;font-weight:800;line-height:1.3;margin-bottom:14px"><?=h($g['title'])?></h1>
    <div style="display:flex;align-items:center;gap:12px;margin-bottom:20px">
      <a href="<?=url("seller/{$g['seller_id']}")?>" style="display:flex;align-items:center;gap:10px;text-decoration:none;color:var(--text)">
        <div style="width:36px;height:36px;border-radius:50%;background:<?=h($g['avatar_color'])?>;display:flex;align-items:center;justify-content:center;font-size:.9rem;font-weight:800;color:#fff"><?=strtoupper(substr($g['seller_name'],0,1))?></div>
        <div><span style="font-weight:600"><?=h($g['seller_name'])?></span><?php if($g['seller_plan']==='pro'):?> <span class="tag tag-pro" style="font-size:.6rem">PRO</span><?php endif;?></div>
      </a>
      <div style="display:flex;align-items:center;gap:5px"><span class="stars"><?=stars((float)$g['rating'])?></span><span style="font-weight:700"><?=$g['rating']?></span><span style="color:var(--text3)">(<?=$g['review_count']?> reviews)</span></div>
    </div>
    <div class="card card-pad"><p style="line-height:1.7;color:var(--text2)"><?=nl2br(h($g['description']))?></p></div>
    <div style="display:flex;gap:16px;margin-top:16px">
      <div class="card card-pad" style="flex:1;text-align:center"><div style="font-size:1.3rem;font-weight:800;color:var(--t)"><?=$g['orders_completed']?></div><div style="font-size:.75rem;color:var(--text3);font-weight:600;margin-top:2px">Orders Completed</div></div>
      <div class="card card-pad" style="flex:1;text-align:center"><div style="font-size:1.3rem;font-weight:800;color:var(--t)"><?=$g['delivery_days']?>d</div><div style="font-size:.75rem;color:var(--text3);font-weight:600;margin-top:2px">Delivery Time</div></div>
      <div class="card card-pad" style="flex:1;text-align:center"><div style="font-size:1.3rem;font-weight:800;color:var(--t)"><?=$g['rating']?>★</div><div style="font-size:.75rem;color:var(--text3);font-weight:600;margin-top:2px">Avg Rating</div></div>
    </div>
  </div>
  <!-- Order Panel -->
  <div class="card card-pad" style="position:sticky;top:80px">
    <div style="font-size:.75rem;color:var(--text3);font-weight:600;margin-bottom:4px">Starting at</div>
    <div style="font-size:2rem;font-weight:900;margin-bottom:4px"><?=inr((int)$g['price'])?></div>
    <div style="font-size:.8rem;color:var(--text3);margin-bottom:16px">🚚 Delivered in <?=$g['delivery_days']?> day<?=$g['delivery_days']>1?'s':''?></div>
    <form method="POST" action="<?=url('order/place')?>">
      <input type="hidden" name="_csrf" value="<?=h(csrf_token())?>">
      <input type="hidden" name="gig_id" value="<?=$g['id']?>">
      <div class="form-group">
        <label class="form-label">Project Requirements</label>
        <textarea name="requirements" class="form-control" rows="4" placeholder="Describe what you need..."></textarea>
      </div>
      <button class="btn btn-primary btn-lg" style="width:100%">Order Now — <?=inr((int)$g['price'])?></button>
    </form>
    <div style="margin-top:12px;text-align:center;font-size:.75rem;color:var(--text3)">🔒 Secure payment · Money-back guarantee</div>
  </div>
</div>
<?php page_close();
}

/* ══════════════════════════════════════════════════════════
   PAGE: SELLER PROFILE
══════════════════════════════════════════════════════════ */
function page_seller(int $id): void {
    $st=db()->prepare("SELECT id,name,bio,skills,avatar_color,plan,rating,review_count,total_earnings,created_at FROM users WHERE id=? AND role IN('seller','both')");
    $st->execute([$id]); $seller=$st->fetch();
    if(!$seller){page_404();return;}
    $gigs=fetch_gigs('g.seller_id=?',[$id]);
    page_open(h($seller['name']));
    ?>
<div class="main">
  <div class="card card-pad" style="display:flex;gap:20px;align-items:center;margin-bottom:24px">
    <div style="width:72px;height:72px;border-radius:50%;background:<?=h($seller['avatar_color'])?>;display:flex;align-items:center;justify-content:center;font-size:1.8rem;font-weight:800;color:#fff;flex-shrink:0"><?=strtoupper(substr($seller['name'],0,1))?></div>
    <div style="flex:1">
      <div style="display:flex;align-items:center;gap:10px">
        <div style="font-size:1.3rem;font-weight:800"><?=h($seller['name'])?></div>
        <?php if($seller['plan']==='pro'):?><span class="tag tag-pro">⭐ Pro Seller</span><?php endif;?>
      </div>
      <div style="color:var(--text3);font-size:.875rem;margin-top:4px"><?=h($seller['bio'])?:''?></div>
      <div style="display:flex;gap:16px;margin-top:10px">
        <span style="font-size:.8rem;color:var(--text3)">⭐ <?=$seller['rating']?> (<?=$seller['review_count']?> reviews)</span>
        <span style="font-size:.8rem;color:var(--text3)">📦 <?=count($gigs)?> gigs</span>
        <span style="font-size:.8rem;color:var(--text3)">📅 Member since <?=date('M Y',strtotime($seller['created_at']))?></span>
      </div>
      <?php if($seller['skills']):?>
      <div style="margin-top:10px;display:flex;flex-wrap:wrap;gap:6px">
        <?php foreach(explode(',',$seller['skills']) as $sk):?>
        <span style="background:#f0fdfa;color:var(--t);border:1px solid #99f6e4;border-radius:20px;padding:3px 10px;font-size:.75rem;font-weight:600"><?=h(trim($sk))?></span>
        <?php endforeach;?>
      </div>
      <?php endif;?>
    </div>
  </div>
  <div class="page-title" style="margin-bottom:16px">Gigs by <?=h(explode(' ',$seller['name'])[0])?></div>
  <div class="gig-grid"><?php foreach($gigs as $g) gig_card($g);?></div>
</div>
<?php page_close();
}

/* ══════════════════════════════════════════════════════════
   PAGE: ORDER DETAIL
══════════════════════════════════════════════════════════ */
function page_order(int $id): void {
    $u=require_login();
    $st=db()->prepare("SELECT o.*,u.name AS seller_name,b.name AS buyer_name FROM orders o JOIN users u ON u.id=o.seller_id JOIN users b ON b.id=o.buyer_id WHERE o.id=? AND (o.buyer_id=? OR o.seller_id=?)");
    $st->execute([$id,$u['id'],$u['id']]); $order=$st->fetch();
    if(!$order){page_404();return;}
    $status_colors=['pending'=>'tag-warning','in_progress'=>'tag-info','delivered'=>'tag-info','completed'=>'tag-success','cancelled'=>'tag-danger'];
    page_open("Order #$id");
    echo '<div class="main" style="max-width:700px;margin-left:auto;margin-right:auto">';
    echo '<div class="card card-pad">';
    echo '<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px"><div class="page-title">Order #'.h($id).'</div><span class="tag '.($status_colors[$order['status']]??'tag-info').'">'.ucfirst(str_replace('_',' ',$order['status'])).'</span></div>';
    foreach([['Gig',$order['gig_title']],['Buyer',$order['buyer_name']],['Seller',$order['seller_name']],['Price',inr((int)$order['price'])],['Placed',date('d M Y, g:i A',strtotime($order['created_at']))]] as[$l,$v])
        echo "<div style='display:flex;justify-content:space-between;padding:10px 0;border-bottom:1px solid #f3f4f6'><span style='font-size:.875rem;color:var(--text3)'>$l</span><span style='font-weight:600;font-size:.875rem'>".h($v)."</span></div>";
    if($order['requirements']) echo '<div style="margin-top:16px"><div style="font-size:.75rem;font-weight:700;color:var(--text3);text-transform:uppercase;margin-bottom:6px">Requirements</div><p style="font-size:.875rem;color:var(--text2)">'.h($order['requirements']).'</p></div>';
    echo '</div></div>';
    page_close();
}

/* ══════════════════════════════════════════════════════════
   PAGE: DASHBOARD
══════════════════════════════════════════════════════════ */
function page_dashboard(): void {
    $u=require_login(); $u=fresh_user();
    $gig_count=(int)db()->prepare("SELECT COUNT(*) FROM gigs WHERE seller_id=? AND active=1")->execute([$u['id']])?db()->prepare("SELECT COUNT(*) FROM gigs WHERE seller_id=? AND active=1")->execute([$u['id']])||0:0;
    $st=db()->prepare("SELECT COUNT(*) FROM gigs WHERE seller_id=? AND active=1"); $st->execute([$u['id']]); $gig_count=(int)$st->fetchColumn();
    $ost=db()->prepare("SELECT COUNT(*) FROM orders WHERE seller_id=? AND status='completed'"); $ost->execute([$u['id']]); $completed=(int)$ost->fetchColumn();
    $bst=db()->prepare("SELECT COUNT(*) FROM orders WHERE buyer_id=?"); $bst->execute([$u['id']]); $bought=(int)$bst->fetchColumn();

    page_open('Dashboard');
    ?>
<div class="main">
  <?php if($u['plan']==='free'):?>
  <div class="upgrade-banner">
    <div><h3>⭐ Upgrade to Pro</h3><p>Get featured placement, detailed analytics, unlimited gigs and a Pro badge on your profile.</p></div>
    <a href="<?=url('upgrade')?>" class="btn btn-amber">Upgrade Now →</a>
  </div>
  <?php endif;?>
  <div class="page-title" style="margin-bottom:20px">👋 Welcome, <?=h(explode(' ',$u['name'])[0])?>!</div>
  <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:24px">
    <?php foreach([['📋',$gig_count,'Active Gigs'],['✅',$completed,'Completed Orders'],['🛒',$bought,'Purchases'],['⭐',$u['plan']==='pro'?'Pro':'Free','Your Plan']] as[$i,$v,$l]):?>
    <div class="card card-pad" style="text-align:center"><div style="font-size:1.8rem"><?=$i?></div><div style="font-size:1.5rem;font-weight:800;margin:4px 0"><?=$v?></div><div style="font-size:.75rem;color:var(--text3);font-weight:600"><?=$l?></div></div>
    <?php endforeach;?>
  </div>
  <div style="display:grid;grid-template-columns:2fr 1fr;gap:20px">
    <div class="card card-pad">
      <div style="font-weight:700;margin-bottom:14px">Quick Actions</div>
      <?php foreach([[url('orders'),'📦 My Orders'],
                      [url('analytics'),'📈 Analytics'.($u['plan']!=='pro'?' (Pro only)':'')],
                      [url('profile'),'👤 Edit Profile'],
                      [url(),'🔍 Browse Gigs']] as[$href,$lbl]):?>
      <a href="<?=$href?>" class="btn btn-secondary" style="width:100%;margin-bottom:8px;justify-content:flex-start"><?=$lbl?></a>
      <?php endforeach;?>
    </div>
    <div class="card card-pad" style="background:#fffbeb;border-color:#fde68a">
      <div style="font-size:.75rem;font-weight:700;color:#92400e;text-transform:uppercase;margin-bottom:8px">💡 Lab Hint</div>
      <p style="font-size:.8rem;color:#78350f;line-height:1.6">
        The Pro upgrade checks a <code style="background:#fff8;padding:2px 5px;border-radius:3px">payment_token</code> for format only — not validity.<br><br>
        Try: <code style="background:#fff8;padding:2px 5px;border-radius:3px;display:block;margin-top:4px">POST /account/upgrade<br>plan=pro&payment_token=FAKE1234</code>
      </p>
    </div>
  </div>
</div>
<?php page_close();
}

/* ══════════════════════════════════════════════════════════
   PAGE: ANALYTICS (PRO ONLY — FLAG HERE)
══════════════════════════════════════════════════════════ */
function page_analytics(): void {
    $u=require_login(); $u=fresh_user();

    // Gate: Pro only
    if($u['plan']!=='pro'){
        flash('info','Analytics is a Pro feature. Upgrade your plan to unlock it.');
        redirect('upgrade');
    }

    $ost=db()->prepare("SELECT COUNT(*),COALESCE(SUM(price),0) FROM orders WHERE seller_id=? AND status='completed'");
    $ost->execute([$u['id']]); [$orders,$earnings]=$ost->fetch(PDO::FETCH_NUM);
    $pst=db()->prepare("SELECT COUNT(*) FROM orders WHERE seller_id=? AND status='pending'");
    $pst->execute([$u['id']]); $pending=(int)$pst->fetchColumn();
    // Simulated analytics metrics
    $impressions = 2840 + ($u['id']*317);
    $clicks      = 342 + ($u['id']*41);
    $conversion  = $clicks>0 ? round($orders/$clicks*100,1) : 0;

    page_open('Analytics — Pro');
    ?>
<div class="main">
  <div class="flag-box">
    <h3>🚩 Pro Analytics Unlocked!</h3>
    <p style="color:#6ee7b7;font-size:.875rem;margin-bottom:10px">
      You accessed the <strong>Pro-only Analytics dashboard</strong> by bypassing the payment validation.<br>
      The <code style="background:#052e1c;padding:2px 6px;border-radius:4px">/account/upgrade</code> endpoint validates
      <code style="background:#052e1c;padding:2px 6px;border-radius:4px">payment_token</code> only via regex — never against a payments table.
      Any token matching <code style="background:#052e1c;padding:2px 6px;border-radius:4px">^[A-Z0-9]{8,}$</code> is accepted.
    </p>
    <div>Your Flag:</div>
    <div class="flag-val"><?=LAB_FLAG?></div>
  </div>

  <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px">
    <div><div class="page-title">📈 Seller Analytics</div><div class="section-sub">Your performance overview</div></div>
    <span class="tag tag-pro">⭐ Pro Feature</span>
  </div>

  <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:28px">
    <div class="stat-card"><div class="stat-num"><?=number_format($impressions)?></div><div class="stat-label">Impressions</div></div>
    <div class="stat-card"><div class="stat-num"><?=number_format($clicks)?></div><div class="stat-label">Clicks</div></div>
    <div class="stat-card"><div class="stat-num"><?=$conversion?>%</div><div class="stat-label">Conversion Rate</div></div>
    <div class="stat-card"><div class="stat-num"><?=inr((int)$earnings)?></div><div class="stat-label">Total Earnings</div></div>
  </div>

  <div style="display:grid;grid-template-columns:2fr 1fr;gap:20px">
    <div class="card card-pad">
      <div style="font-weight:700;margin-bottom:16px">Monthly Earnings (Simulated)</div>
      <?php foreach(['Apr'=>8200,'May'=>12400,'Jun'=>9800,'Jul'=>15600,'Aug'=>18900,'Sep'=>14999] as $mo=>$amt):?>
      <div style="display:flex;align-items:center;gap:12px;margin-bottom:10px">
        <div style="width:40px;font-size:.78rem;color:var(--text3);font-weight:600"><?=$mo?></div>
        <div style="flex:1;background:#f0fdfa;border-radius:6px;overflow:hidden;height:22px">
          <div style="background:var(--t);height:100%;border-radius:6px;width:<?=min(100,round($amt/200))?>%"></div>
        </div>
        <div style="width:70px;font-weight:700;font-size:.82rem;text-align:right"><?=inr($amt)?></div>
      </div>
      <?php endforeach;?>
    </div>
    <div class="card card-pad">
      <div style="font-weight:700;margin-bottom:14px">Account Summary</div>
      <?php foreach([['Plan','⭐ Pro'],['Completed Orders',$orders],['Pending Orders',$pending],['Profile Views',number_format($clicks)],['Response Rate','98%']] as[$l,$v]):?>
      <div style="display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid #f3f4f6;font-size:.875rem"><span style="color:var(--text3)"><?=$l?></span><span style="font-weight:600"><?=$v?></span></div>
      <?php endforeach;?>
    </div>
  </div>
</div>
<?php page_close();
}

/* ══════════════════════════════════════════════════════════
   PAGE: UPGRADE PLAN
══════════════════════════════════════════════════════════ */
function page_upgrade(): void {
    $u=require_login(); $u=fresh_user();
    if($u['plan']==='pro'){flash('info','You already have a Pro plan!');redirect('dashboard');}
    page_open('Upgrade to Pro');
    ?>
<div class="main" style="max-width:900px;margin-left:auto;margin-right:auto">
  <div style="text-align:center;margin-bottom:36px">
    <div style="font-size:2rem;margin-bottom:8px">⭐</div>
    <div class="page-title" style="font-size:1.6rem">Upgrade to GigBoard Pro</div>
    <p style="color:var(--text3);margin-top:8px">Unlock the full power of the platform</p>
  </div>
  <div style="display:grid;grid-template-columns:1fr 1fr;gap:24px;margin-bottom:32px">
    <div class="card card-pad" style="border:2px solid var(--border)">
      <div style="font-size:1.1rem;font-weight:700;margin-bottom:4px">Free</div>
      <div style="font-size:2.5rem;font-weight:900;margin:10px 0">₹0<span style="font-size:1rem;font-weight:400;color:var(--text3)">/mo</span></div>
      <?php foreach(['Up to 3 gigs','Standard listing','Basic profile','Email support','No analytics'] as $f):?>
      <div style="display:flex;align-items:center;gap:8px;padding:7px 0;font-size:.875rem;color:var(--text3);border-bottom:1px solid #f3f4f6"><span>—</span><?=$f?></div>
      <?php endforeach;?>
      <div style="margin-top:16px;text-align:center;font-weight:600;color:var(--text3)">Current Plan</div>
    </div>
    <div class="card card-pad" style="border:2px solid var(--t);position:relative">
      <div style="position:absolute;top:-12px;left:50%;transform:translateX(-50%);background:var(--t);color:#fff;font-size:.72rem;font-weight:700;padding:4px 14px;border-radius:20px">MOST POPULAR</div>
      <div style="font-size:1.1rem;font-weight:700;margin-bottom:4px;color:var(--t)">Pro ⭐</div>
      <div style="font-size:2.5rem;font-weight:900;margin:10px 0">₹999<span style="font-size:1rem;font-weight:400;color:var(--text3)">/mo</span></div>
      <?php foreach(['Unlimited gigs','Featured listing placement','Pro badge on profile','Full analytics dashboard','Priority support','Earnings insights'] as $f):?>
      <div style="display:flex;align-items:center;gap:8px;padding:7px 0;font-size:.875rem;border-bottom:1px solid #f3f4f6"><span style="color:var(--green);font-weight:700">✓</span><?=$f?></div>
      <?php endforeach;?>
      <a href="<?=url('upgrade/checkout')?>" class="btn btn-primary btn-lg" style="width:100%;margin-top:16px">Get Pro Now →</a>
    </div>
  </div>
</div>
<?php page_close();
}

/* ══════════════════════════════════════════════════════════
   PAGE: UPGRADE CHECKOUT (payment form)
══════════════════════════════════════════════════════════ */
function page_upgrade_checkout(): void {
    $u=require_login(); $u=fresh_user();
    if($u['plan']==='pro'){flash('info','Already on Pro.');redirect('dashboard');}
    page_open('Complete Upgrade');
    ?>
<div class="main" style="max-width:520px;margin-left:auto;margin-right:auto">
  <div class="page-title" style="margin-bottom:20px">Complete Pro Upgrade</div>
  <div class="card card-pad" style="margin-bottom:16px">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px">
      <div style="font-weight:700">GigBoard Pro — Monthly</div>
      <div style="font-size:1.2rem;font-weight:800">₹999</div>
    </div>
    <ul style="font-size:.82rem;color:var(--text3);padding-left:16px;line-height:2">
      <li>Unlimited gig listings</li><li>Featured placement</li><li>Full analytics</li><li>Pro badge</li>
    </ul>
  </div>
  <!-- POST /account/upgrade — payment_token validated by regex only (intentional bug) -->
  <div class="card card-pad">
    <div style="font-weight:700;margin-bottom:14px">💳 Payment Details</div>
    <form method="POST" action="<?=url('account/upgrade')?>">
      <input type="hidden" name="_csrf" value="<?=h(csrf_token())?>">
      <input type="hidden" name="plan" value="pro">
      <div class="form-group">
        <label class="form-label">Card Number</label>
        <input type="text" name="card_number" class="form-control" placeholder="4111 1111 1111 1111" maxlength="19">
      </div>
      <div class="grid-2">
        <div class="form-group"><label class="form-label">Expiry</label><input type="text" name="card_expiry" class="form-control" placeholder="MM/YY" maxlength="5"></div>
        <div class="form-group"><label class="form-label">CVV</label><input type="text" name="card_cvv" class="form-control" placeholder="123" maxlength="4"></div>
      </div>
      <!-- payment_token is generated client-side in real flow; here it is set by the payment processor -->
      <!-- Server only validates regex, not actual payment — intentional bug -->
      <input type="hidden" name="payment_token" id="pt" value="">
      <script>
        // Simulates what a payment processor JS SDK would inject
        document.getElementById('pt').value = 'PAY' + Math.random().toString(36).substr(2,8).toUpperCase();
      </script>
      <button class="btn btn-primary btn-lg" style="width:100%">Pay ₹999 &amp; Upgrade</button>
    </form>
    <div style="margin-top:12px;text-align:center;font-size:.75rem;color:var(--text3)">🔒 Powered by SecurePay Gateway</div>
  </div>
  <div class="card card-pad" style="margin-top:14px;background:#fff7ed;border-color:#fed7aa">
    <div style="font-size:.78rem;font-weight:700;color:#92400e;margin-bottom:6px">💡 Lab Hint</div>
    <p style="font-size:.8rem;color:#78350f;line-height:1.6">
      Notice the hidden <code style="background:#fff8;padding:1px 5px;border-radius:3px">payment_token</code> field is set by JS.<br>
      Intercept with Burp Suite and replace it with any uppercase string of 8+ chars:<br>
      <code style="background:#fff8;padding:2px 6px;border-radius:3px;margin-top:4px;display:inline-block">plan=pro&payment_token=FAKE1234</code>
    </p>
  </div>
</div>
<?php page_close();
}

/* ══════════════════════════════════════════════════════════
   PAGE: ORDERS
══════════════════════════════════════════════════════════ */
function page_orders(): void {
    $u=require_login();
    $st=db()->prepare("SELECT o.*,g.title AS gig_title,seller.name AS seller_name,buyer.name AS buyer_name FROM orders o JOIN gigs g ON g.id=o.gig_id JOIN users seller ON seller.id=o.seller_id JOIN users buyer ON buyer.id=o.buyer_id WHERE o.buyer_id=? OR o.seller_id=? ORDER BY o.id DESC");
    $st->execute([$u['id'],$u['id']]); $orders=$st->fetchAll();
    $sc=['pending'=>'tag-warning','in_progress'=>'tag-info','delivered'=>'tag-info','completed'=>'tag-success','cancelled'=>'tag-danger'];
    page_open('My Orders');
    echo '<div class="main"><div class="page-title" style="margin-bottom:20px">📦 My Orders</div>';
    echo '<div class="card"><table class="table"><thead><tr><th>#</th><th>Gig</th><th>Buyer</th><th>Seller</th><th>Price</th><th>Status</th><th></th></tr></thead><tbody>';
    foreach($orders as $o) echo "<tr><td>#".h($o['id'])."</td><td style='font-weight:600;max-width:220px'>".h($o['gig_title'])."</td><td>".h($o['buyer_name'])."</td><td>".h($o['seller_name'])."</td><td style='font-weight:700'>".inr((int)$o['price'])."</td><td><span class='tag ".($sc[$o['status']]??"tag-info")."'>".ucfirst(str_replace('_',' ',$o['status']))."</span></td><td><a href='".url("order/{$o['id']}")."' class='btn btn-secondary btn-sm'>View</a></td></tr>";
    echo '</tbody></table></div></div>';
    page_close();
}

/* ══════════════════════════════════════════════════════════
   PAGE: PROFILE
══════════════════════════════════════════════════════════ */
function page_profile(): void {
    $u=require_login(); $u=fresh_user();
    page_open('Edit Profile');
    ?>
<div class="main" style="max-width:560px;margin-left:auto;margin-right:auto">
  <div class="page-title" style="margin-bottom:20px">Edit Profile</div>
  <div class="card card-pad">
    <form method="POST" action="<?=url('profile/update')?>">
      <input type="hidden" name="_csrf" value="<?=h(csrf_token())?>">
      <div class="form-group"><label class="form-label">Full Name</label><input type="text" name="name" class="form-control" value="<?=h($u['name'])?>" required></div>
      <div class="form-group"><label class="form-label">Email</label><input type="email" name="email" class="form-control" value="<?=h($u['email'])?>" required></div>
      <div class="form-group"><label class="form-label">Bio</label><textarea name="bio" class="form-control" rows="3" placeholder="Describe yourself..."><?=h($u['bio'])?></textarea></div>
      <div class="form-group"><label class="form-label">Skills (comma-separated)</label><input type="text" name="skills" class="form-control" value="<?=h($u['skills'])?>" placeholder="PHP, React, Figma"></div>
      <div class="form-group"><label class="form-label">New Password</label><input type="password" name="password" class="form-control" placeholder="Leave blank to keep" minlength="6"></div>
      <button class="btn btn-primary" style="width:100%">Save Changes</button>
    </form>
  </div>
</div>
<?php page_close();
}

/* ══════════════════════════════════════════════════════════
   PAGES: AUTH
══════════════════════════════════════════════════════════ */
function page_login(): void {
    if(user()) redirect('');
    page_open('Sign In');
    ?>
<div class="main" style="max-width:420px;margin-left:auto;margin-right:auto;padding-top:40px">
  <div style="text-align:center;margin-bottom:24px"><div style="font-size:2.5rem;margin-bottom:8px">🎯</div><div class="page-title">Welcome Back</div><div style="color:var(--text3);margin-top:4px">Sign in to your GigBoard account</div></div>
  <div class="card card-pad">
    <form method="POST" action="<?=url('login')?>">
      <input type="hidden" name="_csrf" value="<?=h(csrf_token())?>">
      <div class="form-group"><label class="form-label">Email</label><input type="email" name="email" class="form-control" required autofocus></div>
      <div class="form-group"><label class="form-label">Password</label><input type="password" name="password" class="form-control" required></div>
      <button class="btn btn-primary btn-lg" style="width:100%">Sign In</button>
    </form>
    <div style="text-align:center;margin-top:14px;font-size:.85rem;color:var(--text3)">New? <a href="<?=url('register')?>">Create account</a></div>
    <div style="margin-top:14px;padding:12px;background:#f0fdfa;border-radius:8px;font-size:.75rem;color:var(--text3)">
      <strong style="color:var(--t)">Demo accounts:</strong><br>
      student@gigboard.lab / student123 (free plan — to exploit)<br>
      arjun@example.com / arjun123 (pro seller)<br>
      admin@gigboard.app / admin123
    </div>
  </div>
</div>
<?php page_close();
}

function page_register(): void {
    if(user()) redirect('');
    page_open('Join GigBoard');
    ?>
<div class="main" style="max-width:440px;margin-left:auto;margin-right:auto;padding-top:40px">
  <div style="text-align:center;margin-bottom:24px"><div class="page-title">Join GigBoard</div><div style="color:var(--text3);margin-top:4px">Find work or hire talent — it's free</div></div>
  <div class="card card-pad">
    <form method="POST" action="<?=url('register')?>">
      <input type="hidden" name="_csrf" value="<?=h(csrf_token())?>">
      <div class="form-group"><label class="form-label">Full Name</label><input type="text" name="name" class="form-control" required></div>
      <div class="form-group"><label class="form-label">Email</label><input type="email" name="email" class="form-control" required></div>
      <div class="form-group"><label class="form-label">I want to</label>
        <select name="role" class="form-control"><option value="buyer">Hire freelancers</option><option value="seller">Offer services</option><option value="both">Both</option></select>
      </div>
      <div class="form-group"><label class="form-label">Password</label><input type="password" name="password" class="form-control" required minlength="6"></div>
      <button class="btn btn-primary btn-lg" style="width:100%">Create Free Account</button>
    </form>
    <div style="text-align:center;margin-top:14px;font-size:.85rem;color:var(--text3)">Already have an account? <a href="<?=url('login')?>">Sign In</a></div>
  </div>
</div>
<?php page_close();
}

/* ══════════════════════════════════════════════════════════
   PAGE: ADMIN
══════════════════════════════════════════════════════════ */
function page_admin(): void {
    $u=require_login();
    if(!$u['is_admin']){flash('error','Access denied.');redirect('');}
    $users=db()->query("SELECT id,name,email,plan,role,total_earnings,created_at FROM users ORDER BY id")->fetchAll();
    $gigs=db()->query("SELECT g.id,g.title,g.price,g.rating,g.orders_completed,u.name AS seller FROM gigs g JOIN users u ON u.id=g.seller_id ORDER BY g.id")->fetchAll();
    page_open('Admin');
    echo '<div class="main"><div class="page-title" style="margin-bottom:20px">⚙️ Admin Panel</div>';
    echo '<div style="display:grid;grid-template-columns:repeat(3,1fr);gap:14px;margin-bottom:24px">';
    foreach([[count($users),'Users'],[count($gigs),'Gigs'],[(int)db()->query("SELECT COUNT(*) FROM orders")->fetchColumn(),'Orders']] as[$v,$l])
        echo "<div class='card card-pad' style='text-align:center'><div style='font-size:1.6rem;font-weight:800'>{$v}</div><div style='font-size:.78rem;color:var(--text3);font-weight:600;margin-top:4px'>{$l}</div></div>";
    echo '</div><div class="card" style="margin-bottom:20px"><div style="padding:14px 18px;font-weight:700;border-bottom:1px solid var(--border)">Users</div>';
    echo '<table class="table"><thead><tr><th>ID</th><th>Name</th><th>Email</th><th>Role</th><th>Plan</th><th>Earnings</th></tr></thead><tbody>';
    foreach($users as $u2) echo "<tr><td>#".$u2['id']."</td><td style='font-weight:600'>".h($u2['name'])."</td><td>".h($u2['email'])."</td><td>".h($u2['role'])."</td><td><span class='tag ".($u2['plan']==='pro'?'tag-pro':'')."'>".h($u2['plan'])."</span></td><td>".inr((int)$u2['total_earnings'])."</td></tr>";
    echo '</tbody></table></div>';
    echo '<div class="card"><div style="padding:14px 18px;font-weight:700;border-bottom:1px solid var(--border)">Gigs</div>';
    echo '<table class="table"><thead><tr><th>ID</th><th>Title</th><th>Seller</th><th>Price</th><th>Rating</th><th>Orders</th></tr></thead><tbody>';
    foreach($gigs as $g) echo "<tr><td>#".$g['id']."</td><td>".h($g['title'])."</td><td>".h($g['seller'])."</td><td style='font-weight:700'>".inr((int)$g['price'])."</td><td>⭐ ".$g['rating']."</td><td>".$g['orders_completed']."</td></tr>";
    echo '</tbody></table></div></div>';
    page_close();
}

/* ══════════════════════════════════════════════════════════
   PAGE: INSTALL
══════════════════════════════════════════════════════════ */
function page_install(): void {
    page_open('Reset Lab');
    ?>
<div class="main" style="max-width:440px;margin-left:auto;margin-right:auto">
  <div class="card card-pad" style="text-align:center">
    <div style="font-size:2.5rem;margin-bottom:12px">⚗️</div>
    <div class="page-title">Reset Lab Database</div>
    <p style="color:var(--text3);margin:12px 0 20px;font-size:.875rem">Drops and recreates all tables. All user and order data will be lost.</p>
    <form method="POST">
      <button class="btn btn-danger btn-lg" style="width:100%;background:#fee2e2;color:#991b1b;border:1px solid #fca5a5">Reset Database</button>
    </form>
    <a href="<?=url('login')?>" class="btn btn-secondary" style="width:100%;margin-top:10px">Cancel</a>
  </div>
</div>
<?php page_close();
}

function page_404(): void {
    http_response_code(404); page_open('404');
    echo '<div class="main" style="text-align:center;padding:80px"><div style="font-size:3rem;margin-bottom:12px">🔍</div><div class="page-title">Page Not Found</div><a href="'.url().'" class="btn btn-primary" style="margin-top:20px;display:inline-flex">Go Home</a></div>';
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
    flash('success','Welcome back, '.$u['name'].'!'); redirect('dashboard');
}

function action_register(): void {
    verify_csrf();
    $name=trim($_POST['name']??''); $email=trim($_POST['email']??'');
    $pass=$_POST['password']??''; $role=in_array($_POST['role']??'',['buyer','seller','both'])?$_POST['role']:'buyer';
    if(!$name||!$email||!$pass||!filter_var($email,FILTER_VALIDATE_EMAIL)){flash('error','Fill all fields correctly.');redirect('register');}
    try {
        $st=db()->prepare("INSERT INTO users(name,email,password_hash,role) VALUES(?,?,?,?)");
        $st->execute([$name,$email,password_hash($pass,PASSWORD_DEFAULT),$role]);
        session_regenerate_id(true); $_SESSION['uid']=(int)db()->lastInsertId();
        flash('success','Account created! Welcome to GigBoard.'); redirect('dashboard');
    } catch(PDOException){flash('error','Email already registered.');redirect('register');}
}

function action_logout(): void {
    verify_csrf(); session_destroy(); header('Location:'.url('login')); exit;
}

/*
 * INTENTIONALLY VULNERABLE UPGRADE ENDPOINT
 * POST /account/upgrade  params: plan, payment_token
 *
 * Bug: payment_token is validated only for format (regex ^[A-Z0-9]{8,}$)
 * It is NEVER checked against a payments table or external payment processor.
 * Any token matching the regex (e.g. FAKE1234) grants Pro status.
 */
function action_upgrade(): void {
    $u=require_login();
    verify_csrf();
    $plan=trim($_POST['plan']??'');
    $token=trim($_POST['payment_token']??'');

    if(!in_array($plan,['pro'])){flash('error','Invalid plan selected.');redirect('upgrade');}

    // --- INTENTIONAL BUG: only regex check, no DB/payment validation ---
    if(empty($token)||!preg_match('/^[A-Z0-9]{8,}$/',$token)){
        flash('error','Payment failed. Invalid payment token format.');
        redirect('upgrade/checkout');
    }

    // Token "looks valid" — upgrade the account without verifying actual payment
    db()->prepare("UPDATE users SET plan='pro' WHERE id=?")->execute([$u['id']]);
    flash('success','🎉 Congratulations! Your account has been upgraded to Pro.');
    redirect('analytics');
}

function action_order_place(): void {
    $u=require_login();
    verify_csrf();
    $gid=(int)($_POST['gig_id']??0);
    $req=trim($_POST['requirements']??'');
    $gigs=fetch_gigs('g.id=?',[$gid],1); $g=$gigs[0]??null;
    if(!$g){flash('error','Gig not found.');redirect('');}
    if($g['seller_id']===$u['id']){flash('error','You cannot order your own gig.');redirect("gig/$gid");}
    db()->prepare("INSERT INTO orders(buyer_id,seller_id,gig_id,gig_title,price,requirements,status) VALUES(?,?,?,?,?,?,'in_progress')")
        ->execute([$u['id'],$g['seller_id'],$gid,$g['title'],(int)$g['price'],$req]);
    $oid=(int)db()->lastInsertId();
    flash('success','Order placed! The seller will start working soon.');
    redirect("order/$oid");
}

function action_profile_update(): void {
    $u=require_login(); verify_csrf();
    $name=trim($_POST['name']??''); $email=trim($_POST['email']??'');
    $bio=trim($_POST['bio']??''); $skills=trim($_POST['skills']??''); $pass=$_POST['password']??'';
    if(!$name||!$email||!filter_var($email,FILTER_VALIDATE_EMAIL)){flash('error','Invalid name or email.');redirect('profile');}
    try {
        if($pass&&strlen($pass)>=6)
            db()->prepare("UPDATE users SET name=?,email=?,bio=?,skills=?,password_hash=? WHERE id=?")->execute([$name,$email,$bio,$skills,password_hash($pass,PASSWORD_DEFAULT),$u['id']]);
        else
            db()->prepare("UPDATE users SET name=?,email=?,bio=?,skills=? WHERE id=?")->execute([$name,$email,$bio,$skills,$u['id']]);
        flash('success','Profile updated.');
    } catch(PDOException){flash('error','Email already in use.');}
    redirect('profile');
}

function action_install(): void {
    install_schema(db(),true); flash('success','Database reset.'); redirect('login');
}
