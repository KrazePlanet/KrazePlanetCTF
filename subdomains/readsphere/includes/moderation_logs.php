<?php
/**
 * Gestion des logs de modération
 * 
 * Ce fichier contient les fonctions pour gérer les logs des actions de modération
 * effectuées par les administrateurs et les modérateurs.
 */

require_once __DIR__ . '/init.php';

/**
 * Récupère les logs de modération avec pagination et filtres
 * 
 * @param int $page Numéro de page (commence à 1)
 * @param int $per_page Nombre d'éléments par page
 * @param int|null $user_id Filtrer par ID utilisateur
 * @param string $action_type Filtrer par type d'action
 * @param string $start_date Date de début (format YYYY-MM-DD)
 * @param string $end_date Date de fin (format YYYY-MM-DD)
 * @return array Tableau des logs de modération
 */
function get_moderation_logs($page = 1, $per_page = 20, $user_id = null, $action_type = '', $start_date = '', $end_date = '') {
    global $pdo;
    
    $offset = ($page - 1) * $per_page;
    $params = [];
    
    $sql = "SELECT 
                ma.*, 
                u.id as user_id,
                u.username,
                u2.username as moderator_username,
                c.book_id,
                c.content as comment_content,
                b.title as book_title
            FROM moderation_actions ma
            LEFT JOIN comments c ON c.id = ma.comment_id
            LEFT JOIN users u ON u.id = c.user_id
            LEFT JOIN users u2 ON u2.id = ma.moderator_id
            LEFT JOIN books b ON b.id = c.book_id
            WHERE 1=1";
    
    // Filtre par utilisateur (celui qui a posté le commentaire)
    if ($user_id !== null) {
        $sql .= " AND c.user_id = :user_id";
        $params[':user_id'] = $user_id;
    }
    
    // Filtre par type d'action
    if (!empty($action_type)) {
        $sql .= " AND ma.action_type = :action_type";
        $params[':action_type'] = $action_type;
    }
    
    // Filtre par date de début
    if (!empty($start_date)) {
        $sql .= " AND DATE(ma.created_at) >= :start_date";
        $params[':start_date'] = $start_date;
    }
    
    // Filtre par date de fin
    if (!empty($end_date)) {
        $sql .= " AND DATE(ma.created_at) <= :end_date";
        $params[':end_date'] = $end_date;
    }
    
    // Tri et pagination
    $sql .= " ORDER BY ma.created_at DESC
              LIMIT :offset, :per_page";
    
    $params[':offset'] = $offset;
    $params[':per_page'] = $per_page;
    
    try {
        // Log de la requête SQL et des paramètres
        // error_log('Requête SQL: ' . $sql);
        // error_log('Paramètres: ' . print_r($params, true));
        
        $stmt = $pdo->prepare($sql);
        
        // Liaison des paramètres
        foreach ($params as $key => $value) {
            $param_type = is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR;
            $stmt->bindValue($key, $value, $param_type);
        }
        
        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        // error_log('Résultats: ' . count($result) . ' lignes trouvées');
        return $result;
    } catch (PDOException $e) {
        $error_msg = 'Erreur lors de la récupération des logs de modération: ' . $e->getMessage();
        error_log($error_msg);
        error_log('Requête SQL: ' . $sql);
        error_log('Paramètres: ' . print_r($params, true));
        return [];
    }
}

/**
 * Compte le nombre total de logs de modération avec filtres
 * 
 * @param int|null $user_id Filtrer par ID utilisateur
 * @param string $action_type Filtrer par type d'action
 * @param string $start_date Date de début (format YYYY-MM-DD)
 * @param string $end_date Date de fin (format YYYY-MM-DD)
 * @return int Nombre total de logs
 */
function get_moderation_logs_count($user_id = null, $action_type = '', $start_date = '', $end_date = '') {
    global $pdo;
    
    $params = [];
    $sql = "SELECT COUNT(*) as count 
            FROM moderation_actions ma
            LEFT JOIN comments c ON c.id = ma.comment_id
            WHERE 1=1";
    
    // Filtre par utilisateur (celui qui a posté le commentaire)
    if ($user_id !== null) {
        $sql .= " AND c.user_id = :user_id";
        $params[':user_id'] = $user_id;
    }
    
    // Filtre par type d'action
    if (!empty($action_type)) {
        $sql .= " AND ma.action_type = :action_type";
        $params[':action_type'] = $action_type;
    }
    
    // Filtre par date de début
    if (!empty($start_date)) {
        $sql .= " AND DATE(ma.created_at) >= :start_date";
        $params[':start_date'] = $start_date;
    }
    
    // Filtre par date de fin
    if (!empty($end_date)) {
        $sql .= " AND DATE(ma.created_at) <= :end_date";
        $params[':end_date'] = $end_date;
    }
    
    try {
        // Log de la requête SQL et des paramètres
        // error_log('Requête SQL (count): ' . $sql);
        // error_log('Paramètres (count): ' . print_r($params, true));
        
        $stmt = $pdo->prepare($sql);
        
        // Liaison des paramètres
        foreach ($params as $key => $value) {
            $param_type = is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR;
            $stmt->bindValue($key, $value, $param_type);
        }
        
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $count = (int) ($result['count'] ?? 0);
        
        // error_log('Nombre total de résultats: ' . $count);
        return $count;
    } catch (PDOException $e) {
        $error_msg = 'Erreur lors du comptage des logs de modération: ' . $e->getMessage();
        error_log($error_msg);
        error_log('Requête SQL (count): ' . $sql);
        error_log('Paramètres (count): ' . print_r($params, true));
        return 0;
    }
}
