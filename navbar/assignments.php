<?php
// assignments.php

function formatAssignmentInstructions($raw) {
    if (empty($raw)) return '';
    
    $lines = explode("\n", $raw);
    $html = '';
    $inList = false;
    
    foreach ($lines as $line) {
        $trimmed = trim($line);
        if (empty($trimmed)) {
            if ($inList) { $html .= '</ol>'; $inList = false; }
            $html .= '<div class="my-2"></div>';
            continue;
        }
        
        if (strpos($trimmed, 'Report Structure:') !== false) {
            if ($inList) { $html .= '</ol>'; $inList = false; }
            $html .= '<div class="fw-bold text-white small mt-3 mb-2 d-flex align-items-center gap-2"><i class="bi bi-journal-text text-info"></i> ' . htmlspecialchars($trimmed) . '</div>';
        } elseif (preg_match('/^(\d+)\.\s*(.*)/', $trimmed, $m)) {
            if (!$inList) {
                $html .= '<ol class="mb-0 ps-3 text-light" style="font-size: 13.5px; line-height: 1.8;">';
                $inList = true;
            }
            $html .= '<li class="mb-1">' . htmlspecialchars($m[2]) . '</li>';
        } else {
            if ($inList) { $html .= '</ol>'; $inList = false; }
            $html .= '<p class="mb-1 text-light" style="line-height: 1.6; font-size: 13.5px;">' . htmlspecialchars($trimmed) . '</p>';
        }
    }
    if ($inList) { $html .= '</ol>'; }
    return $html;
}

// Unified Assignments Platform & API for KrazePlanet
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/../config/db.php';
// Robust PHPMailer Multi-Path Loader
$phpMailerLoaded = false;
$mailerPaths = [
    __DIR__ . '/PHPMailer',
    __DIR__ . '/../subdomains/PHPMailer',
    __DIR__ . '/../PHPMailer',
    '/opt/lampp/htdocs/subdomains/PHPMailer',
    '/opt/lampp/htdocs/navbar/PHPMailer',
    '/opt/lampp/htdocs/PHPMailer'
];
foreach ($mailerPaths as $mDir) {
    if (file_exists("{$mDir}/Exception.php")) {
        require_once "{$mDir}/Exception.php";
        require_once "{$mDir}/PHPMailer.php";
        require_once "{$mDir}/SMTP.php";
        $phpMailerLoaded = true;
        break;
    }
}

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$isLoggedIn = isset($_SESSION['user_id']);
$currentUsername = $_SESSION['username'] ?? '';
$isAdmin = false;

if ($isLoggedIn && $pdo) {
    $stmt = $pdo->prepare("SELECT role, username FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $u = $stmt->fetch();
    if ($u && ($u['role'] === 'admin' || strtolower($u['username']) === 'admin')) {
        $isAdmin = true;
    }
}

// Helper function to dispatch assignment emails to all tagged users
function notifyTaggedUsers($pdo, $assigned_users_str, $assignment_title, $category_name, $submission_date, $description, $labs_data) {
    $tags = preg_split('/[\s,]+/', $assigned_users_str, -1, PREG_SPLIT_NO_EMPTY);
    if (empty($tags)) return;

    $formattedDate = date('d-m-Y H:i:s T', strtotime($submission_date));

    // Build Labs HTML
    $labsHtml = '';
    foreach ($labs_data as $l) {
        $rep = (!empty($l['report_num']) && !empty($l['report_url'])) ? ' <a href="' . $l['report_url'] . '" style="color:#fb7185;text-decoration:none;font-weight:bold;">[' . $l['report_num'] . ']</a>' : '';
        $labLink = htmlspecialchars($l['link'] ?? '#');
        if (strpos($labLink, 'http') !== 0) {
            $labLink = 'http://localhost' . (strpos($labLink, '/') === 0 ? '' : '/') . $labLink;
        }

        $labsHtml .= '
        <div style="background:#1e293b; border:1px solid rgba(255,255,255,0.08); border-radius:8px; padding:12px 16px; margin-bottom:8px; display:flex; align-items:center; justify-content:space-between;">
            <div>
                <span style="background:rgba(16,185,129,0.15); color:#34d399; font-weight:700; font-family:monospace; padding:3px 8px; border-radius:6px; font-size:12px; margin-right:8px;">' . htmlspecialchars($l['badge'] ?? 'LAB') . '</span>
                <span style="color:#ffffff; font-weight:600; font-size:14px;">' . htmlspecialchars($l['title'] ?? '') . $rep . '</span>
                <span style="color:#94a3b8; font-size:12px; margin-left:8px;">(' . htmlspecialchars($l['difficulty'] ?? 'Easy') . ' • ' . htmlspecialchars($l['type'] ?? 'Training') . ')</span>
            </div>
            <div>
                <a href="' . $labLink . '" style="background:#10b981; color:#ffffff; padding:6px 12px; border-radius:6px; text-decoration:none; font-weight:700; font-size:12px; display:inline-block;">Access Lab &rarr;</a>
            </div>
        </div>';
    }

    foreach ($tags as $tag) {
        $cleanUsername = ltrim($tag, '@');
        if (empty($cleanUsername)) continue;

        $stmt = $pdo->prepare("SELECT id, username, fullname, email FROM users WHERE LOWER(username) = LOWER(?) LIMIT 1");
        $stmt->execute([$cleanUsername]);
        $u = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($u && !empty($u['email']) && filter_var($u['email'], FILTER_VALIDATE_EMAIL)) {
            try {
                $mail = new PHPMailer(true);
                configureKrazeMailer($mail, 'noreply@krazeplanet.com', 'KrazePlanet Security');
                $mail->addAddress($u['email'], $u['username']);
                $mail->isHTML(true);
                $mail->Subject = 'New Assignment: ' . $assignment_title . ' - KrazePlanet';

                $mail->Body = '
                <div style="font-family: -apple-system, BlinkMacSystemFont, Roboto, sans-serif; background:#070b14; padding:30px 15px; color:#f8fafc;">
                    <div style="max-width:620px; margin:0 auto; background:#0f172a; border:1px solid rgba(255,255,255,0.12); border-radius:16px; padding:32px; box-shadow:0 20px 40px rgba(0,0,0,0.6);">
                        <div style="display:flex; align-items:center; gap:8px; margin-bottom:20px;">
                            <span style="font-size:24px; font-weight:800; color:#38bdf8; letter-spacing:-0.5px;">KrazePlanet</span>
                            <span style="font-size:12px; font-weight:600; background:rgba(56,189,248,0.15); color:#38bdf8; padding:3px 8px; border-radius:8px; border:1px solid rgba(56,189,248,0.3);">Assignments</span>
                        </div>
                        
                        <h2 style="font-size:22px; font-weight:800; margin-bottom:8px; color:#ffffff;">New Security Assignment</h2>
                        <p style="color:#94a3b8; font-size:14px; margin-bottom:20px;">Hello <strong>@' . htmlspecialchars($u['username']) . '</strong>, you have been tagged and assigned a new security assignment.</p>

                        <div style="background:#070b14; border-left:4px solid #38bdf8; border-radius:8px; padding:18px; margin-bottom:24px;">
                            <p style="margin:0 0 8px 0; font-size:15px; color:#ffffff;"><strong style="color:#38bdf8;">Assignment:</strong> ' . htmlspecialchars($assignment_title) . '</p>
                            <p style="margin:0 0 8px 0; font-size:13px; color:#94a3b8;"><strong style="color:#ffffff;">Category:</strong> ' . htmlspecialchars($category_name) . '</p>
                            <p style="margin:0 0 8px 0; font-size:13px; color:#fbbf24;"><strong style="color:#ffffff;">Submission Deadline:</strong> ' . htmlspecialchars($formattedDate) . ' (48 Hours)</p>
                            <p style="margin:0; font-size:13px; color:#cbd5e1;"><strong style="color:#ffffff;">Scope & Instructions:</strong> ' . htmlspecialchars($description) . '</p>
                        </div>

                        <h3 style="font-size:16px; font-weight:700; color:#ffffff; margin-bottom:12px;">Required Vulnerability Labs (' . count($labs_data) . '):</h3>
                        ' . $labsHtml . '

                        <div style="text-align:center; margin-top:28px; margin-bottom:16px;">
                            <a href="http://localhost/assignments.php" style="background:linear-gradient(135deg, #0284c7, #0369a1); color:#ffffff; padding:12px 28px; border-radius:10px; text-decoration:none; font-weight:700; font-size:14px; display:inline-block; box-shadow:0 4px 15px rgba(2,132,199,0.4);">
                                Open Assignments Portal &rarr;
                            </a>
                        </div>

                        <hr style="border:none; border-top:1px solid rgba(255,255,255,0.08); margin:24px 0 16px 0;">
                        <p style="color:#64748b; font-size:12px; text-align:center; margin:0;">
                            KrazePlanet Cyber Security Training Platform • Automated Assignment Notification
                        </p>
                    </div>
                </div>';

                $mail->send();
            } catch (Exception $e) {
                error_log("Assignment Notify Mail Error for {$u['email']}: " . $mail->ErrorInfo);
            }
        }
    }
}

// ---------------- API HANDLERS (when action query/post is supplied) ----------------
$action = $_GET['action'] ?? $_POST['action'] ?? '';

if (!empty($action)) {
    header('Content-Type: application/json');

    if ($action === 'create_assignment' || $action === 'create_task') {
        if (!$isAdmin) {
            echo json_encode(['success' => false, 'message' => 'Unauthorized. Admin access required.']);
            exit;
        }

        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $category_name = trim($_POST['category_name'] ?? 'General');
        $assigned_users = trim($_POST['assigned_users'] ?? '');
        $submission_date = trim($_POST['submission_date'] ?? '');
        $labs_raw = trim($_POST['labs_json'] ?? '[]');

        if (empty($title) || empty($assigned_users) || empty($submission_date)) {
            echo json_encode(['success' => false, 'message' => 'Please fill in Title, Assigned Users, and Submission Deadline.']);
            exit;
        }

        $labs_data = json_decode($labs_raw, true);
        if (!is_array($labs_data)) {
            $labs_data = [];
        }

        try {
            $stmtCheck = $pdo->prepare("SELECT id FROM tasks WHERE LOWER(TRIM(title)) = LOWER(TRIM(?))");
            $stmtCheck->execute([$title]);
            $existingTask = $stmtCheck->fetch();

            if ($existingTask) {
                $stmtUpdate = $pdo->prepare("
                    UPDATE tasks 
                    SET description = ?, category_name = ?, assigned_users = ?, submission_date = ?, labs_json = ?
                    WHERE id = ?
                ");
                $stmtUpdate->execute([
                    $description,
                    $category_name,
                    $assigned_users,
                    $submission_date,
                    json_encode($labs_data, JSON_UNESCAPED_SLASHES),
                    $existingTask['id']
                ]);

                notifyTaggedUsers($pdo, $assigned_users, $title, $category_name, $submission_date, $description, $labs_data);

                echo json_encode([
                    'success' => true,
                    'message' => 'Existing assignment updated and notifications dispatched to tagged students!'
                ]);
                exit;
            }

            $stmt = $pdo->prepare("
                INSERT INTO tasks (title, description, category_name, assigned_users, submission_date, labs_json, created_by)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $title,
                $description,
                $category_name,
                $assigned_users,
                $submission_date,
                json_encode($labs_data, JSON_UNESCAPED_SLASHES),
                $_SESSION['user_id']
            ]);

            notifyTaggedUsers($pdo, $assigned_users, $title, $category_name, $submission_date, $description, $labs_data);

            echo json_encode([
                'success' => true,
                'message' => 'Assignment created and notification emails dispatched to tagged students!'
            ]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
        }
        exit;
    }

    if ($action === 'delete_assignment' || $action === 'delete_task') {
        if (!$isAdmin) {
            echo json_encode(['success' => false, 'message' => 'Unauthorized. Admin access required.']);
            exit;
        }

        $taskId = intval($_POST['task_id'] ?? $_POST['assignment_id'] ?? 0);
        if ($taskId <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid assignment ID.']);
            exit;
        }

        try {
            $stmt = $pdo->prepare("DELETE FROM tasks WHERE id = ?");
            $stmt->execute([$taskId]);
            echo json_encode(['success' => true, 'message' => 'Assignment deleted successfully.']);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
        }
        exit;
    }
}

// ---------------- HTML VIEW ----------------
$tasks = [];
if ($pdo) {
    $stmt = $pdo->query("SELECT * FROM tasks ORDER BY id DESC");
    $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!doctype html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Security Assignments - KrazePlanet</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0/dist/css/bootstrap.min.css" rel="stylesheet"
    integrity="sha384-gH2yIJqKdNHPEq0n4Mqa/HGKIhSkIHeL5AyhkYV8i59U5AR6csBvApHHNl/vI1Bx" crossorigin="anonymous">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.9.1/font/bootstrap-icons.css">
  <link rel="icon" href="favicon.ico" />
  
  <!-- Google Fonts: Inter, Outfit, JetBrains Mono -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500;600;700;800&family=Outfit:wght@500;600;700;800&display=swap" rel="stylesheet">

  <style>
    :root {
      --bg-dark: #070b14;
      --bg-card: rgba(15, 23, 42, 0.85);
      --border-card: rgba(255, 255, 255, 0.09);
      --accent-green: #10b981;
      --accent-blue: #38bdf8;
      --accent-purple: #a855f7;
      --font-primary: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
      --font-mono: 'JetBrains Mono', monospace;
    }

    * {
      box-sizing: border-box;
    }

    body {
      background-color: var(--bg-dark);
      background-image: 
        radial-gradient(at 0% 0%, rgba(56, 189, 248, 0.08) 0px, transparent 50%),
        radial-gradient(at 100% 0%, rgba(16, 185, 129, 0.08) 0px, transparent 50%),
        radial-gradient(at 50% 100%, rgba(15, 23, 42, 0.6) 0px, transparent 50%);
      background-attachment: fixed;
      color: #f8fafc;
      font-family: var(--font-primary);
      min-height: 100vh;
      display: flex;
      flex-direction: column;
    }

    h1, h2, h3, h4, h5, h6 {
      font-family: 'Outfit', sans-serif;
    }

    .hero-title {
      font-size: 2.6rem;
      font-weight: 800;
      background: linear-gradient(135deg, #ffffff 30%, #38bdf8 70%, #34d399 100%);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      margin-bottom: 0.6rem;
      letter-spacing: -0.5px;
    }

    .task-container-card {
      background: var(--bg-card);
      border: 1px solid var(--border-card);
      border-radius: 18px;
      padding: 32px;
      backdrop-filter: blur(16px);
      box-shadow: 0 15px 35px rgba(0, 0, 0, 0.35);
      margin-bottom: 2.5rem;
      position: relative;
      overflow: hidden;
    }

    .task-container-card::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      height: 3px;
      background: linear-gradient(90deg, #38bdf8, #10b981, #a855f7);
    }

    /* Digital Countdown Timer HUD */
    .timer-hud {
      background: rgba(7, 11, 20, 0.85);
      border: 1px solid rgba(56, 189, 248, 0.25);
      border-radius: 14px;
      padding: 18px 24px;
      display: inline-flex;
      align-items: center;
      gap: 16px;
      box-shadow: 0 4px 20px rgba(0, 0, 0, 0.4), inset 0 0 15px rgba(56, 189, 248, 0.05);
    }

    .timer-unit {
      text-align: center;
    }

    .timer-value {
      font-family: var(--font-mono);
      font-size: 1.8rem;
      font-weight: 800;
      color: #38bdf8;
      background: rgba(15, 23, 42, 0.9);
      padding: 4px 10px;
      border-radius: 8px;
      border: 1px solid rgba(56, 189, 248, 0.3);
      min-width: 54px;
      display: inline-block;
      box-shadow: 0 0 12px rgba(56, 189, 248, 0.2);
    }

    .timer-label {
      font-size: 0.68rem;
      text-transform: uppercase;
      font-weight: 700;
      color: #94a3b8;
      margin-top: 4px;
      letter-spacing: 0.5px;
    }

    .timer-sep {
      font-family: var(--font-mono);
      font-size: 1.5rem;
      font-weight: 800;
      color: #38bdf8;
      opacity: 0.6;
      animation: blinkSep 1s infinite;
    }

    @keyframes blinkSep {
      0%, 100% { opacity: 0.8; }
      50% { opacity: 0.2; }
    }

    /* Tagged User Badges */
    .user-tag-pill {
      background: rgba(56, 189, 248, 0.15);
      color: #38bdf8;
      border: 1px solid rgba(56, 189, 248, 0.3);
      padding: 4px 12px;
      border-radius: 20px;
      font-size: 0.85rem;
      font-weight: 600;
      display: inline-flex;
      align-items: center;
      gap: 6px;
    }

    .my-assignment-badge {
      background: linear-gradient(135deg, rgba(16, 185, 129, 0.25), rgba(5, 150, 105, 0.35));
      color: #34d399;
      border: 1px solid rgba(16, 185, 129, 0.4);
      padding: 5px 14px;
      border-radius: 20px;
      font-size: 0.82rem;
      font-weight: 700;
      box-shadow: 0 0 15px rgba(16, 185, 129, 0.25);
    }

    /* 1:1 Matching Lab Cards & Category Styling from index.php */
    .category-title {
      font-size: 1.35rem;
      font-weight: 800;
      color: #ffffff;
      margin: 1.5rem 0 1.25rem 0;
      padding: 0.5rem 0 0.5rem 1.1rem;
      border-left: 4px solid #10b981;
      background: linear-gradient(90deg, rgba(16, 185, 129, 0.15), transparent);
      border-radius: 0 8px 8px 0;
      letter-spacing: -0.3px;
      display: flex;
      align-items: center;
      justify-content: space-between;
    }

    .labs-list {
      display: flex;
      flex-direction: column;
      gap: 0.95rem;
      margin-bottom: 1.5rem;
    }

    .lab-card {
      display: flex;
      background: linear-gradient(135deg, rgba(30, 41, 59, 0.95) 0%, rgba(20, 29, 47, 0.95) 100%);
      border-radius: 14px;
      border: 1px solid rgba(255, 255, 255, 0.14);
      border-left: 4px solid #10b981;
      box-shadow: 0 4px 20px rgba(0, 0, 0, 0.35), inset 0 1px 0 rgba(255, 255, 255, 0.08);
      transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
      overflow: hidden;
      align-items: center;
      position: relative;
      backdrop-filter: blur(12px);
    }

    .lab-card:hover {
      transform: translateY(-3px);
      background: linear-gradient(135deg, rgba(35, 48, 68, 0.98) 0%, rgba(24, 34, 53, 0.98) 100%);
      box-shadow: 0 12px 30px rgba(0, 0, 0, 0.5), 0 0 25px rgba(52, 211, 153, 0.25);
      border-color: rgba(52, 211, 153, 0.6);
      border-left-color: #34d399;
    }

    .lab-badge {
      background: rgba(15, 23, 42, 0.95);
      color: #ffffff;
      padding: 1.1rem 1.35rem;
      display: flex;
      align-items: center;
      gap: 8px;
      font-weight: 800;
      font-size: 0.88rem;
      font-family: var(--font-mono);
      letter-spacing: 0.06em;
      text-transform: uppercase;
      align-self: stretch;
      min-width: 120px;
      justify-content: center;
      border-right: 1px solid rgba(255, 255, 255, 0.14);
      white-space: nowrap;
    }

    .lab-badge svg {
      width: 20px;
      height: 20px;
      stroke: #34d399;
      stroke-width: 2.3;
      filter: drop-shadow(0 0 6px rgba(52, 211, 153, 0.5));
      fill: none;
    }

    .lab-content {
      flex: 1;
      padding: 0.9rem 1.35rem;
      display: flex;
      flex-direction: column;
      justify-content: center;
      gap: 6px;
    }

    .difficulty-tag {
      background: linear-gradient(135deg, #10b981, #047857);
      color: #ffffff;
      font-size: 0.7rem;
      font-weight: 800;
      padding: 4px 10px;
      border-radius: 6px;
      text-transform: uppercase;
      letter-spacing: 0.06em;
      display: inline-flex;
      width: fit-content;
      box-shadow: 0 2px 8px rgba(16, 185, 129, 0.35);
    }

    .difficulty-tag.medium {
      background: linear-gradient(135deg, #f59e0b, #b45309);
      color: #ffffff;
      box-shadow: 0 2px 8px rgba(245, 158, 11, 0.35);
    }

    .difficulty-tag.hard {
      background: linear-gradient(135deg, #f43f5e, #be123c);
      color: #ffffff;
      box-shadow: 0 2px 8px rgba(244, 63, 94, 0.35);
    }

    .lab-title {
      font-size: 1.1rem;
      font-weight: 700;
      color: #ffffff !important;
      display: flex;
      align-items: center;
      gap: 8px;
      font-family: var(--font-primary);
      letter-spacing: -0.01em;
    }

    .lab-title svg {
      width: 16px;
      height: 16px;
      stroke: #cbd5e1;
      stroke-width: 2;
      fill: none;
      transition: transform 0.2s, stroke 0.2s;
    }

    .lab-card:hover .lab-title svg {
      transform: translateX(4px);
      stroke: #34d399;
    }

    .report-badge {
      color: #ffffff;
      background: linear-gradient(135deg, #be185d, #9d174d);
      padding: 3px 8px;
      border-radius: 5px;
      text-decoration: none;
      font-weight: 700;
      font-size: 0.74rem;
      font-family: var(--font-mono);
      letter-spacing: 0.02em;
      flex-shrink: 0;
      box-shadow: 0 2px 8px rgba(190, 24, 93, 0.4);
    }
    .report-badge:hover { background: #9d174d; color: #ffffff; }

    .lab-action {
      padding: 0.85rem 1.25rem;
      display: flex;
      align-items: center;
      gap: 10px;
      flex-wrap: wrap;
    }

    .btn-ACCESS {
      background: linear-gradient(135deg, #10b981 0%, #059669 100%);
      border: 1px solid rgba(52, 211, 153, 0.4);
      color: #ffffff;
      text-decoration: none;
      font-weight: 700;
      font-size: 0.82rem;
      padding: 8px 18px;
      border-radius: 10px;
      display: inline-flex;
      align-items: center;
      gap: 6px;
      transition: all 0.2s ease;
      box-shadow: 0 4px 14px rgba(16, 185, 129, 0.35);
      text-transform: uppercase;
      letter-spacing: 0.04em;
      cursor: pointer;
    }

    .btn-ACCESS svg {
      width: 16px;
      height: 16px;
      stroke: currentColor;
      stroke-width: 2;
      fill: none;
    }

    .btn-ACCESS:hover {
      background: linear-gradient(135deg, #059669 0%, #047857 100%);
      transform: translateY(-2px);
      box-shadow: 0 6px 20px rgba(16, 185, 129, 0.5);
      color: #ffffff;
    }

    .btn-star-toggle {
      background: transparent;
      border: none;
      color: #cbd5e1;
      font-size: 1.2rem;
      padding: 4px 8px;
      transition: all 0.2s ease;
      cursor: pointer;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      margin-right: 2px;
    }
    .btn-star-toggle:hover {
      color: #fbbf24;
      transform: scale(1.2);
    }
    .btn-star-toggle.bookmarked {
      color: #fbbf24;
    }

    .btn-solved-toggle {
      background: rgba(255, 255, 255, 0.08);
      border: 1px solid rgba(255, 255, 255, 0.18);
      color: #cbd5e1;
      border-radius: 10px;
      padding: 7px 14px;
      font-size: 0.8rem;
      font-weight: 600;
      transition: all 0.2s ease;
      cursor: pointer;
      display: inline-flex;
      align-items: center;
    }
    .btn-solved-toggle:hover {
      background: rgba(255, 255, 255, 0.16);
      color: #ffffff;
      border-color: rgba(255, 255, 255, 0.3);
    }
    .btn-solved-toggle.solved {
      background: rgba(16, 185, 129, 0.2);
      border-color: #10b981;
      color: #34d399;
    }

    .btn-create-task {
      background: linear-gradient(135deg, #0284c7, #0369a1);
      color: #ffffff;
      font-weight: 700;
      padding: 9px 18px;
      border-radius: 10px;
      border: none;
      transition: all 0.2s;
      box-shadow: 0 4px 15px rgba(2, 132, 199, 0.35);
    }
    .btn-create-task:hover {
      background: linear-gradient(135deg, #0369a1, #075985);
      color: #ffffff;
      transform: translateY(-1px);
    }

    @media (max-width: 991.98px) {
      .lab-card {
        flex-direction: column;
        align-items: stretch;
        border-radius: 14px;
      }
      .lab-badge {
        border-radius: 14px 14px 0 0;
        border-right: none;
        border-bottom: 1px solid rgba(255, 255, 255, 0.07);
        padding: 0.75rem 1rem;
      }
      .lab-content {
        padding: 1rem;
      }
      .lab-action {
        padding: 0 1rem 1rem;
        justify-content: flex-start;
      }
    }
  </style>
</head>

<body>
  <!-- Standard Navbar -->
  <?php include __DIR__ . '/navbar.php'; ?>

  <div class="container py-5">
    
    <!-- Hero Header -->
    <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between mb-4 pb-2 border-bottom border-secondary border-opacity-25">
      <div>
        <h1 class="hero-title">Assignments</h1>
        <p class="text-secondary small mb-0">
          Track assigned vulnerability testing reports, deadlines, and hands-on lab progress.
        </p>
      </div>

      <div class="mt-3 mt-md-0 d-flex align-items-center gap-2">
        <?php if ($isAdmin): ?>
          <button type="button" class="btn btn-create-task d-flex align-items-center gap-2" data-bs-toggle="modal" data-bs-target="#createTaskModal">
            <i class="bi bi-plus-circle-fill"></i>
            <span>Create New Assignment</span>
          </button>
        <?php endif; ?>
      </div>
    </div>

    <!-- Active Assignments Feed -->
    <?php if (empty($tasks)): ?>
      <div class="text-center py-5 task-container-card">
        <i class="bi bi-clipboard-check text-muted" style="font-size: 3.5rem; opacity: 0.4;"></i>
        <h4 class="mt-3 text-white">No Active Assignments</h4>
        <p class="text-secondary small mb-0">There are currently no assigned tasks. Check back soon!</p>
      </div>
    <?php else: ?>
      <?php foreach ($tasks as $t): ?>
        <?php 
          $labs = json_decode($t['labs_json'] ?? '[]', true) ?: [];
          $assignedUsers = $t['assigned_users'] ?? '';
          $isAssignedToMe = $isLoggedIn && !empty($currentUsername) && (stripos($assignedUsers, $currentUsername) !== false);
          $subDate = strtotime($t['submission_date']);
          $formattedDate = date('d-m-Y H:i:s', $subDate);
          $isoDate = date('c', $subDate);
        ?>
        <div class="task-container-card" id="task-card-<?= $t['id'] ?>">
          
          <!-- Top Task Info & Status Header -->
          <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3 mb-4 pb-3 border-bottom border-secondary border-opacity-25">
            <div>
              <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                <span class="badge" style="background: rgba(56, 189, 248, 0.15); color: #38bdf8; border: 1px solid rgba(56, 189, 248, 0.3); font-size: 12px;">
                  <i class="bi bi-folder2-open me-1"></i> <?= htmlspecialchars($t['category_name']) ?>
                </span>

                <?php if ($isAssignedToMe): ?>
                  <span class="my-assignment-badge">
                    <i class="bi bi-person-check-fill me-1"></i> Assigned to You
                  </span>
                <?php endif; ?>
              </div>

              <h2 class="fw-bold text-white mb-2" style="font-size: 1.6rem;"><?= htmlspecialchars($t['title']) ?></h2>
              
              <div class="d-flex flex-wrap align-items-center gap-2">
                <span class="text-secondary small fw-medium">Assigned to:</span>
                <?php 
                  $tags = preg_split('/[\s,]+/', $assignedUsers, -1, PREG_SPLIT_NO_EMPTY);
                  foreach ($tags as $tag): 
                    $cleanTag = ltrim($tag, '@');
                ?>
                  <span class="user-tag-pill">
                    <i class="bi bi-at text-info"></i> <?= htmlspecialchars($cleanTag) ?>
                  </span>
                <?php endforeach; ?>
              </div>
            </div>

            <!-- Live Countdown Timer -->
            <div class="d-flex flex-column align-items-lg-end">
              <div class="text-secondary small fw-semibold mb-2">
                <i class="bi bi-clock-history text-warning me-1"></i> Submission Deadline: <span class="text-white"><?= $formattedDate ?></span>
              </div>
              <div class="timer-hud" data-deadline="<?= $isoDate ?>">
                <div class="timer-unit">
                  <span class="timer-value timer-days">00</span>
                  <div class="timer-label">Days</div>
                </div>
                <div class="timer-sep">:</div>
                <div class="timer-unit">
                  <span class="timer-value timer-hours">00</span>
                  <div class="timer-label">Hours</div>
                </div>
                <div class="timer-sep">:</div>
                <div class="timer-unit">
                  <span class="timer-value timer-mins">00</span>
                  <div class="timer-label">Mins</div>
                </div>
                <div class="timer-sep">:</div>
                <div class="timer-unit">
                  <span class="timer-value timer-secs">00</span>
                  <div class="timer-label">Secs</div>
                </div>
              </div>
            </div>
          </div>

          <!-- Description & Quality Guidelines Box -->
          <?php if (!empty($t['description'])): ?>
            <div class="p-3 mb-4 rounded-3" style="background: rgba(7, 11, 20, 0.7); border: 1px solid rgba(56, 189, 248, 0.2); border-left: 4px solid #38bdf8; font-size: 13.5px; color: #cbd5e1; box-shadow: 0 4px 20px rgba(0,0,0,0.3);">
              <div class="d-flex align-items-center justify-content-between mb-2">
                <div class="fw-bold text-white small d-flex align-items-center gap-2">
                  <i class="bi bi-shield-lock-fill text-info fs-6"></i> Assignment Scope & Instructions
                </div>
                <span class="badge py-1 px-2 small" style="background: rgba(56, 189, 248, 0.15); border: 1px solid rgba(56, 189, 248, 0.3); color: #38bdf8; font-size: 11px;">
                  <i class="bi bi-github me-1"></i> GitHub Submission Required
                </span>
              </div>
              <?= formatAssignmentInstructions($t['description']) ?>
            </div>
          <?php endif; ?>

          <!-- Category Banner & Required Vulnerability Labs List matching index.php -->
          <h3 class="category-title">
            <span><?= htmlspecialchars($t['category_name']) ?></span>
            <?php if ($isAdmin): ?>
              <button type="button" class="btn btn-outline-danger btn-sm py-0 px-2 delete-task-btn" data-task-id="<?= $t['id'] ?>" style="border-radius: 6px; font-size: 11.5px; border-color: rgba(239, 68, 68, 0.4);">
                <i class="bi bi-trash-fill me-1"></i> Delete Assignment
              </button>
            <?php endif; ?>
          </h3>

          <div class="labs-list">
            <?php foreach ($labs as $lab): ?>
              <?php 
                $diff = strtolower($lab['difficulty'] ?? 'easy');
                $diffClass = '';
                if ($diff === 'medium') $diffClass = 'medium';
                else if ($diff === 'hard') $diffClass = 'hard';

                $isTraining = (strtolower($lab['type'] ?? '') === 'training');
              ?>
              <div class="lab-card" data-lab-href="<?= htmlspecialchars($lab['link'] ?? '') ?>">
                <div class="lab-badge">
                  <svg viewBox="0 0 24 24"><path d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path></svg>
                  <?= htmlspecialchars($lab['badge'] ?? 'LAB') ?>
                </div>

                <div class="lab-content">
                  <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;">
                    <span class="difficulty-tag <?= $diffClass ?>"><?= htmlspecialchars($lab['difficulty'] ?? 'Easy') ?></span>
                    <span class="difficulty-tag" style="background:<?= $isTraining ? '#0D9488' : '#6366F1' ?>;color:#fff;">
                      <?= htmlspecialchars($lab['type'] ?? ($isTraining ? 'Training' : 'Real World')) ?>
                    </span>
                  </div>

                  <div class="lab-title">
                    <?= htmlspecialchars($lab['title'] ?? '') ?>
                    <?php if (!empty($lab['report_num']) && !empty($lab['report_url'])): ?>
                      <a href="<?= htmlspecialchars($lab['report_url']) ?>" target="_blank" rel="noopener noreferrer" class="report-badge">
                        <?= htmlspecialchars($lab['report_num']) ?>
                      </a>
                    <?php endif; ?>
                    <svg viewBox="0 0 24 24"><path d="M5 12h14M12 5l7 7-7 7"></path></svg>
                  </div>
                </div>

                <div class="lab-action">
                  <a href="<?= htmlspecialchars($lab['link'] ?? '#') ?>" class="btn-ACCESS" target="_blank">
                    <svg viewBox="0 0 24 24"><path d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path></svg>
                    ACCESS THE LAB
                  </a>
                  <button type="button" class="btn-star-toggle" data-lab-id="<?= htmlspecialchars($lab['link'] ?? '') ?>" title="Bookmark lab">
                    <i class="bi bi-star"></i>
                  </button>
                  <button type="button" class="btn-solved-toggle" data-lab-id="<?= htmlspecialchars($lab['link'] ?? '') ?>" title="Click to mark as solved">
                    <i class="bi bi-circle me-1"></i><span>Mark Solved</span>
                  </button>
                </div>
              </div>
            <?php endforeach; ?>
          </div>

        </div>
      <?php endforeach; ?>
    <?php endif; ?>

  </div>

  <!-- Admin Modal: Create New Assignment -->
  <?php if ($isAdmin): ?>
    <div class="modal fade" id="createTaskModal" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content" style="background: #0f172a; border: 1px solid rgba(255, 255, 255, 0.12); border-radius: 18px; color: #fff; box-shadow: 0 20px 50px rgba(0,0,0,0.7);">
          <div class="modal-header border-bottom border-secondary border-opacity-25 pb-3">
            <h5 class="modal-title fw-bold text-white d-flex align-items-center gap-2">
              <i class="bi bi-plus-square-fill text-info"></i> Create & Assign New Assignment
            </h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>

          <form id="createTaskForm">
            <div class="modal-body p-4">
              <div id="createTaskAlert" class="alert alert-danger py-2 px-3 small" style="display: none; border-radius: 8px;"></div>

              <div class="mb-3">
                <label class="form-label small fw-semibold text-light mb-1">Assignment Title</label>
                <input type="text" id="taskTitleInput" class="form-control" placeholder="e.g. Create report for all of: HTML Injection (HTMLI)" required style="background:#070b14; border:1px solid rgba(255,255,255,0.12); color:#fff;">
              </div>

              <div class="row g-3 mb-3">
                <div class="col-md-6">
                  <label class="form-label small fw-semibold text-light mb-1">Category Name</label>
                  <input type="text" id="taskCategoryInput" class="form-control" placeholder="e.g. HTML Injection (HTMLI)" required style="background:#070b14; border:1px solid rgba(255,255,255,0.12); color:#fff;">
                </div>
                <div class="col-md-6">
                  <label class="form-label small fw-semibold text-light mb-1">Tag Users (with @)</label>
                  <input type="text" id="taskUsersInput" class="form-control" placeholder="e.g. @Tkamer @Anil" required style="background:#070b14; border:1px solid rgba(255,255,255,0.12); color:#fff;">
                </div>
              </div>

              <div class="mb-3">
                <label class="form-label small fw-semibold text-light mb-1">Submission Deadline (Date & Time)</label>
                <input type="datetime-local" id="taskDateInput" class="form-control" required style="background:#070b14; border:1px solid rgba(255,255,255,0.12); color:#fff;">
              </div>

              <div class="mb-3">
                <label class="form-label small fw-semibold text-light mb-1">Assignment Scope & Instructions</label>
                <textarea id="taskDescInput" class="form-control" rows="3" placeholder="Provide assignment instructions..." rows="6" style="background:#070b14; border:1px solid rgba(255,255,255,0.12); color:#fff;"></textarea>
              </div>

              <div class="mb-2">
                <label class="form-label small fw-semibold text-light mb-1">Labs JSON (or leave default for auto-format)</label>
                <textarea id="taskLabsJsonInput" class="form-control font-monospace" rows="4" placeholder='[{"badge":"LAB 1","difficulty":"Easy","type":"Training","title":"Reflected XSS","link":"/xss1","report_num":"","report_url":""}]' style="background:#070b14; border:1px solid rgba(255,255,255,0.12); color:#38bdf8; font-size:12px;"></textarea>
                <div class="text-secondary small mt-1">Leave empty or paste custom array of lab objects.</div>
              </div>
            </div>

            <div class="modal-footer border-top border-secondary border-opacity-25 p-3">
              <button type="button" class="btn btn-outline-secondary px-3" data-bs-dismiss="modal" style="border-radius: 8px;">Cancel</button>
              <button type="submit" class="btn btn-create-task px-4">Create Assignment &rarr;</button>
            </div>
          </form>
        </div>
      </div>
    </div>
  <?php endif; ?>

  <!-- Standard Footer -->
  <?php include __DIR__ . '/../footer/footer.php'; ?>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0/dist/js/bootstrap.bundle.min.js"
    integrity="sha384-A3rJD856KowSb7dwlZdYEkO39Gagi7vIsF0jrRAoQmDKKtQBHUuLZ9AsSv4jD4Xa"
    crossorigin="anonymous"></script>

  <!-- Live Countdown Timer & Lab State Sync Script -->
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      // 1. Live Countdown Timer Engine
      const timerElements = document.querySelectorAll('.timer-hud');
      
      function updateAllTimers() {
        const now = new Date().getTime();

        timerElements.forEach(timer => {
          const deadlineStr = timer.getAttribute('data-deadline');
          if (!deadlineStr) return;

          const deadline = new Date(deadlineStr).getTime();
          const distance = deadline - now;

          const daysEl = timer.querySelector('.timer-days');
          const hoursEl = timer.querySelector('.timer-hours');
          const minsEl = timer.querySelector('.timer-mins');
          const secsEl = timer.querySelector('.timer-secs');

          if (distance <= 0) {
            if (daysEl) daysEl.textContent = '00';
            if (hoursEl) hoursEl.textContent = '00';
            if (minsEl) minsEl.textContent = '00';
            if (secsEl) secsEl.textContent = '00';
            timer.style.borderColor = '#f43f5e';
            return;
          }

          const days = Math.floor(distance / (1000 * 60 * 60 * 24));
          const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
          const mins = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
          const secs = Math.floor((distance % (1000 * 60)) / 1000);

          if (daysEl) daysEl.textContent = days < 10 ? '0' + days : days;
          if (hoursEl) hoursEl.textContent = hours < 10 ? '0' + hours : hours;
          if (minsEl) minsEl.textContent = mins < 10 ? '0' + mins : mins;
          if (secsEl) secsEl.textContent = secs < 10 ? '0' + secs : secs;
        });
      }

      updateAllTimers();
      setInterval(updateAllTimers, 1000);

      // 2. User Lab Tracking Sync (Bookmark & Solved)
      let solvedLabs = [];
      let bookmarkedLabs = [];

      function fetchUserState() {
        fetch('portal.php?action=get_state')
          .then(res => res.json())
          .then(res => {
            if (res.success && res.data) {
              solvedLabs = res.data.solvedLabs || [];
              bookmarkedLabs = res.data.bookmarkedLabs || [];
              updateAllTaskLabButtons();
            }
          });
      }

      function updateAllTaskLabButtons() {
        document.querySelectorAll('.lab-card').forEach(card => {
          const labId = card.getAttribute('data-lab-href');
          if (!labId) return;

          const starBtn = card.querySelector('.btn-star-toggle');
          if (starBtn) {
            if (bookmarkedLabs.includes(labId)) {
              starBtn.classList.add('bookmarked');
              starBtn.innerHTML = `<i class="bi bi-star-fill text-warning"></i>`;
            } else {
              starBtn.classList.remove('bookmarked');
              starBtn.innerHTML = `<i class="bi bi-star"></i>`;
            }
          }

          const solvedBtn = card.querySelector('.btn-solved-toggle');
          if (solvedBtn) {
            if (solvedLabs.includes(labId)) {
              solvedBtn.classList.add('solved');
              solvedBtn.title = 'Click to mark as unsolved';
              solvedBtn.innerHTML = `<i class="bi bi-check-circle-fill me-1"></i><span>Solved</span>`;
            } else {
              solvedBtn.classList.remove('solved');
              solvedBtn.title = 'Click to mark as solved';
              solvedBtn.innerHTML = `<i class="bi bi-circle me-1"></i><span>Mark Solved</span>`;
            }
          }
        });
      }

      // Bookmark click listener
      document.addEventListener('click', function(e) {
        const starBtn = e.target.closest('.btn-star-toggle');
        if (starBtn) {
          e.preventDefault();
          const targetLabId = starBtn.getAttribute('data-lab-id');
          const formData = new FormData();
          formData.append('lab_id', targetLabId);

          fetch('portal.php?action=toggle_bookmark', { method: 'POST', body: formData })
            .then(res => res.json())
            .then(res => {
              if (res.requireLogin) {
                if (typeof openLoginModal === 'function') openLoginModal();
                return;
              }
              if (res.success && res.data) {
                solvedLabs = res.data.solvedLabs || [];
                bookmarkedLabs = res.data.bookmarkedLabs || [];
                updateAllTaskLabButtons();
              }
            });
        }

        // Solved click listener
        const solvedBtn = e.target.closest('.btn-solved-toggle');
        if (solvedBtn) {
          e.preventDefault();
          const targetLabId = solvedBtn.getAttribute('data-lab-id');
          const formData = new FormData();
          formData.append('lab_id', targetLabId);

          fetch('portal.php?action=toggle_solved', { method: 'POST', body: formData })
            .then(res => res.json())
            .then(res => {
              if (res.requireLogin) {
                if (typeof openLoginModal === 'function') openLoginModal();
                return;
              }
              if (res.success && res.data) {
                solvedLabs = res.data.solvedLabs || [];
                bookmarkedLabs = res.data.bookmarkedLabs || [];
                updateAllTaskLabButtons();
              }
            });
        }

        // Delete Assignment listener (Admin)
        const delBtn = e.target.closest('.delete-task-btn');
        if (delBtn) {
          if (!confirm('Are you sure you want to delete this assignment?')) return;
          const taskId = delBtn.getAttribute('data-task-id');
          const formData = new FormData();
          formData.append('assignment_id', taskId);

          fetch('assignments.php?action=delete_assignment', { method: 'POST', body: formData })
            .then(res => res.json())
            .then(res => {
              if (res.success) {
                location.reload();
              } else {
                alert(res.message || 'Failed to delete assignment.');
              }
            });
        }
      });

      // Auto-set 48h / 2 days default deadline when opening Create Assignment Modal
      const createTaskModalEl = document.getElementById('createTaskModal');
      if (createTaskModalEl) {
        createTaskModalEl.addEventListener('show.bs.modal', function() {
          const dateInput = document.getElementById('taskDateInput');
          if (dateInput && !dateInput.value) {
            const deadline48h = new Date();
            deadline48h.setDate(deadline48h.getDate() + 2);
            deadline48h.setHours(23, 59, 0, 0);
            const isoLocal = new Date(deadline48h.getTime() - deadline48h.getTimezoneOffset() * 60000).toISOString().slice(0, 16);
            dateInput.value = isoLocal;
          }
        });
      }

      // Admin Create Assignment Form Submission
      const createTaskForm = document.getElementById('createTaskForm');
      if (createTaskForm) {
        createTaskForm.addEventListener('submit', function(e) {
          e.preventDefault();
          const alertEl = document.getElementById('createTaskAlert');
          alertEl.style.display = 'none';

          const formData = new FormData();
          formData.append('title', document.getElementById('taskTitleInput').value.trim());
          formData.append('category_name', document.getElementById('taskCategoryInput').value.trim());
          formData.append('assigned_users', document.getElementById('taskUsersInput').value.trim());
          formData.append('submission_date', document.getElementById('taskDateInput').value);
          formData.append('description', document.getElementById('taskDescInput').value.trim());
          formData.append('labs_json', document.getElementById('taskLabsJsonInput').value.trim());

          fetch('assignments.php?action=create_assignment', { method: 'POST', body: formData })
            .then(res => res.json())
            .then(res => {
              if (res.success) {
                location.reload();
              } else {
                alertEl.textContent = res.message || 'Failed to create assignment.';
                alertEl.style.display = 'block';
              }
            });
        });
      }

      fetchUserState();
    });
  </script>
</body>
</html>
