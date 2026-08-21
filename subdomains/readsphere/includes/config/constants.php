<?php
/**
 * Constantes de configuration
 * 
 * Ce fichier définit les constantes utilisées dans l'application.
 */

// Charger les configurations
$paths = require __DIR__ . '/paths.php';
$imagesConfig = require __DIR__ . '/images.php';

// Chemins de base
define('BASE_PATH', $paths['base_path']);

// Chemins des téléchargements
define('UPLOADS_PATH', $paths['uploads']['path']);
define('UPLOADS_URL', $paths['uploads']['url']);

// Chemins des avatars
define('AVATARS_UPLOAD_PATH', $paths['avatars']['path']);
define('AVATARS_PUBLIC_PATH', $paths['avatars']['url']);
define('DEFAULT_AVATAR', $paths['avatars']['default']);

// Chemins des polices
define('FONTS_PATH', $paths['fonts']['path']);
define('FONTS_URL', $paths['fonts']['url']);

// Configuration des images
define('IMAGE_MAX_SIZE', $imagesConfig['max_upload_size']);
define('IMAGE_ALLOWED_TYPES', $imagesConfig['allowed_types']);

// Configuration des avatars
define('AVATAR_MAX_WIDTH', $imagesConfig['avatars']['max_width']);
define('AVATAR_MAX_HEIGHT', $imagesConfig['avatars']['max_height']);
define('AVATAR_QUALITY', $imagesConfig['avatars']['quality']);
