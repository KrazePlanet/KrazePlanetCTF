<?php
/* ╔══════════════════════════════════════════════════════════════════════╗
   ║  Lab 61 — Stored XSS via Rich Text Editor HTML Tab                  ║
   ║  Platform: Quill (blog CMS) — based on Shopify HackerOne #1147433   ║
   ║  Vulnerability: article body stored raw from RTE HTML tab           ║
   ║  Rendered via innerHTML in article feed — XSS fires on page load    ║
   ╚══════════════════════════════════════════════════════════════════════╝ */
ob_start();
$db_host='localhost';$db_name='KrazePlanet';$db_user='root';$db_pass='';
try{
    $pdo=new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8",$db_user,$db_pass,[
        PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC
    ]);
}catch(PDOException $e){die(json_encode(['ok'=>false,'message'=>'DB error']));}

// ── Tables ──
$pdo->exec("CREATE TABLE IF NOT EXISTS lab61_users (
    id INT AUTO_INCREMENT PRIMARY KEY,name VARCHAR(100) NOT NULL,
    email VARCHAR(150) UNIQUE NOT NULL,password VARCHAR(255) NOT NULL,
    role ENUM('user','admin') DEFAULT 'user',created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");
$pdo->exec("CREATE TABLE IF NOT EXISTS lab61_articles (
    id INT AUTO_INCREMENT PRIMARY KEY,author_email VARCHAR(150) NOT NULL,
    author_name VARCHAR(100) NOT NULL,title VARCHAR(300) NOT NULL,
    body TEXT NOT NULL DEFAULT '',tags VARCHAR(255) DEFAULT '',
    status ENUM('published','draft') DEFAULT 'published',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

// ── Seed ──
if(!$pdo->query("SELECT COUNT(*) FROM lab61_users WHERE email='liam@quill.io'")->fetchColumn()){
    $iu=$pdo->prepare("INSERT IGNORE INTO lab61_users(name,email,password)VALUES(?,?,?)");
    $iu->execute(['Liam Foster','liam@quill.io',password_hash('liam@123',PASSWORD_DEFAULT)]);
    $iu->execute(['Priya Nair','priya@quill.io',password_hash('priya@123',PASSWORD_DEFAULT)]);

    $ia=$pdo->prepare("INSERT INTO lab61_articles(author_email,author_name,title,body,tags,created_at)VALUES(?,?,?,?,?,?)");
    $ia->execute(['liam@quill.io','Liam Foster','Getting Started with Web Development',
        '<p>Web development can seem overwhelming at first. Breaking it into three pillars — <strong>HTML</strong>, <strong>CSS</strong>, and <strong>JavaScript</strong> — makes the journey manageable. Start with structure, then style, then interactivity.</p>',
        'webdev,beginner',date('Y-m-d H:i:s',strtotime('-8 days'))]);
    $ia->execute(['liam@quill.io','Liam Foster','10 CSS Tips Every Developer Should Know',
        '<p>From mastering the <em>box model</em> to leveraging CSS custom properties, these tips will level up your front-end skills. Modern CSS is incredibly powerful — you would be surprised what you can achieve without a single line of JavaScript.</p>',
        'css,frontend',date('Y-m-d H:i:s',strtotime('-5 days'))]);
    $ia->execute(['liam@quill.io','Liam Foster','Understanding REST APIs',
        '<p>REST APIs are the backbone of modern web applications. They allow systems to communicate using standard HTTP methods — GET, POST, PUT, and DELETE. Understanding them is essential for any full-stack developer.</p>',
        'api,backend',date('Y-m-d H:i:s',strtotime('-2 days'))]);
}

// ── Helpers ──
function sendJ($d){while(ob_get_level())ob_end_clean();header('Content-Type: application/json');echo json_encode($d);exit;}
function reqBody(){return json_decode(file_get_contents('php://input'),true)??[];}

// ── Router ──
$act=$_GET['action']??'';
if($act&&$_SERVER['REQUEST_METHOD']==='POST'){
    switch($act){
        case 'register':
            try{
                $b=reqBody();$nm=trim($b['name']??'');$em=trim($b['email']??'');$pw=$b['password']??'';
                if(!$nm||!$em||!$pw)sendJ(['ok'=>false,'message'=>'All fields required.']);
                if(strlen($pw)<6)sendJ(['ok'=>false,'message'=>'Password must be at least 6 characters.']);
                $c=$pdo->prepare("SELECT id FROM lab61_users WHERE email=?");$c->execute([$em]);
                if($c->fetch())sendJ(['ok'=>false,'message'=>'Email already registered.']);
                $pdo->prepare("INSERT INTO lab61_users(name,email,password)VALUES(?,?,?)")->execute([$nm,$em,password_hash($pw,PASSWORD_DEFAULT)]);
                sendJ(['ok'=>true,'name'=>$nm,'email'=>$em,'role'=>'user']);
            }catch(Exception $e){sendJ(['ok'=>false,'message'=>'Server error.']);}
            break;
        case 'login':
            try{
                $b=reqBody();$em=trim($b['email']??'');$pw=$b['password']??'';
                if(!$em||!$pw)sendJ(['ok'=>false,'message'=>'Email and password required.']);
                $s=$pdo->prepare("SELECT name,password,role FROM lab61_users WHERE email=?");$s->execute([$em]);
                $r=$s->fetch();
                if($r&&password_verify($pw,$r['password']))sendJ(['ok'=>true,'name'=>$r['name'],'email'=>$em,'role'=>$r['role']]);
                else sendJ(['ok'=>false,'message'=>'Invalid email or password.']);
            }catch(Exception $e){sendJ(['ok'=>false,'message'=>'Server error.']);}
            break;
        case 'get_articles':
            try{
                $b=reqBody();$em=trim($b['email']??'');
                if(!$em)sendJ(['ok'=>false,'articles'=>[]]);
                $s=$pdo->prepare("SELECT * FROM lab61_articles WHERE author_email=? ORDER BY created_at DESC");
                $s->execute([$em]);
                sendJ(['ok'=>true,'articles'=>$s->fetchAll()]);
            }catch(Exception $e){sendJ(['ok'=>false,'articles'=>[]]);}
            break;
        case 'create_article':
            try{
                $b=reqBody();$em=trim($b['email']??'');$an=trim($b['author']??'');
                $title=trim($b['title']??'');
                $artBody=$b['body']??''; // VULNERABLE: stored raw — no htmlspecialchars()
                $tags=trim($b['tags']??'');$st=($b['status']??'published')==='draft'?'draft':'published';
                if(!$em||!$title)sendJ(['ok'=>false,'message'=>'Title is required.']);
                $pdo->prepare("INSERT INTO lab61_articles(author_email,author_name,title,body,tags,status)VALUES(?,?,?,?,?,?)")->execute([$em,$an,$title,$artBody,$tags,$st]);
                sendJ(['ok'=>true,'message'=>'Article published.']);
            }catch(Exception $e){sendJ(['ok'=>false,'message'=>'Server error.']);}
            break;
        case 'delete_article':
            try{
                $b=reqBody();$id=(int)($b['id']??0);$em=trim($b['email']??'');
                if(!$id||!$em)sendJ(['ok'=>false,'message'=>'Invalid request.']);
                $pdo->prepare("DELETE FROM lab61_articles WHERE id=? AND author_email=?")->execute([$id,$em]);
                sendJ(['ok'=>true]);
            }catch(Exception $e){sendJ(['ok'=>false,'message'=>'Server error.']);}
            break;
        case 'update_profile':
            try{
                $b=reqBody();$em=trim($b['email']??'');$nm=trim($b['name']??'');
                if(!$em||!$nm)sendJ(['ok'=>false,'message'=>'Missing fields.']);
                $pdo->prepare("UPDATE lab61_users SET name=? WHERE email=?")->execute([$nm,$em]);
                sendJ(['ok'=>true,'name'=>$nm]);
            }catch(Exception $e){sendJ(['ok'=>false,'message'=>'Server error.']);}
            break;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Quill — Your Writing Space</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.9.1/font/bootstrap-icons.css">
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0;}
:root{
    --q-bg:#f8fafc; --q-white:#fff; --q-indigo:#6366f1; --q-indigo-d:#4f46e5;
    --q-indigo-light:#eef2ff; --q-indigo-text:#4338ca; --q-border:#e2e8f0;
    --q-text:#0f172a; --q-muted:#64748b; --q-red:#ef4444; --q-red-light:#fef2f2;
    --q-green:#16a34a; --q-green-light:#f0fdf4; --q-nav-h:60px;
}
body{background:var(--q-bg);color:var(--q-text);font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;font-size:15px;line-height:1.6;}

/* ── AUTH ── */
.auth-page{min-height:100vh;background:linear-gradient(135deg,#f8fafc 0%,#eef2ff 100%);display:flex;flex-direction:column;align-items:center;justify-content:center;padding:2rem 1rem;}
.auth-logo{display:flex;align-items:center;gap:.55rem;margin-bottom:1.75rem;}
.auth-logo-icon{width:44px;height:44px;background:var(--q-indigo);border-radius:12px;display:flex;align-items:center;justify-content:center;color:#fff;font-size:1.2rem;}
.auth-logo-name{font-size:1.65rem;font-weight:800;color:var(--q-text);letter-spacing:-.03em;}
.auth-card{background:var(--q-white);border:1px solid var(--q-border);border-radius:16px;padding:2rem 2.25rem;width:100%;max-width:400px;box-shadow:0 4px 24px rgba(0,0,0,.07);}
.auth-card h2{font-size:1.05rem;font-weight:700;text-align:center;margin-bottom:1.5rem;}
.form-group{margin-bottom:1.1rem;}
.form-label{display:block;font-size:.78rem;font-weight:600;color:var(--q-text);margin-bottom:.3rem;}
.form-control{width:100%;border:1.5px solid var(--q-border);border-radius:8px;background:var(--q-white);color:var(--q-text);padding:.6rem .9rem;font-size:.88rem;font-family:inherit;outline:none;transition:border-color .15s,box-shadow .15s;}
.form-control:focus{border-color:var(--q-indigo);box-shadow:0 0 0 3px rgba(99,102,241,.12);}
select.form-control{cursor:pointer;}
.btn-primary{width:100%;background:var(--q-indigo);color:#fff;border:none;border-radius:8px;padding:.7rem;font-size:.9rem;font-weight:700;font-family:inherit;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:.4rem;transition:background .2s;margin-top:.25rem;}
.btn-primary:hover{background:var(--q-indigo-d);}
.btn-primary:disabled{opacity:.5;cursor:default;}
.auth-err{background:var(--q-red-light);border:1px solid #fecaca;color:var(--q-red);border-radius:8px;padding:.55rem .9rem;font-size:.8rem;margin-top:.75rem;display:none;}
.auth-switch{text-align:center;font-size:.8rem;color:var(--q-muted);margin-top:1rem;}
.auth-switch a{color:var(--q-indigo);text-decoration:none;font-weight:600;}
.accounts-hint{background:var(--q-white);border:1px solid var(--q-border);border-radius:10px;padding:1rem 1.25rem;margin-top:1.25rem;width:100%;max-width:400px;font-size:.78rem;}
.accounts-hint h4{font-size:.65rem;font-weight:700;color:var(--q-muted);text-transform:uppercase;letter-spacing:.07em;margin-bottom:.6rem;}
.acc-row{display:flex;gap:.75rem;align-items:center;padding:.25rem 0;border-bottom:1px solid var(--q-border);}
.acc-row:last-child{border-bottom:none;}
.acc-email{flex:1;color:var(--q-muted);}
.acc-pass{font-weight:700;color:var(--q-text);}

/* ── NAVBAR ── */
#dashView{display:none;}
.q-nav{position:fixed;top:0;left:0;right:0;height:var(--q-nav-h);background:var(--q-white);border-bottom:1px solid var(--q-border);z-index:100;display:flex;align-items:center;justify-content:space-between;padding:0 2rem;}
.q-brand{display:flex;align-items:center;gap:.5rem;cursor:pointer;}
.q-brand-icon{width:32px;height:32px;background:var(--q-indigo);border-radius:8px;display:flex;align-items:center;justify-content:center;color:#fff;font-size:.95rem;}
.q-brand-name{font-size:1.15rem;font-weight:800;color:var(--q-text);letter-spacing:-.02em;}
.q-nav-links{display:flex;gap:.2rem;}
.q-nav-link{padding:.4rem .85rem;border-radius:8px;font-size:.82rem;font-weight:500;color:var(--q-muted);text-decoration:none;cursor:pointer;transition:all .12s;display:inline-flex;align-items:center;gap:.35rem;}
.q-nav-link:hover{background:var(--q-bg);color:var(--q-text);}
.q-nav-link.active{background:var(--q-indigo-light);color:var(--q-indigo-text);font-weight:600;}
.q-nav-right{display:flex;align-items:center;gap:.75rem;}
.q-avatar{width:30px;height:30px;background:var(--q-indigo);border-radius:50%;display:flex;align-items:center;justify-content:center;color:#fff;font-size:.78rem;font-weight:700;flex-shrink:0;}
.q-username{font-size:.82rem;font-weight:600;color:var(--q-text);}
.btn-logout{background:transparent;border:1.5px solid var(--q-border);border-radius:8px;padding:.32rem .75rem;font-size:.78rem;font-weight:600;color:var(--q-muted);cursor:pointer;transition:all .15s;}
.btn-logout:hover{border-color:var(--q-red);color:var(--q-red);background:var(--q-red-light);}

/* ── LAYOUT ── */
.q-main{margin-top:var(--q-nav-h);max-width:740px;margin-left:auto;margin-right:auto;padding:2.5rem 1.5rem;min-height:calc(100vh - var(--q-nav-h));}
.page-hdr{display:flex;align-items:center;justify-content:space-between;margin-bottom:2rem;}
.page-hdr h1{font-size:1.35rem;font-weight:800;letter-spacing:-.02em;}
.btn-write{background:var(--q-indigo);color:#fff;border:none;border-radius:8px;padding:.55rem 1.1rem;font-size:.84rem;font-weight:700;font-family:inherit;cursor:pointer;display:inline-flex;align-items:center;gap:.4rem;transition:background .15s;}
.btn-write:hover{background:var(--q-indigo-d);}

/* ── ARTICLE CARDS ── */
.article-feed{display:flex;flex-direction:column;gap:1.1rem;}
.article-card{background:var(--q-white);border:1px solid var(--q-border);border-radius:14px;padding:1.5rem 1.75rem;transition:box-shadow .15s,border-color .15s;}
.article-card:hover{box-shadow:0 4px 20px rgba(0,0,0,.06);border-color:#cbd5e1;}
.card-hdr{display:flex;align-items:center;justify-content:space-between;margin-bottom:.85rem;}
.article-date{font-size:.73rem;color:var(--q-muted);}
.card-hdr-right{display:flex;align-items:center;gap:.5rem;}
.status-badge{display:inline-block;font-size:.6rem;font-weight:700;padding:.15rem .5rem;border-radius:4px;text-transform:uppercase;letter-spacing:.05em;}
.status-published{background:var(--q-green-light);color:var(--q-green);}
.status-draft{background:#f1f5f9;color:var(--q-muted);border:1px solid var(--q-border);}
.btn-del{background:none;border:1px solid var(--q-border);border-radius:6px;padding:.2rem .45rem;color:var(--q-muted);cursor:pointer;font-size:.8rem;transition:all .12s;}
.btn-del:hover{background:var(--q-red-light);border-color:#fca5a5;color:var(--q-red);}
.article-title{font-size:1.05rem;font-weight:700;color:var(--q-text);margin-bottom:.55rem;line-height:1.4;letter-spacing:-.01em;}
.article-preview{font-size:.86rem;color:var(--q-muted);line-height:1.65;margin-bottom:.85rem;overflow:hidden;display:-webkit-box;-webkit-line-clamp:3;-webkit-box-orient:vertical;}
.article-tags{display:flex;flex-wrap:wrap;gap:.35rem;}
.tag{font-size:.7rem;font-weight:600;padding:.18rem .55rem;border-radius:99px;background:var(--q-indigo-light);color:var(--q-indigo-text);}
.feed-empty{text-align:center;padding:4rem 1rem;color:var(--q-muted);}
.feed-empty i{font-size:2.5rem;display:block;margin-bottom:.75rem;color:#cbd5e1;}
.feed-empty p{font-size:.88rem;margin-bottom:1.25rem;}

/* ── EDITOR ── */
.editor-back{margin-bottom:1.5rem;}
.btn-back{background:none;border:none;color:var(--q-muted);cursor:pointer;font-size:.82rem;font-family:inherit;display:inline-flex;align-items:center;gap:.35rem;padding:.3rem 0;transition:color .12s;}
.btn-back:hover{color:var(--q-text);}
.editor-card{background:var(--q-white);border:1px solid var(--q-border);border-radius:14px;padding:2rem 2.25rem;}
.editor-card .form-group{margin-bottom:1.25rem;}
/* RTE */
.rte-outer{border:1.5px solid var(--q-border);border-radius:10px;overflow:hidden;transition:border-color .15s,box-shadow .15s;}
.rte-outer:focus-within{border-color:var(--q-indigo);box-shadow:0 0 0 3px rgba(99,102,241,.1);}
.rte-tabs-row{display:flex;align-items:center;justify-content:space-between;background:#f8fafc;border-bottom:1px solid var(--q-border);padding:.4rem .75rem;}
.rte-tab-btns{display:flex;gap:.25rem;}
.rte-tab{background:none;border:1.5px solid transparent;border-radius:6px;padding:.25rem .7rem;font-size:.78rem;font-weight:600;color:var(--q-muted);cursor:pointer;font-family:inherit;transition:all .12s;display:inline-flex;align-items:center;gap:.3rem;}
.rte-tab:hover{background:var(--q-white);color:var(--q-text);}
.rte-tab.active{background:var(--q-white);border-color:var(--q-border);color:var(--q-indigo-text);}
.rte-toolbar{display:flex;gap:.2rem;}
.rte-fmt{background:none;border:1px solid transparent;border-radius:4px;padding:.15rem .4rem;font-size:.8rem;cursor:pointer;color:var(--q-muted);transition:all .12s;}
.rte-fmt:hover{background:var(--q-white);border-color:var(--q-border);color:var(--q-text);}
.rte-visual{min-height:160px;padding:.9rem 1rem;outline:none;font-size:.9rem;line-height:1.7;color:var(--q-text);}
.rte-source{display:none;width:100%;min-height:160px;border:none;outline:none;resize:vertical;font-family:'Courier New',monospace;font-size:.82rem;padding:.9rem 1rem;color:#be185d;background:#fff0f8;line-height:1.6;}
.rte-hint{font-size:.72rem;color:var(--q-muted);margin-top:.3rem;}
.form-row-2{display:grid;grid-template-columns:1fr auto;gap:.75rem;align-items:end;}
.editor-actions{display:flex;align-items:center;justify-content:flex-end;gap:.65rem;margin-top:1.25rem;}
.btn-outline{background:var(--q-white);border:1.5px solid var(--q-border);border-radius:8px;padding:.55rem 1rem;font-size:.84rem;font-weight:600;color:var(--q-text);cursor:pointer;font-family:inherit;transition:all .15s;}
.btn-outline:hover{border-color:#94a3b8;background:var(--q-bg);}
.editor-err{color:var(--q-red);font-size:.78rem;display:none;margin-top:.5rem;text-align:right;}

/* ── PROFILE ── */
.profile-card{background:var(--q-white);border:1px solid var(--q-border);border-radius:14px;padding:2rem 2.25rem;max-width:480px;}
.profile-card h2{font-size:1rem;font-weight:700;margin-bottom:1.25rem;}
.profile-av-row{display:flex;align-items:center;gap:1rem;margin-bottom:1.5rem;padding-bottom:1.25rem;border-bottom:1px solid var(--q-border);}
.profile-av-big{width:60px;height:60px;background:var(--q-indigo);border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:1.5rem;font-weight:700;color:#fff;flex-shrink:0;}
.profile-av-info h3{font-size:.9rem;font-weight:700;}
.profile-av-info p{font-size:.78rem;color:var(--q-muted);}
.input-ro{background:var(--q-bg);border:1.5px solid var(--q-border);border-radius:8px;padding:.6rem .9rem;font-size:.88rem;color:var(--q-muted);cursor:not-allowed;}
.btn-save{background:var(--q-indigo);color:#fff;border:none;border-radius:8px;padding:.6rem 1.25rem;font-size:.85rem;font-weight:700;font-family:inherit;cursor:pointer;display:inline-flex;align-items:center;gap:.4rem;transition:background .15s;margin-top:.25rem;}
.btn-save:hover{background:var(--q-indigo-d);}

/* ── TOAST ── */
.toast{position:fixed;bottom:1.5rem;right:1.5rem;z-index:2000;background:#1e293b;color:#fff;border-radius:10px;padding:.8rem 1.2rem;font-size:.82rem;display:flex;align-items:center;gap:.6rem;box-shadow:0 8px 30px rgba(0,0,0,.2);opacity:0;transform:translateY(8px);transition:all .25s;pointer-events:none;}
.toast.show{opacity:1;transform:translateY(0);}
.toast.success{background:#065f46;}
.toast.error{background:#991b1b;}
</style>
</head>
<body>

<!-- ════ LOGIN ════ -->
<div class="auth-page" id="loginPage">
    <div class="auth-logo">
        <div class="auth-logo-icon"><i class="bi bi-feather"></i></div>
        <span class="auth-logo-name">Quill</span>
    </div>
    <div class="auth-card">
        <h2>Sign in to your account</h2>
        <form onsubmit="submitLogin(event)">
            <div class="form-group">
                <label class="form-label">Email</label>
                <input type="email" id="liEmail" class="form-control" placeholder="you@email.com" autocomplete="off">
            </div>
            <div class="form-group">
                <label class="form-label">Password</label>
                <input type="password" id="liPass" class="form-control" placeholder="Password">
            </div>
            <button type="submit" class="btn-primary" id="liBtn"><i class="bi bi-arrow-right-circle"></i> Sign in</button>
            <div class="auth-err" id="liErr"></div>
        </form>
        <div class="auth-switch">New to Quill? <a href="#" onclick="showRegister();return false;">Create account</a></div>
    </div>
    <div class="accounts-hint">
        <h4><i class="bi bi-person-badge"></i> Test credentials</h4>
        <div class="acc-row"><span class="acc-email">liam@quill.io</span><span class="acc-pass">liam@123</span></div>
        <div class="acc-row"><span class="acc-email">priya@quill.io</span><span class="acc-pass">priya@123</span></div>
    </div>
</div>

<!-- ════ REGISTER ════ -->
<div class="auth-page" id="registerPage" style="display:none;">
    <div class="auth-logo">
        <div class="auth-logo-icon"><i class="bi bi-feather"></i></div>
        <span class="auth-logo-name">Quill</span>
    </div>
    <div class="auth-card">
        <h2>Create your account</h2>
        <form onsubmit="submitRegister(event)">
            <div class="form-group"><label class="form-label">Full Name</label><input type="text" id="rgName" class="form-control" placeholder="Your name"></div>
            <div class="form-group"><label class="form-label">Email</label><input type="email" id="rgEmail" class="form-control" placeholder="you@email.com" autocomplete="off"></div>
            <div class="form-group"><label class="form-label">Password</label><input type="password" id="rgPass" class="form-control" placeholder="Min. 6 characters"></div>
            <button type="submit" class="btn-primary" id="rgBtn"><i class="bi bi-person-plus"></i> Create account</button>
            <div class="auth-err" id="rgErr"></div>
        </form>
        <div class="auth-switch">Already registered? <a href="#" onclick="showLogin();return false;">Sign in</a></div>
    </div>
</div>

<!-- ════ DASHBOARD ════ -->
<div id="dashView">
    <nav class="q-nav">
        <div class="q-brand" onclick="showSection('articles')">
            <div class="q-brand-icon"><i class="bi bi-feather"></i></div>
            <span class="q-brand-name">Quill</span>
        </div>
        <div class="q-nav-links">
            <a class="q-nav-link active" id="nav-articles" onclick="showSection('articles');return false;" href="#"><i class="bi bi-journals"></i> My Articles</a>
            <a class="q-nav-link"        id="nav-write"    onclick="showSection('write');return false;"    href="#"><i class="bi bi-pencil-square"></i> Write</a>
            <a class="q-nav-link"        id="nav-profile"  onclick="showSection('profile');return false;"  href="#"><i class="bi bi-person-circle"></i> Profile</a>
        </div>
        <div class="q-nav-right">
            <div style="display:flex;align-items:center;gap:.45rem;">
                <div class="q-avatar" id="navAvatar">L</div>
                <span class="q-username" id="navUsername">User</span>
            </div>
            <button class="btn-logout" onclick="logout()"><i class="bi bi-box-arrow-right"></i> Logout</button>
        </div>
    </nav>

    <main class="q-main">

        <!-- ── MY ARTICLES ── -->
        <div id="sec-articles">
            <div class="page-hdr">
                <h1>My Articles</h1>
                <button class="btn-write" onclick="showSection('write')"><i class="bi bi-plus-lg"></i> Write article</button>
            </div>
            <div class="article-feed" id="articleFeed">
                <div class="feed-empty"><i class="bi bi-hourglass-split"></i><p>Loading your articles…</p></div>
            </div>
        </div>

        <!-- ── WRITE ── -->
        <div id="sec-write" style="display:none;">
            <div class="editor-back">
                <button class="btn-back" onclick="showSection('articles')"><i class="bi bi-arrow-left"></i> Back to My Articles</button>
            </div>
            <div class="editor-card">
                <div class="form-group">
                    <label class="form-label">Title <span style="color:var(--q-red)">*</span></label>
                    <input type="text" id="artTitle" class="form-control" placeholder="Give your article a compelling title…">
                </div>
                <div class="form-group">
                    <label class="form-label">Tags <span style="color:var(--q-muted);font-weight:400;font-size:.73rem;">(comma-separated)</span></label>
                    <input type="text" id="artTags" class="form-control" placeholder="e.g. webdev, css, javascript">
                </div>
                <div class="form-group">
                    <label class="form-label">Body</label>
                    <div class="rte-outer">
                        <div class="rte-tabs-row">
                            <div class="rte-tab-btns">
                                <button type="button" class="rte-tab active" id="tab-write" onclick="switchTab('write')"><i class="bi bi-pen"></i> Write</button>
                                <button type="button" class="rte-tab"        id="tab-html"  onclick="switchTab('html')"><i class="bi bi-code-slash"></i> HTML</button>
                            </div>
                            <div class="rte-toolbar" id="rteToolbar">
                                <button type="button" class="rte-fmt" onclick="rteExec('bold')"              title="Bold"><strong>B</strong></button>
                                <button type="button" class="rte-fmt" onclick="rteExec('italic')"            title="Italic"><em>I</em></button>
                                <button type="button" class="rte-fmt" onclick="rteExec('underline')"         title="Underline"><u>U</u></button>
                                <button type="button" class="rte-fmt" onclick="rteExec('insertOrderedList')" title="Ordered list"><i class="bi bi-list-ol"></i></button>
                            </div>
                        </div>
                        <div contenteditable="true" class="rte-visual" id="rteVisual"></div>
                        <textarea class="rte-source" id="rteSource"
                            placeholder="<!-- Paste your raw HTML here -->&#10;Payload: &quot;&gt;]&lt;img src=x onerror=alert(document.domain)&gt;"></textarea>
                    </div>
                    <div class="rte-hint">Click the <strong>HTML</strong> tab to paste raw HTML directly — stored without sanitisation and rendered via <code>innerHTML</code> on your articles page.</div>
                </div>
                <div style="display:flex;align-items:center;gap:.75rem;">
                    <label class="form-label" style="margin:0;white-space:nowrap;">Status:</label>
                    <select id="artStatus" class="form-control" style="max-width:145px;">
                        <option value="published">Published</option>
                        <option value="draft">Draft</option>
                    </select>
                </div>
                <div class="editor-actions">
                    <button class="btn-outline" onclick="showSection('articles')">Cancel</button>
                    <button class="btn-write"   onclick="publishArticle()"><i class="bi bi-send"></i> Publish</button>
                </div>
                <div class="editor-err" id="artErr"></div>
            </div>
        </div>

        <!-- ── PROFILE ── -->
        <div id="sec-profile" style="display:none;">
            <div class="page-hdr"><h1>Profile</h1></div>
            <div class="profile-card">
                <h2>Account settings</h2>
                <div class="profile-av-row">
                    <div class="profile-av-big" id="profAvBig">L</div>
                    <div class="profile-av-info">
                        <h3 id="profDispName">Liam Foster</h3>
                        <p id="profDispEmail">liam@quill.io</p>
                    </div>
                </div>
                <form onsubmit="saveProfile(event)">
                    <div class="form-group"><label class="form-label">Display Name</label><input type="text" id="profName" class="form-control"></div>
                    <div class="form-group"><label class="form-label">Email</label><div class="input-ro" id="profEmail">—</div></div>
                    <button type="submit" class="btn-save" id="btnSave"><i class="bi bi-check2"></i> Save changes</button>
                </form>
            </div>
        </div>

    </main>
</div>

<div class="toast" id="toast"><i id="toastIcon" class="bi bi-check-circle-fill"></i><span id="toastMsg"></span></div>

<script>
let _email='', _name='', _role='user', _tabMode='write', _toastT=null;

// ── Toast ──
function toast(msg,type){
    const el=document.getElementById('toast'),ic=document.getElementById('toastIcon');
    el.className='toast '+(type||'success');
    ic.className=type==='error'?'bi bi-x-circle-fill':'bi bi-check-circle-fill';
    document.getElementById('toastMsg').textContent=msg;
    el.classList.add('show'); clearTimeout(_toastT);
    _toastT=setTimeout(()=>el.classList.remove('show'),3500);
}
function safe(s){return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');}

// ── Session ──
function saveSession(n,e,r){localStorage.setItem('lab61_session',JSON.stringify({name:n,email:e,role:r||'user'}));}
function logout(){localStorage.removeItem('lab61_session');location.reload();}

// ── Auth views ──
function showLogin(){
    document.getElementById('registerPage').style.display='none';
    document.getElementById('loginPage').style.display='flex';
}
function showRegister(){
    document.getElementById('loginPage').style.display='none';
    document.getElementById('registerPage').style.display='flex';
}
function showDash(name,email,role){
    _name=name;_email=email;_role=role||'user';
    ['loginPage','registerPage'].forEach(id=>document.getElementById(id).style.display='none');
    document.getElementById('dashView').style.display='block';
    document.getElementById('navUsername').textContent=name;
    document.getElementById('navAvatar').textContent=name.charAt(0).toUpperCase();
    document.getElementById('profName').value=name;
    document.getElementById('profEmail').textContent=email;
    document.getElementById('profDispName').textContent=name;
    document.getElementById('profDispEmail').textContent=email;
    document.getElementById('profAvBig').textContent=name.charAt(0).toUpperCase();
    showSection('articles');
}

// ── Section routing ──
function showSection(sec){
    ['articles','write','profile'].forEach(s=>{
        document.getElementById('sec-'+s).style.display=s===sec?'block':'none';
        const n=document.getElementById('nav-'+s);
        if(n)n.className='q-nav-link'+(s===sec?' active':'');
    });
    if(sec==='articles')loadArticles();
    if(sec==='write')resetEditor();
}

// ── RTE: Write / HTML tabs ──
// KEY FIX: HTML tab always starts EMPTY — no copy from visual div.
// This prevents contamination from whatever the user typed in Write mode.
// (In the previous version, switchTab copied visual.innerHTML → textarea,
//  so if the user typed in Write mode first, that content got prepended to
//  the payload, breaking execution. Now HTML tab is always a clean slate.)
function switchTab(tab){
    const visual=document.getElementById('rteVisual');
    const source=document.getElementById('rteSource');
    const toolbar=document.getElementById('rteToolbar');
    _tabMode=tab;
    if(tab==='html'){
        // HTML tab: start fresh — DO NOT copy from visual
        source.value='';
        visual.style.display='none'; source.style.display='block';
        toolbar.style.display='none';
        document.getElementById('tab-write').classList.remove('active');
        document.getElementById('tab-html').classList.add('active');
        source.focus();
    } else {
        // Write tab: sync visual from HTML source if source has content
        if(source.value.trim()) visual.innerHTML=source.value;
        visual.style.display='block'; source.style.display='none';
        toolbar.style.display='flex';
        document.getElementById('tab-write').classList.add('active');
        document.getElementById('tab-html').classList.remove('active');
    }
}
function rteExec(cmd){document.getElementById('rteVisual').focus();document.execCommand(cmd,false,null);}
function getBody(){
    return _tabMode==='html'
        ? document.getElementById('rteSource').value
        : document.getElementById('rteVisual').innerHTML;
}
function resetEditor(){
    _tabMode='write';
    document.getElementById('artTitle').value='';
    document.getElementById('artTags').value='';
    document.getElementById('rteVisual').innerHTML='';
    document.getElementById('rteSource').value='';
    document.getElementById('rteVisual').style.display='block';
    document.getElementById('rteSource').style.display='none';
    document.getElementById('rteToolbar').style.display='flex';
    document.getElementById('tab-write').classList.add('active');
    document.getElementById('tab-html').classList.remove('active');
    document.getElementById('artErr').style.display='none';
}

// ── Load articles — VULNERABLE ──────────────────────────────────────────
// a.body is inserted directly via innerHTML (not escaped).
// PHP saves the raw value from the RTE HTML tab without htmlspecialchars().
// Payload in the HTML tab → stored as-is → fires on every page load.
// Real-world reference: Shopify HackerOne #1147433 ($5,300 bounty).
// ────────────────────────────────────────────────────────────────────────
async function loadArticles(){
    const feed=document.getElementById('articleFeed');
    feed.innerHTML='<div class="feed-empty"><i class="bi bi-hourglass-split"></i><p>Loading your articles…</p></div>';
    try{
        const d=await(await fetch('?action=get_articles',{
            method:'POST',headers:{'Content-Type':'application/json'},
            body:JSON.stringify({email:_email})
        })).json();
        if(!d.ok||!d.articles.length){
            feed.innerHTML=`<div class="feed-empty">
                <i class="bi bi-feather"></i>
                <p>No articles yet — write your first one!</p>
                <button class="btn-write" onclick="showSection('write')"><i class="bi bi-pencil-square"></i> Write now</button>
            </div>`;
            return;
        }
        feed.innerHTML='';
        d.articles.forEach(a=>{
            const card=document.createElement('div');
            card.className='article-card';
            const dt=new Date(a.created_at).toLocaleDateString('en-US',{year:'numeric',month:'long',day:'numeric'});
            const tags=a.tags?a.tags.split(',').filter(t=>t.trim()).map(t=>`<span class="tag">${safe(t.trim())}</span>`).join(''):'';
            // ⚠ VULNERABLE: a.body rendered via innerHTML — not escaped
            // Payload typed in the HTML tab is stored raw by PHP and
            // executed here when the articles page is loaded.
            card.innerHTML=`
                <div class="card-hdr">
                    <span class="article-date">${dt}</span>
                    <div class="card-hdr-right">
                        <span class="status-badge status-${safe(a.status)}">${safe(a.status)}</span>
                        <button class="btn-del" onclick="deleteArticle(${parseInt(a.id)},this)" title="Delete">
                            <i class="bi bi-trash3"></i>
                        </button>
                    </div>
                </div>
                <h2 class="article-title">${safe(a.title)}</h2>
                <div class="article-preview">${a.body}</div>
                <div class="article-tags">${tags}</div>
            `;
            feed.appendChild(card);
        });
    }catch(e){
        feed.innerHTML='<div class="feed-empty"><i class="bi bi-exclamation-circle"></i><p>Failed to load articles.</p></div>';
    }
}

// ── Publish article ──
async function publishArticle(){
    const title=document.getElementById('artTitle').value.trim();
    const body=getBody();
    const err=document.getElementById('artErr');
    if(!title){err.textContent='Title is required.';err.style.display='block';return;}
    err.style.display='none';
    try{
        const d=await(await fetch('?action=create_article',{
            method:'POST',headers:{'Content-Type':'application/json'},
            body:JSON.stringify({email:_email,author:_name,title,body,
                tags:document.getElementById('artTags').value.trim(),
                status:document.getElementById('artStatus').value})
        })).json();
        if(d.ok){
            showSection('articles');
            toast('Article published — XSS payload fires when your articles page loads.');
        } else{err.textContent=d.message||'Failed to publish.';err.style.display='block';}
    }catch(e){err.textContent='Network error.';err.style.display='block';}
}

// ── Delete article ──
async function deleteArticle(id,btn){
    if(btn){btn.disabled=true;btn.innerHTML='<i class="bi bi-hourglass-split"></i>';}
    try{
        const d=await(await fetch('?action=delete_article',{
            method:'POST',headers:{'Content-Type':'application/json'},
            body:JSON.stringify({id,email:_email})
        })).json();
        if(d.ok){loadArticles();toast('Article deleted.');}
        else{toast('Failed to delete.','error');if(btn){btn.disabled=false;btn.innerHTML='<i class="bi bi-trash3"></i>';}}
    }catch(e){toast('Network error.','error');if(btn){btn.disabled=false;btn.innerHTML='<i class="bi bi-trash3"></i>';}}
}

// ── Profile ──
async function saveProfile(e){
    e.preventDefault();
    const name=document.getElementById('profName').value.trim();
    if(!name){toast('Name cannot be empty.','error');return;}
    const btn=document.getElementById('btnSave');
    btn.disabled=true;btn.innerHTML='<i class="bi bi-hourglass-split"></i> Saving…';
    try{
        const d=await(await fetch('?action=update_profile',{
            method:'POST',headers:{'Content-Type':'application/json'},
            body:JSON.stringify({email:_email,name})
        })).json();
        if(d.ok){
            _name=d.name;
            document.getElementById('navUsername').textContent=d.name;
            document.getElementById('navAvatar').textContent=d.name.charAt(0).toUpperCase();
            document.getElementById('profDispName').textContent=d.name;
            document.getElementById('profAvBig').textContent=d.name.charAt(0).toUpperCase();
            const s=JSON.parse(localStorage.getItem('lab61_session')||'{}');
            s.name=d.name;localStorage.setItem('lab61_session',JSON.stringify(s));
            toast('Profile updated.');
        }else toast(d.message||'Update failed.','error');
    }catch(e){toast('Network error.','error');}
    btn.disabled=false;btn.innerHTML='<i class="bi bi-check2"></i> Save changes';
}

// ── Login ──
async function submitLogin(e){
    e.preventDefault();
    const btn=document.getElementById('liBtn'),err=document.getElementById('liErr');
    err.style.display='none';
    const email=document.getElementById('liEmail').value.trim(),pass=document.getElementById('liPass').value;
    if(!email||!pass){err.textContent='Email and password required.';err.style.display='block';return;}
    btn.disabled=true;btn.innerHTML='<i class="bi bi-hourglass-split"></i> Signing in…';
    try{
        const d=await(await fetch('?action=login',{
            method:'POST',headers:{'Content-Type':'application/json'},
            body:JSON.stringify({email,password:pass})
        })).json();
        if(d.ok){saveSession(d.name,d.email,d.role);showDash(d.name,d.email,d.role);}
        else{err.textContent=d.message||'Invalid credentials.';err.style.display='block';}
    }catch(ex){err.textContent='Network error.';err.style.display='block';}
    btn.disabled=false;btn.innerHTML='<i class="bi bi-arrow-right-circle"></i> Sign in';
}

// ── Register ──
async function submitRegister(e){
    e.preventDefault();
    const btn=document.getElementById('rgBtn'),err=document.getElementById('rgErr');
    err.style.display='none';
    const name=document.getElementById('rgName').value.trim(),email=document.getElementById('rgEmail').value.trim(),pass=document.getElementById('rgPass').value;
    if(!name||!email||!pass){err.textContent='All fields required.';err.style.display='block';return;}
    btn.disabled=true;btn.innerHTML='<i class="bi bi-hourglass-split"></i> Creating…';
    try{
        const d=await(await fetch('?action=register',{
            method:'POST',headers:{'Content-Type':'application/json'},
            body:JSON.stringify({name,email,password:pass})
        })).json();
        if(d.ok){saveSession(d.name,d.email,d.role);showDash(d.name,d.email,d.role);}
        else{err.textContent=d.message||'Registration failed.';err.style.display='block';}
    }catch(ex){err.textContent='Network error.';err.style.display='block';}
    btn.disabled=false;btn.innerHTML='<i class="bi bi-person-plus"></i> Create account';
}

// ── Init ──
(function(){
    const raw=localStorage.getItem('lab61_session');
    if(!raw)return;
    try{const s=JSON.parse(raw);if(s&&s.name&&s.email)showDash(s.name,s.email,s.role||'user');}catch(e){}
})();
</script>
</body>
</html>
