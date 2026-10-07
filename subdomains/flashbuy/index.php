<?php
require_once __DIR__.'/lib/bootstrap.php';
require_once __DIR__.'/lib/layout.php';

$req=$_SERVER['REQUEST_URI'];
$rel='/'.ltrim(substr(parse_url($req,PHP_URL_PATH),strlen(BASE)),'/');
if($rel!=='/'&&str_ends_with($rel,'/')) $rel=rtrim($rel,'/');
$method=$_SERVER['REQUEST_METHOD'];


match(true){
    $rel==='/login'         &&$method==='POST' => action_login(),
    $rel==='/register'      &&$method==='POST' => action_register(),
    $rel==='/logout'        &&$method==='POST' => action_logout(),
    $rel==='/buy'           &&$method==='POST' => action_buy(),
    $rel==='/install'       &&$method==='POST' => action_install(),

    $rel==='/'                                              => page_home(),
    (bool)preg_match('#^/product/(\d+)$#',$rel,$m)         => page_product((int)$m[1]),
    $rel==='/orders'                                        => page_orders(),
    $rel==='/dashboard'                                     => page_dashboard(),
    $rel==='/login'                                         => page_login(),
    $rel==='/register'                                      => page_register(),
    $rel==='/admin'                                         => page_admin(),
    $rel==='/install'                                       => page_install(),
    default                                                 => page_404(),
};

/* ══════════════════════════════════════════════════════════
   PAGE: HOME
══════════════════════════════════════════════════════════ */
function page_home(): void {
    $u=user();
    $products=db()->query("SELECT * FROM products ORDER BY id")->fetchAll();
    $bought=[];
    if($u){
        $st=db()->prepare("SELECT product_id FROM orders WHERE user_id=? AND status!='cancelled'");
        $st->execute([$u['id']]); foreach($st->fetchAll() as $r) $bought[$r['product_id']]=true;
    }
    $mini=array_slice($products,0,3);
    page_open('Flash Sale — Up to 60% Off');
    ?>
<!-- HERO -->
<div class="hero">
  <div class="hero-inner">
    <div>
      <div class="hero-eyebrow">
        <div class="live-dot"></div>
        <div class="live-text">Live Flash Sale</div>
        <div style="background:#ef4444;color:#fff;font-size:.55rem;font-weight:800;padding:2px 7px;border-radius:3px;letter-spacing:.5px">TODAY ONLY</div>
      </div>
      <h1 class="hero-title">Deals That<br><span>Don't Wait.</span></h1>
      <p class="hero-sub">Up to 60% off on premium electronics. Limited stock. Strictly 1 per customer — act fast before it's gone.</p>
      <div style="margin-bottom:16px;font-size:.78rem;color:#6b7280;font-weight:600;text-transform:uppercase;letter-spacing:.5px">Sale ends in</div>
      <div class="countdown">
        <div class="cd-box"><span class="cd-num" id="cd-h">00</span><div class="cd-label">Hours</div></div>
        <div class="cd-sep">:</div>
        <div class="cd-box"><span class="cd-num" id="cd-m">00</span><div class="cd-label">Mins</div></div>
        <div class="cd-sep">:</div>
        <div class="cd-box"><span class="cd-num" id="cd-s">00</span><div class="cd-label">Secs</div></div>
      </div>
      <div class="hero-chips">
        <span class="hero-chip">🔒 Secure checkout</span>
        <span class="hero-chip">🚚 Free delivery</span>
        <span class="hero-chip">↩️ 10-day returns</span>
        <span class="hero-chip">✅ Genuine products</span>
      </div>
    </div>
    <div class="hero-visual">
      <?php foreach($mini as $p):?>
      <a href="<?=url("product/{$p['id']}")?>" class="mini-deal" style="text-decoration:none">
        <div class="mini-emoji"><?=h($p['emoji'])?></div>
        <div class="mini-info">
          <div class="mini-name"><?=h($p['name'])?></div>
          <div class="mini-price"><?=inr($p['sale_price'])?> <span style="color:#6b7280;font-weight:400;text-decoration:line-through;font-size:.65rem"><?=inr($p['original_price'])?></span></div>
        </div>
        <div style="background:var(--p);color:#fff;font-size:.65rem;font-weight:800;padding:3px 8px;border-radius:5px;white-space:nowrap"><?=pct($p['original_price'],$p['sale_price'])?>% OFF</div>
      </a>
      <?php endforeach;?>
    </div>
  </div>
</div>

<!-- CATEGORY FILTERS (decorative) -->
<div style="background:#fff;border-bottom:1px solid var(--border);padding:0 24px">
  <div style="max-width:1200px;margin:0 auto;display:flex;gap:4px;overflow-x:auto;padding:12px 0">
    <?php foreach(['All Deals'=>'⚡','Smartphones'=>'📱','Laptops'=>'💻','Gaming'=>'🎮','Audio'=>'🎧','Wearables'=>'⌚','TVs'=>'📺','Appliances'=>'🌀'] as $lbl=>$ic):?>
    <div style="white-space:nowrap;padding:6px 14px;border-radius:20px;font-size:.8rem;font-weight:600;border:1px solid var(--border);cursor:pointer;background:<?=$lbl==='All Deals'?'var(--p)':'#fff'?>;color:<?=$lbl==='All Deals'?'#fff':'var(--text2)'?>">
      <?=$ic?> <?=$lbl?>
    </div>
    <?php endforeach;?>
  </div>
</div>

<div class="main">
  <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:24px">
    <div>
      <div class="page-title">⚡ Today's Flash Deals</div>
      <div class="section-sub"><?=count($products)?> products · New deals every hour · Strictly 1 per customer</div>
    </div>
    <div style="display:flex;align-items:center;gap:6px;background:#fff3e8;border:1px solid var(--p3);border-radius:8px;padding:8px 14px;font-size:.78rem;font-weight:600;color:#7c2d12">
      ⚠️ Limit: 1 per customer
    </div>
  </div>
  <div class="product-grid">
    <?php foreach($products as $p) deal_card($p, isset($bought[$p['id']]));?>
  </div>
</div>
<?php page_close();
}

/* ══════════════════════════════════════════════════════════
   PAGE: PRODUCT DETAIL
══════════════════════════════════════════════════════════ */
function page_product(int $id): void {
    $st=db()->prepare("SELECT * FROM products WHERE id=?"); $st->execute([$id]); $p=$st->fetch();
    if(!$p){page_404();return;}
    $u=user(); $bought=false; $order_count=0;
    if($u){
        $ost=db()->prepare("SELECT COUNT(*) FROM orders WHERE user_id=? AND product_id=? AND status!='cancelled'");
        $ost->execute([$u['id'],$id]); $order_count=(int)$ost->fetchColumn();
        $bought=$order_count>0;
    }
    $disc=pct($p['original_price'],$p['sale_price']);
    $stock_pct=min(100,max(0,(int)round($p['stock']/10*100)));

    page_open(h($p['name']));
    ?>
<div class="main">
  <div style="font-size:.82rem;color:var(--text3);margin-bottom:16px">
    <a href="<?=url()?>">Home</a> › <?=h($p['category'])?> › <?=h($p['name'])?>
  </div>

  <div style="display:grid;grid-template-columns:1fr 1fr;gap:32px;align-items:start">
    <!-- Image Panel -->
    <div>
      <div class="card" style="aspect-ratio:1/1;display:flex;align-items:center;justify-content:center;font-size:10rem;background:linear-gradient(135deg,#fff8f3,#fff3e8);position:relative">
        <?=h($p['emoji'])?>
        <div style="position:absolute;top:14px;left:14px;background:var(--p);color:#fff;font-size:.7rem;font-weight:800;padding:4px 10px;border-radius:5px"><?=$disc?>% OFF</div>
        <?php if($p['stock']<=3&&$p['stock']>0):?>
        <div style="position:absolute;bottom:14px;left:0;right:0;background:rgba(220,38,38,.9);color:#fff;font-size:.75rem;font-weight:700;text-align:center;padding:8px">
          🔥 Only <?=$p['stock']?> left — Selling Fast!
        </div>
        <?php endif;?>
      </div>
      <!-- Thumbnails (decorative) -->
      <div style="display:flex;gap:8px;margin-top:12px">
        <?php for($i=0;$i<4;$i++):?>
        <div style="flex:1;aspect-ratio:1;background:linear-gradient(135deg,#fff8f3,#fff3e8);border:<?=$i===0?'2px solid var(--p)':'1px solid var(--border)'?>;border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:1.4rem;cursor:pointer">
          <?=h($p['emoji'])?>
        </div>
        <?php endfor;?>
      </div>
    </div>

    <!-- Info Panel -->
    <div>
      <div style="font-size:.7rem;font-weight:700;color:var(--p);text-transform:uppercase;letter-spacing:.6px;margin-bottom:6px">
        <?=h($p['brand'])?> · <?=h($p['category'])?>
      </div>
      <h1 style="font-size:1.5rem;font-weight:800;line-height:1.25;margin-bottom:10px"><?=h($p['name'])?></h1>
      <div style="display:flex;align-items:center;gap:8px;margin-bottom:14px">
        <span style="color:var(--amber);font-size:.9rem;letter-spacing:2px"><?=stars((float)$p['rating'])?></span>
        <span style="font-weight:700"><?=$p['rating']?></span>
        <span style="color:var(--text3)">(<?=number_format($p['review_count'])?> ratings)</span>
        <span style="color:var(--text3)">·</span>
        <span style="color:var(--green);font-weight:600;font-size:.8rem">✓ Verified Purchase</span>
      </div>

      <!-- Price Block -->
      <div style="background:var(--bg3);border:1px solid var(--p3);border-radius:12px;padding:18px;margin-bottom:16px">
        <div style="display:flex;align-items:center;gap:10px;margin-bottom:6px">
          <span style="font-size:.72rem;font-weight:600;background:var(--p);color:#fff;padding:3px 8px;border-radius:4px">FLASH PRICE</span>
          <span style="font-size:.72rem;color:var(--text3)">Original: <s><?=inr($p['original_price'])?></s></span>
        </div>
        <div style="display:flex;align-items:baseline;gap:10px">
          <span style="font-size:2.2rem;font-weight:900"><?=inr($p['sale_price'])?></span>
          <span style="font-size:1rem;font-weight:800;color:var(--green)"><?=$disc?>% OFF</span>
        </div>
        <div style="font-size:.75rem;color:var(--text3);margin-top:3px">Inclusive of all taxes · Free delivery</div>
      </div>

      <!-- Stock Meter -->
      <div style="margin-bottom:16px">
        <div style="display:flex;justify-content:space-between;font-size:.75rem;margin-bottom:5px">
          <span style="font-weight:600;color:var(--red)">🔥 Selling Fast</span>
          <span style="color:var(--text3)"><?=$p['stock']?> units remaining</span>
        </div>
        <div class="stock-bar-bg" style="height:8px"><div class="stock-bar" style="width:<?=$stock_pct?>%"></div></div>
      </div>

      <!-- Limit Notice -->
      <div style="background:#fef3c7;border:1px solid #fcd34d;border-radius:8px;padding:12px 14px;margin-bottom:18px;display:flex;gap:8px;align-items:flex-start">
        <span style="font-size:1rem">⚠️</span>
        <div>
          <div style="font-size:.8rem;font-weight:700;color:#92400e">Purchase Limit: <?=$p['limit_per_user']?> per customer</div>
          <div style="font-size:.75rem;color:#78350f;margin-top:2px">To ensure everyone gets a fair chance, each customer may purchase this item only <?=$p['limit_per_user']?> time<?=$p['limit_per_user']>1?'s':''?>.</div>
        </div>
      </div>

      <!-- Buy Action -->
      <?php if(!$u):?>
      <a href="<?=url('login')?>" class="btn btn-primary btn-lg" style="width:100%">Sign In to Buy</a>
      <?php elseif($bought):?>
      <div class="btn btn-lg" style="width:100%;background:#dcfce7;color:#14532d;cursor:default">✅ Already Purchased (Limit Reached)</div>
      <a href="<?=url('orders')?>" class="btn btn-secondary" style="width:100%;margin-top:10px">View My Orders →</a>
      <?php elseif($p['stock']<=0):?>
      <div class="btn btn-lg" style="width:100%;background:#f3f4f6;color:var(--text3);cursor:not-allowed">Sold Out</div>
      <?php else:?>
      <form method="POST" action="<?=url('buy')?>">
        <input type="hidden" name="_csrf" value="<?=h(csrf_token())?>">
        <input type="hidden" name="product_id" value="<?=$id?>">
        <button class="btn btn-primary btn-lg" style="width:100%;font-size:1.05rem">
          ⚡ Buy Now — <?=inr($p['sale_price'])?>
        </button>
      </form>
      <?php endif;?>

      <!-- Trust Badges -->
      <div style="display:flex;gap:8px;flex-wrap:wrap;margin-top:14px">
        <span class="tag tag-success">✓ Genuine Product</span>
        <span class="tag tag-info">🚚 Free Delivery</span>
        <span class="tag tag-info">↩️ 10-day Return</span>
        <span class="tag tag-dark">🔒 Secure Pay</span>
      </div>
    </div>
  </div>

  <!-- Description -->
  <div class="card card-pad" style="margin-top:24px">
    <div style="font-weight:700;margin-bottom:12px">Product Details</div>
    <p style="color:var(--text2);line-height:1.8;font-size:.9rem"><?=h($p['description'])?></p>
  </div>
</div>
<?php page_close();
}

/* ══════════════════════════════════════════════════════════
   PAGE: ORDERS
══════════════════════════════════════════════════════════ */
function page_orders(): void {
    $u=require_login();
    $st=db()->prepare("SELECT o.*,p.name AS pname,p.brand,p.emoji,p.sale_price FROM orders o JOIN products p ON p.id=o.product_id WHERE o.user_id=? ORDER BY o.id DESC");
    $st->execute([$u['id']]); $orders=$st->fetchAll();

    // Check for race condition exploit: same user, same product, 2+ orders
    $flag_show=false;
    if($orders){
        $dup_st=db()->prepare("SELECT product_id,COUNT(*) AS cnt FROM orders WHERE user_id=? AND status!='cancelled' GROUP BY product_id HAVING cnt>=2");
        $dup_st->execute([$u['id']]); $flag_show=(bool)$dup_st->fetch();
    }

    page_open('My Orders');
    echo '<div class="main">';
    echo '<div style="margin-bottom:24px"><div class="page-title">📦 My Orders</div><div class="section-sub">'.count($orders).' orders placed</div></div>';

    if($flag_show):?>
    <div class="flag-box">
      <h3>🚩 Race Condition Exploit Successful!</h3>
      <p style="color:#6ee7b7;font-size:.875rem;margin-bottom:10px">
        You bypassed the <strong>1-per-customer purchase limit</strong> using a race condition.<br>
        The <code style="background:#052e1c;padding:2px 6px;border-radius:4px">POST /buy</code> endpoint checks the order count and then inserts — but with <strong>no transaction and no SELECT FOR UPDATE</strong>, concurrent requests all see zero orders before any insert commits.<br>
        All concurrent requests pass the check simultaneously, each inserting a separate order.
      </p>
      <div style="font-size:.78rem;color:#34d399;margin-bottom:6px">Your Flag:</div>
      <div class="flag-val"><?=LAB_FLAG?></div>
      <div style="margin-top:12px;font-size:.75rem;color:#6ee7b7">
        <strong>Fix:</strong> Wrap the check+insert in a transaction with <code style="background:#052e1c;padding:2px 4px;border-radius:3px">SELECT ... FOR UPDATE</code>, or add a <code style="background:#052e1c;padding:2px 4px;border-radius:3px">UNIQUE(user_id, product_id)</code> constraint.
      </div>
    </div>
    <?php endif;?>

    <?php if(!$orders):?>
    <div class="card card-pad" style="text-align:center;padding:60px">
      <div style="font-size:3.5rem;margin-bottom:12px">📦</div>
      <div class="page-title">No orders yet</div>
      <a href="<?=url()?>" class="btn btn-primary" style="margin-top:20px">Shop Flash Deals</a>
    </div>
    <?php else:?>
    <div class="card">
      <table class="table">
        <thead><tr><th>Order #</th><th>Product</th><th>Price Paid</th><th>Status</th><th>Date</th></tr></thead>
        <tbody>
        <?php foreach($orders as $o):
          $sc=['confirmed'=>'tag-info','shipped'=>'tag-warning','delivered'=>'tag-success','cancelled'=>'tag-danger'];?>
        <tr>
          <td style="font-weight:700">#<?=$o['id']?></td>
          <td>
            <div style="display:flex;align-items:center;gap:10px">
              <span style="font-size:1.5rem"><?=h($o['emoji'])?></span>
              <div>
                <div style="font-weight:600;font-size:.875rem"><?=h($o['pname'])?></div>
                <div style="font-size:.72rem;color:var(--text3)"><?=h($o['brand'])?></div>
              </div>
            </div>
          </td>
          <td style="font-weight:700"><?=inr($o['sale_price'])?></td>
          <td><span class="tag <?=$sc[$o['status']]??'tag-info'?>"><?=ucfirst($o['status'])?></span></td>
          <td style="font-size:.8rem;color:var(--text3)"><?=date('d M Y, g:i A',strtotime($o['placed_at']))?></td>
        </tr>
        <?php endforeach;?>
        </tbody>
      </table>
    </div>
    <?php endif;?>
    </div>
<?php page_close();
}

/* ══════════════════════════════════════════════════════════
   PAGE: DASHBOARD
══════════════════════════════════════════════════════════ */
function page_dashboard(): void {
    $u=require_login();
    $st=db()->prepare("SELECT COUNT(*),COALESCE(SUM(p.sale_price),0) FROM orders o JOIN products p ON p.id=o.product_id WHERE o.user_id=? AND o.status!='cancelled'");
    $st->execute([$u['id']]); [$ocnt,$spent]=$st->fetch(PDO::FETCH_NUM);
    page_open('Dashboard');
    ?>
<div class="main">
  <div class="page-title" style="margin-bottom:20px">👤 My Account</div>
  <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:16px;margin-bottom:24px">
    <?php foreach([['📦',$ocnt,'Orders Placed'],['💰',inr((int)$spent),'Total Spent'],['⚡','Active','Sale Status']] as[$i,$v,$l]):?>
    <div class="card card-pad" style="text-align:center"><div style="font-size:1.8rem"><?=$i?></div><div style="font-size:1.4rem;font-weight:800;margin:4px 0"><?=$v?></div><div style="font-size:.75rem;color:var(--text3);font-weight:600"><?=$l?></div></div>
    <?php endforeach;?>
  </div>
  <div style="display:grid;grid-template-columns:2fr 1fr;gap:20px">
    <div class="card card-pad">
      <div style="font-weight:700;margin-bottom:12px">Quick Links</div>
      <?php foreach([[url(),'⚡ Browse Flash Deals'],[url('orders'),'📦 My Orders']] as[$href,$lbl]):?>
      <a href="<?=$href?>" class="btn btn-secondary" style="width:100%;margin-bottom:8px;justify-content:flex-start"><?=$lbl?></a>
      <?php endforeach;?>
    </div>
    <div class="card card-pad" style="background:#fff8f3;border-color:var(--p3)">
      <div style="font-size:.75rem;font-weight:700;color:#7c2d12;margin-bottom:8px">💡 Lab Hint</div>
      <p style="font-size:.8rem;color:#92400e;line-height:1.6">
        Flash deals have a <strong>1-per-customer limit</strong>.<br><br>
        The <code style="background:#fff3e8;padding:1px 4px;border-radius:3px">/buy</code> endpoint checks the limit but has no <code style="background:#fff3e8;padding:1px 4px;border-radius:3px">SELECT FOR UPDATE</code>.<br><br>
        Send multiple requests <strong>simultaneously</strong> to bypass it.
      </p>
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
    page_open('Sign In');
    ?>
<div class="main" style="max-width:420px;margin-left:auto;margin-right:auto;padding-top:32px">
  <div style="text-align:center;margin-bottom:24px"><div style="font-size:2.5rem">⚡</div><div class="page-title" style="margin-top:8px">Sign In to FlashBuy</div></div>
  <div class="card card-pad">
    <form method="POST" action="<?=url('login')?>">
      <input type="hidden" name="_csrf" value="<?=h(csrf_token())?>">
      <div class="form-group"><label class="form-label">Email</label><input type="email" name="email" class="form-control" required autofocus></div>
      <div class="form-group"><label class="form-label">Password</label><input type="password" name="password" class="form-control" required></div>
      <button class="btn btn-primary btn-lg" style="width:100%">Sign In</button>
    </form>
    <div style="text-align:center;margin-top:14px;font-size:.85rem;color:var(--text3)">New? <a href="<?=url('register')?>">Create account</a></div>
    <div style="margin-top:14px;padding:12px;background:var(--bg3);border-radius:8px;font-size:.75rem;color:var(--text3)">
      <strong style="color:var(--p)">Demo accounts:</strong><br>
      student@flashbuy.lab / student123<br>
      flash@example.com / flash123<br>
      admin@flashbuy.app / admin123
    </div>
  </div>
</div>
<?php page_close();
}

function page_register(): void {
    if(user()) redirect('');
    page_open('Create Account');
    ?>
<div class="main" style="max-width:420px;margin-left:auto;margin-right:auto;padding-top:32px">
  <div class="page-title" style="margin-bottom:20px;text-align:center">Create Account</div>
  <div class="card card-pad">
    <form method="POST" action="<?=url('register')?>">
      <input type="hidden" name="_csrf" value="<?=h(csrf_token())?>">
      <div class="form-group"><label class="form-label">Full Name</label><input type="text" name="name" class="form-control" required></div>
      <div class="form-group"><label class="form-label">Email</label><input type="email" name="email" class="form-control" required></div>
      <div class="form-group"><label class="form-label">Password</label><input type="password" name="password" class="form-control" required minlength="6"></div>
      <button class="btn btn-primary btn-lg" style="width:100%">Create Account</button>
    </form>
    <div style="text-align:center;margin-top:14px;font-size:.85rem;color:var(--text3)">Already have an account? <a href="<?=url('login')?>">Sign In</a></div>
  </div>
</div>
<?php page_close();
}

function page_admin(): void {
    $u=require_login();
    if(!$u['is_admin']){flash('error','Access denied.');redirect('');}
    $orders=db()->query("SELECT o.*,u.name AS uname,u.email,p.name AS pname,p.sale_price FROM orders o JOIN users u ON u.id=o.user_id JOIN products p ON p.id=o.product_id ORDER BY o.id DESC LIMIT 50")->fetchAll();
    $users=db()->query("SELECT u.*,(SELECT COUNT(*) FROM orders WHERE user_id=u.id) AS order_cnt FROM users u ORDER BY id")->fetchAll();
    page_open('Admin');
    echo '<div class="main"><div class="page-title" style="margin-bottom:20px">⚙️ Admin — FlashBuy</div>';
    echo '<div class="card" style="margin-bottom:20px"><div style="padding:14px 20px;font-weight:700;border-bottom:1px solid var(--border)">All Orders</div>';
    echo '<table class="table"><thead><tr><th>#</th><th>Customer</th><th>Product</th><th>Price</th><th>Status</th><th>Date</th></tr></thead><tbody>';
    foreach($orders as $o)
        echo "<tr><td style='font-weight:700'>#".h($o['id'])."</td><td>".h($o['uname'])."<div style='font-size:.7rem;color:var(--text3)'>".h($o['email'])."</div></td><td>".h($o['pname'])."</td><td>".inr($o['sale_price'])."</td><td><span class='tag ".(['confirmed'=>'tag-info','shipped'=>'tag-warning','delivered'=>'tag-success','cancelled'=>'tag-danger'][$o['status']]??"tag-info").">".ucfirst($o['status'])."</span></td><td style='font-size:.78rem;color:var(--text3)'>".date('d M Y, g:i A',strtotime($o['placed_at']))."</td></tr>";
    echo '</tbody></table></div>';
    echo '<div class="card"><div style="padding:14px 20px;font-weight:700;border-bottom:1px solid var(--border)">Users</div>';
    echo '<table class="table"><thead><tr><th>ID</th><th>Name</th><th>Email</th><th>Orders</th><th>Admin</th></tr></thead><tbody>';
    foreach($users as $uu)
        echo "<tr><td>".h($uu['id'])."</td><td>".h($uu['name'])."</td><td>".h($uu['email'])."</td><td><span class='tag tag-info'>".h($uu['order_cnt'])."</span></td><td>".($uu['is_admin']?'<span class="tag tag-dark">Admin</span>':'—')."</td></tr>";
    echo '</tbody></table></div></div>';
    page_close();
}

function page_install(): void {
    page_open('Reset Lab');
    ?>
<div class="main" style="max-width:440px;margin-left:auto;margin-right:auto">
  <div class="card card-pad" style="text-align:center">
    <div style="font-size:2.5rem;margin-bottom:12px">⚗️</div>
    <div class="page-title">Reset Lab Database</div>
    <p style="color:var(--text3);margin:12px 0 20px;font-size:.875rem">Drops all tables and re-seeds with fresh data.</p>
    <form method="POST"><button class="btn btn-danger btn-lg" style="width:100%">Reset Database</button></form>
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
    $name=trim($_POST['name']??''); $email=trim($_POST['email']??''); $pass=$_POST['password']??'';
    if(!$name||!$email||!$pass||!filter_var($email,FILTER_VALIDATE_EMAIL)){flash('error','Fill all fields correctly.');redirect('register');}
    try {
        db()->prepare("INSERT INTO users(name,email,password_hash) VALUES(?,?,?)")->execute([$name,$email,password_hash($pass,PASSWORD_DEFAULT)]);
        session_regenerate_id(true); $_SESSION['uid']=(int)db()->lastInsertId();
        flash('success','Account created!'); redirect('dashboard');
    } catch(PDOException){flash('error','Email already registered.');redirect('register');}
}

function action_logout(): void {
    verify_csrf(); session_destroy(); header('Location:'.url('login')); exit;
}

/*
 * INTENTIONALLY VULNERABLE BUY ENDPOINT
 * POST /buy  params: product_id
 *
 * Vulnerability: race condition — check-then-act without a transaction or SELECT FOR UPDATE.
 * Multiple concurrent POST requests all see 0 existing orders before any INSERT commits,
 * so they all pass the limit check and each creates a separate order.
 *
 * To exploit: send 10+ concurrent requests using Burp Intruder (Pitchfork, null payloads)
 * or any concurrent HTTP client (e.g. Python asyncio, Go goroutines, GNU parallel).
 */
function action_buy(): void {
    $u=require_login(); verify_csrf();
    $pid=(int)($_POST['product_id']??0);

    // Load product
    $ps=db()->prepare("SELECT * FROM products WHERE id=?"); $ps->execute([$pid]); $p=$ps->fetch();
    if(!$p){flash('error','Product not found.');redirect('');}
    if($p['stock']<=0){flash('error','Sorry, this item is sold out.');redirect("product/$pid");}

    // Release session file lock NOW so concurrent requests are not serialized.
    // PHP's default session handler holds an exclusive flock() on the session file.
    // Without session_write_close() here, concurrent requests queue behind each other
    // and the race window never opens. Real apps often release the lock early for
    // read-only pages — here it's the mechanism that makes the race possible.
    session_write_close();

    // INTENTIONAL VULNERABILITY: limit check has no transaction and no SELECT FOR UPDATE.
    // Race window: concurrent requests all reach here before any INSERT commits.
    // All see count=0, all pass the check, all insert.
    $lc=db()->prepare("SELECT COUNT(*) FROM orders WHERE user_id=? AND product_id=? AND status!='cancelled'");
    $lc->execute([$u['id'],$pid]);
    if((int)$lc->fetchColumn()>=$p['limit_per_user']){
        session_start();
        flash('error',"Purchase limit reached. Only {$p['limit_per_user']} per customer.");
        redirect("product/$pid");
    }

    // No transaction here — this is the exploitable race window
    db()->prepare("INSERT INTO orders(user_id,product_id,status) VALUES(?,?,'confirmed')")->execute([$u['id'],$pid]);
    db()->prepare("UPDATE products SET stock=stock-1 WHERE id=? AND stock>0")->execute([$pid]);

    session_start();
    flash('success',"⚡ Order confirmed! {$p['name']} is on its way.");
    redirect('orders');
}

function action_install(): void {
    install_schema(db(),true); flash('success','Database reset.'); redirect('login');
}
