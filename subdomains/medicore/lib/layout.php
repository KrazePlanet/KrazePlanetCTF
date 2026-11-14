<?php
function icon(string $n, int $s = 18): string {
    static $p = [
        'flask'    => '<path d="M9 3h6M10 3v6l-5.5 9.5A2 2 0 0 0 6.2 21.5h11.6a2 2 0 0 0 1.7-3L14 9V3"/><path d="M7.5 15h9"/>',
        'calendar' => '<rect x="3" y="4" width="18" height="17" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/>',
        'user'     => '<circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/>',
        'home'     => '<path d="M3 11l9-8 9 8"/><path d="M5 10v10h14V10"/>',
        'file'     => '<path d="M14 3H6a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z"/><path d="M14 3v6h6M8 13h8M8 17h5"/>',
        'download' => '<path d="M12 3v12M7 10l5 5 5-5M5 21h14"/>',
        'phone'    => '<path d="M5 4h4l2 5-2.5 1.5a11 11 0 0 0 5 5L15 13l5 2v4a2 2 0 0 1-2 2A16 16 0 0 1 3 6a2 2 0 0 1 2-2"/>',
        'pin'      => '<path d="M12 22s7-6.2 7-12a7 7 0 0 0-14 0c0 5.8 7 12 7 12z"/><circle cx="12" cy="10" r="2.5"/>',
        'heart'    => '<path d="M20.8 4.6a5.5 5.5 0 0 0-7.8 0L12 5.7l-1-1.1a5.5 5.5 0 0 0-7.8 7.8L12 21.2l8.8-8.8a5.5 5.5 0 0 0 0-7.8z"/>',
        'shield'   => '<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><path d="M9 12l2 2 4-4"/>',
        'steth'    => '<path d="M6 3v6a6 6 0 0 0 12 0V3"/><path d="M12 15v2a4 4 0 0 0 8 0v-2"/><circle cx="20" cy="13" r="2"/>',
        'logout'   => '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4M16 17l5-5-5-5M21 12H9"/>',
        'clock'    => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 3"/>',
        'users'    => '<circle cx="9" cy="8" r="4"/><path d="M2 21a7 7 0 0 1 14 0M16 3.5a4 4 0 0 1 0 7.5M22 21a7 7 0 0 0-5-6.7"/>',
        'arrow'    => '<path d="M5 12h14M13 6l6 6-6 6"/>',
        'check'    => '<path d="M20 6L9 17l-5-5"/>',
        'mail'     => '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="M3 7l9 6 9-6"/>',
        'plus'     => '<path d="M12 5v14M5 12h14"/>',
        'bone'     => '<path d="M17 10c.7-.7 1.7-.7 2.6-.2a2 2 0 1 0 .6-3.4A2 2 0 1 0 16.8 7L7 16.8a2 2 0 1 0-3.4.6 2 2 0 1 0 .2 2.6c.5.9.5 1.9-.2 2.6"/>',
        'activity' => '<path d="M22 12h-4l-3 9L9 3l-3 9H2"/>',
        'print'    => '<path d="M6 9V3h12v6M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="7"/>',
    ];
    return '<svg class="ic" width="' . $s . '" height="' . $s . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . ($p[$n] ?? '') . '</svg>';
}

function logo(): string {
    return '<a href="' . url() . '" class="logo"><span class="logo-mark"><svg viewBox="0 0 24 24" width="18" height="18"><path fill="#fff" d="M9 3h6v6h6v6h-6v6H9v-6H3V9h6z"/></svg></span><span>MediCore<small>HOSPITALS</small></span></a>';
}

const FAVICON = "data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Crect width='24' height='24' rx='6' fill='%230e7c86'/%3E%3Cpath fill='%23fff' d='M10 5h4v5h5v4h-5v5h-4v-5H5v-4h5z'/%3E%3C/svg%3E";

function page_head(string $title): void {
    $full = $title === '' ? HOSPITAL . ' — Multi-speciality Hospital, Mumbai' : $title . ' | ' . HOSPITAL;
?><!doctype html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= h($full) ?></title>
<meta name="description" content="MediCore Hospitals — multi-speciality care, diagnostics and online lab reports.">
<link rel="icon" href="<?= FAVICON ?>">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>
:root{--navy:#0b2545;--navy2:#13315c;--teal:#0e7c86;--teal2:#0a6870;--tealbg:#e7f4f5;--bg:#f5f7fa;--card:#fff;--line:#e3e8ef;--text:#14213d;--muted:#5b6b82;--red:#c0392b;--redbg:#fdecea;--amber:#9a6700;--amberbg:#fff4d6;--green:#1e7b4f;--greenbg:#e6f5ec;--r:10px}
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:Inter,system-ui,-apple-system,"Segoe UI",sans-serif;background:var(--bg);color:var(--text);font-size:15px;line-height:1.5;min-height:100vh;display:flex;flex-direction:column}
a{color:var(--teal);text-decoration:none}a:hover{color:var(--teal2)}
.ic{flex:none;vertical-align:middle}
.container{max-width:1160px;margin:0 auto;padding:0 20px;width:100%}
.topbar{background:var(--navy);color:#c9d6e8;font-size:.78rem}
.topbar .container{display:flex;gap:22px;align-items:center;height:34px}
.topbar span{display:inline-flex;gap:6px;align-items:center}
.topbar .em{margin-left:auto;color:#fff;font-weight:600}
header.site{background:#fff;border-bottom:1px solid var(--line);position:sticky;top:0;z-index:20}
header.site .container{display:flex;align-items:center;height:68px;gap:28px}
.logo{display:flex;align-items:center;gap:10px;color:var(--navy);font-weight:800;font-size:1.15rem;letter-spacing:-.2px}
.logo:hover{color:var(--navy)}
.logo small{display:block;font-size:.58rem;letter-spacing:2.6px;color:var(--teal);font-weight:700;margin-top:-3px}
.logo-mark{width:34px;height:34px;border-radius:8px;background:var(--teal);display:grid;place-items:center}
nav.main{display:flex;gap:4px}
nav.main a{color:var(--text);font-weight:500;font-size:.9rem;padding:8px 12px;border-radius:6px}
nav.main a:hover,nav.main a.on{background:var(--tealbg);color:var(--teal2)}
.hdr-right{margin-left:auto;display:flex;gap:10px;align-items:center}
.btn{display:inline-flex;align-items:center;justify-content:center;gap:7px;font:inherit;font-weight:600;font-size:.88rem;border-radius:8px;padding:9px 18px;border:1px solid transparent;cursor:pointer;transition:background .15s,border-color .15s;white-space:nowrap}
.btn-p{background:var(--teal);color:#fff}.btn-p:hover{background:var(--teal2);color:#fff}
.btn-o{background:#fff;border-color:var(--line);color:var(--text)}.btn-o:hover{border-color:var(--teal);color:var(--teal2)}
.btn-n{background:var(--navy);color:#fff}.btn-n:hover{background:var(--navy2);color:#fff}
.btn-sm{padding:6px 12px;font-size:.8rem}.btn-lg{padding:12px 24px;font-size:.95rem}.btn-block{width:100%}
main{flex:1}
.flashes{margin:16px auto 0}
.flash{padding:11px 14px;border-radius:8px;font-size:.88rem;margin-bottom:8px;border:1px solid}
.flash-success{background:var(--greenbg);border-color:#b7e1c8;color:var(--green)}
.flash-error{background:var(--redbg);border-color:#f5c2bc;color:var(--red)}
.flash-info{background:var(--tealbg);border-color:#bfe0e3;color:var(--teal2)}
.card{background:var(--card);border:1px solid var(--line);border-radius:var(--r)}
.pad{padding:22px}
h1,h2,h3{color:var(--navy);letter-spacing:-.3px;line-height:1.25}
.muted{color:var(--muted)}.small{font-size:.83rem}
.sec{padding:56px 0}.sec-title{font-size:1.6rem;margin-bottom:6px}.sec-sub{color:var(--muted);margin-bottom:28px}
.grid{display:grid;gap:18px}.g2{grid-template-columns:repeat(2,1fr)}.g3{grid-template-columns:repeat(3,1fr)}.g4{grid-template-columns:repeat(4,1fr)}
.form-row{margin-bottom:15px}
label.l{display:block;font-size:.82rem;font-weight:600;margin-bottom:5px;color:var(--text)}
.in{width:100%;font:inherit;font-size:.92rem;padding:10px 12px;border:1px solid #cfd8e3;border-radius:8px;background:#fff;color:var(--text)}
.in:focus{outline:none;border-color:var(--teal);box-shadow:0 0 0 3px rgba(14,124,134,.15)}
.tbl{width:100%;border-collapse:collapse;font-size:.88rem}
.tbl th{text-align:left;font-size:.74rem;text-transform:uppercase;letter-spacing:.5px;color:var(--muted);font-weight:600;padding:10px 14px;background:#f8fafc;border-bottom:1px solid var(--line)}
.tbl td{padding:12px 14px;border-bottom:1px solid var(--line);vertical-align:middle}
.tbl tr:last-child td{border-bottom:0}
.tag{display:inline-block;font-size:.72rem;font-weight:600;padding:3px 9px;border-radius:20px}
.t-green{background:var(--greenbg);color:var(--green)}.t-teal{background:var(--tealbg);color:var(--teal2)}.t-amber{background:var(--amberbg);color:var(--amber)}.t-red{background:var(--redbg);color:var(--red)}.t-grey{background:#eef1f5;color:var(--muted)}
.res-H,.res-L{color:var(--red);font-weight:700}
footer.site{background:var(--navy);color:#9fb1c9;font-size:.85rem;margin-top:56px}
footer.site .cols{display:grid;grid-template-columns:2fr 1fr 1fr 1.3fr;gap:30px;padding:44px 0 30px}
footer.site h4{color:#fff;font-size:.9rem;margin-bottom:12px}
footer.site a{color:#9fb1c9;display:block;margin-bottom:7px}footer.site a:hover{color:#fff}
footer.site .logo{color:#fff}footer.site .logo:hover{color:#fff}
footer.site .base{border-top:1px solid rgba(255,255,255,.1);padding:16px 0;font-size:.78rem;display:flex;justify-content:space-between;gap:12px;flex-wrap:wrap}
/* portal */
.portal{display:grid;grid-template-columns:240px 1fr;gap:26px;padding-top:26px;align-items:start}
.side{position:sticky;top:90px}
.side .who{padding:18px;border-bottom:1px solid var(--line)}
.side .who b{display:block;color:var(--navy)}
.side a.it{display:flex;gap:10px;align-items:center;padding:11px 18px;color:var(--text);font-size:.9rem;font-weight:500;border-left:3px solid transparent}
.side a.it:hover{background:#f8fafc;color:var(--teal2)}
.side a.it.on{border-left-color:var(--teal);background:var(--tealbg);color:var(--teal2);font-weight:600}
.side form{border-top:1px solid var(--line)}
.side button.it{all:unset;display:flex;gap:10px;align-items:center;padding:11px 18px;font-size:.9rem;color:var(--muted);cursor:pointer;width:calc(100% - 36px)}
.side button.it:hover{color:var(--red)}
.ph{display:flex;align-items:flex-end;justify-content:space-between;gap:16px;margin-bottom:20px;flex-wrap:wrap}
.ph h1{font-size:1.45rem}
.stat{padding:18px 20px}.stat .v{font-size:1.5rem;font-weight:700;color:var(--navy)}.stat .k{font-size:.8rem;color:var(--muted)}
.empty{text-align:center;padding:44px 20px;color:var(--muted)}
.empty .ic{color:#b4c0cf;margin-bottom:10px}
@media(max-width:900px){nav.main{display:none}.g3,.g4{grid-template-columns:repeat(2,1fr)}footer.site .cols{grid-template-columns:1fr 1fr}.portal{grid-template-columns:1fr}.side{position:static}.topbar .hide-sm{display:none}}
@media(max-width:560px){.g2,.g3,.g4{grid-template-columns:1fr}.tbl .hide-sm{display:none}.hdr-right .btn-o{display:none}header.site .container{gap:12px}}
</style>
</head>
<body>
<?php
}

function flashes(): void {
    $fl = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    if (!$fl) return;
    echo '<div class="container flashes">';
    foreach ($fl as [$t, $m]) echo '<div class="flash flash-' . h($t) . '">' . h($m) . '</div>';
    echo '</div>';
}

function page_open(string $title = '', ?string $active = null): void {
    page_head($title);
    $u = user();
    $nav = ['' => 'Home', 'doctors' => 'Find a Doctor', 'health-packages' => 'Health Packages', 'contact' => 'Contact Us'];
?>
<div class="topbar"><div class="container">
  <span><?= icon('clock', 14) ?> OPD: Mon – Sat, 8 AM – 8 PM</span>
  <span class="hide-sm"><?= icon('mail', 14) ?> care@medicore.example</span>
  <span class="em"><?= icon('phone', 14) ?> Emergency 24×7: 1800 000 0000</span>
</div></div>
<header class="site"><div class="container">
  <?= logo() ?>
  <nav class="main">
    <?php foreach ($nav as $p => $l): ?><a href="<?= url($p) ?>" class="<?= $active === $p ? 'on' : '' ?>"><?= $l ?></a><?php endforeach; ?>
  </nav>
  <div class="hdr-right">
    <?php if ($u): ?>
      <a href="<?= url('portal') ?>" class="btn btn-p"><?= icon('user', 16) ?> My Portal</a>
    <?php else: ?>
      <a href="<?= url('register') ?>" class="btn btn-o">Register</a>
      <a href="<?= url('login') ?>" class="btn btn-p"><?= icon('user', 16) ?> Patient Login</a>
    <?php endif; ?>
  </div>
</div></header>
<main>
<?php flashes();
}

function page_close(): void { ?>
</main>
<footer class="site"><div class="container">
  <div class="cols">
    <div>
      <?= logo() ?>
      <p style="margin-top:14px;max-width:320px">A multi-speciality hospital offering outpatient care, diagnostics and same-day lab reports, available online through the MediCore patient portal.</p>
    </div>
    <div><h4>Patients</h4><a href="<?= url('login') ?>">Patient Login</a><a href="<?= url('register') ?>">New Registration</a><a href="<?= url('portal/reports') ?>">Lab Reports</a><a href="<?= url('portal/appointments') ?>">Book Appointment</a></div>
    <div><h4>Hospital</h4><a href="<?= url('doctors') ?>">Our Doctors</a><a href="<?= url('health-packages') ?>">Health Packages</a><a href="<?= url('contact') ?>">Contact Us</a><a href="<?= url('login?staff=1') ?>">Staff Login</a></div>
    <div><h4>Reach us</h4><p style="margin-bottom:8px">Main Campus, Andheri East<br>Mumbai, Maharashtra</p><p>1800 000 0000 (24×7)<br>care@medicore.example</p></div>
  </div>
  <div class="base"><span>© <?= date('Y') ?> MediCore Hospitals Pvt. Ltd. All rights reserved.</span><span>Privacy Policy · Terms of Use · Patient Rights</span></div>
</div></footer>
</body></html>
<?php }

/* Portal pages: same header/footer, with a side menu. */
function portal_open(string $title, string $active): void {
    page_open($title);
    $u = user();
    $items = $u['role'] === 'doctor'
        ? ['portal' => ['home', 'Overview'], 'portal/clinician/patients' => ['users', 'Patient Directory'], 'portal/appointments' => ['calendar', 'Appointments'], 'portal/profile' => ['user', 'Profile']]
        : ['portal' => ['home', 'Overview'], 'portal/reports' => ['file', 'Lab Reports'], 'portal/book-test' => ['flask', 'Book a Lab Test'], 'portal/appointments' => ['calendar', 'Appointments'], 'portal/profile' => ['user', 'My Profile']];
?>
<div class="container portal">
  <aside class="card side">
    <div class="who"><b><?= h($u['name']) ?></b><span class="small muted"><?= $u['role'] === 'doctor' ? h($u['department']) : 'MRN ' . mrn((int)$u['id']) ?></span></div>
    <?php foreach ($items as $p => [$ic, $l]): ?>
      <a href="<?= url($p) ?>" class="it <?= $active === $p ? 'on' : '' ?>"><?= icon($ic, 17) ?> <?= $l ?></a>
    <?php endforeach; ?>
    <form method="POST" action="<?= url('logout') ?>"><?= csrf_field() ?><button class="it"><?= icon('logout', 17) ?> Sign out</button></form>
  </aside>
  <section>
<?php
}

function portal_close(): void {
    echo "  </section>\n</div>\n";
    echo '<script src="' . BASE . '/assets/portal.js?v=2.4.1" data-base="' . h(BASE) . '"></script>' . "\n";
    page_close();
}

function status_tag(string $s): string {
    return match ($s) {
        'reviewed'   => '<span class="tag t-green">Reviewed</span>',
        'ready'      => '<span class="tag t-teal">Ready</span>',
        'processing' => '<span class="tag t-amber">Processing</span>',
        'scheduled'  => '<span class="tag t-teal">Scheduled</span>',
        'completed'  => '<span class="tag t-green">Completed</span>',
        'cancelled'  => '<span class="tag t-grey">Cancelled</span>',
        default      => '<span class="tag t-grey">' . h(ucfirst($s)) . '</span>',
    };
}

function result_table(array $detail): string {
    $o = '<table class="tbl"><thead><tr><th>Investigation</th><th>Result</th><th>Unit</th><th class="hide-sm">Reference range</th></tr></thead><tbody>';
    foreach ($detail['rows'] ?? [] as [$n, $v, $u, $r, $f]) {
        $o .= '<tr><td>' . h($n) . '</td><td class="res-' . h($f) . '">' . h($v) . ($f ? ' ' . h($f) : '') . '</td><td class="muted">' . h($u) . '</td><td class="muted hide-sm">' . h($r) . '</td></tr>';
    }
    return $o . '</tbody></table>';
}
