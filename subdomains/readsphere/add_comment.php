<?php

/**
 * Ajouter un commentaire
 * 
 * Cette page permet d'ajouter un nouveau commentaire à un livre.
 */

require_once 'includes/init.php';

// Vérifier que l'utilisateur est connecté
require_auth();

// Vérifier que la requête est de type POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    set_flash('Méthode non autorisée', 'error');
    redirect('index.php');
}

// Vérifier que l'ID du livre est fourni
if (!isset($_POST['book_id']) || !is_numeric($_POST['book_id'])) {
    set_flash('Livre non trouvé', 'error');
    redirect('index.php');
}

$book_id = (int)$_POST['book_id'];
$user_id = $_SESSION['user_id'];
$content = trim($_POST['content'] ?? '');
$errors = [];

// Validation
if (empty($content)) {
    $errors[] = 'Le commentaire ne peut pas être vide';
} elseif (strlen($content) > 1000) {
    $errors[] = 'Le commentaire ne doit pas dépasser 1000 caractères';
}

// Vérifier que le livre existe
$book = $pdo->prepare("SELECT id FROM books WHERE id = ?");
$book->execute([$book_id]);

if (!$book->fetch()) {
    $errors[] = 'Livre non trouvé';
}

// Si pas d'erreurs, ajouter le commentaire
if (empty($errors)) {
    try {
        $pdo->beginTransaction();
        
        $stmt = $pdo->prepare("
            INSERT INTO comments (book_id, user_id, content)
            VALUES (?, ?, ?)
        ");
        
        $stmt->execute([
            $book_id,
            $user_id,
            $content
        ]);
        
        $pdo->commit();
        
        set_flash('Votre commentaire a été ajouté avec succès !');
        
    } catch (PDOException $e) {
        $pdo->rollBack();
        error_log('Erreur lors de l\'ajout du commentaire : ' . $e->getMessage());
        $errors[] = 'Une erreur est survenue lors de l\'ajout du commentaire. Veuillez réessayer.';
    }
} else {
    // Stocker les erreurs dans la session pour les afficher sur la page du livre
    $_SESSION['comment_errors'] = $errors;
    $_SESSION['comment_content'] = $content;
}

// Rediriger vers la page du livre
redirect('book.php?id=' . $book_id);
