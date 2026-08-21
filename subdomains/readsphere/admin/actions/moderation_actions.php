
<?php

/**
 * Actions de modération
 * 
 * Gère les actions de modération pour les commentaires et les signalements
 */

require_once __DIR__ . '/../../includes/init.php';

// Vérifier que l'utilisateur est administrateur
if (!is_admin()) {
    header('HTTP/1.1 403 Forbidden');
    echo json_encode(['success' => false, 'message' => 'Accès refusé']);
    exit;
}

// Vérifier le jeton CSRF
if (!verify_csrf_token($_POST['_token'])) {
    header('HTTP/1.1 403 Forbidden');
    echo json_encode(['success' => false, 'message' => 'Jeton de sécurité invalide']);
    exit;
}

// Vérifier que la requête est de type POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('HTTP/1.1 405 Method Not Allowed');
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit;
}

// Récupérer l'action demandée
$action = $_POST['action'] ?? '';
$comment_id = isset($_POST['comment_id']) ? (int)$_POST['comment_id'] : 0;
$report_id = isset($_POST['report_id']) ? (int)$_POST['report_id'] : 0;
$user_id = $_SESSION['user_id'];

// Traiter l'action demandée
switch ($action) {
    case 'delete_comment':
        handle_delete_comment($comment_id, $user_id);
        break;
        
    case 'resolve_report':
        handle_resolve_report($report_id, $user_id);
        break;
        
    case 'reject_report':
        handle_reject_report($report_id, $user_id);
        break;
        
    default:
        header('HTTP/1.1 400 Bad Request');
        echo json_encode(['success' => false, 'message' => 'Action non reconnue']);
        exit;
}

/**
 * Gère la suppression d'un commentaire
 */
function handle_delete_comment($comment_id, $user_id) {
    if ($comment_id <= 0) {
        send_error('ID de commentaire invalide');
    }
    
    // Supprimer le commentaire (action administrateur)
    $result = delete_comment($comment_id, $user_id, true);
    
    if ($result) {
        // Enregistrer l'action de modération
        log_moderation_action($user_id, $comment_id, 'delete_comment', 'Commentaire supprimé par un modérateur');
        
        send_success('Le commentaire a été supprimé avec succès');
    } else {
        send_error('Échec de la suppression du commentaire');
    }
}

/**
 * Gère la résolution d'un signalement
 */
function handle_resolve_report($report_id, $user_id) {
    if ($report_id <= 0) {
        send_error('ID de signalement invalide');
    }
    
    // Résoudre le signalement
    $result = resolve_comment_report($report_id, $user_id);
    
    if ($result) {
        send_success('Le signalement a été marqué comme résolu');
    } else {
        send_error('Échec du traitement du signalement');
    }
}

/**
 * Gère le rejet d'un signalement
 */
function handle_reject_report($report_id, $user_id) {
    if ($report_id <= 0) {
        send_error('ID de signalement invalide');
    }
    
    // Rejeter le signalement
    $result = reject_comment_report($report_id, $user_id);
    
    if ($result) {
        send_success('Le signalement a été rejeté');
    } else {
        send_error('Échec du rejet du signalement');
    }
}

/**
 * Envoie une réponse d'erreur au format JSON
 */
function send_error($message) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => $message]);
    exit;
}

/**
 * Envoie une réponse de succès au format JSON
 */
function send_success($message, $data = []) {
    $response = ['success' => true, 'message' => $message];
    if (!empty($data)) {
        $response['data'] = $data;
    }
    
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

?>
