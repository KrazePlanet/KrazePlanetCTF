<!DOCTYPE html>
<html lang="fr" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ReadSphere - <?= $page_title ?? 'Bienvenue' ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    
    <!-- Tailwind CSS -->
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    
    <!-- Favicon -->
    <link rel="apple-touch-icon" sizes="180x180" href="<?= url('public/assets/favicon/apple-touch-icon.png') ?>">
    <link rel="icon" type="image/png" sizes="32x32" href="<?= url('public/assets/favicon/favicon-32x32.png') ?>">
    <link rel="icon" type="image/png" sizes="16x16" href="<?= url('public/assets/favicon/favicon-16x16.png') ?>">
    <link rel="manifest" href="<?= url('public/assets/favicon/site.webmanifest') ?>">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?= url('assets/css/style.css') ?>">
    
    <style>
        /* Réinitialisation des styles de base */
        body, h1, h2, h3, h4, h5, h6, p, ul, ol, li, a {
            margin: 0;
            padding: 0;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
        }
        
        /* Styles spécifiques pour le header */
        .navbar-custom {
            background: linear-gradient(135deg, #1e40af 0%, #1e3a8a 100%);
            padding: 0.5rem 0;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .navbar-custom .navbar-brand {
            font-size: 1.5rem;
            font-weight: 700;
            color: white !important;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .navbar-custom .navbar-brand i {
            font-size: 1.75rem;
        }
        
        .navbar-custom .nav-link {
            color: rgba(255, 255, 255, 0.9) !important;
            text-decoration: none;
            margin: 0 0.25rem;
            padding: 0.5rem 0.75rem;
            border-radius: 0.375rem;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.95rem;
        }
        
        .navbar-custom .nav-link i {
            width: 1.25rem;
            text-align: center;
        }
        
        .navbar-custom .nav-link:hover,
        .navbar-custom .nav-link:focus {
            background-color: rgba(255, 255, 255, 0.15);
            color: white !important;
            transform: translateY(-1px);
        }
        
        .navbar-custom .btn-primary {
            background-color: #3b82f6;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 0.375rem;
            font-weight: 500;
            transition: all 0.2s;
        }
        
        .navbar-custom .btn-primary:hover {
            background-color: #2563eb;
            transform: translateY(-1px);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }
        
        .navbar-custom .dropdown-menu {
            border: none;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            border-radius: 0.5rem;
            padding: 0.5rem 0;
            min-width: 14rem;
        }
        
        .navbar-custom .dropdown-item {
            padding: 0.5rem 1.25rem;
            color: #374151;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            transition: all 0.2s;
        }
        
        .navbar-custom .dropdown-item i {
            width: 1.25rem;
            text-align: center;
            color: #6b7280;
        }
        
        .navbar-custom .dropdown-item:hover,
        .navbar-custom .dropdown-item:focus {
            background-color: #f3f4f6;
            color: #111827;
            transform: translateX(2px);
        }
        
        .navbar-custom .dropdown-divider {
            margin: 0.5rem 0;
            border-color: #e5e7eb;
        }
        
        .badge-notification {
            position: absolute;
            top: 0.25rem;
            right: 0.25rem;
            font-size: 0.6rem;
            padding: 0.2rem 0.4rem;
        }
        
        @media (max-width: 991.98px) {
            .navbar-custom .navbar-nav {
                padding: 1rem 0;
            }
            
            .navbar-custom .nav-item {
                margin: 0.25rem 0;
            }
            
            .navbar-custom .dropdown-menu {
                box-shadow: none;
                border: none;
                padding-left: 1.5rem;
                background-color: rgba(255, 255, 255, 0.05);
            }
        }
    </style>
    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Initialisation des tooltips Bootstrap -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
            
            // Activer les popovers
            var popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
            var popoverList = popoverTriggerList.map(function (popoverTriggerEl) {
                return new bootstrap.Popover(popoverTriggerEl);
            });
        });
    </script>
</head>
<body class="bg-gray-100 flex flex-col min-h-screen">
    <div id="page-container">
        <!-- Barre de navigation -->
        <nav class="navbar navbar-expand-lg navbar-dark navbar-custom">
            <div class="container">
                <a class="navbar-brand" href="<?= url('') ?>">
                    <img src="<?= url('public/assets/logo/logo_remake_bg.png') ?>" alt="ReadSphere Logo" style="height: 50px; width: auto;">
                    <span>ReadSphere</span>
                </a>
                
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                    <i class="fas fa-bars"></i>
                </button>
                
                <div class="collapse navbar-collapse" id="navbarNav">
                    <ul class="navbar-nav me-auto">
                        <li class="nav-item">
                            <a class="nav-link" href="<?= url('') ?>">
                                <i class="fas fa-home"></i>
                                <span>Accueil</span>
                            </a>
                        </li>
                        
                        <?php if (is_logged_in()): ?>
                            <?php 
                            $current_user = current_user();
                            $report_count = function_exists('get_reported_comments_count') ? get_reported_comments_count() : 0;
                            ?>
                            
                            <li class="nav-item">
                                <a class="nav-link" href="<?= url('dashboard.php') ?>">
                                    <i class="fas fa-tachometer-alt"></i>
                                    <span>Tableau de bord</span>
                                </a>
                            </li>
                            
                            <?php if (is_admin()): ?>
                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle position-relative" href="#" id="adminDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="fas fa-shield-alt"></i>
                                    <span>Administration</span>
                                    <?php if ($report_count > 0): ?>
                                        <span class="badge bg-danger badge-notification"><?= $report_count ?></span>
                                    <?php endif; ?>
                                </a>
                                <ul class="dropdown-menu" aria-labelledby="adminDropdown">
                                    <li>
                                        <a class="dropdown-item" href="<?= url('admin/') ?>">
                                            <i class="fas fa-tachometer-alt"></i>
                                            <span>Tableau de bord</span>
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item d-flex justify-content-between align-items-center" href="<?= url('admin/comments_dashboard.php') ?>">
                                            <span>
                                                <i class="fas fa-flag"></i>
                                                Signalements
                                            </span>
                                            <?php if ($report_count > 0): ?>
                                                <span class="badge bg-danger ms-2"><?= $report_count ?></span>
                                            <?php endif; ?>
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="<?= url('admin/moderation_logs.php') ?>">
                                            <i class="fas fa-history"></i>
                                            <span>Historique</span>
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="<?= url('admin/deleted_books.php') ?>">
                                            <i class="fas fa-trash-restore"></i>
                                            <span>Lires supprimés</span>
                                        </a>
                                    </li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li>
                                        <a class="dropdown-item" href="<?= url('admin/users/') ?>">
                                            <i class="fas fa-users"></i>
                                            <span>Utilisateurs</span>
                                        </a>
                                    </li>
                                </ul>
                            </li>
                            <?php endif; ?>
                            
                            <li class="nav-item d-none d-lg-block">
                                <a class="nav-link btn-primary text-white" href="<?= url('add_book.php') ?>">
                                    <i class="fas fa-plus"></i>
                                    <span>Ajouter un livre</span>
                                </a>
                            </li>
                            
                        <?php endif; ?>
                    </ul>
                    
                    <ul class="navbar-nav ms-auto">
                        <?php if (is_logged_in() && $current_user): ?>
                            <!-- Bouton mobile pour ajouter un livre -->
                            <li class="nav-item d-lg-none mb-2">
                                <a class="nav-link btn-primary text-white w-100 text-center" href="<?= url('add_book.php') ?>">
                                    <i class="fas fa-plus"></i>
                                    <span>Ajouter un livre</span>
                                </a>
                            </li>
                            
                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    <div class="position-relative d-inline-block me-2">
                                        <?php if (!empty($current_user['avatar'])): ?>
                                            <img src="<?= htmlspecialchars($current_user['avatar']) ?>" alt="Avatar" class="rounded-circle" width="32" height="32">
                                        <?php else: ?>
                                            <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                                                <?= strtoupper(substr($current_user['username'], 0, 1)) ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <span class="d-none d-lg-inline">
                                        <?= htmlspecialchars($current_user['username']) ?>
                                    </span>
                                </a>
                                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                                    <li>
                                        <a class="dropdown-item" href="<?= url('profile.php?id=' . $current_user['id']) ?>">
                                            <i class="fas fa-user"></i>
                                            <span>Mon profil</span>
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="<?= url('livres-favoris.php') ?>">
                                            <i class="fas fa-heart"></i>
                                            <span>Mes favoris</span>
                                        </a>
                                    </li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li>
                                        <a class="dropdown-item" href="<?= url('settings/account.php') ?>">
                                            <i class="fas fa-cog"></i>
                                            <span>Paramètres</span>
                                        </a>
                                    </li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li>
                                        <a class="dropdown-item text-danger" href="<?= url('logout.php') ?>">
                                            <i class="fas fa-sign-out-alt"></i>
                                            <span>Déconnexion</span>
                                        </a>
                                    </li>
                                </ul>
                            </li>
                        <?php else: ?>
                            <li class="nav-item">
                                <a class="nav-link" href="<?= url('login.php') ?>">
                                    <i class="fas fa-sign-in-alt"></i>
                                    <span>Connexion</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link btn-primary text-white" href="<?= url('signup.php') ?>">
                                    <i class="fas fa-user-plus"></i>
                                    <span>S'inscrire</span>
                                </a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </nav>
        
        <!-- Contenu principal -->
        <main class="container mx-auto p-4 flex-grow">
