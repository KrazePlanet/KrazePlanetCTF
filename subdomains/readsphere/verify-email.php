<?php

/**
 * Vérifier l'email de l'utilisateur
 */

require_once 'includes/init.php';

// Vérifier si un jeton est fourni
$token = $_GET['token'] ?? '';

if (empty($token)) {
    set_flash('Jeton de vérification manquant.', 'error');
    redirect('index.php');
}

try {
    global $pdo;
    
    // Vérifier le jeton
    $stmt = $pdo->prepare("
        SELECT id, user_id, expires_at 
        FROM user_tokens 
        WHERE token = ? AND type = 'email_verification' AND is_used = 0 AND expires_at > NOW()
        LIMIT 1
    ");
    
    $stmt->execute([$token]);
    $token_data = $stmt->fetch();
    
    if ($token_data) {
        // Marquer le jeton comme utilisé
        $pdo->prepare("UPDATE user_tokens SET is_used = 1, used_at = NOW() WHERE id = ?")->execute([$token_data['id']]);
        
        // Mettre à jour l'utilisateur
        $pdo->prepare("UPDATE users 
            SET email_verified_at = NOW(),
            is_active = 1 
            WHERE id = ?"
        )->execute([$token_data['user_id']]);
        
        set_flash('Votre adresse email a été vérifiée avec succès ! Vous pouvez maintenant vous connecter.', 'success');
    } else {
        set_flash('Le lien de vérification est invalide ou a expiré.', 'error');
    }
} catch (PDOException $e) {
    error_log('Erreur lors de la vérification de l\'email : ' . $e->getMessage());
    set_flash('Une erreur est survenue lors de la vérification de votre email. Veuillez réessayer.', 'error');
}

redirect('login.php');