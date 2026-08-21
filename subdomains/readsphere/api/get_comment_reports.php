
<?php

/*
 * Obtenir les signalements d'un commentaire
 * 
 * Retourne les signalements d'un commentaire en format JSON
 */

 require_once __DIR__ . '/../includes/init.php';

// Vérifier que l'utilisateur est administrateur
if (!is_admin()) {
    http_response_code(403);
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Accès non autorisé'
    ]);
    exit;
}

// Vérifier que l'ID du commentaire est fourni
if (!isset($_GET['comment_id']) || !is_numeric($_GET['comment_id'])) {
    http_response_code(400);
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'ID de commentaire manquant ou invalide'
    ]);
    exit;
}

$comment_id = (int)$_GET['comment_id'];

try {
    // Récupérer les signalements du commentaire
    $reports = get_comment_reports($comment_id);
    
    // Si la fonction retourne false, une erreur s'est produite
    if ($reports === false) {
        throw new Exception('Erreur lors de la récupération des signalements');
    }
    
    // Formater les données pour la réponse
    $formatted_reports = [];
    foreach ($reports as $report) {
        $formatted_reports[] = [
            'id' => $report['id'],
            'comment_id' => $report['comment_id'],
            'reporter_id' => $report['user_id'],
            'reporter_name' => $report['username'],
            'reason' => $report['reason'],
            'status' => $report['status'],
            'created_at' => $report['created_at'],
            'updated_at' => $report['updated_at']
        ];
    }
    
    // Retourner la réponse JSON
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'reports' => $formatted_reports
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Erreur serveur: ' . $e->getMessage()
    ]);
}
?>
