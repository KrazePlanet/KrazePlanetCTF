<?php
/**
 * Opérations liées aux commentaires
 * 
 * Ce fichier contient les fonctions pour gérer les opérations CRUD sur les commentaires
 * ainsi que la gestion des signalements de commentaires.
 * 
 * Inclus par init.php
 */

/**
 * Récupère le nombre total de commentaires
 * 
 * @return int Nombre total de commentaires non supprimés
 */
function get_total_comments_count() {
    global $pdo;
    
    try {
        $stmt = $pdo->query("SELECT COUNT(*) FROM comments WHERE is_deleted = 0 AND is_admin_deleted = 0");
        return (int)$stmt->fetchColumn();
    } catch (PDOException $e) {
        error_log("Erreur lors du comptage des commentaires: " . $e->getMessage());
        return 0;
    }
}

/**
 * Récupère le nombre de signalements en attente
 * 
 * @return int Nombre de signalements en attente
 */
function get_pending_reports_count() {
    global $pdo;
    
    try {
        $stmt = $pdo->query("SELECT COUNT(*) FROM comment_reports WHERE status = 'pending'");
        return (int)$stmt->fetchColumn();
    } catch (PDOException $e) {
        error_log("Erreur lors du comptage des signalements en attente: " . $e->getMessage());
        return 0;
    }
}

/**
 * Récupère les activités récentes d'administration
 * 
 * @param int $limit Nombre maximum d'activités à retourner
 * @return array Tableau des activités
 */
function get_recent_admin_activities($limit = 5) {
    global $pdo;
    
    $activities = [];
    $sql = "
        SELECT 
            u.id, u.username, u.avatar,
            a.action, a.details, a.created_at,
            a.ip_address
        FROM user_activities a
        JOIN users u ON a.user_id = u.id
        WHERE a.is_admin_activity = 1
        ORDER BY a.created_at DESC
        LIMIT :limit
    ";
    
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->execute();
        
        $icons = [
            'login' => 'sign-in-alt',
            'logout' => 'sign-out-alt',
            'create' => 'plus-circle',
            'update' => 'edit',
            'delete' => 'trash-alt',
            'report' => 'flag',
            'ban' => 'ban',
            'warn' => 'exclamation-triangle',
            'approve' => 'check-circle',
            'reject' => 'times-circle'
        ];
        
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $icon = 'info-circle'; // Icône par défaut
            foreach ($icons as $key => $i) {
                if (stripos($row['action'], $key) !== false) {
                    $icon = $i;
                    break;
                }
            }
            
            $activities[] = [
                'user_id' => $row['id'],
                'username' => $row['username'],
                'avatar' => $row['avatar'],
                'action' => $row['action'],
                'details' => $row['details'],
                'created_at' => $row['created_at'],
                'ip_address' => $row['ip_address'],
                'icon' => $icon
            ];
        }
    } catch (PDOException $e) {
        error_log("Erreur lors de la récupération des activités admin: " . $e->getMessage());
    }
    
    return $activities;
}

/**
 * Ajoute un nouveau commentaire à un livre
 * 
 * @param array $data Données du commentaire
 * @return int|false ID du commentaire ajouté ou false en cas d'échec
 */
function add_comment($data) {
    global $pdo;
    
    // Valider les données
    $errors = [];
    $required_fields = ['book_id', 'user_id', 'content'];
    
    foreach ($required_fields as $field) {
        if (empty($data[$field])) {
            $errors[$field] = 'Ce champ est obligatoire';
        }
    }
    
    // Vérifier la longueur du commentaire
    if (strlen(trim($data['content'] ?? '')) < 5) {
        $errors['content'] = 'Le commentaire doit contenir au moins 5 caractères';
    }
    
    if (!empty($errors)) {
        $_SESSION['form_errors'] = $errors;
        $_SESSION['form_data'] = $data;
        return false;
    }
    
    // Vérifier que le livre existe
    $book = get_book($data['book_id']);
    if (!$book) {
        $errors['book_id'] = 'Livre non trouvé';
        $_SESSION['form_errors'] = $errors;
        return false;
    }
    
    try {
        $pdo->beginTransaction();
        
        $stmt = $pdo->prepare("
            INSERT INTO comments (
                book_id, 
                user_id, 
                content,
                created_at,
                updated_at
            ) VALUES (
                :book_id, 
                :user_id, 
                :content,
                NOW(),
                NOW()
            )
        ");
        
        $result = $stmt->execute([
            ':book_id' => $data['book_id'],
            ':user_id' => $data['user_id'],
            ':content' => $data['content']
        ]);
        
        $comment_id = $pdo->lastInsertId();
        $pdo->commit();
        
        if ($result) {
            // Mettre à jour le compteur de commentaires
            update_comment_count($data['book_id']);
            
            // Journaliser l'action
            log_message("Nouveau commentaire ajouté (ID: $comment_id) par l'utilisateur ID: " . $data['user_id'], 'info');
            
            // Envoyer une notification à l'auteur du livre (sauf si c'est le même utilisateur)
            if ($book['user_id'] != $data['user_id']) {
                $comment_author = get_user_by_id($data['user_id']);
                if ($comment_author && $book_author = get_user_by_id($book['user_id'])) {
                    send_comment_notification(
                        $comment_author,
                        $book_author,
                        $book,
                        $data['content']
                    );
                }
            }
            
            return $comment_id;
        }
        
        return false;
        
    } catch (PDOException $e) {
        $pdo->rollBack();
        
        error_log('Erreur lors de l\'ajout du commentaire : ' . $e->getMessage());
        log_message("Échec de l'ajout d'un commentaire par l'utilisateur ID: " . ($data['user_id'] ?? 'inconnu'), 'error');
        
        $_SESSION['form_errors'] = ['general' => 'Une erreur est survenue lors de l\'ajout du commentaire. Veuillez réessayer.'];
        $_SESSION['form_data'] = $data;
        return false;
    }
}

/**
 * Met à jour un commentaire existant
 * 
 * @param int $comment_id ID du commentaire à mettre à jour
 * @param string $content Nouveau contenu du commentaire
 * @param int $user_id ID de l'utilisateur qui effectue la mise à jour
 * @return bool True en cas de succès, false sinon
 */
function update_comment($comment_id, $content, $user_id) {
    global $pdo;
    
    // Valider les données
    $content = trim($content);
    if (strlen($content) < 5) {
        $_SESSION['form_errors'] = ['content' => 'Le commentaire doit contenir au moins 5 caractères'];
        return false;
    }
    
    try {
        // Vérifier que le commentaire existe et que l'utilisateur est l'auteur ou un administrateur
        $comment = get_comment($comment_id);
        if (!$comment) {
            $_SESSION['error'] = 'Commentaire non trouvé.';
            return false;
        }
        
        $is_admin = is_admin();
        if ($comment['user_id'] != $user_id && !$is_admin) {
            $_SESSION['error'] = 'Vous n\'êtes pas autorisé à modifier ce commentaire.';
            return false;
        }
        
        $pdo->beginTransaction();
        
        $stmt = $pdo->prepare("
            UPDATE comments 
            SET 
                content = :content,
                updated_at = NOW(),
                is_edited = 1
            WHERE id = :id
        ");
        
        $result = $stmt->execute([
            ':content' => $content,
            ':id' => $comment_id
        ]);
        
        $pdo->commit();
        
        if ($result) {
            log_message("Commentaire mis à jour (ID: $comment_id) par l'utilisateur ID: $user_id", 'info');
            return true;
        }
        
        return false;
        
    } catch (PDOException $e) {
        $pdo->rollBack();
        
        error_log('Erreur lors de la mise à jour du commentaire : ' . $e->getMessage());
        log_message("Échec de la mise à jour du commentaire ID: $comment_id par l'utilisateur ID: $user_id", 'error');
        
        $_SESSION['error'] = 'Une erreur est survenue lors de la mise à jour du commentaire. Veuillez réessayer.';
        return false;
    }
}

/**
 * Supprime un commentaire
 * 
 * @param int $comment_id ID du commentaire à supprimer
 * @param int $user_id ID de l'utilisateur qui effectue la suppression
 * @param bool $is_admin_action Si vrai, l'action est effectuée par un administrateur
 * @return bool True en cas de succès, false sinon
 */
function delete_comment($comment_id, $user_id, $is_admin_action = false) {
    global $pdo;
    
    try {
        // Vérifier que le commentaire existe
        $comment = get_comment($comment_id);
        if (!$comment) {
            $_SESSION['error'] = 'Commentaire non trouvé.';
            return false;
        }
        
        $is_admin = is_admin();
        
        // Si c'est une action admin, vérifier que l'utilisateur est bien admin
        if ($is_admin_action && !$is_admin) {
            $_SESSION['error'] = 'Action non autorisée.';
            return false;
        }
        
        // Vérifier que l'utilisateur est l'auteur du commentaire ou un administrateur
        $is_author = $comment['user_id'] == $user_id;
        $is_book_author = false; // À implémenter si nécessaire
        
        if (!$is_author && !$is_admin && !$is_book_author) {
            $_SESSION['error'] = 'Vous n\'êtes pas autorisé à supprimer ce commentaire.';
            return false;
        }
        
        // Démarrer une transaction
        $pdo->beginTransaction();
        
        // Marquer le commentaire comme supprimé (suppression logique)
        $stmt = $pdo->prepare("
            UPDATE comments 
            SET is_deleted = 1, 
                deleted_at = NOW(),
                deleted_by = ?,
                is_admin_deleted = ?
            WHERE id = ?
        ");
        
        $result = $stmt->execute([
            $user_id,
            $is_admin_action ? 1 : 0,
            $comment_id
        ]);
        
        if ($result) {
            // Si c'est une suppression par un administrateur, on peut aussi supprimer les signalements
            if ($is_admin_action) {
                $stmt = $pdo->prepare("DELETE FROM comment_reports WHERE comment_id = ?");
                $stmt->execute([$comment_id]);
            }
            
            // Mettre à jour le compteur de commentaires du livre
            update_comment_count($comment['book_id']);
            
            // Valider la transaction
            $pdo->commit();
            
            $log_message = $is_admin_action 
                ? "Commentaire supprimé par l'administrateur (ID: $comment_id) - Utilisateur ID: $user_id"
                : "Commentaire supprimé (ID: $comment_id) par l'utilisateur ID: $user_id";
                
            log_message($log_message, 'info');
            
            // Ajouter une notification pour l'auteur du commentaire si ce n'est pas lui qui supprime
            if ($comment['user_id'] != $user_id) {
                $admin = get_user_by_id($user_id);
                $message = sprintf(
                    'Votre commentaire sur le livre "%s" a été %s par un administrateur.',
                    htmlspecialchars($comment['book_title'] ?? 'un livre'),
                    $is_admin_action ? 'supprimé' : 'modéré'
                );
                
                add_notification($comment['user_id'], 'comment_deleted', $message, '/book.php?id=' . $comment['book_id']);
                
                // Enregistrer l'action de modération
                $reason = $is_admin_action ? 'Commentaire signalé comme inapproprié' : 'Supprimé par un modérateur';
                log_moderation_action($user_id, $comment_id, 'delete', $reason);
            } else {
                // Enregistrer la suppression par l'auteur lui-même
                $reason = 'Supprimé par l\'auteur';
                log_moderation_action($user_id, $comment_id, 'delete', $reason);
            }
            
            return true;
        }
        
        return false;
        
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        
        error_log('Erreur lors de la suppression du commentaire : ' . $e->getMessage());
        log_message("Échec de la suppression du commentaire ID: $comment_id par l'utilisateur ID: $user_id", 'error');
        
        $_SESSION['error'] = 'Une erreur est survenue lors de la suppression du commentaire. Veuillez réessayer.';
        return false;
    }
}

/**
 * Récupère un commentaire par son ID
 * 
 * @param int $comment_id ID du commentaire
 * @return array|false Tableau des données du commentaire ou false si non trouvé
 */
function get_comment($comment_id) {
    global $pdo;
    
    $stmt = $pdo->prepare("
        SELECT c.*, u.username, u.avatar, u.email
        FROM comments c
        JOIN users u ON c.user_id = u.id
        WHERE c.id = ?
    ");
    
    $stmt->execute([$comment_id]);
    return $stmt->fetch();
}

/**
 * Récupère les commentaires d'un livre avec pagination
 * 
 * @param int $book_id ID du livre
 * @param int $page Numéro de la page (par défaut: 1)
 * @param int $per_page Nombre de commentaires par page (par défaut: 10)
 * @param bool $show_deleted_admin Afficher les commentaires supprimés par un admin (pour les admins uniquement)
 * @return array Tableau contenant les commentaires et les informations de pagination
 */
function get_comments($book_id, $page = 1, $per_page = 10, $show_deleted_admin = false) {
    global $pdo;
    
    $offset = ($page - 1) * $per_page;
    $user_id = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;
    $is_admin = is_admin(); // Vérifie si l'utilisateur est un administrateur
    
    // Construction de la condition WHERE
    $where_conditions = ["c.book_id = ?", "c.is_deleted = 0"];
    $params = [$book_id];
    
    if (!$show_deleted_admin) {
        // Pour les utilisateurs normaux, ne pas afficher les commentaires supprimés par un admin
        $where_conditions[] = "(c.is_admin_deleted = 0 OR c.user_id = ?)";
        $params[] = $user_id;
    }
    
    $where_clause = implode(' AND ', $where_conditions);
    
    // Compter le nombre total de commentaires visibles
    $count_sql = "SELECT COUNT(*) FROM comments c WHERE $where_clause";
    $count_stmt = $pdo->prepare($count_sql);
    $count_stmt->execute($params);
    $total_items = $count_stmt->fetchColumn();
    $total_pages = ceil($total_items / $per_page);
    
    // Récupérer les commentaires de la page courante
    $sql = "
        SELECT 
            c.*, 
            u.username, 
            u.avatar,
            (SELECT COUNT(*) FROM comment_likes cl WHERE cl.comment_id = c.id) as like_count,
            (SELECT COUNT(*) FROM comment_reports cr WHERE cr.comment_id = c.id AND cr.status = 'pending') as report_count,
            EXISTS(SELECT 1 FROM comment_likes cl WHERE cl.comment_id = c.id AND cl.user_id = ?) as is_liked,
            u2.username as deleted_by_username
        FROM comments c
        JOIN users u ON c.user_id = u.id
        LEFT JOIN users u2 ON c.deleted_by = u2.id
        WHERE $where_clause
        ORDER BY c.created_at DESC
        LIMIT ? OFFSET ?
    ";
    
    // Ajouter les paramètres pour la requête principale
    $params = array_merge([$user_id], $params, [$per_page, $offset]);
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $comments = $stmt->fetchAll();
    
    return [
        'comments' => $comments,
        'pagination' => [
            'current_page' => $page,
            'per_page' => $per_page,
            'total_items' => $total_items,
            'total_pages' => $total_pages
        ]
    ];
}

/**
 * Met à jour le compteur de commentaires pour un livre
 * 
 * @param int $book_id ID du livre
 * @return bool True en cas de succès, false sinon
 */
function update_comment_count($book_id) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            UPDATE books 
            SET comment_count = (
                SELECT COUNT(*) 
                FROM comments 
                WHERE book_id = ? 
                AND is_deleted = 0 
                AND is_admin_deleted = 0
            )
            WHERE id = ?
        ");
        
        return $stmt->execute([$book_id, $book_id]);
        
    } catch (PDOException $e) {
        error_log('Erreur lors de la mise à jour du compteur de commentaires : ' . $e->getMessage());
        return false;
    }
}

/**
 * Ajoute ou supprime un "like" sur un commentaire
 * 
 * @param int $comment_id ID du commentaire
 * @param int $user_id ID de l'utilisateur
 * @return array Tableau contenant le statut de l'opération et le nouveau nombre de likes
 */
function toggle_comment_like($comment_id, $user_id) {
    global $pdo;
    
    try {
        $pdo->beginTransaction();
        
        // Vérifier si l'utilisateur a déjà aimé ce commentaire
        $stmt = $pdo->prepare("SELECT id FROM comment_likes WHERE comment_id = ? AND user_id = ?");
        $stmt->execute([$comment_id, $user_id]);
        $like = $stmt->fetch();
        
        if ($like) {
            // Retirer le like
            $stmt = $pdo->prepare("DELETE FROM comment_likes WHERE id = ?");
            $stmt->execute([$like['id']]);
            $liked = false;
        } else {
            // Ajouter un like
            $stmt = $pdo->prepare("INSERT INTO comment_likes (comment_id, user_id, created_at) VALUES (?, ?, NOW())");
            $stmt->execute([$comment_id, $user_id]);
            $liked = true;
            
            // Envoyer une notification à l'auteur du commentaire (sauf si c'est l'utilisateur actuel)
            $comment = get_comment($comment_id);
            $user = get_user_by_id($user_id);
            
            if ($comment && $user && $comment['user_id'] != $user_id) {
                $book = get_book($comment['book_id']);
                if ($book) {
                    $notification_message = sprintf(
                        '%s a aimé votre commentaire sur le livre "%s"',
                        htmlspecialchars($user['username']),
                        htmlspecialchars($book['title'])
                    );
                    
                    // Ajouter une notification en base de données
                    add_notification($comment['user_id'], 'comment_like', $notification_message, '/book/' . $book['id']);
                }
            }
        }
        
        // Compter le nombre total de likes
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM comment_likes WHERE comment_id = ?");
        $stmt->execute([$comment_id]);
        $like_count = (int)$stmt->fetchColumn();
        
        $pdo->commit();
        
        return [
            'success' => true,
            'liked' => $liked,
            'like_count' => $like_count
        ];
        
    } catch (PDOException $e) {
        $pdo->rollBack();
        
        error_log('Erreur lors de la mise à jour du like : ' . $e->getMessage());
        log_message("Échec de la mise à jour du like pour le commentaire ID: $comment_id par l'utilisateur ID: $user_id", 'error');
        
        return [
            'success' => false,
            'message' => 'Une erreur est survenue. Veuillez réessayer.'
        ];
    }
}

/**
 * Récupère les commentaires signalés avec pagination et filtrage
 * 
 * @param int $page Numéro de la page (défaut: 1)
 * @param int $per_page Nombre de résultats par page (défaut: 10)
 * @param string $status Filtre par statut (pending, resolved, all)
 * @return array Tableau des commentaires signalés avec les informations associées
 */
function get_reported_comments($page = 1, $per_page = 10, $status = 'pending') {
    global $pdo;
    try {
        // Validation des entrées
        $page = max(1, (int)$page);
        $per_page = max(1, min(100, (int)$per_page)); // Limiter à 100 résultats par page
        $offset = ($page - 1) * $per_page;
        
        // Vérifier que le statut est valide
        $valid_statuses = ['pending', 'resolved', 'all'];
        if (!in_array($status, $valid_statuses)) {
            $status = 'pending'; // Valeur par défaut si le statut est invalide
        }
        
        // Préparer les paramètres de la requête
        $params = [
            ':per_page' => $per_page, 
            ':offset' => $offset
        ];
        
        // Construire la condition de statut
        $status_condition = '';
        if ($status !== 'all') {
            $status_condition = 'AND cr.status = :status';
            $params[':status'] = $status;
        } else {
            // Si status est 'all', on ne filtre pas sur le statut
            $status_condition = 'AND cr.status IN (\'pending\', \'resolved\', \'rejected\')';
        }
        
        // Requête pour récupérer les commentaires signalés
        $query = "
            SELECT 
                c.id,
                c.book_id,
                c.user_id,
                c.content,
                c.created_at,
                c.updated_at,
                c.is_deleted,
                c.report_count,
                b.title AS book_title,
                u.username AS author_name,
                MAX(cr.created_at) AS last_reported_at,
                COUNT(DISTINCT cr.id) AS report_count
            FROM comments c
            INNER JOIN comment_reports cr ON c.id = cr.comment_id
            LEFT JOIN books b ON c.book_id = b.id
            LEFT JOIN users u ON c.user_id = u.id
            WHERE c.is_deleted = 0 
            AND b.id IS NOT NULL  -- S'assurer que le livre existe
            AND u.id IS NOT NULL  -- S'assurer que l'utilisateur existe
            $status_condition
            GROUP BY c.id
            ORDER BY report_count DESC, last_reported_at DESC
            LIMIT :per_page OFFSET :offset
        ";
        
        $stmt = $pdo->prepare($query);
        
        // Lier les paramètres avec leur type approprié
        $stmt->bindValue(':per_page', $per_page, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        if ($status !== 'all') {
            $stmt->bindValue(':status', $status, PDO::PARAM_STR);
        }
        
        $execution_result = $stmt->execute();
        
        $comments = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Récupérer les détails des signalements pour chaque commentaire
        foreach ($comments as &$comment) {
            try {
                $comment['reports'] = get_comment_reports($comment['id']);
            } catch (Exception $e) {
                // En cas d'erreur, initialiser un tableau vide pour éviter les erreurs
                $comment['reports'] = [];
                error_log("Erreur lors de la récupération des signalements pour le commentaire {$comment['id']}: " . $e->getMessage());
            }
        }
        
        return $comments;
        
    } catch (PDOException $e) {
        // Journaliser l'erreur
        error_log("Erreur dans get_reported_comments: " . $e->getMessage());
        
        // Retourner un tableau vide en cas d'erreur
        return [];
    }
}

/**
 * Compte le nombre total de commentaires signalés
 * 
 * @param string $status Filtre par statut (pending, resolved, rejected, all)
 * @return int Nombre total de commentaires signalés selon le filtre
 */
function get_reported_comments_count($status = 'pending') {
    global $pdo;
    
    try {
        $params = [];
        $where_conditions = ["c.is_deleted = 0"];
        
        // Ajout de la condition de statut
        if ($status === 'pending') {
            $where_conditions[] = "cr.status = 'pending'";
        } elseif ($status === 'resolved') {
            $where_conditions[] = "cr.status = 'resolved'";
        } elseif ($status === 'rejected') {
            $where_conditions[] = "cr.status = 'rejected'";
        } // Si 'all', pas de condition supplémentaire
        
        $where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';
        
        $query = "
            SELECT COUNT(DISTINCT c.id) as count
            FROM comments c
            INNER JOIN comment_reports cr ON c.id = cr.comment_id
            $where_clause
        ";        
        
        try {
            $stmt = $pdo->prepare($query);
            
            // Exécution de la requête
            $execution_result = $stmt->execute($params);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return (int) ($result['count'] ?? 0);
        } catch (PDOException $e) {
            throw $e; // Relancer l'exception pour qu'elle soit gérée par le bloc catch principal
        }        
    } catch (PDOException $e) {
        // Journaliser l'erreur pour le débogage
        error_log('Erreur dans get_reported_comments_count: ' . $e->getMessage());
        return 0; // Retourne 0 en cas d'erreur pour éviter les erreurs d'affichage
    }
}


/**
 * Récupère les signalements pour un commentaire spécifique
 * 
 * @param int $comment_id ID du commentaire
 * @param int $limit Nombre maximum de signalements à retourner (0 pour tous)
 * @return array Tableau des signalements
 */
function get_comment_reports($comment_id, $limit = 0) {
    global $pdo;
    
    try {
        // Validation des entrées
        $comment_id = (int)$comment_id;
        $limit = max(0, (int)$limit); // S'assurer que la limite est un entier positif
        
        // Préparer la clause LIMIT
        $limit_clause = $limit > 0 ? 'LIMIT :limit' : '';
        
        // Requête pour récupérer les signalements d'un commentaire
        $query = "
            SELECT 
                cr.id,
                cr.comment_id,
                cr.user_id,
                cr.reason,
                cr.status,
                cr.created_at,
                cr.resolved_at,
                cr.resolved_by,
                cr.resolution_notes,
                u.username AS reporter_name,
                ru.username AS resolved_by_name
            FROM comment_reports cr
            LEFT JOIN users u ON cr.user_id = u.id
            LEFT JOIN users ru ON cr.resolved_by = ru.id
            LEFT JOIN comments c ON cr.comment_id = c.id
            WHERE cr.comment_id = :comment_id
            AND c.id IS NOT NULL  -- S'assurer que le commentaire existe encore
            ORDER BY cr.created_at DESC
            $limit_clause
        ";
        
        $stmt = $pdo->prepare($query);
        
        // Lier les paramètres avec leur type approprié
        $stmt->bindValue(':comment_id', $comment_id, PDO::PARAM_INT);
        
        if ($limit > 0) {
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        }
        
        $stmt->execute();
        
        // Récupérer les résultats
        $reports = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Formater les dates si nécessaire
        foreach ($reports as &$report) {
            // Vous pouvez formater les dates ici si nécessaire
            // Par exemple : $report['created_at_formatted'] = format_date($report['created_at']);
        }
        
        return $reports;
        
    } catch (PDOException $e) {
        // Journaliser l'erreur
        error_log("Erreur dans get_comment_reports pour le commentaire $comment_id: " . $e->getMessage());
        
        // Retourner un tableau vide en cas d'erreur
        return [];
    }
}

/**
 * Marque un signalement comme résolu
 * 
 * @param int $report_id ID du signalement
 * @param int $admin_id ID de l'administrateur qui effectue l'action
 * @return bool True en cas de succès, false sinon
 */
function resolve_comment_report($report_id, $admin_id) {
    global $pdo;
    
    try {
        $pdo->beginTransaction();
        
        // 1. D'abord, récupérer l'ID du commentaire lié au signalement
        $stmt = $pdo->prepare("
            SELECT comment_id, user_id, 
                   (SELECT book_id FROM comments WHERE id = comment_id) as book_id
            FROM comment_reports 
            WHERE id = ? AND status = 'pending'
        ");
        $stmt->execute([$report_id]);
        $report = $stmt->fetch();
        
        if (!$report) {
            $pdo->rollBack();
            return false;
        }
        
        $comment_id = $report['comment_id'];
        
        // 2. Mettre à jour le statut du signalement
        $stmt = $pdo->prepare("
            UPDATE comment_reports 
            SET status = 'resolved', 
                resolved_at = NOW(), 
                resolved_by = ? 
            WHERE id = ? AND status = 'pending'
        ");
        $stmt->execute([$admin_id, $report_id]);
        
        $updated = $stmt->rowCount() > 0;
        
        if ($updated) {
            // 3. Marquer le commentaire comme supprimé par un admin
            $stmt = $pdo->prepare("
                UPDATE comments 
                SET is_admin_deleted = 1,
                    deleted_at = NOW(),
                    deleted_by = ?,
                    report_count = (
                        SELECT COUNT(*) 
                        FROM comment_reports 
                        WHERE comment_id = ? AND status = 'pending'
                    )
                WHERE id = ?
            ");
            $stmt->execute([$admin_id, $comment_id, $comment_id]);
            
            // 4. Notifier l'utilisateur qui a signalé
            $admin = get_user_by_id($admin_id);
            if ($admin) {
                // Notification pour l'utilisateur qui a signalé
                $message = sprintf(
                    'Votre signalement sur un commentaire a été traité par un administrateur (%s). Le commentaire a été supprimé.',
                    htmlspecialchars($admin['username'])
                );
                
                add_notification(
                    $report['user_id'],
                    'report_resolved',
                    $message,
                    '/book.php?id=' . $report['book_id']
                );
                
                // Notification pour l'auteur du commentaire
                $message_author = 'Votre commentaire a été supprimé par un modérateur car il ne respectait pas nos conditions d\'utilisation.';
                add_notification(
                    $report['user_id'],
                    'comment_deleted',
                    $message_author,
                    '/book.php?id=' . $report['book_id']
                );
                
                // 5. Enregistrer l'action de modération
                log_moderation_action(
                    $admin_id,
                    $comment_id,
                    'resolve_report',
                    'Commentaire supprimé suite à un signalement validé par un modérateur'
                );
            }
        }
        
        $pdo->commit();
        return $updated;
        
    } catch (PDOException $e) {
        $pdo->rollBack();
        error_log('Erreur lors de la résolution du signalement : ' . $e->getMessage());
        return false;
    }
}

/**
 * Rejette un signalement
 * 
 * @param int $report_id ID du signalement
 * @param int $admin_id ID de l'administrateur qui effectue l'action
 * @return bool True en cas de succès, false sinon
 */
function reject_comment_report($report_id, $admin_id) {
    global $pdo;
    
    try {
        $pdo->beginTransaction();
        
        // 1. Récupérer les détails du signalement
        $stmt = $pdo->prepare("\n            SELECT cr.*, \n                   (SELECT user_id FROM comments WHERE id = cr.comment_id) as comment_user_id,\n                   (SELECT book_id FROM comments WHERE id = cr.comment_id) as book_id\n            FROM comment_reports cr\n            WHERE cr.id = ? AND cr.status = 'pending'\n        ");
        $stmt->execute([$report_id]);
        $report = $stmt->fetch();
        
        if (!$report) {
            $pdo->rollBack();
            return false;
        }
        
        $comment_id = $report['comment_id'];
        
        // 2. Mettre à jour le statut du signalement
        $stmt = $pdo->prepare("
            UPDATE comment_reports 
            SET status = 'rejected', 
                resolved_at = NOW(), 
                resolved_by = ? 
            WHERE id = ? AND status = 'pending'
        ");
        
        $stmt->execute([$admin_id, $report_id]);
        $updated = $stmt->rowCount() > 0;
        
        if ($updated) {
            // 3. Mettre à jour le compteur de signalements
            $stmt = $pdo->prepare("
                UPDATE comments 
                SET report_count = (
                    SELECT COUNT(*) 
                    FROM comment_reports 
                    WHERE comment_id = ? AND status = 'pending'
                )
                WHERE id = ?
            ");
            $stmt->execute([$comment_id, $comment_id]);
            
            // 4. Notifier l'utilisateur qui a signalé
            $admin = get_user_by_id($admin_id);
            if ($admin) {
                $message = sprintf(
                    'Votre signalement sur un commentaire a été examiné par un administrateur (%s) et n\'a pas été retenu.',
                    htmlspecialchars($admin['username'])
                );
                
                add_notification(
                    $report['user_id'],
                    'report_rejected',
                    $message,
                    '/book.php?id=' . $report['book_id'] . '#comment-' . $comment_id
                );
            }
            
            // 5. Enregistrer l'action de modération
            log_moderation_action(
                $admin_id,
                $comment_id,
                'reject_report',
                'Signalement rejeté par un modérateur'
            );
        }
        
        $pdo->commit();
        return $updated;
        
    } catch (PDOException $e) {
        if (isset($pdo) && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log('Erreur lors du rejet du signalement : ' . $e->getMessage());
        return false;
    }
}

/**
 * Ignore un signalement de commentaire
 * 
 * @param int $moderator_id ID du modérateur qui ignore le signalement
 * @param int $report_id ID du signalement à ignorer
 * @param string $reason Raison pour ignorer le signalement
 * @return bool True si le signalement a été ignoré avec succès
 */
function ignore_report($moderator_id, $report_id, $reason = '') {
    global $pdo;
    
    try {
        // Récupérer les détails du signalement
        $stmt = $pdo->prepare("
            SELECT cr.*, c.id as comment_id, c.user_id 
            FROM comment_reports cr
            JOIN comments c ON c.id = cr.comment_id
            WHERE cr.id = ? AND cr.status = 'pending'
        ");
        $stmt->execute([$report_id]);
        $report = $stmt->fetch();
        
        if (!$report) {
            throw new Exception("Signalement non trouvé ou déjà traité");
        }
        
        // Mettre à jour le statut du signalement
        $stmt = $pdo->prepare("
            UPDATE comment_reports 
            SET status = 'ignored',
                resolved_at = NOW(),
                resolved_by = :moderator_id,
                resolution_reason = :reason
            WHERE id = :report_id
        ");
        
        $result = $stmt->execute([
            ':moderator_id' => $moderator_id,
            ':reason' => $reason,
            ':report_id' => $report_id
        ]);
        
        if (!$result) {
            throw new Exception("Échec de la mise à jour du signalement");
        }
        
        // Enregistrer l'action de modération
        $log_reason = $reason ? "Raison : $reason" : "Aucune raison fournie";
        return log_moderation_action($moderator_id, $report['comment_id'], 'ignore', $log_reason);
        
    } catch (Exception $e) {
        error_log("Erreur lors de l'ignorance du signalement : " . $e->getMessage());
        return false;
    }
}

/**
 * Bannit un utilisateur suite à un commentaire inapproprié
 * 
 * @param int $moderator_id ID du modérateur qui bannit
 * @param int $user_id ID de l'utilisateur à bannir
 * @param int $comment_id ID du commentaire concerné
 * @param string $reason Raison du bannissement
 * @param string $duration Durée du bannissement (ex: '7 days', '1 month', 'permanent')
 * @return bool True si le bannissement a été effectué avec succès
 */
function ban_user($moderator_id, $user_id, $comment_id, $reason = '', $duration = '7 days') {
    global $pdo;
    
    try {
        // Vérifier que l'utilisateur et le commentaire existent
        $stmt = $pdo->prepare("SELECT id, is_banned, banned_until FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();
        
        if (!$user) {
            throw new Exception("Utilisateur non trouvé");
        }
        
        $stmt = $pdo->prepare("SELECT id, user_id FROM comments WHERE id = ?");
        $stmt->execute([$comment_id]);
        $comment = $stmt->fetch();
        
        if (!$comment) {
            throw new Exception("Commentaire non trouvé");
        }
        
        if ($comment['user_id'] != $user_id) {
            throw new Exception("Le commentaire n'appartient pas à cet utilisateur");
        }
        
        // Calculer la date de fin de bannissement
        $banned_until = null;
        if (strtolower($duration) !== 'permanent') {
            $banned_until = date('Y-m-d H:i:s', strtotime("+$duration"));
        }
        
        // Mettre à jour le statut de l'utilisateur
        $stmt = $pdo->prepare("
            UPDATE users 
            SET is_banned = 1,
                ban_reason = :reason,
                banned_until = :banned_until,
                banned_at = NOW(),
                banned_by = :banned_by
            WHERE id = :user_id
        ");
        
        $result = $stmt->execute([
            ':reason' => $reason,
            ':banned_until' => $banned_until,
            ':banned_by' => $moderator_id,
            ':user_id' => $user_id
        ]);
        
        if (!$result) {
            throw new Exception("Échec de la mise à jour du statut de l'utilisateur");
        }
        
        // Ajouter une notification à l'utilisateur
        $duration_text = ($duration === 'permanent') ? 'définitivement' : "pendant $duration";
        $message = "Votre compte a été banni $duration_text pour violation de nos conditions d'utilisation.";
        if (!empty($reason)) {
            $message .= " Raison : $reason";
        }
        
        add_notification($user_id, 'user_banned', $message, '/contact.php');
        
        // Enregistrer l'action de modération
        $log_reason = $reason . (empty($reason) ? '' : ' - ') . "Durée: $duration";
        return log_moderation_action($moderator_id, $comment_id, 'ban_user', $log_reason);
        
    } catch (Exception $e) {
        error_log("Erreur lors du bannissement de l'utilisateur : " . $e->getMessage());
        return false;
    }
}

/**
 * Envoie un avertissement à un utilisateur suite à un commentaire inapproprié
 * 
 * @param int $moderator_id ID du modérateur qui envoie l'avertissement
 * @param int $user_id ID de l'utilisateur à avertir
 * @param int $comment_id ID du commentaire concerné
 * @param string $reason Raison de l'avertissement
 * @return bool True si l'avertissement a été envoyé avec succès
 */
function warn_user($moderator_id, $user_id, $comment_id, $reason = '') {
    global $pdo;
    
    try {
        // Vérifier que l'utilisateur et le commentaire existent
        $stmt = $pdo->prepare("SELECT id FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        if (!$stmt->fetch()) {
            throw new Exception("Utilisateur non trouvé");
        }
        
        $stmt = $pdo->prepare("SELECT id, user_id FROM comments WHERE id = ?");
        $stmt->execute([$comment_id]);
        $comment = $stmt->fetch();
        
        if (!$comment) {
            throw new Exception("Commentaire non trouvé");
        }
        
        if ($comment['user_id'] != $user_id) {
            throw new Exception("Le commentaire n'appartient pas à cet utilisateur");
        }
        
        // Ajouter une notification à l'utilisateur
        $message = 'Vous avez reçu un avertissement pour un commentaire inapproprié.';
        if (!empty($reason)) {
            $message .= ' Raison : ' . $reason;
        }
        
        add_notification($user_id, 'user_warning', $message, '/profile.php?tab=warnings');
        
        // Enregistrer l'action de modération
        return log_moderation_action($moderator_id, $comment_id, 'warn_user', $reason);
        
    } catch (Exception $e) {
        error_log("Erreur lors de l'envoi de l'avertissement : " . $e->getMessage());
        return false;
    }
}

/**
 * Journalise une action de modération
 * 
 * @param int $moderator_id ID du modérateur
 * @param int $comment_id ID du commentaire concerné
 * @param string $action_type Type d'action (delete, resolve_report, reject_report, warn_user, ban_user)
 * @param string $reason Raison de l'action
 * @return bool True en cas de succès, false sinon
 */
function log_moderation_action($moderator_id, $comment_id, $action_type, $reason = '') {
    global $pdo;
    
    // Liste des types d'action valides
    $valid_actions = [
        'delete', 
        'ignore', 
        'warn_user', 
        'ban_user',
        'resolve_report',
        'reject_report'
    ];
    
    // Valider le type d'action
    if (!in_array($action_type, $valid_actions)) {
        error_log("Type d'action de modération invalide: " . $action_type);
        return false;
    }
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO moderation_actions (
                moderator_id, 
                comment_id, 
                action_type, 
                reason, 
                created_at
            ) VALUES (
                :moderator_id, 
                :comment_id, 
                :action_type, 
                :reason, 
                NOW()
            )
        ");
        
        $result = $stmt->execute([
            ':moderator_id' => $moderator_id,
            ':comment_id' => $comment_id,
            ':action_type' => $action_type,
            ':reason' => $reason
        ]);
        
        if (!$result) {
            $error = $stmt->errorInfo();
            error_log("Erreur SQL lors de la journalisation: " . print_r($error, true));
        }
        
        return $result;
        
    } catch (Exception $e) {
        error_log("Erreur lors de la journalisation de l'action de modération: " . $e->getMessage());
        return false;
    }
}


/**
 * Signale un commentaire comme inapproprié
 * 
 * @param int $comment_id ID du commentaire à signaler
 * @param int $user_id ID de l'utilisateur qui signale
 * @param string $reason Raison du signalement
 * @return array Tableau avec les clés 'success', 'message' et 'deleted'
 */
function report_comment($comment_id, $user_id, $reason) {
    global $pdo;
    
    // Valider la raison
    $reason = trim($reason);
    if (strlen($reason) < 10) {
        return [
            'success' => false,
            'message' => 'Veuillez fournir une raison détaillée (au moins 10 caractères)',
            'deleted' => false
        ];
    }
    
    try {
        // Vérifier que le commentaire existe
        $stmt = $pdo->prepare("SELECT id, user_id, report_count, book_id FROM comments WHERE id = ?");
        $stmt->execute([$comment_id]);
        $comment = $stmt->fetch();
        
        if (!$comment) {
            return [
                'success' => false,
                'message' => 'Commentaire non trouvé.',
                'deleted' => false
            ];
        }
        
        // Vérifier que l'utilisateur ne signale pas son propre commentaire
        if ($comment['user_id'] == $user_id) {
            return [
                'success' => false,
                'message' => 'Vous ne pouvez pas signaler votre propre commentaire.',
                'deleted' => false
            ];
        }
        
        // Vérifier si l'utilisateur a déjà signalé ce commentaire
        $stmt = $pdo->prepare("
            SELECT id, status 
            FROM comment_reports 
            WHERE comment_id = :comment_id 
            AND user_id = :user_id
        ");
        $stmt->execute([
            ':comment_id' => $comment_id,
            ':user_id' => $user_id
        ]);
        $existing_report = $stmt->fetch();
        
        if ($existing_report) {
            if ($existing_report['status'] === 'pending') {
                return [
                    'success' => false,
                    'message' => 'Vous avez déjà signalé ce commentaire. Il est en cours de modération.',
                    'deleted' => false
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Vous avez déjà signalé ce commentaire. Statut: ' . $existing_report['status'],
                    'deleted' => false
                ];
            }
        }
        
        // Démarrer une transaction
        $pdo->beginTransaction();
        
        // Enregistrer le signalement
        $stmt = $pdo->prepare("
            INSERT INTO comment_reports (
                comment_id, 
                user_id, 
                reason,
                created_at,
                status
            ) VALUES (
                :comment_id, 
                :user_id, 
                :reason,
                NOW(),
                'pending'
            )
        ");
        
        $result = $stmt->execute([
            ':comment_id' => $comment_id,
            ':user_id' => $user_id,
            ':reason' => $reason
        ]);
        
        if (!$result) {
            $pdo->rollBack();
            return [
                'success' => false,
                'message' => 'Une erreur est survenue lors de l\'enregistrement du signalement.',
                'deleted' => false
            ];
        }
        
        // Mettre à jour le compteur de signalements
        $stmt = $pdo->prepare("
            UPDATE comments 
            SET report_count = report_count + 1,
                updated_at = NOW()
            WHERE id = :comment_id
        ");
        $stmt->execute([':comment_id' => $comment_id]);
        
        // Enregistrer l'action de modération (signalement)
        log_moderation_action($user_id, $comment_id, 'report', $reason);
        
        // Vérifier si le commentaire doit être automatiquement supprimé (seuil de 5 signalements)
        $stmt = $pdo->prepare("SELECT report_count FROM comments WHERE id = ?");
        $stmt->execute([$comment_id]);
        $comment_data = $stmt->fetch();
        
        if ($comment_data && $comment_data['report_count'] + 1 >= 5) {
            // Supprimer automatiquement le commentaire
            $deleted = delete_comment($comment_id, $user_id, true);
            
            if ($deleted) {
                // Marquer tous les signalements en attente comme résolus
                $stmt = $pdo->prepare("
                    UPDATE comment_reports 
                    SET status = 'resolved',
                        resolved_at = NOW(),
                        resolved_by = :system_id
                    WHERE comment_id = :comment_id
                    AND status = 'pending'
                ");
                $stmt->execute([
                    ':comment_id' => $comment_id,
                    ':system_id' => 0 // 0 pour système
                ]);
                
                $pdo->commit();
                
                // Notifier les administrateurs
                $admins = get_users_by_role('admin');
                $comment_author = get_user_by_id($comment['user_id']);
                
                foreach ($admins as $admin) {
                    $message = sprintf(
                        'Le commentaire de %s a été signalé et supprimé automatiquement (trop de signalements). Raison : %s',
                        htmlspecialchars($comment_author['username'] ?? 'Utilisateur inconnu'),
                        htmlspecialchars($reason)
                    );
                    add_notification($admin['id'], 'comment_deleted', $message, '/admin/comments/reported');
                }
                
                // Notifier l'auteur du commentaire
                $author_message = 'Votre commentaire a été supprimé automatiquement en raison d\'un nombre élevé de signalements.';
                add_notification($comment['user_id'], 'comment_deleted', $author_message, '/book.php?id=' . $comment['book_id']);
                
                return [
                    'success' => true,
                    'message' => 'Le commentaire a été signalé et supprimé automatiquement en raison du nombre élevé de signalements.',
                    'deleted' => true
                ];
            }
        }
        
        // Si on arrive ici, le commentaire n'a pas été supprimé automatiquement
        $pdo->commit();
        
        // Notifier les administrateurs
        $admins = get_users_by_role('admin');
        $comment_author = get_user_by_id($comment['user_id']);
        $reporter = get_user_by_id($user_id);
        
        foreach ($admins as $admin) {
            $message = sprintf(
                'Le commentaire de %s a été signalé par %s. Raison : %s',
                htmlspecialchars($comment_author['username'] ?? 'Utilisateur inconnu'),
                htmlspecialchars($reporter['username'] ?? 'Utilisateur inconnu'),
                htmlspecialchars($reason)
            );
            
            add_notification($admin['id'], 'comment_reported', $message, '/admin/comments/reported');
        }
        
        // Ajouter une notification à l'auteur du commentaire
        $admin_message = 'Votre commentaire a été signalé pour le motif suivant : ' . htmlspecialchars($reason);
        add_notification($comment['user_id'], 'comment_reported', $admin_message, '/book.php?id=' . $comment['book_id']);
        
        log_message("Commentaire signalé (ID: $comment_id) par l'utilisateur ID: $user_id", 'info');
        
        return [
            'success' => true,
            'message' => 'Votre signalement a été enregistré. Merci pour votre contribution.',
            'deleted' => false
        ];
        
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        
        error_log("Erreur lors du signalement du commentaire: " . $e->getMessage());
        log_message("Échec du signalement du commentaire ID: $comment_id par l'utilisateur ID: $user_id - " . $e->getMessage(), 'error');
        
        return [
            'success' => false,
            'message' => 'Une erreur est survenue lors du traitement de votre signalement. Veuillez réessayer.',
            'deleted' => false
        ];
    }
}
