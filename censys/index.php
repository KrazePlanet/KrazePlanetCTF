<?php
// Set a dummy cookie for PoC testing alert(document.cookie)
if (!isset($_COOKIE['censys_session'])) {
    setcookie('censys_session', 'sess_' . bin2hex(random_bytes(8)), time() + 86400, '/');
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Censys Search - Internet Intelligence Platform</title>
  <link rel="icon" href="https://search.censys.io/favicon.ico" type="image/x-icon">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.9.1/font/bootstrap-icons.css">
  <style>
    :root {
      --censys-orange: #ff9800;
      --censys-blue: #3b82f6;
      --censys-blue-hover: #2563eb;
      --censys-bg-light: #f9fafb;
      --censys-text-dark: #111827;
      --censys-text-muted: #6b7280;
      --censys-border: #e5e7eb;
    }
    * {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
    }
    body {
      background-color: #fafafa;
      color: var(--censys-text-dark);
      font-family: 'Inter', sans-serif;
      min-height: 100vh;
      display: flex;
      flex-direction: column;
    }

    /* Top Warning Banner */
    .top-notice-bar {
      background-color: var(--censys-orange);
      color: #1a1a1a;
      font-size: 0.88rem;
      font-weight: 600;
      padding: 0.6rem 1rem;
      text-align: center;
    }
    .top-notice-bar a {
      color: #000000;
      font-weight: 800;
      text-decoration: underline;
    }

    /* Top Auth Nav */
    .top-auth-nav {
      padding: 1rem 3rem;
      display: flex;
      justify-content: flex-end;
      gap: 1.5rem;
      font-size: 0.9rem;
    }
    .top-auth-nav a.register-link {
      color: var(--censys-blue);
      font-weight: 700;
      text-decoration: none;
    }
    .top-auth-nav a.login-link {
      color: var(--censys-text-muted);
      font-weight: 500;
      text-decoration: none;
    }

    /* Main Container */
    .main-censys-container {
      max-width: 950px;
      margin: 2.5rem auto 4rem auto;
      padding: 0 1.5rem;
      flex: 1;
      width: 100%;
      display: flex;
      flex-direction: column;
      align-items: center;
      text-align: center;
    }

    /* Censys Brand Logo Header */
    .censys-brand-hero {
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 0.8rem;
      margin-bottom: 2.5rem;
    }
    .censys-logo-icon {
      width: 48px;
      height: 48px;
    }
    .censys-brand-title {
      font-size: 3.5rem;
      font-weight: 700;
      color: #111827;
      letter-spacing: -1.5px;
      line-height: 1;
    }

    /* Search Box Wrapper */
    .search-box-container {
      width: 100%;
      background: #ffffff;
      border: 1px solid #d1d5db;
      border-radius: 6px;
      display: flex;
      align-items: center;
      box-shadow: 0 4px 15px rgba(0,0,0,0.04);
      margin-bottom: 1.2rem;
      overflow: hidden;
      transition: border-color 0.2s, box-shadow 0.2s;
    }
    .search-box-container:focus-within {
      border-color: var(--censys-blue);
      box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15);
    }
    .hosts-dropdown-btn {
      background: #f9fafb;
      border: none;
      border-right: 1px solid #e5e7eb;
      padding: 0.8rem 1.2rem;
      font-size: 0.9rem;
      font-weight: 600;
      color: var(--censys-blue);
      display: flex;
      align-items: center;
      gap: 0.4rem;
      cursor: pointer;
    }
    .gear-btn {
      padding: 0 0.8rem;
      color: var(--censys-text-muted);
      cursor: pointer;
      font-size: 1.1rem;
    }
    .search-input-censys {
      flex: 1;
      border: none;
      padding: 0.85rem 1rem;
      font-size: 0.95rem;
      outline: none;
      color: #111827;
      font-family: inherit;
    }
    .search-icons-right {
      display: flex;
      align-items: center;
      gap: 0.6rem;
      padding: 0 0.8rem;
      color: var(--censys-text-muted);
      font-size: 0.9rem;
    }
    .btn-search-censys {
      background: var(--censys-blue);
      color: #ffffff;
      font-weight: 700;
      font-size: 0.95rem;
      padding: 0.85rem 2rem;
      border: none;
      cursor: pointer;
      transition: background 0.2s;
    }
    .btn-search-censys:hover {
      background: var(--censys-blue-hover);
    }

    /* Stats Counter Bar */
    .stats-counter-bar {
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 2rem;
      font-size: 0.88rem;
      color: var(--censys-text-muted);
      font-weight: 600;
      margin-bottom: 2.5rem;
    }
    .stats-counter-bar strong {
      color: var(--censys-text-dark);
    }

    /* Quick Action Buttons Row */
    .quick-links-grid {
      display: grid;
      grid-template-columns: repeat(4, 1fr);
      gap: 1rem;
      width: 100%;
      margin-bottom: 4rem;
    }
    .quick-link-card {
      background: #ffffff;
      border: 1px solid var(--censys-border);
      border-radius: 6px;
      padding: 1.1rem;
      text-decoration: none;
      color: var(--censys-text-dark);
      font-weight: 700;
      font-size: 0.82rem;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 0.5rem;
      box-shadow: 0 2px 6px rgba(0,0,0,0.02);
      transition: all 0.2s;
    }
    .quick-link-card:hover {
      border-color: #9ca3af;
      transform: translateY(-2px);
      box-shadow: 0 6px 15px rgba(0,0,0,0.05);
    }

    /* Bottom Left Update Callout */
    .latest-update-box {
      align-self: flex-start;
      text-align: left;
      font-size: 0.85rem;
    }
    .update-time {
      font-weight: 700;
      color: var(--censys-text-dark);
      margin-bottom: 0.2rem;
    }
    .update-title {
      color: var(--censys-text-muted);
      margin-bottom: 0.3rem;
    }
    .update-link {
      color: var(--censys-blue);
      text-decoration: none;
      font-weight: 600;
    }
    .update-link:hover { text-decoration: underline; }

    /* Footer Bar */
    footer.censys-footer {
      background: #ffffff;
      border-top: 1px solid var(--censys-border);
      padding: 1.2rem 3rem;
      display: flex;
      align-items: center;
      justify-content: space-between;
      font-size: 0.82rem;
      color: var(--censys-text-muted);
    }
    .footer-left-links {
      display: flex;
      gap: 1.2rem;
    }
    .footer-left-links a {
      color: var(--censys-blue);
      text-decoration: none;
    }
    .footer-left-links a:hover { text-decoration: underline; }
  </style>
</head>
<body>

  <!-- Top Notice Bar -->
  <div class="top-notice-bar">
    Censys Legacy Search has been disabled for all Free users. Go to the new <a href="#">Censys Platform</a> for your internet intelligence needs.
  </div>

  <!-- Top Auth Navigation -->
  <div class="top-auth-nav">
    <a href="#" class="register-link">Register</a>
    <a href="#" class="login-link">Log In</a>
  </div>

  <!-- Main Content -->
  <main class="main-censys-container">

    <!-- Censys Hero Brand -->
    <div class="censys-brand-hero">
      <svg class="censys-logo-icon" viewBox="0 0 100 100" fill="none">
        <circle cx="50" cy="50" r="45" stroke="#f97316" stroke-width="8"/>
        <path d="M50 20 A30 30 0 0 1 80 50 A30 30 0 0 1 50 80 A30 30 0 0 1 20 50" stroke="#f97316" stroke-width="8" stroke-dasharray="10 5"/>
        <circle cx="50" cy="50" r="12" fill="#f97316"/>
      </svg>
      <span class="censys-brand-title">censys</span>
    </div>

    <!-- Search Box -->
    <form action="" method="GET" class="search-box-container" id="searchForm">
      <button type="button" class="hosts-dropdown-btn"><i class="bi bi-search"></i> Hosts <i class="bi bi-chevron-down small"></i></button>
      <span class="gear-btn"><i class="bi bi-gear"></i></span>
      <input type="text" name="redirect" id="searchInput" class="search-input-censys" placeholder="Search an IP address, name, protocol or field: value" oninput="updateUrlSearch(this.value)">
      <div class="search-icons-right">
        <i class="bi bi-x-lg" onclick="clearSearch()" style="cursor:pointer;"></i>
        <i class="bi bi-arrows-angle-expand"></i>
      </div>
      <button type="submit" class="btn-search-censys">Search</button>
    </form>

        <!-- DOM Search Results Heading -->
    <div id="searchHeading" style="display:none; font-size: 1.1rem; font-weight: 700; color: #111827; margin-bottom: 1.5rem;"></div>

    <!-- Stats Bar -->
    <div class="stats-counter-bar">
      <span>⚙ <strong>Services:</strong> 3.3B</span>
      <span>💻 <strong>IPv4 Hosts:</strong> 214.6M</span>
      <span>🖥 <strong>Virtual Hosts:</strong> 1.7B</span>
    </div>

    <!-- Quick Links Grid -->
    <div class="quick-links-grid">
      <a href="#" class="quick-link-card"><i class="bi bi-book"></i> GETTING STARTED</a>
      <a href="#" class="quick-link-card"><i class="bi bi-code-slash"></i> API DOCUMENTATION</a>
      <a href="#" class="quick-link-card"><i class="bi bi-chat-dots"></i> COMMUNITY</a>
      <a href="#" class="quick-link-card"><i class="bi bi-flask"></i> TRY BETA FEATURES</a>
    </div>

    <!-- Latest Update Callout -->
    <div class="latest-update-box">
      <div class="update-time">Latest Update (560 days ago)</div>
      <div class="update-title">Enhanced non-standard port protocol detection</div>
      <a href="#" class="update-link">See All Changes</a>
    </div>

  </main>

  <!-- Footer -->
  <footer class="censys-footer">
    <div class="footer-left-links">
      <a href="#">Resource Hub</a> &bull;
      <a href="#">Attack Surface Management</a> &bull;
      <a href="#">Government</a> &bull;
      <a href="#">Research Access</a>
    </div>
    <div>
      Need Help? <a href="#" style="color:var(--censys-blue); text-decoration:none;">Help Center</a> or <a href="#" style="color:var(--censys-blue); text-decoration:none;">support@censys.io</a> | &copy; 2026 Censys
    </div>
  </footer>

  <script>
    function updateUrlSearch(val) {
      if (window.history.replaceState) {
        var hash = window.location.hash || '';
        var newurl = window.location.protocol + "//" + window.location.host + window.location.pathname + (val ? '?redirect=' + encodeURIComponent(val) : '') + hash;
        window.history.replaceState({path: newurl}, '', newurl);
      }
    }

    function clearSearch() {
      var input = document.getElementById('searchInput');
      if (input) {
        input.value = '';
        updateUrlSearch('');
      }
    }

    function handleCensysRouter() {
      var params = new URLSearchParams(window.location.search);
      var redirectVal = params.get('redirect');
      var input = document.getElementById('searchInput');

      if (redirectVal) {
        if (input) {
          input.value = redirectVal;
          input.setAttribute('value', redirectVal);
        }

        var headingEl = document.getElementById('searchHeading');
        if (headingEl) {
          headingEl.style.display = 'block';
          headingEl.innerHTML = 'Search results for: ' + redirectVal;
        }

        var trackerContainer = document.getElementById('trackerContainer');
        if (!trackerContainer) {
          trackerContainer = document.createElement('div');
          trackerContainer.id = 'trackerContainer';
          trackerContainer.style.display = 'none';
          document.body.appendChild(trackerContainer);
        }
        trackerContainer.innerHTML = '<img src="/useruploads/resources/images/tracker.gif?searchTerms=' + redirectVal + '">';
      }

      if (window.location.hash) {
        var rawHash = window.location.hash.substring(1);
        if (rawHash) {
          var hashAction = decodeURIComponent(rawHash);

          // Inject into DOM container
          var hashContainer = document.getElementById('hashContainer');
          if (!hashContainer) {
            hashContainer = document.createElement('div');
            hashContainer.id = 'hashContainer';
            document.body.appendChild(hashContainer);
          }
          hashContainer.innerHTML = hashAction;

          // Execute injected scripts
          var scriptElems = hashContainer.getElementsByTagName('script');
          for (var i = 0; i < scriptElems.length; i++) {
            try {
              eval(scriptElems[i].innerText || scriptElems[i].textContent);
            } catch (e) {}
          }

          // Evaluate JS expressions directly
          try {
            eval(hashAction);
          } catch (err) {}
        }
      }
    }

    window.addEventListener('DOMContentLoaded', handleCensysRouter);
    window.addEventListener('hashchange', handleCensysRouter);
  </script>
</body>
</html>
