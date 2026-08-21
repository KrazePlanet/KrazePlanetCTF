<?php

/**
 * Supprimer un utilisateur
 */

// Vérifier si l'utilisateur est connecté et est un administrateur
require_once __DIR__ . '/../../includes/init.php';

if (!is_logged_in() || !is_admin()) {
    header('Location: ' . BASE_PATH . '/login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    exit;
}

// Vérifier si l'ID de l'utilisateur est fourni
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    set_flash('ID utilisateur invalide', 'error');
    header('Location: ' . BASE_PATH . '/admin/users/');
    exit;
}

$user_id = (int)$_GET['id'];
$current_user_id = $_SESSION['user_id'];

// Empêcher l'auto-suppression
if ($user_id === $current_user_id) {
    set_flash('Vous ne pouvez pas supprimer votre propre compte depuis cette interface', 'error');
    header('Location: ' . BASE_PATH . '/admin/users/'); 
    exit;
}

try {
    global $pdo;
    
    // Démarrer une transaction
    $pdo->beginTransaction();
    
    // 1. Récupérer les informations de l'utilisateur avant suppression (pour les logs)
    $stmt = $pdo->prepare("SELECT username, email, role FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
    
    if (!$user) {
        throw new Exception('Utilisateur non trouvé');
    }
    
    // 2. Supprimer les jetons de réinitialisation de mot de passe
    $pdo->prepare("DELETE FROM password_resets WHERE email = ?")->execute([$user['email']]);
    
    // 3. Supprimer les jetons "Se souvenir de moi"
    $pdo->prepare("DELETE FROM remember_me_tokens WHERE user_id = ?")->execute([$user_id]);
    
    // 4. Marquer les commentaires de l'utilisateur comme supprimés (suppression logique)
    $pdo->prepare("
        UPDATE comments 
        SET is_deleted = 1,
            deleted_at = NOW(),
            deleted_by = ?
        WHERE user_id = ?
    ")->execute([$current_user_id, $user_id]);
    
    // 5. Marquer les livres de l'utilisateur comme supprimés (suppression logique)
    $pdo->prepare("
        UPDATE books 
        SET is_deleted = 1,
            deleted_at = NOW(),
            deleted_by = ?
        WHERE user_id = ?
    ")->execute([$current_user_id, $user_id]);
    
    // 6. Supprimer l'utilisateur
    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    
    if ($stmt->rowCount() === 0) {
        throw new Exception('Échec de la suppression de l\'utilisateur');
    }
    
    // 7. Journaliser l'action
    $action = sprintf(
        'Suppression de l\'utilisateur %s (ID: %d) par l\'administrateur %s (ID: %d)',
        $user['username'],
        $user_id,
        $_SESSION['user_username'],
        $current_user_id
    );
    
    $pdo->prepare("
        INSERT INTO admin_logs (user_id, action, ip_address, user_agent)
        VALUES (?, ?, ?, ?)
    ")->execute([
        $current_user_id,
        $action,
        $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
        $_SERVER['HTTP_USER_AGENT'] ?? 'Inconnu'
    ]);
    
    // Valider la transaction
    $pdo->commit();
    
    set_flash(sprintf('L\'utilisateur %s a été supprimé avec succès', htmlspecialchars($user['username'])));
    
} catch (Exception $e) {
    // Annuler la transaction en cas d'erreur
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    error_log('Erreur lors de la suppression de l\'utilisateur : ' . $e->getMessage());
    set_flash('Une erreur est survenue lors de la suppression de l\'utilisateur : ' . $e->getMessage(), 'error');
}

// Rediriger vers la liste des utilisateurs
header('Location: ' . BASE_PATH . '/admin/users/');
exit;
