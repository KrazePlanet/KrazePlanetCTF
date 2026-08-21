<?php
/**
 * Fonctions d'authentification
 * 
 * Ce fichier est inclus par init.php qui fournit déjà :
 * - La connexion à la base de données
 * - Les fonctions utilitaires
 * - Les opérations utilisateur
 */

/**
 * Vérifie si l'utilisateur est connecté
 * 
 * @return bool
 */
function is_authenticated(): bool {
    return is_logged_in();
}

/**
 * Redirige l'utilisateur s'il n'est pas connecté
 * 
 * @param string $redirect_url URL de redirection
 * @return void
 */
function require_auth(string $redirect_url = 'login.php'): void {
    if (!is_authenticated()) {
        if (!in_array(basename($_SERVER['PHP_SELF']), ['login.php', 'signup.php'])) {
            $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
        }
        redirect($redirect_url);
    }
}

/**
 * Vérifie si l'utilisateur est administrateur
 * 
 * @return bool
 */
function is_admin(): bool {
    if (!is_authenticated()) {
        return false;
    }
    
    $user = current_user();
    return $user && isset($user['role']) && $user['role'] === 'admin';
}

/**
 * Redirige l'utilisateur s'il n'est pas administrateur
 * 
 * @param string $redirect_url URL de redirection
 * @return void
 */
function require_admin(string $redirect_url = 'index.php'): void {
    require_auth($redirect_url);
    
    if (!is_admin()) {
        set_flash('Accès refusé. Vous n\'avez pas les permissions nécessaires.', 'error');
        redirect($redirect_url);
    }
}

/**
 * Récupère l'utilisateur actuellement connecté
 * 
 * @return array|false
 */
function current_user() {
    if (!is_authenticated()) {
        return false;
    }
    
    // Si l'utilisateur est déjà en session et qu'on a toutes les infos nécessaires
    if (isset($_SESSION['user_data']) && is_array($_SESSION['user_data']) && 
        isset($_SESSION['user_data']['id'], $_SESSION['user_data']['username'], $_SESSION['user_data']['email'], $_SESSION['user_data']['role'])) {
        return $_SESSION['user_data'];
    }
    
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            SELECT id, username, email, role, is_active, email_verified_at, 
                   created_at, updated_at, last_login_at, avatar
            FROM users 
            WHERE id = ?
        ");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Mettre en cache les données utilisateur en session
        if ($user) {
            $_SESSION['user_data'] = $user;
        }
        
        return $user;
    } catch (PDOException $e) {
        error_log('Erreur lors de la récupération de l\'utilisateur : ' . $e->getMessage());
        return false;
    }
}