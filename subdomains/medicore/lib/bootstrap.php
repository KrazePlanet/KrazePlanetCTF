<?php
// MediCore — Healthcare Patient Portal Training Lab
// Intentional vulnerability: GET /reports/view?id=X trusts the id query parameter
// without checking if the report belongs to the authenticated session user — classic IDOR.
const BASE     = '/subdomains/medicore';
const LAB_FLAG = 'KP{idor_medical_report_access}';

session_set_cookie_params(['path' => BASE, 'httponly' => true, 'samesite' => 'Lax']);
session_name('MEDICORESID');
session_start();

function db(): PDO {
    static $pdo = null;
    if ($pdo) return $pdo;
    $host='127.0.0.1'; $user='root'; $pass=''; $name='medicore_lab';
    $opt=[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,
          PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,
          PDO::ATTR_EMULATE_PREPARES=>false];
    try { $pdo=new PDO("mysql:host=$host;dbname=$name;charset=utf8mb4",$user,$pass,$opt); }
    catch(PDOException $e){
        if((int)$e->getCode()!==1049) throw $e;
        $r=new PDO("mysql:host=$host;charset=utf8mb4",$user,$pass,$opt);
        $r->exec("CREATE DATABASE `$name` CHARACTER SET utf8mb4");
        $pdo=new PDO("mysql:host=$host;dbname=$name;charset=utf8mb4",$user,$pass,$opt);
        install_schema($pdo);
    }
    return $pdo;
}

function install_schema(PDO $pdo, bool $reset=false): void {
    if($reset)
        foreach(['reports','appointments','users'] as $t) $pdo->exec("DROP TABLE IF EXISTS $t");

    $pdo->exec("CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        username VARCHAR(50) NOT NULL UNIQUE,
        email VARCHAR(190) NOT NULL UNIQUE,
        password_hash VARCHAR(255) NOT NULL,
        role ENUM('patient','doctor','admin') DEFAULT 'patient',
        blood_group VARCHAR(5) DEFAULT 'O+',
        dob DATE NULL,
        allergies VARCHAR(255) DEFAULT 'None',
        phone VARCHAR(20) DEFAULT '',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP)");

    $pdo->exec("CREATE TABLE IF NOT EXISTS reports (
        id INT AUTO_INCREMENT PRIMARY KEY,
        patient_id INT NOT NULL,
        ordered_by INT NOT NULL,
        test_type VARCHAR(100) NOT NULL,
        test_date DATE NOT NULL,
        result_summary TEXT NOT NULL,
        result_detail TEXT NOT NULL,
        status ENUM('pending','ready','reviewed') DEFAULT 'ready',
        accessed_by INT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY(patient_id) REFERENCES users(id),
        FOREIGN KEY(ordered_by) REFERENCES users(id))");

    $pdo->exec("CREATE TABLE IF NOT EXISTS appointments (
        id INT AUTO_INCREMENT PRIMARY KEY,
        patient_id INT NOT NULL,
        doctor_id INT NOT NULL,
        appointment_date DATE NOT NULL,
        appointment_time VARCHAR(10) NOT NULL,
        reason VARCHAR(255) NOT NULL,
        status ENUM('scheduled','completed','cancelled') DEFAULT 'scheduled',
        FOREIGN KEY(patient_id) REFERENCES users(id),
        FOREIGN KEY(doctor_id) REFERENCES users(id))");

    if((int)$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn()===0){
        $seed_users=[
            ['Dr. Rajesh Sharma','dr_sharma','dr.sharma@medicore.in','doctor123','doctor','A+','1975-04-12','None','+91 98765 00001'],
            ['Riya Patel','patient_riya','riya@example.com','riya123','patient','B+','1994-07-23','Penicillin','+91 98765 00002'],
            ['Arjun Mehta','patient_arjun','arjun@example.com','arjun123','patient','O+','1988-11-05','Dust, Pollen','+91 98765 00003'],
            ['Student','student','student@medicore.lab','student123','patient','AB+','2000-01-15','None','+91 98765 00004'],
        ];
        $ui=$pdo->prepare("INSERT INTO users(name,username,email,password_hash,role,blood_group,dob,allergies,phone) VALUES(?,?,?,?,?,?,?,?,?)");
        foreach($seed_users as $u)
            $ui->execute([$u[0],$u[1],$u[2],password_hash($u[3],PASSWORD_DEFAULT),$u[4],$u[5],$u[6],$u[7],$u[8]]);

        $ids=[];
        foreach(['dr.sharma@medicore.in','riya@example.com','arjun@example.com','student@medicore.lab'] as $e)
            $ids[]=(int)$pdo->query("SELECT id FROM users WHERE email='$e'")->fetchColumn();
        [$doc,$riya,$arjun,$student]=$ids;

        $seed_reports=[
            [$riya,$doc,'Complete Blood Count','2024-09-15',
             'Haemoglobin slightly low (11.2 g/dL). WBC and platelets within normal range.',
             "Haemoglobin: 11.2 g/dL (Low)\nWBC: 7,400 /μL (Normal)\nPlatelets: 2,10,000 /μL (Normal)\nRBC: 4.1 M/μL (Normal)\nHaematocrit: 34% (Low)\n\nDoctor's Note: Patient reports fatigue. Recommend iron supplement (Ferrous Sulphate 150mg OD). Repeat CBC in 4 weeks.",'ready'],
            [$riya,$doc,'Urine Routine & Microscopy','2024-10-01',
             'Mild urinary infection detected. Bacteria present.',
             "Colour: Pale yellow\nClarity: Slightly turbid\npH: 6.5\nProtein: Trace\nGlucose: Nil\nPus Cells: 10-15/HPF (High)\nBacteria: Present (++)\nRBC: 2-4/HPF\n\nDoctor's Note: Likely UTI. Prescribed Nitrofurantoin 100mg BD x 5 days. Push fluids.",'reviewed'],
            [$arjun,$doc,'Lipid Profile','2024-08-20',
             'Total cholesterol elevated. LDL high. Lifestyle modification advised.',
             "Total Cholesterol: 228 mg/dL (High)\nLDL: 158 mg/dL (High)\nHDL: 42 mg/dL (Low)\nTriglycerides: 185 mg/dL (Borderline)\nVLDL: 37 mg/dL\n\nDoctor's Note: Diet intervention required. Reduce saturated fats. Consider statin therapy if no improvement in 3 months.",'ready'],
            [$arjun,$doc,'Blood Glucose (Fasting)','2024-10-05',
             'Fasting glucose 118 mg/dL — pre-diabetic range. Follow up needed.',
             "Fasting Blood Glucose: 118 mg/dL (Pre-diabetic: 100-125)\nHbA1c: 6.1% (Pre-diabetic: 5.7-6.4%)\n\nDoctor's Note: Pre-diabetes confirmed. Immediate lifestyle changes: 30 min walk daily, reduce sugar and refined carbs. Review in 3 months.",'reviewed'],
            [$student,$doc,'Thyroid Function Test (TFT)','2024-09-28',
             'TSH mildly elevated. T3 and T4 within normal range.',
             "TSH: 5.8 mIU/L (Slightly High, Normal: 0.4–4.5)\nFree T3: 3.1 pg/mL (Normal)\nFree T4: 1.1 ng/dL (Normal)\n\nDoctor's Note: Sub-clinical hypothyroidism. Monitor every 6 months. No medication required at this time.",'ready'],
            [$student,$doc,'ECG (Electrocardiogram)','2024-10-10',
             'Normal sinus rhythm. No significant abnormalities detected.',
             "Heart Rate: 72 bpm (Normal)\nRhythm: Regular sinus rhythm\nPR Interval: 160 ms (Normal)\nQRS Duration: 88 ms (Normal)\nQTc: 410 ms (Normal)\nAxis: Normal\n\nDoctor's Note: ECG normal. No cardiac concerns at this time.",'reviewed'],
        ];
        $ri=$pdo->prepare("INSERT INTO reports(patient_id,ordered_by,test_type,test_date,result_summary,result_detail,status) VALUES(?,?,?,?,?,?,?)");
        foreach($seed_reports as $r) $ri->execute($r);

        $seed_appt=[
            [$riya,$doc,'2024-11-15','10:30 AM','Follow-up for CBC results','scheduled'],
            [$arjun,$doc,'2024-11-18','02:00 PM','Lipid profile review and diet counselling','scheduled'],
            [$student,$doc,'2024-11-20','11:00 AM','Thyroid follow-up','scheduled'],
        ];
        $ai=$pdo->prepare("INSERT INTO appointments(patient_id,doctor_id,appointment_date,appointment_time,reason,status) VALUES(?,?,?,?,?,?)");
        foreach($seed_appt as $a) $ai->execute($a);
    }
}

/* ── HELPERS ──────────────────────────────────────────────── */
function h($s): string { return htmlspecialchars((string)$s,ENT_QUOTES,'UTF-8'); }
function url(string $p=''): string { return BASE.($p?'/'.ltrim($p,'/'):''); }
function flash(string $t, string $m): void { $_SESSION['flash'][]=[$t,$m]; }
function redirect(string $p): never { header('Location:'.url($p)); exit; }
function user(): ?array {
    static $u=false; if($u!==false) return $u; $u=null;
    if(!empty($_SESSION['uid'])){
        $st=db()->prepare("SELECT * FROM users WHERE id=?");
        $st->execute([$_SESSION['uid']]); $u=$st->fetch()?:null;
    }
    return $u;
}
function require_login(): array { $u=user(); if(!$u){flash('error','Please sign in.');redirect('login');} return $u; }
function csrf_token(): string { if(empty($_SESSION['csrf'])) $_SESSION['csrf']=bin2hex(random_bytes(16)); return $_SESSION['csrf']; }
function verify_csrf(): void { if(!hash_equals($_SESSION['csrf']??'',$_POST['_csrf']??'')){http_response_code(403);die('Invalid CSRF token.');} }
function is_doctor(): bool { $u=user(); return $u&&in_array($u['role'],['doctor','admin']); }

db();
