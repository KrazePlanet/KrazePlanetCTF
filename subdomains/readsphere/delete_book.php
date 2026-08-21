<?php

/**
 * Supprimer un livre
 * 
 * Cette page permet de supprimer un livre.
 */

require_once 'includes/init.php';

// Vérifier que l'utilisateur est connecté
require_auth();

// Vérifier que l'ID du livre est fourni
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    set_flash('Livre non trouvé', 'error');
    redirect('dashboard.php');
}

$book_id = (int)$_GET['id'];
$user_id = $_SESSION['user_id'];
$is_admin = is_admin();
$is_admin_action = isset($_GET['admin_action']) && $_GET['admin_action'] == 1;

try {
    // Vérifier que le livre existe et que l'utilisateur a les droits
    $book = get_book($book_id, true); // Récupérer même les livres supprimés
    
    if (!$book) {
        set_flash('Livre non trouvé', 'error');
        redirect('dashboard.php');
    }
    
    // Vérifier les permissions si ce n'est pas une action admin
    if (!$is_admin_action && $book['user_id'] != $user_id) {
        set_flash('Vous n\'êtes pas autorisé à supprimer ce livre', 'error');
        redirect('dashboard.php');
    }
    
    // Utiliser la fonction de suppression logique
    if (delete_book($book_id)) {
        // Si c'est un admin qui supprime, on redirige vers la page d'administration
        if ($is_admin && $is_admin_action) {
            redirect( BASE_PATH . '/admin/books/');
        }
        set_flash('Le livre a été supprimé avec succès.', 'success');
    } else {
        $error = $_SESSION['error'] ?? 'Une erreur est survenue lors de la suppression du livre.';
        set_flash($error, 'error');
    }
    
} catch (Exception $e) {
    error_log('Erreur lors de la suppression du livre : ' . $e->getMessage());
    set_flash('Une erreur est survenue lors de la suppression du livre.', 'error');
}

// Rediriger vers le tableau de bord
redirect('dashboard.php');
