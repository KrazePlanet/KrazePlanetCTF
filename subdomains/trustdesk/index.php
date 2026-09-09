<?php $title='Safety & Trust Center'; require 'partials_header.php'; ?>
<main>
<section class="hero">
  <div class="hero-copy">
    <div class="eyebrow">TRUST &amp; SAFETY</div>
    <h1>Help us keep the community <em>safe.</em></h1>
    <p>Report harmful, deceptive, abusive, or suspicious activity. Our review team will assess your report and take appropriate action.</p>
    <div class="hero-actions"><a class="button dark" href="report.php">Make a report <span>↗</span></a><a class="text-link" href="track.php">Track an existing case →</a></div>
  </div>
  <div class="hero-art">
    <div class="orbit o1"></div><div class="orbit o2"></div><div class="core">✓</div>
    <div class="tag t1">Community<br>protected</div><div class="tag t2">24/7 review<br>queue</div>
  </div>
</section>

<section class="strip">
  <div><b>01</b><span>Report</span><small>Tell us what happened</small></div>
  <div><b>02</b><span>Review</span><small>Our team investigates</small></div>
  <div><b>03</b><span>Action</span><small>We resolve the case</small></div>
</section>

<section class="categories">
  <div class="section-head"><div><span class="eyebrow">WHAT CAN WE HELP WITH?</span><h2>Choose a report type</h2></div><a href="safety.php">Read safety guidelines →</a></div>
  <div class="card-grid">
    <a class="category-card" href="report.php?type=content"><span>◈</span><b>Report content</b><small>Harmful, illegal or misleading content</small></a>
    <a class="category-card" href="report.php?type=user"><span>◎</span><b>Report a user</b><small>Harassment, impersonation or abuse</small></a>
    <a class="category-card" href="report.php?type=spam"><span>⌁</span><b>Report spam</b><small>Scams, unwanted or deceptive activity</small></a>
    <a class="category-card" href="report.php?type=review"><span>◇</span><b>Flag a review</b><small>Manipulated or inappropriate reviews</small></a>
    <a class="category-card" href="report.php?type=account"><span>◌</span><b>Account appeal</b><small>Request a review of an account action</small></a>
    <a class="category-card" href="report.php?type=other"><span>＋</span><b>Something else</b><small>Tell us about another safety concern</small></a>
  </div>
</section>

<section class="assurance">
  <div><span class="eyebrow">YOUR REPORT MATTERS</span><h2>Clear process. Human review. Respect for everyone.</h2></div>
  <div class="assurance-items"><p><b>Private by default</b><br>We only use information needed to review your case.</p><p><b>Case tracking</b><br>Get a case number and check progress whenever you need.</p><p><b>Evidence welcome</b><br>Include useful links, usernames and context to help our reviewers.</p></div>
</section>
</main>
<?php require 'partials_footer.php'; ?>