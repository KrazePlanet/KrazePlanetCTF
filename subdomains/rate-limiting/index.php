<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rate Limiting & Brute Force Defense Lab</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #0f172a; color: #e2e8f0; min-height: 100vh; display: flex; align-items: center; justify-content: center; font-family: sans-serif; }
        .card-box { background: #1e293b; border: 1px solid #334155; border-radius: 14px; padding: 30px; max-width: 480px; width: 100%; box-shadow: 0 10px 30px rgba(0,0,0,0.5); }
    </style>
</head>
<body>
    <div class="card-box text-center">
        <h3 class="text-white mb-2">🛡️ Rate Limiting Lab</h3>
        <p class="text-muted small mb-4">Test account login brute force protection and IP-based rate limiting bypasses (e.g. X-Forwarded-For, X-Real-IP).</p>
        <form method="POST" action="">
            <input type="text" name="username" class="form-control mb-3 bg-dark text-white border-secondary" placeholder="Username (admin)" required>
            <input type="password" name="password" class="form-control mb-3 bg-dark text-white border-secondary" placeholder="Password" required>
            <button type="submit" class="btn btn-success w-100">Attempt Login</button>
        </form>
    </div>
</body>
</html>