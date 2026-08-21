<?php

/**
 * Page de profil
 */

// Initialisation de l'application
require_once __DIR__ . '/includes/init.php';

// Vérifier que l'utilisateur est connecté
if (!is_logged_in()) {
    header('Location: ' . BASE_PATH . '/login.php');
    exit;
}

// Vérifier si un ID de profil est spécifié
$profile_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$profile_id) {
    // Rediriger vers la page de connexion si aucun ID n'est spécifié et que l'utilisateur n'est pas connecté
    if (!is_logged_in()) {
        header('Location: ' . BASE_PATH . '/login.php');
        exit;
    }
    $profile_id = $_SESSION['user_id'];
    header("Location: " . BASE_PATH . "/profile.php?id=$profile_id");
    exit;
}

// Récupérer les informations du profil
$user = get_user_by_id($profile_id);
if (!$user) {
    // Rediriger vers une page d'erreur 404
    header('HTTP/1.0 404 Not Found');
    include_once 'includes/error_pages/404.php';
    exit;
}

// Récupérer les statistiques de l'utilisateur
try {
    global $pdo;

    // En cas d'erreur, on initialise les compteurs à 0
    $user_books_count = 0;
    $user_comments_count = 0;
    $user_favorites_count = 0;
    $user_likes_count = 0;
    $user_book_likes_count = 0;
    
    // Nombre de livres (non supprimés)
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM books WHERE user_id = ? AND (is_deleted = 0 OR is_deleted IS NULL)");
    $stmt->execute([$profile_id]);
    $user_books_count = $stmt->fetchColumn();
    
    // Nombre de commentaires (non supprimés et non supprimés par un admin)
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM comments WHERE user_id = ? AND is_deleted = 0 AND is_admin_deleted = 0");
    $stmt->execute([$profile_id]);
    $user_comments_count = $stmt->fetchColumn();
    
    // Nombre de livres en favoris (vérifie si la table existe d'abord)
    $user_favorites_count = 0;
    try {
        $stmt = $pdo->prepare("
            SELECT COUNT(*) 
            FROM book_likes bl
            JOIN books b ON bl.book_id = b.id 
            WHERE bl.user_id = ? AND (b.is_deleted = 0 OR b.is_deleted IS NULL)
        ");
        $stmt->execute([$profile_id]);
        $user_favorites_count = $stmt->fetchColumn();
    } catch (Exception $e) {
        error_log('Erreur lors de la récupération des livres aimés: ' . $e->getMessage());
    }
    
    // Nombre de j'aime reçus sur les commentaires (non supprimés et non supprimés par un admin)
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM comment_likes cl 
                          JOIN comments c ON cl.comment_id = c.id 
                          WHERE c.user_id = ? AND c.is_deleted = 0 AND c.is_admin_deleted = 0");
    $stmt->execute([$profile_id]);
    $user_likes_count = $stmt->fetchColumn();
    
    // Nombre de j'aime donnés aux livres (vérifie si la table existe d'abord)
    $user_book_likes_count = 0;
    try {
        // Vérifier si la table book_likes existe
        $tableExists = $pdo->query("SHOW TABLES LIKE 'book_likes'")->rowCount() > 0;
        
        if ($tableExists) {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM book_likes bl 
                                JOIN books b ON bl.book_id = b.id 
                                WHERE bl.user_id = ? AND (b.is_deleted = 0 OR b.is_deleted IS NULL)");
            $stmt->execute([$profile_id]);
            $user_book_likes_count = $stmt->fetchColumn();
        }
    } catch (Exception $e) {
        // En cas d'erreur, on continue avec 0 comme valeur par défaut
        error_log('Erreur lors de la récupération des j\'aime donnés: ' . $e->getMessage());
    }
    
} catch (Exception $e) {
    error_log('Erreur lors de la récupération des statistiques: ' . $e->getMessage());
}

// Définir le titre de la page
$page_title = "Profil de " . htmlspecialchars($user['username']);

// Inclure le header
include_once 'templates/header.php';
?>

<div class="container mx-auto py-8">
    <div class="bg-white rounded-lg shadow-md p-6">
        <div class="flex flex-col md:flex-row items-center">
            <div class="w-32 h-32 rounded-full bg-gray-200 flex items-center justify-center mb-4 md:mb-0 md:mr-6">
                <i class="fas fa-user text-6xl text-gray-400"></i>
            </div>
            <div class="text-center md:text-left">
                <h1 class="text-2xl font-bold"><?= htmlspecialchars($user['username']) ?></h1>
                <p class="text-gray-600">Membre depuis <?= format_date($user['created_at'], 'F Y') ?></p>
                
                <?php if (is_logged_in() && ($_SESSION['user_id'] === $user['id'] || is_admin())): ?>
                    <div class="mt-4">
                        <a href="<?= url("/profile.php?id=$profile_id") ?>" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 transition">
                            <i class="fas fa-edit mr-2"></i>Modifier le profil
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="mt-8">
            <h2 class="text-xl font-semibold mb-4">À propos</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <h3 class="font-medium text-gray-700">Informations</h3>
                    <ul class="mt-2 space-y-2">
                        <li class="flex items-center">
                            <i class="fas fa-envelope text-gray-500 w-5 mr-2"></i>
                            <span><?= htmlspecialchars($user['email']) ?></span>
                        </li>
                        <?php if (!empty($user['location'])): ?>
                        <li class="flex items-center">
                            <i class="fas fa-map-marker-alt text-gray-500 w-5 mr-2"></i>
                            <span><?= htmlspecialchars($user['location']) ?></span>
                        </li>
                        <?php endif; ?>
                        <?php if (!empty($user['website'])): ?>
                        <li class="flex items-center">
                            <i class="fas fa-globe text-gray-500 w-5 mr-2"></i>
                            <a href="<?= htmlspecialchars($user['website']) ?>" target="_blank" class="text-blue-600 hover:underline">
                                <?= htmlspecialchars($user['website']) ?>
                            </a>
                        </li>
                        <?php endif; ?>
                    </ul>
                </div>
                
                <?php if (!empty($user['bio'])): ?>
                <div>
                    <h3 class="font-medium text-gray-700">Biographie</h3>
                    <p class="mt-2 text-gray-600"><?= nl2br(htmlspecialchars($user['bio'])) ?></p>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Section des statistiques -->
        <div class="mt-8">
            <h2 class="text-xl font-semibold mb-4">Statistiques</h2>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="bg-gray-50 p-4 rounded-lg text-center hover:shadow-md transition-shadow">
                    <div class="text-2xl font-bold text-blue-600"><?= $user_books_count ?? 0 ?></div>
                    <div class="text-sm text-gray-500">
                        <i class="fas fa-book"></i> Livres publiés
                    </div>
                </div>
                <div class="bg-gray-50 p-4 rounded-lg text-center hover:shadow-md transition-shadow">
                    <div class="text-2xl font-bold text-purple-600"><?= $user_comments_count ?? 0 ?></div>
                    <div class="text-sm text-gray-500">
                        <i class="fas fa-comment"></i> Commentaires
                    </div>
                </div>
                <div class="bg-gray-50 p-4 rounded-lg text-center hover:shadow-md transition-shadow">
                    <div class="text-2xl font-bold text-green-600"><?= $user_favorites_count ?? 0 ?></div>
                    <div class="text-sm text-gray-500">
                        <i class="fas fa-heart"></i> Favoris
                    </div>
                </div>
                <div class="bg-gray-50 p-4 rounded-lg text-center hover:shadow-md transition-shadow">
                    <div class="text-2xl font-bold text-yellow-600"><?= $user_likes_count ?? 0 ?></div>
                    <div class="text-sm text-gray-500">
                        <i class="fas fa-thumbs-up"></i> J'aime reçus
                    </div>
                </div>
                <div class="bg-gray-50 p-4 rounded-lg text-center hover:shadow-md transition-shadow">
                    <div class="text-2xl font-bold text-red-600"><?= $user_book_likes_count ?? 0 ?></div>
                    <div class="text-sm text-gray-500">
                        <i class="fas fa-star"></i> J'aime donnés
                    </div>
                </div>
                <?php if (isset($user_reviews_count)): ?>
                <div class="bg-gray-50 p-4 rounded-lg text-center hover:shadow-md transition-shadow">
                    <div class="text-2xl font-bold text-indigo-600"><?= $user_reviews_count ?? 0 ?></div>
                    <div class="text-sm text-gray-500">
                        <i class="fas fa-star-half-alt"></i> Avis
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Dernières activités -->
        <div class="mt-8">
            <h2 class="text-xl font-semibold mb-4">Dernières activités</h2>
            <div class="space-y-4">
                <?php if (!empty($recent_activities)): ?>
                    <?php foreach ($recent_activities as $activity): ?>
                        <div class="border-b border-gray-200 pb-4 last:border-0 last:pb-0">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <i class="fas fa-<?= $activity['icon'] ?> text-gray-400"></i>
                                </div>
                                <div class="ml-3">
                                    <p class="text-sm text-gray-600">
                                        <?= $activity['text'] ?>
                                        <span class="text-xs text-gray-400"><?= time_elapsed_string($activity['created_at']) ?></span>
                                    </p>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="text-gray-500 italic">Aucune activité récente.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once 'templates/footer.php'; ?>