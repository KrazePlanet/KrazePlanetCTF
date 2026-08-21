<?php
/**
 * Opérations liées aux livres
 * 
 * Ce fichier contient les fonctions pour gérer les opérations CRUD sur les livres.
 */

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';

/**
 * Récupère le nombre total de livres
 * 
 * @param bool $include_pending Inclure les livres en attente de modération
 * @return int Nombre total de livres
 */
function get_total_books_count($include_pending = true) {
    global $pdo;
    
    try {
        $sql = "SELECT COUNT(*) FROM books WHERE 1=1";
        $params = [];
        
        if (!$include_pending) {
            $sql .= " AND status = 'approved'";
        }
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        
        return (int)$stmt->fetchColumn();
    } catch (PDOException $e) {
        error_log("Erreur lors du comptage des livres: " . $e->getMessage());
        return 0;
    }
}

/**
 * Récupère le nombre de livres en attente de modération
 * 
 * @return int Nombre de livres en attente
 */
function get_pending_books_count() {
    global $pdo;
    
    try {
        $stmt = $pdo->query("SELECT COUNT(*) FROM books WHERE status = 'pending'");
        return (int)$stmt->fetchColumn();
    } catch (PDOException $e) {
        error_log("Erreur lors du comptage des livres en attente: " . $e->getMessage());
        return 0;
    }
}

/**
 * Récupère les statistiques des livres par genre
 * 
 * @return array Tableau associatif genre => nombre de livres
 */
function get_books_stats_by_genre() {
    global $pdo;
    
    $stats = [];
    $sql = "
        SELECT 
            genre,
            COUNT(*) as count
        FROM books
        WHERE status = 'approved'
        GROUP BY genre
        ORDER BY count DESC
    ";
    
    try {
        $stmt = $pdo->query($sql);
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $stats[$row['genre']] = (int)$row['count'];
        }
    } catch (PDOException $e) {
        error_log("Erreur lors de la récupération des statistiques par genre: " . $e->getMessage());
    }
    
    return $stats;
}

/**
 * Récupère les statistiques d'ajout de livres sur une période
 * 
 * @param string $period Période ('day', 'week', 'month')
 * @return array Tableau des statistiques
 */
function get_books_addition_stats($period = 'month') {
    global $pdo;
    
    $stats = [];
    $sql = "";
    
    switch ($period) {
        case 'day':
            $sql = "
                SELECT 
                    DATE(created_at) as date,
                    COUNT(*) as count
                FROM books
                WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
                GROUP BY DATE(created_at)
                ORDER BY date
            ";
            break;
            
        case 'week':
            $sql = "
                SELECT 
                    YEARWEEK(created_at, 1) as week,
                    COUNT(*) as count
                FROM books
                WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 12 WEEK)
                GROUP BY YEARWEEK(created_at, 1)
                ORDER BY week
            ";
            break;
            
        case 'month':
        default:
            $sql = "
                SELECT 
                    DATE_FORMAT(created_at, '%Y-%m') as month,
                    COUNT(*) as count
                FROM books
                WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
                GROUP BY DATE_FORMAT(created_at, '%Y-%m')
                ORDER BY month
            ";
            break;
    }
    
    try {
        $stmt = $pdo->query($sql);
        $stats = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Erreur lors de la récupération des statistiques d'ajout: " . $e->getMessage());
    }
    
    return $stats;
}

/**
 * Ajoute un nouveau livre à la base de données
 * 
 * @param array $data Données du livre à ajouter
 * @param array $file Fichier image de couverture (optionnel)
 * @return int|false ID du livre ajouté ou false en cas d'échec
 */
function add_book($data, $file = null) {
    global $pdo;
    
    // Valider les données
    $errors = [];
    $required_fields = ['title', 'author', 'user_id'];
    
    foreach ($required_fields as $field) {
        if (empty($data[$field])) {
            $errors[$field] = 'Ce champ est obligatoire';
        }
    }
    
    if (!empty($errors)) {
        $_SESSION['form_errors'] = $errors;
        $_SESSION['form_data'] = $data;
        return false;
    }
    
    // Traiter l'image de couverture
    $image_path = null;
    if (!empty($file) && $file['error'] === UPLOAD_ERR_OK) {
        try {
            $image_path = upload_image($file);
        } catch (Exception $e) {
            $errors['image'] = $e->getMessage();
            $_SESSION['form_errors'] = $errors;
            $_SESSION['form_data'] = $data;
            return false;
        }
    }
    
    try {
        $pdo->beginTransaction();
        
        $stmt = $pdo->prepare("
            INSERT INTO books (
                title, 
                author, 
                genre, 
                summary, 
                liked, 
                disliked, 
                image, 
                user_id,
                created_at,
                updated_at
            ) VALUES (
                :title, 
                :author, 
                :genre, 
                :summary, 
                :liked, 
                :disliked, 
                :image, 
                :user_id,
                NOW(),
                NOW()
            )
        ");
        
        $stmt->execute([
            ':title' => $data['title'],
            ':author' => $data['author'],
            ':genre' => $data['genre'] ?? null,
            ':summary' => $data['summary'] ?? null,
            ':liked' => $data['liked'] ?? null,
            ':disliked' => $data['disliked'] ?? null,
            ':image' => $image_path,
            ':user_id' => $data['user_id']
        ]);
        
        $book_id = $pdo->lastInsertId();
        $pdo->commit();
        
        // Journaliser l'action
        log_message("Nouveau livre ajouté (ID: $book_id) par l'utilisateur ID: " . $data['user_id'], 'info');
        
        return $book_id;
        
    } catch (PDOException $e) {
        $pdo->rollBack();
        
        // Supprimer l'image téléchargée en cas d'échec
        if ($image_path && file_exists('uploads/' . $image_path)) {
            unlink('uploads/' . $image_path);
        }
        
        error_log('Erreur lors de l\'ajout du livre : ' . $e->getMessage());
        log_message("Échec de l'ajout d'un livre par l'utilisateur ID: " . ($data['user_id'] ?? 'inconnu'), 'error');
        
        $_SESSION['form_errors'] = ['general' => 'Une erreur est survenue lors de l\'ajout du livre. Veuillez réessayer.'];
        $_SESSION['form_data'] = $data;
        return false;
    }
}

/**
 * Met à jour un livre existant
 * 
 * @param int $book_id ID du livre à mettre à jour
 * @param array $data Nouvelles données du livre
 * @param array $file Nouvelle image de couverture (optionnel)
 * @param bool $remove_image Supprimer l'image existante
 * @return bool True en cas de succès, false sinon
 */
function update_book($book_id, $data, $file = null, $remove_image = false) {
    global $pdo;
    
    // Valider les données
    $errors = [];
    $required_fields = ['title', 'author'];
    
    foreach ($required_fields as $field) {
        if (empty($data[$field])) {
            $errors[$field] = 'Ce champ est obligatoire';
        }
    }
    
    if (!empty($errors)) {
        $_SESSION['form_errors'] = $errors;
        $_SESSION['form_data'] = $data;
        return false;
    }
    
    // Récupérer le livre existant
    $book = get_book($book_id);
    if (!$book) {
        $_SESSION['form_errors'] = ['general' => 'Livre non trouvé.'];
        return false;
    }
    
    // Vérifier que l'utilisateur est l'auteur du livre ou un administrateur
    $current_user_id = $_SESSION['user_id'] ?? 0;
    if ($book['user_id'] != $current_user_id && !is_admin()) {
        $_SESSION['form_errors'] = ['general' => 'Vous n\'êtes pas autorisé à modifier ce livre.'];
        return false;
    }
    
    $image_path = $book['image'];
    
    // Gérer la suppression de l'image existante
    if ($remove_image && $image_path) {
        if (file_exists('uploads/' . $image_path)) {
            unlink('uploads/' . $image_path);
        }
        $image_path = null;
    }
    
    // Traiter la nouvelle image
    if (!empty($file) && $file['error'] === UPLOAD_ERR_OK) {
        try {
            // Supprimer l'ancienne image si elle existe
            if ($image_path && file_exists('uploads/' . $image_path)) {
                unlink('uploads/' . $image_path);
            }
            
            $image_path = upload_image($file);
        } catch (Exception $e) {
            $errors['image'] = $e->getMessage();
            $_SESSION['form_errors'] = $errors;
            $_SESSION['form_data'] = $data;
            return false;
        }
    }
    
    try {
        $pdo->beginTransaction();
        
        $stmt = $pdo->prepare("
            UPDATE books 
            SET 
                title = :title,
                author = :author,
                genre = :genre,
                summary = :summary,
                liked = :liked,
                disliked = :disliked,
                image = :image,
                updated_at = NOW()
            WHERE id = :id AND user_id = :user_id
        ");
        
        $result = $stmt->execute([
            ':title' => $data['title'],
            ':author' => $data['author'],
            ':genre' => $data['genre'] ?? null,
            ':summary' => $data['summary'] ?? null,
            ':liked' => $data['liked'] ?? null,
            ':disliked' => $data['disliked'] ?? null,
            ':image' => $image_path,
            ':id' => $book_id,
            ':user_id' => $book['user_id']
        ]);
        
        $pdo->commit();
        
        if ($result) {
            log_message("Livre mis à jour (ID: $book_id) par l'utilisateur ID: " . $book['user_id'], 'info');
            return true;
        }
        
        return false;
        
    } catch (PDOException $e) {
        $pdo->rollBack();
        
        // Supprimer la nouvelle image téléchargée en cas d'échec
        if (isset($file) && $file['error'] === UPLOAD_ERR_OK && $image_path && file_exists('uploads/' . $image_path)) {
            unlink('uploads/' . $image_path);
        }
        
        error_log('Erreur lors de la mise à jour du livre : ' . $e->getMessage());
        log_message("Échec de la mise à jour du livre ID: $book_id par l'utilisateur ID: " . ($book['user_id'] ?? 'inconnu'), 'error');
        
        $_SESSION['form_errors'] = ['general' => 'Une erreur est survenue lors de la mise à jour du livre. Veuillez réessayer.'];
        $_SESSION['form_data'] = $data;
        return false;
    }
}

/**
 * Supprime un livre de manière logique (marque comme supprimé sans supprimer physiquement)
 * 
 * @param int $book_id ID du livre à supprimer
 * @return bool True en cas de succès, false sinon
 */
function delete_book($book_id) {
    global $pdo;

    // Récupérer le livre
    $book = get_book($book_id, true); // Récupérer même les livres supprimés
    if (!$book) {
        $_SESSION['error'] = 'Livre non trouvé.';
        return false;
    }

    // Vérifier que le livre n'est pas déjà supprimé
    if (!empty($book['is_deleted'])) {
        $_SESSION['error'] = 'Ce livre a déjà été supprimé.';
        return false;
    }

    // Vérifier que l'utilisateur est l'auteur du livre ou un administrateur
    $current_user_id = $_SESSION['user_id'] ?? 0;
    $is_admin = is_admin();
    $is_author = isset($book['user_id']) && $book['user_id'] == $current_user_id;

    if (!$is_admin && !$is_author) {
        $_SESSION['error'] = 'Vous n\'êtes pas autorisé à supprimer ce livre.';
        return false;
    }

    // Journalisation avant suppression
    $user_id = $_SESSION['user_id'] ?? 0;
    log_message("Tentative de suppression logique du livre ID: $book_id par l'utilisateur ID: $user_id", 'info');

    try {
        $pdo->beginTransaction();

        // Marquer le livre comme supprimé (suppression logique)
        $stmt = $pdo->prepare("
            UPDATE books 
            SET is_deleted = 1,
                deleted_at = NOW(),
                deleted_by = ?
            WHERE id = ? AND is_deleted = 0
        ");
        $stmt->execute([$user_id, $book_id]);

        // Vérifier si la mise à jour a réussi
        if ($stmt->rowCount() > 0) {
            // Marquer les commentaires comme supprimés (suppression logique)
            $stmt = $pdo->prepare("
                UPDATE comments 
                SET is_deleted = 1,
                    deleted_at = NOW(),
                    deleted_by = ?
                WHERE book_id = ? AND is_deleted = 0
            ");
            $stmt->execute([$user_id, $book_id]);

            $pdo->commit();

            // Journalisation du succès
            log_message("Livre ID: $book_id marqué comme supprimé par l'utilisateur ID: $user_id", 'info');

            $_SESSION['success'] = 'Le livre a été supprimé avec succès.';
            return true;
        } else {
            $pdo->rollBack();
            $_SESSION['error'] = 'Le livre n\'a pas pu être supprimé ou a déjà été supprimé.';
            return false;
        }

    } catch (PDOException $e) {
        $pdo->rollBack();

        error_log('Erreur lors de la suppression logique du livre : ' . $e->getMessage());
        log_message("Échec de la suppression logique du livre ID: $book_id par l'utilisateur ID: " . $user_id, 'error');

        $_SESSION['error'] = 'Une erreur est survenue lors de la suppression du livre. Veuillez réessayer.';
        return false;
    }
}

/**
 * Récupère la liste des livres avec pagination
 * 
 * @param int $page Numéro de la page
 * @param int $per_page Nombre d'éléments par page
 * @param string $search Terme de recherche (optionnel)
 * @param string $genre Filtre par genre (optionnel)
 * @param int $user_id Filtre par utilisateur (optionnel)
 * @param bool $include_deleted Inclure les livres supprimés (false par défaut)
 * @return array Tableau contenant les livres et les informations de pagination
 */
function get_books($page = 1, $per_page = 10, $search = '', $genre = '', $user_id = null, $include_deleted = false) {
    global $pdo;
    
    $offset = max(0, ($page - 1) * $per_page);
    $params = [];
    $where_conditions = [];
    
    // Ne pas inclure les livres supprimés sauf si explicitement demandé
    if (!$include_deleted) {
        $where_conditions[] = "(b.is_deleted = 0 OR b.is_deleted IS NULL)";
    } else {
        // Si on inclut les supprimés, on ne filtre pas sur is_deleted
        // mais on peut ajouter une condition pour voir seulement les supprimés si nécessaire
        $where_conditions[] = "b.is_deleted = 1";
    }
    
    // Construire les conditions de la requête
    if (!empty($search)) {
        $where_conditions[] = "(b.title LIKE ? OR b.author LIKE ? OR b.summary LIKE ?)";
        $search_term = "%$search%";
        $params = array_merge($params, [$search_term, $search_term, $search_term]);
    }
    
    if (!empty($genre)) {
        $where_conditions[] = "b.genre = ?";
        $params[] = $genre;
    }
    
    if ($user_id !== null) {
        $where_conditions[] = "b.user_id = ?";
        $params[] = $user_id;
    }
    
    $where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';
    
    // Requête pour compter le nombre total de livres
    $count_sql = "SELECT COUNT(*) as total FROM books b $where_clause";
    $count_stmt = $pdo->prepare($count_sql);
    $count_stmt->execute($params);
    $total_items = (int)$count_stmt->fetch(PDO::FETCH_ASSOC)['total'];
    $total_pages = ceil($total_items / $per_page);
    
    // Requête pour récupérer les livres de la page courante
    $sql = "
        SELECT 
            b.*, 
            u.username as author_username,
            u.avatar as author_avatar,
            (SELECT COUNT(*) FROM comments c WHERE c.book_id = b.id) as comment_count,
            (SELECT COUNT(*) FROM book_likes bl WHERE bl.book_id = b.id) as like_count
        FROM books b
        JOIN users u ON b.user_id = u.id
        $where_clause
        ORDER BY b.created_at DESC
        LIMIT ? OFFSET ?
    ";
    
    // Ajouter les paramètres de pagination
    $params[] = (int)$per_page;
    $params[] = (int)$offset;
    
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $books = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return [
            'books' => $books,
            'pagination' => [
                'current_page' => (int)$page,
                'per_page' => (int)$per_page,
                'total_items' => $total_items,
                'total_pages' => $total_pages
            ],
            'filters' => [
                'search' => $search,
                'genre' => $genre,
                'user_id' => $user_id
            ]
        ];
    } catch (PDOException $e) {
        error_log('Erreur lors de la récupération des livres : ' . $e->getMessage());
        return [
            'books' => [],
            'pagination' => [
                'current_page' => 1,
                'per_page' => $per_page,
                'total_items' => 0,
                'total_pages' => 0
            ],
            'filters' => [
                'search' => $search,
                'genre' => $genre,
                'user_id' => $user_id
            ]
        ];
    }
}

/**
 * Récupère les genres uniques des livres
 * 
 * @return array Tableau des genres
 */
function get_book_genres() {
    global $pdo;
    
    $stmt = $pdo->query("SELECT DISTINCT genre FROM books WHERE genre IS NOT NULL AND genre != '' ORDER BY genre");
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}

/**
 * Restaure un livre précédemment supprimé logiquement
 * 
 * @param int $book_id ID du livre à restaurer
 * @return bool True en cas de succès, false sinon
 */
function restore_book($book_id) {
    global $pdo;
    
    // Récupérer le livre (y compris les supprimés)
    $book = get_book($book_id, true);
    if (!$book) {
        $_SESSION['error'] = 'Livre non trouvé.';
        return false;
    }
    
    // Vérifier que le livre est bien marqué comme supprimé
    if (empty($book['is_deleted'])) {
        $_SESSION['error'] = 'Ce livre n\'est pas marqué comme supprimé.';
        return false;
    }
    
    // Vérifier les droits d'administration
    if (!is_admin()) {
        $_SESSION['error'] = 'Vous n\'êtes pas autorisé à restaurer ce livre.';
        return false;
    }
    
    try {
        $pdo->beginTransaction();
        
        // Restaurer le livre
        $stmt = $pdo->prepare("
            UPDATE books 
            SET is_deleted = 0,
                deleted_at = NULL,
                deleted_by = NULL
            WHERE id = ? AND is_deleted = 1
        ");
        $stmt->execute([$book_id]);
        
        if ($stmt->rowCount() === 0) {
            throw new Exception('Aucun livre à restaurer.');
        }
        
        // Restaurer les commentaires associés
        $stmt = $pdo->prepare("
            UPDATE comments 
            SET is_deleted = 0,
                deleted_at = NULL,
                deleted_by = NULL
            WHERE book_id = ? AND is_deleted = 1
        ");
        $stmt->execute([$book_id]);
        
        $pdo->commit();
        
        // Journalisation
        log_message("Livre ID: $book_id restauré par l'utilisateur ID: " . ($_SESSION['user_id'] ?? 0), 'info');
        
        $_SESSION['success'] = 'Le livre a été restauré avec succès.';
        return true;
        
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log('Erreur lors de la restauration du livre : ' . $e->getMessage());
        $_SESSION['error'] = 'Une erreur est survenue lors de la restauration du livre.';
        return false;
    }
}

/**
 * Vérifie si un utilisateur est l'auteur d'un livre
 * 
 * @param int $book_id ID du livre
 * @param int $user_id ID de l'utilisateur
 * @return bool True si l'utilisateur est l'auteur, false sinon
 */
function is_book_author($book_id, $user_id) {
    global $pdo;
    
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM books WHERE id = ? AND user_id = ?");
    $stmt->execute([$book_id, $user_id]);
    return $stmt->fetchColumn() > 0;
}

/**
 * Incrémente le compteur de vues d'un livre
 * 
 * @param int $book_id ID du livre
 * @return bool True en cas de succès, false sinon
 */
function increment_book_views($book_id)
{
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("UPDATE books SET views = views + 1 WHERE id = ?");
        return $stmt->execute([$book_id]);
    } catch (PDOException $e) {
        error_log('Erreur lors de l\'incrémentation des vues du livre : ' . $e->getMessage());
        return false;
    }
}

/**
 * Ajoute ou supprime un like pour un livre
 * 
 * @param int $book_id ID du livre
 * @param int $user_id ID de l'utilisateur
 * @return array Tableau avec le statut et le nombre total de likes
 */
function toggle_book_like($book_id, $user_id)
{
    global $pdo;
    
    try {
        $pdo->beginTransaction();
        
        // Vérifier si l'utilisateur a déjà liké ce livre
        $stmt = $pdo->prepare("SELECT id FROM book_likes WHERE book_id = ? AND user_id = ?");
        $stmt->execute([$book_id, $user_id]);
        $existing_like = $stmt->fetch();
        
        if ($existing_like) {
            // Supprimer le like
            $stmt = $pdo->prepare("DELETE FROM book_likes WHERE id = ?");
            $stmt->execute([$existing_like['id']]);
            $liked = false;
        } else {
            // Ajouter le like
            $stmt = $pdo->prepare("INSERT INTO book_likes (book_id, user_id) VALUES (?, ?)");
            $stmt->execute([$book_id, $user_id]);
            $liked = true;
            
            // Envoyer une notification à l'auteur du livre
            $book = get_book($book_id);
            if ($book && $book['user_id'] != $user_id) {
                $notification_message = sprintf(
                    '%s a aimé votre livre "%s"',
                    htmlspecialchars(get_username($user_id)),
                    htmlspecialchars($book['title'])
                );
                
                // Vérifier si la table notifications existe avant d'insérer
                try {
                    $pdo->prepare("
                        INSERT INTO notifications (user_id, type, message, target_url, created_at)
                        VALUES (?, 'book_like', ?, ?, NOW())
                    ")->execute([
                        $book['user_id'],
                        $notification_message,
                        url("book.php?id=" . $book_id)
                    ]);
                } catch (PDOException $e) {
                    // Log l'erreur mais ne pas arrêter l'exécution
                    error_log('Erreur lors de l\'ajout de la notification : ' . $e->getMessage());
                }
            }
        }
        
        // Récupérer le nombre total de likes
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM book_likes WHERE book_id = ?");
        $stmt->execute([$book_id]);
        $like_count = $stmt->fetchColumn();
        
        // Mettre à jour le compteur de likes dans la table books
        $pdo->prepare("UPDATE books SET like_count = ? WHERE id = ?")
            ->execute([$like_count, $book_id]);
        
        $pdo->commit();
        
        return [
            'success' => true,
            'liked' => $liked,
            'like_count' => (int)$like_count
        ];
        
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log('Erreur lors du like du livre : ' . $e->getMessage());
        return ['success' => false, 'error' => 'Une erreur est survenue'];
    }
}

/**
 * Vérifie si un utilisateur a aimé un livre
 * 
 * @param int $book_id ID du livre
 * @param int $user_id ID de l'utilisateur
 * @return bool True si l'utilisateur a aimé le livre, false sinon
 */
function has_user_liked_book($book_id, $user_id)
{
    if (!$user_id) return false;
    
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM book_likes WHERE book_id = ? AND user_id = ?");
        $stmt->execute([$book_id, $user_id]);
        return (bool)$stmt->fetchColumn();
    } catch (PDOException $e) {
        error_log('Erreur lors de la vérification du like : ' . $e->getMessage());
        return false;
    }
}

/**
 * Récupère les livres aimés par un utilisateur
 * 
 * @param int $user_id ID de l'utilisateur
 * @param int $page Numéro de la page
 * @param int $per_page Nombre d'éléments par page
 * @return array Tableau contenant les livres et les informations de pagination
 */
function get_liked_books($user_id, $page = 1, $per_page = 10)
{
    global $pdo;
    
    $offset = ($page - 1) * $per_page;
    
    try {
        // Compter le nombre total de livres aimés
        $count_stmt = $pdo->prepare("
            SELECT COUNT(*) 
            FROM book_likes bl
            JOIN books b ON bl.book_id = b.id
            WHERE bl.user_id = ? AND b.is_deleted = 0
        ");
        $count_stmt->execute([$user_id]);
        $total = $count_stmt->fetchColumn();
        
        // Récupérer les livres aimés avec pagination
        $stmt = $pdo->prepare("
            SELECT b.*, 
                   u.username as author_name,
                   u.avatar as author_avatar,
                   (SELECT COUNT(*) FROM comments c WHERE c.book_id = b.id) as comment_count
            FROM book_likes bl
            JOIN books b ON bl.book_id = b.id
            JOIN users u ON b.user_id = u.id
            WHERE bl.user_id = ? AND b.is_deleted = 0
            ORDER BY bl.created_at DESC
            LIMIT ? OFFSET ?
        ");
        $stmt->execute([$user_id, $per_page, $offset]);
        $books = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return [
            'books' => $books,
            'total' => (int)$total,
            'page' => $page,
            'per_page' => $per_page,
            'total_pages' => ceil($total / $per_page)
        ];
        
    } catch (PDOException $e) {
        error_log('Erreur lors de la récupération des livres aimés : ' . $e->getMessage());
        return [
            'books' => [],
            'total' => 0,
            'page' => $page,
            'per_page' => $per_page,
            'total_pages' => 0
        ];
    }
}

/**
 * Récupère les livres les plus populaires (par nombre de likes)
 * 
 * @param int $limit Nombre maximum de livres à retourner
 * @return array Tableau des livres populaires
 */
function get_popular_books($limit = 5)
{
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            SELECT b.*, 
                   u.username as author_name,
                   u.avatar as author_avatar,
                   b.like_count,
                   (SELECT COUNT(*) FROM comments c WHERE c.book_id = b.id) as comment_count
            FROM books b
            JOIN users u ON b.user_id = u.id
            WHERE b.is_deleted = 0
            ORDER BY b.like_count DESC, b.views DESC, b.created_at DESC
            LIMIT ?
        ");
        $stmt->execute([$limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log('Erreur lors de la récupération des livres populaires : ' . $e->getMessage());
        return [];
    }
}
