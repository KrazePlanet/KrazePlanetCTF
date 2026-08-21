<?php
/**
 * Gestionnaire d'avatars
 * 
 * Cette classe gère le téléchargement, la génération et la suppression des avatars utilisateurs.
 */

// Vérifier si les chemins sont chargés
if (!defined('PATHS_LOADED')) {
    require_once __DIR__ . '/../config/paths.php';
}

// Charger la configuration des images
$config = require __DIR__ . '/../config/images.php';

class AvatarManager
{
    private $config;
    private $paths;
    private $defaultInitials = 'U';

    public function __construct()
    {
        global $config;

        $this->config = $config;
        
        // Définir les chemins de base
        $rootPath = dirname(__DIR__, 2);
        $basePath = rtrim($config['base_url'] ?? '', '/');
        
        $this->paths = [
            'base' => [
                'path' => $rootPath,
                'url' => $basePath
            ],
            'public' => [
                'path' => $rootPath . '/public',
                'url' => $basePath
            ],
            'avatars' => [
                'path' => $rootPath . '/public/uploads/avatars',
                'url' => $basePath . '/uploads/avatars'
            ],
            'thumbnails' => [
                'path' => $rootPath . '/public/uploads/thumbnails',
                'url' => $basePath . '/uploads/thumbnails'
            ],
            'cache_dir' => [
                'images' => $rootPath . '/cache/images'
            ],
            'default_avatar' => [
                'path' => $rootPath . '/public/assets/images/default-avatar.png',
                'url' => $basePath . '/assets/images/default-avatar.png'
            ]
        ];

        // Créer les dossiers nécessaires
        $this->createDirectories();
    }

    /**
     * Crée les répertoires nécessaires s'ils n'existent pas
     */
    private function createDirectories()
    {
        $dirs = [
            $this->paths['avatars']['path'],
            $this->paths['thumbnails']['path'],
            $this->paths['cache_dir']['images']
        ];

        foreach ($dirs as $dir) {
            if ($dir && !file_exists($dir)) {
                if (!@mkdir($dir, 0755, true) && !is_dir($dir)) {
                    error_log("Impossible de créer le répertoire : $dir");
                }
            }
        }
    }

    /**
     * Télécharge et traite un avatar
     * 
     * @param array $file Fichier uploadé ($_FILES['avatar'])
     * @param int $userId ID de l'utilisateur
     * @param string $username Nom d'utilisateur pour les initiales
     * @return array Tableau avec 'success' et 'message' ou 'filename'
     */
    public function uploadAvatar($file, $userId, $username = null)
    {
        // Vérifier les erreurs d'upload
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return [
                'success' => false,
                'message' => $this->getUploadErrorMessage($file['error'])
            ];
        }

        // Vérifier le type MIME
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mime, $this->config['allowed_types'])) {
            return [
                'success' => false,
                'message' => 'Type de fichier non autorisé. Formats acceptés : ' .
                    implode(', ', $this->config['allowed_types'])
            ];
        }

        // Vérifier la taille du fichier
        if ($file['size'] > $this->config['max_size']) {
            $maxSizeMB = $this->config['max_size'] / (1024 * 1024);
            return [
                'success' => false,
                'message' => "La taille du fichier ne doit pas dépasser {$maxSizeMB}MB"
            ];
        }

        // Créer le dossier s'il n'existe pas
        if (!is_dir($this->config['upload_path'])) {
            if (!mkdir($this->config['upload_path'], 0755, true)) {
                return [
                    'success' => false,
                    'message' => 'Impossible de créer le dossier de destination.'
                ];
            }
        }

        // Générer un nom de fichier unique
        $extension = $this->getExtensionFromMime($mime);
        $filename = 'user_' . $userId . '_' . uniqid() . $extension;
        $filepath = $this->config['upload_path'] . $filename;

        // Déplacer et redimensionner l'image
        try {
            $this->resizeAndSaveImage($file['tmp_name'], $filepath);

            return [
                'success' => true,
                'filename' => $filename,
                'filepath' => $filepath,
                'public_url' => $this->config['public_path'] . $filename
            ];

        } catch (Exception $e) {
            error_log('Erreur lors du traitement de l\'avatar : ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Une erreur est survenue lors du traitement de l\'image.'
            ];
        }
    }

    /**
     * Génère un avatar par défaut avec les initiales
     * 
     * @param string $username Nom d'utilisateur pour les initiales
     * @param int $userId ID de l'utilisateur pour le nom de fichier
     * @return array Tableau avec 'success' et 'filename' ou 'message'
     */
    public function generateDefaultAvatar($username, $userId)
    {
        try {
            $initials = $this->getInitials($username);
            $filename = 'default_' . $userId . '_' . md5($initials . time()) . '.png';
            $filepath = rtrim($this->paths['avatars']['path'], '/') . '/' . $filename;

            // Créer le dossier s'il n'existe pas
            if (!is_dir(dirname($filepath))) {
                mkdir(dirname($filepath), 0755, true);
            }

            // Récupérer les dimensions depuis la configuration ou utiliser des valeurs par défaut
            $width = $this->config['avatars']['default']['width'] ?? 200;
            $height = $this->config['avatars']['default']['height'] ?? 200;
            $fontSize = ($width * 0.4); // Taille de police proportionnelle

            // Créer une image avec fond de couleur
            $image = imagecreatetruecolor($width, $height);
            if ($image === false) {
                throw new Exception('Impossible de créer l\'image');
            }

            // Activer le mode anti-alias pour de meilleurs résultats
            if (function_exists('imageantialias')) {
                imageantialias($image, true);
            }

            // Récupérer les couleurs depuis la configuration ou utiliser des valeurs par défaut
            $bgColor = $this->hexToRgb($this->config['avatars']['default']['bg_color'] ?? '#6B7280');
            $textColor = $this->hexToRgb($this->config['avatars']['default']['text_color'] ?? '#FFFFFF');

            // Allouer les couleurs
            $bg = imagecolorallocate($image, $bgColor['r'], $bgColor['g'], $bgColor['b']);
            $text = imagecolorallocate($image, $textColor['r'], $textColor['g'], $textColor['b']);

            // Remplir l'image avec la couleur de fond
            imagefill($image, 0, 0, $bg);

            // Calculer la position du texte pour le centrer
            $fontPath = $this->paths['public'] . '/assets/fonts/Roboto-Regular.ttf';
            $useTtf = file_exists($fontPath);

            if ($useTtf) {
                // Utiliser une police TTF si disponible
                $bbox = imagettfbbox($fontSize, 0, $fontPath, $initials);
                $textWidth = $bbox[4] - $bbox[0];
                $textHeight = $bbox[1] - $bbox[5];
                $x = ($width - $textWidth) / 2;
                $y = ($height - $textHeight) / 2 + $textHeight;
                imagettftext($image, $fontSize, 0, (int) $x, (int) $y, $text, $fontPath, $initials);
            } else {
                // Utiliser la police système par défaut
                $fontSize = 5; // Taille de police pour imagestring (1-5)
                $textWidth = imagefontwidth($fontSize) * strlen($initials);
                $textHeight = imagefontheight($fontSize);
                $x = ($width - $textWidth) / 2;
                $y = ($height - $textHeight) / 2;
                imagestring($image, $fontSize, (int) $x, (int) $y, $initials, $text);
            }

            // Enregistrer l'image
            if (!imagepng($image, $filepath)) {
                throw new Exception('Échec de l\'enregistrement de l\'image');
            }

            // Libérer la mémoire
            imagedestroy($image);

            // Vérifier que le fichier a bien été créé
            if (!file_exists($filepath)) {
                throw new Exception('Le fichier d\'avatar n\'a pas été créé');
            }

            return [
                'success' => true,
                'filename' => $filename,
                'filepath' => $filepath,
                'public_url' => rtrim($this->paths['avatars']['url'], '/') . '/' . $filename
            ];

        } catch (Exception $e) {
            error_log('Erreur lors de la génération de l\'avatar par défaut : ' . $e->getMessage());

            // Nettoyer en cas d'erreur
            if (isset($image) && is_resource($image)) {
                imagedestroy($image);
            }
            if (isset($filepath) && file_exists($filepath)) {
                @unlink($filepath);
            }

            // Retourner l'URL de l'avatar par défaut en cas d'échec
            return [
                'success' => false,
                'message' => 'Impossible de générer l\'avatar par défaut.',
                'public_url' => $this->paths['avatars']['default']['url']
            ];
        }
    }

    /**
     * Supprime un fichier d'avatar
     * 
     * @param string $filename Nom du fichier à supprimer
     * @return bool True si la suppression a réussi
     */
    public function deleteAvatar($filename)
    {
        if (empty($filename) || $filename === 'default.png') {
            return true; // Ne pas supprimer l'avatar par défaut
        }

        $filepath = $this->config['upload_path'] . $filename;

        if (file_exists($filepath) && is_file($filepath)) {
            return unlink($filepath);
        }

        return true;
    }

    /**
     * Obtient l'URL publique d'un avatar
     * 
     * @param string|null $filename Nom du fichier
     * @return string URL complète de l'avatar
     */
    public function getAvatarUrl($filename = null, $size = 'medium')
    {
        // Si pas de fichier spécifié, retourner l'avatar par défaut
        if (empty($filename)) {
            return $this->paths['default_avatar']['url'];
        }

        $filepath = $this->paths['avatars']['path'] . '/' . $filename;

        // Vérifier si le fichier existe
        if (!file_exists($filepath) || !is_file($filepath)) {
            return $this->paths['default_avatar']['url'];
        }

        // Si la taille demandée est la taille originale
        if ($size === 'original') {
            return $this->paths['avatars']['url'] . '/' . $filename;
        }

        // Vérifier si une version en cache de la taille demandée existe
        $cachedFile = $this->getCachedFilename($filename, $size);
        $cachedPath = $this->paths['cache_dir']['images'] . '/' . $cachedFile;

        // Si le fichier en cache n'existe pas, le créer
        if (!file_exists($cachedPath)) {
            if (!$this->createThumbnail($filepath, $cachedPath, $size)) {
                // En cas d'échec, retourner l'original
                return $this->paths['avatars']['url'] . '/' . $filename;
            }
        }

        return rtrim($this->paths['base']['url'], '/') . '/cache/images/' . $cachedFile;
    }

    /**
     * Génère un nom de fichier en cache pour une image redimensionnée
     * 
     * @param string $filename Nom du fichier original
     * @param string $size Taille de l'image (small, medium, large)
     * @return string Nom du fichier en cache
     */
    protected function getCachedFilename($filename, $size)
    {
        $pathinfo = pathinfo($filename);
        $extension = $pathinfo['extension'] ?? 'jpg';
        $name = $pathinfo['filename'] ?? pathinfo($filename, PATHINFO_FILENAME);
        return $name . '_' . $size . '.' . $extension;
    }

    /**
     * Redimensionne et enregistre une image
     * 
     * @param string $sourcePath Chemin vers le fichier source
     * @param string $destinationPath Chemin de destination
     * @param string $size Taille de sortie (small, medium, large)
     * @return bool True en cas de succès, false sinon
     */
    protected function resizeAndSaveImage($sourcePath, $destinationPath, $size = 'medium')
    {
        // Vérifier si le fichier source existe
        if (!file_exists($sourcePath) || !is_file($sourcePath)) {
            error_log("Fichier source introuvable : $sourcePath");
            return false;
        }

        // Créer le répertoire de destination si nécessaire
        $destinationDir = dirname($destinationPath);
        if (!is_dir($destinationDir)) {
            if (!mkdir($destinationDir, 0755, true)) {
                error_log("Impossible de créer le répertoire de destination : $destinationDir");
                return false;
            }
        }

        // Récupérer les dimensions de l'image source
        list($srcWidth, $srcHeight, $type) = getimagesize($sourcePath);
        if (!$srcWidth || !$srcHeight) {
            error_log("Impossible de déterminer les dimensions de l'image : $sourcePath");
            return false;
        }

        // Définir les dimensions cibles en fonction de la taille demandée
        switch ($size) {
            case 'small':
                $maxWidth = $this->config['avatars']['sizes']['small']['width'] ?? 100;
                $maxHeight = $this->config['avatars']['sizes']['small']['height'] ?? 100;
                break;
            case 'large':
                $maxWidth = $this->config['avatars']['sizes']['large']['width'] ?? 400;
                $maxHeight = $this->config['avatars']['sizes']['large']['height'] ?? 400;
                break;
            case 'medium':
            default:
                $maxWidth = $this->config['avatars']['sizes']['medium']['width'] ?? 200;
                $maxHeight = $this->config['avatars']['sizes']['medium']['height'] ?? 200;
        }

        // Calculer les nouvelles dimensions en conservant le ratio
        $ratio = min($maxWidth / $srcWidth, $maxHeight / $srcHeight);
        $newWidth = (int) round($srcWidth * $ratio);
        $newHeight = (int) round($srcHeight * $ratio);

        // Créer une nouvelle image vide avec les dimensions cibles
        $newImage = imagecreatetruecolor($newWidth, $newHeight);
        if ($newImage === false) {
            error_log("Impossible de créer une nouvelle image");
            return false;
        }

        // Activer la transparence pour les PNG
        imagesavealpha($newImage, true);
        $transparent = imagecolorallocatealpha($newImage, 0, 0, 0, 127);
        imagefill($newImage, 0, 0, $transparent);

        // Créer l'image source à partir du fichier
        $sourceImage = $this->createImageFromFile($sourcePath, $type);
        if ($sourceImage === false) {
            imagedestroy($newImage);
            return false;
        }

        // Redimensionner l'image
        imagecopyresampled(
            $newImage,
            $sourceImage,
            0,
            0,
            0,
            0,
            $newWidth,
            $newHeight,
            $srcWidth,
            $srcHeight
        );

        // Enregistrer l'image redimensionnée
        $result = $this->saveImage($newImage, $destinationPath, $type);

        // Libérer la mémoire
        imagedestroy($sourceImage);
        imagedestroy($newImage);

        return $result;
    }

    /**
     * Crée une miniature d'une image
     * 
     * @param string $sourcePath Chemin vers le fichier source
     * @param string $destinationPath Chemin de destination
     * @param string $size Taille de la miniature (small, medium, large)
     * @return bool True en cas de succès, false sinon
     */
    private function createThumbnail($sourcePath, $destinationPath, $size = 'medium')
    {
        // Vérifier si le fichier source existe
        if (!file_exists($sourcePath)) {
            error_log("Le fichier source n'existe pas: $sourcePath");
            return false;
        }

        // Vérifier les dimensions et le type de l'image
        $imageInfo = @getimagesize($sourcePath);
        if ($imageInfo === false) {
            error_log("Le fichier n'est pas une image valide: $sourcePath");
            return false;
        }

        list($srcWidth, $srcHeight, $type) = $imageInfo;

        // Obtenir les dimensions cibles
        $targetSize = $this->config['thumbnails'][$size] ?? $this->config['thumbnails']['medium'];
        $targetWidth = $targetSize['width'];
        $targetHeight = $targetSize['height'];
        $crop = $targetSize['crop'] ?? false;

        // Calculer les nouvelles dimensions en conservant les proportions
        $ratio = $srcWidth / $srcHeight;

        if ($crop) {
            // Recadrer l'image pour qu'elle remplisse complètement la zone
            if ($srcWidth / $targetWidth > $srcHeight / $targetHeight) {
                $newWidth = $srcHeight * ($targetWidth / $targetHeight);
                $x = ($srcWidth - $newWidth) / 2;
                $srcX = (int) round($x);
                $srcY = 0;
                $srcWidth = (int) round($newWidth);
            } else {
                $newHeight = $srcWidth * ($targetHeight / $targetWidth);
                $y = ($srcHeight - $newHeight) / 2;
                $srcX = 0;
                $srcY = (int) round($y);
                $srcHeight = (int) round($newHeight);
            }
        } else {
            // Redimensionner en conservant les proportions
            if ($targetWidth / $targetHeight > $ratio) {
                $targetWidth = (int) round($targetHeight * $ratio);
            } else {
                $targetHeight = (int) round($targetWidth / $ratio);
            }
            $srcX = 0;
            $srcY = 0;
        }

        // Créer une nouvelle image vide
        $newImage = imagecreatetruecolor($targetWidth, $targetHeight);

        // Conserver la transparence pour les PNG et GIF
        if ($type === IMAGETYPE_PNG || $type === IMAGETYPE_GIF) {
            imagecolortransparent($newImage, imagecolorallocatealpha($newImage, 0, 0, 0, 127));
            imagealphablending($newImage, false);
            imagesavealpha($newImage, true);
        }

        // Charger l'image source
        $sourceImage = $this->createImageFromFile($sourcePath, $type);
        if ($sourceImage === false) {
            return false;
        }

        // Redimensionner l'image
        imagecopyresampled(
            $newImage,
            $sourceImage,
            0,
            0,
            $srcX,
            $srcY,
            $targetWidth,
            $targetHeight,
            $srcWidth,
            $srcHeight
        );

        // Enregistrer l'image redimensionnée
        $result = $this->saveImage($newImage, $destinationPath, $type);

        // Libérer la mémoire
        imagedestroy($newImage);
        imagedestroy($sourceImage);

        return $result !== false;
    }

    /**
     * Crée une ressource image à partir d'un fichier
     * 
     * @param string $filepath Chemin vers le fichier image
     * @param int $type Type d'image (constante IMAGETYPE_*)
     * @return resource|false Ressource image ou false en cas d'échec
     */
    private function createImageFromFile($filepath, $type)
    {
        switch ($type) {
            case IMAGETYPE_JPEG:
                return imagecreatefromjpeg($filepath);
            case IMAGETYPE_PNG:
                return imagecreatefrompng($filepath);
            case IMAGETYPE_GIF:
                return imagecreatefromgif($filepath);
            case IMAGETYPE_WEBP:
                if (function_exists('imagecreatefromwebp')) {
                    return imagecreatefromwebp($filepath);
                }
                break;
        }

        error_log("Type d'image non supporté: " . image_type_to_mime_type($type));
        return false;
    }

    /**
     * Enregistre une ressource image dans un fichier
     * 
     * @param resource $image Ressource image
     * @param string $filepath Chemin de destination
     * @param int $type Type d'image (constante IMAGETYPE_*)
     * @return bool True en cas de succès, false sinon
     */
    private function saveImage($image, $filepath, $type)
    {
        $quality = $this->config['default_quality'];

        switch ($type) {
            case IMAGETYPE_JPEG:
                return imagejpeg($image, $filepath, $quality);
            case IMAGETYPE_PNG:
                // Qualité pour PNG (0-9, 0 = sans compression)
                $quality = 9 - round(($quality * 9) / 100);
                return imagepng($image, $filepath, $quality);
            case IMAGETYPE_GIF:
                return imagegif($image, $filepath);
            case IMAGETYPE_WEBP:
                if (function_exists('imagewebp')) {
                    return imagewebp($image, $filepath, $quality);
                }
                break;
        }

        return false;
    }



    /**
     * Obtient les initiales d'un nom
     */
    private function getInitials($name)
    {
        if (empty($name)) {
            return $this->defaultInitials;
        }

        $words = preg_split('/\s+/', trim($name));
        $initials = '';

        foreach ($words as $word) {
            $initials .= strtoupper(substr($word, 0, 1));
        }

        return substr($initials, 0, 2) ?: $this->defaultInitials;
    }

    /**
     * Convertit une couleur hexadécimale en RGB
     */
    private function hexToRgb($hex)
    {
        $hex = str_replace('#', '', $hex);

        if (strlen($hex) == 3) {
            $r = hexdec(str_repeat(substr($hex, 0, 1), 2));
            $g = hexdec(str_repeat(substr($hex, 1, 1), 2));
            $b = hexdec(str_repeat(substr($hex, 2, 1), 2));
        } else {
            $r = hexdec(substr($hex, 0, 2));
            $g = hexdec(substr($hex, 2, 2));
            $b = hexdec(substr($hex, 4, 2));
        }

        return ['r' => $r, 'g' => $g, 'b' => $b];
    }

    /**
     * Obtient l'extension de fichier à partir du type MIME
     */
    private function getExtensionFromMime($mime)
    {
        $extensions = [
            'image/jpeg' => '.jpg',
            'image/png' => '.png',
            'image/gif' => '.gif'
        ];

        return $extensions[$mime] ?? '.jpg';
    }

    /**
     * Retourne un message d'erreur lisible pour les erreurs d'upload
     */
    private function getUploadErrorMessage($errorCode)
    {
        $errors = [
            UPLOAD_ERR_INI_SIZE => 'Le fichier dépasse la taille maximale autorisée par le serveur.',
            UPLOAD_ERR_FORM_SIZE => 'Le fichier dépasse la taille maximale spécifiée dans le formulaire.',
            UPLOAD_ERR_PARTIAL => 'Le fichier n\'a été que partiellement téléchargé.',
            UPLOAD_ERR_NO_FILE => 'Aucun fichier n\'a été téléchargé.',
            UPLOAD_ERR_NO_TMP_DIR => 'Dossier temporaire manquant.',
            UPLOAD_ERR_CANT_WRITE => 'Échec de l\'écriture du fichier sur le disque.',
            UPLOAD_ERR_EXTENSION => 'Une extension PHP a arrêté le téléchargement du fichier.'
        ];

        return $errors[$errorCode] ?? 'Erreur inconnue lors du téléchargement du fichier.';
    }
}
