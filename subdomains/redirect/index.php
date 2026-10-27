<?php
// DevPulse — daily developer news

// Outbound links are routed through the site so clicks can be counted before the
// reader is sent on to the original article. The destination comes straight from
// the ?url= parameter.
$url = $_GET['url'] ?? '';
if ($url !== '') {
    header('Location: ' . $url);
    exit;
}

$stories = [
    ['title' => 'PostgreSQL 17 ships with faster vacuum and incremental backups',
     'source' => 'postgresql.org', 'url' => 'https://www.postgresql.org/about/news/',
     'snippet' => 'The latest release focuses on operational wins: a rewritten vacuum, incremental base backups, and big improvements to high-concurrency write workloads.',
     'tag' => 'Databases', 'time' => '2h ago'],
    ['title' => 'A practical guide to HTTP caching headers in 2026',
     'source' => 'developer.mozilla.org', 'url' => 'https://developer.mozilla.org/en-US/docs/Web/HTTP/Caching',
     'snippet' => 'Cache-Control, ETag, and the stale-while-revalidate directive explained with real request/response traces you can copy.',
     'tag' => 'Web', 'time' => '5h ago'],
    ['title' => 'Rust 1.84 stabilises async closures',
     'source' => 'blog.rust-lang.org', 'url' => 'https://blog.rust-lang.org/',
     'snippet' => 'Async closures land on stable, closing a long-standing ergonomics gap for people writing async-heavy libraries and services.',
     'tag' => 'Languages', 'time' => '8h ago'],
    ['title' => 'How we cut our Kubernetes bill by 40% without losing capacity',
     'source' => 'engineering.example.com', 'url' => 'https://kubernetes.io/blog/',
     'snippet' => 'Right-sizing requests, bin-packing with topology spread, and moving batch jobs to spot nodes — the numbers behind each change.',
     'tag' => 'Infra', 'time' => '11h ago'],
    ['title' => 'The case for boring technology, revisited',
     'source' => 'news.ycombinator.com', 'url' => 'https://news.ycombinator.com/',
     'snippet' => 'Choosing well-understood tools is still the highest-leverage decision most teams can make. A look at where the idea holds and where it breaks.',
     'tag' => 'Opinion', 'time' => '1d ago'],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>DevPulse — Daily developer news</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>
:root{--accent:#e11d48;--ink:#18181b;--muted:#71717a;--line:#e4e4e7;--bg:#fafafa;--white:#fff;}
*{margin:0;padding:0;box-sizing:border-box;}
body{font-family:'Inter',-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;background:var(--bg);color:var(--ink);line-height:1.6;}
a{text-decoration:none;color:inherit;}
.top{background:var(--white);border-bottom:1px solid var(--line);position:sticky;top:0;z-index:10;}
.top-in{max-width:760px;margin:0 auto;padding:16px 20px;display:flex;align-items:center;gap:20px;}
.logo{font-size:20px;font-weight:800;letter-spacing:-.4px;display:flex;align-items:center;gap:8px;}
.logo .dot{width:11px;height:11px;border-radius:50%;background:var(--accent);display:inline-block;}
.top-nav{display:flex;gap:18px;margin-left:auto;font-size:14px;font-weight:500;color:var(--muted);}
.top-nav a:hover{color:var(--ink);}
.wrap{max-width:760px;margin:0 auto;padding:28px 20px 60px;}
.lead{font-size:13px;color:var(--muted);text-transform:uppercase;letter-spacing:.6px;font-weight:600;margin-bottom:18px;}
.story{background:var(--white);border:1px solid var(--line);border-radius:12px;padding:20px 22px;margin-bottom:16px;transition:box-shadow .15s;}
.story:hover{box-shadow:0 6px 24px rgba(0,0,0,.06);}
.story-meta{display:flex;align-items:center;gap:10px;font-size:12.5px;color:var(--muted);margin-bottom:9px;}
.tag{background:#fef2f2;color:var(--accent);font-weight:600;font-size:11.5px;padding:2px 9px;border-radius:20px;}
.story h2{font-size:18px;font-weight:700;line-height:1.35;margin-bottom:8px;letter-spacing:-.2px;}
.story h2 a:hover{color:var(--accent);}
.story p{font-size:14.5px;color:#3f3f46;margin-bottom:14px;}
.read{display:inline-flex;align-items:center;gap:6px;font-size:13.5px;font-weight:600;color:var(--accent);}
.read:hover{gap:9px;}
.foot{max-width:760px;margin:0 auto;padding:24px 20px 40px;font-size:13px;color:var(--muted);text-align:center;border-top:1px solid var(--line);}
</style>
</head>
<body>
<header class="top">
  <div class="top-in">
    <span class="logo"><span class="dot"></span>DevPulse</span>
    <nav class="top-nav"><a href="/">Top</a><a href="/">New</a><a href="/">Jobs</a><a href="/">About</a></nav>
  </div>
</header>

<main class="wrap">
  <div class="lead">Today's top stories</div>
  <?php foreach ($stories as $s): ?>
  <article class="story">
    <div class="story-meta"><span class="tag"><?php echo htmlspecialchars($s['tag']); ?></span><span><?php echo htmlspecialchars($s['source']); ?></span><span>·</span><span><?php echo htmlspecialchars($s['time']); ?></span></div>
    <h2><a href="/?url=<?php echo urlencode($s['url']); ?>"><?php echo htmlspecialchars($s['title']); ?></a></h2>
    <p><?php echo htmlspecialchars($s['snippet']); ?></p>
    <a class="read" href="/?url=<?php echo urlencode($s['url']); ?>">Read at <?php echo htmlspecialchars($s['source']); ?> &rarr;</a>
  </article>
  <?php endforeach; ?>
</main>

<footer class="foot">&copy; 2026 DevPulse — curated developer news, updated daily.</footer>
</body>
</html>
