<?php
/*
 * MediCore Hospitals — patient portal.
 * KrazePlanet lab: Improper Access Control (maintainer notes, never rendered).
 *
 *   The clinician workspace (/portal/clinician/patients and .../view?id=N) is
 *   only hidden from patients: the menu link is rendered for doctors, but the
 *   handlers just require a signed-in user, with no role check (the bug).
 *   Any registered patient who finds the route — it is listed in robots.txt
 *   and in /assets/portal.js — gets the full patient directory and every
 *   patient's chart and lab results. Patient report pages (view and PDF) do
 *   check ownership, so this is not an IDOR lab.
 *
 * Nothing on the site mentions the lab, a flag or hints, on purpose. All seed
 * people are fictional placeholders. The database seeds itself on first use
 * and is thrown away by the nightly lab reset.
 */
const LEGACY_BASE = '/subdomains/medicore';
const DB_NAME     = 'medicore_portal';
const HOSPITAL    = 'MediCore Hospitals';

(function () {
    $p = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
    // Served at the root of medicore.<domain>; the old /subdomains/medicore/
    // path keeps working through the labs Alias.
    define('BASE', ($p === LEGACY_BASE || str_starts_with($p, LEGACY_BASE . '/')) ? LEGACY_BASE : '');
    // Static-file requests (favicon.ico, robots.txt, ...) are answered before
    // the session starts, so a parallel favicon request can't replace the
    // session cookie the login page just set (CSRF mismatch on POST).
    if (preg_match('/\.[A-Za-z0-9]{2,5}$/', $p)) {
        http_response_code(404);
        header('Content-Type: text/plain; charset=UTF-8');
        exit("404 Not Found\n");
    }
})();

session_set_cookie_params(['path' => BASE === '' ? '/' : BASE, 'httponly' => true, 'samesite' => 'Lax']);
session_name('mc_session');
session_start();

const DOCTORS = [
    // key, name, department, qualification, years, OPD hours
    ['sharma',   'Dr. R. Sharma',   'General Medicine',         'MBBS, MD (Internal Medicine)', 22, 'Mon – Sat, 10:00 – 14:00'],
    ['iyer',     'Dr. A. Iyer',     'Endocrinology',            'MBBS, MD, DM (Endocrinology)', 14, 'Mon, Wed, Fri, 11:00 – 16:00'],
    ['rao',      'Dr. V. Rao',      'Cardiology',               'MBBS, MD, DM (Cardiology)',    18, 'Tue, Thu, Sat, 09:00 – 13:00'],
    ['menon',    'Dr. K. Menon',    'Orthopaedics',             'MBBS, MS (Orthopaedics)',      12, 'Tue – Sat, 10:00 – 14:00'],
    ['khan',     'Dr. S. Khan',     'Pathology & Lab Medicine', 'MBBS, MD (Pathology)',         15, 'Mon – Sat, 08:00 – 17:00'],
];
const PATHOLOGIST = 'khan';

const TESTS = [
    // code => [name, price (INR), sample]
    'cbc'   => ['Complete Blood Count (CBC)',    450,  'EDTA whole blood'],
    'lipid' => ['Lipid Profile',                 650,  'Serum (fasting)'],
    'tft'   => ['Thyroid Profile (T3, T4, TSH)', 550,  'Serum'],
    'hba1c' => ['HbA1c (Glycated Haemoglobin)',  500,  'EDTA whole blood'],
    'lft'   => ['Liver Function Test (LFT)',     800,  'Serum'],
    'vitd'  => ['Vitamin D (25-Hydroxy)',        1200, 'Serum'],
];

const PACKAGES = [
    ['Essential Health Check',     1499, ['cbc', 'lipid', 'hba1c'],                      'Basic screening for adults under 35.'],
    ['Comprehensive Health Check', 3499, ['cbc', 'lipid', 'hba1c', 'tft', 'lft', 'vitd'], 'Full-body screening with a physician consultation.'],
    ['Thyroid & Vitamin Panel',    1599, ['tft', 'vitd'],                                 'Thyroid function and vitamin D levels.'],
    ['Senior Citizen Care',        3999, ['cbc', 'lipid', 'hba1c', 'lft', 'vitd'],       'Age-appropriate screening with a cardiology review.'],
];

/* ── DATABASE ─────────────────────────────────────────────── */
function db(): PDO {
    static $pdo = null;
    if ($pdo) return $pdo;
    $opt = [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false];
    $dsn = 'mysql:host=127.0.0.1;charset=utf8mb4';
    try {
        $pdo = new PDO($dsn . ';dbname=' . DB_NAME, 'root', '', $opt);
    } catch (PDOException $e) {
        if ((int)$e->getCode() !== 1049) throw $e;
        $r = new PDO($dsn, 'root', '', $opt);
        $r->exec('CREATE DATABASE IF NOT EXISTS `' . DB_NAME . '` CHARACTER SET utf8mb4');
        $pdo = new PDO($dsn . ';dbname=' . DB_NAME, 'root', '', $opt);
        install_schema($pdo);
    }
    return $pdo;
}

function install_schema(PDO $pdo): void {
    // Two first requests can race; the lock keeps the seed single.
    $pdo->query("SELECT GET_LOCK('medicore_seed', 20)")->fetchAll();
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            email VARCHAR(190) NOT NULL UNIQUE,
            password_hash VARCHAR(255) NOT NULL,
            role ENUM('patient','doctor') NOT NULL DEFAULT 'patient',
            department VARCHAR(80) NULL,
            gender ENUM('M','F','O') NULL,
            dob DATE NULL,
            blood_group VARCHAR(5) NULL,
            allergies VARCHAR(255) NOT NULL DEFAULT 'None known',
            phone VARCHAR(20) NOT NULL DEFAULT '',
            city VARCHAR(80) NOT NULL DEFAULT '',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP) AUTO_INCREMENT=100231");

        $pdo->exec("CREATE TABLE IF NOT EXISTS reports (
            id INT AUTO_INCREMENT PRIMARY KEY,
            patient_id INT NOT NULL,
            ordered_by INT NOT NULL,
            test_code VARCHAR(20) NOT NULL,
            test_type VARCHAR(120) NOT NULL,
            sample_type VARCHAR(60) NOT NULL,
            collected_at DATETIME NOT NULL,
            reported_at DATETIME NOT NULL,
            result_summary VARCHAR(500) NOT NULL,
            result_detail TEXT NOT NULL,
            status ENUM('processing','ready','reviewed') NOT NULL DEFAULT 'ready',
            INDEX(patient_id),
            FOREIGN KEY(patient_id) REFERENCES users(id),
            FOREIGN KEY(ordered_by) REFERENCES users(id)) AUTO_INCREMENT=48211");

        $pdo->exec("CREATE TABLE IF NOT EXISTS appointments (
            id INT AUTO_INCREMENT PRIMARY KEY,
            patient_id INT NOT NULL,
            doctor_id INT NOT NULL,
            appointment_date DATE NOT NULL,
            appointment_time VARCHAR(10) NOT NULL,
            reason VARCHAR(255) NOT NULL,
            status ENUM('scheduled','completed','cancelled') NOT NULL DEFAULT 'scheduled',
            INDEX(patient_id),
            FOREIGN KEY(patient_id) REFERENCES users(id),
            FOREIGN KEY(doctor_id) REFERENCES users(id)) AUTO_INCREMENT=7301");

        if ((int)$pdo->query('SELECT COUNT(*) FROM users')->fetchColumn() === 0) seed($pdo);
    } finally {
        $pdo->query("SELECT RELEASE_LOCK('medicore_seed')")->fetchAll();
    }
}

function seed(PDO $pdo): void {
    // Placeholder accounts nobody signs in to: random passwords.
    $pw  = fn() => password_hash(bin2hex(random_bytes(12)), PASSWORD_DEFAULT);
    $ins = $pdo->prepare('INSERT INTO users(name,email,password_hash,role,department,gender,dob,blood_group,phone,city) VALUES(?,?,?,?,?,?,?,?,?,?)');

    $doc = [];
    foreach (DOCTORS as [$key, $name, $dept]) {
        $ins->execute([$name, $key . '@staff.example.com', $pw(), 'doctor', $dept, null, null, null, '', '']);
        $doc[$key] = (int)$pdo->lastInsertId();
    }

    // Fictional placeholder patients.
    $patients = [
        ['Patient A. Kumar',  'F', '1990-01-01', 'B+'],
        ['Patient B. Singh',  'M', '1985-01-01', 'O+'],
        ['Patient C. Das',    'F', '1992-01-01', 'A+'],
        ['Patient D. Verma',  'M', '1978-01-01', 'AB+'],
        ['Patient E. Joshi',  'F', '1995-01-01', 'O-'],
        ['Patient F. Gupta',  'M', '1969-01-01', 'A-'],
        ['Patient G. Reddy',  'F', '1988-01-01', 'B-'],
        ['Patient H. Bose',   'M', '1999-01-01', 'O+'],
    ];
    $pid = [];
    foreach ($patients as $i => [$name, $g, $dob, $bg]) {
        $n = $i + 1;
        $ins->execute([$name, "patient$n@example.com", $pw(), 'patient', null, $g, $dob, $bg,
                       sprintf('+91 90000 %05d', $n), 'Mumbai']);
        $pid[] = (int)$pdo->lastInsertId();
    }

    // [patient index, test, ordering doctor, status] in date order.
    $plan = [
        [5, 'lipid', 'rao', 'reviewed'],    [0, 'cbc', 'sharma', 'reviewed'],
        [3, 'hba1c', 'iyer', 'reviewed'],   [1, 'lipid', 'sharma', 'reviewed'],
        [2, 'tft', 'iyer', 'reviewed'],     [4, 'cbc', 'menon', 'reviewed'],
        [5, 'hba1c', 'iyer', 'reviewed'],   [6, 'vitd', 'menon', 'reviewed'],
        [0, 'tft', 'iyer', 'reviewed'],     [3, 'lft', 'sharma', 'reviewed'],
        [1, 'hba1c', 'iyer', 'ready'],      [7, 'cbc', 'sharma', 'ready'],
        [2, 'vitd', 'iyer', 'ready'],       [5, 'lft', 'rao', 'ready'],
        [6, 'tft', 'iyer', 'ready'],        [4, 'lipid', 'rao', 'ready'],
        [0, 'lipid', 'sharma', 'ready'],    [7, 'vitd', 'menon', 'ready'],
        [3, 'cbc', 'sharma', 'ready'],
    ];
    mt_srand(48211);
    $t = strtotime('2026-08-04 08:40');
    foreach ($plan as [$pi, $code, $by, $status]) {
        $t += mt_rand(2, 5) * 86400 + mt_rand(-90, 90) * 60;
        insert_report($pdo, $pid[$pi], $doc[$by], $code, $t, $status);
    }
    mt_srand();

    $ai = $pdo->prepare('INSERT INTO appointments(patient_id,doctor_id,appointment_date,appointment_time,reason,status) VALUES(?,?,?,?,?,?)');
    foreach ([
        [0, 'sharma', '2026-09-02', '10:30 AM', 'Follow-up consultation', 'completed'],
        [1, 'iyer',   '2026-10-28', '11:30 AM', 'Report review',          'completed'],
        [5, 'rao',    '2026-11-20', '09:30 AM', 'Routine review',         'scheduled'],
        [0, 'iyer',   '2026-11-24', '11:00 AM', 'Follow-up consultation', 'scheduled'],
        [3, 'sharma', '2026-11-26', '10:00 AM', 'Report review',          'scheduled'],
    ] as [$pi, $by, $d, $tm, $why, $st]) {
        $ai->execute([$pid[$pi], $doc[$by], $d, $tm, $why, $st]);
    }
}

/* ── LAB RESULTS ──────────────────────────────────────────── */
function rnd(float $a, float $b): float { return $a + mt_rand() / mt_getrandmax() * ($b - $a); }

// One row: [parameter, value, unit, reference range, flag H/L/'']
function lr(string $n, float $v, string $u, float $lo, float $hi, int $dp = 1, ?string $range = null): array {
    $f = fn($x) => number_format($x, $dp, '.', '');
    return [$n, $f($v), $u, $range ?? ($f($lo) . ' – ' . $f($hi)), $v < $lo ? 'L' : ($v > $hi ? 'H' : '')];
}

/** Routine panels with values drawn around the reference range. */
function lab_rows(string $code): array {
    return match ($code) {
        'cbc' => [
            lr('Haemoglobin', rnd(11.0, 16.2), 'g/dL', 12.0, 16.5),
            lr('Total RBC count', rnd(3.9, 5.6), 'million/µL', 4.0, 5.5, 2),
            lr('Haematocrit (PCV)', rnd(34, 49), '%', 36, 48),
            lr('MCV', rnd(78, 98), 'fL', 80, 96),
            lr('Total WBC count', rnd(4200, 11500), '/µL', 4000, 11000, 0),
            lr('Platelet count', rnd(1.4, 4.1), 'lakh/µL', 1.5, 4.0, 2),
        ],
        'lipid' => (function () {
            $tg = rnd(80, 230);
            return [
                lr('Total cholesterol', rnd(150, 245), 'mg/dL', 0, 200, 0, '< 200'),
                lr('LDL cholesterol', rnd(70, 170), 'mg/dL', 0, 130, 0, '< 130'),
                lr('HDL cholesterol', rnd(34, 66), 'mg/dL', 40, 999, 0, '> 40'),
                lr('Triglycerides', $tg, 'mg/dL', 0, 150, 0, '< 150'),
                lr('VLDL cholesterol', $tg / 5, 'mg/dL', 0, 30, 0, '< 30'),
            ];
        })(),
        'tft' => [
            lr('Total T3', rnd(0.8, 2.0), 'ng/mL', 0.8, 2.0, 2),
            lr('Total T4', rnd(5.0, 13.0), 'µg/dL', 5.1, 14.1),
            lr('TSH (ultrasensitive)', rnd(0.6, 6.5), 'µIU/mL', 0.27, 4.2, 2),
        ],
        'hba1c' => (function () {
            $a = rnd(4.9, 7.4);
            return [
                lr('HbA1c', $a, '%', 0, 5.6, 1, '< 5.7'),
                lr('Estimated average glucose', 28.7 * $a - 46.7, 'mg/dL', 0, 999, 0, '—'),
            ];
        })(),
        'lft' => [
            lr('Bilirubin, total', rnd(0.3, 1.4), 'mg/dL', 0.2, 1.2, 2),
            lr('SGOT (AST)', rnd(15, 52), 'U/L', 0, 40, 0, '< 40'),
            lr('SGPT (ALT)', rnd(12, 60), 'U/L', 0, 41, 0, '< 41'),
            lr('Alkaline phosphatase', rnd(48, 140), 'U/L', 44, 147, 0),
            lr('Albumin', rnd(3.4, 5.0), 'g/dL', 3.5, 5.2),
        ],
        'vitd' => [lr('25-Hydroxy vitamin D', rnd(12, 46), 'ng/mL', 30, 100)],
        default => [],
    };
}

function insert_report(PDO $pdo, int $patient, int $doctor, string $code, int $collected, string $status): int {
    [$name, , $sample] = TESTS[$code];
    $rows = lab_rows($code);
    $out  = array_values(array_filter($rows, fn($r) => $r[4] !== ''));
    $summary = $out
        ? 'Outside reference range: ' . implode(', ', array_map(fn($r) => $r[0] . ($r[4] === 'H' ? ' (high)' : ' (low)'), $out)) . '.'
        : 'All parameters within reference range.';
    $remark = $out ? 'Kindly correlate clinically. Repeat testing may be advised by your doctor.' : 'No abnormality detected.';
    $pdo->prepare('INSERT INTO reports(patient_id,ordered_by,test_code,test_type,sample_type,collected_at,reported_at,result_summary,result_detail,status) VALUES(?,?,?,?,?,?,?,?,?,?)')
        ->execute([$patient, $doctor, $code, $name, $sample, date('Y-m-d H:i:s', $collected),
                   date('Y-m-d H:i:s', $collected + mt_rand(4, 9) * 3600), $summary,
                   json_encode(['rows' => $rows, 'remark' => $remark], JSON_UNESCAPED_UNICODE), $status]);
    return (int)$pdo->lastInsertId();
}

/* ── HELPERS ──────────────────────────────────────────────── */
function h($s): string { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function url(string $p = ''): string { return BASE . '/' . ltrim($p, '/'); }
function flash(string $t, string $m): void { $_SESSION['flash'][] = [$t, $m]; }
function redirect(string $p): never { header('Location: ' . url($p)); exit; }
function mrn(int $id): string { return 'MC' . str_pad((string)$id, 8, '0', STR_PAD_LEFT); }
function age(?string $dob): string { return $dob ? (string)(new DateTime($dob))->diff(new DateTime())->y : '—'; }
function sex(?string $g): string { return ['M' => 'Male', 'F' => 'Female', 'O' => 'Other'][$g] ?? '—'; }

function user(): ?array {
    static $u = false;
    if ($u !== false) return $u;
    $u = null;
    if (!empty($_SESSION['uid'])) {
        $st = db()->prepare('SELECT * FROM users WHERE id=?');
        $st->execute([$_SESSION['uid']]);
        $u = $st->fetch() ?: null;
    }
    return $u;
}
function require_login(): array {
    $u = user();
    if (!$u) { $_SESSION['after_login'] = $_SERVER['REQUEST_URI'] ?? ''; flash('info', 'Please sign in to continue.'); redirect('login'); }
    return $u;
}
function is_doctor(): bool { $u = user(); return $u && $u['role'] === 'doctor'; }
function csrf_token(): string { if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(16)); return $_SESSION['csrf']; }
function csrf_field(): string { return '<input type="hidden" name="_csrf" value="' . h(csrf_token()) . '">'; }
function verify_csrf(): void {
    if (!hash_equals($_SESSION['csrf'] ?? '', $_POST['_csrf'] ?? '')) {
        http_response_code(400);
        flash('error', 'Your session expired. Please try again.');
        redirect(ltrim(substr(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), strlen(BASE)), '/'));
    }
}

db();
