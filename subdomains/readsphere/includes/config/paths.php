<?php
/**
 * Configuration des chemins
 * 
 * Contient toutes les constantes de chemins du projet
 */

// Vérifier si le fichier a déjà été inclus
if (!defined('PATHS_LOADED')) {
    // Définir une constante pour indiquer que les chemins sont chargés
    define('PATHS_LOADED', true);

    // Définir les chemins de base
    $rootPath = dirname(__DIR__, 2);
    $basePath = rtrim($_ENV['APP_URL'] ?? '', '/');
    
    // Définir les constantes de manière globale
    if (!defined('ROOT_PATH')) define('ROOT_PATH', $rootPath);
    if (!defined('PUBLIC_PATH')) define('PUBLIC_PATH', $rootPath . '/public');
    if (!defined('UPLOADS_PATH')) define('UPLOADS_PATH', PUBLIC_PATH . '/uploads');
    if (!defined('AVATARS_PATH')) define('AVATARS_PATH', UPLOADS_PATH . '/avatars');
    if (!defined('THUMBNAILS_PATH')) define('THUMBNAILS_PATH', UPLOADS_PATH . '/thumbnails');
    if (!defined('CACHE_PATH')) define('CACHE_PATH', $rootPath . '/cache');
    if (!defined('IMAGE_CACHE_PATH')) define('IMAGE_CACHE_PATH', CACHE_PATH . '/images');
    if (!defined('VIEW_CACHE_PATH')) define('VIEW_CACHE_PATH', CACHE_PATH . '/views');
    if (!defined('LOGS_PATH')) define('LOGS_PATH', $rootPath . '/logs');
    
    // Définir les URLs
    if (!defined('BASE_URL')) define('BASE_URL', $basePath);
    if (!defined('UPLOADS_URL')) define('UPLOADS_URL', $basePath . '/uploads');
    if (!defined('AVATARS_URL')) define('AVATARS_URL', UPLOADS_URL . '/avatars');
    if (!defined('THUMBNAILS_URL')) define('THUMBNAILS_URL', UPLOADS_URL . '/thumbnails');
    
    // Fonction pour la compatibilité descendante
    if (!function_exists('define_path')) {
        function define_path($name, $value) {
            if (!defined($name)) {
                define($name, $value);
            }
            return $value;
        }
    }
    
    // Alias pour la compatibilité
    $define = 'define_path';

    // 1. Chemins de base absolus
    $rootPath = dirname(__DIR__, 2);
    $basePath = rtrim($_ENV['APP_URL'] ?? '', '/');
    
    // 2. Chemins du système de fichiers
    $define('ROOT_PATH', $rootPath);
    $define('INCLUDE_PATH', $rootPath . '/includes');
    $define('TEMPLATES_PATH', $rootPath . '/templates');
    $define('ADMIN_PATH', $rootPath . '/admin');
    $define('PUBLIC_PATH', $rootPath . '/public');
    
    // 3. Chemins pour les téléchargements
    $define('UPLOADS_PATH', $rootPath . '/uploads');
    $define('CACHE_PATH', $rootPath . '/cache');
    
    // 4. URL de base et chemins web
    $define('BASE_URL', $basePath);
    $define('ASSETS_URL', $basePath . '/assets');
    $define('UPLOADS_URL', $basePath . '/uploads');
    
    // 5. Chemins pour les avatars et médias
    $define('AVATARS_PATH', UPLOADS_PATH . '/avatars');
    $define('AVATARS_URL', UPLOADS_URL . '/avatars');
    $define('THUMBNAILS_PATH', UPLOADS_PATH . '/thumbnails');
    $define('THUMBNAILS_URL', UPLOADS_URL . '/thumbnails');
    $define('BOOKS_PATH', UPLOADS_PATH . '/books');
    $define('BOOKS_URL', UPLOADS_URL . '/books');
    $define('COVERS_PATH', UPLOADS_PATH . '/covers');
    $define('COVERS_URL', UPLOADS_URL . '/covers');
    
    // 6. Chemins système de base
    $define('PUBLIC_PATH', $rootPath . '/public');
    $define('INCLUDE_PATH', $rootPath . '/includes');
    $define('FULL_INCLUDE_PATH', $rootPath . '/includes');
    
    // 7. Chemins de base
    $cachePath = $rootPath . '/cache';
    $logsPath = $rootPath . '/logs';
    
    // 8. Chemins de cache et logs
    $define('CACHE_PATH', $cachePath);
    $define('LOGS_PATH', $logsPath);
    $define('TEMP_PATH', $rootPath . '/temp');
    
    // 9. Chemins dépendants
    $define('IMAGE_CACHE_PATH', $cachePath . '/images');
    $define('VIEW_CACHE_PATH', $cachePath . '/views');
    $define('ERROR_LOG', $logsPath . '/error.log');
    $define('DEBUG_LOG', $logsPath . '/debug.log');
    
    // 10. Chemins pour les assets
    $define('ASSETS_PATH', $rootPath . '/public/assets');
    $define('CSS_URL', $basePath . '/assets/css');
    $define('JS_URL', $basePath . '/assets/js');
    $define('IMAGES_URL', $basePath . '/assets/images');
    $define('FONTS_URL', $basePath . '/assets/fonts');
    
    // 11. Chemins par défaut
    $define('DEFAULT_AVATAR_PATH', '/assets/images/default-avatar.png');
    $define('DEFAULT_AVATAR_URL', $basePath . '/assets/images/default-avatar.png');
    $define('DEFAULT_BOOK_COVER', '/assets/images/default-book-cover.jpg');
    $define('DEFAULT_BOOK_COVER_URL', $basePath . '/assets/images/default-book-cover.jpg');

    // 12. Chemins par défaut
    $define('DEFAULT_AVATAR_PATH', '/assets/images/default-avatar.png');
    $define('DEFAULT_AVATAR_URL', $basePath . '/assets/images/default-avatar.png');
    $define('DEFAULT_BOOK_COVER', '/assets/images/default-book-cover.jpg');
    $define('DEFAULT_BOOK_COVER_URL', $basePath . '/assets/images/default-book-cover.jpg');
}