
<?php

/*
 * Basculer le like d'un livre
 * 
 * Permet de basculer le like d'un livre en favori ou non
 */

header('Content-Type: application/json');

require_once __DIR__ . '/../includes/init.php';

// Vérifier si l'utilisateur est connecté
if (!is_logged_in()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Vous devez être connecté pour aimer un livre']);
    exit;
}

// Vérifier si la requête est de type POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Méthode non autorisée']);
    exit;
}

// Récupérer les données de la requête
$data = json_decode(file_get_contents('php://input'), true);
$book_id = isset($data['book_id']) ? (int)$data['book_id'] : 0;

// Valider l'ID du livre
if ($book_id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'ID de livre invalide']);
    exit;
}

// Basculer le like
$result = toggle_book_like($book_id, $_SESSION['user_id']);

// Renvoyer la réponse
if ($result['success']) {
    echo json_encode([
        'success' => true,
        'liked' => $result['liked'],
        'like_count' => $result['like_count']
    ]);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $result['error'] ?? 'Une erreur est survenue']);
}
