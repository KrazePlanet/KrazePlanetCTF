<?php

/**
 * Gestion des actions sur les commentaires
 * 
 * Ce fichier gère les actions sur les commentaires, notamment la suppression et la mise à jour.
 */

require_once __DIR__ . '/init.php';

// Vérifier que la requête est de type POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée.']);
    exit;
}

// Initialiser la réponse
header('Content-Type: application/json');
$response = ['success' => false, 'message' => 'Action inconnue.'];

try {
    // Vérifier que l'utilisateur est connecté
    if (!is_logged_in()) {
        throw new Exception('Non autorisé. Veuillez vous connecter.');
    }

    // Récupérer l'action demandée
    $action = $_POST['action'] ?? '';
    $comment_id = isset($_POST['comment_id']) ? (int)$_POST['comment_id'] : 0;
    $user_id = $_SESSION['user_id'];

    switch ($action) {
        case 'delete':
            // Suppression d'un commentaire
            $is_admin_action = isset($_POST['admin_action']) && $_POST['admin_action'] === '1';
            
            if ($comment_id <= 0) {
                throw new Exception('ID de commentaire invalide.');
            }
            
            // Vérifier si l'utilisateur a le droit de supprimer ce commentaire
            $comment = get_comment($comment_id);
            if (!$comment) {
                throw new Exception('Commentaire non trouvé.');
            }
            
            // Vérifier les droits (auteur du commentaire ou admin)
            $is_author = $comment['user_id'] == $user_id;
            $is_admin = is_admin();
            
            if (!$is_author && !$is_admin) {
                throw new Exception('Vous n\'êtes pas autorisé à supprimer ce commentaire.');
            }
            
            // Si c'est une action admin, vérifier que l'utilisateur est bien admin
            if ($is_admin_action && !$is_admin) {
                throw new Exception('Action non autorisée.');
            }
            
            // Supprimer le commentaire
            $result = delete_comment($comment_id, $user_id, $is_admin_action);
            
            if ($result) {
                $response = [
                    'success' => true,
                    'message' => $is_admin_action 
                        ? 'Le commentaire a été supprimé avec succès en tant qu\'administrateur.' 
                        : 'Votre commentaire a été supprimé avec succès.'
                ];
            } else {
                $error = $_SESSION['error'] ?? 'Une erreur est survenue lors de la suppression du commentaire.';
                unset($_SESSION['error']);
                throw new Exception($error);
            }
            break;
            
        case 'update':
            // Mise à jour d'un commentaire
            $content = trim($_POST['content'] ?? '');
            
            if ($comment_id <= 0) {
                throw new Exception('ID de commentaire invalide.');
            }
            
            if (empty($content)) {
                throw new Exception('Le commentaire ne peut pas être vide.');
            }
            
            if (strlen($content) > 1000) {
                throw new Exception('Le commentaire ne doit pas dépasser 1000 caractères.');
            }
            
            // Vérifier si l'utilisateur a le droit de modifier ce commentaire
            $comment = get_comment($comment_id);
            if (!$comment) {
                throw new Exception('Commentaire non trouvé.');
            }
            
            // Seul l'auteur peut modifier son commentaire
            if ($comment['user_id'] != $user_id) {
                throw new Exception('Vous n\'êtes pas autorisé à modifier ce commentaire.');
            }
            
            // Mettre à jour le commentaire
            $result = update_comment($comment_id, $content, $user_id);
            
            if ($result) {
                $response = [
                    'success' => true,
                    'message' => 'Votre commentaire a été mis à jour avec succès.'
                ];
            } else {
                throw new Exception('Une erreur est survenue lors de la mise à jour du commentaire.');
            }
            break;
            
        case 'report':
            // Signaler un commentaire
            $reason = trim($_POST['reason'] ?? '');
            
            if ($comment_id <= 0) {
                throw new Exception('ID de commentaire invalide.');
            }
            
            if (empty($reason) || strlen($reason) < 10) {
                throw new Exception('Veuillez fournir une raison détaillée (au moins 10 caractères).');
            }
            
            // Vérifier que le commentaire existe
            $comment = get_comment($comment_id);
            if (!$comment) {
                throw new Exception('Commentaire non trouvé.');
            }
            
            // Vérifier que l'utilisateur ne signale pas son propre commentaire
            if ($comment['user_id'] == $user_id) {
                throw new Exception('Vous ne pouvez pas signaler votre propre commentaire.');
            }
            
            // Utiliser la fonction report_comment pour gérer le signalement
            $result = report_comment($comment_id, $user_id, $reason);
            
            if ($result['success']) {
                $response = [
                    'success' => true,
                    'message' => $result['message']
                ];
            } else {
                throw new Exception($result['message']);
            }
            break;
            
        default:
            throw new Exception('Action non reconnue.');
    }
    
} catch (Exception $e) {
    $response = [
        'success' => false,
        'message' => $e->getMessage()
    ];
}

// Renvoyer la réponse au format JSON
echo json_encode($response);
?>
