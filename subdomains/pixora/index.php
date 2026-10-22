<?php
require_once __DIR__.'/lib/bootstrap.php';
require_once __DIR__.'/lib/layout.php';

$req=$_SERVER['REQUEST_URI'];
$rel='/'.ltrim(substr(parse_url($req,PHP_URL_PATH),strlen(BASE)),'/');
if($rel!=='/'&&str_ends_with($rel,'/')) $rel=rtrim($rel,'/');
$method=$_SERVER['REQUEST_METHOD'];

match(true){
    $rel==='/login'          &&$method==='POST' => action_login(),
    $rel==='/register'       &&$method==='POST' => action_register(),
    $rel==='/logout'         &&$method==='POST' => action_logout(),
    $rel==='/profile/avatar' &&$method==='POST' => action_update_avatar(),
    $rel==='/profile/bio'    &&$method==='POST' => action_update_bio(),
    $rel==='/install'        &&$method==='POST' => action_install(),

    $rel==='/'                                              => page_home(),
    $rel==='/explore'                                       => page_explore(),
    (bool)preg_match('#^/profile/(\d+)$#',$rel,$m)         => page_profile((int)$m[1]),
    $rel==='/settings'                                      => page_settings(),
    $rel==='/login'                                         => page_login(),
    $rel==='/register'                                      => page_register(),
    $rel==='/install'                                       => page_install(),
    default                                                 => page_404(),
};

/* ══════════════════════════════════════════════════════════
   PAGE: HOME / FEED
══════════════════════════════════════════════════════════ */
function page_home(): void {
    $u=require_login(); $u=fresh_user();
    $posts=db()->query("SELECT p.*,u.name,u.username,u.avatar FROM posts p JOIN users u ON u.id=p.user_id ORDER BY p.id DESC")->fetchAll();

    page_open('Home');
    ?>
<div class="wrap">
  <div class="feed-layout">
    <!-- FEED -->
    <div>
      <div style="font-weight:700;font-size:1rem;margin-bottom:18px;color:var(--text3);text-transform:uppercase;letter-spacing:.6px">✦ Your Feed</div>
      <?php foreach($posts as $p) render_post($p);?>
    </div>

    <!-- SIDEBAR -->
    <div style="display:flex;flex-direction:column;gap:16px">
      <!-- My Profile Card -->
      <div class="card card-pad">
        <div style="display:flex;align-items:center;gap:14px;margin-bottom:14px">
          <div class="avatar avatar-lg"><?=avatar_html($u['avatar'])?></div>
          <div>
            <div style="font-weight:800;font-size:1rem"><?=h($u['name'])?></div>
            <div style="font-size:.8rem;color:var(--text3)">@<?=h($u['username'])?></div>
            <div style="font-size:.75rem;color:var(--text3);margin-top:4px"><?=h($u['bio'])?></div>
          </div>
        </div>
        <div style="display:flex;gap:0;border-top:1px solid var(--border);padding-top:12px">
          <?php foreach([[$u['followers'],'Followers'],[$u['following'],'Following']] as[$n,$l]):?>
          <div style="flex:1;text-align:center"><div style="font-weight:800"><?=number_format($n)?></div><div style="font-size:.7rem;color:var(--text3)"><?=$l?></div></div>
          <?php endforeach;?>
        </div>
        <div style="display:flex;gap:8px;margin-top:14px">
          <a href="<?=url('profile/'.$u['id'])?>" class="btn btn-outline btn-sm" style="flex:1">View Profile</a>
          <a href="<?=url('settings')?>" class="btn btn-light btn-sm" style="flex:1">⚙️ Settings</a>
        </div>
      </div>

      <!-- Who to Follow -->
      <div class="card">
        <div style="padding:14px 16px;font-weight:700;font-size:.875rem;border-bottom:1px solid var(--border)">People to Follow</div>
        <?php
        $others=db()->prepare("SELECT * FROM users WHERE id!=? ORDER BY followers DESC LIMIT 4");
        $others->execute([$u['id']]); foreach($others->fetchAll() as $ou):?>
        <div class="user-row">
          <a href="<?=url("profile/{$ou['id']}")?>"><div class="avatar avatar-sm"><?=avatar_html($ou['avatar'])?></div></a>
          <div style="flex:1">
            <div style="font-weight:600;font-size:.84rem"><?=h($ou['name'])?></div>
            <div style="font-size:.72rem;color:var(--text3)">@<?=h($ou['username'])?> · <?=number_format($ou['followers'])?> followers</div>
          </div>
          <button class="btn btn-outline btn-sm">Follow</button>
        </div>
        <?php endforeach;?>
      </div>
    </div>
  </div>
</div>
<?php page_close();
}

/* ══════════════════════════════════════════════════════════
   PAGE: PROFILE
══════════════════════════════════════════════════════════ */
function page_profile(int $uid): void {
    $u=require_login();
    $target=fresh_user($uid);
    if(!$target){page_404();return;}

    $posts=db()->prepare("SELECT p.*,u.name,u.username,u.avatar FROM posts p JOIN users u ON u.id=p.user_id WHERE p.user_id=? ORDER BY p.id DESC");
    $posts->execute([$uid]); $posts=$posts->fetchAll();
    $is_own=$u['id']===$uid;

    page_open('@'.h($target['username']));
    ?>
<div class="wrap" style="max-width:760px;margin-left:auto;margin-right:auto">
  <div class="profile-header">
    <div class="profile-cover"></div>
    <div class="profile-info">
      <div style="display:flex;align-items:flex-end;justify-content:space-between">
        <div class="profile-avatar-wrap">
          <div class="avatar avatar-xl" style="border-color:#fff;border-width:4px;box-shadow:0 0 0 2px var(--p)"><?=avatar_html($target['avatar'])?></div>
        </div>
        <div style="padding-bottom:8px;display:flex;gap:8px">
          <?php if($is_own):?>
          <a href="<?=url('settings')?>" class="btn btn-light btn-sm">✏️ Edit Profile</a>
          <?php else:?>
          <button class="btn btn-primary btn-sm">Follow</button>
          <button class="btn btn-light btn-sm">Message</button>
          <?php endif;?>
        </div>
      </div>
      <div style="font-size:1.15rem;font-weight:800"><?=h($target['name'])?></div>
      <div style="font-size:.85rem;color:var(--text3)">@<?=h($target['username'])?></div>
      <?php if($target['bio']):?>
      <div style="font-size:.875rem;margin-top:8px;color:var(--text2);line-height:1.5"><?=h($target['bio'])?></div>
      <?php endif;?>
      <div class="profile-stats">
        <div class="stat-item"><div class="stat-num"><?=count($posts)?></div><div class="stat-lbl">Posts</div></div>
        <div class="stat-item"><div class="stat-num"><?=number_format($target['followers'])?></div><div class="stat-lbl">Followers</div></div>
        <div class="stat-item"><div class="stat-num"><?=number_format($target['following'])?></div><div class="stat-lbl">Following</div></div>
      </div>
    </div>
  </div>

  <div style="margin-top:24px">
    <?php if($posts): foreach($posts as $p) render_post($p);
    else: ?>
    <div class="card card-pad" style="text-align:center;padding:48px">
      <div style="font-size:2.5rem;margin-bottom:8px">📸</div>
      <div style="font-weight:600">No posts yet</div>
    </div>
    <?php endif;?>
  </div>
</div>
<?php page_close();
}

/* ══════════════════════════════════════════════════════════
   PAGE: EXPLORE
══════════════════════════════════════════════════════════ */
function page_explore(): void {
    $u=require_login();
    $users=db()->query("SELECT * FROM users ORDER BY followers DESC")->fetchAll();
    page_open('Explore');
    ?>
<div class="wrap" style="max-width:680px;margin-left:auto;margin-right:auto">
  <div class="page-title" style="margin-bottom:20px">🔍 Explore Creators</div>
  <div class="card">
    <?php foreach($users as $ou):?>
    <div class="user-row" style="padding:16px 20px">
      <a href="<?=url("profile/{$ou['id']}")?>">
        <div class="avatar avatar-md"><?=avatar_html($ou['avatar'])?></div>
      </a>
      <div style="flex:1">
        <div style="display:flex;align-items:center;gap:8px">
          <div style="font-weight:700"><?=h($ou['name'])?></div>
          <?php if($ou['is_admin']):?><span class="tag tag-purple">✦ Official</span><?php endif;?>
        </div>
        <div style="font-size:.78rem;color:var(--text3)">@<?=h($ou['username'])?> · <?=number_format($ou['followers'])?> followers</div>
        <?php if($ou['bio']):?><div style="font-size:.8rem;color:var(--text2);margin-top:4px"><?=h($ou['bio'])?></div><?php endif;?>
      </div>
      <a href="<?=url("profile/{$ou['id']}")?>" class="btn btn-outline btn-sm">View</a>
    </div>
    <?php endforeach;?>
  </div>
</div>
<?php page_close();
}

/* ══════════════════════════════════════════════════════════
   PAGE: SETTINGS (avatar + bio edit)
══════════════════════════════════════════════════════════ */
function page_settings(): void {
    $u=require_login(); $u=fresh_user();
    page_open('Settings');
    ?>
<div class="wrap" style="max-width:600px;margin-left:auto;margin-right:auto">
  <div class="page-title" style="margin-bottom:6px">⚙️ Account Settings</div>
  <div style="font-size:.84rem;color:var(--text3);margin-bottom:24px">Manage your profile and account preferences</div>

  <!-- AVATAR SECTION -->
  <div class="card card-pad" style="margin-bottom:20px">
    <div style="font-weight:700;margin-bottom:6px">Profile Picture</div>
    <div style="font-size:.8rem;color:var(--text3);margin-bottom:16px">Choose an avatar that represents you on Pixora</div>

    <div style="display:flex;align-items:center;gap:18px;margin-bottom:20px">
      <div class="avatar avatar-lg" id="preview-avatar"><?=avatar_html($u['avatar'])?></div>
      <div>
        <div style="font-size:.875rem;font-weight:600">Current avatar</div>
        <div style="font-size:.78rem;color:var(--text3);margin-top:2px">Upload your own photo, or pick a default below</div>
      </div>
    </div>

    <form method="POST" action="<?=url('profile/avatar')?>" id="avatar-form" enctype="multipart/form-data">
      <input type="hidden" name="_csrf" value="<?=h(csrf_token())?>">
      <input type="hidden" name="user_id" id="avatar-uid" value="<?=$u['id']?>">
      <input type="hidden" name="avatar" id="avatar-val" value="<?=h($u['avatar'])?>">

      <label class="avatar-upload" for="avatar-file">
        <span class="au-ic">⬆️</span>
        <span><strong>Upload a photo</strong><small>PNG, JPG, GIF or WEBP · up to 2 MB</small></span>
        <input type="file" id="avatar-file" name="photo" accept="image/png,image/jpeg,image/gif,image/webp" onchange="previewUpload(this)" hidden>
      </label>

      <div style="display:flex;align-items:center;gap:10px;margin:14px 0 10px;color:var(--text3);font-size:.78rem">
        <span style="flex:1;height:1px;background:var(--border)"></span>or choose a default<span style="flex:1;height:1px;background:var(--border)"></span>
      </div>

      <div class="avatar-grid" id="avatar-grid">
        <?php foreach(AVATARS as $av):
          $sel=$av===$u['avatar'];?>
        <div class="avatar-opt <?=$sel?'selected':''?>" data-avatar="<?=h($av)?>" onclick="selectAvatar('<?=h($av)?>')">
          <?=$av?>
        </div>
        <?php endforeach;?>
      </div>

      <button type="submit" class="btn btn-primary" style="width:100%;margin-top:16px">Save Profile Picture</button>
    </form>
  </div>

  <!-- BIO SECTION -->
  <div class="card card-pad" style="margin-bottom:20px">
    <div style="font-weight:700;margin-bottom:14px">Profile Info</div>
    <form method="POST" action="<?=url('profile/bio')?>">
      <input type="hidden" name="_csrf" value="<?=h(csrf_token())?>">
      <div class="form-group">
        <label class="form-label">Bio</label>
        <textarea name="bio" class="form-control" rows="3" maxlength="255" placeholder="Tell people about yourself..."><?=h($u['bio'])?></textarea>
      </div>
      <button class="btn btn-primary" style="width:100%">Update Bio</button>
    </form>
  </div>

  <!-- ACCOUNT INFO (read-only) -->
  <div class="card card-pad">
    <div style="font-weight:700;margin-bottom:14px">Account Info</div>
    <div style="display:flex;flex-direction:column;gap:12px;font-size:.875rem">
      <div style="display:flex;justify-content:space-between;padding-bottom:10px;border-bottom:1px solid var(--border)">
        <span style="color:var(--text3)">Display Name</span><span style="font-weight:600"><?=h($u['name'])?></span>
      </div>
      <div style="display:flex;justify-content:space-between;padding-bottom:10px;border-bottom:1px solid var(--border)">
        <span style="color:var(--text3)">Username</span><span style="font-weight:600">@<?=h($u['username'])?></span>
      </div>
      <div style="display:flex;justify-content:space-between;padding-bottom:10px;border-bottom:1px solid var(--border)">
        <span style="color:var(--text3)">Email</span><span style="font-weight:600"><?=h($u['email'])?></span>
      </div>
      <div style="display:flex;justify-content:space-between">
        <span style="color:var(--text3)">User ID</span>
        <span style="font-weight:600;font-family:monospace;background:var(--bg3);padding:2px 8px;border-radius:5px"><?=$u['id']?></span>
      </div>
    </div>
  </div>
</div>
<script>
function selectAvatar(av){
  document.getElementById('avatar-val').value=av;
  var p=document.getElementById('preview-avatar');
  p.textContent=av; // emoji preview
  var f=document.getElementById('avatar-file'); if(f) f.value=''; // clear any chosen file
  document.querySelectorAll('.avatar-opt').forEach(function(el){
    el.classList.toggle('selected', el.dataset.avatar===av);
  });
}
function previewUpload(input){
  var file=input.files&&input.files[0]; if(!file) return;
  if(file.size>2*1024*1024){ alert('Image is larger than 2 MB. Please choose a smaller file.'); input.value=''; return; }
  var reader=new FileReader();
  reader.onload=function(e){
    var p=document.getElementById('preview-avatar');
    p.textContent='';
    p.innerHTML='<img src="'+e.target.result+'" alt="avatar">';
    document.querySelectorAll('.avatar-opt').forEach(function(el){el.classList.remove('selected');});
  };
  reader.readAsDataURL(file);
}
</script>
<?php page_close();
}

/* ══════════════════════════════════════════════════════════
   AUTH PAGES
══════════════════════════════════════════════════════════ */
function page_login(): void {
    if(user()) redirect('');
    page_open('Sign In');
    ?>
<div class="wrap" style="max-width:400px;margin-left:auto;margin-right:auto;padding-top:32px">
  <div style="text-align:center;margin-bottom:28px">
    <div style="font-size:2rem;font-weight:900;background:linear-gradient(135deg,#a78bfa,#f472b6);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text">✦ Pixora</div>
    <div style="font-size:.9rem;color:var(--text3);margin-top:6px">Sign in to your account</div>
  </div>
  <div class="card card-pad">
    <form method="POST" action="<?=url('login')?>">
      <input type="hidden" name="_csrf" value="<?=h(csrf_token())?>">
      <div class="form-group"><label class="form-label">Email</label><input type="email" name="email" class="form-control" required autofocus></div>
      <div class="form-group"><label class="form-label">Password</label><input type="password" name="password" class="form-control" required></div>
      <button class="btn btn-primary btn-lg" style="width:100%">Sign In →</button>
    </form>
    <div style="text-align:center;margin-top:14px;font-size:.84rem;color:var(--text3)">New to Pixora? <a href="<?=url('register')?>">Create account</a></div>
    <div style="margin-top:14px;padding:12px;background:var(--bg3);border-radius:8px;font-size:.75rem;color:var(--text3)">
      <strong style="color:var(--p)">Demo accounts:</strong><br>
      student@pixora.lab / student123<br>
      alice@pixora.app / alice123<br>
      admin@pixora.app / admin123
    </div>
  </div>
</div>
<?php page_close();
}

function page_register(): void {
    if(user()) redirect('');
    page_open('Create Account');
    ?>
<div class="wrap" style="max-width:400px;margin-left:auto;margin-right:auto;padding-top:32px">
  <div style="text-align:center;margin-bottom:24px"><div style="font-size:1.8rem;font-weight:900;background:linear-gradient(135deg,#a78bfa,#f472b6);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text">✦ Pixora</div></div>
  <div class="card card-pad">
    <form method="POST" action="<?=url('register')?>">
      <input type="hidden" name="_csrf" value="<?=h(csrf_token())?>">
      <div class="form-group"><label class="form-label">Full Name</label><input type="text" name="name" class="form-control" required></div>
      <div class="form-group"><label class="form-label">Username</label><input type="text" name="username" class="form-control" required pattern="[a-z0-9_]+" placeholder="lowercase letters, numbers, _"></div>
      <div class="form-group"><label class="form-label">Email</label><input type="email" name="email" class="form-control" required></div>
      <div class="form-group"><label class="form-label">Password</label><input type="password" name="password" class="form-control" required minlength="6"></div>
      <button class="btn btn-primary btn-lg" style="width:100%">Join Pixora →</button>
    </form>
    <div style="text-align:center;margin-top:14px;font-size:.84rem;color:var(--text3)">Already have an account? <a href="<?=url('login')?>">Sign in</a></div>
  </div>
</div>
<?php page_close();
}

function page_install(): void {
    page_open('Reset Lab');
    ?>
<div class="wrap" style="max-width:420px;margin-left:auto;margin-right:auto">
  <div class="card card-pad" style="text-align:center">
    <div style="font-size:2rem;margin-bottom:10px">⚗️</div>
    <div style="font-size:1.1rem;font-weight:700;margin-bottom:8px">Reset Lab Database</div>
    <p style="color:var(--text3);margin:0 0 20px;font-size:.875rem">Drops all tables and re-seeds with fresh data.</p>
    <form method="POST"><button class="btn btn-danger btn-lg" style="width:100%">Reset Database</button></form>
    <a href="<?=url('login')?>" class="btn btn-light" style="width:100%;margin-top:10px">Cancel</a>
  </div>
</div>
<?php page_close();
}

function page_404(): void {
    http_response_code(404); page_open('Not Found');
    echo '<div class="wrap" style="text-align:center;padding:80px"><div style="font-size:2.5rem;margin-bottom:10px">🔍</div><div style="font-size:1.2rem;font-weight:700">Page Not Found</div><a href="'.url().'" class="btn btn-primary" style="margin-top:20px;display:inline-flex">Go Home</a></div>';
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

function action_register(): void {
    verify_csrf();
    $name=trim($_POST['name']??''); $uname=strtolower(trim($_POST['username']??''));
    $email=trim($_POST['email']??''); $pass=$_POST['password']??'';
    if(!$name||!$uname||!$email||!$pass||!filter_var($email,FILTER_VALIDATE_EMAIL)||!preg_match('/^[a-z0-9_]+$/',$uname)){
        flash('error','Fill all fields correctly.'); redirect('register');
    }
    try {
        db()->prepare("INSERT INTO users(name,username,email,password_hash) VALUES(?,?,?,?)")
            ->execute([$name,$uname,$email,password_hash($pass,PASSWORD_DEFAULT)]);
        session_regenerate_id(true); $_SESSION['uid']=(int)db()->lastInsertId();
        flash('success','Welcome to Pixora!'); redirect('');
    } catch(PDOException){flash('error','Username or email already taken.');redirect('register');}
}

function action_logout(): void {
    verify_csrf(); session_destroy(); header('Location:'.url('login')); exit;
}

/* POST /profile/avatar — params: user_id, avatar/photo, _csrf */
function action_update_avatar(): void {
    $u=require_login(); verify_csrf();

    $target_uid=(int)($_POST['user_id']??0);
    $avatar=$_POST['avatar']??'';

    // A user may upload their own photo, or pick one of the default emoji avatars.
    if(!empty($_FILES['photo']['name']) && (int)($_FILES['photo']['error']??UPLOAD_ERR_NO_FILE)!==UPLOAD_ERR_NO_FILE){
        $avatar=save_avatar_upload($_FILES['photo']);
        if($avatar===''){flash('error','Please upload a valid image (PNG, JPG, GIF or WEBP, up to 2 MB).');redirect('settings');}
    } elseif(!in_array($avatar,AVATARS,true)){
        flash('error','Invalid avatar selection.');redirect('settings');
    }

    $st=db()->prepare("SELECT id,name FROM users WHERE id=?"); $st->execute([$target_uid]); $target=$st->fetch();
    if(!$target){flash('error','User not found.');redirect('settings');}

    db()->prepare("UPDATE users SET avatar=?, avatar_changed_by=? WHERE id=?")
        ->execute([$avatar,$u['id'],$target_uid]);

    flash('success','Profile picture updated!');
    redirect('settings');
}

function action_update_bio(): void {
    $u=require_login(); verify_csrf();
    $bio=trim($_POST['bio']??'');
    if(strlen($bio)>255){flash('error','Bio too long (max 255 characters).');redirect('settings');}
    db()->prepare("UPDATE users SET bio=? WHERE id=?")->execute([$bio,$u['id']]);
    flash('success','Bio updated!'); redirect('settings');
}

function action_install(): void {
    install_schema(db(),true); flash('success','Database reset.'); redirect('login');
}
