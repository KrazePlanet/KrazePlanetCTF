<?php
/*
 * UPchieve — account registration with email verification.
 * Training lab (lab204). The /api/verify/confirm endpoint always reports
 * success:false; the client-side code trusts that flag to decide whether the
 * account is verified, so manipulating the response completes registration.
 */
$uri    = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

// -- API: (re)send the verification code --
if ($method === 'POST' && preg_match('#/api/verify/send/?$#', $uri)) {
    header('Content-Type: application/json');
    echo json_encode(['sent' => true]);
    exit;
}

// -- API: confirm the verification code --
if ($method === 'POST' && preg_match('#/api/verify/confirm/?$#', $uri)) {
    header('Content-Type: application/json');
    // Server-side the code never matches, so verification reports failure.
    echo json_encode(['success' => false, 'message' => 'The verification code you entered is incorrect or has expired.']);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Sign Up — UPchieve</title>
<style>
    *,*::before,*::after{box-sizing:border-box;margin:0;padding:0;}
    :root{
        --teal:#17b3a3; --teal-d:#0f8c80; --teal-dd:#0a6b61;
        --ink:#17303a; --muted:#6a7d85; --bg:#eef5f4; --card:#ffffff;
        --line:#e0ebe9; --accent:#ffb547; --danger:#e05260; --ok:#1f9d6b;
    }
    html,body{min-height:100vh;}
    body{
        font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,'Helvetica Neue',Arial,sans-serif;
        color:var(--ink); background:var(--bg); -webkit-font-smoothing:antialiased;
        display:flex; min-height:100vh;
    }

    /* -- Brand panel -- */
    .brand{
        width:42%; max-width:560px; min-width:360px;
        background:linear-gradient(160deg,var(--teal) 0%,var(--teal-d) 55%,var(--teal-dd) 100%);
        color:#fff; padding:48px 52px; position:relative; overflow:hidden;
        display:flex; flex-direction:column;
    }
    .brand .logo{display:flex;align-items:center;gap:10px;font-weight:800;font-size:1.35rem;letter-spacing:-.3px;}
    .brand .logo .mark{width:34px;height:34px;}
    .brand h1{font-size:2.05rem;line-height:1.18;margin-top:auto;font-weight:800;letter-spacing:-.5px;}
    .brand .lead{margin-top:14px;font-size:1rem;line-height:1.6;color:rgba(255,255,255,.88);max-width:400px;}
    .brand .illus{margin:30px 0;align-self:center;width:260px;max-width:80%;}
    .brand .quote{margin-top:auto;padding-top:26px;border-top:1px solid rgba(255,255,255,.2);font-size:.88rem;line-height:1.6;color:rgba(255,255,255,.92);}
    .brand .quote .who{margin-top:8px;font-size:.78rem;color:rgba(255,255,255,.7);}
    .brand .blob{position:absolute;border-radius:50%;background:rgba(255,255,255,.07);}
    .brand .blob.b1{width:260px;height:260px;top:-80px;right:-80px;}
    .brand .blob.b2{width:170px;height:170px;bottom:40px;right:-60px;background:rgba(255,255,255,.05);}

    /* -- Form panel -- */
    .panel{flex:1;display:flex;align-items:center;justify-content:center;padding:40px 24px;}
    .form-wrap{width:100%;max-width:440px;}
    .steps{display:flex;gap:8px;margin-bottom:28px;}
    .steps .seg{flex:1;height:5px;border-radius:3px;background:var(--line);transition:background .3s;}
    .steps .seg.on{background:var(--teal);}
    .form-wrap .eyebrow{font-size:.72rem;font-weight:700;letter-spacing:.09em;text-transform:uppercase;color:var(--teal-d);margin-bottom:6px;}
    .form-wrap h2{font-size:1.5rem;font-weight:800;letter-spacing:-.4px;}
    .form-wrap .sub{color:var(--muted);font-size:.92rem;margin-top:6px;margin-bottom:24px;line-height:1.5;}

    .field{margin-bottom:16px;}
    .field label{display:block;font-size:.82rem;font-weight:600;margin-bottom:6px;color:var(--ink);}
    .field input{
        width:100%;border:1.5px solid var(--line);border-radius:10px;padding:12px 14px;
        font-size:.95rem;color:var(--ink);background:#fff;transition:border-color .15s,box-shadow .15s;
    }
    .field input:focus{outline:none;border-color:var(--teal);box-shadow:0 0 0 3px rgba(23,179,163,.15);}
    .field input::placeholder{color:#a7b6b4;}

    .role-grid{display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:8px;}
    .role-card{
        border:1.5px solid var(--line);border-radius:12px;padding:18px 14px;cursor:pointer;text-align:center;
        transition:border-color .15s,background .15s,transform .1s;background:#fff;
    }
    .role-card:hover{border-color:var(--teal);transform:translateY(-2px);}
    .role-card.sel{border-color:var(--teal);background:rgba(23,179,163,.06);box-shadow:0 0 0 3px rgba(23,179,163,.12);}
    .role-card .ri{width:42px;height:42px;margin:0 auto 10px;color:var(--teal-d);}
    .role-card .rt{font-weight:700;font-size:.95rem;}
    .role-card .rd{font-size:.76rem;color:var(--muted);margin-top:4px;line-height:1.4;}

    .btn{
        width:100%;border:none;border-radius:10px;padding:13px;font-size:.98rem;font-weight:700;cursor:pointer;
        background:var(--teal);color:#fff;transition:background .15s,transform .1s;margin-top:8px;
        display:inline-flex;align-items:center;justify-content:center;gap:8px;
    }
    .btn:hover{background:var(--teal-d);}
    .btn:active{transform:translateY(1px);}
    .btn:disabled{opacity:.6;cursor:default;}
    .btn-ghost{background:transparent;color:var(--muted);border:1.5px solid var(--line);margin-top:10px;}
    .btn-ghost:hover{background:#f6faf9;color:var(--ink);}

    .row-split{display:flex;gap:10px;margin-top:4px;}
    .row-split .btn{margin-top:0;}
    .row-split .btn-ghost{flex:0 0 120px;}

    .foot{margin-top:22px;font-size:.84rem;color:var(--muted);text-align:center;}
    .foot a{color:var(--teal-d);font-weight:600;text-decoration:none;}

    .alert{display:none;background:#fde8ea;border:1px solid #f6c3c9;color:#a33240;border-radius:10px;padding:11px 14px;font-size:.85rem;margin-bottom:16px;}
    .alert.show{display:block;}

    /* OTP */
    .otp{display:flex;gap:10px;justify-content:space-between;margin:6px 0 4px;}
    .otp input{
        width:100%;aspect-ratio:1/1.1;text-align:center;font-size:1.4rem;font-weight:700;
        border:1.5px solid var(--line);border-radius:10px;color:var(--ink);
    }
    .otp input:focus{outline:none;border-color:var(--teal);box-shadow:0 0 0 3px rgba(23,179,163,.15);}
    .resend{font-size:.82rem;color:var(--muted);text-align:center;margin-top:16px;}
    .resend a{color:var(--teal-d);font-weight:600;cursor:pointer;text-decoration:none;}
    .verify-mail{width:58px;height:58px;color:var(--teal);margin-bottom:4px;}

    .hidden{display:none;}

    /* -- Welcome / dashboard -- */
    #welcome{display:none;width:100%;min-height:100vh;flex-direction:column;background:var(--bg);}
    .wl-top{background:#fff;border-bottom:1px solid var(--line);padding:0 28px;height:62px;display:flex;align-items:center;gap:10px;}
    .wl-top .logo{display:flex;align-items:center;gap:9px;font-weight:800;color:var(--teal-dd);font-size:1.05rem;}
    .wl-top .logo .mark{width:26px;height:26px;color:var(--teal);}
    .wl-top .spacer{flex:1;}
    .wl-top .who{display:flex;align-items:center;gap:9px;font-size:.86rem;color:var(--muted);}
    .wl-top .av{width:32px;height:32px;border-radius:50%;background:var(--teal);color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:.9rem;}
    .wl-logout{background:transparent;border:1.5px solid var(--line);color:var(--muted);border-radius:8px;padding:7px 13px;font-size:.8rem;font-weight:600;cursor:pointer;display:inline-flex;align-items:center;gap:6px;}
    .wl-logout:hover{border-color:var(--danger);color:var(--danger);}
    .wl-body{max-width:960px;width:100%;margin:0 auto;padding:34px 24px 60px;}
    .wl-hero{background:linear-gradient(135deg,var(--teal),var(--teal-d));color:#fff;border-radius:16px;padding:28px 30px;display:flex;align-items:center;gap:20px;}
    .wl-hero .badge{background:rgba(255,255,255,.2);border:1px solid rgba(255,255,255,.35);border-radius:999px;padding:5px 12px;font-size:.72rem;font-weight:700;letter-spacing:.04em;display:inline-flex;align-items:center;gap:6px;}
    .wl-hero h2{font-size:1.5rem;font-weight:800;margin-top:10px;letter-spacing:-.3px;}
    .wl-hero p{color:rgba(255,255,255,.9);font-size:.92rem;margin-top:6px;}
    .wl-hero .ill{width:120px;flex-shrink:0;}
    .wl-sec{margin-top:30px;}
    .wl-sec h3{font-size:1.02rem;font-weight:800;margin-bottom:4px;}
    .wl-sec .muted{color:var(--muted);font-size:.85rem;margin-bottom:16px;}
    .subj-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(150px,1fr));gap:14px;}
    .subj{background:#fff;border:1px solid var(--line);border-radius:12px;padding:18px 16px;transition:transform .12s,box-shadow .12s;cursor:pointer;}
    .subj:hover{transform:translateY(-2px);box-shadow:0 8px 22px rgba(10,107,97,.08);}
    .subj .ic{width:40px;height:40px;border-radius:10px;display:flex;align-items:center;justify-content:center;color:#fff;margin-bottom:10px;}
    .subj .nm{font-weight:700;font-size:.92rem;}
    .subj .co{font-size:.76rem;color:var(--muted);margin-top:2px;}
    .info-cards{display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:14px;margin-top:4px;}
    .info-card{background:#fff;border:1px solid var(--line);border-radius:12px;padding:18px;}
    .info-card .k{font-size:.72rem;text-transform:uppercase;letter-spacing:.06em;color:var(--muted);font-weight:700;}
    .info-card .v{font-size:1.5rem;font-weight:800;margin-top:6px;color:var(--teal-dd);}
    .info-card .s{font-size:.78rem;color:var(--muted);margin-top:2px;}

    @media(max-width:820px){
        body{flex-direction:column;}
        .brand{width:100%;max-width:none;min-width:0;padding:30px 28px;}
        .brand h1{font-size:1.6rem;margin-top:18px;}
        .brand .illus,.brand .quote{display:none;}
    }
</style>
</head>
<body>

<!-- BRAND PANEL -->
<aside class="brand">
    <span class="blob b1"></span><span class="blob b2"></span>
    <div class="logo">
        <svg class="mark" viewBox="0 0 40 40" fill="none"><path d="M20 4L6 30h8l6-11 6 11h8L20 4z" fill="#fff"/><circle cx="20" cy="31" r="3.4" fill="#ffb547"/></svg>
        UPchieve
    </div>

    <svg class="illus" viewBox="0 0 320 240" fill="none" xmlns="http://www.w3.org/2000/svg">
        <rect x="40" y="150" width="240" height="70" rx="12" fill="rgba(255,255,255,0.12)"/>
        <rect x="150" y="60" width="130" height="86" rx="10" fill="#ffffff"/>
        <rect x="164" y="78" width="80" height="8" rx="4" fill="#bfe8e3"/>
        <rect x="164" y="96" width="102" height="8" rx="4" fill="#d9f1ee"/>
        <rect x="164" y="114" width="64" height="8" rx="4" fill="#d9f1ee"/>
        <circle cx="92" cy="96" r="30" fill="#ffffff"/>
        <circle cx="92" cy="86" r="11" fill="#ffb547"/>
        <path d="M74 112c0-11 8-18 18-18s18 7 18 18" fill="#17b3a3"/>
        <path d="M70 52l24 10 24-10-24-10-24 10z" fill="#ffffff"/>
        <path d="M94 62v16" stroke="#ffffff" stroke-width="3" stroke-linecap="round"/>
        <circle cx="94" cy="80" r="3" fill="#ffb547"/>
        <circle cx="238" cy="176" r="6" fill="#ffb547"/>
        <circle cx="70" cy="178" r="5" fill="#bfe8e3"/>
    </svg>

    <h1>Free, online tutoring &amp; college counseling.</h1>
    <p class="lead">Join thousands of students getting academic support from vetted volunteer coaches &mdash; anytime, from anywhere.</p>

    <div class="quote">
        &ldquo;UPchieve helped me get unstuck on calculus at midnight before my exam. I honestly don't know what I'd have done without it.&rdquo;
        <div class="who">&mdash; Maya R., high school junior</div>
    </div>
</aside>

<!-- FORM PANEL -->
<main class="panel" id="panel">
    <div class="form-wrap">

        <div class="steps">
            <div class="seg on" data-seg="1"></div>
            <div class="seg" data-seg="2"></div>
            <div class="seg" data-seg="3"></div>
            <div class="seg" data-seg="4"></div>
        </div>

        <div class="alert" id="formAlert"></div>

        <!-- Step 1: role -->
        <section id="step1">
            <div class="eyebrow">Get started</div>
            <h2>How can we help?</h2>
            <p class="sub">Tell us why you're joining UPchieve so we can tailor your experience.</p>
            <div class="role-grid">
                <div class="role-card sel" data-role="student" onclick="pickRole('student',this)">
                    <svg class="ri" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M22 10L12 5 2 10l10 5 10-5z"/><path d="M6 12v5c0 1 2.7 2.5 6 2.5s6-1.5 6-2.5v-5"/></svg>
                    <div class="rt">I need a coach</div>
                    <div class="rd">Get free help with schoolwork &amp; college prep</div>
                </div>
                <div class="role-card" data-role="coach" onclick="pickRole('coach',this)">
                    <svg class="ri" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="8" r="4"/><path d="M4 21c0-4 3.6-6.5 8-6.5s8 2.5 8 6.5"/></svg>
                    <div class="rt">I'm a volunteer</div>
                    <div class="rd">Coach students in subjects you love</div>
                </div>
            </div>
            <button class="btn" onclick="toStep(2)">Continue</button>
            <div class="foot">Already have an account? <a href="#" onclick="return false;">Log in</a></div>
        </section>

        <!-- Step 2: account -->
        <section id="step2" class="hidden">
            <div class="eyebrow">Step 2 of 4</div>
            <h2>Create your account</h2>
            <p class="sub">Use an email you check often &mdash; we'll send a verification code there.</p>
            <div class="field">
                <label for="email">Email address</label>
                <input type="email" id="email" placeholder="you@example.com" autocomplete="email">
            </div>
            <div class="field">
                <label for="password">Password</label>
                <input type="password" id="password" placeholder="At least 8 characters" autocomplete="new-password">
            </div>
            <div class="row-split">
                <button class="btn btn-ghost" onclick="toStep(1)">Back</button>
                <button class="btn" onclick="toStep(3)">Continue</button>
            </div>
        </section>

        <!-- Step 3: profile -->
        <section id="step3" class="hidden">
            <div class="eyebrow">Step 3 of 4</div>
            <h2>A little about you</h2>
            <p class="sub">This helps your coach get to know you before your first session.</p>
            <div class="field">
                <label for="name">Full name</label>
                <input type="text" id="name" placeholder="Alex Johnson" autocomplete="name">
            </div>
            <div class="field">
                <label for="phone">Mobile number</label>
                <input type="tel" id="phone" placeholder="(555) 123-4567" autocomplete="tel">
            </div>
            <div class="row-split">
                <button class="btn btn-ghost" onclick="toStep(2)">Back</button>
                <button class="btn" id="signupBtn" onclick="requestCode()">Sign up</button>
            </div>
        </section>

        <!-- Step 4: verify -->
        <section id="step4" class="hidden" style="text-align:center;">
            <svg class="verify-mail" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><rect x="2.5" y="4.5" width="19" height="15" rx="2.5"/><path d="M3 6l9 7 9-7"/></svg>
            <div class="eyebrow">Step 4 of 4</div>
            <h2>Verify your email</h2>
            <p class="sub">We sent a 6-digit code to <strong id="verifyEmail">your email</strong>. Enter it below to finish creating your account.</p>
            <div class="otp" id="otp">
                <input maxlength="1" inputmode="numeric"><input maxlength="1" inputmode="numeric"><input maxlength="1" inputmode="numeric">
                <input maxlength="1" inputmode="numeric"><input maxlength="1" inputmode="numeric"><input maxlength="1" inputmode="numeric">
            </div>
            <button class="btn" id="verifyBtn" onclick="confirmCode()">Verify &amp; create account</button>
            <div class="resend">Didn't get it? <a onclick="requestCode(true)">Resend code</a></div>
            <button class="btn btn-ghost" onclick="toStep(3)">Back</button>
        </section>

    </div>
</main>

<!-- WELCOME / DASHBOARD -->
<div id="welcome">
    <div class="wl-top">
        <div class="logo">
            <svg class="mark" viewBox="0 0 40 40" fill="none"><path d="M20 4L6 30h8l6-11 6 11h8L20 4z" fill="currentColor"/><circle cx="20" cy="31" r="3.4" fill="#ffb547"/></svg>
            UPchieve
        </div>
        <div class="spacer"></div>
        <div class="who">
            <div class="av" id="wlAvatar">A</div>
            <span id="wlWho">Alex</span>
        </div>
        <button class="wl-logout" onclick="logout()">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="M16 17l5-5-5-5M21 12H9"/></svg>
            Log out
        </button>
    </div>

    <div class="wl-body">
        <div class="wl-hero">
            <div style="flex:1;">
                <span class="badge">&check; Email verified</span>
                <h2 id="wlGreeting">Welcome to UPchieve!</h2>
                <p>Your account is all set. Pick a subject below to connect with a free volunteer coach &mdash; usually in under a minute.</p>
            </div>
            <svg class="ill" viewBox="0 0 120 120" fill="none"><circle cx="60" cy="60" r="54" fill="rgba(255,255,255,0.12)"/><path d="M38 64l16 16 30-34" stroke="#fff" stroke-width="7" stroke-linecap="round" stroke-linejoin="round"/></svg>
        </div>

        <div class="wl-sec">
            <h3>Get help now</h3>
            <div class="muted">Choose a subject to request a coach.</div>
            <div class="subj-grid" id="subjGrid"></div>
        </div>

        <div class="wl-sec">
            <h3>Your account</h3>
            <div class="muted">A snapshot of your new UPchieve profile.</div>
            <div class="info-cards">
                <div class="info-card"><div class="k">Sessions available</div><div class="v">Unlimited</div><div class="s">Free, always</div></div>
                <div class="info-card"><div class="k">Coaches online</div><div class="v">214</div><div class="s">Ready to help</div></div>
                <div class="info-card"><div class="k">Avg. wait</div><div class="v">47s</div><div class="s">To get matched</div></div>
                <div class="info-card"><div class="k">Member since</div><div class="v" id="wlSince">Today</div><div class="s">Welcome aboard</div></div>
            </div>
        </div>
    </div>
</div>

<script>
(function(){
    var data = { role:'student', email:'', password:'', name:'', phone:'' };

    var panel   = document.getElementById('panel');
    var welcome = document.getElementById('welcome');
    var alertEl = document.getElementById('formAlert');

    function showAlert(msg){ alertEl.textContent = msg; alertEl.classList.add('show'); }
    function clearAlert(){ alertEl.classList.remove('show'); }

    window.pickRole = function(role, el){
        data.role = role;
        document.querySelectorAll('.role-card').forEach(function(c){ c.classList.remove('sel'); });
        el.classList.add('sel');
    };

    function setSteps(n){
        document.querySelectorAll('.steps .seg').forEach(function(s){
            s.classList.toggle('on', parseInt(s.dataset.seg,10) <= n);
        });
    }

    window.toStep = function(n){
        clearAlert();
        if (n === 3) {
            var email = document.getElementById('email').value.trim();
            var pass  = document.getElementById('password').value;
            if (!/^[^@\s]+@[^@\s]+\.[^@\s]+$/.test(email)) { showAlert('Please enter a valid email address.'); return; }
            if (pass.length < 8) { showAlert('Password must be at least 8 characters.'); return; }
            data.email = email; data.password = pass;
        }
        for (var i=1;i<=4;i++){ document.getElementById('step'+i).classList.add('hidden'); }
        document.getElementById('step'+n).classList.remove('hidden');
        setSteps(n);
    };

    window.requestCode = function(isResend){
        if (!isResend) {
            var name  = document.getElementById('name').value.trim();
            var phone = document.getElementById('phone').value.trim();
            if (!name)  { showAlert('Please enter your name.'); return; }
            if (!phone) { showAlert('Please enter your mobile number.'); return; }
            data.name = name; data.phone = phone;
        }
        var btn = document.getElementById('signupBtn');
        if (btn && !isResend) { btn.disabled = true; btn.textContent = 'Sending code...'; }

        fetch('/api/verify/send', {
            method:'POST', headers:{'Content-Type':'application/json'},
            body: JSON.stringify({ email: data.email })
        }).then(function(r){ return r.json(); }).then(function(){
            document.getElementById('verifyEmail').textContent = data.email;
            if (!isResend) toStep(4);
            document.querySelectorAll('#otp input').forEach(function(i){ i.value=''; });
            document.querySelector('#otp input').focus();
        }).catch(function(){ showAlert('Could not send code. Please try again.'); })
        .finally(function(){ if (btn) { btn.disabled=false; btn.textContent='Sign up'; } });
    };

    window.confirmCode = function(){
        clearAlert();
        var code = [].slice.call(document.querySelectorAll('#otp input')).map(function(i){ return i.value; }).join('');
        if (code.length !== 6) { showAlert('Enter the 6-digit code from your email.'); return; }

        var btn = document.getElementById('verifyBtn');
        btn.disabled = true; btn.textContent = 'Verifying...';

        fetch('/api/verify/confirm', {
            method:'POST', headers:{'Content-Type':'application/json'},
            body: JSON.stringify({ email: data.email, code: code })
        })
        .then(function(r){ return r.json(); })
        .then(function(res){
            if (res.success === true) {
                showWelcome();
            } else {
                showAlert(res.message || 'The verification code you entered is incorrect.');
                document.querySelectorAll('#otp input').forEach(function(i){ i.value=''; });
                document.querySelector('#otp input').focus();
            }
        })
        .catch(function(){ showAlert('Network error. Please try again.'); })
        .finally(function(){ btn.disabled=false; btn.textContent='Verify & create account'; });
    };

    function showWelcome(){
        var first = (data.name || 'there').split(' ')[0];
        document.getElementById('wlWho').textContent = first;
        document.getElementById('wlAvatar').textContent = (first[0]||'A').toUpperCase();
        document.getElementById('wlGreeting').textContent = 'Welcome to UPchieve, ' + first + '!';
        document.getElementById('wlSince').textContent =
            new Date().toLocaleDateString('en-US',{month:'short',day:'numeric',year:'numeric'});
        renderSubjects();
        panel.style.display = 'none';
        document.querySelector('.brand').style.display = 'none';
        welcome.style.display = 'flex';
        window.scrollTo(0,0);
    }

    window.logout = function(){
        welcome.style.display = 'none';
        document.querySelector('.brand').style.display = '';
        panel.style.display = 'flex';
        ['email','password','name','phone'].forEach(function(id){ var el=document.getElementById(id); if(el) el.value=''; });
        document.querySelectorAll('#otp input').forEach(function(i){ i.value=''; });
        toStep(1);
        window.scrollTo(0,0);
    };

    function renderSubjects(){
        var subjects = [
            {n:'Algebra',     c:'#17b3a3', p:'38 coaches online'},
            {n:'Geometry',    c:'#3b82f6', p:'22 coaches online'},
            {n:'Calculus',    c:'#8b5cf6', p:'15 coaches online'},
            {n:'Biology',     c:'#10b981', p:'27 coaches online'},
            {n:'Chemistry',   c:'#f59e0b', p:'19 coaches online'},
            {n:'Physics',     c:'#ef4444', p:'12 coaches online'},
            {n:'Essays',      c:'#ec4899', p:'31 coaches online'},
            {n:'College Prep',c:'#0ea5e9', p:'24 coaches online'}
        ];
        var icon = '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2"><path d="M12 6.5C10.5 5 8 4.5 5 5v13c3-.5 5.5 0 7 1.5 1.5-1.5 4-2 7-1.5V5c-3-.5-5.5 0-7 1.5z"/></svg>';
        document.getElementById('subjGrid').innerHTML = subjects.map(function(s){
            return '<div class="subj"><div class="ic" style="background:'+s.c+'">'+icon+'</div>'+
                   '<div class="nm">'+s.n+'</div><div class="co">'+s.p+'</div></div>';
        }).join('');
    }

    // OTP box UX
    var boxes = document.querySelectorAll('#otp input');
    boxes.forEach(function(b,i){
        b.addEventListener('input',function(){ b.value=b.value.replace(/\D/,''); if(b.value&&i<boxes.length-1) boxes[i+1].focus(); });
        b.addEventListener('keydown',function(e){ if(e.key==='Backspace'&&!b.value&&i>0) boxes[i-1].focus(); });
        b.addEventListener('paste',function(e){
            e.preventDefault();
            var d=(e.clipboardData.getData('text')||'').replace(/\D/g,'').slice(0,6);
            boxes.forEach(function(x,j){ x.value=d[j]||''; }); if(d.length) boxes[Math.min(d.length,5)].focus();
        });
    });
})();
</script>
</body>
</html>
