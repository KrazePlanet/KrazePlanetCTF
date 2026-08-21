
<?php

/*
 * Basculer le like d'un commentaire
 * 
 * Permet de basculer le like d'un commentaire en favori ou non
 */

require_once __DIR__ . '/../includes/init.php';

header('Content-Type: application/json');

// Vérifier que l'utilisateur est connecté
if (!is_logged_in()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Vous devez être connecté pour aimer un commentaire']);
    exit;
}

// Vérifier que l'ID du commentaire est fourni
if (!isset($_POST['comment_id']) || !is_numeric($_POST['comment_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID de commentaire invalide']);
    exit;
}

$comment_id = (int)$_POST['comment_id'];
$user_id = $_SESSION['user_id'];

// Appeler la fonction pour basculer le like
$result = toggle_comment_like($comment_id, $user_id);

// Retourner le résultat au format JSON
echo json_encode($result);
