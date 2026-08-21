<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Channels &amp; Routing — Courier</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: #09090b; color: #f4f4f5; }
        .courier-nav { background: #141417; border-bottom: 1px solid #27272a; padding: 16px 0; }
    </style>
</head>
<body>
    <nav class="courier-nav">
        <div class="container d-flex justify-content-between align-items-center">
            <a href="index.php" class="text-white text-decoration-none fw-bold fs-5"><i class="bi bi-send-fill text-purple me-2"></i> Courier Channels</a>
            <a href="index.php" class="btn btn-outline-light btn-sm">&larr; Return Home</a>
        </div>
    </nav>
    <div class="container py-5">
        <h1 class="fw-bold fs-2 mb-3">Supported Messaging Providers</h1>
        <p class="text-secondary mb-4">Connect SendGrid, Twilio, Postmark, AWS SES, and Firebase FCM in seconds.</p>
        <div class="row g-4">
            <div class="col-md-4">
                <div class="card bg-dark text-white p-4 border-secondary border-opacity-25 rounded-3">
                    <h5 class="fw-bold mb-1">Email Providers</h5>
                    <p class="text-secondary small">SendGrid, AWS SES, Postmark, Mailgun, SMTP Relay.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card bg-dark text-white p-4 border-secondary border-opacity-25 rounded-3">
                    <h5 class="fw-bold mb-1">SMS &amp; Chat</h5>
                    <p class="text-secondary small">Twilio SMS, MessageBird, Slack Webhooks, Discord Bot.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card bg-dark text-white p-4 border-secondary border-opacity-25 rounded-3">
                    <h5 class="fw-bold mb-1">Push Notifications</h5>
                    <p class="text-secondary small">Firebase Cloud Messaging (FCM), Apple APNs, OneSignal.</p>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
