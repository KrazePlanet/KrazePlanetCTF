<?php
// templates/header.php — page header
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($L['_meta']['lang'] ?? 'en') ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($config['title'] ?? $L['app']['title']) ?> — <?= htmlspecialchars(basename($currentFull ?? '')) ?></title>
    <link rel="icon" type="image/png" href="includes/favicon.png">
    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Custom styles + dark theme CSS variables -->
    <link href="includes/styles.css" rel="stylesheet">
    <!-- FontAwesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <!-- Translations for JS -->
    <script>const Lang = <?= json_encode($L, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) ?>;</script>
    <!-- Apply theme BEFORE page render — prevents flash of unstyled content -->
    <script>
    (function() {
        var theme = localStorage.getItem('fm_theme');
        if (theme === 'dark') {
            document.documentElement.setAttribute('data-theme', 'dark');
        }
    })();
    </script>
</head>
<body class="bg-light" <?php if (!empty($config['background'])): ?>
    style="background: url('<?= htmlspecialchars($config['background']) ?>'); background-size: cover;"
<?php endif; ?>>
<div class="container my-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0">
            <i class="fa-solid fa-folder-tree me-2 text-primary"></i>
            <?= htmlspecialchars($config['title'] ?? $L['app']['title_full']) ?>
        </h2>
        <!-- Light / dark theme toggle button -->
        <button id="btn-theme-toggle" class="btn btn-outline-secondary" title="<?= htmlspecialchars($L['theme']['dark'] ?? 'Toggle Theme') ?>" aria-label="<?= htmlspecialchars($L['theme']['dark'] ?? 'Toggle Theme') ?>">
            <i id="theme-icon" class="fa-solid fa-moon"></i>
        </button>
    </div>
<?php
/**
 * DISPLAY NOTIFICATIONS
 */
if (!empty($config['filter_error'])) {
    echo '<div class="alert alert-warning alert-dismissible fade show" role="alert">
            <i class="fa-solid fa-triangle-exclamation me-2"></i>' . $config['filter_error'] . '
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
          </div>';
}
?>
