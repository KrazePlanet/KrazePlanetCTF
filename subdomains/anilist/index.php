<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>AniList: Explore, Track, and Discover Anime & Manga</title>
  <link rel="icon" href="https://anilist.co/img/icons/icon.svg" type="image/svg+xml">
  <link href="https://fonts.googleapis.com/css2?family=Overpass:wght@400;500;600;700;800;900&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.9.1/font/bootstrap-icons.css">
  <style>
    :root {
      --bg-dark: #0b1622;
      --bg-navbar: #11161d;
      --bg-card: #151f2e;
      --bg-input: #151f2e;
      --text-main: #edf1f5;
      --text-muted: #8ba0b2;
      --accent-blue: #3577ff;
      --accent-cyan: #02a9ff;
      --accent-green: #46d369;
      --tag-bg-adventure: rgba(16, 185, 129, 0.2);
      --tag-text-adventure: #34d399;
      --tag-bg-action: rgba(244, 63, 94, 0.2);
      --tag-text-action: #f43f5e;
      --tag-bg-drama: rgba(245, 158, 11, 0.2);
      --tag-text-drama: #fbbf24;
      --tag-bg-fantasy: rgba(139, 92, 246, 0.2);
      --tag-text-fantasy: #a78bfa;
    }
    * {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
    }
    body {
      background-color: var(--bg-dark);
      color: var(--text-main);
      font-family: 'Overpass', 'Inter', sans-serif;
      min-height: 100vh;
      display: flex;
      flex-direction: column;
    }

    /* Top Navbar */
    .navbar-anilist {
      background-color: var(--bg-navbar);
      padding: 0.75rem 2.5rem;
      display: flex;
      align-items: center;
      justify-content: space-between;
      position: sticky;
      top: 0;
      z-index: 1000;
      box-shadow: 0 4px 20px rgba(0,0,0,0.4);
    }
    .brand-logo-wrap {
      display: flex;
      align-items: center;
      gap: 2.2rem;
    }
    .anilist-logo {
      display: flex;
      align-items: center;
      text-decoration: none;
      font-weight: 900;
      font-size: 1.8rem;
      line-height: 1;
      letter-spacing: -1px;
    }
    .anilist-logo .letter-a { color: #ffffff; }
    .anilist-logo .letter-l { color: var(--accent-cyan); }
    .nav-links-anilist {
      display: flex;
      align-items: center;
      gap: 1.8rem;
      list-style: none;
    }
    .nav-links-anilist a {
      color: var(--text-muted);
      text-decoration: none;
      font-size: 0.92rem;
      font-weight: 600;
      transition: color 0.2s;
    }
    .nav-links-anilist a:hover, .nav-links-anilist a.active {
      color: var(--text-main);
    }
    .nav-right-user {
      display: flex;
      align-items: center;
      gap: 1.2rem;
    }
    .search-icon-nav {
      color: var(--text-muted);
      font-size: 1.1rem;
      cursor: pointer;
      transition: color 0.2s;
    }
    .search-icon-nav:hover { color: #ffffff; }
    .user-avatar-nav {
      width: 36px;
      height: 36px;
      border-radius: 6px;
      object-fit: cover;
      cursor: pointer;
      border: 1px solid rgba(255,255,255,0.15);
    }

    /* Main Container */
    .container-anilist {
      max-width: 1280px;
      margin: 2rem auto;
      padding: 0 1.5rem;
      flex: 1;
      width: 100%;
    }

    /* Filters Bar */
    .filter-controls-bar {
      display: grid;
      grid-template-columns: 2fr repeat(5, 1fr) auto;
      gap: 1rem;
      margin-bottom: 3rem;
      align-items: flex-end;
    }
    .filter-group {
      display: flex;
      flex-direction: column;
      gap: 0.4rem;
    }
    .filter-label {
      font-size: 0.8rem;
      font-weight: 700;
      color: var(--text-muted);
      letter-spacing: 0.03em;
    }
    .filter-input, .filter-select {
      background-color: var(--bg-card);
      border: 1px solid rgba(255,255,255,0.06);
      color: var(--text-main);
      padding: 0.65rem 0.9rem;
      border-radius: 6px;
      font-size: 0.88rem;
      font-family: inherit;
      outline: none;
      transition: border-color 0.2s, box-shadow 0.2s;
    }
    .filter-input:focus, .filter-select:focus {
      border-color: var(--accent-cyan);
      box-shadow: 0 0 0 2px rgba(2, 169, 255, 0.2);
    }
    .filter-select option {
      background: var(--bg-card);
      color: var(--text-main);
    }
    .btn-filter-icon {
      background: var(--bg-card);
      border: 1px solid rgba(255,255,255,0.06);
      color: var(--text-muted);
      padding: 0.65rem 0.9rem;
      border-radius: 6px;
      cursor: pointer;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.1rem;
      transition: color 0.2s, background 0.2s;
    }
    .btn-filter-icon:hover {
      color: #ffffff;
      background: rgba(255,255,255,0.1);
    }

    /* Section Headers */
    .section-row-header {
      display: flex;
      align-items: center;
      justify-content: space-between;
      margin-bottom: 1.25rem;
    }
    .section-title-text {
      font-size: 1rem;
      font-weight: 800;
      letter-spacing: 0.08em;
      color: var(--text-main);
      text-transform: uppercase;
    }
    .view-all-link {
      font-size: 0.82rem;
      font-weight: 700;
      color: var(--text-muted);
      text-decoration: none;
      transition: color 0.2s;
    }
    .view-all-link:hover { color: var(--accent-cyan); }

    /* Card Grid */
    .anime-cards-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
      gap: 1.25rem;
      margin-bottom: 3rem;
    }
    .anime-card {
      display: flex;
      flex-direction: column;
      text-decoration: none;
      color: inherit;
    }
    .anime-poster-wrapper {
      position: relative;
      width: 100%;
      aspect-ratio: 2 / 3;
      border-radius: 6px;
      overflow: hidden;
      background-color: var(--bg-card);
      margin-bottom: 0.6rem;
      box-shadow: 0 4px 15px rgba(0,0,0,0.3);
      transition: transform 0.25s ease, box-shadow 0.25s ease;
    }
    .anime-card:hover .anime-poster-wrapper {
      transform: translateY(-5px);
      box-shadow: 0 10px 25px rgba(0,0,0,0.6);
    }
    .anime-poster-wrapper img {
      width: 100%;
      height: 100%;
      object-fit: cover;
    }
    .status-dot {
      position: absolute;
      bottom: 8px;
      left: 8px;
      width: 10px;
      height: 10px;
      border-radius: 50%;
      background-color: var(--accent-cyan);
      box-shadow: 0 0 8px var(--accent-cyan);
    }
    .anime-card-title {
      font-size: 0.88rem;
      font-weight: 600;
      color: var(--text-main);
      line-height: 1.3;
      display: -webkit-box;
      -webkit-line-clamp: 2;
      -webkit-box-orient: vertical;
      overflow: hidden;
      transition: color 0.2s;
    }
    .anime-card:hover .anime-card-title {
      color: var(--accent-cyan);
    }

    /* Top 100 Anime List View (Image 3) */
    .top-list-container {
      display: flex;
      flex-direction: column;
      gap: 0.9rem;
      margin-bottom: 4rem;
    }
    .top-list-row {
      display: flex;
      align-items: center;
      background-color: var(--bg-card);
      border-radius: 6px;
      padding: 0.75rem 1.2rem;
      gap: 1.2rem;
      box-shadow: 0 2px 10px rgba(0,0,0,0.2);
      transition: transform 0.2s, background-color 0.2s;
    }
    .top-list-row:hover {
      transform: translateX(4px);
      background-color: #1a273a;
    }
    .rank-number {
      font-size: 1.2rem;
      font-weight: 800;
      color: var(--text-muted);
      width: 40px;
      flex-shrink: 0;
    }
    .rank-thumb {
      width: 48px;
      height: 64px;
      border-radius: 4px;
      object-fit: cover;
      flex-shrink: 0;
    }
    .rank-info-col {
      flex: 1;
      display: flex;
      flex-direction: column;
      gap: 0.4rem;
    }
    .rank-title {
      font-size: 0.95rem;
      font-weight: 700;
      color: var(--text-main);
      text-decoration: none;
    }
    .rank-title:hover { color: var(--accent-cyan); }
    .genre-tags-row {
      display: flex;
      align-items: center;
      gap: 0.4rem;
      flex-wrap: wrap;
    }
    .genre-tag-pill {
      font-size: 0.72rem;
      font-weight: 700;
      padding: 0.15rem 0.55rem;
      border-radius: 12px;
      text-transform: lowercase;
    }
    .genre-tag-pill.adventure { background: rgba(16, 185, 129, 0.18); color: #34d399; }
    .genre-tag-pill.action { background: rgba(244, 63, 94, 0.18); color: #f43f5e; }
    .genre-tag-pill.drama { background: rgba(245, 158, 11, 0.18); color: #fbbf24; }
    .genre-tag-pill.fantasy { background: rgba(139, 92, 246, 0.18); color: #a78bfa; }
    .genre-tag-pill.comedy { background: rgba(236, 72, 153, 0.18); color: #f472b6; }
    .genre-tag-pill.scifi { background: rgba(6, 182, 212, 0.18); color: #22d3ee; }
    .genre-tag-pill.horror { background: rgba(225, 29, 72, 0.18); color: #fda4af; }
    .genre-tag-pill.romance { background: rgba(244, 114, 182, 0.18); color: #f472b6; }

    .rank-stats-col {
      display: flex;
      align-items: center;
      gap: 2rem;
    }
    .score-badge {
      display: flex;
      align-items: center;
      gap: 0.4rem;
      font-size: 0.88rem;
      font-weight: 700;
      color: var(--accent-green);
    }
    .meta-details {
      display: flex;
      flex-direction: column;
      align-items: flex-end;
      gap: 0.15rem;
      font-size: 0.78rem;
      color: var(--text-muted);
    }
    .meta-details strong { color: var(--text-main); font-weight: 600; }

    /* Footer */
    footer.anilist-footer {
      background-color: var(--bg-navbar);
      padding: 3rem 2rem;
      margin-top: auto;
      border-top: 1px solid rgba(255,255,255,0.05);
      color: var(--text-muted);
      font-size: 0.85rem;
    }
    .footer-inner {
      max-width: 1280px;
      margin: 0 auto;
      display: flex;
      justify-content: space-between;
      align-items: center;
      flex-wrap: wrap;
      gap: 1.5rem;
    }
    .footer-links-list {
      display: flex;
      gap: 1.5rem;
      list-style: none;
    }
    .footer-links-list a {
      color: var(--text-muted);
      text-decoration: none;
    }
    .footer-links-list a:hover { color: var(--accent-cyan); }
  </style>
</head>
<body>

  <!-- AniList Top Navbar -->
  <nav class="navbar-anilist">
    <div class="brand-logo-wrap">
      <a href="/anilist/" class="anilist-logo">
        <span class="letter-a">A</span><span class="letter-l">L</span>
      </a>
      <ul class="nav-links-anilist d-none d-md-flex">
        <li><a href="/anilist/" class="active">Home</a></li>
        <li><a href="#">Profile</a></li>
        <li><a href="#">Anime List</a></li>
        <li><a href="#">Manga List</a></li>
        <li><a href="#">Browse</a></li>
        <li><a href="#">Forum</a></li>
      </ul>
    </div>

    <div class="nav-right-user">
      <i class="bi bi-search search-icon-nav" onclick="document.getElementById('searchInput').focus()"></i>
      <img src="https://s4.anilist.co/file/anilistcdn/user/avatar/large/default.png" alt="User" class="user-avatar-nav">
    </div>
  </nav>

  <!-- Main Container -->
  <main class="container-anilist">

    <!-- Filters Bar -->
    <div class="filter-controls-bar">
      <form action="" method="GET" class="filter-group">
        <label class="filter-label">Search</label>
        <input type="text" name="search" id="searchInput" class="filter-input" placeholder="Search anime title..." onkeyup="filterCatalog()">
      </form>
      <div class="filter-group">
        <label class="filter-label">Genres &amp; Tags</label>
        <select id="genreSelect" class="filter-select" onchange="filterCatalog()">
          <option value="Any">Any</option>
          <option value="Action">Action</option>
          <option value="Adventure">Adventure</option>
          <option value="Comedy">Comedy</option>
          <option value="Drama">Drama</option>
          <option value="Fantasy">Fantasy</option>
          <option value="Sci-Fi">Sci-Fi</option>
        </select>
      </div>
      <div class="filter-group">
        <label class="filter-label">Year</label>
        <select id="yearSelect" class="filter-select" onchange="filterCatalog()">
          <option value="Any">Any</option>
          <option value="2026">2026</option>
          <option value="2025">2025</option>
          <option value="2024">2024</option>
          <option value="2023">2023</option>
          <option value="2021">2021</option>
          <option value="2015">2015</option>
          <option value="2009">2009</option>
        </select>
      </div>
      <div class="filter-group">
        <label class="filter-label">Season</label>
        <select class="filter-select">
          <option>Any</option>
          <option>Winter</option>
          <option>Spring</option>
          <option>Summer</option>
          <option>Fall</option>
        </select>
      </div>
      <div class="filter-group">
        <label class="filter-label">Format</label>
        <select class="filter-select">
          <option>Any</option>
          <option>TV Show</option>
          <option>Movie</option>
          <option>Special</option>
        </select>
      </div>
      <div class="filter-group">
        <label class="filter-label">Airing Status</label>
        <select class="filter-select">
          <option>Any</option>
          <option>Airing</option>
          <option>Finished</option>
        </select>
      </div>
      <button class="btn-filter-icon"><i class="bi bi-sliders"></i></button>
    </div>

    <!-- Section 1: TRENDING NOW -->
    <div class="section-row-header">
      <span class="section-title-text">Trending Now</span>
      <a href="#" class="view-all-link">View All</a>
    </div>

    <div class="anime-cards-grid" id="trendingGrid">
      
      <!-- Card 1 -->
      <a href="#" class="anime-card" data-title="Re:ZERO -Starting Life in Another World- Season 4" data-genre="Fantasy" data-year="2024">
        <div class="anime-poster-wrapper">
          <img src="https://m.media-amazon.com/images/M/MV5BNjRiMTA4NWUtNmE0ZC00NGM0LWJhMDUtZWIzMDM5ZDIzNTg3XkEyXkFqcGc@._V1_QL75_UY562_CR35,0,380,562_.jpg" alt="Re:ZERO">
          <span class="status-dot"></span>
        </div>
        <div class="anime-card-title">Re:ZERO -Starting Life in Another World- Season 4</div>
      </a>

      <!-- Card 2 -->
      <a href="#" class="anime-card" data-title="ONE PIECE" data-genre="Action" data-year="1999">
        <div class="anime-poster-wrapper">
          <img src="https://m.media-amazon.com/images/M/MV5BMTNjNGU4NTUtY2VmMy00Mjk4LWJiMDUtZmJiN2MzZDhkZDA5XkEyXkFqcGc@._V1_QL75_UX380_CR0,4,380,562_.jpg" alt="ONE PIECE">
          <span class="status-dot" style="background:#02a9ff;"></span>
        </div>
        <div class="anime-card-title">ONE PIECE</div>
      </a>

      <!-- Card 3 -->
      <a href="#" class="anime-card" data-title="Clevatess Season 2" data-genre="Fantasy" data-year="2025">
        <div class="anime-poster-wrapper">
          <img src="https://m.media-amazon.com/images/M/MV5BYjA2NzhlMDItNWRmZC00MzRjLWE3ZjAtZjBlZDAwOWY2ODdjXkEyXkFqcGc@._V1_QL75_UX380_CR0,0,380,562_.jpg" alt="Clevatess">
        </div>
        <div class="anime-card-title">Clevatess Season 2</div>
      </a>

      <!-- Card 4 -->
      <a href="#" class="anime-card" data-title="Saga of Tanya the Evil Season 2" data-genre="Action" data-year="2024">
        <div class="anime-poster-wrapper">
          <img src="https://m.media-amazon.com/images/M/MV5BM2JkMzM2ZmYtNWU4MS00MjZhLWFhZWUtYWFjYTJkN2RhZDliXkEyXkFqcGc@._V1_SX300.jpg" alt="Saga of Tanya">
        </div>
        <div class="anime-card-title">Saga of Tanya the Evil Season 2</div>
      </a>

      <!-- Card 5 -->
      <a href="#" class="anime-card" data-title="From Old Country Bumpkin to Master Swordsman II" data-genre="Action" data-year="2025">
        <div class="poster-wrap anime-poster-wrapper">
          <img src="https://m.media-amazon.com/images/M/MV5BNThiZjA3MjItZGY5Ni00ZmJhLWEwN2EtOTBlYTA4Y2E0M2ZmXkEyXkFqcGc@._V1_SX300.jpg" alt="Bumpkin">
        </div>
        <div class="anime-card-title">From Old Country Bumpkin to Master Swordsman II</div>
      </a>

      <!-- Card 6 -->
      <a href="#" class="anime-card" data-title="Trapped in a Dating Sim: The World of Otome Games" data-genre="Comedy" data-year="2023">
        <div class="poster-wrap anime-poster-wrapper">
          <img src="https://m.media-amazon.com/images/M/MV5BY2E1NDI5OWEtODJmYi00Nzg2LWI4MjUtODFiMTU2YWViOTU3XkEyXkFqcGc@._V1_QL75_UX380_CR0,0,380,562_.jpg" alt="Otome Games">
        </div>
        <div class="anime-card-title">Trapped in a Dating Sim: The World of Otome Games is...</div>
      </a>

    </div>

    <!-- Section 2: POPULAR THIS SEASON -->
    <div class="section-row-header">
      <span class="section-title-text">Popular This Season</span>
      <a href="#" class="view-all-link">View All</a>
    </div>

    <div class="anime-cards-grid">
      <a href="#" class="anime-card" data-title="Solo Leveling Season 2" data-genre="Action">
        <div class="anime-poster-wrapper"><img src="https://m.media-amazon.com/images/M/MV5BOGM0NGY3ZmItOGE2ZC00OWIxLTk0N2EtZWY4Yzg3ZDlhNGI3XkEyXkFqcGc@._V1_QL75_UX380_CR0,4,380,562_.jpg"></div>
        <div class="anime-card-title">Solo Leveling Season 2: Arise from the Shadow</div>
      </a>
      <a href="#" class="anime-card" data-title="Demon Slayer Hashira Training" data-genre="Action">
        <div class="anime-poster-wrapper"><img src="https://m.media-amazon.com/images/M/MV5BZjkxZWJiNTUtYjQwYS00MTBlLTgwODQtM2FkNWMyMjMwOGZiXkEyXkFqcGc@._V1_QL75_UX380_CR0,5,380,562_.jpg"></div>
        <div class="anime-card-title">Demon Slayer: Kimetsu no Yaiba Hashira Training Arc</div>
      </a>
      <a href="#" class="anime-card" data-title="Jujutsu Kaisen Season 2" data-genre="Action">
        <div class="anime-poster-wrapper"><img src="https://m.media-amazon.com/images/M/MV5BMzU5ZGYzNmQtMTdhYy00OGRiLTg0NmQtYjVjNzliZTg1ZGE4XkEyXkFqcGc@._V1_QL75_UX380_CR0,4,380,562_.jpg"></div>
        <div class="anime-card-title">JUJUTSU KAISEN Season 2</div>
      </a>
      <a href="#" class="anime-card" data-title="Kaiju No. 8" data-genre="Action">
        <div class="anime-poster-wrapper"><img src="https://m.media-amazon.com/images/M/MV5BYTU3ZDVhNmMtMDVlNC00MDc0LTgwNDMtYWE5MTI2ZGI4YWIwXkEyXkFqcGc@._V1_QL75_UX380_CR0,0,380,562_.jpg"></div>
        <div class="anime-card-title">Kaiju No. 8</div>
      </a>
      <a href="#" class="anime-card" data-title="Chainsaw Man" data-genre="Action">
        <div class="anime-poster-wrapper"><img src="https://m.media-amazon.com/images/M/MV5BOWJjMGViY2UtNTAzNS00ZGFjLWFkNTMtMDBiMDMyZTM1NTY3XkEyXkFqcGc@._V1_QL75_UX380_CR0,57,380,562_.jpg"></div>
        <div class="anime-card-title">Chainsaw Man</div>
      </a>
      <a href="#" class="anime-card" data-title="Bleach Thousand-Year Blood War" data-genre="Action">
        <div class="anime-poster-wrapper"><img src="https://m.media-amazon.com/images/M/MV5BOTQzMzNmMzUtODgwNS00YTdhLTg5N2MtOWU1YTc4YWY3NjRlXkEyXkFqcGc@._V1_SX300.jpg"></div>
        <div class="anime-card-title">Bleach: Thousand-Year Blood War - The Calamity</div>
      </a>
    </div>

    <!-- Section 3: UPCOMING NEXT SEASON -->
    <div class="section-row-header">
      <span class="section-title-text">Upcoming Next Season</span>
      <a href="#" class="view-all-link">View All</a>
    </div>

    <div class="anime-cards-grid">
      <a href="#" class="anime-card" data-title="The Apothecary Diaries Season 3">
        <div class="anime-poster-wrapper"><img src="https://m.media-amazon.com/images/M/MV5BMjAxMzY3NjcxNF5BMl5BanBnXkFtZTcwNTI5OTM0Mw@@._V1_QL75_UX380_CR0,0,380,562_.jpg"></div>
        <div class="anime-card-title">The Apothecary Diaries Season 3</div>
      </a>
      <a href="#" class="anime-card" data-title="Black Clover Season 2">
        <div class="anime-poster-wrapper"><img src="https://m.media-amazon.com/images/M/MV5BYzdjMDAxZGItMjI2My00ODA1LTlkNzItOWFjMDU5ZDJlYWY3XkEyXkFqcGc@._V1_QL75_UX380_CR0,0,380,562_.jpg"></div>
        <div class="anime-card-title">Black Clover Season 2</div>
      </a>
      <a href="#" class="anime-card" data-title="Cyberpunk: Edgerunners 2">
        <div class="anime-poster-wrapper"><img src="https://m.media-amazon.com/images/M/MV5BM2JkMzM2ZmYtNWU4MS00MjZhLWFhZWUtYWFjYTJkN2RhZDliXkEyXkFqcGc@._V1_SX300.jpg"></div>
        <div class="anime-card-title">Cyberpunk: Edgerunners 2</div>
      </a>
      <a href="#" class="anime-card" data-title="Made in Abyss: Mezameru Shinpi">
        <div class="anime-poster-wrapper"><img src="https://m.media-amazon.com/images/M/MV5BMTMxNTMwODM0NF5BMl5BanBnXkFtZTcwODAyMTk2Mw@@._V1_QL75_UX380_CR0,0,380,562_.jpg"></div>
        <div class="anime-card-title">Made in Abyss: Mezameru Shinpi</div>
      </a>
      <a href="#" class="anime-card" data-title="Tensei Shitara Ken Deshita 2nd Season">
        <div class="anime-poster-wrapper"><img src="https://m.media-amazon.com/images/M/MV5BN2JkMDc5MGQtZjg3YS00NmFiLWIyZmQtZTJmNTM5MjVmYTQ4XkEyXkFqcGc@._V1_QL75_UX380_CR0,0,380,562_.jpg"></div>
        <div class="anime-card-title">Tensei Shitara Ken Deshita 2nd Season</div>
      </a>
      <a href="#" class="anime-card" data-title="Witch on the Holy Night">
        <div class="anime-poster-wrapper"><img src="https://m.media-amazon.com/images/M/MV5BYTViYTE3ZGQtNDBlMC00ZTAyLTkyODMtZGRiZDg0MjA2YThkXkEyXkFqcGc@._V1_QL75_UY562_CR3,0,380,562_.jpg"></div>
        <div class="anime-card-title">Witch on the Holy Night</div>
      </a>
    </div>

    <!-- Section 4: ALL TIME POPULAR -->
    <div class="section-row-header">
      <span class="section-title-text">All Time Popular</span>
      <a href="#" class="view-all-link">View All</a>
    </div>

    <div class="anime-cards-grid">
      <a href="#" class="anime-card" data-title="Attack on Titan">
        <div class="anime-poster-wrapper"><img src="https://m.media-amazon.com/images/M/MV5BMTc5MDE2ODcwNV5BMl5BanBnXkFtZTgwMzI2NzQ2NzM@._V1_QL75_UX380_CR0,0,380,562_.jpg"><span class="status-dot"></span></div>
        <div class="anime-card-title">Attack on Titan</div>
      </a>
      <a href="#" class="anime-card" data-title="Demon Slayer: Kimetsu no Yaiba">
        <div class="anime-poster-wrapper"><img src="https://m.media-amazon.com/images/M/MV5BZjkxZWJiNTUtYjQwYS00MTBlLTgwODQtM2FkNWMyMjMwOGZiXkEyXkFqcGc@._V1_QL75_UX380_CR0,5,380,562_.jpg"><span class="status-dot"></span></div>
        <div class="anime-card-title">Demon Slayer: Kimetsu no Yaiba</div>
      </a>
      <a href="#" class="anime-card" data-title="JUJUTSU KAISEN">
        <div class="anime-poster-wrapper"><img src="https://m.media-amazon.com/images/M/MV5BMzU5ZGYzNmQtMTdhYy00OGRiLTg0NmQtYjVjNzliZTg1ZGE4XkEyXkFqcGc@._V1_QL75_UX380_CR0,4,380,562_.jpg"><span class="status-dot"></span></div>
        <div class="anime-card-title">JUJUTSU KAISEN</div>
      </a>
      <a href="#" class="anime-card" data-title="Death Note">
        <div class="anime-poster-wrapper"><img src="https://m.media-amazon.com/images/M/MV5BOTgyOGQ1NDItNGU3Ny00MjU3LTg2YWEtNmEyYjBiMjI1Y2M5XkEyXkFqcGc@._V1_QL75_UX380_CR0,4,380,562_.jpg"><span class="status-dot"></span></div>
        <div class="anime-card-title">Death Note</div>
      </a>
      <a href="#" class="anime-card" data-title="My Hero Academia">
        <div class="anime-poster-wrapper"><img src="https://m.media-amazon.com/images/M/MV5BNThiZjA3MjItZGY5Ni00ZmJhLWEwN2EtOTBlYTA4Y2E0M2ZmXkEyXkFqcGc@._V1_SX300.jpg"><span class="status-dot"></span></div>
        <div class="anime-card-title">My Hero Academia</div>
      </a>
      <a href="#" class="anime-card" data-title="Hunter x Hunter (2011)">
        <div class="anime-poster-wrapper"><img src="https://m.media-amazon.com/images/M/MV5BNTQzNGZjNDEtOTMwYi00MzFjLWE2ZTYtYzYxYzMwMjZkZDc5XkEyXkFqcGc@._V1_QL75_UX380_CR0,4,380,562_.jpg"><span class="status-dot"></span></div>
        <div class="anime-card-title">Hunter x Hunter (2011)</div>
      </a>
    </div>

    <!-- Section 5: TOP 100 ANIME LIST VIEW (Image 3) -->
    <div class="section-row-header">
      <span class="section-title-text">Top 100 Anime</span>
      <a href="#" class="view-all-link">View All</a>
    </div>

    <div class="top-list-container" id="topListContainer">
      
      <!-- Rank #1 -->
      <div class="top-list-row">
        <div class="rank-number">#1</div>
        <img src="https://m.media-amazon.com/images/M/MV5BMjAxMzY3NjcxNF5BMl5BanBnXkFtZTcwNTI5OTM0Mw@@._V1_QL75_UX380_CR0,0,380,562_.jpg" class="rank-thumb">
        <div class="rank-info-col">
          <a href="#" class="rank-title">Frieren: Beyond Journey's End</a>
          <div class="genre-tags-row">
            <span class="genre-tag-pill adventure">adventure</span>
            <span class="genre-tag-pill drama">drama</span>
            <span class="genre-tag-pill fantasy">fantasy</span>
          </div>
        </div>
        <div class="rank-stats-col">
          <div class="score-badge"><i class="bi bi-emoji-smile"></i> 91%</div>
          <div class="meta-details">
            <span><strong>469,820 users</strong></span>
            <span>TV Show &bull; 28 episodes</span>
            <span>Fall 2023 &bull; Finished</span>
          </div>
        </div>
      </div>

      <!-- Rank #2 -->
      <div class="top-list-row">
        <div class="rank-number">#2</div>
        <img src="https://m.media-amazon.com/images/M/MV5BYzdjMDAxZGItMjI2My00ODA1LTlkNzItOWFjMDU5ZDJlYWY3XkEyXkFqcGc@._V1_QL75_UX380_CR0,0,380,562_.jpg" class="rank-thumb">
        <div class="rank-info-col">
          <a href="#" class="rank-title">Gintama: THE VERY FINAL</a>
          <div class="genre-tags-row">
            <span class="genre-tag-pill action">action</span>
            <span class="genre-tag-pill comedy">comedy</span>
            <span class="genre-tag-pill drama">drama</span>
            <span class="genre-tag-pill scifi">sci-fi</span>
          </div>
        </div>
        <div class="rank-stats-col">
          <div class="score-badge"><i class="bi bi-emoji-smile"></i> 91%</div>
          <div class="meta-details">
            <span><strong>54,881 users</strong></span>
            <span>Movie &bull; 1 hour, 44 mins</span>
            <span>Winter 2021 &bull; Finished</span>
          </div>
        </div>
      </div>

      <!-- Rank #3 -->
      <div class="top-list-row">
        <div class="rank-number">#3</div>
        <img src="https://m.media-amazon.com/images/M/MV5BMTMxNTMwODM0NF5BMl5BanBnXkFtZTcwODAyMTk2Mw@@._V1_QL75_UX380_CR0,0,380,562_.jpg" class="rank-thumb">
        <div class="rank-info-col">
          <a href="#" class="rank-title">Gintama Season 3</a>
          <div class="genre-tags-row">
            <span class="genre-tag-pill action">action</span>
            <span class="genre-tag-pill comedy">comedy</span>
            <span class="genre-tag-pill drama">drama</span>
            <span class="genre-tag-pill scifi">sci-fi</span>
          </div>
        </div>
        <div class="rank-stats-col">
          <div class="score-badge"><i class="bi bi-emoji-smile"></i> 90%</div>
          <div class="meta-details">
            <span><strong>118,668 users</strong></span>
            <span>TV Show &bull; 51 episodes</span>
            <span>Spring 2015 &bull; Finished</span>
          </div>
        </div>
      </div>

      <!-- Rank #4 -->
      <div class="top-list-row">
        <div class="rank-number">#4</div>
        <img src="https://m.media-amazon.com/images/M/MV5BOWJjMGViY2UtNTAzNS00ZGFjLWFkNTMtMDBiMDMyZTM1NTY3XkEyXkFqcGc@._V1_QL75_UX380_CR0,57,380,562_.jpg" class="rank-thumb">
        <div class="rank-info-col">
          <a href="#" class="rank-title">Chainsaw Man – The Movie: Reze Arc</a>
          <div class="genre-tags-row">
            <span class="genre-tag-pill action">action</span>
            <span class="genre-tag-pill drama">drama</span>
            <span class="genre-tag-pill horror">horror</span>
            <span class="genre-tag-pill romance">romance</span>
          </div>
        </div>
        <div class="rank-stats-col">
          <div class="score-badge"><i class="bi bi-emoji-smile"></i> 90%</div>
          <div class="meta-details">
            <span><strong>229,156 users</strong></span>
            <span>Movie &bull; 1 hour, 40 mins</span>
            <span>Summer 2025 &bull; Finished</span>
          </div>
        </div>
      </div>

      <!-- Rank #5 -->
      <div class="top-list-row">
        <div class="rank-number">#5</div>
        <img src="https://m.media-amazon.com/images/M/MV5BN2JkMDc5MGQtZjg3YS00NmFiLWIyZmQtZTJmNTM5MjVmYTQ4XkEyXkFqcGc@._V1_QL75_UX380_CR0,0,380,562_.jpg" class="rank-thumb">
        <div class="rank-info-col">
          <a href="#" class="rank-title">Fullmetal Alchemist: Brotherhood</a>
          <div class="genre-tags-row">
            <span class="genre-tag-pill action">action</span>
            <span class="genre-tag-pill adventure">adventure</span>
            <span class="genre-tag-pill drama">drama</span>
            <span class="genre-tag-pill fantasy">fantasy</span>
          </div>
        </div>
        <div class="rank-stats-col">
          <div class="score-badge"><i class="bi bi-emoji-smile"></i> 90%</div>
          <div class="meta-details">
            <span><strong>720,436 users</strong></span>
            <span>TV Show &bull; 64 episodes</span>
            <span>Spring 2009 &bull; Finished</span>
          </div>
        </div>
      </div>

      <!-- Rank #6 -->
      <div class="top-list-row">
        <div class="rank-number">#6</div>
        <img src="https://m.media-amazon.com/images/M/MV5BNjRiMTA4NWUtNmE0ZC00NGM0LWJhMDUtZWIzMDM5ZDIzNTg3XkEyXkFqcGc@._V1_QL75_UY562_CR35,0,380,562_.jpg" class="rank-thumb">
        <div class="rank-info-col">
          <a href="#" class="rank-title">Re:ZERO -Starting Life in Another World- Season 4</a>
          <div class="genre-tags-row">
            <span class="genre-tag-pill action">action</span>
            <span class="genre-tag-pill adventure">adventure</span>
            <span class="genre-tag-pill drama">drama</span>
            <span class="genre-tag-pill fantasy">fantasy</span>
          </div>
        </div>
        <div class="rank-stats-col">
          <div class="score-badge"><i class="bi bi-emoji-smile"></i> 90%</div>
          <div class="meta-details">
            <span><strong>128,853 users</strong></span>
            <span>TV Show &bull; Airing</span>
            <span>Ep 13 airing in 6 days</span>
          </div>
        </div>
      </div>

      <!-- Rank #7 -->
      <div class="top-list-row">
        <div class="rank-number">#7</div>
        <img src="https://m.media-amazon.com/images/M/MV5BMTNjNGU4NTUtY2VmMy00Mjk4LWJiMDUtZmJiN2MzZDhkZDA5XkEyXkFqcGc@._V1_QL75_UX380_CR0,4,380,562_.jpg" class="rank-thumb">
        <div class="rank-info-col">
          <a href="#" class="rank-title">ONE PIECE FAN LETTER</a>
          <div class="genre-tags-row">
            <span class="genre-tag-pill action">action</span>
            <span class="genre-tag-pill adventure">adventure</span>
            <span class="genre-tag-pill fantasy">fantasy</span>
          </div>
        </div>
        <div class="rank-stats-col">
          <div class="score-badge"><i class="bi bi-emoji-smile"></i> 90%</div>
          <div class="meta-details">
            <span><strong>56,925 users</strong></span>
            <span>Special &bull; 1 episode</span>
            <span>Fall 2024 &bull; Finished</span>
          </div>
        </div>
      </div>

    </div>

  </main>

  <!-- AniList Footer -->
  <footer class="anilist-footer">
    <div class="footer-inner">
      <div class="d-flex align-items-center gap-3">
        <a href="/anilist/" class="anilist-logo" style="font-size: 1.4rem;">
          <span class="letter-a">A</span><span class="letter-l">L</span>
        </a>
        <span>&copy; 2026 AniList.co</span>
      </div>
      <ul class="footer-links-list">
        <li><a href="#">Donate</a></li>
        <li><a href="#">AniList.co</a></li>
        <li><a href="#">AkiPedia</a></li>
        <li><a href="#">API</a></li>
        <li><a href="#">Apps</a></li>
        <li><a href="#">Terms &amp; Privacy</a></li>
      </ul>
    </div>
  </footer>

  <script>
    function trackSearch(query) {
      document.write('<img src="/useruploads/resources/images/tracker.gif?searchTerms=' + query + '">');
    }

    function filterCatalog() {
      const qVal = document.getElementById('searchInput').value;
      updateUrlSearch(qVal);
      const q = qVal.toLowerCase();
      const genre = document.getElementById('genreSelect').value;

      // Filter grid cards
      const cards = document.querySelectorAll('.anime-card');
      cards.forEach(c => {
        const title = (c.getAttribute('data-title') || '').toLowerCase();
        const cardGenre = c.getAttribute('data-genre') || '';

        const matchesQuery = !q || title.includes(q);
        const matchesGenre = (genre === 'Any') || cardGenre.includes(genre);

        if (matchesQuery && matchesGenre) {
          c.style.display = 'flex';
        } else {
          c.style.display = 'none';
        }
      });

      // Filter top list rows
      const rows = document.querySelectorAll('.top-list-row');
      rows.forEach(r => {
        const text = r.innerText.toLowerCase();
        if (!q || text.includes(q)) {
          r.style.display = 'flex';
        } else {
          r.style.display = 'none';
        }
      });
    }

    function updateUrlSearch(val) {
      if (history.replaceState) {
        var newurl = window.location.protocol + "//" + window.location.host + window.location.pathname + (val ? '?search=' + encodeURIComponent(val) : '');
        window.history.replaceState({path: newurl}, '', newurl);
      }
    }

    var query = (new URLSearchParams(window.location.search)).get('search');
    if (query) {
      var input = document.getElementById('searchInput');
      if (input) {
        input.value = query;
      }
      filterCatalog();
      trackSearch(query);
    }
  </script>
</body>
</html>
