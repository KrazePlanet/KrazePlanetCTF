<?php
/**
 * Configuration de l'application
 * 
 * Ce fichier contient toutes les configurations de l'application.
 * Il doit être inclus après la définition de ROOT_PATH.
 */

// Vérifier les dépendances
if (!defined('ROOT_PATH')) {
    die('ERREUR: ROOT_PATH doit être défini avant config.php');
}

// Charger les variables d'environnement
$envFile = ROOT_PATH . '/.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        // Ignorer les commentaires et les lignes vides
        if (strpos(trim($line), '#') === 0) {
            continue;
        }
        
        // Séparer le nom et la valeur
        list($name, $value) = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value, " \t\n\r\0\x0B\"'");
        
        // Définir la variable d'environnement si elle n'existe pas déjà
        if (!array_key_exists($name, $_ENV)) {
            $_ENV[$name] = $value;
            putenv("$name=$value");
        }
    }
}

define('ENVIRONMENT', $_ENV['APP_ENV'] ?? 'production');

// Configuration de la base de données
define('DB_HOST', $_ENV['DB_HOST'] ?? 'localhost');
define('DB_NAME', $_ENV['DB_NAME'] ?? 'read_sphere_db');
define('DB_USER', $_ENV['DB_USER'] ?? 'root');
define('DB_PASS', $_ENV['DB_PASS'] ?? '');
define('DB_CHARSET', $_ENV['DB_CHARSET'] ?? 'utf8mb4');


// Configuration des sessions
$session_config = [
    'cookie_httponly' => 1,
    'use_only_cookies' => 1,
    'cookie_secure' => isset($_SERVER['HTTPS']),
    'cookie_samesite' => 'Lax',
    'name' => $_ENV['SESSION_NAME'] ?? 'bookreview_session',
    'cookie_lifetime' => (int) ($_ENV['SESSION_LIFETIME'] ?? 86400),
    'gc_maxlifetime' => (int) ($_ENV['SESSION_LIFETIME'] ?? 86400)
];

// Configuration du fuseau horaire
date_default_timezone_set('Europe/Paris');

// Configuration des erreurs
error_reporting(E_ALL);
ini_set('display_errors', ENVIRONMENT === 'development' ? '1' : '0');
ini_set('log_errors', '1');

// Configuration des logs
try {
    $log_dir = ROOT_PATH . '/logs';
    if (!is_dir($log_dir) && !mkdir($log_dir, 0755, true)) {
        throw new RuntimeException("Impossible de créer le répertoire de logs : $log_dir");
    }

    $log_file = $log_dir . '/php_errors.log';
    if ((!is_writable($log_dir) && !is_writable($log_file)) || !ini_set('error_log', $log_file)) {
        throw new RuntimeException("Impossible d'écrire dans le fichier de log : $log_file");
    }
    
    // Vérification des constantes de base de données
    $required_constants = ['DB_HOST', 'DB_NAME', 'DB_USER'];
    $missing_constants = [];
    
    foreach ($required_constants as $constant) {
        if (!defined($constant)) {
            $missing_constants[] = $constant;
        }
    }
    
    if (!empty($missing_constants)) {
        throw new RuntimeException("Constantes de base de données manquantes : " . implode(', ', $missing_constants));
    }
} catch (RuntimeException $e) {
    // En mode développement, on affiche l'erreur complète
    if (defined('ENVIRONMENT') && ENVIRONMENT === 'development') {
        die("ERREUR DE CONFIGURATION : " . $e->getMessage());
    }
    
    // En production, on enregistre l'erreur et on affiche un message générique
    if (function_exists('error_log')) {
        error_log('Erreur de configuration : ' . $e->getMessage());
    }
    die('Une erreur de configuration est survenue. Veuillez contacter l\'administrateur.');
}

// Configuration de l'upload
ini_set('upload_max_filesize', '10M');
ini_set('post_max_size', '12M');
ini_set('max_file_uploads', 5);

// Configuration de la mémoire
ini_set('memory_limit', '128M');

// Configuration des chemins d'inclusion
$include_paths = [
    get_include_path(),
    ROOT_PATH . '/includes',
    ROOT_PATH
];

// Définir le nouveau chemin d'inclusion
set_include_path(implode(PATH_SEPARATOR, array_unique($include_paths)));

// Autoloader des classes
spl_autoload_register(function ($class) {
    $file = str_replace('\\', DIRECTORY_SEPARATOR, $class) . '.php';
    $paths = explode(PATH_SEPARATOR, get_include_path());
    
    foreach ($paths as $path) {
        $fullPath = $path . DIRECTORY_SEPARATOR . $file;
        if (file_exists($fullPath)) {
            require $fullPath;
            return;
        }
    }
});

/**
 * Configure les paramètres de session
 */
function configure_session() {
    global $session_config;
    
    if (session_status() === PHP_SESSION_NONE) {
        // Apply session configuration
        foreach ($session_config as $key => $value) {
            if ($key === 'name') {
                session_name($value);
            } else {
                ini_set('session.' . $key, $value);
            }
        }
        return true;
    }
    return false;
}