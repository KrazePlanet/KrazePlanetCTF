<?php
/**
 * Page de déconnexion
 */

// Désactiver la mise en cache pour cette page
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0', false);
header('Pragma: no-cache');

// Démarrer la mise en tampon de sortie avec gestion des erreurs
if (ob_get_level() === 0) {
    ob_start();
}

// Inclure le fichier d'initialisation
try {
    require_once 'includes/init.php';
} catch (Exception $e) {
    error_log('Erreur lors de l\'initialisation : ' . $e->getMessage());
    // Essayer de rediriger même en cas d'erreur
    header('Location: login.php');
    exit();
}

// Déconnecter l'utilisateur
$logoutResult = false;
$errorMessage = '';

try {
    $logoutResult = logout_user();
} catch (Exception $e) {
    $errorMessage = $e->getMessage();
    error_log('Erreur lors de la déconnexion : ' . $errorMessage);
}

// Nettoyer le buffer de sortie
if (ob_get_level() > 0) {
    ob_end_clean();
}

// Définir le message de statut
if ($logoutResult) {
    set_flash('Vous avez été déconnecté avec succès.', 'success');
} else {
    $errorMsg = !empty($errorMessage) 
        ? 'Une erreur est survenue : ' . htmlspecialchars($errorMessage)
        : 'Une erreur est survenue lors de la déconnexion. Veuillez réessayer.';
    set_flash($errorMsg, 'error');
}

// Rediriger vers la page de connexion
redirect('login.php');