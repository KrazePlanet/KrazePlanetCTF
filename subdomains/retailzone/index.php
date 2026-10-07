<?php
require_once __DIR__.'/lib/bootstrap.php';
require_once __DIR__.'/lib/layout.php';

$req=$_SERVER['REQUEST_URI'];
$rel='/'.ltrim(substr(parse_url($req,PHP_URL_PATH),strlen(BASE)),'/');
if($rel!=='/'&&str_ends_with($rel,'/')) $rel=rtrim($rel,'/');
$method=$_SERVER['REQUEST_METHOD'];

match(true){
    $rel==='/login'            &&$method==='POST' => action_login(),
    $rel==='/register'         &&$method==='POST' => action_register(),
    $rel==='/logout'           &&$method==='POST' => action_logout(),
    $rel==='/cart/add'         &&$method==='POST' => action_cart_add(),
    $rel==='/cart/update'      &&$method==='POST' => action_cart_update(),
    $rel==='/cart/remove'      &&$method==='POST' => action_cart_remove(),
    $rel==='/checkout'         &&$method==='POST' => action_checkout(),
    $rel==='/returns/initiate' &&$method==='POST' => action_return_initiate(),
    $rel==='/returns/cancel'   &&$method==='POST' => action_return_cancel(),
    $rel==='/profile/update'   &&$method==='POST' => action_profile_update(),
    $rel==='/install'          &&$method==='POST' => action_install(),

    $rel==='/'                                              => page_home(),
    (bool)preg_match('#^/category/([a-z]+)$#',$rel,$m)     => page_category($m[1]),
    (bool)preg_match('#^/product/(\d+)$#',$rel,$m)         => page_product((int)$m[1]),
    $rel==='/cart'                                          => page_cart(),
    $rel==='/checkout'                                      => page_checkout_get(),
    (bool)preg_match('#^/order/(\d+)$#',$rel,$m)           => page_order((int)$m[1]),
    $rel==='/orders'                                        => page_orders(),
    $rel==='/returns'                                       => page_returns(),
    $rel==='/wallet'                                        => page_wallet(),
    $rel==='/dashboard'                                     => page_dashboard(),
    $rel==='/profile'                                       => page_profile(),
    $rel==='/login'                                         => page_login(),
    $rel==='/register'                                      => page_register(),
    $rel==='/admin'                                         => page_admin(),
    $rel==='/install'                                       => page_install(),
    default                                                 => page_404(),
};

/* ══════════════════════════════════════════════════════════
   HELPERS
══════════════════════════════════════════════════════════ */
function fetch_products(string $where='1=1', array $params=[], int $limit=60): array {
    $st=db()->prepare("SELECT p.*,c.slug AS cat_slug,c.name AS cat_name FROM products p JOIN categories c ON c.id=p.category_id WHERE $where ORDER BY p.rating DESC,p.review_count DESC LIMIT $limit");
    $st->execute($params); return $st->fetchAll();
}

function cart_lines(int $uid): array {
    $st=db()->prepare("SELECT ci.*,p.name,p.brand,p.price,p.original_price,c.slug AS cat_slug,(p.price*ci.quantity) AS subtotal FROM cart_items ci JOIN products p ON p.id=ci.product_id JOIN categories c ON c.id=p.category_id WHERE ci.user_id=?");
    $st->execute([$uid]); return $st->fetchAll();
}

function order_status_label(string $s): string {
    return match($s){
        'processing'=>'Processing','shipped'=>'Shipped','delivered'=>'Delivered',
        'return_initiated'=>'Return Initiated','return_cancelled'=>'Return Cancelled',
        'returned'=>'Returned','refunded'=>'Refunded',default=>ucfirst($s)
    };
}
function order_status_tag(string $s): string {
    return match($s){
        'delivered'=>'tag-success','refunded'=>'tag-success','returned'=>'tag-success',
        'return_initiated'=>'tag-warning','return_cancelled'=>'tag-danger',
        'processing','shipped'=>'tag-info',default=>'tag-info'
    };
}

/* ══════════════════════════════════════════════════════════
   PAGE: HOME
══════════════════════════════════════════════════════════ */
function page_home(): void {
    $cats=db()->query("SELECT * FROM categories ORDER BY sort_order")->fetchAll();
    $products=fetch_products('1=1',[],20);
    page_open('Fashion Forward, Always');
    ?>
<div class="hero">
  <div class="hero-title">Fashion Forward,<br>Always.</div>
  <p class="hero-sub">Shop the latest trends in women's, men's and kids' fashion. New arrivals every week.</p>
  <div class="hero-chips">
    <span class="hero-chip">🚚 Free delivery ₹799+</span>
    <span class="hero-chip">↩️ 7-day returns</span>
    <span class="hero-chip">💳 Instant wallet refunds</span>
    <span class="hero-chip">✅ Genuine brands</span>
  </div>
</div>
<div class="cat-strip">
  <?php foreach($cats as $c):?>
  <a href="<?=url("category/{$c['slug']}")?>" class="cat-pill"><?=$c['icon']?> <?=h($c['name'])?></a>
  <?php endforeach;?>
</div>
<div class="main">
  <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px">
    <div><div class="page-title">🔥 Trending Now</div><div class="section-sub">Bestsellers this season</div></div>
  </div>
  <div class="product-grid"><?php foreach($products as $p) product_card($p);?></div>
</div>
<?php page_close();
}

/* ══════════════════════════════════════════════════════════
   PAGE: CATEGORY
══════════════════════════════════════════════════════════ */
function page_category(string $slug): void {
    $st=db()->prepare("SELECT * FROM categories WHERE slug=?"); $st->execute([$slug]); $cat=$st->fetch();
    if(!$cat){page_404();return;}
    $products=fetch_products('p.category_id=?',[$cat['id']]);
    page_open($cat['name']);
    echo '<div class="main">';
    echo '<div style="margin-bottom:20px"><div class="page-title">'.$cat['icon'].' '.h($cat['name']).'</div><div class="section-sub">'.count($products).' styles available</div></div>';
    echo '<div class="product-grid">'; foreach($products as $p) product_card($p); echo '</div></div>';
    page_close();
}

/* ══════════════════════════════════════════════════════════
   PAGE: PRODUCT
══════════════════════════════════════════════════════════ */
function page_product(int $id): void {
    $ps=fetch_products('p.id=?',[$id],1); $p=$ps[0]??null;
    if(!$p){page_404();return;}
    $disc=$p['original_price']?(int)round((1-$p['price']/$p['original_price'])*100):0;
    $emojis=['women'=>'👗','men'=>'👔','kids'=>'🧒','footwear'=>'👟','accessories'=>'👜','beauty'=>'💄'];
    $bgs=['women'=>'#fdf2f8','men'=>'#eff6ff','kids'=>'#f0fdf4','footwear'=>'#fffbeb','accessories'=>'#faf5ff','beauty'=>'#fdf2f8'];
    page_open(h($p['name']));
    ?>
<div class="main" style="display:grid;grid-template-columns:1fr 1fr;gap:32px;align-items:start">
  <div class="card" style="aspect-ratio:3/4;display:flex;align-items:center;justify-content:center;font-size:8rem;background:<?=$bgs[$p['cat_slug']]??'#fdf2f8'?>">
    <?=$emojis[$p['cat_slug']]??'🛍️'?>
  </div>
  <div>
    <div style="font-size:.75rem;color:var(--p);font-weight:700;text-transform:uppercase;letter-spacing:.6px"><?=h($p['cat_name'])?></div>
    <h1 style="font-size:1.5rem;font-weight:800;margin:6px 0;line-height:1.3"><?=h($p['name'])?></h1>
    <div style="font-size:.82rem;color:var(--text3);margin-bottom:12px"><?=h($p['brand'])?></div>
    <div style="display:flex;align-items:center;gap:6px;margin-bottom:16px">
      <span class="stars"><?=stars((float)$p['rating'])?></span>
      <span style="font-weight:700"><?=$p['rating']?></span>
      <span style="color:var(--text3)">(<?=number_format($p['review_count'])?> ratings)</span>
    </div>
    <div style="display:flex;align-items:baseline;gap:10px;margin-bottom:16px">
      <span style="font-size:1.8rem;font-weight:900"><?=inr((int)$p['price'])?></span>
      <?php if($p['original_price']):?>
      <span style="font-size:1rem;color:var(--text3);text-decoration:line-through"><?=inr((int)$p['original_price'])?></span>
      <span class="tag tag-info"><?=$disc?>% OFF</span>
      <?php endif;?>
    </div>
    <p style="color:var(--text2);line-height:1.7;margin-bottom:20px;font-size:.9rem"><?=h($p['description'])?></p>
    <form method="POST" action="<?=url('cart/add')?>">
      <input type="hidden" name="_csrf" value="<?=h(csrf_token())?>">
      <input type="hidden" name="product_id" value="<?=$p['id']?>">
      <?php if($p['size_options']&&$p['size_options']!=='One Size'):
        $sizes=explode(',',$p['size_options']);?>
      <div class="form-group">
        <label class="form-label">Select Size</label>
        <div style="display:flex;gap:8px;flex-wrap:wrap">
          <?php foreach($sizes as $i=>$sz):?>
          <label style="cursor:pointer">
            <input type="radio" name="size" value="<?=h(trim($sz))?>" <?=$i===0?'checked':''?> style="display:none" class="size-radio">
            <div class="size-opt" style="padding:7px 14px;border:1.5px solid #e5e7eb;border-radius:7px;font-size:.82rem;font-weight:600;transition:.15s"><?=h(trim($sz))?></div>
          </label>
          <?php endforeach;?>
        </div>
      </div>
      <?php else:?>
      <input type="hidden" name="size" value="One Size">
      <?php endif;?>
      <div style="display:flex;gap:10px;margin-top:4px">
        <button class="btn btn-primary btn-lg" style="flex:1">🛒 Add to Bag</button>
        <a href="<?=url('cart')?>" class="btn btn-outline">View Bag</a>
      </div>
    </form>
    <div style="display:flex;gap:8px;margin-top:14px;flex-wrap:wrap">
      <span class="tag tag-success">✓ In Stock</span>
      <span class="tag tag-info">↩️ 7-day returns</span>
      <span class="tag tag-info">🚚 Free delivery ₹799+</span>
    </div>
  </div>
</div>
<script>
document.querySelectorAll('.size-radio').forEach(r=>{
  r.addEventListener('change',()=>{
    document.querySelectorAll('.size-opt').forEach(d=>d.style.borderColor='#e5e7eb');
    r.nextElementSibling.style.borderColor='var(--p)';
  });
  if(r.checked) r.nextElementSibling.style.borderColor='var(--p)';
});
</script>
<?php page_close();
}

/* ══════════════════════════════════════════════════════════
   PAGE: CART
══════════════════════════════════════════════════════════ */
function page_cart(): void {
    $u=require_login(); $u=fresh_user();
    $lines=cart_lines($u['id']);
    $subtotal=array_sum(array_column($lines,'subtotal'));
    $shipping=$subtotal>=79900?0:9900;
    $wallet_usable=min((int)$u['wallet_balance'],$subtotal+$shipping);
    page_open('Shopping Bag');
    ?>
<div class="main">
  <div style="margin-bottom:20px"><div class="page-title">🛒 My Bag</div><div class="section-sub"><?=count($lines)?> item(s)</div></div>
  <?php if(!$lines):?>
  <div class="card card-pad" style="text-align:center;padding:60px">
    <div style="font-size:4rem;margin-bottom:12px">👜</div>
    <div class="page-title">Your bag is empty</div>
    <a href="<?=url()?>" class="btn btn-primary" style="margin-top:20px">Continue Shopping</a>
  </div>
  <?php else:?>
  <div style="display:grid;grid-template-columns:1fr 340px;gap:24px;align-items:start">
    <div class="card">
      <?php foreach($lines as $l):
        $disc=$l['original_price']?(int)round((1-$l['price']/$l['original_price'])*100):0;?>
      <div style="display:flex;gap:16px;padding:18px 20px;border-bottom:1px solid #f9fafb;align-items:center">
        <div style="width:80px;height:100px;background:#fdf2f8;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:2.5rem;flex-shrink:0">
          <?=(['women'=>'👗','men'=>'👔','kids'=>'🧒','footwear'=>'👟','accessories'=>'👜','beauty'=>'💄'])[$l['cat_slug']]??'🛍️'?>
        </div>
        <div style="flex:1">
          <div style="font-size:.7rem;color:var(--text3);font-weight:700;text-transform:uppercase"><?=h($l['brand'])?></div>
          <div style="font-weight:600;font-size:.9rem;margin:3px 0"><?=h($l['name'])?></div>
          <?php if($l['size']&&$l['size']!=='null'):?><div style="font-size:.75rem;color:var(--text3)">Size: <?=h($l['size'])?></div><?php endif;?>
          <div style="display:flex;align-items:center;gap:10px;margin-top:8px">
            <span style="font-weight:800"><?=inr((int)$l['price'])?></span>
            <?php if($disc>0):?><span class="tag tag-info"><?=$disc?>% OFF</span><?php endif;?>
          </div>
        </div>
        <div style="display:flex;flex-direction:column;gap:8px;align-items:center">
          <form method="POST" action="<?=url('cart/update')?>">
            <input type="hidden" name="_csrf" value="<?=h(csrf_token())?>">
            <input type="hidden" name="product_id" value="<?=$l['product_id']?>">
            <select name="quantity" class="form-control" style="width:70px;padding:6px" onchange="this.form.submit()">
              <?php for($q=1;$q<=10;$q++):?><option <?=$l['quantity']==$q?'selected':''?> value="<?=$q?>"><?=$q?></option><?php endfor;?>
            </select>
          </form>
          <form method="POST" action="<?=url('cart/remove')?>">
            <input type="hidden" name="_csrf" value="<?=h(csrf_token())?>">
            <input type="hidden" name="product_id" value="<?=$l['product_id']?>">
            <button class="btn btn-sm" style="background:transparent;color:var(--text3);font-size:.75rem;border:none">✕ Remove</button>
          </form>
        </div>
      </div>
      <?php endforeach;?>
    </div>

    <div>
      <?php if($u['wallet_balance']>0):?>
      <div class="wallet-card" style="margin-bottom:16px">
        <div style="font-size:.75rem;font-weight:600;opacity:.75;text-transform:uppercase;letter-spacing:.6px">RetailZone Wallet</div>
        <div style="font-size:1.8rem;font-weight:900;margin:4px 0"><?=inr((int)$u['wallet_balance'])?></div>
        <div style="font-size:.78rem;opacity:.8">Available balance</div>
      </div>
      <?php endif;?>
      <div class="card card-pad">
        <div style="font-weight:700;margin-bottom:16px">Price Details</div>
        <div style="display:flex;justify-content:space-between;font-size:.875rem;padding:7px 0;border-bottom:1px solid #f9fafb"><span style="color:var(--text3)">Subtotal (<?=count($lines)?> items)</span><span><?=inr($subtotal)?></span></div>
        <div style="display:flex;justify-content:space-between;font-size:.875rem;padding:7px 0;border-bottom:1px solid #f9fafb">
          <span style="color:var(--text3)">Delivery</span>
          <span><?=$shipping===0?'<span style="color:var(--green)">FREE</span>':inr($shipping)?></span>
        </div>
        <div style="display:flex;justify-content:space-between;font-weight:800;font-size:1.05rem;padding:12px 0"><span>Total</span><span><?=inr($subtotal+$shipping)?></span></div>
        <a href="<?=url('checkout')?>" class="btn btn-primary btn-lg" style="width:100%">Checkout →</a>
      </div>
    </div>
  </div>
  <?php endif;?>
</div>
<?php page_close();
}

/* ══════════════════════════════════════════════════════════
   PAGE: CHECKOUT
══════════════════════════════════════════════════════════ */
function page_checkout_get(): void {
    $u=require_login(); $u=fresh_user();
    $lines=cart_lines($u['id']);
    if(!$lines){flash('error','Your bag is empty.');redirect('cart');}
    $subtotal=array_sum(array_column($lines,'subtotal'));
    $shipping=$subtotal>=79900?0:9900;
    $total=$subtotal+$shipping;
    page_open('Checkout');
    ?>
<div class="main">
  <div class="page-title" style="margin-bottom:20px">Checkout</div>
  <form method="POST" action="<?=url('checkout')?>">
    <input type="hidden" name="_csrf" value="<?=h(csrf_token())?>">
    <div style="display:grid;grid-template-columns:1fr 340px;gap:24px;align-items:start">
      <div style="display:flex;flex-direction:column;gap:16px">
        <div class="card card-pad">
          <div style="font-weight:700;margin-bottom:14px">Delivery Address</div>
          <div class="grid-2">
            <div class="form-group"><label class="form-label">Full Name</label><input type="text" name="name" class="form-control" value="<?=h($u['name'])?>" required></div>
            <div class="form-group"><label class="form-label">Phone</label><input type="tel" name="phone" class="form-control" placeholder="9XXXXXXXXX" required></div>
          </div>
          <div class="form-group"><label class="form-label">Address</label><input type="text" name="address" class="form-control" placeholder="House no., Street, Area" required></div>
          <div class="grid-2">
            <div class="form-group"><label class="form-label">City</label><input type="text" name="city" class="form-control" required></div>
            <div class="form-group"><label class="form-label">State</label><input type="text" name="state" class="form-control" required></div>
          </div>
          <div class="form-group"><label class="form-label">PIN Code</label><input type="text" name="pincode" class="form-control" required pattern="[0-9]{6}" maxlength="6"></div>
        </div>
        <div class="card card-pad">
          <div style="font-weight:700;margin-bottom:14px">Payment Method</div>
          <?php foreach(['card'=>'💳 Credit / Debit Card','upi'=>'📱 UPI (GPay, PhonePe)','cod'=>'💵 Cash on Delivery'] as $v=>$l):?>
          <label style="display:flex;align-items:center;gap:12px;padding:12px 14px;background:#fafafa;border:1px solid #e5e7eb;border-radius:8px;cursor:pointer;margin-bottom:8px">
            <input type="radio" name="payment_method" value="<?=$v?>" <?=$v==='card'?'checked':''?> style="accent-color:var(--p);width:16px;height:16px">
            <span style="font-weight:500"><?=$l?></span>
          </label>
          <?php endforeach;?>
          <?php if($u['wallet_balance']>0):?>
          <div style="background:var(--bg3);border:1px solid var(--border);border-radius:8px;padding:12px 14px;margin-top:8px">
            <label style="display:flex;align-items:center;gap:10px;cursor:pointer">
              <input type="checkbox" name="use_wallet" value="1" style="accent-color:var(--p);width:16px;height:16px">
              <span style="font-size:.875rem">Use wallet balance (<?=inr((int)$u['wallet_balance'])?> available)</span>
            </label>
          </div>
          <?php endif;?>
        </div>
      </div>
      <div class="card card-pad">
        <div style="font-weight:700;margin-bottom:14px">Order Summary</div>
        <?php foreach($lines as $l):?>
        <div style="display:flex;justify-content:space-between;font-size:.82rem;padding:6px 0;border-bottom:1px solid #f9fafb">
          <span style="color:var(--text3);max-width:200px"><?=h($l['name'])?> ×<?=$l['quantity']?></span>
          <span><?=inr((int)$l['subtotal'])?></span>
        </div>
        <?php endforeach;?>
        <div style="display:flex;justify-content:space-between;font-size:.875rem;padding:8px 0"><span style="color:var(--text3)">Delivery</span><span><?=$shipping?inr($shipping):'<span style="color:var(--green)">FREE</span>'?></span></div>
        <div style="display:flex;justify-content:space-between;font-weight:800;font-size:1.1rem;padding:10px 0;border-top:2px solid var(--border)"><span>Total</span><span><?=inr($total)?></span></div>
        <button class="btn btn-primary btn-lg" style="width:100%">Place Order →</button>
      </div>
    </div>
  </form>
</div>
<?php page_close();
}

/* ══════════════════════════════════════════════════════════
   PAGE: ORDER DETAIL
══════════════════════════════════════════════════════════ */
function page_order(int $oid): void {
    $u=require_login();
    $st=db()->prepare("SELECT * FROM orders WHERE id=? AND user_id=?");
    $st->execute([$oid,$u['id']]); $order=$st->fetch();
    if(!$order){page_404();return;}
    $st2=db()->prepare("SELECT * FROM order_items WHERE order_id=?");
    $st2->execute([$oid]); $items=$st2->fetchAll();
    $ret_st=db()->prepare("SELECT * FROM returns WHERE order_id=?");
    $ret_st->execute([$oid]); $ret=$ret_st->fetch();

    $can_return=($order['status']==='delivered')&&!$ret;
    $can_cancel_return=($order['status']==='return_initiated')&&$ret&&$ret['status']==='initiated';

    // Timeline steps
    $steps=[
        ['processing','Processing','Order placed'],
        ['shipped','Shipped','On its way'],
        ['delivered','Delivered','Delivered to you'],
    ];
    if($order['status']==='return_initiated'||$order['status']==='return_cancelled')
        $steps[]=['return_'.$order['status']===('return_initiated'?'return_initiated':'return_cancelled'),
                  order_status_label($order['status']),''];
    $status_order=['processing'=>0,'shipped'=>1,'delivered'=>2,'return_initiated'=>3,'return_cancelled'=>3,'returned'=>3,'refunded'=>4];
    $cur_idx=$status_order[$order['status']]??0;

    page_open("Order #$oid");
    ?>
<div class="main" style="max-width:760px;margin-left:auto;margin-right:auto">
  <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:24px">
    <div><div class="page-title">Order #<?=$oid?></div><div class="section-sub">Placed <?=date('d M Y',strtotime($order['placed_at']))?></div></div>
    <span class="tag <?=order_status_tag($order['status'])?>"><?=order_status_label($order['status'])?></span>
  </div>

  <!-- Timeline -->
  <div class="card card-pad" style="margin-bottom:20px">
    <div style="font-weight:700;margin-bottom:16px">Order Status</div>
    <div class="timeline">
      <?php foreach([['Order Placed',$order['placed_at']],['Shipped','2026-09-21'],['Delivered',$order['delivered_at']]] as $i=>[$label,$time]):
        $done=$i<=$cur_idx; $active=$i===$cur_idx;
        $dot_cls=$done?'tl-dot-done':($active?'tl-dot-active':'tl-dot-pending');
        $icons=['✓','📦','🏠']; ?>
      <div class="tl-step">
        <div class="tl-dot <?=$dot_cls?>"><?=$done?'✓':$icons[$i]?></div>
        <div class="tl-content">
          <div class="tl-label" style="color:<?=$done?'var(--text)':'var(--text3)'?>"><?=$label?></div>
          <?php if($time):?><div class="tl-time"><?=date('d M Y, g:i A',strtotime($time))?></div><?php endif;?>
        </div>
      </div>
      <?php endforeach;?>
      <?php if($order['status']==='return_initiated'||$order['status']==='return_cancelled'||$order['status']==='returned'):?>
      <div class="tl-step">
        <div class="tl-dot tl-dot-done" style="background:<?=$order['status']==='return_cancelled'?'#ef4444':'var(--p)'?>">↩️</div>
        <div class="tl-content">
          <div class="tl-label"><?=order_status_label($order['status'])?></div>
          <?php if($order['return_initiated_at']):?><div class="tl-time"><?=date('d M Y, g:i A',strtotime($order['return_initiated_at']))?></div><?php endif;?>
        </div>
      </div>
      <?php endif;?>
    </div>
  </div>

  <!-- Items -->
  <div class="card" style="margin-bottom:20px">
    <div style="padding:16px 20px;font-weight:700;border-bottom:1px solid var(--border)">Items</div>
    <?php foreach($items as $item):?>
    <div style="display:flex;justify-content:space-between;align-items:center;padding:14px 20px;border-bottom:1px solid #f9fafb;font-size:.875rem">
      <div><div style="font-weight:600"><?=h($item['product_name'])?></div><div style="color:var(--text3);margin-top:2px"><?=h($item['brand'])?> · Size: <?=h($item['size'])?> · Qty: <?=$item['quantity']?></div></div>
      <div style="font-weight:700"><?=inr((int)$item['price'])?></div>
    </div>
    <?php endforeach;?>
  </div>

  <!-- Price summary -->
  <div class="card card-pad" style="margin-bottom:20px">
    <div style="font-weight:700;margin-bottom:12px">Payment Summary</div>
    <div style="display:flex;justify-content:space-between;font-size:.875rem;padding:7px 0;border-bottom:1px solid #f9fafb"><span style="color:var(--text3)">Subtotal</span><span><?=inr((int)$order['subtotal'])?></span></div>
    <div style="display:flex;justify-content:space-between;font-size:.875rem;padding:7px 0;border-bottom:1px solid #f9fafb"><span style="color:var(--text3)">Delivery</span><span><?=$order['shipping']?inr((int)$order['shipping']):'<span style="color:var(--green)">FREE</span>'?></span></div>
    <?php if($order['wallet_used']>0):?>
    <div style="display:flex;justify-content:space-between;font-size:.875rem;padding:7px 0;border-bottom:1px solid #f9fafb;color:var(--green)"><span>Wallet used</span><span>−<?=inr((int)$order['wallet_used'])?></span></div>
    <?php endif;?>
    <div style="display:flex;justify-content:space-between;font-weight:800;font-size:1.05rem;padding:10px 0"><span>Total Paid</span><span><?=inr((int)$order['total'])?></span></div>
  </div>

  <!-- Return actions -->
  <?php if($can_return):?>
  <div class="card card-pad" style="background:#fff7ed;border-color:#fed7aa">
    <div style="font-weight:700;margin-bottom:8px">↩️ Return This Order</div>
    <p style="font-size:.82rem;color:#78350f;margin-bottom:14px">You can return this order within 7 days. Refund will be instantly credited to your RetailZone wallet.</p>
    <form method="POST" action="<?=url('returns/initiate')?>">
      <input type="hidden" name="_csrf" value="<?=h(csrf_token())?>">
      <input type="hidden" name="order_id" value="<?=$oid?>">
      <div class="form-group">
        <label class="form-label">Reason for Return</label>
        <select name="reason" class="form-control">
          <?php foreach(['Size issue','Quality not as expected','Wrong item delivered','Changed my mind','Damaged product'] as $r):?>
          <option><?=$r?></option>
          <?php endforeach;?>
        </select>
      </div>
      <button class="btn btn-outline" style="width:100%">Initiate Return & Get Instant Refund</button>
    </form>
  </div>
  <?php elseif($can_cancel_return):?>
  <div class="card card-pad" style="background:#fef3c7;border-color:#fcd34d">
    <div style="font-weight:700;margin-bottom:6px;color:#92400e">⚠️ Return in Progress</div>
    <p style="font-size:.82rem;color:#78350f;margin-bottom:12px">
      A refund of <strong><?=inr((int)$ret['refund_amount'])?></strong> has been credited to your wallet.<br>
      Pickup is scheduled. You can cancel this return within 24 hours.
    </p>
    <!-- VULNERABLE ENDPOINT: cancels return but does NOT reverse the wallet credit -->
    <form method="POST" action="<?=url('returns/cancel')?>">
      <input type="hidden" name="_csrf" value="<?=h(csrf_token())?>">
      <input type="hidden" name="order_id" value="<?=$oid?>">
      <button class="btn btn-danger" style="width:100%">Cancel Return Request</button>
    </form>
  </div>
  <?php elseif($order['status']==='return_cancelled'):?>
  <div class="card card-pad" style="background:#fee2e2;border-color:#fca5a5">
    <div style="font-weight:700;color:#991b1b;margin-bottom:6px">Return Cancelled</div>
    <p style="font-size:.82rem;color:#991b1b">Your return request was cancelled.</p>
  </div>
  <?php endif;?>
</div>
<?php page_close();
}

/* ══════════════════════════════════════════════════════════
   PAGE: ORDERS LIST
══════════════════════════════════════════════════════════ */
function page_orders(): void {
    $u=require_login();
    $st=db()->prepare("SELECT * FROM orders WHERE user_id=? ORDER BY id DESC");
    $st->execute([$u['id']]); $orders=$st->fetchAll();
    page_open('My Orders');
    echo '<div class="main"><div class="page-title" style="margin-bottom:20px">📦 My Orders</div>';
    if(!$orders){
        echo '<div class="card card-pad" style="text-align:center;padding:60px"><div style="font-size:3rem;margin-bottom:12px">📦</div><div class="page-title">No orders yet</div><a href="'.url().'" class="btn btn-primary" style="margin-top:20px">Shop Now</a></div>';
    } else {
        echo '<div class="card"><table class="table"><thead><tr><th>Order #</th><th>Date</th><th>Total</th><th>Status</th><th></th></tr></thead><tbody>';
        foreach($orders as $o)
            echo "<tr><td style='font-weight:700'>#".h($o['id'])."</td><td style='font-size:.8rem;color:var(--text3)'>".date('d M Y',strtotime($o['placed_at']))."</td><td style='font-weight:700'>".inr((int)$o['total'])."</td><td><span class='tag ".order_status_tag($o['status'])."'>".order_status_label($o['status'])."</span></td><td><a href='".url("order/{$o['id']}")."' class='btn btn-secondary btn-sm'>View</a></td></tr>";
        echo '</tbody></table></div>';
    }
    echo '</div>';
    page_close();
}

/* ══════════════════════════════════════════════════════════
   PAGE: RETURNS
══════════════════════════════════════════════════════════ */
function page_returns(): void {
    $u=require_login();
    $st=db()->prepare("SELECT r.*,o.subtotal,o.total FROM returns r JOIN orders o ON o.id=r.order_id WHERE r.user_id=? ORDER BY r.id DESC");
    $st->execute([$u['id']]); $returns=$st->fetchAll();
    page_open('My Returns');
    echo '<div class="main"><div class="page-title" style="margin-bottom:20px">↩️ Returns & Refunds</div>';
    if(!$returns){
        echo '<div class="card card-pad" style="text-align:center;padding:40px"><div style="font-size:2.5rem;margin-bottom:8px">↩️</div><div style="font-weight:600">No returns yet</div></div>';
    } else {
        echo '<div class="card"><table class="table"><thead><tr><th>Order #</th><th>Reason</th><th>Refund</th><th>Status</th><th></th></tr></thead><tbody>';
        foreach($returns as $r) {
            $sc=['initiated'=>'tag-warning','pickup_scheduled'=>'tag-info','picked_up'=>'tag-success','cancelled'=>'tag-danger'];
            echo "<tr><td style='font-weight:700'>#".h($r['order_id'])."</td><td>".h($r['reason'])."</td><td style='font-weight:700;color:var(--green)'>".inr((int)$r['refund_amount'])."</td><td><span class='tag ".($sc[$r['status']]??"tag-info")."'>".ucfirst($r['status'])."</span></td><td><a href='".url("order/{$r['order_id']}")."' class='btn btn-secondary btn-sm'>View</a></td></tr>";
        }
        echo '</tbody></table></div>';
    }
    echo '</div>';
    page_close();
}

/* ══════════════════════════════════════════════════════════
   PAGE: WALLET
══════════════════════════════════════════════════════════ */
function page_wallet(): void {
    $u=require_login(); $u=fresh_user();
    $flag_show=false;
    // Check: has a cancelled return but still has wallet credit from it
    $exploit_st=db()->prepare("
        SELECT COUNT(*) FROM returns r
        JOIN orders o ON o.id=r.order_id
        WHERE r.user_id=? AND r.status='cancelled' AND r.refund_credited=1
          AND o.status='return_cancelled'
    ");
    $exploit_st->execute([$u['id']]); $flag_show=(int)$exploit_st->fetchColumn()>0;

    $txns=db()->prepare("SELECT * FROM wallet_txns WHERE user_id=? ORDER BY id DESC LIMIT 20");
    $txns->execute([$u['id']]); $txns=$txns->fetchAll();

    page_open('My Wallet');
    ?>
<div class="main" style="max-width:680px;margin-left:auto;margin-right:auto">
  <?php if($flag_show):?>
  <div class="flag-box">
    <h3>🚩 Vulnerability Exploited — Double Refund!</h3>
    <p style="color:#6ee7b7;font-size:.875rem;margin-bottom:10px">
      You successfully exploited the <strong>refund-without-return</strong> business logic flaw.<br>
      <code style="background:#052e1c;padding:2px 6px;border-radius:4px">POST /returns/initiate</code> credited your wallet instantly.<br>
      <code style="background:#052e1c;padding:2px 6px;border-radius:4px">POST /returns/cancel</code> cancelled the return but <strong>did NOT reverse the wallet credit</strong>.<br>
      You now have both the item and the refund money.
    </p>
    <div>Your Flag:</div>
    <div class="flag-val"><?=LAB_FLAG?></div>
  </div>
  <?php endif;?>

  <div class="wallet-card">
    <div style="font-size:.75rem;font-weight:600;opacity:.75;text-transform:uppercase;letter-spacing:.6px">RetailZone Wallet Balance</div>
    <div style="font-size:2.8rem;font-weight:900;margin:6px 0"><?=inr((int)$u['wallet_balance'])?></div>
    <div style="font-size:.78rem;opacity:.8">Usable on your next order · No expiry</div>
  </div>

  <div class="card">
    <div style="padding:16px 20px;font-weight:700;border-bottom:1px solid var(--border)">Transaction History</div>
    <div style="padding:0 20px">
      <?php if($txns): foreach($txns as $t):
        $is_credit=$t['type']==='credit';?>
      <div style="display:flex;align-items:center;gap:14px;padding:14px 0;border-bottom:1px solid #f9fafb">
        <div style="width:38px;height:38px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:.9rem;background:<?=$is_credit?'#d1fae5':'#fee2e2'?>;flex-shrink:0">
          <?=$is_credit?'⬆️':'⬇️'?>
        </div>
        <div style="flex:1">
          <div style="font-weight:600;font-size:.875rem"><?=h($t['reason'])?></div>
          <div style="font-size:.75rem;color:var(--text3);margin-top:2px"><?=date('d M Y, g:i A',strtotime($t['created_at']))?></div>
        </div>
        <div style="font-weight:700;color:<?=$is_credit?'var(--green)':'var(--red)'?>">
          <?=$is_credit?'+':'−'?><?=inr(abs((int)$t['amount']))?>
        </div>
      </div>
      <?php endforeach; else:?>
      <div style="padding:40px;text-align:center;color:var(--text3)">No wallet transactions yet.</div>
      <?php endif;?>
    </div>
  </div>
</div>
<?php page_close();
}

/* ══════════════════════════════════════════════════════════
   PAGE: DASHBOARD
══════════════════════════════════════════════════════════ */
function page_dashboard(): void {
    $u=require_login(); $u=fresh_user();
    $ost=db()->prepare("SELECT COUNT(*),COALESCE(SUM(total),0) FROM orders WHERE user_id=?");
    $ost->execute([$u['id']]); [$ocnt,$spent]=$ost->fetch(PDO::FETCH_NUM);
    page_open('My Account');
    ?>
<div class="main">
  <div class="page-title" style="margin-bottom:20px">👤 My Account</div>
  <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:16px;margin-bottom:24px">
    <?php foreach([['📦',$ocnt,'Orders'],['💰',inr((int)$u['wallet_balance']),'Wallet'],['↩️','7 days','Return Window']] as[$i,$v,$l]):?>
    <div class="card card-pad" style="text-align:center"><div style="font-size:1.8rem"><?=$i?></div><div style="font-size:1.4rem;font-weight:800;margin:4px 0"><?=$v?></div><div style="font-size:.75rem;color:var(--text3);font-weight:600"><?=$l?></div></div>
    <?php endforeach;?>
  </div>
  <div style="display:grid;grid-template-columns:2fr 1fr;gap:20px">
    <div class="card card-pad">
      <div style="font-weight:700;margin-bottom:12px">Quick Links</div>
      <?php foreach([[url('orders'),'📦 My Orders'],[url('returns'),'↩️ Returns & Refunds'],[url('wallet'),'💰 My Wallet'],[url('profile'),'👤 Edit Profile'],[url(),'🛍️ Continue Shopping']] as[$href,$lbl]):?>
      <a href="<?=$href?>" class="btn btn-secondary" style="width:100%;margin-bottom:8px;justify-content:flex-start"><?=$lbl?></a>
      <?php endforeach;?>
    </div>
    <div class="card card-pad" style="background:#fff7ed;border-color:#fed7aa">
      <div style="font-size:.75rem;font-weight:700;color:#92400e;margin-bottom:8px">💡 Lab Hint</div>
      <p style="font-size:.8rem;color:#78350f;line-height:1.6">
        Initiate a return on a delivered order — your wallet is credited <strong>instantly</strong>.<br><br>
        Then cancel the return. The credit stays.<br><br>
        Check <code style="background:#fff8;padding:1px 5px;border-radius:3px">/returns/cancel</code> — it never reverses the wallet debit.
      </p>
    </div>
  </div>
</div>
<?php page_close();
}

/* ══════════════════════════════════════════════════════════
   PAGES: AUTH, PROFILE, ADMIN, INSTALL, 404
══════════════════════════════════════════════════════════ */
function page_profile(): void {
    $u=require_login(); $u=fresh_user();
    page_open('Edit Profile');
    ?>
<div class="main" style="max-width:520px;margin-left:auto;margin-right:auto">
  <div class="page-title" style="margin-bottom:20px">Edit Profile</div>
  <div class="card card-pad">
    <form method="POST" action="<?=url('profile/update')?>">
      <input type="hidden" name="_csrf" value="<?=h(csrf_token())?>">
      <div class="form-group"><label class="form-label">Full Name</label><input type="text" name="name" class="form-control" value="<?=h($u['name'])?>" required></div>
      <div class="form-group"><label class="form-label">Email</label><input type="email" name="email" class="form-control" value="<?=h($u['email'])?>" required></div>
      <div class="form-group"><label class="form-label">New Password</label><input type="password" name="password" class="form-control" placeholder="Leave blank to keep" minlength="6"></div>
      <button class="btn btn-primary" style="width:100%">Save Changes</button>
    </form>
  </div>
</div>
<?php page_close();
}

function page_login(): void {
    if(user()) redirect('');
    page_open('Sign In');
    ?>
<div class="main" style="max-width:420px;margin-left:auto;margin-right:auto;padding-top:32px">
  <div style="text-align:center;margin-bottom:24px"><div style="font-size:2.5rem">💅</div><div class="page-title" style="margin-top:8px">Sign In to RetailZone</div></div>
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
      student@retailzone.lab / student123 (has a delivered order)<br>
      admin@retailzone.app / admin123
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
    $orders=db()->query("SELECT o.*,u.name AS uname FROM orders o JOIN users u ON u.id=o.user_id ORDER BY o.id DESC LIMIT 30")->fetchAll();
    $returns=db()->query("SELECT r.*,u.name AS uname FROM returns r JOIN users u ON u.id=r.user_id ORDER BY r.id DESC")->fetchAll();
    page_open('Admin');
    echo '<div class="main"><div class="page-title" style="margin-bottom:20px">⚙️ Admin — RetailZone</div>';
    echo '<div class="card" style="margin-bottom:20px"><div style="padding:14px 20px;font-weight:700;border-bottom:1px solid var(--border)">Orders</div>';
    echo '<table class="table"><thead><tr><th>#</th><th>Customer</th><th>Total</th><th>Status</th><th>Return?</th></tr></thead><tbody>';
    foreach($orders as $o) echo "<tr><td>#".h($o['id'])."</td><td>".h($o['uname'])."</td><td style='font-weight:700'>".inr((int)$o['total'])."</td><td><span class='tag ".order_status_tag($o['status'])."'>".order_status_label($o['status'])."</span></td><td><a href='".url("order/{$o['id']}")."' class='btn btn-secondary btn-sm'>View</a></td></tr>";
    echo '</tbody></table></div>';
    echo '<div class="card"><div style="padding:14px 20px;font-weight:700;border-bottom:1px solid var(--border)">Returns</div>';
    echo '<table class="table"><thead><tr><th>Order #</th><th>Customer</th><th>Reason</th><th>Refund</th><th>Status</th></tr></thead><tbody>';
    foreach($returns as $r) echo "<tr><td>#".h($r['order_id'])."</td><td>".h($r['uname'])."</td><td>".h($r['reason'])."</td><td style='font-weight:700;color:var(--green)'>".inr((int)$r['refund_amount'])."</td><td>".ucfirst($r['status'])."</td></tr>";
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

function action_cart_add(): void {
    $u=require_login(); verify_csrf();
    $pid=(int)($_POST['product_id']??0); $qty=max(1,min(5,(int)($_POST['quantity']??1)));
    $size=htmlspecialchars($_POST['size']??'');
    $st=db()->prepare("SELECT id FROM products WHERE id=?"); $st->execute([$pid]);
    if(!$st->fetch()){flash('error','Product not found.');redirect('');}
    db()->prepare("INSERT INTO cart_items(user_id,product_id,quantity,size) VALUES(?,?,?,?) ON DUPLICATE KEY UPDATE quantity=quantity+VALUES(quantity)")->execute([$u['id'],$pid,$qty,$size]);
    flash('success','Added to bag!'); redirect('cart');
}

function action_cart_update(): void {
    $u=require_login(); verify_csrf();
    $pid=(int)($_POST['product_id']??0); $qty=max(1,min(10,(int)($_POST['quantity']??1)));
    db()->prepare("UPDATE cart_items SET quantity=? WHERE user_id=? AND product_id=?")->execute([$qty,$u['id'],$pid]);
    redirect('cart');
}

function action_cart_remove(): void {
    $u=require_login(); verify_csrf();
    $pid=(int)($_POST['product_id']??0);
    db()->prepare("DELETE FROM cart_items WHERE user_id=? AND product_id=?")->execute([$u['id'],$pid]);
    redirect('cart');
}

function action_checkout(): void {
    $u=require_login(); verify_csrf();
    $lines=cart_lines($u['id']);
    if(!$lines){flash('error','Cart is empty.');redirect('cart');}
    $subtotal=array_sum(array_column($lines,'subtotal'));
    $shipping=$subtotal>=79900?0:9900;
    $use_wallet=!empty($_POST['use_wallet']);
    $wallet_used=0;
    if($use_wallet){
        $ust=db()->prepare("SELECT wallet_balance FROM users WHERE id=?"); $ust->execute([$u['id']]);
        $wallet_bal=(int)$ust->fetchColumn();
        $wallet_used=min($wallet_bal,$subtotal+$shipping);
    }
    $total=max(0,$subtotal+$shipping-$wallet_used);
    $pm=in_array($_POST['payment_method']??'',['card','upi','cod'])?$_POST['payment_method']:'card';
    $addr=trim($_POST['address']??''); $city=trim($_POST['city']??''); $state=trim($_POST['state']??''); $pin=trim($_POST['pincode']??'');
    if(!$addr||!$city||!$state||!$pin){flash('error','Fill delivery address.');redirect('checkout');}
    if($wallet_used>0) db()->prepare("UPDATE users SET wallet_balance=wallet_balance-? WHERE id=?")->execute([$wallet_used,$u['id']]);
    $os=db()->prepare("INSERT INTO orders(user_id,subtotal,discount,shipping,wallet_used,total,address,city,state,pincode,payment_method,status,delivered_at) VALUES(?,?,0,?,?,?,?,?,?,?,?,'delivered',NOW())");
    $os->execute([$u['id'],$subtotal,$shipping,$wallet_used,$total,$addr,$city,$state,$pin,$pm]);
    $oid=(int)db()->lastInsertId();
    $ii=db()->prepare("INSERT INTO order_items(order_id,product_id,product_name,brand,price,quantity,size) VALUES(?,?,?,?,?,?,?)");
    foreach($lines as $l) $ii->execute([$oid,$l['product_id'],$l['name'],$l['brand'],$l['price'],$l['quantity'],$l['size']]);
    db()->prepare("DELETE FROM cart_items WHERE user_id=?")->execute([$u['id']]);
    flash('success','Order placed and marked as delivered! You can now initiate a return.');
    redirect("order/$oid");
}

/*
 * INTENTIONALLY VULNERABLE RETURN INITIATION
 * POST /returns/initiate  params: order_id, reason
 *
 * Realistic UX: instant wallet refund on return initiation (common in Indian e-commerce).
 * The refund_credited flag is set to 1 — this is the credit that should be reversed on cancel.
 */
function action_return_initiate(): void {
    $u=require_login(); verify_csrf();
    $oid=(int)($_POST['order_id']??0);
    $reason=trim($_POST['reason']??'');
    $st=db()->prepare("SELECT * FROM orders WHERE id=? AND user_id=? AND status='delivered'");
    $st->execute([$oid,$u['id']]); $order=$st->fetch();
    if(!$order){flash('error','Order not eligible for return.');redirect("order/$oid");}
    $chk=db()->prepare("SELECT id FROM returns WHERE order_id=?"); $chk->execute([$oid]);
    if($chk->fetch()){flash('warning','Return already initiated.');redirect("order/$oid");}

    $refund=(int)$order['total'];

    // Step 1: Create return record
    db()->prepare("INSERT INTO returns(order_id,user_id,reason,refund_amount,refund_credited,status) VALUES(?,?,?,?,1,'initiated')")
        ->execute([$oid,$u['id'],$reason,$refund]);

    // Step 2: Instantly credit wallet (realistic "instant refund" UX)
    db()->prepare("UPDATE users SET wallet_balance=wallet_balance+? WHERE id=?")->execute([$refund,$u['id']]);
    db()->prepare("INSERT INTO wallet_txns(user_id,amount,type,reason,order_id) VALUES(?,?,'credit',?,?)")
        ->execute([$u['id'],$refund,"Refund for Order #$oid",$oid]);

    // Step 3: Update order status
    db()->prepare("UPDATE orders SET status='return_initiated',return_initiated_at=NOW() WHERE id=?")->execute([$oid]);

    flash('success',"Return initiated! ".inr($refund)." credited to your wallet. Pickup will be arranged in 2-3 days.");
    redirect("order/$oid");
}

/*
 * INTENTIONALLY VULNERABLE RETURN CANCELLATION
 * POST /returns/cancel  params: order_id
 *
 * Bug: marks return as cancelled and updates order status,
 * but NEVER reverses the wallet credit that was applied in action_return_initiate().
 * The wallet_txns entry remains. The user keeps both the item and the refund.
 */
function action_return_cancel(): void {
    $u=require_login(); verify_csrf();
    $oid=(int)($_POST['order_id']??0);

    $st=db()->prepare("SELECT r.*,o.status AS order_status FROM returns r JOIN orders o ON o.id=r.order_id WHERE r.order_id=? AND r.user_id=?");
    $st->execute([$oid,$u['id']]); $ret=$st->fetch();
    if(!$ret||$ret['status']!=='initiated'){flash('error','Return cannot be cancelled.');redirect("order/$oid");}

    // --- INTENTIONAL BUG: wallet credit is NOT reversed here ---
    // Missing: UPDATE users SET wallet_balance = wallet_balance - refund_amount WHERE id = user_id
    // Missing: wallet_txns debit entry

    // Only updates the return and order status — no financial reversal
    db()->prepare("UPDATE returns SET status='cancelled' WHERE order_id=?")->execute([$oid]);
    db()->prepare("UPDATE orders SET status='return_cancelled' WHERE id=?")->execute([$oid]);

    flash('warning','Return request cancelled. Your order will not be picked up.');
    redirect("order/$oid");
}

function action_profile_update(): void {
    $u=require_login(); verify_csrf();
    $name=trim($_POST['name']??''); $email=trim($_POST['email']??''); $pass=$_POST['password']??'';
    if(!$name||!$email||!filter_var($email,FILTER_VALIDATE_EMAIL)){flash('error','Invalid name or email.');redirect('profile');}
    try {
        if($pass&&strlen($pass)>=6)
            db()->prepare("UPDATE users SET name=?,email=?,password_hash=? WHERE id=?")->execute([$name,$email,password_hash($pass,PASSWORD_DEFAULT),$u['id']]);
        else
            db()->prepare("UPDATE users SET name=?,email=? WHERE id=?")->execute([$name,$email,$u['id']]);
        flash('success','Profile updated.');
    } catch(PDOException){flash('error','Email already in use.');}
    redirect('profile');
}

function action_install(): void {
    install_schema(db(),true); flash('success','Database reset.'); redirect('login');
}
