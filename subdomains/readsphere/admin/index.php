<?php

/**
 * Tableau de bord administrateur
 * 
 * Affiche les statistiques et les activités récentes pour les administrateurs
 */
 
// Inclure le fichier d'initialisation
require_once __DIR__ . '/../includes/init.php';


// Vérifier si l'utilisateur est connecté et est un administrateur
if (!is_logged_in() || !is_admin()) {   
    // Construire l'URL de redirection complète
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
    $host = $_SERVER['HTTP_HOST'];
    $basePath = rtrim(dirname(dirname($_SERVER['SCRIPT_NAME'])), '/\\');
    $redirectUrl = $protocol . $host . $basePath . '/login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']);
    
    header('Location: ' . $redirectUrl);
    exit;
}

// Récupérer les statistiques
$reported_comments = get_reported_comments_count();
$pending_reports = get_pending_reports_count();
$total_users = get_total_users_count();
$new_users_today = get_new_users_count('today');
$total_books = get_total_books_count();
$pending_books = get_pending_books_count();
$total_comments = get_total_comments_count();
$recent_activities = get_recent_admin_activities(5);

$page_title = 'Tableau de bord administrateur';
require_once __DIR__ . '/../templates/header.php';
?>

<div class="container mx-auto px-4 py-8">
    <div class="flex justify-between items-center mb-8">
        <h1 class="text-3xl font-bold text-gray-900">Tableau de bord administrateur</h1>
        <span class="text-sm text-gray-500">
            <i class="far fa-clock mr-1"></i> 
            <?= date('d/m/Y H:i') ?>
        </span>
    </div>
    
    <!-- Cartes de statistiques -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <!-- Utilisateurs -->
        <div class="bg-white rounded-lg border border-gray-200 shadow overflow-hidden">
            <div class="p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-500">Utilisateurs</p>
                        <p class="text-2xl font-bold text-gray-900"><?= number_format($total_users) ?></p>
                        <p class="text-xs text-green-600 font-medium">
                            +<?= $new_users_today ?> aujourd'hui
                        </p>
                    </div>
                    <div class="p-3 rounded-full bg-blue-100 text-blue-600">
                        <i class="fas fa-users text-xl"></i>
                    </div>
                </div>
                <a href="users/" class="mt-4 inline-flex items-center text-sm font-medium text-blue-600 hover:text-blue-800">
                    Gérer les utilisateurs
                    <i class="fas fa-arrow-right ml-1"></i>
                </a>
            </div>
        </div>

        <!-- Livres -->
        <div class="bg-white rounded-lg border border-gray-200 shadow overflow-hidden">
            <div class="p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-500">Livres</p>
                        <p class="text-2xl font-bold text-gray-900"><?= number_format($total_books) ?></p>
                        <?php if ($pending_books > 0): ?>
                        <p class="text-xs text-red-600 font-medium">
                            <?= $pending_books ?> en attente
                        </p>
                        <?php endif; ?>
                    </div>
                    <div class="p-3 rounded-full bg-green-100 text-green-600">
                        <i class="fas fa-book text-xl"></i>
                    </div>
                </div>
                <a href="books/" class="mt-4 inline-flex items-center text-sm font-medium text-blue-600 hover:text-blue-800">
                    Gérer les livres
                    <i class="fas fa-arrow-right ml-1"></i>
                </a>
            </div>
        </div>

        <!-- Commentaires -->
        <div class="bg-white rounded-lg border border-gray-200 shadow overflow-hidden">
            <div class="p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-500">Commentaires</p>
                        <p class="text-2xl font-bold text-gray-900"><?= number_format($total_comments) ?></p>
                        <?php if ($reported_comments > 0): ?>
                        <p class="text-xs text-red-600 font-medium">
                            <?= $reported_comments ?> signalés
                        </p>
                        <?php endif; ?>
                    </div>
                    <div class="p-3 rounded-full bg-purple-100 text-purple-600">
                        <i class="fas fa-comments text-xl"></i>
                    </div>
                </div>
                <a href="comments/" class="mt-4 inline-flex items-center text-sm font-medium text-blue-600 hover:text-blue-800">
                    Gérer les commentaires
                    <i class="fas fa-arrow-right ml-1"></i>
                </a>
            </div>
        </div>

        <!-- Signalements -->
        <div class="bg-white rounded-lg border border-gray-200 shadow overflow-hidden">
            <div class="p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-500">Signalements</p>
                        <p class="text-2xl font-bold text-gray-900"><?= $pending_reports ?></p>
                        <p class="text-xs text-gray-500">en attente de modération</p>
                    </div>
                    <div class="p-3 rounded-full bg-yellow-100 text-yellow-600">
                        <i class="fas fa-flag text-xl"></i>
                    </div>
                </div>
                <a href="reports/" class="mt-4 inline-flex items-center text-sm font-medium text-blue-600 hover:text-blue-800">
                    Voir les signalements
                    <i class="fas fa-arrow-right ml-1"></i>
                </a>
            </div>
        </div>
    </div>

    <!-- Dernières activités -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 bg-white rounded-lg border border-gray-200 shadow overflow-hidden">
            <div class="p-6">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-lg font-medium text-gray-900">Activités récentes</h2>
                    <a href="activities/" class="text-sm text-blue-600 hover:text-blue-800">Voir tout</a>
                </div>
                <div class="space-y-4">
                    <?php if (!empty($recent_activities)): ?>
                        <?php foreach ($recent_activities as $activity): ?>
                        <div class="flex items-start pb-4 border-b border-gray-100 last:border-0 last:pb-0">
                            <div class="flex-shrink-0 h-10 w-10 rounded-full bg-gray-100 flex items-center justify-center mr-3">
                                <i class="fas <?= htmlspecialchars($activity['icon']) ?> text-gray-500"></i>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-gray-900">
                                    <?= htmlspecialchars($activity['action']) ?>
                                </p>
                                <p class="text-sm text-gray-500">
                                    <?= htmlspecialchars($activity['details']) ?>
                                </p>
                                <p class="text-xs text-gray-400 mt-1">
                                    <i class="far fa-clock mr-1"></i>
                                    <?= time_elapsed_string($activity['created_at']) ?>
                                </p>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="text-sm text-gray-500 text-center py-4">Aucune activité récente</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Actions rapides -->
        <div class="bg-white rounded-lg border border-gray-200 shadow overflow-hidden">
            <div class="p-6">
                <h2 class="text-lg font-medium text-gray-900 mb-4">Actions rapides</h2>
                <div class="space-y-3">
                    <a href="books/add.php" class="flex items-center p-3 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors">
                        <div class="bg-blue-100 p-2 rounded-full mr-3">
                            <i class="fas fa-plus text-blue-600"></i>
                        </div>
                        <span>Ajouter un livre</span>
                    </a>
                    <a href="users/add.php" class="flex items-center p-3 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors">
                        <div class="bg-green-100 p-2 rounded-full mr-3">
                            <i class="fas fa-user-plus text-green-600"></i>
                        </div>
                        <span>Créer un utilisateur</span>
                    </a>
                    <a href="settings/" class="flex items-center p-3 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors">
                        <div class="bg-purple-100 p-2 rounded-full mr-3">
                            <i class="fas fa-cog text-purple-600"></i>
                        </div>
                        <span>Paramètres du site</span>
                    </a>
                    <a href="backup/" class="flex items-center p-3 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors">
                        <div class="bg-yellow-100 p-2 rounded-full mr-3">
                            <i class="fas fa-database text-yellow-600"></i>
                        </div>
                        <span>Sauvegarder la base de données</span>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../templates/footer.php'; ?>
