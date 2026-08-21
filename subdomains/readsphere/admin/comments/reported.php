
<?php

/**
 * Gestion des signalements de commentaires
 * 
 * Affiche les commentaires signalés et permet de les gérer
 */

// Activer l'affichage des erreurs
// error_reporting(E_ALL);
// ini_set('display_errors', 1);

// Activer la journalisation des erreurs
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../../logs/error.log');

// Créer le répertoire de logs s'il n'existe pas
if (!file_exists(__DIR__ . '/../../logs')) {
    mkdir(__DIR__ . '/../../logs', 0755, true);
}

// Définir le chemin de base s'il n'est pas déjà défini
if (!defined('BASE_PATH')) {
    $basePath = rtrim($_ENV['APP_URL'] ?? '', '/');
    define('BASE_PATH', $basePath);
}

// Fonction de journalisation
function log_debug($message, $data = null) {
    $log_message = '[' . date('Y-m-d H:i:s') . '] ' . $message . PHP_EOL;
    if ($data !== null) {
        $log_message .= 'Data: ' . print_r($data, true) . PHP_EOL;
    }
    error_log($log_message, 3, __DIR__ . '/../../logs/debug.log');
}

// Journaliser la requête actuelle
log_debug('Requête reçue', [
    'URI' => $_SERVER['REQUEST_URI'],
    'Méthode' => $_SERVER['REQUEST_METHOD'],
    'Session ID' => session_id(),
    'Session data' => isset($_SESSION) ? $_SESSION : 'Session non initialisée',
    'POST data' => $_POST,
    'GET data' => $_GET
]);

// Inclure les fichiers nécessaires
require_once __DIR__ . '/../../includes/init.php';

// Initialiser la variable $base_path si elle n'existe pas
$base_path = defined('BASE_PATH') ? BASE_PATH : '/ReadSphere';

// Vérifier si l'utilisateur est connecté
log_debug('Vérification de la connexion utilisateur');
if (!function_exists('is_logged_in')) {
    log_debug('La fonction is_logged_in n\'existe pas');
    header('Location: ' . $base_path . '/login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    exit;
}

if (!is_logged_in()) {
    log_debug('Utilisateur non connecté', [
        'user_id' => $_SESSION['user_id'] ?? 'Non défini',
        'last_activity' => $_SESSION['last_activity'] ?? 'Non défini',
        'session_expiry' => defined('SESSION_EXPIRY') ? SESSION_EXPIRY : 'Non défini'
    ]);
    header('Location: ' . $base_path . '/login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    exit;
}

// Vérifier si l'utilisateur est administrateur
log_debug('Vérification des droits administrateur');
if (!function_exists('is_admin')) {
    log_debug('La fonction is_admin n\'existe pas');
    header('Location: ' . $base_path . '/index.php');
    exit;
}

$current_user = current_user();
log_debug('Utilisateur actuel', [
    'user_id' => $current_user['id'] ?? 'Non défini',
    'username' => $current_user['username'] ?? 'Non défini',
    'role' => $current_user['role'] ?? 'Non défini',
    'is_admin' => is_admin() ? 'Oui' : 'Non'
]);

if (!is_admin()) {
    log_debug('Accès refusé - Droits administrateur requis', [
        'user_role' => $current_user['role'] ?? 'Non défini'
    ]);
    header('Location: ' . $base_path . '/index.php');
    exit;
}

log_debug('Accès autorisé à la page des commentaires signalés');

// Traitement des actions sur les signalements
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Vérifier le jeton CSRF
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $_SESSION['error_message'] = 'Jeton de sécurité invalide. Veuillez réessayer.';
        header('Location: ' . $base_path . '/admin/comments/reported.php');
        exit;
    }
    
    // Vérifier si l'action est valide
    if (!isset($_POST['action'])) {
        $_SESSION['error_message'] = 'Action non spécifiée.';
        header('Location: ' . $base_path . '/admin/comments/reported.php');
        exit;
    }
    
    $action = $_POST['action'];
    $admin_id = $_SESSION['user_id'];
    
    try {
        switch ($action) {
            case 'resolve':
                // Vérifier si l'ID du signalement est fourni
                if (!isset($_POST['report_id'])) {
                    throw new Exception('ID du signalement manquant.');
                }
                
                $report_id = (int)$_POST['report_id'];
                
                // Marquer le signalement comme résolu
                $result = resolve_comment_report($report_id, $admin_id);
                
                if ($result && is_array($result) && isset($result['comment_id'])) {
                    $_SESSION['success_message'] = 'Le signalement a été marqué comme résolu avec succès.';
                    
                    // Journaliser l'action
                    if (function_exists('log_moderation_action')) {
                        log_moderation_action(
                            $admin_id,
                            $result['comment_id'],
                            'resolve_report',
                            'Signalement #' . $report_id . ' marqué comme résolu'
                        );
                    }
                } else {
                    throw new Exception('Une erreur est survenue lors du traitement du signalement.');
                }
                break;
                
            case 'reject':
                // Vérifier si l'ID du signalement est fourni
                if (!isset($_POST['report_id'])) {
                    throw new Exception('ID du signalement manquant.');
                }
                
                $report_id = (int)$_POST['report_id'];
                
                // Rejeter le signalement
                $result = reject_comment_report($report_id, $admin_id);
                
                if ($result && is_array($result) && isset($result['comment_id'])) {
                    $_SESSION['success_message'] = 'Le signalement a été rejeté avec succès.';
                    
                    // Journaliser l'action
                    if (function_exists('log_moderation_action')) {
                        log_moderation_action(
                            $admin_id,
                            $result['comment_id'],
                            'reject_report',
                            'Signalement #' . $report_id . ' rejeté'
                        );
                    }
                } else {
                    throw new Exception('Une erreur est survenue lors du rejet du signalement.');
                }
                break;
                
            case 'delete_comment':
                // Vérifier si l'ID du commentaire est fourni
                if (!isset($_POST['comment_id'])) {
                    throw new Exception('ID du commentaire manquant.');
                }
                
                $comment_id = (int)$_POST['comment_id'];
                
                // Supprimer le commentaire signalé
                $result = delete_comment($comment_id, $admin_id, true);
                
                if ($result) {
                    $_SESSION['success_message'] = 'Le commentaire et tous ses signalements associés ont été supprimés avec succès.';
                    
                    // Journaliser l'action
                    if (function_exists('log_moderation_action')) {
                        log_moderation_action(
                            $admin_id,
                            $comment_id,
                            'delete_comment',
                            'Commentaire #' . $comment_id . ' supprimé suite à des signalements'
                        );
                    }
                } else {
                    throw new Exception('Une erreur est survenue lors de la suppression du commentaire.');
                }
                break;
                
            default:
                throw new Exception('Action non reconnue.');
        }
        
        // Rediriger pour éviter la soumission multiple du formulaire
        $redirect_url = $base_path . '/admin/comments/reported.php';
        if (isset($status) && $status !== 'all') {
            $redirect_url .= '?status=' . $status;
        }
        header('Location: ' . $redirect_url);
        exit;
        
    } catch (Exception $e) {
        // Journaliser l'erreur
        error_log('Erreur lors du traitement de l\'action ' . $action . ': ' . $e->getMessage());
        
        // Définir le message d'erreur
        $_SESSION['error_message'] = 'Erreur : ' . $e->getMessage();
        
        // Rediriger vers la page précédente
        $referer = $_SERVER['HTTP_REFERER'] ?? $base_path . '/admin/comments/reported.php';
        header('Location: ' . $referer);
        exit;
    }
}

// Générer un jeton CSRF pour le formulaire
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Initialiser les variables de pagination
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$per_page = 10;
$status = isset($_GET['status']) ? $_GET['status'] : 'pending';

// Valider le statut
$valid_statuses = ['pending', 'resolved', 'rejected', 'all'];
if (!in_array($status, $valid_statuses)) {
    $status = 'pending';
}

try {
    // Récupérer les commentaires signalés
    log_debug('Appel de get_reported_comments', [
        'page' => $page,
        'per_page' => $per_page,
        'status' => $status
    ]);
    
    $reported_comments = get_reported_comments($page, $per_page, $status);
    
    log_debug('Résultat de get_reported_comments', [
        'nombre_commentaires' => is_array($reported_comments) ? count($reported_comments) : 'Non tableau',
        'type_retour' => gettype($reported_comments)
    ]);
    
    // Compter le nombre total de commentaires signalés
    log_debug('Appel de get_reported_comments_count', ['status' => $status]);
    $total_reports = get_reported_comments_count($status);
    $total_pages = max(1, ceil($total_reports / $per_page));
    
    log_debug('Résultats de la pagination', [
        'total_reports' => $total_reports,
        'total_pages' => $total_pages
    ]);
    
    // Corriger le numéro de page si nécessaire
    if ($page > $total_pages && $total_pages > 0) {
        log_debug('Correction du numéro de page', [
            'ancienne_page' => $page,
            'nouvelle_page' => $total_pages
        ]);
        $page = $total_pages;
        $reported_comments = get_reported_comments($page, $per_page, $status);
    }
} catch (Exception $e) {
    // En cas d'erreur, initialiser des tableaux vides
    $reported_comments = [];
    $total_reports = 0;
    $total_pages = 1;
    
    // Afficher un message d'erreur
    $_SESSION['error_message'] = 'Une erreur est survenue lors de la récupération des commentaires signalés : ' . $e->getMessage();
}

// Définir le titre de la page
$page_title = 'Commentaires signalés';

// Inclure l'en-tête
require_once __DIR__ . '/../../templates/header.php';
?>

<div class="container mx-auto px-4 py-8">
    <!-- Messages d'alerte -->
    <?php if (isset($_SESSION['success_message'])): ?>
        <div class="alert-container mb-6 rounded-md bg-green-50 p-4">
            <div class="flex">
                <div class="flex-shrink-0">
                    <i class="h-5 w-5 text-green-400 fas fa-check-circle"></i>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium text-green-800">
                        <?= htmlspecialchars($_SESSION['success_message']) ?>
                    </p>
                </div>
                <div class="ml-auto pl-3">
                    <div class="-mx-1.5 -my-1.5">
                        <button type="button" class="close-alert inline-flex rounded-md bg-green-50 p-1.5 text-green-500 hover:bg-green-100 focus:outline-none focus:ring-2 focus:ring-green-600 focus:ring-offset-2 focus:ring-offset-green-50">
                            <span class="sr-only">Fermer</span>
                            <i class="h-5 w-5 fas fa-times"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <?php unset($_SESSION['success_message']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['error_message'])): ?>
        <div class="alert-container mb-6 rounded-md bg-red-50 p-4">
            <div class="flex">
                <div class="flex-shrink-0">
                    <i class="h-5 w-5 text-red-400 fas fa-exclamation-circle"></i>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium text-red-800">
                        <?= htmlspecialchars($_SESSION['error_message']) ?>
                    </p>
                </div>
                <div class="ml-auto pl-3">
                    <div class="-mx-1.5 -my-1.5">
                        <button type="button" class="close-alert inline-flex rounded-md bg-red-50 p-1.5 text-red-500 hover:bg-red-100 focus:outline-none focus:ring-2 focus:ring-red-600 focus:ring-offset-2 focus:ring-offset-red-50">
                    <span class="sr-only">Fermer</span>
                    <i class="h-5 w-5 fas fa-times"></i>
                </button>
                    </div>
                </div>
            </div>
        </div>
        <?php unset($_SESSION['error_message']); ?>
    <?php endif; ?>

    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Commentaires signalés</h1>
            <p class="mt-1 text-sm text-gray-500">Gérez les commentaires signalés par les utilisateurs.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="?status=pending" class="px-4 py-2 rounded-md <?= $status === 'pending' ? 'bg-blue-600 text-white' : 'bg-white text-gray-700 border border-gray-300' ?>">
                En attente
                <?php if ($status === 'pending'): ?>
                <span class="ml-1 inline-flex items-center justify-center px-2 py-0.5 text-xs font-medium rounded-full bg-blue-100 text-blue-800">
                    <?= get_reported_comments_count('pending') ?: '0' ?>
                </span>
                <?php endif; ?>
            </a>
            <a href="?status=resolved" class="px-4 py-2 rounded-md <?= $status === 'resolved' ? 'bg-green-600 text-white' : 'bg-white text-gray-700 border border-gray-300' ?>">
                Résolus
                <?php if ($status === 'resolved'): ?>
                <span class="ml-1 inline-flex items-center justify-center px-2 py-0.5 text-xs font-medium rounded-full bg-green-100 text-green-800">
                    <?= get_reported_comments_count('resolved') ?: '0' ?>
                </span>
                <?php endif; ?>
            </a>
            <a href="?status=rejected" class="px-4 py-2 rounded-md <?= $status === 'rejected' ? 'bg-gray-600 text-white' : 'bg-white text-gray-700 border border-gray-300' ?>">
                Rejetés
                <?php if ($status === 'rejected'): ?>
                <span class="ml-1 inline-flex items-center justify-center px-2 py-0.5 text-xs font-medium rounded-full bg-gray-100 text-gray-800">
                    <?= get_reported_comments_count('rejected') ?: '0' ?>
                </span>
                <?php endif; ?>
            </a>
            <a href="?status=all" class="px-4 py-2 rounded-md <?= $status === 'all' ? 'bg-indigo-600 text-white' : 'bg-white text-gray-700 border border-gray-300' ?>">
                Tous
                <?php if ($status === 'all'): ?>
                <span class="ml-1 inline-flex items-center justify-center px-2 py-0.5 text-xs font-medium rounded-full bg-indigo-100 text-indigo-800">
                    <?= get_reported_comments_count('all') ?: '0' ?>
                </span>
                <?php endif; ?>
            </a>
            <a href="<?= $base_path ?>/admin/" class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                <i class="fas fa-arrow-left mr-2"></i> Tableau de bord
            </a>
        </div>
    </div>
    
    <?php if (empty($reported_comments)): ?>
        <div class="bg-white p-6 rounded-lg shadow">
            <div class="text-center">
                <i class="fas fa-flag text-4xl text-gray-300 mb-4"></i>
                <h3 class="text-lg font-medium text-gray-900">Aucun commentaire signalé</h3>
                <p class="mt-1 text-gray-500">
                    <?= $status === 'all' ? 'Aucun commentaire signalé pour le moment.' : 'Aucun commentaire ' . ($status === 'pending' ? 'en attente' : 'résolu') . ' pour le moment.' ?>
                </p>
            </div>
        </div>
    <?php else: ?>
        <div class="bg-white shadow overflow-hidden sm:rounded-md">
            <ul class="divide-y divide-gray-200">
            <?php foreach ($reported_comments as $comment): ?>
                <li class="border-b border-gray-200 last:border-b-0">
                    <div class="px-4 py-4 sm:px-6 hover:bg-gray-50">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 h-10 w-10 rounded-full bg-gray-200 flex items-center justify-center text-gray-500">
                                    <i class="fas fa-comment"></i>
                                </div>
                                <div class="ml-4">
                                    <div class="text-sm font-medium text-gray-900">
                                        <?= htmlspecialchars($comment['author_name']) ?>
                                        <span class="text-gray-500 font-normal">a commenté</span>
                                        <a href="<?= $base_path ?>/livre.php?id=<?= $comment['book_id'] ?>" class="text-blue-600 hover:text-blue-800">
                                            "<?= htmlspecialchars($comment['book_title']) ?>"
                                        </a>
                                    </div>
                                    <div class="text-sm text-gray-500">
                                        <?= date('d/m/Y à H:i', strtotime($comment['created_at'])) ?>
                                        <?php if ($comment['updated_at'] && $comment['updated_at'] !== $comment['created_at']): ?>
                                            <span class="text-xs text-gray-400">(modifié le <?= date('d/m/Y à H:i', strtotime($comment['updated_at'])) ?>)</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            <div class="flex space-x-2">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                    <i class="fas fa-flag mr-1"></i> <?= $comment['report_count'] ?>
                                </span>
                                <?php if (isset($comment['reports'][0]['status']) && $comment['reports'][0]['status'] === 'pending'): ?>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                        En attente
                                    </span>
                                <?php else: ?>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                        Résolu
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="mt-2 text-sm text-gray-700">
                            <div class="prose max-w-none">
                                <?= nl2br(htmlspecialchars($comment['content'])) ?>
                            </div>
                        </div>
                        
                        <?php if (!empty($comment['reports'])): ?>
                            <div class="mt-3">
                                <h4 class="text-sm font-medium text-gray-900 mb-2">Signalements :</h4>
                                <div class="space-y-2">
                                    <?php foreach ($comment['reports'] as $report): ?>
                                        <div class="bg-gray-50 p-3 rounded-md border border-gray-200">
                                            <div class="flex justify-between items-start">
                                                <div class="flex-1">
                                                    <div class="text-sm font-medium text-gray-900">
                                                        <?= htmlspecialchars($report['reporter_name']) ?>
                                                        <span class="text-gray-500 font-normal">a signalé ce commentaire</span>
                                                    </div>
                                                    <div class="text-sm text-gray-500">
                                                        <?= date('d/m/Y à H:i', strtotime($report['created_at'])) ?>
                                                        <?php if ($report['status'] === 'resolved' && $report['resolved_by']): ?>
                                                            <span class="text-green-600">
                                                                • Résolu par <?= htmlspecialchars($report['resolved_by_name']) ?>
                                                                <?= $report['resolved_at'] ? 'le ' . date('d/m/Y à H:i', strtotime($report['resolved_at'])) : '' ?>
                                                            </span>
                                                        <?php endif; ?>
                                                    </div>
                                                    <?php if (!empty($report['reason'])): ?>
                                                        <div class="mt-1 text-sm text-gray-700 bg-white p-2 rounded border border-gray-200">
                                                            <span class="font-medium">Raison :</span> <?= nl2br(htmlspecialchars($report['reason'])) ?>
                                                        </div>
                                                    <?php endif; ?>
                                                    <?php if (!empty($report['resolution_notes'])): ?>
                                                        <div class="mt-1 text-sm text-gray-700 bg-green-50 p-2 rounded border border-green-200">
                                                            <span class="font-medium">Notes de résolution :</span> <?= nl2br(htmlspecialchars($report['resolution_notes'])) ?>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                                <?php if ($report['status'] === 'pending'): ?>
                                                    <div class="ml-2 flex-shrink-0 flex space-x-1">
                                                        <form method="POST" class="inline" onsubmit="return confirm('Êtes-vous sûr de vouloir marquer ce signalement comme résolu ?');">
                                                            <input type="hidden" name="action" value="resolve">
                                                            <input type="hidden" name="report_id" value="<?= $report['id'] ?>">
                                                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                                                            <button type="submit" class="text-green-600 hover:text-green-800" title="Marquer comme résolu">
                                                                <i class="fas fa-check-circle"></i>
                                                            </button>
                                                        </form>
                                                        <form method="POST" class="inline" onsubmit="return confirm('Êtes-vous sûr de vouloir rejeter ce signalement ?');">
                                                            <input type="hidden" name="action" value="reject">
                                                            <input type="hidden" name="report_id" value="<?= $report['id'] ?>">
                                                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                                                            <button type="submit" class="text-yellow-600 hover:text-yellow-800" title="Rejeter le signalement">
                                                                <i class="fas fa-times-circle"></i>
                                                            </button>
                                                        </form>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <div class="mt-4 flex justify-end space-x-3">
                            <a href="<?= url('livre.php?id=' . $comment['book_id'] . '#comment-' . $comment['id']) ?>" 
                               target="_blank" 
                               class="inline-flex items-center px-3 py-1.5 border border-gray-300 shadow-sm text-xs font-medium rounded text-gray-700 bg-white hover:bg-gray-50">
                                <i class="fas fa-external-link-alt mr-1"></i> Voir en contexte
                            </a>
                            <form method="POST" class="inline" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer ce commentaire ? Tous les signalements associés seront également supprimés.');">
                                <input type="hidden" name="action" value="delete_comment">
                                <input type="hidden" name="comment_id" value="<?= $comment['id'] ?>">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                                <button type="submit" class="inline-flex items-center px-3 py-1.5 border border-transparent text-xs font-medium rounded shadow-sm text-white bg-red-600 hover:bg-red-700">
                                    <i class="fas fa-trash-alt mr-1"></i> Supprimer
                                </button>
                            </form>
                        </div>
                    </div>
                </li>
            <?php endforeach; ?>
            
            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
                <div class="bg-white px-4 py-3 flex items-center justify-between border-t border-gray-200 sm:px-6">
                    <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                        <div>
                            <p class="text-sm text-gray-700">
                                Affichage de <span class="font-medium"><?= (($page - 1) * $per_page) + 1 ?></span> à 
                                <span class="font-medium"><?= min($page * $per_page, $total_reports) ?></span> sur 
                                <span class="font-medium"><?= $total_reports ?></span> commentaire<?= $total_reports > 1 ? 's' : '' ?>
                            </p>
                        </div>
                        <div>
                            <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                                <?php if ($page > 1): ?>
                                    <a href="?page=<?= $page - 1 ?><?= $status !== 'all' ? '&status=' . $status : '' ?>" 
                                       class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                                        <span class="sr-only">Précédent</span>
                                        <i class="fas fa-chevron-left h-5 w-5"></i>
                                    </a>
                                <?php endif; ?>
                                
                                <?php 
                                $start = max(1, $page - 2);
                                $end = min($start + 4, $total_pages);
                                $start = max(1, $end - 4);
                                
                                if ($start > 1): ?>
                                    <a href="?page=1<?= $status !== 'all' ? '&status=' . $status : '' ?>" 
                                       class="bg-white border-gray-300 text-gray-500 hover:bg-gray-50 relative inline-flex items-center px-4 py-2 border text-sm font-medium">
                                        1
                                    </a>
                                    <?php if ($start > 2): ?>
                                        <span class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700">
                                            ...
                                        </span>
                                    <?php endif; ?>
                                <?php endif; ?>
                                
                                <?php for ($i = $start; $i <= $end; $i++): ?>
                                    <a href="?page=<?= $i ?><?= $status !== 'all' ? '&status=' . $status : '' ?>" 
                                       class="<?= $i === $page ? 'z-10 bg-blue-50 border-blue-500 text-blue-600' : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50' ?> relative inline-flex items-center px-4 py-2 border text-sm font-medium">
                                        <?= $i ?>
                                    </a>
                                <?php endfor; ?>
                                
                                <?php if ($end < $total_pages): ?>
                                    <?php if ($end < $total_pages - 1): ?>
                                        <span class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700">
                                            ...
                                        </span>
                                    <?php endif; ?>
                                    <a href="?page=<?= $total_pages ?><?= $status !== 'all' ? '&status=' . $status : '' ?>" 
                                       class="bg-white border-gray-300 text-gray-500 hover:bg-gray-50 relative inline-flex items-center px-4 py-2 border text-sm font-medium">
                                        <?= $total_pages ?>
                                    </a>
                                <?php endif; ?>
                                
                                <?php if ($page < $total_pages): ?>
                                    <a href="?page=<?= $page + 1 ?><?= $status !== 'all' ? '&status=' . $status : '' ?>" 
                                       class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                                        <span class="sr-only">Suivant</span>
                                        <i class="fas fa-chevron-right h-5 w-5"></i>
                                    </a>
                                <?php endif; ?>
                            </nav>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../../templates/footer.php'; ?>

<script>
    // Gestion de la fermeture des messages d'alerte
    document.addEventListener('DOMContentLoaded', function() {
        // Fermeture des messages d'alerte au clic sur le bouton de fermeture
        document.querySelectorAll('.close-alert').forEach(button => {
            button.addEventListener('click', function() {
                this.closest('.alert-container').style.display = 'none';
            });
        });
        
        // Fermeture automatique après 5 secondes
        setTimeout(() => {
            document.querySelectorAll('.alert-container').forEach(alert => {
                alert.style.transition = 'opacity 0.5s';
                alert.style.opacity = '0';
                setTimeout(() => {
                    alert.style.display = 'none';
                }, 500);
            });
        }, 5000);
    });
</script>
