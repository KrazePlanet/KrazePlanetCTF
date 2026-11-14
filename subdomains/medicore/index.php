<?php
require_once __DIR__ . '/lib/bootstrap.php';
require_once __DIR__ . '/lib/layout.php';

$path   = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '/';
$rel    = '/' . trim(substr($path, strlen(BASE)), '/');
$method = $_SERVER['REQUEST_METHOD'];
$post   = $method === 'POST';

match (true) {
    $rel === '/'                        => page_home(),
    $rel === '/doctors'                 => page_doctors(),
    $rel === '/health-packages'         => page_packages(),
    $rel === '/contact'                 => $post ? action_contact() : page_contact(),
    $rel === '/login'                   => $post ? action_login() : page_login(),
    $rel === '/register'                => $post ? action_register() : page_register(),
    $rel === '/forgot-password'         => $post ? action_forgot() : page_forgot(),
    $rel === '/logout' && $post         => action_logout(),

    $rel === '/portal'                  => portal_home(),
    $rel === '/portal/reports'          => portal_reports(),
    $rel === '/portal/reports/view'     => portal_report_view(),
    $rel === '/portal/reports/print'    => portal_report_print(),
    $rel === '/portal/book-test'        => $post ? action_book_test() : portal_book_test(),
    $rel === '/portal/appointments'     => $post ? action_book_appointment() : portal_appointments(),
    $rel === '/portal/profile'          => $post ? action_profile() : portal_profile(),
    $rel === '/portal/clinician'               => redirect('portal/clinician/patients'),
    $rel === '/portal/clinician/patients'      => clinician_patients(),
    $rel === '/portal/clinician/patients/view' => clinician_chart(),
    default                             => page_404(),
};

/* ══════════════ PUBLIC SITE ══════════════ */

function page_home(): void {
    page_open('', ''); ?>
<section style="background:linear-gradient(120deg,#0b2545 0%,#13315c 55%,#0e7c86 100%);color:#fff">
  <div class="container" style="display:grid;grid-template-columns:1.25fr 1fr;gap:40px;padding:64px 20px;align-items:center" id="hero">
    <div>
      <span class="tag" style="background:rgba(255,255,255,.14);color:#d6f1f3">Online lab reports · Same-day results</span>
      <h1 style="color:#fff;font-size:2.4rem;margin:16px 0 14px">Care that stays with you,<br>from consultation to report.</h1>
      <p style="color:#c9d6e8;max-width:520px;font-size:1.02rem">Book consultations with our specialists, schedule lab tests with home sample collection, and view your reports securely in the MediCore patient portal.</p>
      <div style="display:flex;gap:12px;margin-top:26px;flex-wrap:wrap">
        <a href="<?= url('portal/book-test') ?>" class="btn btn-p btn-lg"><?= icon('flask') ?> Book a Lab Test</a>
        <a href="<?= url('portal/appointments') ?>" class="btn btn-lg" style="background:#fff;color:var(--navy)"><?= icon('calendar') ?> Book Appointment</a>
      </div>
    </div>
    <div class="card pad" style="color:var(--text)">
      <h3 style="margin-bottom:4px">Patient Portal</h3>
      <p class="small muted" style="margin-bottom:16px">Access your lab reports and appointments.</p>
      <a href="<?= url('login') ?>" class="btn btn-p btn-block" style="margin-bottom:10px">Sign in</a>
      <a href="<?= url('register') ?>" class="btn btn-o btn-block">New patient? Register</a>
      <div style="display:flex;gap:8px;align-items:center;margin-top:16px" class="small muted"><?= icon('shield', 16) ?> Your records are encrypted and private.</div>
    </div>
  </div>
</section>

<section class="sec"><div class="container">
  <div class="grid g4">
    <?php foreach ([['activity', '120+', 'Specialist doctors'], ['flask', '1,800+', 'Tests performed daily'], ['clock', '6 hrs', 'Average report time'], ['heart', '24×7', 'Emergency & ICU']] as [$i, $v, $k]): ?>
    <div class="card stat" style="display:flex;gap:14px;align-items:center"><span style="color:var(--teal)"><?= icon($i, 26) ?></span><div><div class="v"><?= $v ?></div><div class="k"><?= $k ?></div></div></div>
    <?php endforeach; ?>
  </div>
</div></section>

<section class="sec" style="padding-top:0"><div class="container">
  <h2 class="sec-title">Centres of excellence</h2>
  <p class="sec-sub">Specialist care across departments, backed by an in-house diagnostic laboratory.</p>
  <div class="grid g3">
    <?php foreach ([
        ['heart', 'Cardiology', 'ECG, echocardiography, stress testing and preventive heart care.'],
        ['activity', 'Endocrinology', 'Diabetes, thyroid and hormonal disorder management.'],
        ['steth', 'General Medicine', 'Consultations for fever, infections, chronic illness and check-ups.'],
        ['bone', 'Orthopaedics', 'Joint pain, sports injuries, fractures and physiotherapy.'],
        ['flask', 'Pathology & Lab', 'NABL-standard processes, home sample collection, online reports.'],
        ['shield', 'Preventive Health', 'Health check packages tailored to age and lifestyle.'],
    ] as [$i, $t, $d]): ?>
    <div class="card pad"><span style="color:var(--teal)"><?= icon($i, 26) ?></span><h3 style="font-size:1.05rem;margin:12px 0 6px"><?= $t ?></h3><p class="small muted"><?= $d ?></p></div>
    <?php endforeach; ?>
  </div>
</div></section>

<section class="sec" style="background:#fff;border-top:1px solid var(--line);border-bottom:1px solid var(--line)"><div class="container">
  <div class="ph"><div><h2 class="sec-title">Popular health packages</h2><p class="muted">Includes home sample collection within city limits.</p></div><a href="<?= url('health-packages') ?>" class="btn btn-o">View all packages <?= icon('arrow', 16) ?></a></div>
  <div class="grid g3">
    <?php foreach (array_slice(PACKAGES, 0, 3) as [$n, $price, $tests, $d]): ?>
    <div class="card pad"><h3 style="font-size:1.05rem"><?= h($n) ?></h3><p class="small muted" style="margin:6px 0 14px"><?= h($d) ?></p>
      <div style="font-size:1.4rem;font-weight:700;color:var(--navy)">₹<?= number_format($price) ?></div>
      <p class="small muted" style="margin-bottom:14px"><?= count($tests) ?> tests included</p>
      <a href="<?= url('portal/book-test') ?>" class="btn btn-p btn-sm">Book now</a></div>
    <?php endforeach; ?>
  </div>
</div></section>

<section class="sec"><div class="container">
  <h2 class="sec-title">Your reports, online</h2>
  <p class="sec-sub">Three steps from sample to result.</p>
  <div class="grid g3">
    <?php foreach ([['1', 'Book a test', 'Choose a test or package in the patient portal and pick a collection slot.'], ['2', 'Give your sample', 'Our phlebotomist visits your home, or walk in to any MediCore collection centre.'], ['3', 'Get your report', 'Results are verified by our pathologists and published to your portal.']] as [$n, $t, $d]): ?>
    <div class="card pad"><div style="width:34px;height:34px;border-radius:50%;background:var(--tealbg);color:var(--teal2);display:grid;place-items:center;font-weight:700"><?= $n ?></div><h3 style="font-size:1.02rem;margin:12px 0 6px"><?= $t ?></h3><p class="small muted"><?= $d ?></p></div>
    <?php endforeach; ?>
  </div>
</div></section>
<style>@media(max-width:900px){#hero{grid-template-columns:1fr!important}}</style>
<?php page_close();
}

function page_doctors(): void {
    page_open('Find a Doctor', 'doctors'); ?>
<div class="container sec">
  <h1 class="sec-title">Find a Doctor</h1>
  <p class="sec-sub">Our consultants and their outpatient hours. Sign in to book an appointment.</p>
  <div class="grid g3">
    <?php foreach (DOCTORS as [, $name, $dept, $qual, $yrs, $hrs]):
        $ini = implode('', array_map(fn($w) => $w[0], array_slice(explode(' ', $name), 1))); ?>
    <div class="card pad">
      <div style="display:flex;gap:14px;align-items:center;margin-bottom:14px">
        <div style="width:52px;height:52px;border-radius:50%;background:var(--tealbg);color:var(--teal2);display:grid;place-items:center;font-weight:700"><?= h($ini) ?></div>
        <div><h3 style="font-size:1.02rem"><?= h($name) ?></h3><div class="small" style="color:var(--teal2);font-weight:600"><?= h($dept) ?></div></div>
      </div>
      <p class="small muted"><?= h($qual) ?> · <?= $yrs ?> years experience</p>
      <p class="small muted" style="margin:6px 0 16px;display:flex;gap:6px;align-items:center"><?= icon('clock', 14) ?> <?= h($hrs) ?></p>
      <a href="<?= url('portal/appointments') ?>" class="btn btn-o btn-sm">Book appointment</a>
    </div>
    <?php endforeach; ?>
  </div>
</div>
<?php page_close();
}

function page_packages(): void {
    page_open('Health Packages', 'health-packages'); ?>
<div class="container sec">
  <h1 class="sec-title">Health Packages & Lab Tests</h1>
  <p class="sec-sub">Prices include home sample collection. Reports are published to your patient portal.</p>
  <div class="grid g2" style="margin-bottom:36px">
    <?php foreach (PACKAGES as [$n, $price, $tests, $d]): ?>
    <div class="card pad">
      <div style="display:flex;justify-content:space-between;gap:12px"><h3 style="font-size:1.08rem"><?= h($n) ?></h3><div style="font-size:1.25rem;font-weight:700;color:var(--navy)">₹<?= number_format($price) ?></div></div>
      <p class="small muted" style="margin:6px 0 12px"><?= h($d) ?></p>
      <ul style="list-style:none;font-size:.86rem;margin-bottom:16px">
        <?php foreach ($tests as $t): ?><li style="display:flex;gap:8px;align-items:center;margin-bottom:4px"><span style="color:var(--teal)"><?= icon('check', 15) ?></span><?= h(TESTS[$t][0]) ?></li><?php endforeach; ?>
      </ul>
      <a href="<?= url('portal/book-test') ?>" class="btn btn-p btn-sm">Book package</a>
    </div>
    <?php endforeach; ?>
  </div>
  <h2 class="sec-title" style="font-size:1.25rem">Individual tests</h2>
  <div class="card" style="margin-top:14px;overflow-x:auto">
    <table class="tbl"><thead><tr><th>Test</th><th class="hide-sm">Sample</th><th>Price</th><th></th></tr></thead><tbody>
    <?php foreach (TESTS as [$n, $p, $s]): ?>
      <tr><td><?= h($n) ?></td><td class="muted hide-sm"><?= h($s) ?></td><td>₹<?= number_format($p) ?></td><td style="text-align:right"><a href="<?= url('portal/book-test') ?>" class="btn btn-o btn-sm">Book</a></td></tr>
    <?php endforeach; ?>
    </tbody></table>
  </div>
</div>
<?php page_close();
}

function page_contact(): void {
    page_open('Contact Us', 'contact'); ?>
<div class="container sec">
  <h1 class="sec-title">Contact Us</h1>
  <p class="sec-sub">For emergencies, call our 24×7 helpline. For anything else, send us a message.</p>
  <div class="grid g2">
    <div class="card pad">
      <?php foreach ([['phone', 'Emergency & ambulance', '1800 000 0000 (24×7)'], ['calendar', 'Appointments', '1800 000 0001 · Mon – Sat, 8 AM – 8 PM'], ['mail', 'Email', 'care@medicore.example'], ['pin', 'Main campus', 'Andheri East, Mumbai, Maharashtra']] as [$i, $k, $v]): ?>
      <div style="display:flex;gap:14px;margin-bottom:18px"><span style="color:var(--teal)"><?= icon($i, 22) ?></span><div><div style="font-weight:600"><?= $k ?></div><div class="small muted"><?= $v ?></div></div></div>
      <?php endforeach; ?>
    </div>
    <form class="card pad" method="POST" action="<?= url('contact') ?>">
      <?= csrf_field() ?>
      <div class="form-row"><label class="l">Your name</label><input class="in" name="name" required></div>
      <div class="form-row"><label class="l">Email</label><input class="in" type="email" name="email" required></div>
      <div class="form-row"><label class="l">Message</label><textarea class="in" name="message" rows="5" required></textarea></div>
      <button class="btn btn-p">Send message</button>
    </form>
  </div>
</div>
<?php page_close();
}

function action_contact(): void {
    verify_csrf();
    flash('success', 'Thank you. Our patient care team will get back to you within one working day.');
    redirect('contact');
}

/* ══════════════ ACCOUNTS ══════════════ */

function auth_card_open(string $title, string $sub): void {
    page_open($title); ?>
<div class="container" style="max-width:460px;padding-top:44px">
  <div class="card pad" style="padding:30px">
    <h1 style="font-size:1.4rem;margin-bottom:4px"><?= h($title) ?></h1>
    <p class="muted small" style="margin-bottom:22px"><?= h($sub) ?></p>
<?php
}
function auth_card_close(): void { echo "  </div>\n</div>\n"; page_close(); }

function page_login(): void {
    if (user()) redirect('portal');
    if (!empty($_GET['staff'])) auth_card_open('Staff Sign In', 'MediCore clinicians and staff. Use your hospital email address.');
    else auth_card_open('Patient Login', 'Sign in to view your lab reports and appointments.'); ?>
    <form method="POST" action="<?= url('login') ?>">
      <?= csrf_field() ?>
      <div class="form-row"><label class="l">Email address</label><input class="in" type="email" name="email" required autofocus autocomplete="username"></div>
      <div class="form-row"><label class="l">Password</label><input class="in" type="password" name="password" required autocomplete="current-password"></div>
      <div style="text-align:right;margin:-6px 0 16px" class="small"><a href="<?= url('forgot-password') ?>">Forgot password?</a></div>
      <button class="btn btn-p btn-block btn-lg">Sign in</button>
    </form>
    <p class="small muted" style="text-align:center;margin-top:18px">New to MediCore? <a href="<?= url('register') ?>">Create an account</a></p>
<?php auth_card_close();
}

function action_login(): void {
    verify_csrf();
    $st = db()->prepare('SELECT * FROM users WHERE email=?');
    $st->execute([trim($_POST['email'] ?? '')]);
    $u = $st->fetch();
    if (!$u || !password_verify($_POST['password'] ?? '', $u['password_hash'])) {
        flash('error', 'Incorrect email or password.');
        redirect('login');
    }
    session_regenerate_id(true);
    $_SESSION['uid'] = (int)$u['id'];
    $next = $_SESSION['after_login'] ?? '';
    unset($_SESSION['after_login']);
    // Only same-site paths inside the portal.
    if (is_string($next) && preg_match('#^' . preg_quote(BASE, '#') . '/portal(/[A-Za-z0-9/_-]*)?(\?[A-Za-z0-9=&_-]*)?$#', $next)) {
        header('Location: ' . $next);
        exit;
    }
    redirect('portal');
}

function page_register(): void {
    if (user()) redirect('portal');
    auth_card_open('Create your account', 'Register once to book tests and see your reports online.'); ?>
    <form method="POST" action="<?= url('register') ?>">
      <?= csrf_field() ?>
      <div class="form-row"><label class="l">Full name</label><input class="in" name="name" required maxlength="100" autocomplete="name"></div>
      <div class="form-row"><label class="l">Email address</label><input class="in" type="email" name="email" required maxlength="190" autocomplete="email"></div>
      <div class="form-row"><label class="l">Mobile number</label><input class="in" name="phone" required maxlength="20" placeholder="+91" autocomplete="tel"></div>
      <div class="grid g2" style="gap:12px">
        <div class="form-row"><label class="l">Date of birth</label><input class="in" type="date" name="dob" required max="<?= date('Y-m-d') ?>"></div>
        <div class="form-row"><label class="l">Gender</label><select class="in" name="gender"><option value="F">Female</option><option value="M">Male</option><option value="O">Other</option></select></div>
      </div>
      <div class="form-row"><label class="l">Password</label><input class="in" type="password" name="password" required minlength="8" autocomplete="new-password"></div>
      <div class="form-row"><label class="l">Confirm password</label><input class="in" type="password" name="password2" required minlength="8" autocomplete="new-password"></div>
      <label class="small muted" style="display:flex;gap:8px;align-items:flex-start;margin-bottom:18px"><input type="checkbox" required style="margin-top:3px"> I agree to the Terms of Use and consent to my health records being stored in the MediCore patient portal.</label>
      <button class="btn btn-p btn-block btn-lg">Create account</button>
    </form>
    <p class="small muted" style="text-align:center;margin-top:18px">Already registered? <a href="<?= url('login') ?>">Sign in</a></p>
<?php auth_card_close();
}

function action_register(): void {
    verify_csrf();
    $name  = trim($_POST['name'] ?? '');
    $email = strtolower(trim($_POST['email'] ?? ''));
    $phone = trim($_POST['phone'] ?? '');
    $dob   = $_POST['dob'] ?? '';
    $g     = in_array($_POST['gender'] ?? '', ['M', 'F', 'O'], true) ? $_POST['gender'] : 'O';
    $pw    = $_POST['password'] ?? '';
    $err = match (true) {
        $name === '' || mb_strlen($name) > 100                     => 'Please enter your full name.',
        !filter_var($email, FILTER_VALIDATE_EMAIL)                 => 'Please enter a valid email address.',
        !preg_match('/^\+?[0-9 ]{8,18}$/', $phone)                 => 'Please enter a valid mobile number.',
        !DateTime::createFromFormat('Y-m-d', $dob) || $dob > date('Y-m-d') => 'Please enter a valid date of birth.',
        strlen($pw) < 8                                            => 'Password must be at least 8 characters.',
        $pw !== ($_POST['password2'] ?? '')                        => 'Passwords do not match.',
        default                                                    => null,
    };
    if (!$err) {
        $st = db()->prepare('SELECT 1 FROM users WHERE email=?');
        $st->execute([$email]);
        if ($st->fetchColumn()) $err = 'An account with this email already exists. Please sign in.';
    }
    if ($err) { flash('error', $err); redirect('register'); }

    db()->prepare('INSERT INTO users(name,email,password_hash,role,gender,dob,phone) VALUES(?,?,?,?,?,?,?)')
        ->execute([$name, $email, password_hash($pw, PASSWORD_DEFAULT), 'patient', $g, $dob, $phone]);
    session_regenerate_id(true);
    $_SESSION['uid'] = (int)db()->lastInsertId();
    flash('success', 'Welcome to MediCore. Your patient ID (MRN) is ' . mrn($_SESSION['uid']) . '.');
    redirect('portal');
}

function page_forgot(): void {
    auth_card_open('Reset your password', 'Enter your registered email and we will send you a reset link.'); ?>
    <form method="POST" action="<?= url('forgot-password') ?>">
      <?= csrf_field() ?>
      <div class="form-row"><label class="l">Email address</label><input class="in" type="email" name="email" required></div>
      <button class="btn btn-p btn-block">Send reset link</button>
    </form>
    <p class="small muted" style="text-align:center;margin-top:18px"><a href="<?= url('login') ?>">Back to sign in</a></p>
<?php auth_card_close();
}

function action_forgot(): void {
    verify_csrf();
    flash('info', 'If an account exists for that email, a password reset link has been sent.');
    redirect('login');
}

function action_logout(): void {
    verify_csrf();
    $_SESSION = [];
    session_destroy();
    header('Location: ' . url('login'));
    exit;
}

/* ══════════════ PATIENT PORTAL ══════════════ */

function report_list(int $patient, int $limit = 0): array {
    $st = db()->prepare('SELECT r.id,r.test_type,r.collected_at,r.reported_at,r.status,r.result_summary,d.name AS doctor FROM reports r JOIN users d ON d.id=r.ordered_by WHERE r.patient_id=? ORDER BY r.collected_at DESC' . ($limit ? " LIMIT $limit" : ''));
    $st->execute([$patient]);
    return $st->fetchAll();
}

function report_rows_html(array $rows): string {
    if (!$rows) return '<div class="empty">' . icon('file', 34) . '<div>No reports yet.</div><a href="' . url('portal/book-test') . '" class="btn btn-p btn-sm" style="margin-top:14px">Book a lab test</a></div>';
    $o = '<div style="overflow-x:auto"><table class="tbl"><thead><tr><th>Report no.</th><th>Test</th><th class="hide-sm">Collected</th><th class="hide-sm">Referred by</th><th>Status</th><th></th></tr></thead><tbody>';
    foreach ($rows as $r) {
        $o .= '<tr><td class="muted">' . $r['id'] . '</td><td style="font-weight:500">' . h($r['test_type']) . '</td><td class="hide-sm muted">' . date('d M Y', strtotime($r['collected_at'])) . '</td><td class="hide-sm muted">' . h($r['doctor']) . '</td><td>' . status_tag($r['status']) . '</td><td style="text-align:right"><a class="btn btn-o btn-sm" href="' . url('portal/reports/view?id=' . $r['id']) . '">View</a></td></tr>';
    }
    return $o . '</tbody></table></div>';
}

function portal_home(): void {
    $u = require_login();
    if ($u['role'] === 'doctor') { doctor_home($u); return; }
    $reports = report_list((int)$u['id'], 5);
    $st = db()->prepare("SELECT COUNT(*) FROM reports WHERE patient_id=?"); $st->execute([$u['id']]); $nrep = (int)$st->fetchColumn();
    $st = db()->prepare("SELECT a.*,d.name AS doctor,d.department FROM appointments a JOIN users d ON d.id=a.doctor_id WHERE a.patient_id=? AND a.status='scheduled' AND a.appointment_date>=CURDATE() ORDER BY a.appointment_date LIMIT 1");
    $st->execute([$u['id']]); $next = $st->fetch();
    portal_open('My Portal', 'portal'); ?>
    <div class="ph"><div><h1>Hello, <?= h(explode(' ', $u['name'])[0]) ?></h1><p class="muted small">MRN <?= mrn((int)$u['id']) ?></p></div>
      <a href="<?= url('portal/book-test') ?>" class="btn btn-p"><?= icon('plus', 16) ?> Book a lab test</a></div>
    <div class="grid g3" style="margin-bottom:22px">
      <div class="card stat"><div class="k">Lab reports</div><div class="v"><?= $nrep ?></div></div>
      <div class="card stat"><div class="k">Next appointment</div><div class="v" style="font-size:1.05rem;margin-top:6px"><?= $next ? date('d M Y', strtotime($next['appointment_date'])) . ', ' . h($next['appointment_time']) : 'None booked' ?></div></div>
      <div class="card stat"><div class="k">Blood group</div><div class="v"><?= h($u['blood_group'] ?: '—') ?></div></div>
    </div>
    <div class="card">
      <div class="ph pad" style="margin:0;padding-bottom:12px"><h2 style="font-size:1.05rem">Recent reports</h2><a href="<?= url('portal/reports') ?>" class="small">All reports</a></div>
      <?= report_rows_html($reports) ?>
    </div>
<?php portal_close();
}

function portal_reports(): void {
    $u = require_login();
    if ($u['role'] === 'doctor') redirect('portal/clinician/patients');
    portal_open('Lab Reports', 'portal/reports'); ?>
    <div class="ph"><div><h1>Lab Reports</h1><p class="muted small">Reports are published once verified by our pathologist.</p></div></div>
    <div class="card"><?= report_rows_html(report_list((int)$u['id'])) ?></div>
<?php portal_close();
}

function load_report(int $id): ?array {
    $st = db()->prepare('SELECT r.*,d.name AS doctor,d.department,p.name AS patient_name,p.gender,p.dob,p.phone FROM reports r JOIN users d ON d.id=r.ordered_by JOIN users p ON p.id=r.patient_id WHERE r.id=?');
    $st->execute([$id]);
    return $st->fetch() ?: null;
}

function portal_report_view(): void {
    $u = require_login();
    $r = load_report((int)($_GET['id'] ?? 0));
    // Patients may only open their own reports; doctors can open any.
    if (!$r || ($u['role'] !== 'doctor' && (int)$r['patient_id'] !== (int)$u['id'])) { page_404('We couldn\'t find that report.'); return; }
    $detail = json_decode($r['result_detail'], true) ?: [];
    portal_open($r['test_type'], $u['role'] === 'doctor' ? 'portal/clinician/patients' : 'portal/reports'); ?>
    <div class="ph">
      <div><a href="<?= url($u['role'] === 'doctor' ? 'portal/clinician/patients/view?id=' . $r['patient_id'] : 'portal/reports') ?>" class="small">← Back</a><h1 style="margin-top:6px"><?= h($r['test_type']) ?></h1><p class="muted small">Report no. <?= $r['id'] ?> · <?= status_tag($r['status']) ?></p></div>
      <a href="<?= url('portal/reports/print?id=' . $r['id']) ?>" target="_blank" class="btn btn-n"><?= icon('download', 16) ?> Download PDF</a>
    </div>
    <div class="card pad" style="margin-bottom:18px">
      <div class="grid g4 small">
        <div><div class="muted">Patient</div><b><?= h($r['patient_name']) ?></b></div>
        <div><div class="muted">Referred by</div><b><?= h($r['doctor']) ?></b></div>
        <div><div class="muted">Collected</div><b><?= date('d M Y, h:i A', strtotime($r['collected_at'])) ?></b></div>
        <div><div class="muted">Reported</div><b><?= date('d M Y, h:i A', strtotime($r['reported_at'])) ?></b></div>
      </div>
    </div>
    <div class="card" style="overflow-x:auto;margin-bottom:18px"><?= result_table($detail) ?></div>
    <div class="card pad small"><b>Summary:</b> <?= h($r['result_summary']) ?><br><span class="muted"><?= h($detail['remark'] ?? '') ?></span></div>
<?php portal_close();
}

/* Printable report (opened by "Download PDF"). */
function portal_report_print(): void {
    $u = require_login();
    $r = load_report((int)($_GET['id'] ?? 0));
    if (!$r || ($u['role'] !== 'doctor' && (int)$r['patient_id'] !== (int)$u['id'])) { page_404('We couldn\'t find that report.'); return; }
    $detail = json_decode($r['result_detail'], true) ?: [];
    page_head('Report ' . $r['id']); ?>
<style>body{background:#e9edf2}.sheet{background:#fff;max-width:820px;margin:24px auto;padding:40px 44px;box-shadow:0 2px 14px rgba(0,0,0,.08)}.lh{display:flex;justify-content:space-between;align-items:flex-start;border-bottom:3px solid var(--teal);padding-bottom:16px;margin-bottom:20px}.meta{display:grid;grid-template-columns:1fr 1fr;gap:6px 30px;font-size:.86rem;margin-bottom:22px}.meta span{color:var(--muted);display:inline-block;width:120px}.bar{max-width:820px;margin:16px auto 0;display:flex;justify-content:flex-end;gap:10px;padding:0 4px}@media print{body{background:#fff}.bar{display:none}.sheet{box-shadow:none;margin:0;max-width:none}}@media(max-width:600px){.sheet{padding:22px}.meta{grid-template-columns:1fr}}</style>
<div class="bar"><button class="btn btn-n btn-sm" onclick="window.print()"><?= icon('print', 15) ?> Print / Save as PDF</button></div>
<div class="sheet">
  <div class="lh">
    <div><?= logo() ?><div class="small muted" style="margin-top:8px">Department of Pathology & Laboratory Medicine<br>Main Campus, Andheri East, Mumbai</div></div>
    <div class="small muted" style="text-align:right">Report no. <b style="color:var(--text)"><?= $r['id'] ?></b><br>Lab helpline 1800 000 0002</div>
  </div>
  <div class="meta">
    <div><span>Patient name</span><b><?= h($r['patient_name']) ?></b></div>
    <div><span>MRN</span><?= mrn((int)$r['patient_id']) ?></div>
    <div><span>Age / Sex</span><?= age($r['dob']) ?> yrs / <?= sex($r['gender']) ?></div>
    <div><span>Mobile</span><?= h($r['phone'] ?: '—') ?></div>
    <div><span>Referred by</span><?= h($r['doctor']) ?></div>
    <div><span>Sample</span><?= h($r['sample_type']) ?></div>
    <div><span>Collected</span><?= date('d M Y, h:i A', strtotime($r['collected_at'])) ?></div>
    <div><span>Reported</span><?= date('d M Y, h:i A', strtotime($r['reported_at'])) ?></div>
  </div>
  <h2 style="font-size:1.05rem;text-align:center;margin-bottom:12px;text-transform:uppercase;letter-spacing:1px"><?= h($r['test_type']) ?></h2>
  <?= result_table($detail) ?>
  <p class="small" style="margin-top:18px"><b>Interpretation:</b> <?= h($r['result_summary']) ?> <?= h($detail['remark'] ?? '') ?></p>
  <div style="display:flex;justify-content:space-between;align-items:flex-end;margin-top:46px" class="small">
    <div class="muted">*** End of report ***<br>Results relate only to the sample tested.</div>
    <div style="text-align:right"><b>Dr. S. Khan</b><br><span class="muted">MD (Pathology), Consultant Pathologist</span></div>
  </div>
</div>
</body></html>
<?php
}

function portal_book_test(): void {
    $u = require_login();
    if ($u['role'] === 'doctor') redirect('portal');
    portal_open('Book a Lab Test', 'portal/book-test'); ?>
    <div class="ph"><div><h1>Book a Lab Test</h1><p class="muted small">Results are published to your portal, usually the same day.</p></div></div>
    <form class="card pad" method="POST" action="<?= url('portal/book-test') ?>" style="max-width:620px">
      <?= csrf_field() ?>
      <div class="form-row"><label class="l">Test</label>
        <select class="in" name="test"><?php foreach (TESTS as $c => [$n, $p]): ?><option value="<?= $c ?>"><?= h($n) ?> — ₹<?= number_format($p) ?></option><?php endforeach; ?></select></div>
      <div class="form-row"><label class="l">Collection</label>
        <select class="in" name="mode"><option value="home">Home sample collection</option><option value="walkin">Walk-in at collection centre</option></select></div>
      <div class="grid g2" style="gap:12px">
        <div class="form-row"><label class="l">Preferred date</label><input class="in" type="date" name="date" required value="<?= date('Y-m-d') ?>" min="<?= date('Y-m-d') ?>"></div>
        <div class="form-row"><label class="l">Time slot</label><select class="in" name="slot"><option>07:00 – 08:00 AM</option><option>08:00 – 09:00 AM</option><option>09:00 – 10:00 AM</option><option>10:00 – 11:00 AM</option></select></div>
      </div>
      <p class="small muted" style="margin-bottom:16px">Payment is collected at the time of sample collection (cash, card or UPI).</p>
      <button class="btn btn-p">Confirm booking</button>
    </form>
<?php portal_close();
}

function action_book_test(): void {
    $u = require_login();
    verify_csrf();
    $code = $_POST['test'] ?? '';
    if (!isset(TESTS[$code]) || $u['role'] === 'doctor') { flash('error', 'Please choose a test.'); redirect('portal/book-test'); }
    $st = db()->prepare("SELECT id FROM users WHERE role='doctor' AND department LIKE 'Pathology%' LIMIT 1");
    $st->execute();
    $id = insert_report(db(), (int)$u['id'], (int)$st->fetchColumn(), $code, time() - 10 * 3600, 'ready');
    flash('success', 'Your sample has been processed and your report is ready.');
    redirect('portal/reports/view?id=' . $id);
}

function portal_appointments(): void {
    $u = require_login();
    $col = $u['role'] === 'doctor' ? 'doctor_id' : 'patient_id';
    $st = db()->prepare("SELECT a.*,d.name AS doctor,d.department,p.name AS patient FROM appointments a JOIN users d ON d.id=a.doctor_id JOIN users p ON p.id=a.patient_id WHERE a.$col=? ORDER BY a.appointment_date DESC");
    $st->execute([$u['id']]);
    $rows = $st->fetchAll();
    $docs = db()->query("SELECT id,name,department FROM users WHERE role='doctor' ORDER BY department")->fetchAll();
    portal_open('Appointments', 'portal/appointments'); ?>
    <div class="ph"><div><h1>Appointments</h1><p class="muted small">Outpatient consultations at the main campus.</p></div></div>
    <?php if ($u['role'] !== 'doctor'): ?>
    <form class="card pad" method="POST" action="<?= url('portal/appointments') ?>" style="margin-bottom:20px">
      <?= csrf_field() ?>
      <h2 style="font-size:1.02rem;margin-bottom:14px">Book a consultation</h2>
      <div class="grid g2" style="gap:12px">
        <div class="form-row"><label class="l">Doctor</label><select class="in" name="doctor"><?php foreach ($docs as $d): ?><option value="<?= $d['id'] ?>"><?= h($d['name']) ?> — <?= h($d['department']) ?></option><?php endforeach; ?></select></div>
        <div class="form-row"><label class="l">Reason for visit</label><input class="in" name="reason" required maxlength="200" placeholder="e.g. Annual check-up"></div>
        <div class="form-row"><label class="l">Date</label><input class="in" type="date" name="date" required min="<?= date('Y-m-d', strtotime('+1 day')) ?>"></div>
        <div class="form-row"><label class="l">Time</label><select class="in" name="time"><?php foreach (['09:30 AM', '10:00 AM', '10:30 AM', '11:00 AM', '11:30 AM', '12:00 PM', '03:00 PM', '03:30 PM', '04:00 PM', '05:00 PM'] as $t): ?><option><?= $t ?></option><?php endforeach; ?></select></div>
      </div>
      <button class="btn btn-p">Request appointment</button>
    </form>
    <?php endif; ?>
    <div class="card">
      <?php if (!$rows): ?><div class="empty"><?= icon('calendar', 34) ?><div>No appointments yet.</div></div>
      <?php else: ?><div style="overflow-x:auto"><table class="tbl"><thead><tr><th>Date</th><th><?= $u['role'] === 'doctor' ? 'Patient' : 'Doctor' ?></th><th class="hide-sm">Reason</th><th>Status</th></tr></thead><tbody>
        <?php foreach ($rows as $a): ?><tr><td><?= date('d M Y', strtotime($a['appointment_date'])) ?><div class="small muted"><?= h($a['appointment_time']) ?></div></td><td><?= h($u['role'] === 'doctor' ? $a['patient'] : $a['doctor']) ?><?php if ($u['role'] !== 'doctor'): ?><div class="small muted"><?= h($a['department']) ?></div><?php endif; ?></td><td class="hide-sm muted"><?= h($a['reason']) ?></td><td><?= status_tag($a['status']) ?></td></tr><?php endforeach; ?>
      </tbody></table></div><?php endif; ?>
    </div>
<?php portal_close();
}

function action_book_appointment(): void {
    $u = require_login();
    verify_csrf();
    $doc = (int)($_POST['doctor'] ?? 0);
    $date = $_POST['date'] ?? '';
    $reason = trim($_POST['reason'] ?? '');
    $time = $_POST['time'] ?? '';
    $st = db()->prepare("SELECT 1 FROM users WHERE id=? AND role='doctor'");
    $st->execute([$doc]);
    if ($u['role'] === 'doctor' || !$st->fetchColumn() || $reason === '' || !DateTime::createFromFormat('Y-m-d', $date) || $date <= date('Y-m-d') || !preg_match('/^\d\d:\d\d [AP]M$/', $time)) {
        flash('error', 'Please check the appointment details and try again.');
        redirect('portal/appointments');
    }
    db()->prepare('INSERT INTO appointments(patient_id,doctor_id,appointment_date,appointment_time,reason) VALUES(?,?,?,?,?)')
        ->execute([$u['id'], $doc, $date, $time, mb_substr($reason, 0, 200)]);
    flash('success', 'Appointment requested. You will receive a confirmation SMS shortly.');
    redirect('portal/appointments');
}

function portal_profile(): void {
    $u = require_login();
    portal_open('My Profile', 'portal/profile'); ?>
    <div class="ph"><div><h1>My Profile</h1><p class="muted small">Keep your contact details up to date.</p></div></div>
    <div class="card pad" style="margin-bottom:18px">
      <div class="grid g3 small">
        <div><div class="muted">Name</div><b><?= h($u['name']) ?></b></div>
        <div><div class="muted">Email</div><b><?= h($u['email']) ?></b></div>
        <div><div class="muted"><?= $u['role'] === 'doctor' ? 'Department' : 'MRN' ?></div><b><?= $u['role'] === 'doctor' ? h($u['department']) : mrn((int)$u['id']) ?></b></div>
        <?php if ($u['role'] !== 'doctor'): ?>
        <div><div class="muted">Date of birth</div><b><?= $u['dob'] ? date('d M Y', strtotime($u['dob'])) : '—' ?></b></div>
        <div><div class="muted">Gender</div><b><?= sex($u['gender']) ?></b></div>
        <div><div class="muted">Member since</div><b><?= date('M Y', strtotime($u['created_at'])) ?></b></div>
        <?php endif; ?>
      </div>
    </div>
    <form class="card pad" method="POST" action="<?= url('portal/profile') ?>" style="max-width:620px">
      <?= csrf_field() ?>
      <div class="grid g2" style="gap:12px">
        <div class="form-row"><label class="l">Mobile number</label><input class="in" name="phone" value="<?= h($u['phone']) ?>" maxlength="20"></div>
        <div class="form-row"><label class="l">City</label><input class="in" name="city" value="<?= h($u['city']) ?>" maxlength="80"></div>
        <div class="form-row"><label class="l">Blood group</label><select class="in" name="blood_group"><option value="">Not known</option><?php foreach (['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'] as $b): ?><option <?= $u['blood_group'] === $b ? 'selected' : '' ?>><?= $b ?></option><?php endforeach; ?></select></div>
        <div class="form-row"><label class="l">Known allergies</label><input class="in" name="allergies" value="<?= h($u['allergies']) ?>" maxlength="200"></div>
      </div>
      <button class="btn btn-p">Save changes</button>
    </form>
<?php portal_close();
}

function action_profile(): void {
    $u = require_login();
    verify_csrf();
    $bg = in_array($_POST['blood_group'] ?? '', ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'], true) ? $_POST['blood_group'] : null;
    db()->prepare('UPDATE users SET phone=?,city=?,blood_group=?,allergies=? WHERE id=?')->execute([
        mb_substr(trim($_POST['phone'] ?? ''), 0, 20), mb_substr(trim($_POST['city'] ?? ''), 0, 80), $bg,
        mb_substr(trim($_POST['allergies'] ?? '') ?: 'None known', 0, 200), $u['id']]);
    flash('success', 'Profile updated.');
    redirect('portal/profile');
}

/* ══════════════ DOCTOR VIEWS ══════════════ */

function doctor_home(array $u): void {
    $st = db()->prepare("SELECT COUNT(*) FROM appointments WHERE doctor_id=? AND status='scheduled'");
    $st->execute([$u['id']]);
    portal_open('Overview', 'portal'); ?>
    <div class="ph"><div><h1><?= h($u['name']) ?></h1><p class="muted small"><?= h($u['department']) ?></p></div></div>
    <div class="grid g3"><div class="card stat"><div class="k">Scheduled appointments</div><div class="v"><?= (int)$st->fetchColumn() ?></div></div></div>
<?php portal_close();
}

/*
 * Clinician workspace. The menu entry is only rendered for doctors, but these
 * handlers only require a signed-in user: no role check (the intended bug).
 */
function clinician_patients(): void {
    require_login();
    $q = trim($_GET['q'] ?? '');
    $st = db()->prepare("SELECT u.*,(SELECT COUNT(*) FROM reports r WHERE r.patient_id=u.id) AS n,(SELECT MAX(collected_at) FROM reports r WHERE r.patient_id=u.id) AS last_test
                         FROM users u WHERE role='patient' AND (?='' OR name LIKE ? OR email LIKE ?) ORDER BY u.id");
    $like = '%' . $q . '%';
    $st->execute([$q, $like, $like]);
    $rows = $st->fetchAll();
    portal_open('Patient Directory', 'portal/clinician/patients'); ?>
    <div class="ph"><div><h1>Patient Directory</h1><p class="muted small">Clinician workspace · <?= count($rows) ?> patients</p></div>
      <form method="GET" action="<?= url('portal/clinician/patients') ?>" style="display:flex;gap:8px"><input class="in" name="q" value="<?= h($q) ?>" placeholder="Search name or email" style="width:220px"><button class="btn btn-o">Search</button></form></div>
    <div class="card" style="overflow-x:auto"><table class="tbl"><thead><tr><th>MRN</th><th>Patient</th><th class="hide-sm">Email</th><th class="hide-sm">Mobile</th><th>Reports</th><th class="hide-sm">Last test</th><th></th></tr></thead><tbody>
      <?php foreach ($rows as $p): ?><tr><td class="muted"><?= mrn((int)$p['id']) ?></td><td style="font-weight:500"><?= h($p['name']) ?><div class="small muted"><?= age($p['dob']) ?> yrs · <?= sex($p['gender']) ?></div></td><td class="hide-sm muted"><?= h($p['email']) ?></td><td class="hide-sm muted"><?= h($p['phone']) ?></td><td><?= (int)$p['n'] ?></td><td class="hide-sm muted"><?= $p['last_test'] ? date('d M Y', strtotime($p['last_test'])) : '—' ?></td><td style="text-align:right"><a class="btn btn-o btn-sm" href="<?= url('portal/clinician/patients/view?id=' . $p['id']) ?>">Open chart</a></td></tr><?php endforeach; ?>
    </tbody></table></div>
<?php portal_close();
}

function clinician_chart(): void {
    require_login();
    $st = db()->prepare("SELECT * FROM users WHERE id=? AND role='patient'");
    $st->execute([(int)($_GET['id'] ?? 0)]);
    $p = $st->fetch();
    if (!$p) { page_404(); return; }
    $st = db()->prepare('SELECT r.*,d.name AS doctor FROM reports r JOIN users d ON d.id=r.ordered_by WHERE r.patient_id=? ORDER BY r.collected_at DESC');
    $st->execute([$p['id']]);
    $reports = $st->fetchAll();
    $st = db()->prepare('SELECT a.*,d.name AS doctor FROM appointments a JOIN users d ON d.id=a.doctor_id WHERE a.patient_id=? ORDER BY a.appointment_date DESC');
    $st->execute([$p['id']]);
    $appts = $st->fetchAll();
    portal_open($p['name'], 'portal/clinician/patients'); ?>
    <div class="ph"><div><a href="<?= url('portal/clinician/patients') ?>" class="small">← Patient Directory</a><h1 style="margin-top:6px"><?= h($p['name']) ?></h1><p class="muted small">MRN <?= mrn((int)$p['id']) ?> · <?= age($p['dob']) ?> yrs · <?= sex($p['gender']) ?></p></div></div>
    <div class="card pad" style="margin-bottom:18px"><div class="grid g3 small">
      <div><div class="muted">Email</div><b><?= h($p['email']) ?></b></div>
      <div><div class="muted">Mobile</div><b><?= h($p['phone'] ?: '—') ?></b></div>
      <div><div class="muted">Date of birth</div><b><?= $p['dob'] ? date('d M Y', strtotime($p['dob'])) : '—' ?></b></div>
      <div><div class="muted">Blood group</div><b><?= h($p['blood_group'] ?: '—') ?></b></div>
      <div><div class="muted">Allergies</div><b><?= h($p['allergies']) ?></b></div>
      <div><div class="muted">City</div><b><?= h($p['city'] ?: '—') ?></b></div>
    </div></div>
    <h2 style="font-size:1.05rem;margin-bottom:12px">Lab results</h2>
    <?php if (!$reports): ?><div class="card empty" style="margin-bottom:18px">No lab results on file.</div><?php endif; ?>
    <?php foreach ($reports as $r): $d = json_decode($r['result_detail'], true) ?: []; ?>
    <div class="card" style="margin-bottom:14px;overflow-x:auto">
      <div class="pad" style="padding-bottom:10px;display:flex;justify-content:space-between;gap:10px;flex-wrap:wrap"><div><b><?= h($r['test_type']) ?></b><div class="small muted">Report no. <?= $r['id'] ?> · <?= date('d M Y', strtotime($r['collected_at'])) ?> · <?= h($r['doctor']) ?></div></div><?= status_tag($r['status']) ?></div>
      <?= result_table($d) ?>
    </div>
    <?php endforeach; ?>
    <h2 style="font-size:1.05rem;margin:22px 0 12px">Appointments</h2>
    <div class="card" style="overflow-x:auto">
      <?php if (!$appts): ?><div class="empty">No appointments.</div><?php else: ?><table class="tbl"><thead><tr><th>Date</th><th>Doctor</th><th class="hide-sm">Reason</th><th>Status</th></tr></thead><tbody>
      <?php foreach ($appts as $a): ?><tr><td><?= date('d M Y', strtotime($a['appointment_date'])) ?></td><td><?= h($a['doctor']) ?></td><td class="hide-sm muted"><?= h($a['reason']) ?></td><td><?= status_tag($a['status']) ?></td></tr><?php endforeach; ?>
      </tbody></table><?php endif; ?>
    </div>
<?php portal_close();
}

function page_404(string $msg = 'The page you are looking for doesn\'t exist or has moved.'): void {
    http_response_code(404);
    page_open('Page not found'); ?>
<div class="container" style="text-align:center;padding:80px 20px">
  <div style="font-size:3rem;font-weight:800;color:var(--teal)">404</div>
  <h1 style="font-size:1.3rem;margin:8px 0">Page not found</h1>
  <p class="muted"><?= h($msg) ?></p>
  <a href="<?= url(user() ? 'portal' : '') ?>" class="btn btn-p" style="margin-top:20px">Go to <?= user() ? 'my portal' : 'home page' ?></a>
</div>
<?php page_close();
}
