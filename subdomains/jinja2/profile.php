<?php
session_start();

$default_display_name = 'Sophia Chen';
$default_handle = 'sophia_sec';
$default_job_title = 'Principal Security Architect';
$default_org = 'Nexus Cyber Systems';
$default_location = 'Seattle, WA';
$default_website = 'https://sophia-chen.dev';
$default_status = '⚡ Securing cloud infrastructure & optimizing {{user.company}} telemetry';
$default_bio = '<div class="bio-intro">
  <p>👋 Hello! I am <strong>{{user.name}}</strong>, working as <strong>{{user.role}}</strong> at <strong>{{user.company}}</strong> based in {{user.location}}.</p>
  <p>Specializing in cloud workload defense, container escape mitigation, and automated policy enforcement. Currently holding <strong>{{user.reputation}}</strong> community reputation score.</p>
  <div class="bio-skills mt-3">
    <span class="badge bg-primary bg-opacity-25 text-primary border border-primary border-opacity-50 me-1">Python</span>
    <span class="badge bg-primary bg-opacity-25 text-primary border border-primary border-opacity-50 me-1">Kubernetes</span>
    <span class="badge bg-primary bg-opacity-25 text-primary border border-primary border-opacity-50 me-1">Cloud Security</span>
    <span class="badge bg-primary bg-opacity-25 text-primary border border-primary border-opacity-50">DevSecOps</span>
  </div>
</div>';

// Support GET parameters for direct query/URL parameter testing as well!
$status_input = $_GET['status'] ?? ($_SESSION['status_message'] ?? $default_status);
$bio_input = $_GET['bio'] ?? ($_SESSION['bio'] ?? $default_bio);
$handle = $_GET['user'] ?? $default_handle;

// Evaluate with Jinja2
$b64_status = base64_encode($status_input);
$cmd_status = sprintf('python3 %s %s 2>&1', escapeshellarg(__DIR__ . '/evaluator.py'), escapeshellarg($b64_status));
$rendered_status = shell_exec($cmd_status);

$b64_bio = base64_encode($bio_input);
$cmd_bio = sprintf('python3 %s %s 2>&1', escapeshellarg(__DIR__ . '/evaluator.py'), escapeshellarg($b64_bio));
$rendered_bio = shell_exec($cmd_bio);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($default_display_name) ?> (@<?= htmlspecialchars($handle) ?>) — DevSpace Public Profile</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: #070b14;
            color: #f1f5f9;
            min-height: 100vh;
        }
        .public-navbar {
            background: #0b1120;
            border-bottom: 1px solid #1e293b;
            padding: 14px 28px;
        }
        .profile-container {
            max-width: 860px;
            margin: 40px auto;
            background: #0d1527;
            border: 1px solid #1e293b;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 20px 40px rgba(0,0,0,0.5);
        }
        .profile-banner {
            height: 160px;
            background: linear-gradient(135deg, #0e7490 0%, #1e1b4b 50%, #042f2e 100%);
            position: relative;
        }
        .profile-badge-overlay {
            position: absolute;
            top: 16px;
            right: 20px;
            background: rgba(0,0,0,0.6);
            backdrop-filter: blur(8px);
            border: 1px solid rgba(255,255,255,0.15);
            border-radius: 20px;
            padding: 5px 14px;
            font-size: 12px;
            font-weight: 600;
            color: #38bdf8;
        }
        .profile-main-avatar {
            width: 96px;
            height: 96px;
            border-radius: 50%;
            background: linear-gradient(135deg, #06b6d4 0%, #3b82f6 100%);
            border: 4px solid #0d1527;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 32px;
            font-weight: 800;
            color: #ffffff;
            box-shadow: 0 6px 16px rgba(0,0,0,0.5);
        }
        .profile-status-pill {
            background: #111e38;
            border: 1px solid #223554;
            border-radius: 20px;
            padding: 10px 18px;
            font-size: 14px;
            color: #cbd5e1;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 20px;
            max-width: 100%;
            word-break: break-word;
        }
        .profile-bio-box {
            background: #090e1c;
            border: 1px solid #1c273d;
            border-radius: 12px;
            padding: 22px;
            font-size: 14px;
            line-height: 1.7;
            color: #e2e8f0;
            word-break: break-word;
        }
    </style>
</head>
<body>
    <header class="public-navbar d-flex justify-content-between align-items-center">
        <div class="d-flex align-items-center gap-2">
            <i class="bi bi-person-badge-fill text-info fs-4"></i>
            <span class="fw-bold fs-5 text-white">DevSpace</span>
            <span class="text-secondary small ms-2">/ Public Profiles</span>
        </div>
        <div>
            <a href="index.php" class="btn btn-sm btn-outline-info">
                <i class="bi bi-pencil-square me-1"></i> Customize Your Profile
            </a>
        </div>
    </header>

    <div class="container px-3">
        <div class="profile-container">
            <div class="profile-banner">
                <div class="profile-badge-overlay">
                    <i class="bi bi-patch-check-fill text-info me-1"></i> Verified Staff Contributor
                </div>
            </div>

            <div class="p-4 pt-0">
                <div class="d-flex justify-content-between align-items-flex-end" style="margin-top: -50px; margin-bottom: 18px;">
                    <div class="profile-main-avatar">SC</div>
                    <div>
                        <button class="btn btn-outline-light btn-sm px-3" onclick="alert('Follow request sent!')">
                            <i class="bi bi-person-plus-fill me-1"></i> Follow
                        </button>
                    </div>
                </div>

                <h2 class="fw-bold text-white mb-1"><?= htmlspecialchars($default_display_name) ?></h2>
                <div class="text-secondary font-monospace small mb-3">
                    @<?= htmlspecialchars($handle) ?> &bull; <?= htmlspecialchars($default_job_title) ?> at <?= htmlspecialchars($default_org) ?>
                </div>

                <!-- Custom Dynamic Status Greeting -->
                <div class="profile-status-pill">
                    <span><?= $rendered_status ?></span>
                </div>

                <!-- Dynamic Jinja2 Rendered Bio -->
                <div class="profile-bio-box mb-4">
                    <div class="small text-uppercase fw-bold text-muted mb-2" style="font-size: 11px; letter-spacing: 0.5px;">
                        <i class="bi bi-person-lines-fill me-1"></i> Developer Biography
                    </div>
                    <div>
                        <?= $rendered_bio ?>
                    </div>
                </div>

                <div class="d-flex align-items-center gap-4 text-secondary small pt-3 border-top border-dark">
                    <div><i class="bi bi-geo-alt me-1 text-muted"></i> <?= htmlspecialchars($default_location) ?></div>
                    <div><i class="bi bi-link-45deg me-1 text-muted"></i> <a href="#" class="text-info text-decoration-none"><?= htmlspecialchars($default_website) ?></a></div>
                    <div><i class="bi bi-calendar3 me-1 text-muted"></i> Member since October 2023</div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
