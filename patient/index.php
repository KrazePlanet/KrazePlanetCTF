<?php
// Lab 703 — IDOR: MediCare+ Healthcare Portal — Patient Appointments & Records Disclosure
// Platform: "MediCare+" — fictional healthcare patient portal / telemedicine platform
// Vulnerability:
//   1) GET /index.php?action=appointment&id=X — No ownership check. Any patient can view ANY appointment.
//   2) GET /index.php?action=lab_result&id=X — No ownership check. Any patient can view ANY lab result.
//   3) GET /index.php?action=prescription&id=X — No ownership check. Any patient can view ANY prescription.
// Real World: Common healthcare portal IDOR — patient record enumeration via sequential IDs
// Difficulty: Easy (Training) | Pure black-box — no hints in UI

session_start();

define('LAB_FLAG', 'flag{idor_healthcare_appointment_disclosure_703}');

// ── Database ──────────────────────────────────────────────────────────────────
$db = new mysqli('localhost', 'root', '', 'VexiumCTF_DB');
if ($db->connect_error) { die('DB connection failed'); }

// ── Tables ────────────────────────────────────────────────────────────────────
$db->query("CREATE TABLE IF NOT EXISTS lab703_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    dob DATE,
    phone VARCHAR(20),
    insurance_id VARCHAR(30),
    blood_group VARCHAR(5),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)") or die($db->error);

$db->query("CREATE TABLE IF NOT EXISTS lab703_appointments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT NOT NULL,
    doctor_name VARCHAR(100) NOT NULL,
    department VARCHAR(80) NOT NULL,
    appointment_date DATE NOT NULL,
    time_slot VARCHAR(20) NOT NULL,
    status ENUM('Scheduled','Completed','Cancelled','No-Show') DEFAULT 'Scheduled',
    symptoms TEXT,
    doctor_notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES lab703_users(id)
)") or die($db->error);

$db->query("CREATE TABLE IF NOT EXISTS lab703_lab_results (
    id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT NOT NULL,
    test_name VARCHAR(100) NOT NULL,
    test_date DATE NOT NULL,
    result_value VARCHAR(80),
    reference_range VARCHAR(80),
    status ENUM('Pending','Completed','Reviewed') DEFAULT 'Pending',
    notes TEXT,
    ordered_by VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES lab703_users(id)
)") or die($db->error);

$db->query("CREATE TABLE IF NOT EXISTS lab703_prescriptions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT NOT NULL,
    medication_name VARCHAR(100) NOT NULL,
    dosage VARCHAR(50) NOT NULL,
    frequency VARCHAR(80) NOT NULL,
    prescribed_by VARCHAR(100) NOT NULL,
    prescribed_date DATE NOT NULL,
    refills INT DEFAULT 0,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES lab703_users(id)
)") or die($db->error);

// ── Seed data (idempotent) ────────────────────────────────────────────────────
function seed703($db) {
    $check = $db->query("SELECT COUNT(*) AS c FROM lab703_users");
    if ($check && $check->fetch_assoc()['c'] > 0) return;

    $p1 = password_hash('sarah123', PASSWORD_BCRYPT);
    $p2 = password_hash('mike123', PASSWORD_BCRYPT);
    $p3 = password_hash('robert123', PASSWORD_BCRYPT);

    // ── Users ────────────────────────────────────────────────────────────────
    $db->query("INSERT INTO lab703_users (id, name, email, password, dob, phone, insurance_id, blood_group) VALUES
        (1, 'Sarah Johnson',  'sarah@medicare.com',  '$p1', '1990-04-12', '(415) 555-0101', 'INS-MC-88421', 'O+'),
        (2, 'Mike Thompson',  'mike@medicare.com',   '$p2', '1985-11-08', '(510) 555-0202', 'INS-MC-77213', 'A+'),
        (3, 'Robert Wilson',  'robert@medicare.com', '$p3', '1972-09-23', '(212) 555-0399', 'INS-MC-99104', 'B-')
    ") or die($db->error);

    // ── Appointments ──────────────────────────────────────────────────────────
    $db->query("INSERT INTO lab703_appointments (id, patient_id, doctor_name, department, appointment_date, time_slot, status, symptoms, doctor_notes) VALUES
        (1, 1, 'Dr. Emily Carter',  'General Medicine', '2026-05-12', '09:30 AM', 'Completed',
         'Persistent headache, dizziness for past 3 days',
         'Patient reports stress-related tension headaches. Prescribed rest and ibuprofen. Follow up if symptoms persist.'),
        (2, 1, 'Dr. James Park',    'Cardiology',       '2026-05-20', '02:00 PM', 'Completed',
         'Chest discomfort, palpitations after exercise',
         'EKG normal. Stress test scheduled. Advised moderate exercise and reduced caffeine intake.'),
        (3, 1, 'Dr. Lisa Nguyen',   'Dermatology',      '2026-05-28', '11:00 AM', 'Scheduled',
         'Rash on forearm, itching for 1 week',
         NULL),
        (4, 2, 'Dr. Emily Carter',  'General Medicine', '2026-05-10', '10:00 AM', 'Completed',
         'Sore throat, cough, low-grade fever',
         'Streptococcal pharyngitis diagnosed. Prescribed amoxicillin 500mg for 10 days.'),
        (5, 3, 'Dr. Priya Patel',   'Neurology',        '2026-06-01', '08:30 AM', 'Completed',
         'Memory lapses, difficulty concentrating, recurring migraines',
         'flag{idor_healthcare_appointment_disclosure_703} Patient shows early indicators requiring further neurological evaluation. MRI and cognitive assessment recommended. Confidential — Attorney has been notified of findings.')
    ") or die($db->error);

    // ── Lab Results ───────────────────────────────────────────────────────────
    $db->query("INSERT INTO lab703_lab_results (id, patient_id, test_name, test_date, result_value, reference_range, status, notes, ordered_by) VALUES
        (1, 1, 'Complete Blood Count (CBC)', '2026-05-13', '5.2M/µL', '4.5-6.0M/µL', 'Completed', 'Within normal range', 'Dr. Emily Carter'),
        (2, 1, 'Lipid Panel', '2026-05-13', 'HDL: 55, LDL: 98', 'HDL: >40, LDL: <130', 'Reviewed', 'Borderline LDL — dietary changes recommended', 'Dr. Emily Carter'),
        (3, 1, 'Vitamin D, 25-Hydroxy', '2026-05-13', '22 ng/mL', '30-100 ng/mL', 'Completed', 'Below optimal — supplement 2000IU daily', 'Dr. Emily Carter'),
        (4, 2, 'Comprehensive Metabolic Panel', '2026-05-11', 'Glucose: 92, BUN: 14', 'Glucose: 70-100, BUN: 7-20', 'Completed', 'All values within normal range', 'Dr. Emily Carter'),
        (5, 2, 'Thyroid Panel (TSH)', '2026-05-11', '3.8 µIU/mL', '0.4-4.0 µIU/mL', 'Reviewed', 'High-normal — retest in 3 months', 'Dr. Emily Carter'),
        (6, 3, 'MRI Brain Scan Report', '2026-06-02', 'See attached report', 'N/A', 'Reviewed', 'Preliminary findings show minor abnormalities requiring further investigation. Urgent follow-up scheduled.', 'Dr. Priya Patel'),
        (7, 3, 'Genetic Screening Panel', '2026-05-15', 'Confidential — on file', 'Confidential', 'Reviewed', 'Genetic markers detected. Patient notified. Results under attorney review.', 'Dr. Priya Patel')
    ") or die($db->error);

    // ── Prescriptions ─────────────────────────────────────────────────────────
    $db->query("INSERT INTO lab703_prescriptions (id, patient_id, medication_name, dosage, frequency, prescribed_by, prescribed_date, refills, notes) VALUES
        (1, 1, 'Ibuprofen', '400mg', 'Every 6 hours as needed for headache', 'Dr. Emily Carter', '2026-05-12', 2, 'Take with food. Max 3 days.'),
        (2, 1, 'Vitamin D3 Supplement', '2000 IU', 'Once daily', 'Dr. Emily Carter', '2026-05-13', 5, 'Take with a meal containing fat.'),
        (3, 2, 'Amoxicillin', '500mg', 'Three times daily for 10 days', 'Dr. Emily Carter', '2026-05-10', 0, 'Complete full course even if symptoms improve.'),
        (4, 2, 'Ibuprofen', '200mg', 'Every 8 hours as needed', 'Dr. Emily Carter', '2026-05-10', 1, 'For fever and discomfort.'),
        (5, 3, 'Sumatriptan', '50mg', 'At onset of migraine, max 2 per day', 'Dr. Priya Patel', '2026-05-20', 3, 'Do not exceed 200mg in 24 hours.'),
        (6, 3, 'Topiramate', '25mg', 'Once daily, increase to twice daily after 1 week', 'Dr. Priya Patel', '2026-05-20', 2, 'May cause dizziness. Avoid driving until tolerance develops.')
    ") or die($db->error);
}
seed703($db);

function esc($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

// ── Action routing ────────────────────────────────────────────────────────────
$action     = $_GET['action'] ?? '';
$isLogout   = isset($_GET['logout']);
$isRegister = ($action === 'register');
$isDash     = ($action === 'dashboard' || !$action);
$isAppts    = ($action === 'appointments');
$isAppt     = ($action === 'appointment');
$isResults  = ($action === 'lab_results');
$isResult   = ($action === 'lab_result');
$isScripts  = ($action === 'prescriptions');
$isScript   = ($action === 'prescription');
$isProfile  = ($action === 'profile');
$error      = '';

// ── Logout ────────────────────────────────────────────────────────────────────
if ($isLogout) {
    session_destroy();
    header('Location: /index.php');
    exit;
}

// ── POST: Register ────────────────────────────────────────────────────────────
if ($isRegister && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $name  = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $pass  = $_POST['password'] ?? '';
    $dob   = $_POST['dob'] ?? '';
    $phone = trim($_POST['phone'] ?? '');
    $ins   = trim($_POST['insurance'] ?? '');
    $bg    = trim($_POST['blood_group'] ?? '');
    if ($name && $email && strlen($pass) >= 4) {
        $h = password_hash($pass, PASSWORD_BCRYPT);
        $st = $db->prepare('INSERT INTO lab703_users (name, email, password, dob, phone, insurance_id, blood_group) VALUES (?,?,?,?,?,?,?)');
        $st->bind_param('sssssss', $name, $email, $h, $dob, $phone, $ins, $bg);
        $st->execute();
        $newUserId = $db->insert_id;
        $st->close();

        $db->query("INSERT INTO lab703_appointments (patient_id, doctor_name, department, appointment_date, time_slot, status, symptoms, doctor_notes) VALUES
            ($newUserId, 'Dr. Emily Carter', 'General Medicine', CURDATE(), '10:00 AM', 'Scheduled', 'New patient intake consultation', NULL)
        ") or die($db->error);

        $db->query("INSERT INTO lab703_lab_results (patient_id, test_name, test_date, result_value, reference_range, status, notes, ordered_by) VALUES
            ($newUserId, 'Complete Blood Count (CBC)', CURDATE(), 'Pending', 'N/A', 'Pending', 'Awaiting results from lab', 'Dr. Emily Carter')
        ") or die($db->error);
    }
    header('Location: /index.php');
    exit;
}

// ── POST: Login ───────────────────────────────────────────────────────────────
if (!$isRegister && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $pass  = $_POST['password'] ?? '';
    if ($email && $pass) {
        $st = $db->prepare('SELECT * FROM lab703_users WHERE email = ?');
        $st->bind_param('s', $email);
        $st->execute();
        $res = $st->get_result();
        $user = $res->fetch_assoc();
        $st->close();
        if ($user && password_verify($pass, $user['password'])) {
            $_SESSION['lab703_user'] = $user['id'];
            header('Location: /index.php?action=dashboard');
            exit;
        } else {
            $error = 'Invalid email or password.';
        }
    }
}

// ── Current user ──────────────────────────────────────────────────────────────
$currentUser = null;
if (!empty($_SESSION['lab703_user'])) {
    $st = $db->prepare('SELECT * FROM lab703_users WHERE id = ?');
    $st->bind_param('i', $_SESSION['lab703_user']);
    $st->execute();
    $res = $st->get_result();
    $currentUser = $res->fetch_assoc();
    $st->close();
}

// Redirect logged-in users from login page
if ($currentUser && !$action && !$isLogout) {
    header('Location: /index.php?action=dashboard');
    exit;
}

// ═══════════════════════════════════════════════════════════════════════════════
//  VULNERABLE: No ownership checks on ANY detail endpoint.
//  Any authenticated user can view ANY appointment, lab result, or prescription
//  by guessing or enumerating the sequential numeric ID.
// ═══════════════════════════════════════════════════════════════════════════════

// ── VULNERABLE: Appointment detail ────────────────────────────────────────────
$apptDetail = null;
if ($isAppt && isset($_GET['id'])) {
    $apptId = (int)$_GET['id'];
    $st = $db->prepare('SELECT a.*, u.name AS patient_name, u.dob, u.blood_group
                        FROM lab703_appointments a
                        JOIN lab703_users u ON a.patient_id = u.id
                        WHERE a.id = ?');
    $st->bind_param('i', $apptId);
    $st->execute();
    $res = $st->get_result();
    $apptDetail = $res->fetch_assoc();
    $st->close();
}

// ── VULNERABLE: Lab result detail ─────────────────────────────────────────────
$resultDetail = null;
if ($isResult && isset($_GET['id'])) {
    $resultId = (int)$_GET['id'];
    $st = $db->prepare('SELECT r.*, u.name AS patient_name FROM lab703_lab_results r JOIN lab703_users u ON r.patient_id = u.id WHERE r.id = ?');
    $st->bind_param('i', $resultId);
    $st->execute();
    $res = $st->get_result();
    $resultDetail = $res->fetch_assoc();
    $st->close();
}

// ── VULNERABLE: Prescription detail ───────────────────────────────────────────
$scriptDetail = null;
if ($isScript && isset($_GET['id'])) {
    $scriptId = (int)$_GET['id'];
    $st = $db->prepare('SELECT p.*, u.name AS patient_name FROM lab703_prescriptions p JOIN lab703_users u ON p.patient_id = u.id WHERE p.id = ?');
    $st->bind_param('i', $scriptId);
    $st->execute();
    $res = $st->get_result();
    $scriptDetail = $res->fetch_assoc();
    $st->close();
}

// ── Fetch logged-in user's lists ──────────────────────────────────────────────
$myAppts = [];
$myResults = [];
$myScripts = [];
if ($currentUser) {
    $st = $db->prepare('SELECT * FROM lab703_appointments WHERE patient_id = ? ORDER BY appointment_date DESC, id DESC');
    $st->bind_param('i', $currentUser['id']);
    $st->execute();
    $res = $st->get_result();
    while ($row = $res->fetch_assoc()) $myAppts[] = $row;
    $st->close();

    $st = $db->prepare('SELECT * FROM lab703_lab_results WHERE patient_id = ? ORDER BY test_date DESC, id DESC');
    $st->bind_param('i', $currentUser['id']);
    $st->execute();
    $res = $st->get_result();
    while ($row = $res->fetch_assoc()) $myResults[] = $row;
    $st->close();

    $st = $db->prepare('SELECT * FROM lab703_prescriptions WHERE patient_id = ? ORDER BY prescribed_date DESC, id DESC');
    $st->bind_param('i', $currentUser['id']);
    $st->execute();
    $res = $st->get_result();
    while ($row = $res->fetch_assoc()) $myScripts[] = $row;
    $st->close();
}

// ── Dashboard stats ───────────────────────────────────────────────────────────
$upcomingAppts = 0;
$pendingResults = 0;
$activeScripts = 0;
if ($currentUser) {
    $r = $db->query("SELECT COUNT(*) AS c FROM lab703_appointments WHERE patient_id = {$currentUser['id']} AND status = 'Scheduled'");
    if ($r) $upcomingAppts = $r->fetch_assoc()['c'];
    $r = $db->query("SELECT COUNT(*) AS c FROM lab703_lab_results WHERE patient_id = {$currentUser['id']} AND status IN ('Pending','Reviewed')");
    if ($r) $pendingResults = $r->fetch_assoc()['c'];
    $r = $db->query("SELECT COUNT(*) AS c FROM lab703_prescriptions WHERE patient_id = {$currentUser['id']}");
    if ($r) $activeScripts = $r->fetch_assoc()['c'];
}
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>MediCare+ — Patient Portal</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:'Inter',sans-serif;background:#F0F4F8;color:#1E293B;min-height:100vh}
a{color:#2563EB;text-decoration:none}
a:hover{text-decoration:underline}

/* ── Login / Register ─────────────────────────────────────────── */
.auth-wrap{display:flex;align-items:center;justify-content:center;min-height:100vh;background:linear-gradient(135deg,#0F172A 0%,#1E3A5F 100%);padding:24px}
.auth-card{width:420px;background:#fff;border-radius:20px;padding:40px;box-shadow:0 20px 60px rgba(0,0,0,.3)}
.auth-logo{text-align:center;margin-bottom:28px}
.auth-logo svg{width:48px;height:48px;fill:#2563EB;margin-bottom:10px}
.auth-title{font-size:1.4rem;font-weight:800;color:#0F172A;margin-bottom:4px}
.auth-sub{font-size:.85rem;color:#64748B}
.auth-input{width:100%;padding:12px 14px;border:1px solid #D1D5DB;border-radius:10px;font-size:.9rem;margin-bottom:14px;color:#1E293B;background:#F9FAFB;transition:.15s}
.auth-input:focus{outline:none;border-color:#2563EB;box-shadow:0 0 0 3px rgba(37,99,235,.12);background:#fff}
.auth-btn{width:100%;padding:12px;background:#2563EB;color:#fff;border:none;border-radius:10px;font-weight:700;font-size:.95rem;cursor:pointer;transition:.15s}
.auth-btn:hover{background:#1D4ED8}
.auth-switch{text-align:center;margin-top:18px;font-size:.85rem;color:#64748B}
.error-msg{background:#FEF2F2;color:#B91C1C;border:1px solid #FECACA;border-radius:10px;padding:10px 14px;font-size:.85rem;margin-bottom:16px}
.demo-box{margin-top:20px;padding:16px;background:#F8FAFC;border:1px solid #E2E8F0;border-radius:10px;font-size:.78rem;color:#64748B}
.demo-box strong{color:#475569}

/* ── Layout ──────────────────────────────────────────────────── */
.app{display:flex;min-height:100vh}
.sidebar{width:250px;background:#0F172A;padding:24px 0;flex-shrink:0;display:flex;flex-direction:column}
.sidebar-brand{padding:0 20px 24px 20px;font-weight:800;font-size:1.1rem;color:#fff;display:flex;align-items:center;gap:10px;border-bottom:1px solid #1E293B}
.sidebar-brand svg{width:24px;height:24px;fill:#3B82F6}
.sidebar-brand span{color:#3B82F6}
.sidebar-nav{padding:12px 0;flex:1}
.sidebar-nav a{display:flex;align-items:center;gap:12px;padding:12px 20px;color:#94A3B8;font-size:.88rem;font-weight:500;transition:.15s;border-left:3px solid transparent}
.sidebar-nav a:hover{color:#E2E8F0;background:rgba(255,255,255,.04);text-decoration:none}
.sidebar-nav a.active{color:#fff;background:rgba(59,130,246,.12);border-left-color:#3B82F6}
.sidebar-nav a svg{width:20px;height:20px;flex-shrink:0;fill:currentColor}
.sidebar-footer{padding:20px;border-top:1px solid #1E293B;font-size:.72rem;color:#475569}
.main{flex:1;padding:28px 36px;overflow:auto}
.topbar{display:flex;justify-content:space-between;align-items:center;margin-bottom:28px}
.topbar h1{font-size:1.35rem;font-weight:700;color:#0F172A}
.user-pill{background:#fff;border:1px solid #E2E8F0;padding:8px 18px;border-radius:20px;font-size:.82rem;font-weight:500;color:#475569;display:flex;align-items:center;gap:8px;box-shadow:0 1px 3px rgba(0,0,0,.04)}
.user-pill .dot{width:8px;height:8px;border-radius:50%;background:#22C55E}

/* ── Cards ────────────────────────────────────────────────────── */
.card{background:#fff;border:1px solid #E2E8F0;border-radius:14px;padding:24px;margin-bottom:20px;box-shadow:0 1px 4px rgba(0,0,0,.04)}
.card-title{font-size:.95rem;font-weight:700;color:#0F172A;margin-bottom:16px;display:flex;align-items:center;gap:8px}
.stats-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:16px;margin-bottom:24px}
.stat-card{background:#fff;border:1px solid #E2E8F0;border-radius:14px;padding:22px;box-shadow:0 1px 4px rgba(0,0,0,.04)}
.stat-icon{width:40px;height:40px;border-radius:10px;display:flex;align-items:center;justify-content:center;margin-bottom:12px}
.stat-icon svg{width:22px;height:22px;fill:#fff}
.stat-label{font-size:.75rem;color:#64748B;text-transform:uppercase;letter-spacing:.4px;font-weight:600;margin-bottom:4px}
.stat-value{font-size:1.7rem;font-weight:800;color:#0F172A}
.stat-sub{font-size:.8rem;color:#64748B;margin-top:4px}

/* ── Tables ───────────────────────────────────────────────────── */
table{width:100%;border-collapse:collapse;font-size:.88rem}
th{text-align:left;padding:12px 14px;font-size:.72rem;font-weight:600;color:#64748B;text-transform:uppercase;letter-spacing:.4px;border-bottom:2px solid #E2E8F0;background:#F8FAFC}
td{padding:14px;border-bottom:1px solid #F1F5F9;vertical-align:middle}
tr:last-child td{border-bottom:none}
tr:hover td{background:#F8FAFC}
.badge{display:inline-block;padding:3px 10px;border-radius:20px;font-size:.7rem;font-weight:600}
.badge-scheduled{background:#EFF6FF;color:#1D4ED8}
.badge-completed{background:#ECFDF5;color:#047857}
.badge-cancelled{background:#FEF2F2;color:#B91C1C}
.badge-pending{background:#FEF3C7;color:#B45309}
.badge-reviewed{background:#F0FDFA;color:#0F766E}
.btn{display:inline-flex;align-items:center;gap:6px;padding:7px 14px;border-radius:8px;font-size:.8rem;font-weight:600;border:none;cursor:pointer;transition:.15s;text-decoration:none}
.btn-primary{background:#2563EB;color:#fff}
.btn-primary:hover{background:#1D4ED8;text-decoration:none}
.btn-ghost{background:transparent;color:#64748B;border:1px solid #E2E8F0}
.btn-ghost:hover{color:#1E293B;border-color:#CBD5E1;text-decoration:none}
.empty-state{text-align:center;padding:40px 20px;color:#94A3B8}

/* ── Detail views ─────────────────────────────────────────────── */
.detail-hdr{display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:12px;margin-bottom:20px}
.detail-hdr-left h2{font-size:1.2rem;font-weight:700;color:#0F172A;margin-bottom:4px}
.detail-hdr-left p{font-size:.85rem;color:#64748B}
.detail-grid{display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:20px}
@media(max-width:700px){.detail-grid{grid-template-columns:1fr}}
.detail-field{background:#F8FAFC;border:1px solid #E2E8F0;border-radius:10px;padding:16px}
.detail-field.full{grid-column:1/-1}
.detail-label{font-size:.7rem;font-weight:700;color:#64748B;text-transform:uppercase;letter-spacing:.4px;margin-bottom:6px}
.detail-value{font-size:.92rem;color:#1E293B;line-height:1.5;white-space:pre-line}
.detail-value strong{color:#0F172A}

/* ── Flag banner ──────────────────────────────────────────────── */
.flag-banner{background:#FEF2F2;border:1px solid #FECACA;border-radius:10px;padding:18px 22px;margin-bottom:20px}
.flag-banner .flag-label{font-size:.72rem;font-weight:700;color:#DC2626;text-transform:uppercase;letter-spacing:.4px;margin-bottom:5px}
.flag-banner .flag-text{font-size:.82rem;color:#7F1D1D;margin-bottom:8px}
.flag-banner .flag-value{font-family:'Courier New',Courier,monospace;font-size:.95rem;font-weight:700;color:#991B1B;background:#FEF2F2;border:1px dashed #FCA5A5;padding:10px 14px;border-radius:6px;word-break:break-all}

/* ── Responsive ────────────────────────────────────────────────── */
@media(max-width:768px){.sidebar{display:none}.main{padding:20px}}
</style>
</head>
<body>

<?php if (!$currentUser): ?>
<!-- ── AUTH PAGES ──────────────────────────────────────────────── -->
<div class="auth-wrap">
  <div class="auth-card">
    <div class="auth-logo">
      <svg viewBox="0 0 24 24"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-7 3c1.93 0 3.5 1.57 3.5 3.5S13.93 13 12 13s-3.5-1.57-3.5-3.5S10.07 6 12 6zm7 13H5v-.23c0-.62.28-1.2.76-1.58C7.47 15.82 9.64 15 12 15s4.53.82 6.24 2.19c.48.38.76.97.76 1.58V19z"/><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 3c1.66 0 3 1.34 3 3s-1.34 3-3 3-3-1.34-3-3 1.34-3 3-3zm0 14.2c-2.5 0-4.71-1.28-6-3.22.03-1.99 4-3.08 6-3.08 1.99 0 5.97 1.09 6 3.08-1.29 1.94-3.5 3.22-6 3.22z"/></svg>
      <div class="auth-title">MediCare+</div>
      <div class="auth-sub"><?= $isRegister ? 'Create your patient account' : 'Sign in to your patient portal' ?></div>
    </div>

    <?php if ($error): ?>
    <div class="error-msg"><?= esc($error) ?></div>
    <?php endif; ?>

    <form method="POST" action="<?= $isRegister ? '/index.php?action=register' : '/index.php' ?>">
      <?php if ($isRegister): ?>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:0 14px;">
        <input type="text" name="name" class="auth-input" placeholder="Full name" required>
        <input type="date" name="dob" class="auth-input" placeholder="Date of birth">
        <input type="tel" name="phone" class="auth-input" placeholder="Phone number">
        <input type="text" name="insurance" class="auth-input" placeholder="Insurance ID">
        <input type="text" name="blood_group" class="auth-input" placeholder="Blood group (e.g. O+)">
      </div>
      <?php endif; ?>
      <input type="email" name="email" class="auth-input" placeholder="Email address" required>
      <input type="password" name="password" class="auth-input" placeholder="Password" required>
      <button type="submit" class="auth-btn"><?= $isRegister ? 'Create Account' : 'Sign In' ?></button>
    </form>

    <div class="auth-switch">
      <?= $isRegister ? 'Already have an account? <a href="/index.php">Sign in</a>' : 'New patient? <a href="/index.php?action=register">Create an account</a>' ?>
    </div>

    <?php if (!$isRegister): ?>
    <div class="demo-box">
      <strong>Demo Patients:</strong><br>
      sarah@medicare.com / sarah123<br>
      mike@medicare.com / mike123<br>
      robert@medicare.com / robert123
    </div>
    <?php endif; ?>
  </div>
</div>

<?php else: ?>
<!-- ── LOGGED-IN LAYOUT ─────────────────────────────────────────── -->
<div class="app">
  <aside class="sidebar">
    <div class="sidebar-brand">
      <svg viewBox="0 0 24 24"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-7 3c1.93 0 3.5 1.57 3.5 3.5S13.93 13 12 13s-3.5-1.57-3.5-3.5S10.07 6 12 6zm7 13H5v-.23c0-.62.28-1.2.76-1.58C7.47 15.82 9.64 15 12 15s4.53.82 6.24 2.19c.48.38.76.97.76 1.58V19z"/></svg>
      <span>MediCare+</span>
    </div>
    <nav class="sidebar-nav">
      <a href="/index.php?action=dashboard" class="<?= $isDash ? 'active' : '' ?>">
        <svg viewBox="0 0 24 24"><path d="M3 13h8V3H3v10zm0 8h8v-6H3v6zm10 0h8V11h-8v10zm0-18v6h8V3h-8z"/></svg>
        Dashboard
      </a>
      <a href="/index.php?action=appointments" class="<?= $isAppts || $isAppt ? 'active' : '' ?>">
        <svg viewBox="0 0 24 24"><path d="M19 4h-1V2h-2v2H8V2H6v2H5c-1.11 0-2 .9-2 2v14c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 16H5V10h14v10zm0-12H5V6h14v2zM9 14H7v-2h2v2zm4 0h-2v-2h2v2zm4 0h-2v-2h2v2zm-8 4H7v-2h2v2zm4 0h-2v-2h2v2zm4 0h-2v-2h2v2z"/></svg>
        Appointments
      </a>
      <a href="/index.php?action=lab_results" class="<?= $isResults || $isResult ? 'active' : '' ?>">
        <svg viewBox="0 0 24 24"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-1 11h-4v4h-4v-4H6v-4h4V6h4v4h4v4z"/></svg>
        Lab Results
      </a>
      <a href="/index.php?action=prescriptions" class="<?= $isScripts || $isScript ? 'active' : '' ?>">
        <svg viewBox="0 0 24 24"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-1 11h-4v4h-4v-4H6v-4h4V6h4v4h4v4z"/></svg>
        Prescriptions
      </a>
      <a href="/index.php?action=profile" class="<?= $isProfile ? 'active' : '' ?>">
        <svg viewBox="0 0 24 24"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>
        Profile
      </a>
    </nav>
    <div class="sidebar-footer">
      &copy; 2026 MediCare+ Health Systems<br>
      HIPAA Compliant Portal v4.2
    </div>
  </aside>

  <main class="main">
    <div class="topbar">
      <h1><?php
        if ($isAppts || $isAppt) echo 'Appointments';
        elseif ($isResults || $isResult) echo 'Lab Results';
        elseif ($isScripts || $isScript) echo 'Prescriptions';
        elseif ($isProfile) echo 'Profile';
        else echo 'Dashboard';
      ?></h1>
      <div class="user-pill">
        <span class="dot"></span>
        <?= esc($currentUser['name']) ?>
        <span style="color:#94A3B8;font-size:.75rem;">&middot; Patient #<?= (int)$currentUser['id'] ?></span>
        <a href="/index.php?logout=1" style="color:#94A3B8;font-size:.75rem;margin-left:8px;">Sign Out</a>
      </div>
    </div>

<?php if ($isDash): ?>
    <!-- ══════════════ DASHBOARD ═══════════════════════════════════ -->
    <div class="stats-grid">
      <div class="stat-card">
        <div class="stat-icon" style="background:#EFF6FF">
          <svg viewBox="0 0 24 24" style="fill:#2563EB"><path d="M19 4h-1V2h-2v2H8V2H6v2H5c-1.11 0-2 .9-2 2v14c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 16H5V10h14v10zm0-12H5V6h14v2z"/></svg>
        </div>
        <div class="stat-label">Upcoming Appointments</div>
        <div class="stat-value"><?= (int)$upcomingAppts ?></div>
        <div class="stat-sub"><a href="/index.php?action=appointments">View all &rarr;</a></div>
      </div>
      <div class="stat-card">
        <div class="stat-icon" style="background:#FEF3C7">
          <svg viewBox="0 0 24 24" style="fill:#D97706"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-1 11h-4v4h-4v-4H6v-4h4V6h4v4h4v4z"/></svg>
        </div>
        <div class="stat-label">Pending Lab Results</div>
        <div class="stat-value"><?= (int)$pendingResults ?></div>
        <div class="stat-sub"><a href="/index.php?action=lab_results">View all &rarr;</a></div>
      </div>
      <div class="stat-card">
        <div class="stat-icon" style="background:#ECFDF5">
          <svg viewBox="0 0 24 24" style="fill:#059669"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-1 11h-4v4h-4v-4H6v-4h4V6h4v4h4v4z"/></svg>
        </div>
        <div class="stat-label">Active Prescriptions</div>
        <div class="stat-value"><?= (int)$activeScripts ?></div>
        <div class="stat-sub"><a href="/index.php?action=prescriptions">View all &rarr;</a></div>
      </div>
      <div class="stat-card">
        <div class="stat-icon" style="background:#F0F4F8">
          <svg viewBox="0 0 24 24" style="fill:#475569"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>
        </div>
        <div class="stat-label">Blood Group</div>
        <div class="stat-value"><?= esc($currentUser['blood_group'] ?? '—') ?></div>
        <div class="stat-sub"><?= esc($currentUser['insurance_id'] ?? '') ?></div>
      </div>
    </div>

    <!-- Recent Appointments -->
    <div class="card">
      <div class="card-title">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="#2563EB"><path d="M19 4h-1V2h-2v2H8V2H6v2H5c-1.11 0-2 .9-2 2v14c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 16H5V10h14v10zm0-12H5V6h14v2z"/></svg>
        Recent Appointments
      </div>
      <?php if ($myAppts): ?>
      <table>
        <tr><th>Date</th><th>Doctor</th><th>Department</th><th>Status</th><th></th></tr>
        <?php foreach (array_slice($myAppts, 0, 4) as $a): ?>
        <tr>
          <td><?= esc($a['appointment_date']) ?> <span style="color:#94A3B8;font-size:.8rem;"><?= esc($a['time_slot'] ?? '') ?></span></td>
          <td style="font-weight:500"><?= esc($a['doctor_name']) ?></td>
          <td><?= esc($a['department']) ?></td>
          <td><span class="badge badge-<?= strtolower($a['status']) ?>"><?= esc($a['status']) ?></span></td>
          <td><a href="/index.php?action=appointment&id=<?= (int)$a['id'] ?>" class="btn btn-ghost" style="padding:4px 10px;font-size:.75rem">View</a></td>
        </tr>
        <?php endforeach; ?>
      </table>
      <?php else: ?>
      <div class="empty-state">No appointments on record.</div>
      <?php endif; ?>
    </div>

    <!-- Recent Lab Results -->
    <div class="card">
      <div class="card-title">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="#D97706"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-1 11h-4v4h-4v-4H6v-4h4V6h4v4h4v4z"/></svg>
        Recent Lab Results
      </div>
      <?php if ($myResults): ?>
      <table>
        <tr><th>Test</th><th>Date</th><th>Status</th><th></th></tr>
        <?php foreach (array_slice($myResults, 0, 3) as $r): ?>
        <tr>
          <td style="font-weight:500"><?= esc($r['test_name']) ?></td>
          <td><?= esc($r['test_date']) ?></td>
          <td><span class="badge badge-<?= strtolower($r['status']) ?>"><?= esc($r['status']) ?></span></td>
          <td><a href="/index.php?action=lab_result&id=<?= (int)$r['id'] ?>" class="btn btn-ghost" style="padding:4px 10px;font-size:.75rem">View</a></td>
        </tr>
        <?php endforeach; ?>
      </table>
      <?php else: ?>
      <div class="empty-state">No lab results on record.</div>
      <?php endif; ?>
    </div>

<?php elseif ($isAppts): ?>
    <!-- ══════════════ APPOINTMENTS LIST ═══════════════════════════ -->
    <div class="card">
      <div class="card-title">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="#2563EB"><path d="M19 4h-1V2h-2v2H8V2H6v2H5c-1.11 0-2 .9-2 2v14c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 16H5V10h14v10zm0-12H5V6h14v2z"/></svg>
        All Appointments
      </div>
      <?php if ($myAppts): ?>
      <table>
        <tr><th>#</th><th>Date</th><th>Time</th><th>Doctor</th><th>Department</th><th>Status</th><th></th></tr>
        <?php foreach ($myAppts as $a): ?>
        <tr>
          <td style="color:#94A3B8">#<?= (int)$a['id'] ?></td>
          <td><?= esc($a['appointment_date']) ?></td>
          <td><?= esc($a['time_slot'] ?? '—') ?></td>
          <td style="font-weight:500"><?= esc($a['doctor_name']) ?></td>
          <td><?= esc($a['department']) ?></td>
          <td><span class="badge badge-<?= strtolower($a['status']) ?>"><?= esc($a['status']) ?></span></td>
          <td><a href="/index.php?action=appointment&id=<?= (int)$a['id'] ?>" class="btn btn-primary" style="padding:5px 12px;font-size:.75rem">View Details</a></td>
        </tr>
        <?php endforeach; ?>
      </table>
      <?php else: ?>
      <div class="empty-state">No appointments found.</div>
      <?php endif; ?>
    </div>

<?php elseif ($isAppt && $apptDetail): ?>
    <!-- ══════════════ APPOINTMENT DETAIL ══════════════════════════ -->
    <div style="margin-bottom:14px;">
      <a href="/index.php?action=appointments" class="btn btn-ghost" style="padding:6px 12px;font-size:.8rem">&larr; Back to Appointments</a>
    </div>

    <?php if ($apptDetail['doctor_notes'] && str_starts_with($apptDetail['doctor_notes'], 'flag{')): ?>
    <div class="flag-banner">
      <div class="flag-label">SENSITIVE MEDICAL RECORD ACCESSED</div>
      <div class="flag-text">This appointment does not belong to you. The IDOR vulnerability allowed viewing another patient's confidential medical records — including their diagnosis and doctor's notes.</div>
      <div class="flag-value"><?= esc($apptDetail['doctor_notes']) ?></div>
    </div>
    <?php endif; ?>

    <div class="card">
      <div class="detail-hdr">
        <div class="detail-hdr-left">
          <h2>Appointment #<?= (int)$apptDetail['id'] ?></h2>
          <p><?= esc($apptDetail['doctor_name']) ?> &middot; <?= esc($apptDetail['department']) ?></p>
        </div>
        <div>
          <span class="badge badge-<?= strtolower($apptDetail['status']) ?>" style="font-size:.8rem;padding:5px 14px;"><?= esc($apptDetail['status']) ?></span>
        </div>
      </div>

      <div class="detail-grid">
        <div class="detail-field">
          <div class="detail-label">Appointment Date</div>
          <div class="detail-value"><?= date('l, F j, Y', strtotime($apptDetail['appointment_date'])) ?></div>
        </div>
        <div class="detail-field">
          <div class="detail-label">Time Slot</div>
          <div class="detail-value"><?= esc($apptDetail['time_slot'] ?? '—') ?></div>
        </div>
        <div class="detail-field">
          <div class="detail-label">Patient Name</div>
          <div class="detail-value"><?= esc($apptDetail['patient_name']) ?></div>
        </div>
        <div class="detail-field">
          <div class="detail-label">Blood Group</div>
          <div class="detail-value"><?= esc($apptDetail['blood_group'] ?? '—') ?></div>
        </div>
        <div class="detail-field full">
          <div class="detail-label">Symptoms / Reason for Visit</div>
          <div class="detail-value"><?= esc($apptDetail['symptoms'] ?? 'Not provided') ?></div>
        </div>
        <?php if ($apptDetail['doctor_notes']): ?>
        <div class="detail-field full">
          <div class="detail-label">Doctor's Notes &amp; Diagnosis</div>
          <div class="detail-value"><?= nl2br(esc($apptDetail['doctor_notes'])) ?></div>
        </div>
        <?php endif; ?>
      </div>
    </div>

<?php elseif ($isAppt): ?>
    <div class="card"><div class="empty-state">Appointment not found.</div></div>

<?php elseif ($isResults): ?>
    <!-- ══════════════ LAB RESULTS LIST ════════════════════════════ -->
    <div class="card">
      <div class="card-title">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="#D97706"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-1 11h-4v4h-4v-4H6v-4h4V6h4v4h4v4z"/></svg>
        Lab Results
      </div>
      <?php if ($myResults): ?>
      <table>
        <tr><th>#</th><th>Test Name</th><th>Date</th><th>Result</th><th>Status</th><th></th></tr>
        <?php foreach ($myResults as $r): ?>
        <tr>
          <td style="color:#94A3B8">#<?= (int)$r['id'] ?></td>
          <td style="font-weight:500"><?= esc($r['test_name']) ?></td>
          <td><?= esc($r['test_date']) ?></td>
          <td><?= esc(mb_strimwidth($r['result_value'], 0, 30, '…')) ?></td>
          <td><span class="badge badge-<?= strtolower($r['status']) ?>"><?= esc($r['status']) ?></span></td>
          <td><a href="/index.php?action=lab_result&id=<?= (int)$r['id'] ?>" class="btn btn-primary" style="padding:5px 12px;font-size:.75rem">View Details</a></td>
        </tr>
        <?php endforeach; ?>
      </table>
      <?php else: ?>
      <div class="empty-state">No lab results found.</div>
      <?php endif; ?>
    </div>

<?php elseif ($isResult && $resultDetail): ?>
    <!-- ══════════════ LAB RESULT DETAIL ══════════════════════════ -->
    <div style="margin-bottom:14px;">
      <a href="/index.php?action=lab_results" class="btn btn-ghost" style="padding:6px 12px;font-size:.8rem">&larr; Back to Lab Results</a>
    </div>

    <div class="card">
      <div class="detail-hdr">
        <div class="detail-hdr-left">
          <h2><?= esc($resultDetail['test_name']) ?></h2>
          <p>Patient: <?= esc($resultDetail['patient_name']) ?> &middot; Ordered by <?= esc($resultDetail['ordered_by']) ?></p>
        </div>
        <div>
          <span class="badge badge-<?= strtolower($resultDetail['status']) ?>" style="font-size:.8rem;padding:5px 14px;"><?= esc($resultDetail['status']) ?></span>
        </div>
      </div>

      <div class="detail-grid">
        <div class="detail-field">
          <div class="detail-label">Test Date</div>
          <div class="detail-value"><?= esc($resultDetail['test_date']) ?></div>
        </div>
        <div class="detail-field">
          <div class="detail-label">Status</div>
          <div class="detail-value"><?= esc($resultDetail['status']) ?></div>
        </div>
        <div class="detail-field">
          <div class="detail-label">Result Value</div>
          <div class="detail-value"><strong><?= esc($resultDetail['result_value']) ?></strong></div>
        </div>
        <div class="detail-field">
          <div class="detail-label">Reference Range</div>
          <div class="detail-value"><?= esc($resultDetail['reference_range']) ?></div>
        </div>
        <?php if ($resultDetail['notes']): ?>
        <div class="detail-field full">
          <div class="detail-label">Doctor's Notes</div>
          <div class="detail-value"><?= nl2br(esc($resultDetail['notes'])) ?></div>
        </div>
        <?php endif; ?>
      </div>
    </div>

<?php elseif ($isResult): ?>
    <div class="card"><div class="empty-state">Lab result not found.</div></div>

<?php elseif ($isScripts): ?>
    <!-- ══════════════ PRESCRIPTIONS LIST ═════════════════════════ -->
    <div class="card">
      <div class="card-title">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="#059669"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-1 11h-4v4h-4v-4H6v-4h4V6h4v4h4v4z"/></svg>
        Prescriptions
      </div>
      <?php if ($myScripts): ?>
      <table>
        <tr><th>#</th><th>Medication</th><th>Dosage</th><th>Prescribed By</th><th>Date</th><th>Refills</th><th></th></tr>
        <?php foreach ($myScripts as $p): ?>
        <tr>
          <td style="color:#94A3B8">#<?= (int)$p['id'] ?></td>
          <td style="font-weight:500"><?= esc($p['medication_name']) ?></td>
          <td><?= esc($p['dosage']) ?></td>
          <td><?= esc($p['prescribed_by']) ?></td>
          <td><?= esc($p['prescribed_date']) ?></td>
          <td><?= (int)$p['refills'] ?></td>
          <td><a href="/index.php?action=prescription&id=<?= (int)$p['id'] ?>" class="btn btn-primary" style="padding:5px 12px;font-size:.75rem">View Details</a></td>
        </tr>
        <?php endforeach; ?>
      </table>
      <?php else: ?>
      <div class="empty-state">No prescriptions found.</div>
      <?php endif; ?>
    </div>

<?php elseif ($isScript && $scriptDetail): ?>
    <!-- ══════════════ PRESCRIPTION DETAIL ════════════════════════ -->
    <div style="margin-bottom:14px;">
      <a href="/index.php?action=prescriptions" class="btn btn-ghost" style="padding:6px 12px;font-size:.8rem">&larr; Back to Prescriptions</a>
    </div>

    <div class="card">
      <div class="detail-hdr">
        <div class="detail-hdr-left">
          <h2><?= esc($scriptDetail['medication_name']) ?></h2>
          <p>Patient: <?= esc($scriptDetail['patient_name']) ?> &middot; Prescribed by <?= esc($scriptDetail['prescribed_by']) ?></p>
        </div>
      </div>

      <div class="detail-grid">
        <div class="detail-field">
          <div class="detail-label">Dosage</div>
          <div class="detail-value"><strong><?= esc($scriptDetail['dosage']) ?></strong></div>
        </div>
        <div class="detail-field">
          <div class="detail-label">Frequency</div>
          <div class="detail-value"><?= esc($scriptDetail['frequency']) ?></div>
        </div>
        <div class="detail-field">
          <div class="detail-label">Prescribed Date</div>
          <div class="detail-value"><?= esc($scriptDetail['prescribed_date']) ?></div>
        </div>
        <div class="detail-field">
          <div class="detail-label">Refills Remaining</div>
          <div class="detail-value"><?= (int)$scriptDetail['refills'] ?></div>
        </div>
        <?php if ($scriptDetail['notes']): ?>
        <div class="detail-field full">
          <div class="detail-label">Notes</div>
          <div class="detail-value"><?= nl2br(esc($scriptDetail['notes'])) ?></div>
        </div>
        <?php endif; ?>
      </div>
    </div>

<?php elseif ($isScript): ?>
    <div class="card"><div class="empty-state">Prescription not found.</div></div>

<?php elseif ($isProfile): ?>
    <!-- ══════════════ PROFILE ════════════════════════════════════ -->
    <div class="card">
      <div class="card-title">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="#475569"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>
        Personal Information
      </div>
      <div class="detail-grid" style="margin-bottom:0">
        <div class="detail-field">
          <div class="detail-label">Full Name</div>
          <div class="detail-value"><?= esc($currentUser['name']) ?></div>
        </div>
        <div class="detail-field">
          <div class="detail-label">Email</div>
          <div class="detail-value"><?= esc($currentUser['email']) ?></div>
        </div>
        <div class="detail-field">
          <div class="detail-label">Date of Birth</div>
          <div class="detail-value"><?= esc($currentUser['dob'] ?? '—') ?></div>
        </div>
        <div class="detail-field">
          <div class="detail-label">Phone</div>
          <div class="detail-value"><?= esc($currentUser['phone'] ?? '—') ?></div>
        </div>
        <div class="detail-field">
          <div class="detail-label">Insurance ID</div>
          <div class="detail-value"><?= esc($currentUser['insurance_id'] ?? '—') ?></div>
        </div>
        <div class="detail-field">
          <div class="detail-label">Blood Group</div>
          <div class="detail-value"><strong><?= esc($currentUser['blood_group'] ?? '—') ?></strong></div>
        </div>
      </div>
    </div>

<?php endif; ?>
  </main>
</div>
<?php endif; ?>

</body>
</html>