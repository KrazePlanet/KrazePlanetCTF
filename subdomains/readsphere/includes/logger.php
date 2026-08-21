<?php
/**
 * Configuration et fonctions de journalisation centralisées
 */

// Charger la configuration des logs
$logger_config = [];
$config_file = dirname(__DIR__) . '/config/logging.php';

if (file_exists($config_file)) {
    $logger_config = require $config_file;
} else {
    // Configuration par défaut
    $logger_config = [
        'log_dir' => dirname(__DIR__) . '/logs',
        'log_file' => 'app_' . date('Y-m-d') . '.log',
        'error_log_file' => 'php_errors.log',
        'max_file_size' => 5 * 1024 * 1024, // 5MB
        'max_files' => 30,
        'default_level' => 'info',
        'levels' => [
            'debug' => 100,
            'info' => 200,
            'warning' => 300,
            'error' => 400,
            'critical' => 500
        ],
        'format' => '[{datetime}] [{level}] {message} {context}',
        'date_format' => 'Y-m-d H:i:s'
    ];
}

// Définition des constantes de niveau de log
define('LOG_LEVEL_DEBUG', 'debug');
define('LOG_LEVEL_INFO', 'info');
define('LOG_LEVEL_WARNING', 'warning');
define('LOG_LEVEL_ERROR', 'error');

/**
 * Écrit un message dans le journal d'application
 */
function log_message($message, $level = null, $file = null, array $context = []) {
    global $logger_config;
    
    // Utiliser le niveau par défaut si non spécifié
    if ($level === null) {
        $level = $logger_config['default_level'];
    }
    
    // Niveau de log en minuscules
    $level = strtolower($level);
    
    // Vérifier si le niveau de log est valide
    if (!isset($logger_config['levels'][$level])) {
        $level = $logger_config['default_level'];
    }
    
    // Déterminer le fichier de log
    if ($file === null) {
        $log_dir = $logger_config['log_dir'];
        
        // Créer le répertoire si nécessaire
        if (!is_dir($log_dir)) {
            mkdir($log_dir, 0755, true);
        }
        
        $file = $log_dir . '/' . $logger_config['log_file'];
    }
    
    // Préparer le contexte
    $context_str = '';
    if (!empty($context)) {
        $context = array_map(function($value) {
            if (is_array($value) || is_object($value)) {
                return json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
            }
            return $value;
        }, $context);
        
        $context_str = json_encode($context, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }
    
    // Formater le message
    $replace = [
        '{datetime}' => date($logger_config['date_format']),
        '{level}' => strtoupper($level),
        '{message}' => $message,
        '{context}' => $context_str,
    ];
    
    $log_message = str_replace(
        array_keys($replace),
        array_values($replace),
        $logger_config['format']
    ) . PHP_EOL;
    
    // Rotation des logs
    if (file_exists($file) && filesize($file) > $logger_config['max_file_size']) {
        $backup_file = $file . '.' . date('YmdHis');
        if (rename($file, $backup_file)) {
            // Compression optionnelle
            if (function_exists('gzcompress')) {
                $compressed = gzcompress(file_get_contents($backup_file));
                file_put_contents($backup_file . '.gz', $compressed);
                unlink($backup_file);
            }
        }
    }
    
    // Écrire dans le fichier
    return file_put_contents($file, $log_message, FILE_APPEND | LOCK_EX) !== false;
}

/**
 * Journalise une exception
 */
function log_exception(Throwable $e, $level = null, array $context = []) {
    global $logger_config;
    
    if ($level === null) {
        $level = 'error';
        if ($e instanceof PDOException) {
            $level = 'critical';
        }
    }
    
    $message = sprintf(
        '%s: %s in %s:%d',
        get_class($e),
        $e->getMessage(),
        $e->getFile(),
        $e->getLine()
    );
    
    $context = array_merge([
        'exception' => get_class($e),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => $e->getTraceAsString()
    ], $context);
    
    return log_message($message, $level, null, $context);
}

/**
 * Configuration de la gestion des erreurs
 */
function setup_error_handling() {
    global $logger_config;
    
    // Configuration de base - tout logger
    error_reporting(E_ALL);
    
    // Créer le répertoire de logs s'il n'existe pas
    if (!is_dir($logger_config['log_dir'])) {
        mkdir($logger_config['log_dir'], 0755, true);
    }
    
    // Définir le chemin complet du fichier de log des erreurs PHP
    $error_log_path = $logger_config['log_dir'] . '/' . $logger_config['error_log_file'];
    
    // Configurer PHP pour utiliser notre fichier de log personnalisé
    ini_set('log_errors', '1');
    ini_set('error_log', $error_log_path);
    
    // Désactiver l'affichage des erreurs dans la sortie
    ini_set('display_errors', '0');
    ini_set('display_startup_errors', '0');
    
    // S'assurer que le fichier de log est accessible en écriture
    if (!is_writable($logger_config['log_dir'])) {
        error_log("Le répertoire de logs n'est pas accessible en écriture : " . $logger_config['log_dir']);
    } elseif (!file_exists($error_log_path) && !touch($error_log_path)) {
        error_log("Impossible de créer le fichier de log : " . $error_log_path);
    } elseif (!is_writable($error_log_path)) {
        error_log("Le fichier de log n'est pas accessible en écriture : " . $error_log_path);
    }
    
    // Gestionnaire d'erreurs
    set_error_handler(function($errno, $errstr, $errfile, $errline) {
        $level = 'error';
        
        switch ($errno) {
            case E_WARNING:
            case E_USER_WARNING: $level = 'warning'; break;
            case E_NOTICE:
            case E_USER_NOTICE: $level = 'info'; break;
            case E_STRICT:
            case E_DEPRECATED:
            case E_USER_DEPRECATED: $level = 'debug'; break;
        }
        
        log_message("$errstr in $errfile on line $errline", $level, null, [
            'errno' => $errno,
            'errfile' => $errfile,
            'errline' => $errline
        ]);
        
        return true; // Empêche le gestionnaire d'erreurs interne
    });
    
    // Gestionnaire d'exceptions
    set_exception_handler(function(Throwable $e) {
        log_exception($e);
        
        if (!headers_sent()) {
            http_response_code(500);
        }
        
        if (defined('IS_DEV') && IS_DEV) {
            echo "<h1>Une erreur est survenue</h1>";
            echo "<p><strong>Message:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
            echo "<p><strong>Fichier:</strong> " . htmlspecialchars($e->getFile()) . " (ligne " . $e->getLine() . ")</p>";
        } else {
            include __DIR__ . '/../templates/errors/500.php';
        }
        
        exit(1);
    });
    
    // Gestion des erreurs fatales
    register_shutdown_function(function() {
        $error = error_get_last();
        
        if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR])) {
            log_message(
                "FATAL: {$error['message']} in {$error['file']} on line {$error['line']}",
                'critical',
                null,
                ['error' => $error]
            );
            
            if (!headers_sent()) {
                http_response_code(500);
            }
            
            if (defined('IS_DEV') && IS_DEV) {
                echo "<h1>Erreur fatale</h1>";
                echo "<p><strong>Message:</strong> " . htmlspecialchars($error['message']) . "</p>";
                echo "<p><strong>Fichier:</strong> " . htmlspecialchars($error['file']) . " (ligne " . $error['line'] . ")</p>";
            } else {
                include __DIR__ . '/../templates/errors/500.php';
            }
        }
    });
}
