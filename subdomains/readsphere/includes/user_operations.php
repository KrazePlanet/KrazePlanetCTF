<?php
/**
 * Opérations liées aux utilisateurs
 * 
 * Ce fichier contient les fonctions pour gérer les opérations CRUD sur les utilisateurs.
 */

// Inclure les dépendances
require_once __DIR__ . '/config/paths.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/mailer.php';
require_once __DIR__ . '/error_handler.php';

// Inclure la configuration des images
$imageConfig = require __DIR__ . '/config/images.php';

// Vérifier et créer les dossiers nécessaires
$requiredDirs = [
    AVATARS_PATH,
    THUMBNAILS_PATH,
    IMAGE_CACHE_PATH
];

foreach ($requiredDirs as $dir) {
    if (!file_exists($dir)) {
        mkdir($dir, 0755, true);
    }
}

/**
 * Récupère le nombre total d'utilisateurs
 * 
 * @return int Nombre total d'utilisateurs
 */
function get_total_users_count() {
    global $pdo;
    
    try {
        $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE deleted_at IS NULL");
        return (int)$stmt->fetchColumn();
    } catch (PDOException $e) {
        error_log("Erreur lors du comptage des utilisateurs: " . $e->getMessage());
        return 0;
    }
}

/**
 * Récupère le nombre de nouveaux utilisateurs pour une période donnée
 * 
 * @param string $period Période ('today', 'week', 'month' ou 'all')
 * @return int Nombre de nouveaux utilisateurs
 */
function get_new_users_count($period = 'today') {
    global $pdo;
    
    $sql = "SELECT COUNT(*) FROM users WHERE 1=1";
    $params = [];
    
    switch ($period) {
        case 'today':
            $sql .= " AND DATE(created_at) = CURDATE()";
            break;
        case 'week':
            $sql .= " AND YEARWEEK(created_at, 1) = YEARWEEK(CURDATE(), 1)";
            break;
        case 'month':
            $sql .= " AND MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE())";
            break;
        // 'all' ou autre - pas de filtre de date
    }
    
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return (int)$stmt->fetchColumn();
    } catch (PDOException $e) {
        error_log("Erreur lors du comptage des nouveaux utilisateurs: " . $e->getMessage());
        return 0;
    }
}

/**
 * Récupère les activités récentes des utilisateurs
 * 
 * @param int $limit Nombre maximum d'activités à retourner
 * @return array Tableau des activités
 */
function get_recent_users_activities($limit = 5) {
    global $pdo;
    
    $activities = [];
    $sql = "
        SELECT 
            u.id, u.username, u.avatar,
            a.action, a.details, a.created_at,
            a.ip_address
        FROM user_activities a
        JOIN users u ON a.user_id = u.id
        WHERE a.is_admin_activity = 0
        ORDER BY a.created_at DESC
        LIMIT :limit
    ";
    
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->execute();
        
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $activities[] = [
                'user_id' => $row['id'],
                'username' => $row['username'],
                'avatar' => $row['avatar'],
                'action' => $row['action'],
                'details' => $row['details'],
                'created_at' => $row['created_at'],
                'ip_address' => $row['ip_address']
            ];
        }
    } catch (PDOException $e) {
        error_log("Erreur lors de la récupération des activités récentes: " . $e->getMessage());
    }
    
    return $activities;
}

// Inclure la classe AvatarManager
require_once __DIR__ . '/utils/AvatarManager.php';

// Constantes pour les durées des cookies (en secondes)
define('REMEMBER_ME_EXPIRY', 30 * 24 * 60 * 60); // 30 jours
define('SESSION_EXPIRY', 24 * 60 * 60); // 24 heures

/**
 * Vérifie si un email existe déjà
 * 
 * @param string $email Email à vérifier
 * @param int $exclude_user_id ID de l'utilisateur à exclure de la vérification
 * @return bool True si l'email existe déjà, false sinon
 */
function email_exists($email, $exclude_user_id = null)
{
    global $pdo;

    $sql = "SELECT COUNT(*) FROM users WHERE email = ?";
    $params = [strtolower(trim($email))];

    if ($exclude_user_id !== null) {
        $sql .= " AND id != ?";
        $params[] = $exclude_user_id;
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    return $stmt->fetchColumn() > 0;
}

/**
 * Vérifie si un nom d'utilisateur existe déjà
 * 
 * @param string $username Nom d'utilisateur à vérifier
 * @param int $exclude_user_id ID de l'utilisateur à exclure de la vérification
 * @return bool True si le nom d'utilisateur existe déjà, false sinon
 */
function username_exists($username, $exclude_user_id = null)
{
    global $pdo;

    $sql = "SELECT COUNT(*) FROM users WHERE username = ?";
    $params = [$username];

    if ($exclude_user_id !== null) {
        $sql .= " AND id != ?";
        $params[] = $exclude_user_id;
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    return $stmt->fetchColumn() > 0;
}

/**
 * Récupère un utilisateur par son ID
 * 
 * @param int $user_id ID de l'utilisateur
 * @return array|false Tableau des données de l'utilisateur ou false si non trouvé
 */
function get_user_by_id($user_id)
{
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("SELECT id, username, email, role, is_active, email_verified_at, 
                              created_at, updated_at, last_login_at, avatar 
                              FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            // Ne pas renvoyer le mot de passe
            unset($user['password']);
            
            // Formater les dates si elles existent
            if (!empty($user['created_at'])) {
                $user['created_at_formatted'] = date('d/m/Y H:i', strtotime($user['created_at']));
            }
            
            if (!empty($user['updated_at'])) {
                $user['updated_at_formatted'] = date('d/m/Y H:i', strtotime($user['updated_at']));
            }
            
            if (!empty($user['last_login_at'])) {
                $user['last_login_at_formatted'] = date('d/m/Y H:i', strtotime($user['last_login_at']));
            }
            
            // Ajouter l'URL complète de l'avatar
            if (!empty($user['avatar'])) {
                $avatarManager = new AvatarManager();
                $user['avatar_url'] = $avatarManager->getAvatarUrl($user['avatar']);
            } else {
                $user['avatar_url'] = '/uploads/avatars/default.png';
            }
        }
        
        return $user;
        
    } catch (PDOException $e) {
        error_log('Erreur lors de la récupération de l\'utilisateur : ' . $e->getMessage());
        return false;
    }
}

/**
 * Récupère un utilisateur par son email
 * 
 * @param string $email Email de l'utilisateur
 * @param bool $with_password Inclure le mot de passe dans le résultat
 * @return array|false Tableau des données de l'utilisateur ou false si non trouvé
 */
function get_user_by_email($email, $with_password = false)
{
    global $pdo;
    
    $select = "id, username, email, role, is_active, email_verified_at, created_at, updated_at, last_login_at, avatar";
    
    if ($with_password) {
        $select .= ", password";
    }
    
    try {
        $stmt = $pdo->prepare("SELECT $select FROM users WHERE email = ?");
        $stmt->execute([strtolower(trim($email))]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log('Erreur lors de la récupération de l\'utilisateur par email : ' . $e->getMessage());
        return false;
    }
}

/**
 * Récupère le nom d'utilisateur à partir de l'ID
 * 
 * @param int $user_id ID de l'utilisateur
 * @return string Nom d'utilisateur ou chaîne vide si non trouvé
 */
function get_username($user_id)
{
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("SELECT username FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ? $result['username'] : '';
    } catch (PDOException $e) {
        error_log('Erreur lors de la récupération du nom d\'utilisateur : ' . $e->getMessage());
        return '';
    }
}

/**
 * Crée un nouvel utilisateur
 * 
 * @param array $data Données de l'utilisateur
 * @return int|false ID de l'utilisateur créé ou false en cas d'échec
 */
function create_user($data)
{
    global $pdo;

    // Valider les données
    $errors = [];
    $required_fields = ['username', 'email', 'password'];

    foreach ($required_fields as $field) {
        if (empty($data[$field])) {
            $errors[$field] = 'Ce champ est obligatoire';
        }
    }

    // Validation du nom d'utilisateur
    if (!empty($data['username'])) {
        if (strlen($data['username']) < 3 || strlen($data['username']) > 30) {
            $errors['username'] = 'Le nom d\'utilisateur doit contenir entre 3 et 30 caractères';
        } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $data['username'])) {
            $errors['username'] = 'Le nom d\'utilisateur ne peut contenir que des lettres, des chiffres et des tirets bas';
        } elseif (username_exists($data['username'])) {
            $errors['username'] = 'Ce nom d\'utilisateur est déjà pris';
        }
    }

    // Validation de l'email
    if (!empty($data['email'])) {
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Adresse email invalide';
        } elseif (email_exists($data['email'])) {
            $errors['email'] = 'Cette adresse email est déjà utilisée';
        }
    }

    // Validation du mot de passe
    if (!empty($data['password'])) {
        if (strlen($data['password']) < 8) {
            $errors['password'] = 'Le mot de passe doit contenir au moins 8 caractères';
        } elseif (isset($data['password_confirm']) && $data['password'] !== $data['password_confirm']) {
            $errors['password_confirm'] = 'Les mots de passe ne correspondent pas';
        }
    }

    if (!empty($errors)) {
        $_SESSION['form_errors'] = $errors;
        $_SESSION['form_data'] = $data;
        return false;
    }

    try {
        $pdo->beginTransaction();

        // 1. D'abord insérer l'utilisateur
        $stmt = $pdo->prepare("
            INSERT INTO users (
                username, 
                email, 
                password,
                created_at,
                updated_at
            ) VALUES (
                :username, 
                :email, 
                :password,
                NOW(),
                NOW()
            )
        ");

        $result = $stmt->execute([
            ':username' => $data['username'],
            ':email' => strtolower(trim($data['email'])),
            ':password' => password_hash($data['password'], PASSWORD_DEFAULT)
        ]);

        $user_id = $pdo->lastInsertId();

        // 2. Ensuite, insérer le jeton dans la table user_tokens
        $email_verification_token = bin2hex(random_bytes(32));
        $email_verification_expires = date('Y-m-d H:i:s', strtotime('+24 hours'));

        $stmt = $pdo->prepare("
            INSERT INTO user_tokens (
                user_id,
                token,
                type,
                expires_at,
                created_at
            ) VALUES (
                :user_id,
                :token,
                'email_verification',
                :expires_at,
                NOW()
            )
        ");

        $token_result = $stmt->execute([
            ':user_id' => $user_id,
            ':token' => $email_verification_token,
            ':expires_at' => $email_verification_expires
        ]);

        // Gérer l'avatar
        $avatar = null;
        if (!empty($data['avatar_tmp']) && is_uploaded_file($data['avatar_tmp']['tmp_name'])) {
            $avatarManager = new AvatarManager();
            $result = $avatarManager->uploadAvatar($data['avatar_tmp'], $user_id, $data['username']);
            
            if ($result['success']) {
                $avatar = $result['filename'];
            } else {
                // En cas d'échec, générer un avatar par défaut
                $result = $avatarManager->generateDefaultAvatar($data['username'], $user_id);
                if ($result['success']) {
                    $avatar = $result['filename'];
                }
            }
        } else {
            // Générer un avatar par défaut si aucun n'est fourni
            $avatarManager = new AvatarManager();
            $result = $avatarManager->generateDefaultAvatar($data['username'], $user_id);
            if ($result['success']) {
                $avatar = $result['filename'];
            }
        }

        if ($result && $token_result) {
            $pdo->commit();
            
            // Envoyer l'email de vérification
            $verification_url = url("verify-email.php?token=" . urlencode($email_verification_token));

            $subject = 'Veuillez verifier votre adresse email';
            $body = "
                <h1>Bienvenue sur " . ($_ENV['APP_NAME'] ?? 'ReadSphere') . " !</h1>
                <p>Merci de vous être inscrit. Pour activer votre compte, veuillez cliquer sur le lien ci-dessous :</p>
                <p><a href=\"$verification_url\" style=\"display: inline-block; padding: 10px 20px; background-color: #4CAF50; color: white; text-decoration: none; border-radius: 5px;\">
                    Vérifier mon email
                </a></p>
                <p>Ou copiez-collez cette URL dans votre navigateur :</p>
                <p><code>$verification_url</code></p>
                <p>Ce lien expirera dans 24 heures.</p>
            ";

            $alt_body = "Bienvenue sur " . ($_ENV['APP_NAME'] ?? 'ReadSphere') . " !\n\n" .
                "Merci de vous être inscrit. Pour activer votre compte, veuillez cliquer sur le lien ci-dessous :\n\n" .
                "$verification_url\n\n" .
                "Ce lien expirera dans 24 heures.\n\n" .
                "Si vous n'avez pas créé de compte, vous pouvez ignorer cet email en toute sécurité.";

            send_email($data['email'], $subject, $body, $alt_body);

            // Journaliser l'action
            log_message("Nouvel utilisateur enregistré (ID: $user_id, Email: " . $data['email'] . ")", 'info');

            // Mettre à jour l'utilisateur avec l'avatar
            $stmt = $pdo->prepare("UPDATE users SET avatar = ? WHERE id = ?");
            $stmt->execute([$avatar, $user_id]);

            return $user_id;
        }

        $pdo->rollBack();
        return false;

    } catch (PDOException $e) {
        $pdo->rollBack();
        error_log('Erreur lors de la création de l\'utilisateur : ' . $e->getMessage());
        log_message("Échec de la création d'un utilisateur avec l'email : " . ($data['email'] ?? 'inconnu') . " - Erreur: " . $e->getMessage(), 'error');
        
        $_SESSION['form_errors'] = ['general' => 'Une erreur est survenue lors de la création du compte. Veuillez réessayer.'];
        $_SESSION['form_data'] = $data;
        return false;
    }
}

/**
 * Authentifie un utilisateur
 * 
 * @param string $email Email de l'utilisateur
 * @param string $password Mot de passe
 * @param bool $remember Se souvenir de l'utilisateur
 * @return bool True en cas de succès, false sinon
 */
function authenticate_user($email, $password, $remember = false)
{
    global $pdo;

    // Valider les entrées
    if (empty($email) || empty($password)) {
        $_SESSION['form_errors'] = [
            'email' => empty($email) ? 'L\'email est requis' : '',
            'password' => empty($password) ? 'Le mot de passe est requis' : ''
        ];
        return false;
    }

    // Vérifier si le compte est bloqué
    if ($blocked = is_login_blocked($email)) {
        $remaining = strtotime($blocked['lock_until']) - time();
        $minutes = ceil($remaining / 60);
        $_SESSION['form_errors'] = ['general' => "Trop de tentatives. Veuillez réessayer dans $minutes minutes."];
        return false;
    }

    try {
        // Récupérer l'utilisateur par email
        $user = get_user_by_email($email, true);

        // Vérifier si l'utilisateur existe et que le mot de passe est correct
        if (!$user || !password_verify($password, $user['password'])) {
            // Incrémenter le compteur de tentatives de connexion échouées
            if ($user) {
                increment_login_attempts($email);
            }
            
            $_SESSION['form_errors'] = ['general' => 'Email ou mot de passe incorrect'];
            return false;
        }

        // Vérifier si le compte est actif
        if (empty($user['is_active'])) {
            $_SESSION['form_errors'] = ['general' => 'Votre compte est désactivé. Veuillez vérifier votre email pour l\'activer.'];
            return false;
        }

        // Vérifier si l'email est vérifié
        if (empty($user['email_verified_at'])) {
            $_SESSION['form_errors'] = ['general' => 'Veuillez vesrifier votre adresse email avant de vous connecter.'];
            return false;
        }

        // Réinitialiser les tentatives de connexion
        reset_login_attempts($user['id']);

        // Mettre à jour la dernière connexion
        update_last_login($user['id'], $_SERVER['REMOTE_ADDR']);

        // Créer la session utilisateur
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $_SESSION['last_activity'] = time();

        // Gestion du "Se souvenir de moi"
        if ($remember) {
            $token = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', strtotime('+30 days'));

            $stmt = $pdo->prepare("
                INSERT INTO user_tokens (user_id, token, type, expires_at)
                VALUES (?, ?, 'remember_me', ?)
            ");
            $stmt->execute([$user['id'], $token, $expires]);

            setcookie(
                'remember_me',
                $token,
                [
                    'expires' => strtotime('+30 days'),
                    'path' => '/',
                    'domain' => '',
                    'secure' => isset($_SERVER['HTTPS']),
                    'httponly' => true,
                    'samesite' => 'Lax'
                ]
            );
        }

        return true;

    } catch (PDOException $e) {
        error_log('Erreur lors de l\'authentification : ' . $e->getMessage());
        $_SESSION['form_errors'] = ['general' => 'Une erreur est survenue lors de la connexion.'];
        return false;
    }
}

/**
 * Crée une session utilisateur
 * 
 * @param array $user Données de l'utilisateur
 * @param bool $remember Se souvenir de l'utilisateur
 * @return void
 */
function create_user_session($user, $remember = false)
{
    // Régénérer l'ID de session pour prévenir les attaques de fixation de session
    session_regenerate_id(true);

    // Stocker les informations essentielles de l'utilisateur en session
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['role'] = $user['role'] ?? 'user'; // Rôle par défaut à 'user' si non défini
    $_SESSION['last_activity'] = time();
    
    // Stocker toutes les données utilisateur dans user_data pour éviter des requêtes supplémentaires
    $_SESSION['user_data'] = [
        'id' => $user['id'],
        'username' => $user['username'],
        'email' => $user['email'] ?? '',
        'role' => $user['role'] ?? 'user',
        'is_active' => $user['is_active'] ?? 1,
        'email_verified_at' => $user['email_verified_at'] ?? null,
        'avatar' => $user['avatar'] ?? null
    ];

    // Définir le cookie de session
    set_session_cookie();

    // Gérer l'option "Se souvenir de moi"
    if ($remember) {
        create_remember_me_token($user['id']);
    }
}

/**
 * Définit le cookie de session
 * 
 * @return void
 */
function set_session_cookie()
{
    $params = session_get_cookie_params();

    setcookie(
        session_name(),
        session_id(),
        [
            'expires' => time() + SESSION_EXPIRY,
            'path' => $params['path'],
            'domain' => $params['domain'],
            'secure' => $params['secure'],
            'httponly' => true,
            'samesite' => 'Lax'
        ]
    );
}

/**
 * Crée un jeton "Se souvenir de moi"
 * 
 * @param int $user_id ID de l'utilisateur
 * @return bool True en cas de succès, false sinon
 */
function create_remember_me_token($user_id)
{
    global $pdo;

    try {
        // Générer un jeton unique
        $token = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', time() + REMEMBER_ME_EXPIRY);

        // Supprimer les anciens jetons
        $pdo->prepare("DELETE FROM user_tokens WHERE user_id = ? AND type = 'remember_me'")->execute([$user_id]);

        // Insérer le nouveau jeton
        $stmt = $pdo->prepare("
            INSERT INTO user_tokens (user_id, token, type, expires_at, created_at, user_agent, ip_address)
            VALUES (?, ?, 'remember_me', ?, NOW(), ?, ?)
        ");

        $result = $stmt->execute([
            $user_id,
            password_hash($token, PASSWORD_DEFAULT),
            $expires,
            $_SERVER['HTTP_USER_AGENT'] ?? '',
            $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0'
        ]);

        if ($result) {
            // Définir le cookie "Se souvenir de moi"
            $cookie_value = $user_id . ':' . $token;
            $expires = time() + REMEMBER_ME_EXPIRY;

            setcookie(
                'remember_me',
                $cookie_value,
                [
                    'expires' => $expires,
                    'path' => '/',
                    'domain' => '',
                    'secure' => isset($_SERVER['HTTPS']),
                    'httponly' => true,
                    'samesite' => 'Lax'
                ]
            );

            return true;
        }

        return false;

    } catch (PDOException $e) {
        error_log('Erreur lors de la création du jeton "Se souvenir de moi" : ' . $e->getMessage());
        return false;
    }
}

/**
 * Vérifie le jeton "Se souvenir de moi"
 * 
 * @return bool True si le jeton est valide et que l'utilisateur a été connecté, false sinon
 */
/**
 * Vérifie le jeton "Se souvenir de moi"
 * 
 * @return bool True si le jeton est valide et que l'utilisateur a été connecté, false sinon
 */
function check_remember_me_token()
{
    // Vérifier si le cookie existe
    if (empty($_COOKIE['remember_me'])) {
        return false;
    }

    // Extraire l'ID utilisateur et le token du cookie
    list($user_id, $token) = explode(':', $_COOKIE['remember_me'] . ':');

    // Vérifier que l'ID utilisateur et le token sont valides
    if (empty($user_id) || empty($token)) {
        clear_remember_me_cookie();
        return false;
    }

    global $pdo;

    try {
        // Récupérer le jeton de la base de données avec les informations utilisateur
        $stmt = $pdo->prepare("
            SELECT ut.*, u.*
            FROM user_tokens ut
            JOIN users u ON ut.user_id = u.id
            WHERE ut.user_id = ? 
            AND ut.token = ?
            AND ut.type = 'remember_me'
            AND ut.expires_at > NOW()
            AND u.is_active = 1
        ");
        $stmt->execute([$user_id, $token]);
        $token_data = $stmt->fetch(PDO::FETCH_ASSOC);

        // Si le jeton est valide
        if ($token_data) {
            // Vérifier que l'email est vérifié
            if (empty($token_data['email_verified_at'])) {
                clear_remember_me_cookie();
                return false;
            }

            // Préparer les données utilisateur pour la session
            $user = [
                'id' => $token_data['user_id'],
                'username' => $token_data['username'],
                'email' => $token_data['email'],
                'role' => $token_data['role'] ?? 'user', // Rôle par défaut si non défini
                'is_active' => $token_data['is_active'],
                'email_verified_at' => $token_data['email_verified_at'],
                'avatar' => $token_data['avatar'] ?? null,
                'created_at' => $token_data['created_at'] ?? null,
                'updated_at' => $token_data['updated_at'] ?? null,
                'last_login_at' => $token_data['last_login_at'] ?? null
            ];

            // Connecter l'utilisateur
            create_user_session($user, true);
            
            // Mettre à jour la dernière connexion
            update_last_login($user['id'], $_SERVER['REMOTE_ADDR']);
            
            // Journaliser la connexion automatique
            log_message("Connexion automatique réussie pour l'utilisateur ID: $user_id", 'info');
            
            return true;
        }
    } catch (PDOException $e) {
        error_log('Erreur lors de la vérification du jeton "Se souvenir de moi" : ' . $e->getMessage());
    }

    // Supprimer le cookie si le jeton est invalide ou en cas d'erreur
    clear_remember_me_cookie();
    return false;
}

/**
 * Supprime le cookie "Se souvenir de moi"
 * 
 * @return bool True si le cookie a été supprimé, false sinon
 */
function clear_remember_me_cookie()
{
    if (headers_sent($file, $line)) {
        error_log("Impossible de supprimer le cookie 'remember_me' - Les en-têtes ont déjà été envoyés dans $file à la ligne $line");
        return false;
    }
    
    return setcookie('remember_me', '', [
        'expires' => time() - 3600,
        'path' => '/',
        'domain' => $_SERVER['HTTP_HOST'] ?? '',
        'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
} 

/**
 * Déconnecte l'utilisateur actuel
 * 
 * @return bool True si la déconnexion a réussi, false sinon
 */
function logout_user() {
    // Vérifier si les en-têtes ont déjà été envoyés
    if (headers_sent($file, $line)) {
        error_log("Impossible de déconnecter l'utilisateur - Les en-têtes ont déjà été envoyés dans $file à la ligne $line");
        return false;
    }
    
    // Vider le buffer de sortie pour éviter les problèmes d'en-têtes
    while (ob_get_level() > 0) {
        if (!@ob_end_clean()) {
            error_log('Erreur lors du nettoyage du buffer de sortie');
            return false;
        }
    }
    
    // Détruire le jeton "Se souvenir de moi" s'il existe
    $rememberMeDeleted = true;
    if (isset($_COOKIE['remember_me'])) {
        $token = $_COOKIE['remember_me'];
        
        try {
            global $pdo;
            $pdo->prepare("DELETE FROM user_tokens WHERE token = ?")->execute([$token]);
        } catch (PDOException $e) {
            error_log('Erreur lors de la suppression du jeton de rappel : ' . $e->getMessage());
            $rememberMeDeleted = false;
        }
        
        // Supprimer le cookie
        if (!clear_remember_me_cookie()) {
            $rememberMeDeleted = false;
        }
    }
    
    // Gestion de la session
    $sessionDestroyed = true;
    if (session_status() === PHP_SESSION_ACTIVE) {
        // Supprimer toutes les variables de session
        $_SESSION = [];
        
        // Supprimer le cookie de session
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(), 
                '', 
                [
                    'expires' => time() - 42000,
                    'path' => $params["path"],
                    'domain' => $params["domain"],
                    'secure' => $params["secure"],
                    'httponly' => $params["httponly"],
                    'samesite' => $params["samesite"] ?? 'Lax'
                ]
            );
        }
        
        // Détruire la session
        if (!session_destroy()) {
            error_log('Erreur lors de la destruction de la session');
            $sessionDestroyed = false;
        }
    }
    
    return $rememberMeDeleted && $sessionDestroyed;
}

/**
 * Vérifie si un utilisateur est connecté
 * 
 * @return bool True si connecté, false sinon
 */
function is_logged_in()
{
    // Vérifier la session
    if (!empty($_SESSION['user_id']) && !empty($_SESSION['last_activity'])) {
        // Vérifier si la session a expiré
        if (time() - $_SESSION['last_activity'] > SESSION_EXPIRY) {
            logout_user();
            return false;
        }

        // Vérifier que l'utilisateur existe toujours
        $user = get_user_by_id($_SESSION['user_id']);
        if (!$user) {
            logout_user();
            return false;
        }

        // Mettre à jour le timestamp de dernière activité
        $_SESSION['last_activity'] = time();
        return true;
    }

    // Vérifier le jeton "Se souvenir de moi"
    return check_remember_me_token();
}

/**
 * Incrémente le compteur de tentatives de connexion échouées
 * 
 * @param int $user_id ID de l'utilisateur
 * @return void
 */
function increment_login_attempts($email) {
    global $pdo;
    try {
        $pdo->prepare("
            INSERT INTO login_attempts 
            (email, ip_address, user_agent, attempts, last_attempt, is_locked, lock_until) 
            VALUES (?, ?, ?, 1, NOW(), 0, NULL)
            ON DUPLICATE KEY UPDATE 
            attempts = attempts + 1, 
            last_attempt = NOW(),
            is_locked = IF(attempts >= 5, 1, 0),
            lock_until = IF(attempts >= 5, DATE_ADD(NOW(), INTERVAL 15 MINUTE), NULL)
        ")->execute([$email, $_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_USER_AGENT'] ?? '']);
    } catch (PDOException $e) {
        error_log('Erreur lors de l\'incrémentation des tentatives de connexion : ' . $e->getMessage());
    }
}

/**
 * Réinitialise le compteur de tentatives de connexion échouées
 * 
 * @param int $user_id ID de l'utilisateur
 * @return void
 */
function reset_login_attempts($user_id) {
    global $pdo;
    try {
        $pdo->prepare("
            DELETE FROM login_attempts 
            WHERE email = (SELECT email FROM users WHERE id = ?)
        ")->execute([$user_id]);
    } catch (PDOException $e) {
        error_log('Erreur lors de la réinitialisation des tentatives de connexion : ' . $e->getMessage());
    }
}

/**
 * Met à jour la date de dernière connexion et l'adresse IP
 * 
 * @param int $user_id ID de l'utilisateur
 * @param string $ip_address Adresse IP de l'utilisateur
 * @return void
 */
function update_last_login($user_id, $ip_address = null)
{
    global $pdo;

    try {
        $stmt = $pdo->prepare("UPDATE users SET last_login_at = NOW(), last_login_ip = ? WHERE id = ?");
        $stmt->execute([$ip_address, $user_id]);
    } catch (PDOException $e) {
        error_log('Erreur lors de la mise à jour de la dernière connexion : ' . $e->getMessage());
    }
}

/**
 * Vérifie si une connexion est bloquée
 * 
 * @param string $email Email de l'utilisateur
 * @return array|false Données de blocage ou false si non bloqué
 */
function is_login_blocked($email) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("
            SELECT lock_until 
            FROM login_attempts 
            WHERE email = ? AND is_locked = 1 AND lock_until > NOW()
            LIMIT 1
        ");
        $stmt->execute([$email]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log('Erreur lors de la vérification du blocage de connexion : ' . $e->getMessage());
        return false;
    }
}
