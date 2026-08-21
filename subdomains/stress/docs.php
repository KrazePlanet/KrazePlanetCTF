<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Documentation — Courier Developer Platform</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: #09090b; color: #f4f4f5; }
        .courier-nav { background: #141417; border-bottom: 1px solid #27272a; padding: 16px 0; }
        .code-block { background: #000000; border: 1px solid #27272a; border-radius: 8px; padding: 18px; font-family: 'JetBrains Mono', monospace; font-size: 13px; color: #38bdf8; }
    </style>
</head>
<body>
    <nav class="courier-nav">
        <div class="container d-flex justify-content-between align-items-center">
            <a href="index.php" class="text-white text-decoration-none fw-bold fs-5"><i class="bi bi-send-fill text-purple me-2"></i> Courier API Docs</a>
            <a href="index.php" class="btn btn-outline-light btn-sm">&larr; Return Home</a>
        </div>
    </nav>

    <div class="container py-5">
        <h1 class="fw-bold fs-2 mb-3">Quickstart &bull; Send Your First Multi-Channel Notification</h1>
        <p class="text-secondary mb-4">Integrate Courier into your Python, Node.js, or Go backend services with 4 lines of code.</p>

        <div class="code-block mb-4">
from courier.client import Courier<br><br>
client = Courier(auth_token="YOUR_AUTH_TOKEN")<br><br>
resp = client.send_message(<br>
&nbsp;&nbsp;&nbsp;&nbsp;message={<br>
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;"to": {"email": "user@example.com"},<br>
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;"template": "WELCOME_NEW_USER",<br>
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;"data": {"name": "Alex", "plan": "Pro Tier"}<br>
&nbsp;&nbsp;&nbsp;&nbsp;}<br>
)<br>
print(resp['requestId'])
        </div>
    </div>
</body>
</html>
