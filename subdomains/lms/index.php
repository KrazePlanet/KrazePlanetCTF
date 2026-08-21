<?php
// EduTrack LMS — Course Resource Controller
$view     = $_GET['view']     ?? 'courses';
$course   = $_GET['course']   ?? null;
$resource = $_GET['resource'] ?? null;   // Vulnerable LFI parameter
$section  = $_GET['section']  ?? 'slides';

// Course catalog
$catalog = [
  'web-security' => [
    'title'      => 'Web Application Security Fundamentals',
    'instructor' => 'Prof. David Reeves',
    'code'       => 'CYB-301',
    'level'      => 'Intermediate',
    'color'      => '#7c3aed',
    'icon'       => 'bi-shield-lock',
    'weeks'      => 3,
    'enrolled'   => 148,
    'slides' => [
      'Week 1 — Introduction to XSS' => 'courses/web-security/slides/week1_intro_xss.txt',
      'Week 2 — SQL Injection'        => 'courses/web-security/slides/week2_sql_injection.txt',
      'Week 3 — File Inclusion'       => 'courses/web-security/slides/week3_file_inclusion.txt',
    ],
    'assignments' => [
      'Assignment 1 — XSS Challenge'  => 'courses/web-security/assignments/assignment1_xss.txt',
    ],
    'notes' => [
      'Lecture Notes Week 1 & 2'      => 'courses/web-security/notes/lecture_notes_week1_2.txt',
    ],
    'instructor_resources' => [
      'Answer Keys (Restricted)'      => 'courses/web-security/instructor/answer_keys.txt',
    ],
  ],
  'networking' => [
    'title'      => 'Network Fundamentals & Protocols',
    'instructor' => 'Prof. Aisha Okonkwo',
    'code'       => 'NET-201',
    'level'      => 'Beginner',
    'color'      => '#0891b2',
    'icon'       => 'bi-diagram-2',
    'weeks'      => 2,
    'enrolled'   => 203,
    'slides' => [
      'Week 1 — TCP/IP Stack'         => 'courses/networking/slides/week1_tcp_ip.txt',
    ],
    'assignments' => [],
    'notes' => [
      'Student Notes Week 1'          => 'courses/networking/notes/student_notes_week1.txt',
    ],
    'instructor_resources' => [],
  ],
];

// Load file content for resource viewer (Vulnerable to LFI)
$file_content = null;
$file_name    = null;
if ($resource) {
  $file_name    = basename($resource);
  $file_content = @file_get_contents($resource);
}

$current = ($course && isset($catalog[$course])) ? $catalog[$course] : null;
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>EduTrack LMS — Learning Management System</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0/dist/css/bootstrap.min.css" rel="stylesheet"
    integrity="sha384-gH2yIJqKdNHPEq0n4Mqa/HGKIhSkIHeL5AyhkYV8i59U5AR6csBvApHHNl/vI1Bx" crossorigin="anonymous">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.9.1/font/bootstrap-icons.css">
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="icon" href="/favicon.ico" />
  <style>
    :root {
      --bg:      #f8f7ff;
      --surface: #ffffff;
      --border:  #e5e2f5;
      --violet:  #7c3aed;
      --violet2: #a78bfa;
      --amber:   #f59e0b;
      --emerald: #059669;
      --cyan:    #0891b2;
      --text:    #1e1b4b;
      --muted:   #6b7280;
      --card-sh: 0 1px 3px rgba(0,0,0,.06), 0 4px 16px rgba(124,58,237,.07);
    }
    * { box-sizing: border-box; }
    body {
      background: var(--bg);
      color: var(--text);
      font-family: 'Plus Jakarta Sans', system-ui, sans-serif;
      min-height: 100vh;
      display: flex;
      flex-direction: column;
    }

    /* ── Header ── */
    .lms-header {
      background: var(--surface);
      border-bottom: 1px solid var(--border);
      padding: .9rem 2rem;
      display: flex;
      align-items: center;
      gap: 1.5rem;
      position: sticky; top: 0; z-index: 100;
      box-shadow: 0 1px 8px rgba(124,58,237,.06);
    }
    .lms-logo {
      font-weight: 800; font-size: 1.3rem; white-space: nowrap;
      background: linear-gradient(135deg, #7c3aed, #a78bfa);
      -webkit-background-clip: text; -webkit-text-fill-color: transparent;
      text-decoration: none;
    }
    .lms-nav { display: flex; gap: 4px; margin-right: auto; }
    .lms-nav-link {
      color: var(--muted); font-weight: 600; font-size: .9rem;
      padding: .45rem 1rem; border-radius: 8px; text-decoration: none;
      transition: all .2s;
    }
    .lms-nav-link:hover, .lms-nav-link.active {
      background: rgba(124,58,237,.1); color: var(--violet);
    }
    .lms-user {
      display: flex; align-items: center; gap: 10px;
      background: rgba(124,58,237,.07);
      border: 1px solid rgba(124,58,237,.2);
      border-radius: 20px; padding: 6px 14px 6px 8px;
      font-size: .85rem; font-weight: 600; color: var(--violet);
    }
    .av {
      width: 28px; height: 28px; border-radius: 50%;
      background: linear-gradient(135deg, #7c3aed, #a78bfa);
      color: #fff; display: flex; align-items: center;
      justify-content: center; font-size: .72rem; font-weight: 800;
    }

    /* ── Breadcrumb ── */
    .breadcrumb-bar {
      background: var(--surface);
      border-bottom: 1px solid var(--border);
      padding: .6rem 2rem;
      font-size: .83rem; color: var(--muted);
    }
    .breadcrumb-bar a { color: var(--violet); text-decoration: none; font-weight: 600; }
    .breadcrumb-bar a:hover { text-decoration: underline; }

    /* ── Main ── */
    .lms-body { flex: 1; padding: 2rem; max-width: 1200px; margin: 0 auto; width: 100%; }

    /* ── Section Heading ── */
    .section-h {
      font-size: 1.5rem; font-weight: 800; color: var(--text);
      margin-bottom: .35rem;
    }
    .section-sub { color: var(--muted); font-size: .95rem; margin-bottom: 1.75rem; }

    /* ── Course Cards ── */
    .course-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(340px,1fr)); gap: 1.4rem; }
    .course-card {
      background: var(--surface);
      border: 1px solid var(--border);
      border-radius: 18px;
      overflow: hidden;
      box-shadow: var(--card-sh);
      transition: transform .2s, box-shadow .2s;
      text-decoration: none;
      color: inherit;
      display: flex; flex-direction: column;
    }
    .course-card:hover {
      transform: translateY(-4px);
      box-shadow: 0 8px 30px rgba(124,58,237,.15);
    }
    .course-banner {
      height: 100px;
      display: flex; align-items: center; justify-content: center;
      font-size: 2.8rem; color: rgba(255,255,255,.9);
      position: relative; overflow: hidden;
    }
    .course-banner::after {
      content: '';
      position: absolute; inset: 0;
      background: linear-gradient(135deg, rgba(0,0,0,.12), rgba(0,0,0,.04));
    }
    .course-body { padding: 1.25rem; flex: 1; }
    .course-code {
      font-size: .72rem; font-weight: 700; text-transform: uppercase;
      letter-spacing: .08em; color: var(--violet); margin-bottom: .4rem;
    }
    .course-title { font-weight: 700; font-size: 1.05rem; color: var(--text); margin-bottom: .4rem; line-height: 1.35; }
    .course-instr { font-size: .84rem; color: var(--muted); margin-bottom: .75rem; }
    .course-meta { display: flex; gap: .7rem; flex-wrap: wrap; }
    .meta-chip {
      display: inline-flex; align-items: center; gap: 4px;
      background: rgba(124,58,237,.07); border: 1px solid rgba(124,58,237,.15);
      color: var(--violet); font-size: .74rem; font-weight: 600;
      padding: 3px 10px; border-radius: 20px;
    }
    .course-footer {
      padding: .85rem 1.25rem;
      border-top: 1px solid var(--border);
      display: flex; align-items: center; justify-content: space-between;
    }
    .enrolled-txt { font-size: .8rem; color: var(--muted); }
    .btn-enter {
      background: var(--violet); color: #fff; border: none;
      font-weight: 700; font-size: .82rem; padding: 7px 16px;
      border-radius: 10px; text-decoration: none; transition: all .2s;
    }
    .btn-enter:hover { background: #6d28d9; color: #fff; }

    /* ── Course Detail: Tab Strip ── */
    .tab-strip {
      display: flex; gap: 4px;
      background: var(--surface);
      border: 1px solid var(--border);
      border-radius: 14px; padding: 5px; margin-bottom: 1.5rem;
      box-shadow: var(--card-sh);
    }
    .tab-btn {
      flex: 1; text-align: center;
      padding: .55rem 1rem; border-radius: 10px;
      font-size: .88rem; font-weight: 600; color: var(--muted);
      text-decoration: none; transition: all .2s;
    }
    .tab-btn:hover { background: rgba(124,58,237,.07); color: var(--violet); }
    .tab-btn.active { background: var(--violet); color: #fff; box-shadow: 0 3px 10px rgba(124,58,237,.3); }

    /* ── Resource List ── */
    .resource-list { display: flex; flex-direction: column; gap: .75rem; }
    .resource-row {
      background: var(--surface);
      border: 1px solid var(--border);
      border-left: 4px solid var(--violet);
      border-radius: 12px;
      padding: 1rem 1.25rem;
      display: flex; align-items: center; gap: 1rem;
      box-shadow: var(--card-sh);
      transition: all .2s;
    }
    .resource-row:hover { border-left-color: var(--amber); box-shadow: 0 4px 16px rgba(124,58,237,.12); }
    .res-icon {
      width: 42px; height: 42px; border-radius: 10px;
      background: rgba(124,58,237,.1);
      display: flex; align-items: center; justify-content: center;
      color: var(--violet); font-size: 1.15rem; flex-shrink: 0;
    }
    .res-name { font-weight: 700; font-size: .95rem; color: var(--text); }
    .res-path { font-size: .78rem; color: var(--muted); font-family: monospace; margin-top: 2px; }
    .res-actions { margin-left: auto; display: flex; gap: .5rem; }
    .btn-preview {
      background: rgba(124,58,237,.1); border: 1px solid rgba(124,58,237,.25);
      color: var(--violet); font-weight: 700; font-size: .8rem;
      padding: 6px 14px; border-radius: 8px; text-decoration: none; transition: all .2s;
    }
    .btn-preview:hover { background: var(--violet); color: #fff; }
    .btn-dl {
      background: rgba(5,150,105,.1); border: 1px solid rgba(5,150,105,.25);
      color: var(--emerald); font-weight: 700; font-size: .8rem;
      padding: 6px 12px; border-radius: 8px; text-decoration: none; transition: all .2s;
    }
    .btn-dl:hover { background: var(--emerald); color: #fff; }

    /* ── Viewer ── */
    .viewer-wrap {
      background: var(--surface);
      border: 1px solid var(--border);
      border-radius: 18px;
      overflow: hidden;
      box-shadow: var(--card-sh);
    }
    .viewer-header {
      background: linear-gradient(135deg, #7c3aed, #6d28d9);
      padding: 1rem 1.4rem;
      display: flex; align-items: center; justify-content: space-between;
    }
    .viewer-filename { color: #fff; font-weight: 700; font-size: .95rem; }
    .viewer-path { color: rgba(255,255,255,.65); font-size: .78rem; font-family: monospace; margin-top: 2px; }
    .viewer-body {
      background: #1a1033;
      font-family: 'JetBrains Mono','Fira Code',monospace;
      font-size: .84rem; color: #c4b5fd;
      padding: 1.4rem 1.6rem;
      white-space: pre-wrap; word-break: break-all;
      max-height: 500px; overflow-y: auto;
      line-height: 1.75;
    }

    /* ── Empty / Error ── */
    .empty-state {
      text-align: center; padding: 3rem;
      color: var(--muted);
    }
    .empty-state i { font-size: 3rem; margin-bottom: 1rem; color: var(--violet2); opacity: .6; }

    /* ── Stats strip ── */
    .stats-strip {
      background: var(--surface);
      border: 1px solid var(--border);
      border-radius: 14px; padding: 1rem 1.5rem;
      display: flex; gap: 2rem; flex-wrap: wrap;
      margin-bottom: 1.5rem; box-shadow: var(--card-sh);
    }
    .stat-item { display: flex; flex-direction: column; }
    .stat-val { font-size: 1.6rem; font-weight: 800; color: var(--violet); }
    .stat-lbl { font-size: .75rem; color: var(--muted); font-weight: 600; text-transform: uppercase; letter-spacing: .05em; }

    footer {
      text-align: center; padding: 1.25rem;
      border-top: 1px solid var(--border);
      color: var(--muted); font-size: .82rem;
      background: var(--surface);
    }
  </style>
</head>
<body>

<!-- LMS Header -->
<header class="lms-header">
  <a class="lms-logo" href="?view=courses"><i class="bi bi-mortarboard-fill me-2"></i>EduTrack LMS</a>
  <nav class="lms-nav">
    <a class="lms-nav-link <?php echo !$course?'active':''; ?>" href="?view=courses">
      <i class="bi bi-grid me-1"></i>My Courses
    </a>
    <a class="lms-nav-link" href="?view=courses">
      <i class="bi bi-calendar3 me-1"></i>Schedule
    </a>
    <a class="lms-nav-link" href="?view=courses">
      <i class="bi bi-bell me-1"></i>Announcements
    </a>
  </nav>
  <div class="lms-user">
    <div class="av">ST</div>
    student@edutrack.local
  </div>
</header>

<!-- Breadcrumb -->
<div class="breadcrumb-bar">
  <?php if ($resource && $current): ?>
    <a href="?view=courses">Courses</a> /
    <a href="?view=course&course=<?php echo urlencode($course); ?>"><?php echo htmlspecialchars($current['code']); ?></a> /
    <span><?php echo htmlspecialchars($file_name); ?></span>
  <?php elseif ($current): ?>
    <a href="?view=courses">Courses</a> /
    <span><?php echo htmlspecialchars($current['code']); ?> — <?php echo htmlspecialchars($current['title']); ?></span>
  <?php else: ?>
    <span>Course Catalog</span>
  <?php endif; ?>
</div>

<!-- Body -->
<div class="lms-body">

  <?php if ($resource && $current): ?>
  <!-- ── RESOURCE VIEWER ── -->
  <div class="d-flex align-items-center justify-content-between mb-4">
    <div>
      <h2 class="section-h"><?php echo htmlspecialchars($file_name); ?></h2>
      <p class="section-sub"><?php echo htmlspecialchars($current['title']); ?> &nbsp;·&nbsp; <?php echo htmlspecialchars($current['instructor']); ?></p>
    </div>
    <div class="d-flex gap-2">
      <a href="<?php echo htmlspecialchars($resource); ?>" download class="btn-dl"><i class="bi bi-download me-1"></i>Download</a>
      <a href="?view=course&course=<?php echo urlencode($course); ?>&section=<?php echo urlencode($section); ?>" class="btn-preview"><i class="bi bi-arrow-left me-1"></i>Back</a>
    </div>
  </div>

  <div class="viewer-wrap">
    <div class="viewer-header">
      <div>
        <div class="viewer-filename"><i class="bi bi-file-earmark-text me-2"></i><?php echo htmlspecialchars($file_name); ?></div>
        <div class="viewer-path"><?php echo htmlspecialchars($resource); ?></div>
      </div>
    </div>
    <?php if ($file_content !== null && $file_content !== false): ?>
      <div class="viewer-body"><?php echo htmlspecialchars($file_content); ?></div>
    <?php else: ?>
      <div class="p-4 text-danger font-monospace small">Error: Resource not found — <?php echo htmlspecialchars($resource); ?></div>
    <?php endif; ?>
  </div>

  <?php elseif ($current): ?>
  <!-- ── COURSE DETAIL ── -->
  <h2 class="section-h"><?php echo htmlspecialchars($current['title']); ?></h2>
  <p class="section-sub">
    <i class="bi bi-person-fill me-1"></i><?php echo htmlspecialchars($current['instructor']); ?>
    &nbsp;·&nbsp;
    <i class="bi bi-people-fill me-1"></i><?php echo $current['enrolled']; ?> enrolled
    &nbsp;·&nbsp;
    <span class="fw-semibold" style="color:var(--violet)"><?php echo $current['level']; ?></span>
  </p>

  <div class="stats-strip">
    <div class="stat-item"><div class="stat-val"><?php echo count($current['slides']); ?></div><div class="stat-lbl">Slide Decks</div></div>
    <div class="stat-item"><div class="stat-val"><?php echo count($current['assignments']); ?></div><div class="stat-lbl">Assignments</div></div>
    <div class="stat-item"><div class="stat-val"><?php echo count($current['notes']); ?></div><div class="stat-lbl">Note Files</div></div>
    <div class="stat-item"><div class="stat-val"><?php echo $current['weeks']; ?></div><div class="stat-lbl">Weeks</div></div>
  </div>

  <!-- Tab strip -->
  <div class="tab-strip">
    <a class="tab-btn <?php echo $section==='slides'?'active':''; ?>" href="?view=course&course=<?php echo urlencode($course); ?>&section=slides">
      <i class="bi bi-easel2 me-1"></i>Lecture Slides
    </a>
    <a class="tab-btn <?php echo $section==='assignments'?'active':''; ?>" href="?view=course&course=<?php echo urlencode($course); ?>&section=assignments">
      <i class="bi bi-pencil-square me-1"></i>Assignments
    </a>
    <a class="tab-btn <?php echo $section==='notes'?'active':''; ?>" href="?view=course&course=<?php echo urlencode($course); ?>&section=notes">
      <i class="bi bi-journal-text me-1"></i>Download Notes
    </a>
    <a class="tab-btn <?php echo $section==='instructor'?'active':''; ?>" href="?view=course&course=<?php echo urlencode($course); ?>&section=instructor">
      <i class="bi bi-person-workspace me-1"></i>Instructor Resources
    </a>
  </div>

  <!-- Resource list for selected section -->
  <?php
  $items = $current[$section === 'instructor' ? 'instructor_resources' : $section] ?? [];
  ?>
  <?php if (!empty($items)): ?>
  <div class="resource-list">
    <?php foreach ($items as $label => $path): ?>
    <div class="resource-row">
      <div class="res-icon">
        <i class="bi <?php echo $section==='slides'?'bi-easel2':($section==='assignments'?'bi-pencil-square':($section==='instructor'?'bi-lock':'bi-journal-text')); ?>"></i>
      </div>
      <div>
        <div class="res-name"><?php echo htmlspecialchars($label); ?></div>
        <div class="res-path"><?php echo htmlspecialchars($path); ?></div>
      </div>
      <div class="res-actions">
        <a href="?view=course&course=<?php echo urlencode($course); ?>&section=<?php echo urlencode($section); ?>&resource=<?php echo urlencode($path); ?>" class="btn-preview">
          <i class="bi bi-eye me-1"></i>Preview
        </a>
        <a href="<?php echo htmlspecialchars($path); ?>" download class="btn-dl">
          <i class="bi bi-download"></i>
        </a>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php else: ?>
  <div class="empty-state">
    <i class="bi bi-folder2-open d-block"></i>
    <p class="fw-semibold">No resources available in this section yet.</p>
  </div>
  <?php endif; ?>

  <?php else: ?>
  <!-- ── COURSE CATALOG ── -->
  <h2 class="section-h">My Enrolled Courses</h2>
  <p class="section-sub">Browse lecture slides, assignments, and downloadable notes.</p>

  <div class="course-grid">
    <?php foreach ($catalog as $slug => $c): ?>
    <a class="course-card" href="?view=course&course=<?php echo urlencode($slug); ?>">
      <div class="course-banner" style="background:<?php echo $c['color']; ?>">
        <i class="bi <?php echo $c['icon']; ?>"></i>
      </div>
      <div class="course-body">
        <div class="course-code"><?php echo htmlspecialchars($c['code']); ?></div>
        <div class="course-title"><?php echo htmlspecialchars($c['title']); ?></div>
        <div class="course-instr"><i class="bi bi-person me-1"></i><?php echo htmlspecialchars($c['instructor']); ?></div>
        <div class="course-meta">
          <span class="meta-chip"><i class="bi bi-bar-chart me-1"></i><?php echo $c['level']; ?></span>
          <span class="meta-chip"><i class="bi bi-calendar me-1"></i><?php echo $c['weeks']; ?> Weeks</span>
          <span class="meta-chip"><i class="bi bi-file-earmark me-1"></i><?php echo count($c['slides']); ?> Slides</span>
        </div>
      </div>
      <div class="course-footer">
        <span class="enrolled-txt"><i class="bi bi-people me-1"></i><?php echo $c['enrolled']; ?> students</span>
        <span class="btn-enter">Open Course →</span>
      </div>
    </a>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

</div>

<footer>&copy; 2026 EduTrack Learning Management System</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0/dist/js/bootstrap.bundle.min.js"
  integrity="sha384-A3rJD856KowSb7dwlZdYKdNhnE+E03B9aA7D0A876B5A1"
  crossorigin="anonymous"></script>
</body>
</html>
