<?php
declare(strict_types=1);
session_start();

/* Local XAMPP configuration */
const DB_HOST = '127.0.0.1';
const DB_USER = 'root';
const DB_PASS = '';
const DB_NAME = 'hireflow';
const SITE_NAME = 'HireFlow';
const ADMIN_EMAIL = 'admin@hireflow.local';
const ADMIN_PASSWORD = 'admin';

function db(): mysqli {
    static $db = null;
    if ($db instanceof mysqli) return $db;

    mysqli_report(MYSQLI_REPORT_OFF);
    $db = new mysqli(DB_HOST, DB_USER, DB_PASS);

    if ($db->connect_errno) {
        http_response_code(500);
        die('HireFlow database connection failed. Check that MySQL is running in XAMPP and that DB_USER/DB_PASS in config.php are correct.');
    }

    $db->set_charset('utf8mb4');

    if (!$db->query("CREATE DATABASE IF NOT EXISTS `".DB_NAME."` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci")) {
        http_response_code(500);
        die('Could not create/select HireFlow database: '.e_db_error($db));
    }

    if (!$db->select_db(DB_NAME)) {
        http_response_code(500);
        die('Could not select HireFlow database: '.e_db_error($db));
    }

    /* Core tables. These are intentionally created on every first request. */
    $queries = [
        "CREATE TABLE IF NOT EXISTS jobs (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            title VARCHAR(160) NOT NULL,
            department VARCHAR(100) NOT NULL,
            location VARCHAR(120) NOT NULL,
            work_mode ENUM('Remote','Hybrid','On-site') NOT NULL DEFAULT 'Hybrid',
            employment_type ENUM('Full-time','Part-time','Contract','Internship') NOT NULL DEFAULT 'Full-time',
            description TEXT NOT NULL,
            requirements TEXT NOT NULL,
            salary VARCHAR(100) NOT NULL DEFAULT '',
            status ENUM('open','closed') NOT NULL DEFAULT 'open',
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        "CREATE TABLE IF NOT EXISTS candidates (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            application_no VARCHAR(30) NOT NULL,
            job_id INT UNSIGNED NOT NULL,
            first_name VARCHAR(80) NOT NULL,
            last_name VARCHAR(80) NOT NULL,
            email VARCHAR(180) NOT NULL,
            phone VARCHAR(50) NOT NULL DEFAULT '',
            location VARCHAR(120) NOT NULL DEFAULT '',
            linkedin VARCHAR(255) NOT NULL DEFAULT '',
            portfolio VARCHAR(255) NOT NULL DEFAULT '',
            resume_path VARCHAR(255) NOT NULL DEFAULT '',
            cover_letter TEXT NOT NULL,
            experience VARCHAR(80) NOT NULL DEFAULT '',
            notice_period VARCHAR(80) NOT NULL DEFAULT '',
            status ENUM('new','screening','interview','offer','hired','rejected') NOT NULL DEFAULT 'new',
            recruiter_note TEXT NOT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uq_application_no (application_no),
            KEY idx_candidate_job (job_id),
            CONSTRAINT fk_candidate_job FOREIGN KEY (job_id) REFERENCES jobs(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        "CREATE TABLE IF NOT EXISTS candidate_events (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            candidate_id INT UNSIGNED NOT NULL,
            event_text VARCHAR(255) NOT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_event_candidate (candidate_id),
            CONSTRAINT fk_event_candidate FOREIGN KEY (candidate_id) REFERENCES candidates(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        "CREATE TABLE IF NOT EXISTS admins (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            email VARCHAR(180) NOT NULL,
            password_hash VARCHAR(255) NOT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uq_admin_email (email)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    ];

    foreach ($queries as $sql) {
        if (!$db->query($sql)) {
            http_response_code(500);
            die('HireFlow database table setup failed: '.e_db_error($db));
        }
    }

    /* Seed a real set of job records. No database.sql/import is required. */
    $countResult = $db->query("SELECT COUNT(*) AS c FROM jobs");
    $jobCount = $countResult ? (int)$countResult->fetch_assoc()['c'] : 0;

    if ($jobCount === 0) {
        $seedJobs = [
            ['Senior Product Designer','Design','Bengaluru, India','Hybrid','Full-time',
             'Shape end-to-end product experiences with a small, senior design team.',
             '5+ years in product design; strong Figma skills; portfolio showing shipped digital products.',
             '₹28–40 LPA'],
            ['Full Stack Engineer','Engineering','Remote — India','Remote','Full-time',
             'Build reliable customer-facing workflows across our web platform and APIs.',
             '3+ years with PHP, Node, React or similar; MySQL/PostgreSQL; testing and API design experience.',
             '₹20–32 LPA'],
            ['Customer Success Manager','Customer Success','Mumbai, India','On-site','Full-time',
             'Help customers adopt modern hiring workflows and turn feedback into outcomes.',
             '2+ years in SaaS customer success; excellent communication; analytical mindset.',
             '₹12–18 LPA'],
            ['Talent Operations Intern','People','Delhi NCR, India','Hybrid','Internship',
             'Support recruiting operations, candidate communication and hiring analytics.',
             'Strong written communication; organized; comfortable with spreadsheets and dashboards.',
             '₹25k–35k/month']
        ];

        $stmt = $db->prepare(
            "INSERT INTO jobs
            (title,department,location,work_mode,employment_type,description,requirements,salary)
            VALUES (?,?,?,?,?,?,?,?)"
        );

        if (!$stmt) {
            http_response_code(500);
            die('Could not prepare HireFlow job seed data: '.e_db_error($db));
        }

        foreach ($seedJobs as $job) {
            $title=$job[0]; $department=$job[1]; $location=$job[2];
            $workMode=$job[3]; $employmentType=$job[4]; $description=$job[5];
            $requirements=$job[6]; $salary=$job[7];

            if (!$stmt->bind_param(
                'ssssssss',
                $title,$department,$location,$workMode,$employmentType,
                $description,$requirements,$salary
            ) || !$stmt->execute()) {
                http_response_code(500);
                die('Could not insert HireFlow job seed data: '.e_db_error($db));
            }
        }
        $stmt->close();
    }

    /* Always ensure the default admin exists. */
    $adminResult = $db->query("SELECT id FROM admins WHERE email='". $db->real_escape_string(ADMIN_EMAIL) ."' LIMIT 1");
    if (!$adminResult || $adminResult->num_rows === 0) {
        $hash = password_hash(ADMIN_PASSWORD, PASSWORD_DEFAULT);
        $stmt = $db->prepare("INSERT INTO admins (email,password_hash) VALUES (?,?)");
        if (!$stmt) {
            http_response_code(500);
            die('Could not prepare HireFlow admin account: '.e_db_error($db));
        }
        $email = ADMIN_EMAIL;
        if (!$stmt->bind_param('ss',$email,$hash) || !$stmt->execute()) {
            http_response_code(500);
            die('Could not create HireFlow admin account: '.e_db_error($db));
        }
        $stmt->close();
    }

    return $db;
}

function e_db_error(mysqli $db): string {
    return htmlspecialchars($db->error ?: 'Unknown MySQL error', ENT_QUOTES, 'UTF-8');
}

function e(?string $v): string {
    return htmlspecialchars($v ?? '', ENT_QUOTES, 'UTF-8');
}

function csrf(): string {
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(16));
    return $_SESSION['csrf'];
}

function verify_csrf(): void {
    if (!hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? '')) die('Invalid request.');
}

function admin_logged(): bool {
    return !empty($_SESSION['admin_id']);
}

function require_admin(): void {
    if (!admin_logged()) {
        header('Location: login.php');
        exit;
    }
}

function app_no(): string {
    return 'HF-' . strtoupper(bin2hex(random_bytes(4)));
}

function flash(?string $message = null): ?string {
    if ($message !== null) {
        $_SESSION['flash'] = $message;
        return null;
    }
    $m = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $m;
}

/*
 * Portable mysqli result reader.
 * This avoids mysqli_stmt::get_result(), which is unavailable on some XAMPP
 * installations that do not have mysqlnd enabled.
 */
function stmt_rows(mysqli_stmt $stmt): array {
    $meta = $stmt->result_metadata();
    if (!$meta) return [];

    $fields = $meta->fetch_fields();
    $row = [];
    $bind = [];

    foreach ($fields as $field) {
        $row[$field->name] = null;
        $bind[] = &$row[$field->name];
    }

    call_user_func_array([$stmt, 'bind_result'], $bind);

    $rows = [];
    while ($stmt->fetch()) {
        $copy = [];
        foreach ($row as $key => $value) $copy[$key] = $value;
        $rows[] = $copy;
    }

    $meta->free();
    return $rows;
}

function stmt_one(mysqli_stmt $stmt): ?array {
    $rows = stmt_rows($stmt);
    return $rows[0] ?? null;
}

function status_label(string $s): string {
    return ucfirst(str_replace('_',' ',$s));
}

/* Initialize the complete application automatically. */
db();
?>
