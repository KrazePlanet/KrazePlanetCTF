<?php
/**
 * Configuration de la journalisation
 * 
 * Ce fichier contient la configuration pour le système de journalisation
 */

return [
    /*
    |--------------------------------------------------------------------------
    | Répertoire des logs
    |--------------------------------------------------------------------------
    |
    | Ce paramètre définit l'emplacement où les fichiers de logs seront stockés.
    |
    */
    'log_dir' => LOGS_PATH,

    /*
    |--------------------------------------------------------------------------
    | Fichier de log principal
    |--------------------------------------------------------------------------
    |
    | Nom du fichier de log principal. Peut utiliser des variables de date.
    |
    */
    'log_file' => 'app_' . date('Y-m-d') . '.log',

    /*
    |--------------------------------------------------------------------------
    | Fichier d'erreurs PHP
    |--------------------------------------------------------------------------
    |
    | Fichier où seront enregistrées les erreurs PHP.
    |
    */
    'error_log_file' => 'php_errors.log',

    /*
    |--------------------------------------------------------------------------
    | Taille maximale des fichiers de log
    |--------------------------------------------------------------------------
    |
    | Taille maximale en octets d'un fichier de log avant rotation.
    |
    */
    'max_file_size' => 5 * 1024 * 1024, // 5MB

    /*
    |--------------------------------------------------------------------------
    | Nombre maximum de fichiers de log
    |--------------------------------------------------------------------------
    |
    | Nombre maximum de fichiers de log à conserver.
    |
    */
    'max_files' => 30, // 30 jours de logs

    /*
    |--------------------------------------------------------------------------
    | Niveau de log par défaut
    |--------------------------------------------------------------------------
    |
    | Niveau de log par défaut pour les messages sans niveau spécifié.
    |
    */
    'default_level' => 'info',

    /*
    |--------------------------------------------------------------------------
    | Niveaux de log disponibles
    |--------------------------------------------------------------------------
    |
    | Liste des niveaux de log disponibles, du moins critique au plus critique.
    |
    */
    'levels' => [
        'debug'     => 100,
        'info'      => 200,
        'notice'    => 250,
        'warning'   => 300,
        'error'     => 400,
        'critical'  => 500,
        'alert'     => 550,
        'emergency' => 600,
    ],

    /*
    |--------------------------------------------------------------------------
    | Format des messages de log
    |--------------------------------------------------------------------------
    |
    | Format des messages de log. Les variables disponibles sont :
    | - {datetime} : Date et heure du log
    | - {level} : Niveau du log (en majuscules)
    | - {message} : Le message de log
    | - {context} : Contexte supplémentaire (sérialisé en JSON)
    |
    */
    'format' => '[{datetime}] [{level}] {message} {context}',

    /*
    |--------------------------------------------------------------------------
    | Format de date
    |--------------------------------------------------------------------------
    |
    | Format de la date utilisé dans les logs.
    |
    */
    'date_format' => 'Y-m-d H:i:s',

    /*
    |--------------------------------------------------------------------------
    | Journalisation des requêtes SQL
    |--------------------------------------------------------------------------
    |
    | Active la journalisation des requêtes SQL.
    |
    */
    'log_queries' => true,

    /*
    |--------------------------------------------------------------------------
    | Journalisation des erreurs 404
    |--------------------------------------------------------------------------
    |
    | Active la journalisation des erreurs 404.
    |
    */
    'log_404' => true,

    /*
    |--------------------------------------------------------------------------
    | Journalisation des erreurs de validation
    |--------------------------------------------------------------------------
    |
    | Active la journalisation des erreurs de validation de formulaire.
    |
    */
    'log_validation_errors' => true,
];
