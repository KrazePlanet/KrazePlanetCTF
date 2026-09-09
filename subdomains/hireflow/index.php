<?php $title='Careers'; require 'header.php'; ?>
<main>
<section class="hero-split"><div class="hero-copy"><div class="eyebrow">WE'RE HIRING · 2026</div><h1>Build work people are proud to put their name on.</h1><p>Join a product-minded team building tools that make hiring clearer for candidates and easier for recruiting teams.</p><div class="hero-actions"><a class="btn dark" href="jobs.php">Explore open roles</a><a class="text-link" href="about.php">See how we hire →</a></div></div>
<div class="hero-art"><div class="orbit orbit-a"></div><div class="orbit orbit-b"></div><div class="note n1">Human-first hiring</div><div class="note n2">Remote friendly</div><div class="note n3">Small teams, big ownership</div><div class="center-badge">HF</div></div></section>
<section class="stats"><div><b>12</b><span>countries</span></div><div><b>4.9/5</b><span>candidate experience</span></div><div><b>38</b><span>open projects</span></div><div><b>1:1</b><span>manager partnership</span></div></section>
<section class="section"><div class="section-head"><span class="eyebrow">THE OPPORTUNITIES</span><h2>Roles with room to make them yours.</h2></div>
<div class="role-preview"><?php $jobs=db()->query("SELECT * FROM jobs WHERE status='open' ORDER BY created_at DESC LIMIT 3"); while($j=$jobs->fetch_assoc()): ?>
<article class="role-card"><div><span class="role-dept"><?=e($j['department'])?></span><h3><?=e($j['title'])?></h3><p><?=e($j['description'])?></p></div><div class="role-meta"><span><?=e($j['location'])?></span><span><?=e($j['work_mode'])?></span><span><?=e($j['employment_type'])?></span><a href="job.php?id=<?=$j['id']?>">View role ↗</a></div></article>
<?php endwhile; ?></div><a class="outline-btn" href="jobs.php">View all roles</a></section>
<section class="quote-band"><div class="quote-mark">“</div><p>We designed HireFlow around a simple idea: candidates deserve the same clarity and care that teams expect from their best products.</p></section>
</main><?php require 'footer.php'; ?>