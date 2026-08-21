<?php
/**
 * Gestionnaire d'erreurs personnalisé
 * 
 * Ce fichier configure la gestion des erreurs et des exceptions pour l'application.
 */

// Inclure les fonctions nécessaires
require_once __DIR__ . '/functions.php';

// Niveaux d'erreur à afficher selon l'environnement
$is_dev = (getenv('APP_ENV') === 'development' || in_array($_SERVER['SERVER_ADDR'] ?? '', ['127.0.0.1', '::1']));
define('IS_DEV', $is_dev);

if ($is_dev) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
} else {
    error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT & ~E_NOTICE);
    ini_set('display_errors', '0');
    ini_set('display_startup_errors', '0');
    ini_set('log_errors', '1');
    ini_set('error_log', __DIR__ . '/../logs/php_errors.log');
}

/**
 * Affiche une page d'erreur
 * 
 * @param int $code Code d'erreur HTTP (404, 500, etc.)
 * @param string $message Message d'erreur
 * @return void
 */
function show_error_page($code = 500, $message = 'Une erreur est survenue') {
    // Nettoyer tout le tampon de sortie
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    
    // Définir le code de réponse HTTP
    http_response_code($code);
    
    // Déterminer le fichier de la page d'erreur
    $error_page = __DIR__ . "/error_pages/{$code}.php";
    
    // Si le fichier d'erreur spécifique n'existe pas, utiliser la page 500
    if (!file_exists($error_page)) {
        $error_page = __DIR__ . '/error_pages/500.php';
    }
    
    // Inclure la page d'erreur
    include $error_page;
    exit;
}

/**
 * Gestionnaire d'erreurs personnalisé
 */
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    // Ne pas exécuter si l'erreur est supprimée par l'opérateur @
    if (!(error_reporting() & $errno)) {
        return false;
    }
    
    // Journaliser l'erreur
    error_log("Erreur PHP [$errno] $errstr dans $errfile à la ligne $errline");
    
    // Si c'est une erreur fatale, laisser le gestionnaire d'arrêt la gérer
    if (in_array($errno, [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR])) {
        return false;
    }
    
    // Pour les autres erreurs, afficher la page d'erreur 500 en production
    if (!IS_DEV) {
        show_error_page(500, $errstr);
    }
    
    // Empêcher l'exécution du gestionnaire d'erreurs interne de PHP
    return true;
});

/**
 * Gestionnaire d'exceptions personnalisé
 */
set_exception_handler(function (Throwable $e) {
    // Journaliser l'exception
    error_log('Exception non capturée : ' . $e->getMessage() . ' dans ' . $e->getFile() . ' à la ligne ' . $e->getLine());
    
    // Nettoyer tout le tampon de sortie
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    
    // Afficher la page d'erreur appropriée
    if (is_ajax_request()) {
        if (!headers_sent()) {
            header('Content-Type: application/json');
            http_response_code(500);
        }
        echo json_encode([
            'error' => true,
            'message' => 'Une erreur est survenue. Veuillez réessayer plus tard.'
        ]);
    } else if (IS_DEV) {
        echo '<h1>Erreur</h1>';
        echo '<p><strong>Message:</strong> ' . htmlspecialchars($e->getMessage()) . '</p>';
        echo '<p><strong>Fichier:</strong> ' . htmlspecialchars($e->getFile()) . ' à la ligne ' . $e->getLine() . '</p>';
        echo '<pre>' . htmlspecialchars($e->getTraceAsString()) . '</pre>';
    } else {
        show_error_page(500, $e->getMessage());
    }
    
    exit(1);
});

// Gestion des erreurs fatales
register_shutdown_function(function() {
    $error = error_get_last();
    
    // Vérifier s'il s'agit d'une erreur fatale
    if ($error !== null && ($error['type'] & (E_ERROR | E_PARSE | E_CORE_ERROR | E_COMPILE_ERROR | E_USER_ERROR))) {
        // Journaliser l'erreur fatale
        error_log(sprintf(
            'Erreur fatale : %s dans %s à la ligne %d',
            $error['message'],
            $error['file'],
            $error['line']
        ));
        
        // Afficher la page d'erreur appropriée
        if (is_ajax_request()) {
            if (!headers_sent()) {
                header('Content-Type: application/json');
                http_response_code(500);
            }
            echo json_encode([
                'error' => true,
                'message' => 'Une erreur est survenue. Veuillez réessayer plus tard.'
            ]);
        } else if (IS_DEV) {
            echo '<!DOCTYPE html><html><head><title>Erreur fatale</title><style>body{font-family:Arial,sans-serif;line-height:1.6;margin:0;padding:20px;color:#333;}</style></head><body>';
            echo '<h1>Erreur fatale</h1>';
            echo '<p><strong>Message:</strong> ' . htmlspecialchars($error['message']) . '</p>';
            echo '<p><strong>Fichier:</strong> ' . htmlspecialchars($error['file']) . ' à la ligne ' . $error['line'] . '</p>';
            echo '<pre>' . print_r($error, true) . '</pre>';
            echo '</body></html>';
        } else {
            show_error_page(500, 'Une erreur fatale est survenue');
        }
    }
});