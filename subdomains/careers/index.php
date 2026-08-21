<?php
// Lab 65 — DOM XSS via URL Tracking Parameter on HackerOne Careers Page
// Platform: HackerOne (hackerone.com/careers) | HackerOne Report #474656
// Vulnerability: "Lever" tracking prefix read from window.location.href,
//   leverParam is passed through decodeURIComponent() and injected unsanitized
//   into jQuery .append() as part of an href="..." string — breaks out of attribute.
// Exploit: index.php?lever-%22%3E%3Cimg+src%3Dx+onerror%3Dalert(document.domain)%3E
//   OR with literal chars: index.php?lever-"><img+src%3Dx+onerror%3Dalert(document.domain)>
//
// Discovery: click any nav link, CTA button ("Open roles" / "See open positions"), or
//   team filter — all of them add ?lever-<value> to the URL so students find the param.
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Careers — HackerOne</title>
<style>
*{box-sizing:border-box;margin:0;padding:0;}
body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;background:#fff;color:#1b2030;}

/* ── Navbar ──────────────────────────────────────────────────────────────── */
.nav{background:#1b2030;position:sticky;top:0;z-index:100;border-bottom:1px solid #2d3748;}
.nav-inner{max-width:1200px;margin:0 auto;padding:0 24px;height:60px;display:flex;align-items:center;gap:32px;}
.nav-logo{display:flex;align-items:center;gap:8px;text-decoration:none;}
.nav-logo-mark{width:32px;height:32px;background:#25a244;border-radius:6px;display:flex;align-items:center;justify-content:center;color:#fff;font-weight:900;font-size:.9rem;flex-shrink:0;}
.nav-logo-text{color:#fff;font-weight:700;font-size:1.05rem;letter-spacing:-.01em;}
.nav-links{display:flex;gap:4px;margin-left:16px;}
.nav-link{color:rgba(255,255,255,.7);font-size:.82rem;font-weight:500;padding:6px 10px;border-radius:5px;cursor:pointer;transition:color .15s,background .15s;text-decoration:none;}
.nav-link:hover{color:#fff;background:rgba(255,255,255,.08);}
.nav-link.active{color:#25a244;}
.nav-right{margin-left:auto;display:flex;align-items:center;gap:10px;}
.nav-btn{background:#25a244;color:#fff;border:none;border-radius:6px;padding:7px 16px;font-size:.8rem;font-weight:700;cursor:pointer;text-decoration:none;transition:background .15s;}
.nav-btn:hover{background:#1e8c38;}

/* ── Hero ────────────────────────────────────────────────────────────────── */
.hero{background:linear-gradient(135deg,#0d1117 0%,#1b2030 50%,#0d2318 100%);padding:80px 24px 72px;text-align:center;position:relative;overflow:hidden;}
.hero::before{content:'';position:absolute;inset:0;background:radial-gradient(ellipse 80% 60% at 50% 0%,rgba(37,162,68,.18),transparent);}
.hero-inner{max-width:720px;margin:0 auto;position:relative;}
.hero-eyebrow{display:inline-flex;align-items:center;gap:6px;background:rgba(37,162,68,.15);border:1px solid rgba(37,162,68,.3);border-radius:20px;padding:4px 12px;font-size:.72rem;font-weight:700;color:#25a244;letter-spacing:.06em;text-transform:uppercase;margin-bottom:20px;}
.hero h1{font-size:2.8rem;font-weight:900;color:#fff;line-height:1.15;margin-bottom:16px;letter-spacing:-.02em;}
.hero h1 span{color:#25a244;}
.hero p{font-size:1.05rem;color:rgba(255,255,255,.65);line-height:1.7;margin-bottom:32px;}
.hero-cta{display:inline-flex;align-items:center;gap:8px;background:#25a244;color:#fff;border-radius:8px;padding:13px 26px;font-size:.9rem;font-weight:700;text-decoration:none;transition:background .15s;}
.hero-cta:hover{background:#1e8c38;}
.hero-stats{display:flex;justify-content:center;gap:40px;margin-top:48px;flex-wrap:wrap;}
.hero-stat-num{font-size:1.5rem;font-weight:900;color:#25a244;}
.hero-stat-label{font-size:.7rem;color:rgba(255,255,255,.5);text-transform:uppercase;letter-spacing:.05em;margin-top:2px;}

/* ── Values strip ────────────────────────────────────────────────────────── */
.values{background:#f8fafc;border-top:1px solid #e5e7eb;border-bottom:1px solid #e5e7eb;padding:40px 24px;}
.values-inner{max-width:1100px;margin:0 auto;display:grid;grid-template-columns:repeat(3,1fr);gap:32px;}
.value-card{display:flex;gap:14px;align-items:flex-start;}
.value-icon{width:36px;height:36px;background:#f0fdf4;border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:1.1rem;flex-shrink:0;}
.value-title{font-size:.85rem;font-weight:700;color:#1b2030;margin-bottom:4px;}
.value-desc{font-size:.77rem;color:#6b7280;line-height:1.6;}
@media(max-width:700px){.values-inner{grid-template-columns:1fr;}.hero h1{font-size:2rem;}}

/* ── Jobs section ────────────────────────────────────────────────────────── */
.jobs-section{max-width:1100px;margin:0 auto;padding:56px 24px;}
.jobs-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:28px;flex-wrap:wrap;gap:12px;}
.jobs-header h2{font-size:1.4rem;font-weight:800;color:#1b2030;}
.jobs-count{font-size:.8rem;color:#9ca3af;background:#f3f4f6;border-radius:20px;padding:3px 10px;}

/* Filters */
.filters{display:flex;gap:8px;flex-wrap:wrap;margin-bottom:32px;}
.filter-btn{border:1px solid #e5e7eb;background:#fff;border-radius:20px;padding:6px 14px;font-size:.78rem;font-weight:600;color:#6b7280;cursor:pointer;transition:all .15s;font-family:inherit;}
.filter-btn:hover{border-color:#25a244;color:#25a244;}
.filter-btn.active{background:#25a244;border-color:#25a244;color:#fff;}

/* Job list */
#jobs-list{}
.job-item{border:1px solid #e5e7eb;border-radius:10px;padding:20px 24px;margin-bottom:12px;display:flex;align-items:center;justify-content:space-between;gap:16px;transition:border-color .15s,box-shadow .15s;background:#fff;}
.job-item:hover{border-color:#25a244;box-shadow:0 2px 12px rgba(37,162,68,.1);}
.job-item.hidden{display:none;}
.job-info{flex:1;}
.job-title{font-size:.95rem;font-weight:700;color:#1b2030;text-decoration:none;display:block;margin-bottom:6px;}
.job-title:hover{color:#25a244;}
.job-tags{display:flex;gap:8px;flex-wrap:wrap;}
.job-tag{font-size:.7rem;color:#6b7280;background:#f3f4f6;border-radius:12px;padding:2px 8px;}
.job-tag.team{background:#f0fdf4;color:#15803d;font-weight:600;}
.job-actions{display:flex;gap:8px;align-items:center;flex-shrink:0;}
.job-btn{background:#25a244;color:#fff;border:none;border-radius:6px;padding:8px 16px;font-size:.78rem;font-weight:700;text-decoration:none;cursor:pointer;transition:background .15s;white-space:nowrap;}
.job-btn:hover{background:#1e8c38;}
.jobs-empty{text-align:center;padding:48px;color:#9ca3af;font-size:.84rem;}

/* ── Perks section ───────────────────────────────────────────────────────── */
.perks{background:#0d1117;padding:56px 24px;}
.perks-inner{max-width:1100px;margin:0 auto;}
.perks h2{font-size:1.4rem;font-weight:800;color:#fff;margin-bottom:8px;}
.perks-sub{font-size:.85rem;color:rgba(255,255,255,.55);margin-bottom:32px;}
.perks-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:16px;}
.perk-card{background:rgba(255,255,255,.05);border:1px solid rgba(255,255,255,.08);border-radius:10px;padding:20px;text-align:center;}
.perk-icon{font-size:1.6rem;margin-bottom:10px;}
.perk-title{font-size:.82rem;font-weight:700;color:#fff;margin-bottom:4px;}
.perk-desc{font-size:.72rem;color:rgba(255,255,255,.45);line-height:1.5;}
@media(max-width:700px){.perks-grid{grid-template-columns:repeat(2,1fr);}}

/* ── Footer ──────────────────────────────────────────────────────────────── */
.footer{background:#1b2030;border-top:1px solid #2d3748;padding:28px 24px;}
.footer-inner{max-width:1100px;margin:0 auto;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;}
.footer-logo{display:flex;align-items:center;gap:8px;}
.footer-logo-mark{width:24px;height:24px;background:#25a244;border-radius:4px;display:flex;align-items:center;justify-content:center;color:#fff;font-weight:900;font-size:.65rem;}
.footer-logo-text{color:rgba(255,255,255,.6);font-size:.78rem;font-weight:600;}
.footer-copy{font-size:.72rem;color:rgba(255,255,255,.35);}
.footer-links{display:flex;gap:16px;}
.footer-links a{font-size:.72rem;color:rgba(255,255,255,.4);text-decoration:none;}
.footer-links a:hover{color:rgba(255,255,255,.7);}
</style>
</head>
<body>

<!-- Navbar -->
<nav class="nav">
  <div class="nav-inner">
    <a href="#" class="nav-logo">
      <div class="nav-logo-mark">h1</div>
      <span class="nav-logo-text">HackerOne</span>
    </a>
    <div class="nav-links">
      <a href="?lever-hackers" class="nav-link">Hackers</a>
      <a href="?lever-programs" class="nav-link">Programs</a>
      <a href="?lever-resources" class="nav-link">Resources</a>
      <a href="?lever-company" class="nav-link">Company</a>
      <a href="#" class="nav-link active">Careers</a>
    </div>
    <div class="nav-right">
      <a href="?lever-apply#open-roles" class="nav-btn">Open roles</a>
    </div>
  </div>
</nav>

<!-- Hero -->
<section class="hero">
  <div class="hero-inner">
    <div class="hero-eyebrow">🌍 We're hiring worldwide</div>
    <h1>Shape the future of<br><span>security</span></h1>
    <p>Join a team of hackers, builders, and dreamers on a mission to make the internet a safer place. We're growing fast and looking for the best.</p>
    <a href="?lever-roles#open-roles" class="hero-cta">
      See open positions
      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M19 9l-7 7-7-7"/></svg>
    </a>
    <div class="hero-stats">
      <div><div class="hero-stat-num">300K+</div><div class="hero-stat-label">Hackers</div></div>
      <div><div class="hero-stat-num">$230M+</div><div class="hero-stat-label">Bounties paid</div></div>
      <div><div class="hero-stat-num">1,400+</div><div class="hero-stat-label">Programs</div></div>
      <div><div class="hero-stat-num">500+</div><div class="hero-stat-label">Team members</div></div>
    </div>
  </div>
</section>

<!-- Values -->
<section class="values">
  <div class="values-inner">
    <div class="value-card">
      <div class="value-icon">🛡️</div>
      <div>
        <div class="value-title">Security First</div>
        <div class="value-desc">We believe security is a human problem. Every decision we make starts with protecting people and their data.</div>
      </div>
    </div>
    <div class="value-card">
      <div class="value-icon">🤝</div>
      <div>
        <div class="value-title">Hacker-Centric</div>
        <div class="value-desc">Hackers are our community. We build with them, for them, and advocate for their rights and recognition worldwide.</div>
      </div>
    </div>
    <div class="value-card">
      <div class="value-icon">🚀</div>
      <div>
        <div class="value-title">Move Fast</div>
        <div class="value-desc">We ship constantly, learn rapidly, and trust our teams to make the right calls without bureaucratic friction.</div>
      </div>
    </div>
  </div>
</section>

<!-- Jobs -->
<section class="jobs-section" id="open-roles">
  <div class="jobs-header">
    <h2>Open Positions</h2>
    <span class="jobs-count" id="jobs-count">6 open roles</span>
  </div>

  <div class="filters">
    <button class="filter-btn active" data-team="all">All Teams</button>
    <button class="filter-btn" data-team="engineering">Engineering</button>
    <button class="filter-btn" data-team="security">Security</button>
    <button class="filter-btn" data-team="sales">Sales</button>
    <button class="filter-btn" data-team="marketing">Marketing</button>
    <button class="filter-btn" data-team="design">Design</button>
  </div>

  <div id="jobs-list">
    <!-- Job cards injected by JavaScript below -->
  </div>
</section>

<!-- Perks -->
<section class="perks">
  <div class="perks-inner">
    <h2>Why HackerOne?</h2>
    <p class="perks-sub">We take care of our team so they can focus on the mission.</p>
    <div class="perks-grid">
      <div class="perk-card"><div class="perk-icon">💰</div><div class="perk-title">Competitive Pay</div><div class="perk-desc">Market-leading salaries + equity for every employee</div></div>
      <div class="perk-card"><div class="perk-icon">🌎</div><div class="perk-title">Remote First</div><div class="perk-desc">Work from anywhere with flexible hours</div></div>
      <div class="perk-card"><div class="perk-icon">🏥</div><div class="perk-title">Full Benefits</div><div class="perk-desc">Medical, dental, vision — 100% covered</div></div>
      <div class="perk-card"><div class="perk-icon">📚</div><div class="perk-title">Learning Budget</div><div class="perk-desc">$2,500/year for conferences, courses, and certifications</div></div>
    </div>
  </div>
</section>

<!-- Footer -->
<footer class="footer">
  <div class="footer-inner">
    <div class="footer-logo">
      <div class="footer-logo-mark">h1</div>
      <span class="footer-logo-text">HackerOne</span>
    </div>
    <div class="footer-links">
      <a href="#">Privacy Policy</a>
      <a href="#">Terms of Service</a>
      <a href="#">Security</a>
      <a href="https://hackerone.com/reports/474656" target="_blank">Report #474656</a>
    </div>
    <span class="footer-copy">© 2019 HackerOne Inc. All rights reserved.</span>
  </div>
</footer>

<!-- jQuery (required by the vulnerable Masonry tracking script, as in the real report) -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<script>
// ============================================================
//  Simulated Lever.co job data (normally fetched from API)
//  https://api.lever.co/v0/postings/hackerone
// ============================================================
var jobs = [
    {
        id: 'a1b2c3d4-senior-security-engineer',
        title: 'Senior Security Engineer',
        team: 'Security',
        teamClass: 'security',
        location: 'Remote',
        commitment: 'Full-time',
        description: 'Lead security architecture reviews, threat modeling, and vulnerability research across HackerOne\'s platform.'
    },
    {
        id: 'b2c3d4e5-backend-engineer-ruby',
        title: 'Backend Engineer (Ruby on Rails)',
        team: 'Engineering',
        teamClass: 'engineering',
        location: 'San Francisco, CA',
        commitment: 'Full-time',
        description: 'Build and scale the core HackerOne platform serving 300,000+ security researchers and 1,400+ programs.'
    },
    {
        id: 'c3d4e5f6-product-security-engineer',
        title: 'Product Security Engineer',
        team: 'Security',
        teamClass: 'security',
        location: 'Remote',
        commitment: 'Full-time',
        description: 'Partner with engineering teams to bake security into the SDLC — code reviews, pen testing, and secure design patterns.'
    },
    {
        id: 'd4e5f6a7-sales-development-rep',
        title: 'Sales Development Representative',
        team: 'Sales',
        teamClass: 'sales',
        location: 'Austin, TX',
        commitment: 'Full-time',
        description: 'Drive outbound prospecting and pipeline generation for HackerOne\'s enterprise security platform.'
    },
    {
        id: 'e5f6a7b8-ux-designer',
        title: 'UX Designer',
        team: 'Design',
        teamClass: 'design',
        location: 'San Francisco, CA',
        commitment: 'Full-time',
        description: 'Design intuitive experiences for hackers and security teams that make the HackerOne platform a joy to use.'
    },
    {
        id: 'f6a7b8c9-marketing-manager',
        title: 'Marketing Manager — Developer Relations',
        team: 'Marketing',
        teamClass: 'marketing',
        location: 'Remote',
        commitment: 'Full-time',
        description: 'Grow HackerOne\'s hacker community through events, content, and partnerships with the security research ecosystem.'
    }
];

// ============================================================
//  Vulnerable tracking code — simulates the Masonry JS file
//  described in HackerOne Report #474656
//
//  Original code (condensed from the report):
//    var pageUrl = window.location.href;
//    var trackingPrefix = '?lever-';
//    if (pageUrl.indexOf(trackingPrefix) >= 0) {
//        leverParam = '?lever-' + pageUrlSplit[1];
//    }
//    var link = posting.hostedUrl + leverParam;
//    jQuery('#jobs-container .jobs-list').append('<a href="'+link+'">' + ... + '</a>');
//
//  The leverParam is taken from the URL and embedded in the
//  HTML string WITHOUT sanitization. decodeURIComponent() is
//  called so that URL-encoded payloads also execute.
// ============================================================
var pageUrl    = window.location.href;
var trackingPrefix = '?lever-';
var leverParam = '';

if (pageUrl.indexOf(trackingPrefix) >= 0) {
    var pageUrlSplit = pageUrl.split(trackingPrefix);
    leverParam = '?lever-' + decodeURIComponent((pageUrlSplit[1] || '').replace(/\+/g, ' '));
}

// Render job listings — leverParam injected unsanitized into href attribute
jobs.forEach(function(job) {
    var hostedUrl = '/' + job.id;
    var link = hostedUrl + leverParam;  // ⚠ VULNERABLE: leverParam not sanitized

    jQuery('#jobs-list').append(
        '<div class="job-item" data-team="' + job.teamClass + '">' +
            '<div class="job-info">' +
                '<a class="job-title" href="' + link + '">' + job.title + '</a>' +
                '<div class="job-tags">' +
                    '<span class="job-tag team">' + job.team + '</span>' +
                    '<span class="job-tag">' + job.location + '</span>' +
                    '<span class="job-tag">' + job.commitment + '</span>' +
                '</div>' +
            '</div>' +
            '<div class="job-actions">' +
                '<a class="job-btn" href="' + link + '">Apply →</a>' +
            '</div>' +
        '</div>'
    );
});

// Update count
$('#jobs-count').text(jobs.length + ' open roles');

// ── Team filter — also writes lever- tracking param to URL ────────────────
//  Students discover the ?lever- prefix in the address bar when filtering.
$('.filter-btn').on('click', function() {
    var team = $(this).data('team');
    $('.filter-btn').removeClass('active');
    $(this).addClass('active');

    // Expose lever- tracking param in the URL so students discover it
    if (team === 'all') {
        history.pushState(null, '', window.location.pathname);
    } else {
        history.pushState(null, '', '?lever-' + team);
    }

    var visible = 0;
    $('.job-item').each(function() {
        if (team === 'all' || $(this).data('team') === team) {
            $(this).removeClass('hidden');
            visible++;
        } else {
            $(this).addClass('hidden');
        }
    });
    $('#jobs-count').text(visible + ' open role' + (visible !== 1 ? 's' : ''));
});
</script>

</body>
</html>
