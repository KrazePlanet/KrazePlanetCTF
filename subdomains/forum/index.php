<?php
// Lab 62 — Stored XSS in Profile Signature Field
// Platform: Acronis Forum (forum.acronis.com) | HackerOne #1084183
// Vulnerability: Profile Signature field stored raw, rendered via innerHTML in every forum post
// Attack: Any user who views a thread where the attacker has posted triggers the XSS payload
// ── Database setup ──
$db_name = 'KrazePlanet';
$db_user = 'root';
$db_pass = '';
$hosts   = ['krazeplanet', '127.0.0.1', 'localhost', '172.19.0.1', 'host.docker.internal'];

$pdo = null;
$lastException = null;

foreach ($hosts as $host) {
    try {
        $pdo = new PDO("mysql:host=$host;charset=utf8mb4", $db_user, $db_pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_TIMEOUT => 2
        ]);
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `$db_name` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $pdo->exec("USE `$db_name`");
        break;
    } catch (PDOException $e) {
        $lastException = $e;
    }
}

if (!$pdo) {
    die("DB connection failed: " . ($lastException ? $lastException->getMessage() : "Unable to connect to database"));
}

$pdo->exec("CREATE TABLE IF NOT EXISTS lab62_users (
    id INT AUTO_INCREMENT PRIMARY KEY, name VARCHAR(100) NOT NULL,
    email VARCHAR(150) UNIQUE NOT NULL, password VARCHAR(255) NOT NULL,
    bio TEXT DEFAULT '', signature TEXT DEFAULT '',
    reputation INT DEFAULT 1, joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");
$pdo->exec("CREATE TABLE IF NOT EXISTS lab62_questions (
    id INT AUTO_INCREMENT PRIMARY KEY, author_email VARCHAR(150) NOT NULL,
    author_name VARCHAR(100) NOT NULL, title VARCHAR(300) NOT NULL,
    body TEXT NOT NULL, votes INT DEFAULT 0, views INT DEFAULT 0,
    tags VARCHAR(300) DEFAULT '', created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");
$pdo->exec("CREATE TABLE IF NOT EXISTS lab62_answers (
    id INT AUTO_INCREMENT PRIMARY KEY, question_id INT NOT NULL,
    author_email VARCHAR(150) NOT NULL, author_name VARCHAR(100) NOT NULL,
    body TEXT NOT NULL, votes INT DEFAULT 0, is_accepted TINYINT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

$sc=$pdo->query("SELECT COUNT(*) FROM lab62_users WHERE email IN ('alice@devask.io','bob@devask.io')")->fetchColumn();
if($sc==0){
    $iu=$pdo->prepare("INSERT IGNORE INTO lab62_users (name,email,password,bio,signature,reputation,joined_at) VALUES (?,?,?,?,?,?,?)");
    foreach([
        ['Alice Chen','alice@devask.io',password_hash('alice@123',PASSWORD_DEFAULT),'Full-stack developer passionate about open-source and web security.','Acronis Forum member since 2021 | Full-Stack Developer',342,date('Y-m-d H:i:s',strtotime('-420 days'))],
        ['Bob Kumar','bob@devask.io',password_hash('bob@123',PASSWORD_DEFAULT),'Security researcher and CTF enthusiast. I break things so you can fix them.','Security Researcher | CTF Player | Bug Hunter',891,date('Y-m-d H:i:s',strtotime('-380 days'))],
        ['Carol White','carol@devask.io',password_hash('carol@123',PASSWORD_DEFAULT),'Frontend developer. Vue, React, and CSS are my jam.','UI/UX Engineer | Open Source Contributor',215,date('Y-m-d H:i:s',strtotime('-300 days'))],
    ] as $u) $iu->execute($u);

    $iq=$pdo->prepare("INSERT INTO lab62_questions (author_email,author_name,title,body,votes,views,tags,created_at) VALUES (?,?,?,?,?,?,?,?)");
    $qids=[];
    foreach([
        ['alice@devask.io','Alice Chen','How do I prevent XSS in user-generated profile fields?',"I'm building a community forum and users can set a custom signature that shows below every post. I'm storing it directly from the form and rendering it with innerHTML.\n\nI've started getting reports of malicious users injecting scripts into their signatures. What is the correct way to sanitize this input on both the backend and frontend?",12,487,'security,xss,php,sanitization',date('Y-m-d H:i:s',strtotime('-14 days'))],
        ['bob@devask.io','Bob Kumar','Why does my JavaScript onerror payload not fire in Safari?',"I'm testing a stored XSS payload <img src=x onerror=alert(1)> inside a profile signature field. It fires in Chrome and Firefox perfectly but Safari blocks it silently.\n\nDoes Safari have stricter HTML parsing rules for onerror events on broken image sources? Is there a more universally supported payload?",8,312,'javascript,xss,browser,safari',date('Y-m-d H:i:s',strtotime('-10 days'))],
        ['carol@devask.io','Carol White','What is the difference between stored, reflected and DOM-based XSS?',"I keep seeing these three categories in OWASP documentation but struggle to understand the practical differences. Can someone give a clear explanation with examples?\n\nSpecifically: when does the payload travel through the server vs stay entirely in the browser?",21,1024,'security,xss,owasp,web-security',date('Y-m-d H:i:s',strtotime('-7 days'))],
    ] as $q){ $iq->execute($q); $qids[]=$pdo->lastInsertId(); }

    $ia=$pdo->prepare("INSERT INTO lab62_answers (question_id,author_email,author_name,body,votes,is_accepted,created_at) VALUES (?,?,?,?,?,?,?)");
    foreach([
        [$qids[0],'bob@devask.io','Bob Kumar',"Always sanitize on the server side before storing. In PHP use htmlspecialchars(\$input, ENT_QUOTES, 'UTF-8') before inserting into the database.\n\nOn the frontend, use textContent instead of innerHTML when rendering untrusted content. If you must render HTML, use a whitelist-based sanitizer like DOMPurify — it strips dangerous tags and attributes while preserving safe formatting.",15,1,date('Y-m-d H:i:s',strtotime('-13 days'))],
        [$qids[0],'carol@devask.io','Carol White',"To add to Bob's answer — Content Security Policy (CSP) headers add a critical second layer of defence. Setting Content-Security-Policy: script-src 'self' will block inline scripts even if they somehow get into the DOM.\n\nAlso consider validating input length — a signature field probably shouldn't accept more than 200 characters.",7,0,date('Y-m-d H:i:s',strtotime('-12 days'))],
        [$qids[1],'alice@devask.io','Alice Chen',"Safari has stricter cross-origin image loading policies. In some versions, onerror doesn't fire for src=x on the same domain context. Try src=//invalid.tld/x instead, or use a SVG-based payload: <svg onload=alert(1)> which is more universally supported across all browsers including Safari.",11,1,date('Y-m-d H:i:s',strtotime('-9 days'))],
        [$qids[2],'bob@devask.io','Bob Kumar',"Stored XSS: Payload is saved in the database (e.g. in a profile signature). It fires for every user who loads the affected page — the most dangerous type.\n\nReflected XSS: Payload is in the URL or request. The server reflects it back in the HTML response. Requires the victim to click a crafted link.\n\nDOM XSS: Payload never reaches the server. Client-side JavaScript reads from a source (e.g. location.hash) and writes to a sink (e.g. innerHTML) — entirely client-side execution.",28,1,date('Y-m-d H:i:s',strtotime('-6 days'))],
        [$qids[2],'carol@devask.io','Carol White',"A useful mental model: Stored = Persistent (lives in the database), Reflected = Transient (in the URL), DOM = Client-only (no server round-trip).\n\nHackerOne report #1084183 is a textbook Stored XSS example — the signature field of an Acronis forum profile accepted raw HTML that fired an alert for any user viewing that profile.",9,0,date('Y-m-d H:i:s',strtotime('-5 days'))],
    ] as $a) $ia->execute($a);
}

if(isset($_GET['action'])&&$_SERVER['REQUEST_METHOD']==='POST'){
    while(ob_get_level())ob_end_clean(); header('Content-Type: application/json');
    $b=json_decode(file_get_contents('php://input'),true)??[];
    $act=$_GET['action'];

    if($act==='register'){
        try{
            $name=trim($b['name']??''); $email=trim($b['email']??''); $pass=trim($b['password']??'');
            if(!$name||!$email||!$pass){echo json_encode(['ok'=>false,'message'=>'All fields required.']);exit;}
            if(strlen($pass)<6){echo json_encode(['ok'=>false,'message'=>'Password min. 6 chars.']);exit;}
            $chk=$pdo->prepare("SELECT id FROM lab62_users WHERE email=?"); $chk->execute([$email]);
            if($chk->fetch()){echo json_encode(['ok'=>false,'message'=>'Email already registered.']);exit;}
            $pdo->prepare("INSERT INTO lab62_users(name,email,password)VALUES(?,?,?)")->execute([$name,$email,password_hash($pass,PASSWORD_DEFAULT)]);
            echo json_encode(['ok'=>true,'name'=>$name,'email'=>$email,'reputation'=>1]);
        }catch(Exception $e){echo json_encode(['ok'=>false,'message'=>'Server error.']);}
        exit;
    }
    if($act==='login'){
        try{
            $email=trim($b['email']??''); $pass=trim($b['password']??'');
            $stmt=$pdo->prepare("SELECT id,name,password,reputation FROM lab62_users WHERE email=?"); $stmt->execute([$email]);
            $row=$stmt->fetch();
            if($row&&password_verify($pass,$row['password'])) echo json_encode(['ok'=>true,'name'=>$row['name'],'email'=>$email,'reputation'=>(int)$row['reputation']]);
            else echo json_encode(['ok'=>false,'message'=>'Invalid email or password.']);
        }catch(Exception $e){echo json_encode(['ok'=>false,'message'=>'Server error.']);}
        exit;
    }
    if($act==='get_questions'){
        try{
            $qs=$pdo->query("SELECT q.*,(SELECT COUNT(*) FROM lab62_answers WHERE question_id=q.id) as answer_count FROM lab62_questions q ORDER BY q.created_at DESC")->fetchAll();
            echo json_encode(['ok'=>true,'questions'=>$qs]);
        }catch(Exception $e){echo json_encode(['ok'=>false,'questions'=>[]]);}
        exit;
    }
    if($act==='get_question'){
        try{
            $id=(int)($b['id']??0);
            $q=$pdo->prepare("SELECT * FROM lab62_questions WHERE id=?"); $q->execute([$id]); $question=$q->fetch();
            if(!$question){echo json_encode(['ok'=>false,'message'=>'Not found']);exit;}
            $pdo->prepare("UPDATE lab62_questions SET views=views+1 WHERE id=?")->execute([$id]);
            $as=$pdo->prepare("SELECT a.id,a.question_id,a.author_email,a.author_name,a.body,a.votes,a.is_accepted,a.created_at,u.signature,u.reputation FROM lab62_answers a LEFT JOIN lab62_users u ON u.email=a.author_email WHERE a.question_id=? ORDER BY a.is_accepted DESC,a.votes DESC,a.created_at ASC");
            $as->execute([$id]); $answers=$as->fetchAll();
            $qu=$pdo->prepare("SELECT signature,reputation FROM lab62_users WHERE email=?"); $qu->execute([$question['author_email']]); $quser=$qu->fetch();
            $question['signature']=$quser['signature']??'';
            $question['reputation']=(int)($quser['reputation']??1);
            echo json_encode(['ok'=>true,'question'=>$question,'answers'=>$answers]);
        }catch(Exception $e){echo json_encode(['ok'=>false,'message'=>'Server error.']);}
        exit;
    }
    if($act==='ask_question'){
        try{
            $email=trim($b['email']??''); $name=trim($b['name']??'');
            $title=trim($b['title']??''); $body=trim($b['body']??''); $tags=trim($b['tags']??'');
            if(!$title||!$body){echo json_encode(['ok'=>false,'message'=>'Title and body required.']);exit;}
            $pdo->prepare("INSERT INTO lab62_questions(author_email,author_name,title,body,tags)VALUES(?,?,?,?,?)")->execute([$email,$name,$title,$body,$tags]);
            echo json_encode(['ok'=>true,'id'=>(int)$pdo->lastInsertId()]);
        }catch(Exception $e){echo json_encode(['ok'=>false,'message'=>'Server error.']);}
        exit;
    }
    if($act==='post_answer'){
        try{
            $email=trim($b['email']??''); $name=trim($b['name']??'');
            $qid=(int)($b['question_id']??0); $body=trim($b['body']??'');
            if(!$body||!$qid){echo json_encode(['ok'=>false,'message'=>'Answer body required.']);exit;}
            $pdo->prepare("INSERT INTO lab62_answers(question_id,author_email,author_name,body)VALUES(?,?,?,?)")->execute([$qid,$email,$name,$body]);
            echo json_encode(['ok'=>true]);
        }catch(Exception $e){echo json_encode(['ok'=>false,'message'=>'Server error.']);}
        exit;
    }
    if($act==='get_profile'){
        try{
            $email=trim($b['email']??'');
            $stmt=$pdo->prepare("SELECT name,email,bio,signature,reputation,joined_at FROM lab62_users WHERE email=?"); $stmt->execute([$email]);
            $row=$stmt->fetch();
            if(!$row){echo json_encode(['ok'=>false,'message'=>'User not found.']);exit;}
            $qc=$pdo->prepare("SELECT COUNT(*) FROM lab62_questions WHERE author_email=?"); $qc->execute([$email]);
            $ac=$pdo->prepare("SELECT COUNT(*) FROM lab62_answers WHERE author_email=?"); $ac->execute([$email]);
            $row['question_count']=(int)$qc->fetchColumn(); $row['answer_count']=(int)$ac->fetchColumn();
            echo json_encode(['ok'=>true,'profile'=>$row]);
        }catch(Exception $e){echo json_encode(['ok'=>false,'message'=>'Server error.']);}
        exit;
    }
    if($act==='update_profile'){
        try{
            $email=trim($b['email']??''); $name=trim($b['name']??'');
            $bio=trim($b['bio']??'');
            $signature=$b['signature']??''; // VULNERABLE: stored raw — no htmlspecialchars()
            if(!$email||!$name){echo json_encode(['ok'=>false,'message'=>'Missing fields.']);exit;}
            $pdo->prepare("UPDATE lab62_users SET name=?,bio=?,signature=? WHERE email=?")->execute([$name,$bio,$signature,$email]);
            echo json_encode(['ok'=>true,'name'=>$name]);
        }catch(Exception $e){echo json_encode(['ok'=>false,'message'=>'Server error.']);}
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Acronis Forum — Community Q&amp;A</title>
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0;}
:root{
  --or:#f48024;--or-d:#da6e1b;--hdr:#232629;--bg:#f1f2f3;--wh:#fff;
  --bd:#d6d9dc;--bdl:#e4e6e8;--tx:#3c4146;--mu:#6a737c;
  --bl:#0074cc;--bll:#e1ecf4;--gn:#5eba7d;--rd:#d1383d;
  --hh:50px;--sw:164px;
}
body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;font-size:13px;color:var(--tx);background:var(--bg);}
a{color:var(--bl);text-decoration:none;}
/* ── HEADER ── */
.hdr{height:var(--hh);background:var(--hdr);border-top:3px solid var(--or);display:flex;align-items:center;padding:0 12px;position:fixed;top:0;left:0;right:0;z-index:200;gap:8px;}
.hlogo{display:flex;align-items:center;gap:7px;text-decoration:none;cursor:pointer;}
.hlogo-box{width:28px;height:28px;background:var(--or);border-radius:4px;display:flex;align-items:center;justify-content:center;}
.hlogo-box svg{width:16px;height:16px;fill:#fff;}
.hlogo-txt{font-size:15px;font-weight:800;color:#fff;letter-spacing:-.3px;}
.hlogo-txt span{color:var(--or);}
.hsearch{flex:1;max-width:480px;background:rgba(255,255,255,.1);border:1px solid rgba(255,255,255,.08);border-radius:3px;display:flex;align-items:center;padding:0 10px;gap:6px;}
.hsearch input{background:none;border:none;outline:none;color:#fff;font-size:13px;width:100%;padding:6px 0;}
.hsearch input::placeholder{color:#8d9399;}
.hnav{display:flex;align-items:center;gap:6px;margin-left:auto;}
.hlink{color:#9fa6ad;font-size:12px;padding:6px 8px;border-radius:3px;cursor:pointer;white-space:nowrap;background:none;border:none;font-family:inherit;}
.hlink:hover{background:rgba(255,255,255,.1);color:#fff;}
.husr{display:flex;align-items:center;gap:6px;background:rgba(255,255,255,.08);border:1px solid rgba(255,255,255,.1);border-radius:3px;padding:5px 8px;cursor:pointer;}
.husr:hover{background:rgba(255,255,255,.15);}
.av{width:24px;height:24px;border-radius:3px;background:var(--or);display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:700;color:#fff;flex-shrink:0;}
.av.lg{width:56px;height:56px;font-size:1.4rem;border-radius:6px;}
.av.xl{width:80px;height:80px;font-size:2rem;border-radius:8px;}
.rep{font-size:11px;color:#bcbbbb;}
.btn-out{background:none;border:1px solid rgba(255,255,255,.18);color:#9fa6ad;border-radius:3px;padding:5px 9px;font-size:12px;cursor:pointer;font-family:inherit;}
.btn-out:hover{background:rgba(255,255,255,.08);color:#fff;}
/* ── LAYOUT ── */
.wrap{display:flex;margin-top:var(--hh);min-height:calc(100vh - var(--hh));}
.sbar{width:var(--sw);flex-shrink:0;padding:10px 0;position:sticky;top:var(--hh);height:calc(100vh - var(--hh));overflow-y:auto;}
.snav-sec{padding:4px 8px 2px;font-size:10px;font-weight:700;color:var(--mu);text-transform:uppercase;letter-spacing:.05em;}
.snav-lnk{display:block;padding:6px 10px 6px 14px;font-size:12px;color:var(--tx);border-left:3px solid transparent;cursor:pointer;transition:background .1s;}
.snav-lnk:hover{background:var(--bdl);}
.snav-lnk.active{background:var(--bdl);font-weight:700;border-left-color:var(--or);}
.main{flex:1;padding:20px 18px 40px;max-width:860px;}
/* ── BUTTONS ── */
.btn{background:var(--or);color:#fff;border:none;border-radius:3px;padding:9px 14px;font-size:13px;font-weight:500;font-family:inherit;cursor:pointer;display:inline-flex;align-items:center;gap:5px;transition:background .12s;}
.btn:hover{background:var(--or-d);}
.btn:disabled{opacity:.45;cursor:default;}
.btn.sm{padding:5px 10px;font-size:12px;}
.btn.ghost{background:var(--wh);color:var(--mu);border:1px solid var(--bd);}
.btn.ghost:hover{background:var(--bg);}
/* ── FORMS ── */
.fg{margin-bottom:13px;}
.fl{display:block;font-size:13px;font-weight:600;margin-bottom:4px;}
.fh{font-size:11px;color:var(--mu);margin-top:3px;}
.fc{width:100%;border:1px solid var(--bd);border-radius:3px;padding:8px 10px;font-size:13px;font-family:inherit;color:var(--tx);background:var(--wh);outline:none;transition:border-color .12s,box-shadow .12s;}
.fc:focus{border-color:var(--bl);box-shadow:0 0 0 4px rgba(0,116,204,.12);}
textarea.fc{resize:vertical;min-height:90px;line-height:1.6;}
.err{background:#fdf3f3;border:1px solid #f1acb0;color:var(--rd);border-radius:3px;padding:8px 12px;font-size:12px;margin:8px 0;display:none;}
/* ── LOGIN ── */
.login-pg{min-height:100vh;background:var(--bg);display:flex;flex-direction:column;align-items:center;justify-content:center;padding:2rem 1rem;}
.login-logo{display:flex;align-items:center;gap:8px;margin-bottom:1.5rem;}
.login-logo-box{width:40px;height:40px;background:var(--or);border-radius:6px;display:flex;align-items:center;justify-content:center;}
.login-logo-box svg{width:22px;height:22px;fill:#fff;}
.login-logo h1{font-size:1.35rem;font-weight:800;color:var(--tx);}
.lcard{background:var(--wh);border:1px solid var(--bd);border-radius:5px;padding:1.6rem;width:100%;max-width:326px;box-shadow:0 1px 3px rgba(0,0,0,.07);}
.lcard h2{font-size:.95rem;font-weight:700;text-align:center;margin-bottom:1.2rem;}
.lfoot{text-align:center;font-size:12px;color:var(--mu);margin-top:.9rem;}
.lfoot a{color:var(--bl);cursor:pointer;}
.accs{background:var(--wh);border:1px solid var(--bd);border-radius:4px;padding:.85rem 1rem;max-width:340px;width:100%;margin-top:1rem;font-size:12px;}
.accs h4{font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:var(--mu);margin-bottom:.5rem;}
.acc-r{display:flex;align-items:center;gap:8px;padding:3px 0;border-bottom:1px solid var(--bdl);}
.acc-r:last-child{border-bottom:none;}
.acc-r .an{font-weight:600;min-width:80px;color:var(--tx);}
.acc-r .ae{color:var(--mu);flex:1;font-size:11px;}
.acc-r .ap{font-family:monospace;color:var(--bl);font-size:11px;}
/* ── FEED ── */
.fhdr{display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;}
.fhdr h1{font-size:1.05rem;font-weight:700;}
.qcard{background:var(--wh);border:1px solid var(--bd);border-radius:3px;padding:12px 14px;margin-bottom:5px;display:flex;gap:12px;cursor:pointer;transition:border-color .1s;}
.qcard:hover{border-color:#b3b8bf;}
.qstats{display:flex;flex-direction:column;align-items:flex-end;gap:3px;min-width:65px;flex-shrink:0;}
.qstat{display:flex;flex-direction:column;align-items:center;}
.qstat-n{font-size:14px;font-weight:700;color:var(--mu);}
.qstat-n.ans{color:var(--gn);}
.qstat-l{font-size:10px;color:var(--mu);}
.qbody{flex:1;}
.qtitle{font-size:13px;color:var(--bl);font-weight:500;margin-bottom:4px;line-height:1.4;}
.qexc{font-size:12px;color:var(--mu);margin-bottom:7px;line-height:1.5;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;}
.qmeta{display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:5px;}
.qtags{display:flex;gap:3px;flex-wrap:wrap;}
.tag{font-size:11px;padding:2px 6px;background:var(--bll);color:#39739d;border:1px solid #9cc3db;border-radius:3px;}
.qauthor{font-size:11px;color:var(--mu);}
.qauthor .nm{color:var(--bl);}
/* ── QUESTION DETAIL ── */
.qdh{border-bottom:1px solid var(--bd);margin-bottom:14px;padding-bottom:10px;}
.qdh-title{font-size:1.2rem;font-weight:500;margin-bottom:7px;line-height:1.4;}
.qdh-meta{display:flex;gap:12px;font-size:12px;color:var(--mu);}
.post{display:flex;gap:14px;padding:12px 0;border-bottom:1px solid var(--bdl);}
.vcol{display:flex;flex-direction:column;align-items:center;gap:3px;min-width:36px;}
.vbtn{width:33px;height:33px;border:2px solid var(--bd);border-radius:50%;background:none;cursor:pointer;color:var(--mu);font-size:14px;display:flex;align-items:center;justify-content:center;transition:all .1s;}
.vbtn:hover{border-color:var(--or);color:var(--or);}
.vcnt{font-size:1.1rem;font-weight:700;}
.acc-mark{width:33px;height:33px;border-radius:50%;background:var(--gn);display:flex;align-items:center;justify-content:center;color:#fff;font-size:17px;}
.pcol{flex:1;}
.pbody{font-size:13px;line-height:1.75;color:var(--tx);margin-bottom:12px;white-space:pre-wrap;word-break:break-word;}
.pfoot{display:flex;justify-content:flex-end;}
.ucard{background:#dce8f2;border:1px solid #c4d8e9;border-radius:3px;padding:8px 10px;min-width:170px;font-size:12px;}
.ucard-lbl{color:var(--mu);font-size:11px;margin-bottom:4px;}
.ucard-in{display:flex;align-items:flex-start;gap:7px;}
.ucard-info{flex:1;}
.ucard-name{color:var(--bl);font-weight:600;font-size:12px;cursor:pointer;}
.ucard-name:hover{color:var(--or);}
.ucard-rep{color:var(--mu);font-size:11px;}
/* ⚠ VULNERABLE — signature rendered via innerHTML inside this element */
.usig{font-size:11px;color:var(--mu);margin-top:5px;border-top:1px solid #c4d8e9;padding-top:4px;font-style:italic;word-break:break-word;}
.ans-hdr{font-size:.95rem;font-weight:700;margin:20px 0 10px;}
.ans-form{margin-top:22px;border-top:1px solid var(--bd);padding-top:18px;}
.ans-form h3{font-size:.9rem;font-weight:700;margin-bottom:10px;}
/* ── PROFILE ── */
.psec{background:var(--wh);border:1px solid var(--bd);border-radius:4px;padding:14px;margin-bottom:12px;}
.psec-ttl{font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:var(--mu);margin-bottom:10px;padding-bottom:6px;border-bottom:1px solid var(--bdl);}
/* ── PUBLIC PROFILE ── */
.pub-hdr{background:var(--wh);border:1px solid var(--bd);border-radius:4px;padding:18px;margin-bottom:12px;display:flex;align-items:flex-start;gap:18px;}
.pub-sig-box{background:#fdf7e2;border:1px solid #e8d8a0;border-radius:3px;padding:10px 12px;margin-bottom:12px;}
.pub-sig-lbl{font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:#997a00;margin-bottom:5px;}
/* ⚠ VULNERABLE — signature rendered via innerHTML here on public profile page */
.pub-sig-val{font-size:13px;color:var(--tx);line-height:1.6;word-break:break-word;}
/* ── TOAST ── */
.toast{position:fixed;bottom:18px;right:18px;z-index:9999;background:var(--tx);color:#fff;border-radius:4px;padding:9px 14px;font-size:12px;display:flex;align-items:center;gap:7px;box-shadow:0 4px 16px rgba(0,0,0,.25);opacity:0;transform:translateY(6px);transition:all .2s;pointer-events:none;}
.toast.show{opacity:1;transform:translateY(0);}
.toast.ok{background:#1a7f5a;}
.toast.er{background:var(--rd);}
.back{display:inline-flex;align-items:center;gap:4px;font-size:12px;color:var(--mu);margin-bottom:10px;cursor:pointer;background:none;border:none;font-family:inherit;padding:0;}
.back:hover{color:var(--bl);}
.div{height:1px;background:var(--bd);margin:10px 0;}
</style>
</head>
<body>

<!-- ════ LOGIN ════ -->
<div class="login-pg" id="loginPage">
  <div class="login-logo">
    <div class="login-logo-box"><svg viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1 14H9V8h2v8zm4 0h-2V8h2v8z"/></svg></div>
    <h1>Acronis Forum</h1>
  </div>
  <div class="lcard">
    <h2>Log in to Acronis Forum</h2>
    <form onsubmit="submitLogin(event)">
      <div class="fg"><label class="fl">Email</label><input type="email" id="liEmail" class="fc" placeholder="you@example.com" autocomplete="off"></div>
      <div class="fg"><label class="fl">Password</label><input type="password" id="liPassword" class="fc" placeholder="Password"></div>
      <button type="submit" class="btn" style="width:100%;justify-content:center;margin-top:4px;" id="liBtn">Log in</button>
      <div class="err" id="liError"></div>
    </form>
    <div class="lfoot">No account? <a onclick="showRegister()">Sign up</a></div>
  </div>
  <div class="accs">
    <h4>Test Accounts</h4>
    <div class="acc-r"><span class="an">Alice Chen</span><span class="ae">alice@devask.io</span><span class="ap">alice@123</span></div>
    <div class="acc-r"><span class="an">Bob Kumar</span><span class="ae">bob@devask.io</span><span class="ap">bob@123</span></div>
    <div class="acc-r"><span class="an">Carol White</span><span class="ae">carol@devask.io</span><span class="ap">carol@123</span></div>
  </div>
</div>

<!-- ════ REGISTER ════ -->
<div class="login-pg" id="registerPage" style="display:none;">
  <div class="login-logo">
    <div class="login-logo-box"><svg viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1 14H9V8h2v8zm4 0h-2V8h2v8z"/></svg></div>
    <h1>Acronis Forum</h1>
  </div>
  <div class="lcard">
    <h2>Join Acronis Forum</h2>
    <form onsubmit="submitRegister(event)">
      <div class="fg"><label class="fl">Display Name</label><input type="text" id="rgName" class="fc" placeholder="Your name"></div>
      <div class="fg"><label class="fl">Email</label><input type="email" id="rgEmail" class="fc" placeholder="you@example.com"></div>
      <div class="fg"><label class="fl">Password</label><input type="password" id="rgPassword" class="fc" placeholder="Min. 6 characters"></div>
      <button type="submit" class="btn" style="width:100%;justify-content:center;margin-top:4px;" id="rgBtn">Sign up</button>
      <div class="err" id="rgError"></div>
    </form>
    <div class="lfoot">Already have an account? <a onclick="showLogin()">Log in</a></div>
  </div>
</div>

<!-- ════ DASHBOARD ════ -->
<div id="dashView" style="display:none;">
  <header class="hdr">
    <div class="hlogo" onclick="showSection('feed')">
      <div class="hlogo-box"><svg viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1 14H9V8h2v8zm4 0h-2V8h2v8z"/></svg></div>
      <span class="hlogo-txt">Acronis <span>Forum</span></span>
    </div>
    <div class="hsearch">
      <svg viewBox="0 0 24 24" width="14" height="14" fill="#8d9399"><path d="M21 21l-4.35-4.35M17 11A6 6 0 1 1 5 11a6 6 0 0 1 12 0z" stroke="#8d9399" stroke-width="2" fill="none"/></svg>
      <input type="text" placeholder="Search questions…">
    </div>
    <div class="hnav">
      <button class="hlink" onclick="showSection('ask')">Ask Question</button>
      <div class="husr" onclick="showSection('profile')">
        <div class="av" id="hAvatar">?</div>
        <div><div style="color:#fff;font-size:12px;font-weight:600;" id="hName">User</div><div class="rep" id="hRep">⬆ 1</div></div>
      </div>
      <button class="btn-out" onclick="logout()">Log out</button>
    </div>
  </header>

  <div class="wrap">
    <nav class="sbar">
      <div class="snav-sec" style="margin-top:8px;">Navigation</div>
      <a class="snav-lnk active" id="nav-feed" onclick="showSection('feed');return false;" href="#">Home</a>
      <a class="snav-lnk" id="nav-ask" onclick="showSection('ask');return false;" href="#">Ask a Question</a>
      <div class="div" style="margin:6px 8px;"></div>
      <div class="snav-sec">Account</div>
      <a class="snav-lnk" id="nav-profile" onclick="showSection('profile');return false;" href="#">My Profile</a>
    </nav>

    <main class="main">

      <!-- FEED -->
      <div id="sec-feed">
        <div class="fhdr"><h1>All Questions</h1><button class="btn sm" onclick="showSection('ask')">+ Ask Question</button></div>
        <div id="questionsList"><div style="color:var(--mu);padding:20px;text-align:center;">Loading…</div></div>
      </div>

      <!-- QUESTION DETAIL -->
      <div id="sec-question" style="display:none;">
        <button class="back" onclick="showSection('feed')">← Back to Questions</button>
        <div id="questionDetail"></div>
      </div>

      <!-- ASK QUESTION -->
      <div id="sec-ask" style="display:none;">
        <h1 style="font-size:1.05rem;font-weight:700;margin-bottom:14px;">Ask a Public Question</h1>
        <div class="psec">
          <div class="fg"><label class="fl">Title <span style="color:var(--rd);">*</span></label><input type="text" id="askTitle" class="fc" placeholder="Be specific — what are you trying to solve?"><div class="fh">Minimum 5 characters.</div></div>
          <div class="fg"><label class="fl">Body <span style="color:var(--rd);">*</span></label><textarea id="askBody" class="fc" rows="8" placeholder="Include all information someone would need to answer your question…"></textarea></div>
          <div class="fg"><label class="fl">Tags</label><input type="text" id="askTags" class="fc" placeholder="e.g. javascript,php,xss (comma-separated)"><div class="fh">Add up to 5 tags describing your question.</div></div>
          <div class="err" id="askError"></div>
          <div style="display:flex;gap:8px;"><button class="btn" id="askBtn" onclick="submitQuestion()">Post Your Question</button><button class="btn ghost" onclick="showSection('feed')">Discard</button></div>
        </div>
      </div>

      <!-- PROFILE -->
      <div id="sec-profile" style="display:none;">
        <h1 style="font-size:1.05rem;font-weight:700;margin-bottom:14px;">Edit Profile</h1>
        <div class="psec">
          <div class="psec-ttl">Public Information</div>
          <div class="fg"><label class="fl">Display Name</label><input type="text" id="profName" class="fc"></div>
          <div class="fg"><label class="fl">Email (read-only)</label><input type="text" id="profEmail" class="fc" readonly style="background:#f6f6f6;color:var(--mu);"></div>
          <div class="fg"><label class="fl">About Me</label><textarea id="profBio" class="fc" rows="3" placeholder="Tell the community about yourself…"></textarea></div>
        </div>
        <div class="psec">
          <div class="psec-ttl">Profile Signature</div>
          <div class="fg">
            <label class="fl">Signature</label>
            <!-- ⚠ VULNERABLE INPUT: stored raw in DB, rendered via innerHTML in every post -->
            <textarea id="profSig" class="fc" rows="3" placeholder="Your signature appears below every answer and post you write…"></textarea>
          </div>
          <div style="margin-top:4px;">
            <div class="fh" style="margin-bottom:4px;">Live Preview (rendered as HTML):</div>
            <!-- ⚠ VULNERABLE: signature rendered via innerHTML — fires XSS on profile page load/save -->
            <div id="sigPreview" style="min-height:28px;padding:7px 10px;border:1px solid var(--bd);border-radius:3px;background:#fffdf5;font-size:12px;font-style:italic;color:var(--mu);"></div>
          </div>
        </div>
        <div class="err" id="profError"></div>
        <button class="btn" id="profBtn" onclick="saveProfile()">Save Profile</button>
      </div>

      <!-- PUBLIC PROFILE -->
      <div id="sec-pub-profile" style="display:none;">
        <button class="back" id="pubBack">← Back</button>
        <div id="pubProfileContent"></div>
      </div>

    </main>
  </div>
</div>

<div class="toast" id="toast"><span id="toastMsg"></span></div>

<script>
let _nm='',_em='',_rp=1,_cq=null,_toastT=null;

function safe(s){return String(s??'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#39;');}
function ago(d){const s=Math.floor((Date.now()-new Date(d))/1000);if(s<60)return s+'s ago';if(s<3600)return Math.floor(s/60)+'m ago';if(s<86400)return Math.floor(s/3600)+'h ago';return Math.floor(s/86400)+'d ago';}
function fmtd(d){return new Date(d).toLocaleDateString('en-GB',{day:'numeric',month:'short',year:'numeric'});}
function toast(msg,type){
    const t=document.getElementById('toast');
    t.className='toast '+(type||'ok');
    document.getElementById('toastMsg').textContent=msg;
    t.classList.add('show');clearTimeout(_toastT);
    _toastT=setTimeout(()=>t.classList.remove('show'),3200);
}
function saveSession(n,e,r){localStorage.setItem('lab62_s',JSON.stringify({n,e,r:r||1}));}
function logout(){localStorage.removeItem('lab62_s');location.reload();}

function showLogin(){document.getElementById('registerPage').style.display='none';document.getElementById('loginPage').style.display='flex';}
function showRegister(){document.getElementById('loginPage').style.display='none';document.getElementById('registerPage').style.display='flex';}

function showDash(n,e,r){
    _nm=n;_em=e;_rp=r||1;
    ['loginPage','registerPage'].forEach(id=>document.getElementById(id).style.display='none');
    document.getElementById('dashView').style.display='block';
    document.getElementById('hName').textContent=n;
    document.getElementById('hAvatar').textContent=n.charAt(0).toUpperCase();
    document.getElementById('hRep').textContent='⬆ '+_rp;
    showSection('feed');
}

function showSection(sec,data){
    ['feed','question','ask','profile','pub-profile'].forEach(s=>{
        const el=document.getElementById('sec-'+s); if(el) el.style.display=s===sec?'block':'none';
        const nav=document.getElementById('nav-'+s); if(nav) nav.className='snav-lnk'+(s===sec?' active':'');
    });
    if(sec==='feed') loadFeed();
    if(sec==='profile') loadMyProfile();
    if(sec==='question'&&data!=null) loadQuestion(data);
    if(sec==='pub-profile'&&data!=null) loadPubProfile(data);
}

// ── Feed ──
async function loadFeed(){
    const el=document.getElementById('questionsList');
    el.innerHTML='<div style="color:var(--mu);padding:20px;text-align:center;">Loading questions…</div>';
    try{
        const d=await(await fetch('?action=get_questions',{method:'POST',headers:{'Content-Type':'application/json'},body:'{}'})).json();
        if(!d.ok||!d.questions.length){el.innerHTML='<div style="color:var(--mu);padding:20px;">No questions yet.</div>';return;}
        el.innerHTML='';
        d.questions.forEach(q=>{
            const tags=(q.tags||'').split(',').filter(Boolean);
            const card=document.createElement('div'); card.className='qcard';
            card.innerHTML=`
                <div class="qstats">
                    <div class="qstat"><div class="qstat-n">${safe(String(q.votes))}</div><div class="qstat-l">votes</div></div>
                    <div class="qstat"><div class="qstat-n ${parseInt(q.answer_count)>0?'ans':''}">${safe(String(q.answer_count))}</div><div class="qstat-l">answers</div></div>
                    <div class="qstat"><div class="qstat-n" style="font-size:12px;">${safe(String(q.views))}</div><div class="qstat-l">views</div></div>
                </div>
                <div class="qbody">
                    <div class="qtitle">${safe(q.title)}</div>
                    <div class="qexc">${safe(q.body)}</div>
                    <div class="qmeta">
                        <div class="qtags">${tags.map(t=>`<span class="tag">${safe(t.trim())}</span>`).join('')}</div>
                        <div class="qauthor">asked ${ago(q.created_at)} by <span class="nm">${safe(q.author_name)}</span></div>
                    </div>
                </div>`;
            card.addEventListener('click',()=>showSection('question',q.id));
            el.appendChild(card);
        });
    }catch(e){el.innerHTML='<div style="color:var(--rd);padding:20px;">Failed to load questions.</div>';}
}

// ── Question Detail ──
// ╔══════════════════════════════════════════════════════════════════════╗
// ║  VULNERABILITY: answer.signature rendered via innerHTML             ║
// ║  PHP update_profile stores signature raw — no htmlspecialchars()    ║
// ║  Any user viewing a thread where attacker posted triggers XSS       ║
// ║  Real-world: Acronis Forum — HackerOne #1084183 ($50 bounty)        ║
// ╚══════════════════════════════════════════════════════════════════════╝
async function loadQuestion(id){
    _cq=id;
    const el=document.getElementById('questionDetail');
    el.innerHTML='<div style="color:var(--mu);padding:20px;">Loading…</div>';
    try{
        const d=await(await fetch('?action=get_question',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({id})})).json();
        if(!d.ok){el.innerHTML='<div style="color:var(--rd);">Question not found.</div>';return;}
        const q=d.question, answers=d.answers;
        const tags=(q.tags||'').split(',').filter(Boolean);
        let html=`
            <div class="qdh">
                <div class="qdh-title">${safe(q.title)}</div>
                <div class="qdh-meta">
                    <span>Asked <b>${fmtd(q.created_at)}</b></span>
                    <span>Modified <b>${ago(q.created_at)}</b></span>
                    <span>Viewed <b>${safe(String(q.views))} times</b></span>
                </div>
            </div>
            <div class="post">
                <div class="vcol">
                    <button class="vbtn">▲</button>
                    <div class="vcnt">${safe(String(q.votes))}</div>
                    <button class="vbtn">▼</button>
                </div>
                <div class="pcol">
                    <div class="pbody">${safe(q.body)}</div>
                    <div class="qtags" style="margin-bottom:12px;">${tags.map(t=>`<span class="tag">${safe(t.trim())}</span>`).join('')}</div>
                    <div class="pfoot">
                        <div class="ucard">
                            <div class="ucard-lbl">asked ${fmtd(q.created_at)}</div>
                            <div class="ucard-in">
                                <div class="av" style="width:32px;height:32px;font-size:.85rem;">${safe(q.author_name.charAt(0).toUpperCase())}</div>
                                <div class="ucard-info">
                                    <div class="ucard-name" onclick="viewPubProfile('${safe(q.author_email)}')">${safe(q.author_name)}</div>
                                    <div class="ucard-rep">⬆ ${safe(String(q.reputation))}</div>
                                    <div class="usig" id="qs-${safe(String(q.id))}"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>`;
        if(answers.length){
            html+=`<div class="ans-hdr">${answers.length} Answer${answers.length>1?'s':''}</div>`;
            answers.forEach(a=>{
                html+=`
                    <div class="post" style="${a.is_accepted?'background:#f6fff6;border-radius:3px;':''}">
                        <div class="vcol">
                            <button class="vbtn">▲</button>
                            <div class="vcnt">${safe(String(a.votes))}</div>
                            ${a.is_accepted?'<div class="acc-mark" title="Accepted answer">✓</div>':'<button class="vbtn">▼</button>'}
                        </div>
                        <div class="pcol">
                            <div class="pbody">${safe(a.body)}</div>
                            <div class="pfoot">
                                <div class="ucard">
                                    <div class="ucard-lbl">answered ${fmtd(a.created_at)}</div>
                                    <div class="ucard-in">
                                        <div class="av" style="width:32px;height:32px;font-size:.85rem;">${safe(a.author_name.charAt(0).toUpperCase())}</div>
                                        <div class="ucard-info">
                                            <div class="ucard-name" onclick="viewPubProfile('${safe(a.author_email)}')">${safe(a.author_name)}</div>
                                            <div class="ucard-rep">⬆ ${safe(String(a.reputation||1))}</div>
                                            <div class="usig" id="as-${safe(String(a.id))}"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>`;
            });
        }
        html+=`
            <div class="ans-form">
                <h3>Your Answer</h3>
                <div class="fg"><textarea id="ansBody" class="fc" rows="8" placeholder="Write your answer here…"></textarea></div>
                <div class="err" id="ansError"></div>
                <button class="btn" id="ansBtn" onclick="submitAnswer(${safe(String(id))})">Post Your Answer</button>
            </div>`;
        el.innerHTML=html;

        // ⚠ VULNERABLE: inject signatures via innerHTML after safe structure is set
        const qsEl=document.getElementById('qs-'+q.id);
        if(qsEl&&q.signature) qsEl.innerHTML=q.signature; // ← VULNERABLE — raw signature fires XSS

        answers.forEach(a=>{
            const sigEl=document.getElementById('as-'+a.id);
            if(sigEl&&a.signature) sigEl.innerHTML=a.signature; // ← VULNERABLE — raw signature fires XSS
        });
    }catch(e){el.innerHTML='<div style="color:var(--rd);">Failed to load.</div>';}
}

// ── Public Profile ──
function viewPubProfile(email){
    const backEl=document.getElementById('pubBack');
    const prevQ=_cq;
    backEl.onclick=()=>{ if(prevQ) showSection('question',prevQ); else showSection('feed'); };
    showSection('pub-profile',email);
}

async function loadPubProfile(email){
    const el=document.getElementById('pubProfileContent');
    el.innerHTML='<div style="color:var(--mu);">Loading…</div>';
    try{
        const d=await(await fetch('?action=get_profile',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({email})})).json();
        if(!d.ok){el.innerHTML='<div style="color:var(--rd);">Profile not found.</div>';return;}
        const p=d.profile;
        el.innerHTML=`
            <div class="pub-hdr">
                <div class="av xl">${safe(p.name.charAt(0).toUpperCase())}</div>
                <div style="flex:1;">
                    <h2 style="font-size:1.15rem;font-weight:700;margin-bottom:4px;">${safe(p.name)}</h2>
                    <div style="font-size:12px;color:var(--mu);margin-bottom:6px;">Member since ${fmtd(p.joined_at)} &nbsp;·&nbsp; ⬆ ${safe(String(p.reputation))} reputation</div>
                    <div style="font-size:13px;color:var(--tx);margin-bottom:10px;">${safe(p.bio||'No bio provided.')}</div>
                    <div style="display:flex;gap:16px;">
                        <div><span style="font-weight:700;">${safe(String(p.question_count))}</span> <span style="color:var(--mu);font-size:12px;">questions</span></div>
                        <div><span style="font-weight:700;">${safe(String(p.answer_count))}</span> <span style="color:var(--mu);font-size:12px;">answers</span></div>
                    </div>
                </div>
            </div>
            ${p.signature?`
            <div class="pub-sig-box">
                <div class="pub-sig-lbl">Profile Signature</div>
                <div class="pub-sig-val" id="pubSigVal"></div>
            </div>`:''}`;
        // ⚠ VULNERABLE: signature set via innerHTML — fires for any user viewing this profile
        if(p.signature){
            const sv=document.getElementById('pubSigVal');
            if(sv) sv.innerHTML=p.signature; // ← VULNERABLE
        }
    }catch(e){el.innerHTML='<div style="color:var(--rd);">Failed to load.</div>';}
}

// ── My Profile ──
async function loadMyProfile(){
    try{
        const d=await(await fetch('?action=get_profile',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({email:_em})})).json();
        if(d.ok){
            document.getElementById('profName').value=d.profile.name;
            document.getElementById('profEmail').value=d.profile.email;
            document.getElementById('profBio').value=d.profile.bio||'';
            document.getElementById('profSig').value=d.profile.signature||'';
            // ⚠ VULNERABLE: render saved signature via innerHTML — fires XSS on profile page load
            document.getElementById('sigPreview').innerHTML=d.profile.signature||'<em>No signature set</em>';
        }
    }catch(e){}
}

async function saveProfile(){
    const btn=document.getElementById('profBtn'),err=document.getElementById('profError');
    const name=document.getElementById('profName').value.trim();
    const bio=document.getElementById('profBio').value;
    const sig=document.getElementById('profSig').value;
    err.style.display='none';
    if(!name){err.textContent='Display name is required.';err.style.display='block';return;}
    btn.disabled=true;btn.textContent='Saving…';
    try{
        const d=await(await fetch('?action=update_profile',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({email:_em,name,bio,signature:sig})})).json();
        if(d.ok){
            _nm=d.name;
            document.getElementById('hName').textContent=d.name;
            document.getElementById('hAvatar').textContent=d.name.charAt(0).toUpperCase();
            const s=JSON.parse(localStorage.getItem('lab62_s')||'{}'); s.n=d.name; localStorage.setItem('lab62_s',JSON.stringify(s));
            // ⚠ VULNERABLE: render updated signature via innerHTML — fires XSS immediately on save
            document.getElementById('sigPreview').innerHTML=sig||'<em>No signature set</em>';
            toast('Profile saved! Your signature is now active on all your posts.');
        }else{err.textContent=d.message||'Save failed.';err.style.display='block';}
    }catch(e){err.textContent='Network error.';err.style.display='block';}
    btn.disabled=false;btn.textContent='Save Profile';
}

// ── Ask Question ──
async function submitQuestion(){
    const btn=document.getElementById('askBtn'),err=document.getElementById('askError');
    const title=document.getElementById('askTitle').value.trim();
    const body=document.getElementById('askBody').value.trim();
    const tags=document.getElementById('askTags').value.trim();
    err.style.display='none';
    if(!title||title.length<5){err.textContent='Title must be at least 5 characters.';err.style.display='block';return;}
    if(!body){err.textContent='Question body is required.';err.style.display='block';return;}
    btn.disabled=true;btn.textContent='Posting…';
    try{
        const d=await(await fetch('?action=ask_question',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({email:_em,name:_nm,title,body,tags})})).json();
        if(d.ok){
            document.getElementById('askTitle').value='';
            document.getElementById('askBody').value='';
            document.getElementById('askTags').value='';
            toast('Question posted!');
            showSection('question',d.id);
        }else{err.textContent=d.message||'Failed.';err.style.display='block';}
    }catch(e){err.textContent='Network error.';err.style.display='block';}
    btn.disabled=false;btn.textContent='Post Your Question';
}

// ── Post Answer ──
async function submitAnswer(qid){
    const btn=document.getElementById('ansBtn'),err=document.getElementById('ansError');
    const body=document.getElementById('ansBody').value.trim();
    err.style.display='none';
    if(!body){err.textContent='Answer cannot be empty.';err.style.display='block';return;}
    btn.disabled=true;btn.textContent='Posting…';
    try{
        const d=await(await fetch('?action=post_answer',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({email:_em,name:_nm,question_id:qid,body})})).json();
        if(d.ok){toast('Answer posted!');loadQuestion(qid);}
        else{err.textContent=d.message||'Failed.';err.style.display='block';}
    }catch(e){err.textContent='Network error.';err.style.display='block';}
    btn.disabled=false;btn.textContent='Post Your Answer';
}

// ── Login ──
async function submitLogin(e){
    e.preventDefault();
    const btn=document.getElementById('liBtn'),err=document.getElementById('liError');
    err.style.display='none';
    const email=document.getElementById('liEmail').value.trim(),pass=document.getElementById('liPassword').value;
    if(!email||!pass){err.textContent='Email and password required.';err.style.display='block';return;}
    btn.disabled=true;btn.textContent='Logging in…';
    try{
        const d=await(await fetch('?action=login',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({email,password:pass})})).json();
        if(d.ok){saveSession(d.name,d.email,d.reputation);showDash(d.name,d.email,d.reputation);}
        else{err.textContent=d.message||'Invalid credentials.';err.style.display='block';}
    }catch(ex){err.textContent='Network error.';err.style.display='block';}
    btn.disabled=false;btn.textContent='Log in';
}

// ── Register ──
async function submitRegister(e){
    e.preventDefault();
    const btn=document.getElementById('rgBtn'),err=document.getElementById('rgError');
    err.style.display='none';
    const name=document.getElementById('rgName').value.trim(),email=document.getElementById('rgEmail').value.trim(),pass=document.getElementById('rgPassword').value;
    if(!name||!email||!pass){err.textContent='All fields required.';err.style.display='block';return;}
    btn.disabled=true;btn.textContent='Creating account…';
    try{
        const d=await(await fetch('?action=register',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({name,email,password:pass})})).json();
        if(d.ok){saveSession(d.name,d.email,1);showDash(d.name,d.email,1);}
        else{err.textContent=d.message||'Registration failed.';err.style.display='block';}
    }catch(ex){err.textContent='Network error.';err.style.display='block';}
    btn.disabled=false;btn.textContent='Sign up';
}

// ── Init ──
(function(){
    const raw=localStorage.getItem('lab62_s');
    if(!raw)return;
    try{const s=JSON.parse(raw);if(s&&s.n&&s.e)showDash(s.n,s.e,s.r||1);}catch(e){}
})();
</script>
</body>
</html>
