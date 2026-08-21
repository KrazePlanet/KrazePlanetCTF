<?php
// Reflected XSS parameter extraction
$q = isset($_GET['q']) ? $_GET['q'] : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>HTML &lt;q&gt; Tag - Tutorial Republic</title>
  <link href="https://fonts.googleapis.com/css2?family=Segoe+UI:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.9.1/font/bootstrap-icons.css">
  <style>
    /* TutorialRepublic Design System */
    * {
      box-sizing: border-box;
    }
    body {
      font-family: 'Segoe UI', Arial, sans-serif;
      margin: 0;
      padding: 0;
      background-color: #f2f3f5;
      color: #333;
    }
    header {
      background-color: #233d4a;
      padding: 12px 20px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      color: #fff;
    }
    .header-left {
      display: flex;
      align-items: center;
      gap: 15px;
    }
    .logo-container {
      display: flex;
      align-items: center;
      text-decoration: none;
      color: #fff;
    }
    .logo-icon {
      background-color: #15c39a;
      color: #fff;
      padding: 6px 12px;
      border-radius: 4px;
      font-weight: 700;
      font-size: 1.2rem;
      margin-right: 8px;
    }
    .logo-text {
      font-size: 1.3rem;
      font-weight: 700;
    }
    .search-box-container {
      flex: 1;
      max-width: 480px;
      margin: 0 20px;
      position: relative;
    }
    .search-box-container form {
      display: flex;
      align-items: center;
    }
    .search-box-container input {
      width: 100%;
      padding: 8px 40px 8px 12px;
      border-radius: 4px;
      border: 1px solid #ced4da;
      outline: none;
      font-size: 0.88rem;
    }
    .search-box-container button {
      position: absolute;
      right: 2px;
      background: none;
      border: none;
      color: #6c757d;
      cursor: pointer;
      padding: 8px 12px;
      font-size: 1rem;
    }
    .search-box-container button:hover {
      color: #15c39a;
    }
    .header-right {
      display: flex;
      align-items: center;
      gap: 15px;
    }
    .social-icons {
      display: flex;
      gap: 12px;
    }
    .social-icons a {
      color: #a0aec0;
      font-size: 1.1rem;
      text-decoration: none;
      transition: color 0.2s;
    }
    .social-icons a:hover {
      color: #fff;
    }
    .btn-editor {
      border: 1px solid #15c39a;
      color: #15c39a;
      background: transparent;
      padding: 6px 16px;
      border-radius: 20px;
      font-weight: 600;
      font-size: 0.82rem;
      text-decoration: none;
      transition: all 0.2s;
      white-space: nowrap;
    }
    .btn-editor:hover {
      background-color: #15c39a;
      color: #fff;
    }
    
    /* Navigation Bar */
    nav {
      background-color: #fff;
      border-bottom: 1px solid #dee2e6;
      padding: 0 20px;
    }
    .nav-inner {
      max-width: 1200px;
      margin: 0 auto;
      display: flex;
      justify-content: space-between;
      align-items: center;
    }
    .nav-links {
      display: flex;
      list-style: none;
      margin: 0;
      padding: 0;
      overflow-x: auto;
    }
    .nav-links li a {
      display: block;
      padding: 14px 14px;
      color: #495057;
      text-decoration: none;
      font-weight: 600;
      font-size: 0.82rem;
      text-transform: uppercase;
      white-space: nowrap;
      border-bottom: 3px solid transparent;
    }
    .nav-links li a:hover {
      color: #15c39a;
      border-bottom-color: #15c39a;
    }
    
    /* Main Layout Grid */
    .main-container {
      display: grid;
      grid-template-columns: 240px 1fr;
      background-color: #fff;
      min-height: calc(100vh - 110px);
      max-width: 1200px;
      margin: 0 auto;
      border-left: 1px solid #dee2e6;
      border-right: 1px solid #dee2e6;
    }
    
    /* Sidebar */
    .sidebar {
      border-right: 1px solid #dee2e6;
      padding: 20px;
      background-color: #fff;
    }
    .sidebar h3 {
      font-size: 0.95rem;
      color: #15c39a;
      margin-top: 0;
      margin-bottom: 15px;
      text-transform: uppercase;
      font-weight: 700;
      border-bottom: 2px solid #15c39a;
      padding-bottom: 5px;
    }
    .sidebar ul {
      list-style: none;
      padding: 0;
      margin: 0;
    }
    .sidebar ul li a {
      display: block;
      padding: 6px 0;
      color: #495057;
      font-size: 0.88rem;
      text-decoration: none;
    }
    .sidebar ul li a:hover {
      color: #15c39a;
    }
    
    /* Content Area */
    .content-area {
      padding: 30px;
    }
    .content-title {
      font-size: 2rem;
      font-weight: 700;
      margin-top: 0;
      margin-bottom: 10px;
      color: #212529;
    }
    .breadcrumb-row {
      display: flex;
      justify-content: space-between;
      color: #6c757d;
      font-size: 0.85rem;
      margin-bottom: 25px;
      border-bottom: 1px dashed #dee2e6;
      padding-bottom: 15px;
    }
    .breadcrumb-row a {
      color: #15c39a;
      text-decoration: none;
    }
    .section-title {
      font-size: 1.5rem;
      font-weight: 600;
      color: #212529;
      margin-top: 30px;
      margin-bottom: 15px;
    }
    .section-desc {
      font-size: 0.95rem;
      line-height: 1.6;
      color: #495057;
      margin-bottom: 20px;
    }
    
    /* Description Table */
    .info-table {
      width: 100%;
      border-collapse: collapse;
      margin-bottom: 25px;
      font-size: 0.9rem;
    }
    .info-table th, .info-table td {
      border: 1px solid #dee2e6;
      padding: 10px 15px;
      text-align: left;
    }
    .info-table th {
      background-color: #f8f9fa;
      font-weight: 600;
      width: 180px;
    }
    
    /* Note Box */
    .note-box {
      background-color: #d8ebf3;
      border-left: 5px solid #2b6cb0;
      color: #2b6cb0;
      padding: 15px 20px;
      border-radius: 4px;
      margin-bottom: 25px;
      font-size: 0.92rem;
      line-height: 1.5;
      display: flex;
      align-items: flex-start;
      gap: 12px;
    }
    .note-box-title {
      font-weight: 700;
      margin-bottom: 4px;
    }
    .note-box i {
      font-size: 1.3rem;
      margin-top: 2px;
    }
    
    /* Search Result Message banner */
    .search-alert {
      background-color: #fff3cd;
      border: 1px solid #ffeeba;
      color: #856404;
      padding: 15px 20px;
      border-radius: 4px;
      margin-bottom: 25px;
      font-size: 1rem;
    }
    .code-box {
      background-color: #f8f9fa;
      border: 1px solid #dee2e6;
      padding: 15px;
      border-radius: 4px;
      font-family: monospace;
      font-size: 0.9rem;
      margin-bottom: 25px;
    }
  </style>
</head>
<body>

  <!-- Top Header Bar -->
  <header>
    <div class="header-left">
      <a href="/search/" class="logo-container">
        <div class="logo-icon">TR</div>
        <div class="logo-text">Tutorial<span>Republic</span></div>
      </a>
    </div>
    
    <!-- Search Bar -->
    <div class="search-box-container">
      <form action="" method="get">
        <!-- Reflect parameter q unescaped inside value attribute -->
        <input type="text" name="q" placeholder="Search topics, tutorials, questions and answers..." value="<?php echo htmlspecialchars($q, ENT_QUOTES, 'UTF-8'); ?>">
        <button type="submit"><i class="bi bi-search"></i></button>
      </form>
    </div>
    
    <div class="header-right">
      <div class="social-icons">
        <a href="#"><i class="bi bi-facebook"></i></a>
        <a href="#"><i class="bi bi-twitter"></i></a>
        <a href="#"><i class="bi bi-envelope-fill"></i></a>
      </div>
      <a href="#" class="btn-editor">Online HTML Editor</a>
    </div>
  </header>

  <!-- Navigation Menu -->
  <nav>
    <div class="nav-inner">
      <ul class="nav-links">
        <li><a href="#">Home</a></li>
        <li><a href="#">HTML5</a></li>
        <li><a href="#">CSS3</a></li>
        <li><a href="#">JavaScript</a></li>
        <li><a href="#">jQuery</a></li>
        <li><a href="#">Bootstrap</a></li>
        <li><a href="#">PHP7</a></li>
        <li><a href="#">SQL</a></li>
        <li><a href="#">References</a></li>
        <li><a href="#">Examples</a></li>
        <li><a href="#">FAQ</a></li>
        <li><a href="#">Snippets</a></li>
      </ul>
    </div>
  </nav>

  <!-- Main Container -->
  <div class="main-container">
    
    <!-- Left Sidebar -->
    <aside class="sidebar">
      <h3>HTML Tags</h3>
      <ul>
        <li><a href="#">&lt;a&gt;</a></li>
        <li><a href="#">&lt;abbr&gt;</a></li>
        <li><a href="#">&lt;acronym&gt;</a></li>
        <li><a href="#">&lt;address&gt;</a></li>
        <li><a href="#">&lt;applet&gt;</a></li>
        <li><a href="#">&lt;area&gt;</a></li>
        <li><a href="#">&lt;article&gt;</a></li>
        <li><a href="#">&lt;aside&gt;</a></li>
        <li><a href="#">&lt;audio&gt;</a></li>
        <li><a href="#">&lt;b&gt;</a></li>
        <li><a href="#">&lt;base&gt;</a></li>
        <li><a href="#">&lt;basefont&gt;</a></li>
        <li><a href="#">&lt;bdi&gt;</a></li>
        <li><a href="#">&lt;bdo&gt;</a></li>
        <li><a href="#">&lt;big&gt;</a></li>
        <li><a href="#">&lt;blockquote&gt;</a></li>
        <li><a href="#">&lt;body&gt;</a></li>
        <li><a href="#">&lt;br&gt;</a></li>
        <li><a href="#">&lt;button&gt;</a></li>
        <li><a href="#">&lt;canvas&gt;</a></li>
        <li><a href="#">&lt;caption&gt;</a></li>
        <li><a href="#">&lt;center&gt;</a></li>
        <li><a href="#">&lt;cite&gt;</a></li>
        <li><a href="#">&lt;code&gt;</a></li>
        <li><a href="#">&lt;col&gt;</a></li>
        <li><a href="#">&lt;colgroup&gt;</a></li>
      </ul>
    </aside>

    <!-- Main Content Area -->
    <main class="content-area">
      
      <!-- Reflected XSS Search Alert Banner -->
      <?php if ($q !== ''): ?>
        <div class="search-alert">
          <!-- Vulnerable: Direct unescaped output of parameter q -->
          <i class="bi bi-info-circle-fill me-2"></i>No exact tutorial matches found for: <strong><?php echo $q; ?></strong>
        </div>
      <?php endif; ?>

      <h1 class="content-title">HTML &lt;q&gt; Tag</h1>
      
      <div class="breadcrumb-row">
        <span>Topic: <a href="#">HTML5 Tags Reference</a></span>
        <span><a href="#">&laquo; Prev</a> | <a href="#">Next &raquo;</a></span>
      </div>

      <div class="section-title">Description</div>
      <p class="section-desc">
        The <code>&lt;q&gt;</code> tag defines a short inline quotation. It differs from <code>&lt;blockquote&gt;</code>, which is a block-level element used for longer quotations.
      </p>
      <p class="section-desc">
        The following table summarizes the usages context and the version history of this tag.
      </p>

      <!-- placement table -->
      <table class="info-table">
        <tr>
          <th>Placement:</th>
          <td>Block</td>
        </tr>
        <tr>
          <th>Content:</th>
          <td>Block, inline, and text</td>
        </tr>
        <tr>
          <th>Start/End Tag:</th>
          <td>Start tag: <strong>required</strong>, End tag: <strong>required</strong></td>
        </tr>
        <tr>
          <th>Version:</th>
          <td>HTML 4, 4.01, 5</td>
        </tr>
      </table>

      <!-- Note Box -->
      <div class="note-box">
        <i class="bi bi-info-circle-fill"></i>
        <div>
          <div class="note-box-title">Note:</div>
          The <code>&lt;q&gt;</code> tag is intended for short quotations (inline-level content) that don't require paragraph breaks, for long quotations (block-level content) use the <code>&lt;blockquote&gt;</code> tag instead.
        </div>
      </div>

      <div class="section-title">Syntax</div>
      <p class="section-desc">The basic syntax of the <code>&lt;q&gt;</code> tag is given with:</p>
      <div class="code-box">
        &lt;q&gt;Some quotation text&lt;/q&gt;
      </div>

    </main>

  </div>

</body>
</html>