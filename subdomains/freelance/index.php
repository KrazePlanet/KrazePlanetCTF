<?php
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/config/config.php';
?>
<!doctype html>
<html lang="en">
<head>
  <?php include __DIR__ . '/includes/head.php'; ?>
  <title><?= e(SITE_NAME) ?> — <?= e(SITE_TAGLINE) ?></title>
</head>
<body>
<?php include __DIR__ . '/includes/navbar.php'; ?>

<main>
<section class="hero section" id="home">
  <div class="container hero-grid">
    <div class="hero-copy reveal">
      <span class="availability"><span></span> <?= e(SITE_AVAILABILITY) ?></span>
      <h1>Building digital products that <em>help businesses grow.</em></h1>
      <p class="hero-text">I build modern websites, web applications, APIs and custom software for startups, businesses and entrepreneurs.</p>
      <div class="hero-actions">
        <a class="btn btn-primary" href="#contact">Start a Project <span>→</span></a>
        <a class="btn btn-secondary" href="#projects">View My Work</a>
      </div>
      <div class="trust-line">Available for freelance &amp; remote projects · India &amp; worldwide</div>
    </div>
    <div class="hero-visual reveal">
      <div class="code-window">
        <div class="window-bar"><span></span><span></span><span></span><small>developer.php</small></div>
        <pre><code><b>&lt;?php</b>

<span class="cyan">$project</span> = [
  <span class="key">'idea'</span> =&gt; <span class="str">'your business'</span>,
  <span class="key">'design'</span> =&gt; <span class="str">'premium'</span>,
  <span class="key">'stack'</span> =&gt; <span class="str">'PHP + MySQL'</span>,
  <span class="key">'result'</span> =&gt; <span class="str">'production-ready'</span>
];

<span class="cyan">build</span>($project);
<span class="cyan">launch</span>($project);
<span class="cyan">grow</span>($project);</code></pre>
      </div>
      <div class="floating-card card-one">⚡ Fast &amp; scalable</div>
      <div class="floating-card card-two">✓ Client-focused</div>
    </div>
  </div>
</section>

<section class="section" id="services">
  <div class="container">
    <div class="section-heading reveal">
      <span class="eyebrow">SERVICES</span>
      <h2>What I can build for you</h2>
      <p>From idea to production, I build reliable digital products tailored to your business.</p>
    </div>
    <div class="card-grid services-grid">
      <?php foreach (services() as $service): ?>
      <article class="service-card reveal">
        <div class="icon-box"><?= $service['icon'] ?></div>
        <h3><?= e($service['title']) ?></h3>
        <p><?= e($service['description']) ?></p>
        <a href="#contact">Discuss this service <span>→</span></a>
      </article>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section class="section section-alt" id="about">
  <div class="container split">
    <div class="section-heading reveal">
      <span class="eyebrow">WHY WORK WITH ME</span>
      <h2>Development that is built around your goals.</h2>
      <p>I focus on clean implementation, practical UX, performance and clear communication so your project is useful—not just beautiful.</p>
      <a class="btn btn-primary" href="#contact">Let's Work Together</a>
    </div>
    <div class="benefits-grid reveal">
      <?php foreach (benefits() as $benefit): ?>
      <div class="benefit"><span>✓</span><div><strong><?= e($benefit[0]) ?></strong><p><?= e($benefit[1]) ?></p></div></div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section class="section" id="process">
  <div class="container">
    <div class="section-heading reveal">
      <span class="eyebrow">PROCESS</span>
      <h2>From idea to launch</h2>
      <p>A straightforward process designed to keep your project moving.</p>
    </div>
    <div class="process-grid">
      <?php foreach (process_steps() as $step): ?>
      <div class="process-step reveal"><span><?= e($step[0]) ?></span><h3><?= e($step[1]) ?></h3><p><?= e($step[2]) ?></p></div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section class="section section-alt" id="projects">
  <div class="container">
    <div class="section-heading reveal">
      <span class="eyebrow">SELECTED WORK</span>
      <h2>Projects built to solve real problems.</h2>
      <p>Replace these demo projects with your own portfolio work from <code>config/config.php</code>.</p>
    </div>
    <div class="project-grid">
      <?php foreach (projects() as $project): ?>
      <article class="project-card reveal">
        <div class="project-art <?= e($project['class']) ?>"><span><?= e($project['tag']) ?></span><strong><?= e($project['title']) ?></strong></div>
        <div class="project-body"><h3><?= e($project['title']) ?></h3><p><?= e($project['description']) ?></p><div class="techs"><?php foreach ($project['tech'] as $t): ?><span><?= e($t) ?></span><?php endforeach; ?></div></div>
      </article>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section class="section" id="technologies">
  <div class="container tech-section">
    <div class="section-heading reveal"><span class="eyebrow">TECHNOLOGY</span><h2>A stack chosen for the job.</h2><p>Modern tools, sensible architecture and maintainable code.</p></div>
    <div class="tech-list reveal"><?php foreach (technologies() as $tech): ?><span><?= e($tech) ?></span><?php endforeach; ?></div>
  </div>
</section>

<section class="section section-alt" id="testimonials">
  <div class="container">
    <div class="section-heading reveal"><span class="eyebrow">TESTIMONIALS</span><h2>What clients can expect.</h2><p>Demo testimonials are included and should be replaced with genuine client feedback.</p></div>
    <div class="card-grid testimonials-grid">
      <?php foreach (testimonials() as $t): ?>
      <article class="testimonial reveal"><div class="stars">★★★★★</div><blockquote>“<?= e($t['quote']) ?>”</blockquote><strong><?= e($t['name']) ?></strong><small><?= e($t['company']) ?></small></article>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section class="section" id="faq">
  <div class="container faq-wrap">
    <div class="section-heading reveal"><span class="eyebrow">FAQ</span><h2>Questions, answered.</h2></div>
    <div class="faq-list reveal">
      <?php foreach (faqs() as $faq): ?>
      <details><summary><?= e($faq[0]) ?><span>+</span></summary><p><?= e($faq[1]) ?></p></details>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section class="section contact-section section-alt" id="contact">
  <div class="container contact-grid">
    <div class="contact-info reveal">
      <span class="eyebrow">CONTACT US</span>
      <h2>Let's build something great together.</h2>
      <p>Have an idea, project, or business challenge? Tell me about it and let's discuss how I can help bring it to life.</p>
      <div class="contact-card">
        <div class="contact-item"><div class="icon-box">✉</div><div><strong>Email</strong><a href="mailto:<?= e(SITE_EMAIL) ?>"><?= e(SITE_EMAIL) ?></a></div></div>
        <div class="contact-item"><div class="icon-box">⌕</div><div><strong>Phone</strong><a href="tel:<?= e(SITE_PHONE_RAW) ?>"><?= e(SITE_PHONE) ?></a></div></div>
        <div class="contact-item"><div class="icon-box">⌖</div><div><strong>Location</strong><span><?= e(SITE_LOCATION) ?></span></div></div>
      </div>
      <div class="socials">
        <a href="<?= e(SITE_GITHUB) ?>" target="_blank" rel="noopener">GitHub</a>
        <a href="<?= e(SITE_LINKEDIN) ?>" target="_blank" rel="noopener">LinkedIn</a>
        <a href="<?= e(SITE_TWITTER) ?>" target="_blank" rel="noopener">X / Twitter</a>
      </div>
    </div>

    <div class="form-card reveal">
      <h2>Start your project</h2>
      <p>Tell me a little about your project and I'll get back to you.</p>
      <?php if ($msg = flash('success')): ?><div class="alert success"><?= e($msg) ?></div><?php endif; ?>
      <?php if ($msg = flash('error')): ?><div class="alert error"><?= e($msg) ?></div><?php endif; ?>
      <form action="actions/submit-inquiry.php" method="post" id="contactForm" novalidate>
        <?= csrf_field() ?>
        <input type="text" name="website" class="hp" tabindex="-1" autocomplete="off">
        <div class="form-row">
          <label>Full Name *<input name="full_name" required maxlength="150" placeholder="John Doe"></label>
          <label>Email *<input type="email" name="email" required maxlength="255" placeholder="john@example.com"></label>
        </div>
        <div class="form-row">
          <label>Phone<input name="phone" maxlength="50" placeholder="+91 98765 43210"></label>
          <label>Service Inquiry *<select name="service" required><option value="">Select a service...</option><?php foreach (services() as $s): ?><option><?= e($s['title']) ?></option><?php endforeach; ?></select></label>
        </div>
        <div class="form-row">
          <label>Project Budget<select name="budget"><option value="">Select...</option><?php foreach (budgets() as $x): ?><option><?= e($x) ?></option><?php endforeach; ?></select></label>
          <label>Project Timeline<select name="timeline"><option value="">Select...</option><?php foreach (timelines() as $x): ?><option><?= e($x) ?></option><?php endforeach; ?></select></label>
        </div>
        <label>Message *<textarea name="message" required minlength="10" maxlength="5000" placeholder="Tell me about your project, goals, required features, and important details..."></textarea></label>
        <button class="btn btn-primary submit-btn" type="submit">Send Project Inquiry <span>→</span></button>
      </form>
    </div>
  </div>
</section>
</main>

<?php include __DIR__ . '/includes/footer.php'; ?>
</body>
</html>
