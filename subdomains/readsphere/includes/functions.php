<?php

// La fonction log_message a été déplacée dans logger.php
if (!function_exists('log_message')) {
    // Inclure le logger si nécessaire
    require_once __DIR__ . '/logger.php';
}

/**
 * Formate une date au format lisible en français
 * 
 * @param string|DateTime $date_string La date à formater (format MySQL, timestamp ou objet DateTime)
 * @param string|bool $format Le format de sortie (par défaut: 'd/m/Y à H:i')
 * @return string La date formatée
 */
function format_date($date_string, $format = 'd/m/Y à H:i') {
    if (empty($date_string)) {
        return 'Date inconnue';
    }
    
    try {
        // Si c'est déjà un objet DateTime
        if ($date_string instanceof DateTime) {
            $date = $date_string;
        } 
        // Si c'est un timestamp numérique
        elseif (is_numeric($date_string)) {
            $date = new DateTime('@' . $date_string);
            $date->setTimezone(new DateTimeZone(date_default_timezone_get()));
        }
        // Si c'est une chaîne de date
        else {
            $date = new DateTime($date_string);
        }
        
        // Si le deuxième paramètre est true, utiliser le format par défaut
        if ($format === true) {
            $format = 'd/m/Y à H:i';
        }
        
        return $date->format($format);
    } catch (Exception $e) {
        error_log('Erreur de formatage de date: ' . $e->getMessage());
        return 'Date invalide';
    }
}

/**
 * Vérifie que le jeton CSRF est présent et valide dans la requête POST
 * Arrête l'exécution avec une erreur 403 si le jeton est invalide
 */
function require_csrf_token() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $token = $_POST['_token'] ?? '';
        if (!verify_csrf_token($token)) {
            http_response_code(403);
            die('Erreur de sécurité : jeton CSRF invalide.');
        }
    }
}

/**
 * Génère un jeton CSRF s'il n'existe pas déjà
 */
function generate_csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Vérifie si le jeton CSRF est valide
 * 
 * @param string $token Le jeton à vérifier
 * @return bool True si le jeton est valide, false sinon
 */
function verify_csrf_token($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Génère un champ caché contenant le jeton CSRF
 * 
 * @return string Le HTML du champ caché
 */
function csrf_field() {
    $token = generate_csrf_token();
    return '<input type="hidden" name="_token" value="' . htmlspecialchars($token) . '">';
}


/**
 * Récupère tous les utilisateurs ayant un rôle spécifique
 * 
 * @param string $role Le rôle des utilisateurs à récupérer
 * @param bool $only_active Ne retourner que les utilisateurs actifs (par défaut: true)
 * @param bool $only_verified Ne retourner que les utilisateurs avec email vérifié (par défaut: true)
 * @return array Tableau des utilisateurs avec le rôle spécifié
 */
function get_users_by_role($role, $only_active = true, $only_verified = true) {
    global $pdo;
    
    $sql = "SELECT id, username, email, role, created_at, last_login_at 
            FROM users 
            WHERE role = ?";
    
    $params = [$role];
    
    if ($only_active) {
        $sql .= " AND is_active = 1";
    }
    
    if ($only_verified) {
        $sql .= " AND email_verified_at IS NOT NULL";
    }
    
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log('Erreur lors de la récupération des utilisateurs par rôle : ' . $e->getMessage());
        return [];
    }
}


// Définir un message flash
function set_flash($message, $type = 'success') {
    $_SESSION['flash'] = [
        'message' => $message,
        'type' => $type
    ];
}

// Télécharger une image et retourner le nom du fichier
function upload_image($file, $target_dir = 'uploads/') {
    $target_dir = __DIR__ . '/../' . $target_dir;
    
    // Vérifier si le dossier de destination existe, sinon le créer
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }
    
    $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $new_filename = uniqid() . '.' . $file_extension;
    $target_file = $target_dir . $new_filename;
    
    // Vérifier le type de fichier
    $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
    if (!in_array($file_extension, $allowed_types)) {
        throw new Exception("Seuls les fichiers JPG, JPEG, PNG & GIF sont autorisés.");
    }
    
    // Vérifier la taille du fichier (max 5MB)
    if ($file['size'] > 5 * 1024 * 1024) {
        throw new Exception("Le fichier est trop volumineux. Taille maximale autorisée : 5MB.");
    }
    
    // Déplacer le fichier téléchargé
    if (!move_uploaded_file($file['tmp_name'], $target_file)) {
        throw new Exception("Une erreur est survenue lors du téléchargement du fichier.");
    }
    
    return $new_filename;
}


/**
 * Récupère un livre par son ID
 * 
 * @param int $id ID du livre à récupérer
 * @param bool $include_deleted Inclure les livres marqués comme supprimés (false par défaut)
 * @return array|false Tableau des données du livre ou false si non trouvé
 */
function get_book($id, $include_deleted = false) {
    global $pdo;
    
    $sql = "
        SELECT b.*, u.username, u.avatar as author_avatar,
               (SELECT COUNT(*) FROM comments c WHERE c.book_id = b.id AND c.is_deleted = 0) as comment_count,
               (SELECT COUNT(*) FROM book_likes bl WHERE bl.book_id = b.id) as like_count
        FROM books b 
        JOIN users u ON b.user_id = u.id 
        WHERE b.id = ?
    ";
    
    if (!$include_deleted) {
        $sql .= " AND b.is_deleted = 0";
    }
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}


/**
 * Add a notification for a user
 *
 * @param int $user_id ID of the user to notify
 * @param string $type Type of notification (e.g., 'comment_reported')
 * @param string $message Notification message
 * @param string $link Optional link related to the notification
 * @return bool True on success, false on failure
 */
function add_notification($user_id, $type, $message, $link = '') {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("INSERT INTO notifications (user_id, type, message, link, is_read, created_at) VALUES (?, ?, ?, ?, 0, NOW())");
        return $stmt->execute([$user_id, $type, $message, $link]);
    } catch (PDOException $e) {
        error_log('Error adding notification: ' . $e->getMessage());
        return false;
    }
}

/**
 * Formate une date en temps écoulé (ex: "il y a 2 heures")
 * 
 * @param string $datetime Date au format datetime
 * @param bool $full Afficher toutes les unités de temps
 * @return string Date formatée
 */
function time_elapsed_string($datetime, $full = false) {
    $now = new DateTime;
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);
    
    // Calculer les semaines à partir des jours
    $weeks = floor($diff->d / 7);
    $days = $diff->d % 7;
    
    // Tableau des unités de temps
    $units = [
        'y' => ['singular' => 'an', 'plural' => 'ans'],
        'm' => ['singular' => 'mois', 'plural' => 'mois'],
        'w' => ['singular' => 'semaine', 'plural' => 'semaines'],
        'd' => ['singular' => 'jour', 'plural' => 'jours'],
        'h' => ['singular' => 'heure', 'plural' => 'heures'],
        'i' => ['singular' => 'minute', 'plural' => 'minutes'],
        's' => ['singular' => 'seconde', 'plural' => 'secondes']
    ];
    
    // Préparer les valeurs pour chaque unité
    $values = [
        'y' => $diff->y,
        'm' => $diff->m,
        'w' => $weeks,
        'd' => $days,
        'h' => $diff->h,
        'i' => $diff->i,
        's' => $diff->s
    ];
    
    // Construire le tableau des parties du texte
    $parts = [];
    foreach ($values as $key => $value) {
        if ($value > 0) {
            $label = $value > 1 ? $units[$key]['plural'] : $units[$key]['singular'];
            $parts[] = $value . ' ' . $label;
            
            // Si on ne veut que la première unité
            if (!$full) {
                break;
            }
        }
    }
    
    return $parts ? 'Il y a ' . implode(', ', $parts) : 'À l\'instant';
}


/**
 * Génère une URL complète à partir d'un chemin relatif
 * 
 * @param string $path Chemin relatif
 * @return string URL complète
 */
function url($path = '') {
    static $baseUrl = null;
    if ($baseUrl === null) {
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $baseUrl = $protocol . $host . '/readsphere';
    }
    return rtrim($baseUrl, '/') . '/' . ltrim($path, '/');
}

/**
 * Génère le chemin absolu du système de fichiers
 * 
 * @param string $path Chemin relatif
 * @return string Chemin absolu
 */
function base_path($path = '') {
    return rtrim(ROOT_PATH, '/') . '/' . ltrim($path, '/');
}