<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>IMDb: Ratings, Reviews, and Where to Watch the Best Movies & TV Shows</title>
  <link rel="icon" href="https://m.media-amazon.com/images/G/01/imdb/images-ANDROIDAUDIENCESERVICE-764324219._CB453896504_.png" type="image/png">
  <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700;900&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.9.1/font/bootstrap-icons.css">
  <style>
    :root {
      --imdb-yellow: #f5c518;
      --imdb-black: #121212;
      --imdb-card-bg: #1a1a1a;
      --imdb-gray-border: #333333;
      --imdb-text-light: #ffffff;
      --imdb-text-secondary: #aaaaaa;
      --imdb-blue: #5799ef;
    }
    * {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
    }
    body {
      background-color: var(--imdb-black);
      color: var(--imdb-text-light);
      font-family: 'Roboto', sans-serif;
      min-height: 100vh;
      display: flex;
      flex-direction: column;
    }

    /* IMDb Top Navbar */
    .navbar-imdb {
      background-color: #121212;
      border-bottom: 1px solid var(--imdb-gray-border);
      padding: 0.6rem 2rem;
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 1.5rem;
      position: sticky;
      top: 0;
      z-index: 1000;
    }
    .imdb-brand {
      background-color: var(--imdb-yellow);
      color: #000000;
      font-weight: 900;
      font-size: 1.4rem;
      padding: 0.2rem 0.6rem;
      border-radius: 4px;
      text-decoration: none;
      letter-spacing: -0.5px;
      line-height: 1;
    }
    .nav-menu-btn {
      display: flex;
      align-items: center;
      gap: 0.4rem;
      color: #ffffff;
      font-weight: 700;
      font-size: 0.9rem;
      text-decoration: none;
      padding: 0.4rem 0.7rem;
      border-radius: 4px;
      transition: background 0.2s;
    }
    .nav-menu-btn:hover {
      background: rgba(255,255,255,0.1);
    }
    .search-bar-wrap {
      flex: 1;
      max-width: 750px;
      display: flex;
      background: #ffffff;
      border-radius: 4px;
      overflow: hidden;
    }
    .search-category-select {
      background: #f0f0f0;
      border: none;
      border-right: 1px solid #ccc;
      padding: 0 0.8rem;
      font-size: 0.85rem;
      font-weight: 500;
      cursor: pointer;
      outline: none;
    }
    .search-input-imdb {
      flex: 1;
      border: none;
      padding: 0.6rem 0.9rem;
      font-size: 0.95rem;
      outline: none;
      color: #111;
    }
    .search-btn-imdb {
      background: #ffffff;
      border: none;
      padding: 0 1rem;
      color: #555555;
      font-size: 1.1rem;
      cursor: pointer;
      transition: color 0.2s;
    }
    .search-btn-imdb:hover {
      color: #000000;
    }
    .nav-right-actions {
      display: flex;
      align-items: center;
      gap: 1.2rem;
    }
    .nav-pro-link {
      color: var(--imdb-blue);
      font-weight: 700;
      font-size: 0.9rem;
      text-decoration: none;
    }
    .watchlist-btn {
      display: flex;
      align-items: center;
      gap: 0.4rem;
      color: #ffffff;
      font-size: 0.9rem;
      font-weight: 500;
      text-decoration: none;
      padding: 0.4rem 0.7rem;
      border-radius: 4px;
    }
    .watchlist-btn:hover {
      background: rgba(255,255,255,0.1);
    }
    .signin-btn {
      color: #ffffff;
      font-size: 0.9rem;
      font-weight: 500;
      text-decoration: none;
    }

    /* Main Section Container */
    .container-imdb {
      max-width: 1250px;
      margin: 2rem auto;
      padding: 0 1.5rem;
      flex: 1;
      width: 100%;
    }

    /* Search Heading */
    .search-title-section {
      margin-bottom: 2rem;
    }
    .search-results-heading {
      font-size: 1.8rem;
      font-weight: 700;
      color: #ffffff;
      margin-bottom: 0.5rem;
    }
    .search-subtext {
      color: var(--imdb-text-secondary);
      font-size: 0.95rem;
    }

    /* Featured Title Banner */
    .featured-hero {
      background: linear-gradient(180deg, rgba(26,26,26,0.5) 0%, rgba(18,18,18,1) 100%), url('https://images.unsplash.com/photo-1489599849927-2ee91cede3ba?w=1600&auto=format&fit=crop&q=80') center/cover;
      border-radius: 12px;
      padding: 3.5rem 2.5rem;
      margin-bottom: 3rem;
      display: flex;
      align-items: flex-end;
      min-height: 380px;
      position: relative;
      border: 1px solid var(--imdb-gray-border);
    }
    .featured-hero-content {
      position: relative;
      z-index: 2;
      max-width: 650px;
    }
    .featured-badge {
      background: var(--imdb-yellow);
      color: #000000;
      font-weight: 900;
      font-size: 0.75rem;
      padding: 0.2rem 0.5rem;
      border-radius: 3px;
      text-transform: uppercase;
      margin-bottom: 0.8rem;
      display: inline-block;
    }
    .featured-title {
      font-size: 2.8rem;
      font-weight: 900;
      margin-bottom: 0.6rem;
      line-height: 1.1;
    }
    .featured-desc {
      color: #dddddd;
      font-size: 1rem;
      line-height: 1.5;
      margin-bottom: 1.2rem;
    }

    /* Section Headers */
    .section-header-imdb {
      display: flex;
      align-items: center;
      gap: 0.6rem;
      font-size: 1.4rem;
      font-weight: 700;
      color: #ffffff;
      margin-bottom: 1.2rem;
      border-left: 4px solid var(--imdb-yellow);
      padding-left: 0.8rem;
    }

    /* Movie Cards Grid */
    .movies-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
      gap: 1.25rem;
      margin-bottom: 3.5rem;
    }
    .movie-card-imdb {
      background-color: var(--imdb-card-bg);
      border-radius: 6px;
      overflow: hidden;
      border: 1px solid var(--imdb-gray-border);
      display: flex;
      flex-direction: column;
      transition: transform 0.2s ease, box-shadow 0.2s ease;
    }
    .movie-card-imdb:hover {
      transform: translateY(-4px);
      box-shadow: 0 8px 24px rgba(0,0,0,0.8);
      border-color: #555;
    }
    .poster-wrap {
      position: relative;
      aspect-ratio: 2 / 3;
      width: 100%;
      overflow: hidden;
      background: #252525;
    }
    .poster-wrap img {
      width: 100%;
      height: 100%;
      object-fit: cover;
    }
    .card-info {
      padding: 0.85rem;
      display: flex;
      flex-direction: column;
      flex: 1;
      gap: 0.4rem;
    }
    .rating-row {
      display: flex;
      align-items: center;
      gap: 0.4rem;
      font-size: 0.85rem;
      color: #ffffff;
      font-weight: 500;
    }
    .rating-row i {
      color: var(--imdb-yellow);
    }
    .movie-item-title {
      font-size: 0.95rem;
      font-weight: 700;
      color: #ffffff;
      text-decoration: none;
      line-height: 1.2;
      display: -webkit-box;
      -webkit-line-clamp: 2;
      -webkit-box-orient: vertical;
      overflow: hidden;
    }
    .movie-item-title:hover {
      text-decoration: underline;
    }
    .movie-year {
      font-size: 0.78rem;
      color: var(--imdb-text-secondary);
    }
    .btn-watchlist-add {
      background: rgba(255,255,255,0.08);
      color: var(--imdb-blue);
      border: none;
      border-radius: 4px;
      padding: 0.5rem;
      font-size: 0.82rem;
      font-weight: 700;
      cursor: pointer;
      margin-top: auto;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 0.3rem;
      transition: background 0.2s;
    }
    .btn-watchlist-add:hover {
      background: rgba(255,255,255,0.18);
    }

    /* Footer */
    footer.imdb-footer {
      background-color: #000000;
      border-top: 1px solid var(--imdb-gray-border);
      padding: 2.5rem 1.5rem;
      text-align: center;
      color: var(--imdb-text-secondary);
      font-size: 0.85rem;
      margin-top: auto;
    }
    .footer-links {
      display: flex;
      justify-content: center;
      gap: 1.5rem;
      flex-wrap: wrap;
      margin-bottom: 1rem;
      list-style: none;
    }
    .footer-links a {
      color: #ffffff;
      text-decoration: none;
    }
    .footer-links a:hover {
      text-decoration: underline;
    }
  </style>
</head>
<body>

  <!-- IMDb Navbar -->
  <nav class="navbar-imdb">
    <div style="display: flex; align-items: center; gap: 1.2rem;">
      <a href="/useruploads/" class="imdb-brand">IMDb</a>
      <a href="#" class="nav-menu-btn"><i class="bi bi-list fs-5"></i> Menu</a>
    </div>

    <!-- Search Form -->
    <div class="search-bar-wrap">
      <select class="search-category-select">
        <option>All</option>
        <option>Titles</option>
        <option>TV Episodes</option>
        <option>Celebs</option>
      </select>
      <form action="" method="GET" style="display: flex; flex: 1;">
        <input type="text" name="search" id="searchInput" class="search-input-imdb" placeholder="Search IMDb">
        <button type="submit" class="search-btn-imdb"><i class="bi bi-search"></i></button>
      </form>
    </div>

    <div class="nav-right-actions d-none d-md-flex">
      <a href="#" class="nav-pro-link">IMDb<span style="color:#fff;">Pro</span></a>
      <a href="#" class="watchlist-btn"><i class="bi bi-bookmark-plus-fill fs-6"></i> Watchlist</a>
      <a href="#" class="signin-btn">Sign In</a>
    </div>
  </nav>

  <!-- Main Content Container -->
  <main class="container-imdb">

    <!-- Search Title Output Container (Static in view-source, Populated dynamically by Client JS) -->
    <div class="search-title-section" id="searchHeaderSection" style="display: none;">
      <h1 class="search-results-heading" id="searchHeading"></h1>
      <p class="search-subtext" id="searchSubtext"></p>
    </div>

    <!-- Featured Banner -->
    <div class="featured-hero">
      <div class="featured-hero-content">
        <span class="featured-badge">Featured Trailer</span>
        <h1 class="featured-title">Inception (2010)</h1>
        <p class="featured-desc">A thief who steals corporate secrets through dream-sharing technology is given the inverse task of planting an idea into the mind of a C.E.O.</p>
      </div>
    </div>

    <!-- Movie Catalog Grid -->
    <div class="section-header-imdb">
      <span>Top Rated Movies</span>
    </div>

    <div class="movies-grid" id="moviesGrid">
      
      <!-- Movie 1 -->
      <div class="movie-card-imdb" data-title="Inception">
        <div class="poster-wrap">
          <img src="https://m.media-amazon.com/images/M/MV5BMjAxMzY3NjcxNF5BMl5BanBnXkFtZTcwNTI5OTM0Mw@@._V1_QL75_UX380_CR0,0,380,562_.jpg" alt="Inception">
        </div>
        <div class="card-info">
          <div class="rating-row"><i class="bi bi-star-fill"></i> 8.8</div>
          <a href="#" class="movie-item-title">Inception</a>
          <span class="movie-year">2010 &bull; Sci-Fi</span>
          <button class="btn-watchlist-add"><i class="bi bi-plus"></i> Watchlist</button>
        </div>
      </div>

      <!-- Movie 2 -->
      <div class="movie-card-imdb" data-title="Interstellar">
        <div class="poster-wrap">
          <img src="https://m.media-amazon.com/images/M/MV5BYzdjMDAxZGItMjI2My00ODA1LTlkNzItOWFjMDU5ZDJlYWY3XkEyXkFqcGc@._V1_QL75_UX380_CR0,0,380,562_.jpg" alt="Interstellar">
        </div>
        <div class="card-info">
          <div class="rating-row"><i class="bi bi-star-fill"></i> 8.7</div>
          <a href="#" class="movie-item-title">Interstellar</a>
          <span class="movie-year">2014 &bull; Sci-Fi</span>
          <button class="btn-watchlist-add"><i class="bi bi-plus"></i> Watchlist</button>
        </div>
      </div>

      <!-- Movie 3 -->
      <div class="movie-card-imdb" data-title="The Dark Knight">
        <div class="poster-wrap">
          <img src="https://m.media-amazon.com/images/M/MV5BMTMxNTMwODM0NF5BMl5BanBnXkFtZTcwODAyMTk2Mw@@._V1_QL75_UX380_CR0,0,380,562_.jpg" alt="The Dark Knight">
        </div>
        <div class="card-info">
          <div class="rating-row"><i class="bi bi-star-fill"></i> 9.0</div>
          <a href="#" class="movie-item-title">The Dark Knight</a>
          <span class="movie-year">2008 &bull; Action</span>
          <button class="btn-watchlist-add"><i class="bi bi-plus"></i> Watchlist</button>
        </div>
      </div>

      <!-- Movie 4 -->
      <div class="movie-card-imdb" data-title="Oppenheimer">
        <div class="poster-wrap">
          <img src="https://m.media-amazon.com/images/M/MV5BN2JkMDc5MGQtZjg3YS00NmFiLWIyZmQtZTJmNTM5MjVmYTQ4XkEyXkFqcGc@._V1_QL75_UX380_CR0,0,380,562_.jpg" alt="Oppenheimer">
        </div>
        <div class="card-info">
          <div class="rating-row"><i class="bi bi-star-fill"></i> 8.9</div>
          <a href="#" class="movie-item-title">Oppenheimer</a>
          <span class="movie-year">2023 &bull; Drama</span>
          <button class="btn-watchlist-add"><i class="bi bi-plus"></i> Watchlist</button>
        </div>
      </div>

      <!-- Movie 5 -->
      <div class="movie-card-imdb" data-title="Pulp Fiction">
        <div class="poster-wrap">
          <img src="https://m.media-amazon.com/images/M/MV5BYTViYTE3ZGQtNDBlMC00ZTAyLTkyODMtZGRiZDg0MjA2YThkXkEyXkFqcGc@._V1_QL75_UY562_CR3,0,380,562_.jpg" alt="Pulp Fiction">
        </div>
        <div class="card-info">
          <div class="rating-row"><i class="bi bi-star-fill"></i> 8.9</div>
          <a href="#" class="movie-item-title">Pulp Fiction</a>
          <span class="movie-year">1994 &bull; Crime</span>
          <button class="btn-watchlist-add"><i class="bi bi-plus"></i> Watchlist</button>
        </div>
      </div>

      <!-- Movie 6 -->
      <div class="movie-card-imdb" data-title="Fight Club">
        <div class="poster-wrap">
          <img src="https://m.media-amazon.com/images/M/MV5BOTgyOGQ1NDItNGU3Ny00MjU3LTg2YWEtNmEyYjBiMjI1Y2M5XkEyXkFqcGc@._V1_QL75_UX380_CR0,4,380,562_.jpg" alt="Fight Club">
        </div>
        <div class="card-info">
          <div class="rating-row"><i class="bi bi-star-fill"></i> 8.8</div>
          <a href="#" class="movie-item-title">Fight Club</a>
          <span class="movie-year">1999 &bull; Drama</span>
          <button class="btn-watchlist-add"><i class="bi bi-plus"></i> Watchlist</button>
        </div>
      </div>

    </div>

    <script>
        function trackSearch(query) {
            document.write('<img src="/useruploads/resources/images/tracker.gif?searchTerms=' + query + '">');
        }

        var query = (new URLSearchParams(window.location.search)).get('search');
        if (query) {
            // Update UI Heading & Search Bar
            var section = document.getElementById('searchHeaderSection');
            var heading = document.getElementById('searchHeading');
            var subtext = document.getElementById('searchSubtext');
            var input = document.getElementById('searchInput');

            if (section && heading && subtext && input) {
                section.style.display = 'block';
                heading.innerText = 'Search "' + query + '"';
                subtext.innerText = 'Showing titles matching your query';
                input.value = query;
            }

            // Filter movies grid client-side
            var cards = document.querySelectorAll('.movie-card-imdb');
            var matchCount = 0;
            cards.forEach(function(card) {
                var title = card.getAttribute('data-title').toLowerCase();
                if (title.includes(query.toLowerCase())) {
                    card.style.display = 'flex';
                    matchCount++;
                } else {
                    card.style.display = 'none';
                }
            });

            // Vulnerable DOM XSS Sink Call
            trackSearch(query);
        }
    </script>

  </main>

  <!-- IMDb Footer -->
  <footer class="imdb-footer">
    <ul class="footer-links">
      <li><a href="#">Help</a></li>
      <li><a href="#">Site Index</a></li>
      <li><a href="#">IMDbPro</a></li>
      <li><a href="#">Box Office Mojo</a></li>
      <li><a href="#">IMDb Developer</a></li>
      <li><a href="#">Press Room</a></li>
      <li><a href="#">Advertising</a></li>
    </ul>
    <p>&copy; 1990-2026 by IMDb.com, Inc. An Amazon company.</p>
  </footer>

</body>
</html>
