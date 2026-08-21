<?php
// auth_modals.php: Unified Responsive Auth Dialogs for KrazePlanet with Perfectly Framed 9:16 Characters & Ambient Full-Modal Glow

$auth_themes = [
    '1.jpg'  => ['primary' => '#f43f5e', 'hover' => '#e11d48', 'grad_start' => '#e11d48', 'grad_end' => '#be123c', 'glow' => 'rgba(244, 63, 94, 0.45)', 'border' => 'rgba(244, 63, 94, 0.4)', 'text' => '#fb7185'], // Deadpool Crimson
    '2.jpg'  => ['primary' => '#f59e0b', 'hover' => '#d97706', 'grad_start' => '#d97706', 'grad_end' => '#b45309', 'glow' => 'rgba(245, 158, 11, 0.45)', 'border' => 'rgba(245, 158, 11, 0.4)', 'text' => '#fbbf24'], // Strange Amber
    '3.jpg'  => ['primary' => '#fb923c', 'hover' => '#f97316', 'grad_start' => '#f97316', 'grad_end' => '#c2410c', 'glow' => 'rgba(251, 146, 60, 0.45)', 'border' => 'rgba(251, 146, 60, 0.4)', 'text' => '#fdba74'], // Flame Orange
    '4.jpg'  => ['primary' => '#e11d48', 'hover' => '#be123c', 'grad_start' => '#e11d48', 'grad_end' => '#9f1239', 'glow' => 'rgba(225, 29, 72, 0.5)', 'border' => 'rgba(225, 29, 72, 0.45)', 'text' => '#fda4af'],  // Ruby Red Deadpool
    '5.jpg'  => ['primary' => '#a855f7', 'hover' => '#9333ea', 'grad_start' => '#9333ea', 'grad_end' => '#7e22ce', 'glow' => 'rgba(168, 85, 247, 0.45)', 'border' => 'rgba(168, 85, 247, 0.4)', 'text' => '#c084fc'], // Electric Purple
    '6.jpg'  => ['primary' => '#38bdf8', 'hover' => '#0284c7', 'grad_start' => '#0284c7', 'grad_end' => '#0369a1', 'glow' => 'rgba(56, 189, 248, 0.45)', 'border' => 'rgba(56, 189, 248, 0.4)', 'text' => '#38bdf8'], // Cyber Cyan
    '7.jpg'  => ['primary' => '#f97316', 'hover' => '#ea580c', 'grad_start' => '#ea580c', 'grad_end' => '#9a3412', 'glow' => 'rgba(249, 115, 22, 0.45)', 'border' => 'rgba(249, 115, 22, 0.4)', 'text' => '#fdba74'], // Solar Flare
    '8.jpg'  => ['primary' => '#10b981', 'hover' => '#059669', 'grad_start' => '#10b981', 'grad_end' => '#047857', 'glow' => 'rgba(16, 185, 129, 0.45)', 'border' => 'rgba(16, 185, 129, 0.4)', 'text' => '#34d399'], // Emerald Matrix
    '9.jpg'  => ['primary' => '#f43f5e', 'hover' => '#e11d48', 'grad_start' => '#e11d48', 'grad_end' => '#be123c', 'glow' => 'rgba(244, 63, 94, 0.45)', 'border' => 'rgba(244, 63, 94, 0.4)', 'text' => '#fb7185'], // Crimson
    '10.jpg' => ['primary' => '#06b6d4', 'hover' => '#0891b2', 'grad_start' => '#0891b2', 'grad_end' => '#0e7490', 'glow' => 'rgba(6, 182, 212, 0.45)', 'border' => 'rgba(6, 182, 212, 0.4)', 'text' => '#22d3ee']  // Ice Aqua
];

$auth_available_images = array_keys($auth_themes);
$initial_auth_img = $auth_available_images[array_rand($auth_available_images)];
$initial_img_src = '/model/images/' . $initial_auth_img;
$initial_theme = $auth_themes[$initial_auth_img];
?>

<style>
  /* Dynamic CSS Custom Properties for Auth Modals */
  :root {
    --auth-theme-primary: <?php echo $initial_theme['primary']; ?>;
    --auth-theme-hover: <?php echo $initial_theme['hover']; ?>;
    --auth-theme-grad-start: <?php echo $initial_theme['grad_start']; ?>;
    --auth-theme-grad-end: <?php echo $initial_theme['grad_end']; ?>;
    --auth-theme-glow: <?php echo $initial_theme['glow']; ?>;
    --auth-theme-border: <?php echo $initial_theme['border']; ?>;
    --auth-theme-text: <?php echo $initial_theme['text']; ?>;
  }

  .auth-modal .modal-content {
    background: #070b14;
    border: 1px solid var(--auth-theme-border);
    border-radius: 20px;
    box-shadow: 0 0 45px var(--auth-theme-glow), 0 25px 50px -12px rgba(0, 0, 0, 0.9);
    overflow: hidden;
    position: relative;
    transition: border-color 0.4s ease, box-shadow 0.4s ease;
  }

  .auth-modal-unified {
    position: relative;
    width: 100%;
    min-height: 520px;
    display: flex;
    overflow: hidden;
    background: #070b14;
  }

  /* 1. Ambient Blurred Glow Layer across the whole card */
  .auth-ambient-backdrop {
    position: absolute;
    top: -15%;
    left: -15%;
    width: 130%;
    height: 130%;
    object-fit: cover;
    filter: blur(55px) saturate(200%) opacity(0.28);
    pointer-events: none;
    z-index: 1;
    transition: opacity 0.4s ease;
  }

  /* 2. Left side character framing (50% card width, perfect 9:16 vertical character cover) */
  .auth-character-frame {
    position: absolute;
    top: 0;
    left: 0;
    width: 50%;
    height: 100%;
    overflow: hidden;
    z-index: 2;
    pointer-events: none;
    display: flex;
    align-items: center;
    justify-content: center;
  }

  .auth-character-hero-img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    object-position: center 12%;
    -webkit-mask-image: linear-gradient(to right, rgba(0, 0, 0, 1) 0%, rgba(0, 0, 0, 1) 68%, rgba(0, 0, 0, 0) 100%),
                        linear-gradient(to top, rgba(0, 0, 0, 0) 0%, rgba(0, 0, 0, 1) 18%, rgba(0, 0, 0, 1) 100%);
    mask-image: linear-gradient(to right, rgba(0, 0, 0, 1) 0%, rgba(0, 0, 0, 1) 68%, rgba(0, 0, 0, 0) 100%),
                linear-gradient(to top, rgba(0, 0, 0, 0) 0%, rgba(0, 0, 0, 1) 18%, rgba(0, 0, 0, 1) 100%);
    -webkit-mask-composite: source-in;
    mask-composite: intersect;
    filter: brightness(1.06) contrast(1.04);
    transition: transform 0.4s ease;
  }

  .auth-modal-unified:hover .auth-character-hero-img {
    transform: scale(1.02);
  }

  /* 3. Form Container layered seamlessly on the right */
  .auth-form-container {
    position: relative;
    z-index: 3;
    width: 56%;
    margin-left: auto;
    padding: 36px 36px 32px 24px;
    display: flex;
    flex-direction: column;
    justify-content: center;
  }

  .auth-modal-unified .btn-close-auth {
    position: absolute;
    top: 20px;
    right: 20px;
    z-index: 10;
    background: rgba(255, 255, 255, 0.08);
    border: 1px solid rgba(255, 255, 255, 0.12);
    color: #94a3b8;
    width: 32px;
    height: 32px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    font-size: 16px;
    transition: all 0.2s ease;
  }
  .auth-modal-unified .btn-close-auth:hover {
    background: rgba(255, 255, 255, 0.2);
    color: #fff;
    transform: scale(1.1);
  }

  .auth-form-title {
    font-size: 26px;
    font-weight: 800;
    color: #ffffff;
    letter-spacing: -0.5px;
    margin-bottom: 4px;
  }
  .auth-form-subtitle {
    font-size: 13px;
    color: #94a3b8;
    margin-bottom: 20px;
  }
  .auth-theme-tag {
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 1px;
    color: var(--auth-theme-primary);
  }

  /* Form Inputs with subtle glassmorphism */
  .auth-form-container .form-control,
  .auth-form-container .form-select {
    background: rgba(15, 23, 42, 0.82) !important;
    border: 1px solid rgba(255, 255, 255, 0.12) !important;
    color: #f8fafc !important;
    border-radius: 10px;
    font-size: 14px;
    padding: 10px 14px;
    transition: all 0.2s ease;
  }
  .auth-form-container .form-control:focus,
  .auth-form-container .form-select:focus {
    border-color: var(--auth-theme-primary) !important;
    box-shadow: 0 0 12px var(--auth-theme-glow) !important;
    background: rgba(15, 23, 42, 0.95) !important;
  }
  .auth-form-container .form-control::placeholder {
    color: #475569 !important;
    font-size: 13px;
  }

  .auth-theme-btn {
    background: linear-gradient(135deg, var(--auth-theme-grad-start) 0%, var(--auth-theme-grad-end) 100%) !important;
    border: 1px solid var(--auth-theme-border) !important;
    color: #ffffff !important;
    border-radius: 12px !important;
    box-shadow: 0 4px 20px var(--auth-theme-glow) !important;
    font-weight: 700 !important;
    letter-spacing: 0.3px;
    transition: all 0.3s ease !important;
  }
  .auth-theme-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px var(--auth-theme-glow) !important;
    filter: brightness(1.1);
  }

  .auth-theme-link {
    color: var(--auth-theme-text) !important;
    text-decoration: none;
    font-weight: 600;
    transition: color 0.2s ease;
  }
  .auth-theme-link:hover {
    text-decoration: underline;
    filter: brightness(1.2);
  }

  /* 6-Digit OTP Boxes (FOFA / CodeShack Design) */
  .otp-inputs {
    display: flex;
    gap: 8px;
    justify-content: center;
    margin: 12px 0 20px;
  }
  .otp-digit-box {
    width: 46px;
    height: 54px;
    text-align: center;
    font-size: 22px;
    font-weight: 800;
    border: 2px solid rgba(255, 255, 255, 0.14);
    background: rgba(15, 23, 42, 0.85);
    color: #ffffff;
    border-radius: 10px;
    outline: none;
    transition: all 0.2s ease;
    caret-color: var(--auth-theme-primary);
  }
  .otp-digit-box:focus {
    border-color: var(--auth-theme-primary);
    box-shadow: 0 0 16px var(--auth-theme-glow);
    background: rgba(30, 41, 59, 0.95);
  }
  .otp-digit-box.error-box {
    border-color: #ef4444;
    background: rgba(239, 68, 68, 0.12);
  }

  /* Mobile Responsiveness */
  @media (max-width: 767.98px) {
    .auth-character-frame {
      display: none !important;
    }
    .auth-form-container {
      width: 100% !important;
      padding: 30px 24px !important;
    }
    .otp-digit-box {
      width: 40px;
      height: 48px;
      font-size: 18px;
    }
  }
</style>

<!-- ==========================================
     1. LOGIN MODAL
     ========================================== -->
<div class="modal fade auth-modal" id="loginModal" tabindex="-1" aria-labelledby="loginModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" style="max-width: 820px;">
    <div class="modal-content">
      <div class="auth-modal-unified">
        
        <!-- Ambient Blurred Backdrop for Full-Box Colors -->
        <img src="<?php echo $initial_img_src; ?>" alt="Background Glow" class="auth-ambient-backdrop">

        <!-- Crisp Character Hero Framed on the Left -->
        <div class="auth-character-frame">
          <img src="<?php echo $initial_img_src; ?>" alt="KrazePlanet Character" class="auth-character-hero-img">
        </div>

        <!-- Right Side Form Overlay -->
        <div class="auth-form-container">
          <div class="d-flex align-items-center gap-2 mb-2">
            <img src="https://krazeplanet.com/favicon.png" alt="Logo" style="height: 22px; width: 22px; object-fit: contain;">
            <span class="auth-theme-tag">Welcome Back</span>
          </div>
          
          <h2 class="auth-form-title">Sign In</h2>
          <p class="auth-form-subtitle">Enter your credentials to access your labs and challenges.</p>

          <div id="loginAlert" style="display:none;" class="alert py-2 px-3 small mb-3 border-0"></div>

          <form id="loginForm" onsubmit="handleLoginSubmit(event)">
            <div class="mb-3">
              <label class="form-label small fw-semibold text-light mb-1">Username or Email</label>
              <div class="input-group">
                <span class="input-group-text bg-dark border-secondary border-opacity-25 text-secondary"><i class="bi bi-person-fill"></i></span>
                <input type="text" id="loginUsername" class="form-control" placeholder="Enter username or email" required autofocus style="border-top-left-radius: 0; border-bottom-left-radius: 0;">
              </div>
            </div>

            <div class="mb-3">
              <div class="d-flex justify-content-between align-items-center mb-1">
                <label class="form-label small fw-semibold text-light mb-0">Password</label>
                <a href="#" onclick="switchModal('loginModal', 'forgotModal')" class="small auth-theme-link text-decoration-none">Forgot Password?</a>
              </div>
              <div class="input-group">
                <span class="input-group-text bg-dark border-secondary border-opacity-25 text-secondary"><i class="bi bi-lock-fill"></i></span>
                <input type="password" id="loginPassword" class="form-control" placeholder="Enter password" required style="border-top-left-radius: 0; border-bottom-left-radius: 0;">
              </div>
            </div>

            <button type="submit" class="btn btn-primary w-100 py-3 mb-3 fw-bold auth-theme-btn" id="loginSubmitBtn">
              <i class="bi bi-box-arrow-in-right me-1"></i> Sign In &rarr;
            </button>

            <p class="text-center small text-secondary mb-0">
              Don't have an account? 
              <a href="#" onclick="switchModal('loginModal', 'signupModal')" class="auth-theme-link fw-semibold">Create one now</a>
            </p>
          </form>
        </div>

        <button type="button" class="btn-close-auth" data-bs-dismiss="modal" aria-label="Close">&times;</button>
      </div>
    </div>
  </div>
</div>

<!-- ==========================================
     2. CREATE ACCOUNT MODAL (STEP 1: DETAILS)
     ========================================== -->
<div class="modal fade auth-modal" id="signupModal" tabindex="-1" aria-labelledby="signupModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" style="max-width: 820px;">
    <div class="modal-content">
      <div class="auth-modal-unified">
        
        <!-- Ambient Blurred Backdrop for Full-Box Colors -->
        <img src="<?php echo $initial_img_src; ?>" alt="Background Glow" class="auth-ambient-backdrop">

        <!-- Crisp Character Hero Framed on the Left -->
        <div class="auth-character-frame">
          <img src="<?php echo $initial_img_src; ?>" alt="KrazePlanet Character" class="auth-character-hero-img">
        </div>

        <!-- Right Side Registration Form Overlay -->
        <div class="auth-form-container">
          <div class="d-flex align-items-center gap-2 mb-1">
            <img src="https://krazeplanet.com/favicon.png" alt="Logo" style="height: 22px; width: 22px; object-fit: contain;">
            <span class="auth-theme-tag">Join KrazePlanet</span>
          </div>
          
          <h2 class="auth-form-title">Create Account</h2>
          <p class="auth-form-subtitle mb-3">Sign up to track your CTF progress & hands-on assignments.</p>

          <div id="signupAlert" style="display:none;" class="alert py-2 px-3 small mb-2 border-0"></div>

          <form id="signupForm" onsubmit="handleSignupSubmit(event)">
            <div class="row g-2 mb-2">
              <div class="col-6">
                <label class="form-label small fw-semibold text-light mb-1">Username <span class="text-danger">*</span></label>
                <input type="text" id="signupUsername" class="form-control" placeholder="cyber_hunter" minlength="3" required>
              </div>
              <div class="col-6">
                <label class="form-label small fw-semibold text-light mb-1">Full Name <span class="text-danger">*</span></label>
                <input type="text" id="signupFullname" class="form-control" placeholder="John Doe" required>
              </div>
            </div>

            <div class="mb-2">
              <label class="form-label small fw-semibold text-light mb-1">Email Address <span class="text-danger">*</span></label>
              <input type="email" id="signupEmail" class="form-control" placeholder="you@domain.com" required>
            </div>

            <div class="row g-2 mb-3">
              <div class="col-6">
                <label class="form-label small fw-semibold text-light mb-1">Password <span class="text-danger">*</span></label>
                <input type="password" id="signupPassword" class="form-control" placeholder="••••••••" minlength="6" required>
              </div>
              <div class="col-6">
                <label class="form-label small fw-semibold text-light mb-1">Confirm <span class="text-danger">*</span></label>
                <input type="password" id="signupConfirmPassword" class="form-control" placeholder="••••••••" minlength="6" required>
              </div>
            </div>

            <button type="submit" class="btn btn-primary w-100 py-3 mb-2 fw-bold auth-theme-btn" id="signupSubmitBtn">
              <i class="bi bi-person-plus-fill me-1"></i> Create Account &rarr;
            </button>

            <p class="text-center small text-secondary mb-0">
              Already have an account? 
              <a href="#" onclick="switchModal('signupModal', 'loginModal')" class="auth-theme-link fw-semibold">Sign in</a>
            </p>
          </form>
        </div>

        <button type="button" class="btn-close-auth" data-bs-dismiss="modal" aria-label="Close">&times;</button>
      </div>
    </div>
  </div>
</div>

<!-- ==========================================
     3. VERIFICATION CODE MODAL (STEP 2: OTP)
     ========================================== -->
<div class="modal fade auth-modal" id="signupVerifyModal" tabindex="-1" aria-labelledby="signupVerifyModalLabel" aria-hidden="true" data-bs-backdrop="static">
  <div class="modal-dialog modal-dialog-centered" style="max-width: 820px;">
    <div class="modal-content">
      <div class="auth-modal-unified">
        
        <!-- Ambient Blurred Backdrop -->
        <img src="<?php echo $initial_img_src; ?>" alt="Background Glow" class="auth-ambient-backdrop">

        <!-- Crisp Character Hero -->
        <div class="auth-character-frame">
          <img src="<?php echo $initial_img_src; ?>" alt="KrazePlanet Character" class="auth-character-hero-img">
        </div>

        <!-- Form Overlay -->
        <div class="auth-form-container">
          <div class="d-flex align-items-center gap-2 mb-1">
            <i class="bi bi-envelope-check-fill auth-theme-tag fs-5"></i>
            <span class="auth-theme-tag">Email Verification</span>
          </div>
          
          <h2 class="auth-form-title">Verify Your Email</h2>
          <p class="auth-form-subtitle mb-3">
            We sent a 6-digit code to <strong id="signupVerifyEmailDisplay" style="color: var(--auth-theme-primary);">you@domain.com</strong>
          </p>

          <div id="signupVerifyAlert" style="display:none;" class="alert py-2 px-3 small mb-3 border-0"></div>

          <form id="signupVerifyForm" onsubmit="handleSignupVerifySubmit(event)">
            
            <div class="otp-inputs mb-3">
              <input type="text" maxlength="1" class="otp-digit-box" inputmode="numeric" pattern="[0-9]*" autocomplete="off" id="regOtp1">
              <input type="text" maxlength="1" class="otp-digit-box" inputmode="numeric" pattern="[0-9]*" autocomplete="off" id="regOtp2">
              <input type="text" maxlength="1" class="otp-digit-box" inputmode="numeric" pattern="[0-9]*" autocomplete="off" id="regOtp3">
              <input type="text" maxlength="1" class="otp-digit-box" inputmode="numeric" pattern="[0-9]*" autocomplete="off" id="regOtp4">
              <input type="text" maxlength="1" class="otp-digit-box" inputmode="numeric" pattern="[0-9]*" autocomplete="off" id="regOtp5">
              <input type="text" maxlength="1" class="otp-digit-box" inputmode="numeric" pattern="[0-9]*" autocomplete="off" id="regOtp6">
            </div>

            <button type="submit" class="btn btn-primary w-100 py-3 mb-3 fw-bold auth-theme-btn" id="regVerifySubmitBtn">
              <i class="bi bi-shield-check me-1"></i> Verify Email &rarr;
            </button>

            <div class="text-center small text-secondary mb-2">
              Did not receive it? 
              <button type="button" class="btn btn-link p-0 auth-theme-link fw-semibold" id="regResendBtn" onclick="handleResendSignupOtp()">
                Resend Code
              </button>
            </div>

            <div class="text-center">
              <a href="#" onclick="switchModal('signupVerifyModal', 'signupModal')" class="small text-secondary text-decoration-none hover-white">
                &larr; Edit Registration Details
              </a>
            </div>
          </form>
        </div>

        <button type="button" class="btn-close-auth" data-bs-dismiss="modal" aria-label="Close">&times;</button>
      </div>
    </div>
  </div>
</div>

<!-- ==========================================
     4. KRAZEPLANET INSTANCE SANDBOX LAUNCHER MODAL
     ========================================== -->
<div class="modal fade" id="krazeSandboxModal" tabindex="-1" aria-hidden="true" style="backdrop-filter: blur(8px); z-index: 100000;">
  <div class="modal-dialog modal-dialog-centered" style="max-width: 500px;">
    <div class="modal-content border-0 shadow-lg" style="background: #0f172a; border-radius: 18px; border: 1px solid rgba(56, 189, 248, 0.3); color: #f8fafc;">
      <div class="modal-body p-4 text-center">
        <div class="mb-3">
          <span class="spinner-border text-info" style="width: 3.5rem; height: 3.5rem; border-width: 0.3em;" role="status"></span>
        </div>
        <h4 class="fw-bold text-white mb-1">Spinning Up Private Sandbox</h4>
        <p class="text-secondary small mb-3" id="krazeSandboxLabTitle">Preparing your dedicated micro-container environment...</p>
        
        <div class="progress mb-3" style="height: 6px; background: rgba(255,255,255,0.1); border-radius: 10px;">
          <div class="progress-bar progress-bar-striped progress-bar-animated bg-info" style="width: 100%;"></div>
        </div>

        <div class="p-3 rounded-3 text-start mb-3" style="background: rgba(0,0,0,0.35); border: 1px solid rgba(255,255,255,0.06); font-family: monospace; font-size: 12px;">
          <div class="text-success"><i class="bi bi-check-circle-fill me-1"></i> Isolated container spawned</div>
          <div class="text-info"><i class="bi bi-arrow-repeat me-1"></i> Configuring subdomains routing</div>
          <div class="text-secondary"><i class="bi bi-clock-history me-1"></i> Auto-destruct timer: 1 hour</div>
        </div>

        <p class="text-muted small mb-0" style="font-size: 11px;">Redirecting automatically in milliseconds...</p>
      </div>
    </div>
  </div>
</div>

<!-- ==========================================
     5. FORGOT PASSWORD MODAL (STEP 1: EMAIL)
     ========================================== -->
<div class="modal fade auth-modal" id="forgotModal" tabindex="-1" aria-labelledby="forgotModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" style="max-width: 820px;">
    <div class="modal-content">
      <div class="auth-modal-unified">
        
        <!-- Ambient Blurred Backdrop -->
        <img src="<?php echo $initial_img_src; ?>" alt="Background Glow" class="auth-ambient-backdrop">

        <!-- Crisp Character Hero -->
        <div class="auth-character-frame">
          <img src="<?php echo $initial_img_src; ?>" alt="KrazePlanet Character" class="auth-character-hero-img">
        </div>

        <div class="auth-form-container">
          <div class="d-flex align-items-center gap-2 mb-2">
            <i class="bi bi-key-fill auth-theme-tag fs-5"></i>
            <span class="auth-theme-tag">Account Recovery</span>
          </div>
          
          <h2 class="auth-form-title">Reset Password</h2>
          <p class="auth-form-subtitle">Enter your registered username or email to receive a reset code.</p>

          <div id="forgotAlert" style="display:none;" class="alert py-2 px-3 small mb-3 border-0"></div>

          <form id="forgotForm" onsubmit="handleForgotSendOtp(event)">
            <div class="mb-3">
              <label class="form-label small fw-semibold text-light mb-1">Username or Email</label>
              <div class="input-group">
                <span class="input-group-text bg-dark border-secondary border-opacity-25 text-secondary"><i class="bi bi-envelope-fill"></i></span>
                <input type="text" id="forgotLoginInput" class="form-control" placeholder="Enter username or email" required autofocus>
              </div>
            </div>

            <button type="submit" class="btn btn-primary w-100 py-3 mb-3 fw-bold auth-theme-btn" id="forgotSubmitBtn">
              <i class="bi bi-send-fill me-1"></i> Send Recovery Code &rarr;
            </button>

            <p class="text-center small text-secondary mb-0">
              Remember your password? 
              <a href="#" onclick="switchModal('forgotModal', 'loginModal')" class="auth-theme-link fw-semibold">Sign in</a>
            </p>
          </form>
        </div>

        <button type="button" class="btn-close-auth" data-bs-dismiss="modal" aria-label="Close">&times;</button>
      </div>
    </div>
  </div>
</div>

<!-- ==========================================
     6. FORGOT PASSWORD OTP & NEW PASS MODAL
     ========================================== -->
<div class="modal fade auth-modal" id="forgotOtpModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
  <div class="modal-dialog modal-dialog-centered" style="max-width: 820px;">
    <div class="modal-content">
      <div class="auth-modal-unified">
        <img src="<?php echo $initial_img_src; ?>" alt="Background Glow" class="auth-ambient-backdrop">

        <div class="auth-character-frame">
          <img src="<?php echo $initial_img_src; ?>" alt="KrazePlanet Character" class="auth-character-hero-img">
        </div>

        <div class="auth-form-container">
          <h3 class="auth-form-title">Enter Code</h3>
          <p class="auth-form-subtitle mb-3">
            We sent a verification code to <strong id="forgotEmailTarget" style="color: var(--auth-theme-primary);">your email</strong>.
          </p>

          <div id="forgotOtpAlert" style="display:none;" class="alert py-2 px-3 small mb-3 border-0"></div>

          <form id="forgotOtpForm" onsubmit="handleForgotResetPassword(event)">
            <div class="mb-3">
              <label class="form-label small fw-semibold text-light mb-1">6-Digit Verification Code</label>
              <input type="text" id="forgotOtpCode" class="form-control text-center font-monospace fs-4 fw-bold letter-spacing-2" placeholder="123456" maxlength="6" pattern="[0-9]{6}" required autofocus>
            </div>

            <div class="row g-2 mb-3">
              <div class="col-6">
                <label class="form-label small fw-semibold text-light mb-1">New Password</label>
                <input type="password" id="forgotNewPass" class="form-control" placeholder="••••••••" minlength="6" required>
              </div>
              <div class="col-6">
                <label class="form-label small fw-semibold text-light mb-1">Confirm</label>
                <input type="password" id="forgotConfirmPass" class="form-control" placeholder="••••••••" minlength="6" required>
              </div>
            </div>

            <button type="submit" class="btn btn-primary w-100 py-3 mb-3 fw-bold auth-theme-btn" id="forgotOtpSubmitBtn">
              <i class="bi bi-shield-lock-fill me-1"></i> Update Password &amp; Sign In &rarr;
            </button>

            <div class="d-flex justify-content-between small">
              <a href="#" onclick="switchModal('forgotOtpModal', 'forgotModal')" class="text-secondary text-decoration-none">&larr; Back</a>
              <a href="#" onclick="handleResendForgotOtp()" id="forgotResendLink" class="auth-theme-link">Resend Code</a>
            </div>
          </form>
        </div>

        <button type="button" class="btn-close-auth" data-bs-dismiss="modal" aria-label="Close">&times;</button>
      </div>
    </div>
  </div>
</div>

<script>
// Authentication Engine, Dynamic Character Switcher & OTP Handlers
const AUTH_THEMES = <?php echo json_encode($auth_themes); ?>;
const AUTH_IMG_KEYS = Object.keys(AUTH_THEMES);

function applyDynamicModalTheme(imgKey) {
  const t = AUTH_THEMES[imgKey] || AUTH_THEMES['6.jpg'];
  const root = document.documentElement;
  
  root.style.setProperty('--auth-theme-primary', t.primary);
  root.style.setProperty('--auth-theme-hover', t.hover);
  root.style.setProperty('--auth-theme-grad-start', t.grad_start);
  root.style.setProperty('--auth-theme-grad-end', t.grad_end);
  root.style.setProperty('--auth-theme-glow', t.glow);
  root.style.setProperty('--auth-theme-border', t.border);
  root.style.setProperty('--auth-theme-text', t.text);
}

function randomizeAuthMascot() {
  const chosenImgKey = AUTH_IMG_KEYS[Math.floor(Math.random() * AUTH_IMG_KEYS.length)];
  const chosenImgSrc = '/model/images/' + chosenImgKey;
  
  // Update both ambient blurred backdrop and crisp hero character
  document.querySelectorAll('.auth-ambient-backdrop').forEach(img => {
    img.src = chosenImgSrc;
  });
  document.querySelectorAll('.auth-character-hero-img').forEach(img => {
    img.src = chosenImgSrc;
  });
  
  applyDynamicModalTheme(chosenImgKey);
}

function showAlert(id, msg, isSuccess = false) {
  const el = document.getElementById(id);
  if (!el) return;
  el.className = `alert py-2 px-3 small mb-3 border-0 ${isSuccess ? 'alert-success' : 'alert-danger'}`;
  el.style.backgroundColor = isSuccess ? 'rgba(16, 185, 129, 0.2)' : 'rgba(244, 63, 94, 0.2)';
  el.style.color = isSuccess ? '#34d399' : '#fb7185';
  el.innerHTML = msg;
  el.style.display = 'block';
}

function hideAlert(id) {
  const el = document.getElementById(id);
  if (el) el.style.display = 'none';
}

// Global Helpers for Triggering Modals from Navbar or Lab Cards
function openLoginModal(e) {
  if (e && e.preventDefault) e.preventDefault();
  const m = document.getElementById('loginModal');
  if (m) {
    randomizeAuthMascot();
    const inst = bootstrap.Modal.getInstance(m) || new bootstrap.Modal(m);
    inst.show();
  }
}

function openSignupModal(e) {
  if (e && e.preventDefault) e.preventDefault();
  const m = document.getElementById('signupModal');
  if (m) {
    randomizeAuthMascot();
    const inst = bootstrap.Modal.getInstance(m) || new bootstrap.Modal(m);
    inst.show();
  }
}

function openForgotModal(e) {
  if (e && e.preventDefault) e.preventDefault();
  const m = document.getElementById('forgotModal');
  if (m) {
    randomizeAuthMascot();
    const inst = bootstrap.Modal.getInstance(m) || new bootstrap.Modal(m);
    inst.show();
  }
}

function switchModal(fromId, toId) {
  const fromEl = document.getElementById(fromId);
  const toEl = document.getElementById(toId);
  if (fromEl) {
    const fromInst = bootstrap.Modal.getInstance(fromEl) || new bootstrap.Modal(fromEl);
    fromInst.hide();
  }
  if (toEl) {
    setTimeout(() => {
      randomizeAuthMascot();
      const toInst = bootstrap.Modal.getInstance(toEl) || new bootstrap.Modal(toEl);
      toInst.show();
    }, 280);
  }
}

// Attach randomizer & handle URL modal triggers (?modal=login, ?modal=signup, ?redirect=...)
document.addEventListener('DOMContentLoaded', () => {
  ['loginModal', 'signupModal', 'signupVerifyModal', 'forgotModal', 'forgotOtpModal'].forEach(id => {
    const m = document.getElementById(id);
    if (m) {
      m.addEventListener('show.bs.modal', randomizeAuthMascot);
    }
  });

  // Setup OTP Digit Boxes Auto-Focus and Paste Handling
  setupOtpDigitBoxes();

  // Auto-open modal on page load if requested via URL query params
  const urlParams = new URLSearchParams(window.location.search);
  const modalParam = (urlParams.get('modal') || '').toLowerCase();
  const redirectParam = urlParams.get('redirect');

  if (redirectParam) {
    sessionStorage.setItem('kraze_pending_lab_url', redirectParam);
  }

  if (modalParam === 'login' || modalParam === 'signin') {
    setTimeout(() => { openLoginModal(); }, 120);
  } else if (modalParam === 'signup' || modalParam === 'register') {
    setTimeout(() => { openSignupModal(); }, 120);
  } else if (modalParam === 'forgot' || modalParam === 'reset') {
    setTimeout(() => { openForgotModal(); }, 120);
  }
});

// ── 6-Digit OTP Box Logic ──────────────────────────────────────────────
function setupOtpDigitBoxes() {
  const boxes = [
    document.getElementById('regOtp1'),
    document.getElementById('regOtp2'),
    document.getElementById('regOtp3'),
    document.getElementById('regOtp4'),
    document.getElementById('regOtp5'),
    document.getElementById('regOtp6')
  ];

  boxes.forEach((box, index) => {
    if (!box) return;

    box.addEventListener('input', (e) => {
      e.target.value = e.target.value.replace(/\D/g, '');
      boxes.forEach(b => b && b.classList.remove('error-box'));
      if (e.target.value && index < boxes.length - 1) {
        boxes[index + 1].focus();
      }
    });

    box.addEventListener('keydown', (e) => {
      if (e.key === 'Backspace' && !box.value && index > 0) {
        boxes[index - 1].focus();
      }
    });

    box.addEventListener('paste', (e) => {
      e.preventDefault();
      const pasted = (e.clipboardData || window.clipboardData).getData('text').replace(/\D/g, '');
      pasted.split('').slice(0, 6).forEach((char, i) => {
        if (boxes[i]) boxes[i].value = char;
      });
      boxes.forEach(b => b && b.classList.remove('error-box'));
      const nextEmpty = boxes.findIndex(b => b && !b.value);
      if (nextEmpty !== -1) {
        boxes[nextEmpty].focus();
      } else if (boxes[5]) {
        boxes[5].focus();
      }
    });
  });
}

function getCollectedRegOtp() {
  return [1, 2, 3, 4, 5, 6].map(i => {
    const el = document.getElementById('regOtp' + i);
    return el ? el.value.trim() : '';
  }).join('');
}

function clearRegOtpBoxes() {
  for (let i = 1; i <= 6; i++) {
    const el = document.getElementById('regOtp' + i);
    if (el) {
      el.value = '';
      el.classList.remove('error-box');
    }
  }
}

// 1. Handle Login Submit
function handleLoginSubmit(e) {
  e.preventDefault();
  hideAlert('loginAlert');
  
  const btn = document.getElementById('loginSubmitBtn');
  const origText = btn.innerHTML;
  btn.disabled = true;
  btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Signing in...';

  const formData = new FormData();
  formData.append('action', 'login');
  formData.append('login_input', document.getElementById('loginUsername').value.trim());
  formData.append('password', document.getElementById('loginPassword').value);

  fetch('/api/auth_api.php', { method: 'POST', body: formData })
    .then(r => r.json())
    .then(data => {
      btn.disabled = false;
      btn.innerHTML = origText;
      if (data.success) {
        showAlert('loginAlert', 'Welcome back! Redirecting...', true);
        const pendingLab = sessionStorage.getItem('kraze_pending_lab_url');
        const urlParams = new URLSearchParams(window.location.search);
        const redirectParam = urlParams.get('redirect');
        const target = pendingLab || redirectParam;
        
        setTimeout(() => {
          if (target) {
            sessionStorage.removeItem('kraze_pending_lab_url');
            window.location.href = target;
          } else {
            window.location.reload();
          }
        }, 600);
      } else {
        showAlert('loginAlert', data.error || 'Invalid credentials.', false);
      }
    })
    .catch(() => {
      btn.disabled = false;
      btn.innerHTML = origText;
      showAlert('loginAlert', 'Network error. Please try again.', false);
    });
}

// 2. Handle Create Account Submit (STEP 1: Validate Details -> Send OTP -> Open Step 2 Modal)
let regResendCountdownInterval = null;

function handleSignupSubmit(e) {
  e.preventDefault();
  hideAlert('signupAlert');

  const usernameInput = document.getElementById('signupUsername');
  const fullnameInput = document.getElementById('signupFullname');
  const emailInput    = document.getElementById('signupEmail');
  const passwordInput = document.getElementById('signupPassword');
  const confirmInput  = document.getElementById('signupConfirmPassword');
  const submitBtn     = document.getElementById('signupSubmitBtn');

  const username = usernameInput.value.trim();
  const fullname = fullnameInput.value.trim();
  const email    = emailInput.value.trim();
  const password = passwordInput.value;
  const confirm  = confirmInput.value;

  if (!username) {
    showAlert('signupAlert', 'Please enter a username.', false);
    usernameInput.focus();
    return;
  }
  if (username.length < 3) {
    showAlert('signupAlert', 'Username must be at least 3 characters long.', false);
    usernameInput.focus();
    return;
  }

  if (!fullname) {
    showAlert('signupAlert', 'Please enter your full name.', false);
    fullnameInput.focus();
    return;
  }

  if (!email || !email.includes('@')) {
    showAlert('signupAlert', 'Please enter a valid email address.', false);
    emailInput.focus();
    return;
  }

  if (!password) {
    showAlert('signupAlert', 'Please enter a password.', false);
    passwordInput.focus();
    return;
  }
  if (password.length < 6) {
    showAlert('signupAlert', 'Password must be at least 6 characters long.', false);
    passwordInput.focus();
    return;
  }

  if (password !== confirm) {
    showAlert('signupAlert', 'Passwords do not match.', false);
    confirmInput.focus();
    return;
  }

  submitBtn.disabled = true;
  const origBtnText = submitBtn.innerHTML;
  submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Sending Verification Code...';

  const formData = new FormData();
  formData.append('action', 'signup_send_otp');
  formData.append('username', username);
  formData.append('fullname', fullname);
  formData.append('email', email);
  formData.append('password', password);
  formData.append('confirm_password', confirm);

  fetch('/api/auth_api.php', { method: 'POST', body: formData })
    .then(r => r.json())
    .then(data => {
      submitBtn.disabled = false;
      submitBtn.innerHTML = origBtnText;

      if (data.success) {
        // Set email display in Step 2 Modal
        const emailDisplay = document.getElementById('signupVerifyEmailDisplay');
        if (emailDisplay) {
          emailDisplay.innerText = data.email || email;
        }

        // Hide Step 1 Modal and Show Step 2 Verification Modal
        const signupModalEl = document.getElementById('signupModal');
        const signupModalInst = bootstrap.Modal.getInstance(signupModalEl) || new bootstrap.Modal(signupModalEl);
        signupModalInst.hide();

        setTimeout(() => {
          randomizeAuthMascot();
          clearRegOtpBoxes();
          hideAlert('signupVerifyAlert');
          
          const verifyModalEl = document.getElementById('signupVerifyModal');
          const verifyModalInst = bootstrap.Modal.getInstance(verifyModalEl) || new bootstrap.Modal(verifyModalEl);
          verifyModalInst.show();

          setTimeout(() => {
            const firstBox = document.getElementById('regOtp1');
            if (firstBox) firstBox.focus();
          }, 350);

          startRegResendCountdown(60);
        }, 280);

      } else {
        showAlert('signupAlert', data.error || 'Failed to send verification code.', false);
      }
    })
    .catch(() => {
      submitBtn.disabled = false;
      submitBtn.innerHTML = origBtnText;
      showAlert('signupAlert', 'Network error while sending verification code.', false);
    });
}

// 3. Handle Verification Code Submit (STEP 2: Verify OTP -> Activate Account)
function handleSignupVerifySubmit(e) {
  e.preventDefault();
  hideAlert('signupVerifyAlert');

  const otpVal = getCollectedRegOtp();
  if (otpVal.length !== 6) {
    showAlert('signupVerifyAlert', 'Please enter the complete 6-digit verification code.', false);
    for (let i = 1; i <= 6; i++) {
      const b = document.getElementById('regOtp' + i);
      if (b && !b.value) {
        b.classList.add('error-box');
      }
    }
    const firstEmpty = [1, 2, 3, 4, 5, 6].find(i => !document.getElementById('regOtp' + i).value);
    if (firstEmpty) {
      document.getElementById('regOtp' + firstEmpty).focus();
    }
    return;
  }

  const btn = document.getElementById('regVerifySubmitBtn');
  const origText = btn.innerHTML;
  btn.disabled = true;
  btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Verifying &amp; Creating Account...';

  const formData = new FormData();
  formData.append('action', 'signup_create_account');
  formData.append('otp', otpVal);

  fetch('/api/auth_api.php', { method: 'POST', body: formData })
    .then(r => r.json())
    .then(data => {
      btn.disabled = false;
      btn.innerHTML = origText;
      if (data.success) {
        showAlert('signupVerifyAlert', 'Account verified successfully! Welcome to KrazePlanet!', true);
        const pendingLab = sessionStorage.getItem('kraze_pending_lab_url');
        const urlParams = new URLSearchParams(window.location.search);
        const redirectParam = urlParams.get('redirect');
        const target = pendingLab || redirectParam;
        
        setTimeout(() => {
          if (target) {
            sessionStorage.removeItem('kraze_pending_lab_url');
            window.location.href = target;
          } else {
            window.location.reload();
          }
        }, 800);
      } else {
        showAlert('signupVerifyAlert', data.error || 'Invalid verification code.', false);
        for (let i = 1; i <= 6; i++) {
          const b = document.getElementById('regOtp' + i);
          if (b) b.classList.add('error-box');
        }
      }
    })
    .catch(() => {
      btn.disabled = false;
      btn.innerHTML = origText;
      showAlert('signupVerifyAlert', 'Network error while creating account.', false);
    });
}

function startRegResendCountdown(seconds) {
  const btn = document.getElementById('regResendBtn');
  if (!btn) return;

  btn.disabled = true;
  let remaining = seconds;
  btn.innerText = `Resend Code (${remaining}s)`;

  if (regResendCountdownInterval) clearInterval(regResendCountdownInterval);
  regResendCountdownInterval = setInterval(() => {
    remaining--;
    if (remaining > 0) {
      btn.innerText = `Resend Code (${remaining}s)`;
    } else {
      clearInterval(regResendCountdownInterval);
      btn.disabled = false;
      btn.innerText = 'Resend Code';
    }
  }, 1000);
}

function handleResendSignupOtp() {
  const btn = document.getElementById('regResendBtn');
  if (btn.disabled) return;

  btn.disabled = true;
  btn.innerText = 'Sending...';

  const formData = new FormData();
  formData.append('action', 'signup_send_otp');
  // Send empty post so backend uses pending session or values
  formData.append('username', document.getElementById('signupUsername').value.trim());
  formData.append('fullname', document.getElementById('signupFullname').value.trim());
  formData.append('email', document.getElementById('signupEmail').value.trim());
  formData.append('password', document.getElementById('signupPassword').value);
  formData.append('confirm_password', document.getElementById('signupConfirmPassword').value);

  fetch('/api/auth_api.php', { method: 'POST', body: formData })
    .then(r => r.json())
    .then(data => {
      if (data.success) {
        showAlert('signupVerifyAlert', 'A fresh 6-digit code has been sent to your email.', true);
        clearRegOtpBoxes();
        startRegResendCountdown(60);
        document.getElementById('regOtp1').focus();
      } else {
        btn.disabled = false;
        btn.innerText = 'Resend Code';
        showAlert('signupVerifyAlert', data.error || 'Failed to resend verification code.', false);
      }
    })
    .catch(() => {
      btn.disabled = false;
      btn.innerText = 'Resend Code';
      showAlert('signupVerifyAlert', 'Network error while resending verification code.', false);
    });
}

// 4. Handle Forgot Password Send OTP
function handleForgotSendOtp(e) {
  e.preventDefault();
  hideAlert('forgotAlert');

  const btn = document.getElementById('forgotSubmitBtn');
  const origText = btn.innerHTML;
  btn.disabled = true;
  btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Sending code...';

  const inputVal = document.getElementById('forgotLoginInput').value.trim();

  const formData = new FormData();
  formData.append('action', 'forgot_send_otp');
  formData.append('login_input', inputVal);

  fetch('/api/auth_api.php', { method: 'POST', body: formData })
    .then(r => r.json())
    .then(data => {
      btn.disabled = false;
      btn.innerHTML = origText;
      if (data.success) {
        document.getElementById('forgotEmailTarget').innerText = data.email || inputVal;
        switchModal('forgotModal', 'forgotOtpModal');
      } else {
        showAlert('forgotAlert', data.error || 'No account found with those details.', false);
      }
    })
    .catch(() => {
      btn.disabled = false;
      btn.innerHTML = origText;
      showAlert('forgotAlert', 'Network error. Please try again.', false);
    });
}

// 5. Handle Forgot Password Reset Submission
function handleForgotResetPassword(e) {
  e.preventDefault();
  hideAlert('forgotOtpAlert');

  const otp = document.getElementById('forgotOtpCode').value.trim();
  const newPass = document.getElementById('forgotNewPass').value;
  const confirmPass = document.getElementById('forgotConfirmPass').value;

  if (newPass !== confirmPass) {
    showAlert('forgotOtpAlert', 'Passwords do not match.', false);
    return;
  }

  const btn = document.getElementById('forgotOtpSubmitBtn');
  const origText = btn.innerHTML;
  btn.disabled = true;
  btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Updating...';

  const formData = new FormData();
  formData.append('action', 'forgot_reset_password');
  formData.append('otp', otp);
  formData.append('new_password', newPass);
  formData.append('confirm_password', confirmPass);

  fetch('/api/auth_api.php', { method: 'POST', body: formData })
    .then(r => r.json())
    .then(data => {
      btn.disabled = false;
      btn.innerHTML = origText;
      if (data.success) {
        showAlert('forgotOtpAlert', 'Password successfully reset! Logging you in...', true);
        setTimeout(() => {
          window.location.reload();
        }, 800);
      } else {
        showAlert('forgotOtpAlert', data.error || 'Failed to reset password.', false);
      }
    })
    .catch(() => {
      btn.disabled = false;
      btn.innerHTML = origText;
      showAlert('forgotOtpAlert', 'Network error. Please try again.', false);
    });
}

function handleResendForgotOtp() {
  const link = document.getElementById('forgotResendLink');
  link.innerText = 'Sending...';

  const formData = new FormData();
  formData.append('action', 'forgot_send_otp');
  formData.append('login_input', document.getElementById('forgotLoginInput').value.trim());

  fetch('/api/auth_api.php', { method: 'POST', body: formData })
    .then(r => r.json())
    .then(data => {
      link.innerText = 'Resend Code';
      if (data.success) {
        showAlert('forgotOtpAlert', 'A new verification code has been dispatched.', true);
      } else {
        showAlert('forgotOtpAlert', data.error || 'Failed to resend code.', false);
      }
    })
    .catch(() => {
      link.innerText = 'Resend Code';
      showAlert('forgotOtpAlert', 'Network error.', false);
    });
}
</script>
