<?php
/**
 * Tableau de bord
 * 
 * Cette page permet de visualiser les statistiques et les livres de l'utilisateur.
 */

require_once 'includes/init.php';

// Vérifier que l'utilisateur est connecté
require_auth();

$user = current_user(); 
$is_admin = is_admin();
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$per_page = 8;
$show_deleted = $is_admin && isset($_GET['show_deleted']);

// Récupérer les livres avec pagination
try {
    $offset = ($page - 1) * $per_page;
    $params = [];
    $where = [];
    
    // Construction de la requête de base
    $sql = "SELECT b.*, 
           u.username as owner_username,
           (SELECT COUNT(*) FROM book_likes l WHERE l.book_id = b.id) as like_count,
           (SELECT COUNT(*) FROM comments c WHERE c.book_id = b.id) as comment_count
           FROM books b
           LEFT JOIN users u ON b.user_id = u.id";
    
    if ($is_admin) {
        // Pour les administrateurs, filtrer selon les options
        if ($show_deleted) {
            $where[] = "b.is_deleted = 1";
        } else {
            $where[] = "b.is_deleted = 0";
        }
    } else {
        // Pour les utilisateurs normaux, n'afficher que leurs livres non supprimés
        $where[] = "b.user_id = :user_id";
        $where[] = "b.is_deleted = 0";
        $params[':user_id'] = $user['id'];
    }
    
    // Ajout des conditions WHERE si nécessaire
    if (!empty($where)) {
        $sql .= " WHERE " . implode(' AND ', $where);
    }
    
    // Comptage total des résultats
    $count_sql = "SELECT COUNT(*) FROM books b" . (!empty($where) ? " WHERE " . implode(' AND ', $where) : '');
    $stmt = $pdo->prepare($count_sql);
    $stmt->execute($params);
    $total_books = $stmt->fetchColumn();
    
    // Requête paginée
    $sql .= " ORDER BY b.created_at DESC LIMIT :limit OFFSET :offset";
    $stmt = $pdo->prepare($sql);
    
    // Liaison des paramètres
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
    }
    $stmt->bindValue(':limit', $per_page, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $books = $stmt->fetchAll();
    
    $total_pages = ceil($total_books / $per_page);
    
    // Récupérer les statistiques pour les administrateurs
    $stats = [];
    if ($is_admin) {
        $stats['total_users'] = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
        $stats['active_users'] = $pdo->query("SELECT COUNT(*) FROM users WHERE is_active = 1")->fetchColumn();
        $stats['verified_users'] = $pdo->query("SELECT COUNT(*) FROM users WHERE email_verified_at IS NOT NULL")->fetchColumn();
        $stats['total_books'] = $pdo->query("SELECT COUNT(*) FROM books WHERE is_deleted = 0")->fetchColumn();
        $stats['deleted_books'] = $pdo->query("SELECT COUNT(*) FROM books WHERE is_deleted = 1")->fetchColumn();
        // Compter uniquement les commentaires non supprimés
        $stats['total_comments'] = $pdo->query("SELECT COUNT(*) FROM comments WHERE is_deleted = 0 AND is_admin_deleted = 0")->fetchColumn();
        $stats['deleted_comments'] = $pdo->query("SELECT COUNT(*) FROM comments WHERE is_deleted = 1 OR is_admin_deleted = 1")->fetchColumn();
        
        // Statistiques sur les signalements
        $stats['pending_reports'] = $pdo->query("SELECT COUNT(*) FROM comment_reports WHERE status = 'pending'")->fetchColumn();
        $stats['resolved_reports'] = $pdo->query("SELECT COUNT(*) FROM comment_reports WHERE status = 'resolved'")->fetchColumn();
        $stats['rejected_reports'] = $pdo->query("SELECT COUNT(*) FROM comment_reports WHERE status = 'rejected'")->fetchColumn();
    }
    
} catch (PDOException $e) {
    error_log('Erreur lors de la récupération des données : ' . $e->getMessage());
    $books = [];
    $total_pages = 0;
    $stats = [];
}

$page_title = $is_admin ? 'Tableau de bord administrateur' : 'Mon espace';
require_once 'templates/header.php';
?>

<div class="mb-8">
    <?php if ($is_admin && !empty($stats)): ?>
    <!-- Filtres pour les administrateurs -->
    <div class="flex justify-between items-center mb-6">
        <div class="flex space-x-2">
            <a href="dashboard.php" class="px-4 py-2 rounded-md <?= !$show_deleted ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-700' ?>">
                Livres actifs (<?= $stats['total_books'] ?>)
            </a>
            <a href="?show_deleted=1" class="px-4 py-2 rounded-md <?= $show_deleted ? 'bg-red-600 text-white' : 'bg-gray-200 text-gray-700' ?>">
                Corbeille (<?= $stats['deleted_books'] ?>)
            </a>
        </div>
    </div>
    
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
        <!-- Utilisateurs -->
        <div class="bg-white p-4 rounded-lg shadow-md border-l-4 border-blue-500">
            <div class="flex justify-between items-start">
                <div>
                    <div class="text-gray-500 text-sm font-medium">Utilisateurs</div>
                    <div class="text-2xl font-bold"><?= $stats['total_users'] ?></div>
                    <div class="text-sm text-gray-500">dont <?= $stats['active_users'] ?> actifs</div>
                </div>
                <div class="bg-blue-100 text-blue-800 text-xs font-medium px-2.5 py-1 rounded-full h-6 flex items-center whitespace-nowrap">
                    <?= $stats['verified_users'] > 0 ? round(($stats['verified_users'] / $stats['total_users']) * 100) : 0 ?>% vérifiés
                </div>
            </div>
        </div>

        <!-- Livres -->
        <div class="bg-white p-4 rounded-lg shadow-md border-l-4 border-yellow-500">
            <div class="flex justify-between items-start">
                <div>
                    <div class="text-gray-500 text-sm font-medium">Livres</div>
                    <div class="text-2xl font-bold"><?= $stats['total_books'] ?></div>
                    <div class="text-sm text-gray-500">
                        <?php 
                        $new_books = $pdo->query("SELECT COUNT(*) FROM books WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) AND is_deleted = 0")->fetchColumn();
                        echo $new_books > 0 ? "+$new_books cette semaine" : "Aucun nouveau cette semaine";
                        ?>
                    </div>
                </div>
                <?php if ($stats['deleted_books'] > 0): ?>
                <div class="bg-red-100 text-red-800 text-xs font-medium px-2.5 py-1 rounded-full h-6 flex items-center whitespace-nowrap">
                    <?= $stats['deleted_books'] ?> supprimés
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Commentaires -->
        <div class="bg-white p-4 rounded-lg shadow-md border-l-4 border-purple-500">
            <div class="flex justify-between items-start">
                <div>
                    <div class="text-gray-500 text-sm font-medium">Commentaires</div>
                    <div class="text-2xl font-bold"><?= $stats['total_comments'] ?></div>
                    <div class="text-sm text-gray-500">
                        <?php 
                        $new_comments = $pdo->query("SELECT COUNT(*) FROM comments WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) AND is_deleted = 0 AND is_admin_deleted = 0")->fetchColumn();
                        echo $new_comments > 0 ? "+$new_comments cette semaine" : "Aucun nouveau cette semaine";
                        ?>
                    </div>
                </div>
                <?php if ($stats['deleted_comments'] > 0): ?>
                <div class="bg-red-100 text-red-800 text-xs font-medium px-2.5 py-1 rounded-full h-6 flex items-center whitespace-nowrap">
                    <?= $stats['deleted_comments'] ?> supprimés
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Signalements -->
        <div class="bg-white p-4 rounded-lg shadow-md border-l-4 border-red-500">
            <div class="flex justify-between items-center mb-1">
                <div class="text-gray-500 text-sm font-medium">Signalements</div>
                <div class="flex space-x-1">
                    <span class="px-2 py-1 text-xs rounded-full bg-red-100 text-red-800">
                        <?= $stats['pending_reports'] ?> en attente
                    </span>
                </div>
            </div>
            <div class="grid grid-cols-2 gap-2 mt-2">
                <div class="text-center p-2 bg-green-50 rounded">
                    <div class="text-green-600 font-bold"><?= $stats['resolved_reports'] ?></div>
                    <div class="text-xs text-green-500">Résolus</div>
                </div>
                <div class="text-center p-2 bg-gray-100 rounded">
                    <div class="text-gray-600 font-bold"><?= $stats['rejected_reports'] ?></div>
                    <div class="text-xs text-gray-500">Rejetés</div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <div class="w-full">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold"><?= $is_admin ? 'Tous les livres' : 'Mes livres' ?></h1>
            <a href="add_book.php" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 flex items-center">
                <i class="fas fa-plus mr-2"></i> Ajouter un livre
            </a>
        </div>
    
    <?php if (empty($books)): ?>
        <div class="bg-white p-8 rounded-lg shadow-md text-center">
            <div class="text-gray-400 mb-4">
                <i class="fas fa-book-open text-5xl"></i>
            </div>
            <h2 class="text-xl font-semibold mb-2">Aucun livre ajouté</h2>
            <p class="text-gray-600 mb-4">Commencez par ajouter votre premier livre pour partager vos lectures.</p>
            <a href="add_book.php" class="inline-block bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700">
                Ajouter mon premier livre
            </a>
        </div>
    <?php else: ?>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php foreach ($books as $book): ?>
                <div class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-lg transition-shadow duration-300 relative <?= $book['is_deleted'] ? 'opacity-70' : '' ?>">
                    <?php if ($book['is_deleted']): ?>
                    <div class="absolute top-2 right-2 bg-red-500 text-white text-xs px-2 py-1 rounded-full">
                        Supprimé
                    </div>
                    <?php endif; ?>
                    <div class="h-48 bg-gray-200 overflow-hidden">
                        <?php if (!empty($book['image'])): ?>
                            <img src="uploads/<?= htmlspecialchars($book['image']) ?>" alt="<?= htmlspecialchars($book['title']) ?>" class="w-full h-full object-cover">
                        <?php else: ?>
                            <div class="w-full h-full flex items-center justify-center text-gray-400">
                                <i class="fas fa-book text-5xl"></i>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="p-4">
                        <h3 class="font-semibold text-lg mb-1">
                            <a href="book.php?id=<?= $book['id'] ?>" class="hover:text-blue-600 transition-colors">
                                <?= htmlspecialchars($book['title']) ?>
                            </a>
                        </h3>
                        <?php if ($is_admin): ?>
                            <p class="text-xs text-gray-500 mb-1">
                                Ajouté par <?= htmlspecialchars($book['owner_username'] ?? 'Utilisateur inconnu') ?>
                            </p>
                        <?php endif; ?>
                        <p class="text-gray-600 text-sm mb-2">
                            <i class="fas fa-user-edit mr-1"></i> <?= htmlspecialchars($book['author']) ?>
                        </p>
                        <?php if (!empty($book['genre'])): ?>
                            <span class="inline-block bg-gray-200 rounded-full px-3 py-1 text-xs font-semibold text-gray-700 mb-2">
                                <?= htmlspecialchars($book['genre']) ?>
                            </span>
                        <?php endif; ?>
                        <p class="text-gray-700 text-sm mb-3 line-clamp-3">
                            <?= !empty($book['summary']) ? nl2br(htmlspecialchars(mb_substr($book['summary'], 0, 120) . (mb_strlen($book['summary']) > 120 ? '...' : ''))) : 'Aucun résumé fourni.' ?>
                        </p>
                        <div class="flex justify-between items-center text-sm text-gray-500 border-t border-gray-100 pt-2">
                            <div class="flex items-center space-x-2">
                                <span title="Likes">
                                    <i class="fas fa-heart text-red-500"></i> <?= $book['like_count'] ?? 0 ?>
                                </span>
                                <span title="Commentaires">
                                    <i class="fas fa-comment text-blue-500 ml-2"></i> <?= $book['comment_count'] ?? 0 ?>
                                </span>
                            </div>
                            <div class="flex space-x-2">
                                <a href="edit_book.php?id=<?= $book['id'] ?>" class="text-blue-600 hover:text-blue-800" title="Modifier">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <a href="#" onclick="confirmDelete(<?= $book['id'] ?>)" class="text-red-600 hover:text-red-800" title="Supprimer">
                                    <i class="fas fa-trash"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php if ($total_pages > 1): ?>
        <div class="mt-8 flex justify-center">
            <nav class="inline-flex rounded-md shadow">
                <?php if ($page > 1): ?>
                    <a href="?page=<?= $page - 1 ?>" class="px-3 py-2 rounded-l-md border border-gray-300 bg-white text-gray-500 hover:bg-gray-50">
                        Précédent
                    </a>
                <?php endif; ?>
                
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <a href="?page=<?= $i ?>" class="px-3 py-2 border-t border-b border-gray-300 <?= $i == $page ? 'bg-blue-600 text-white' : 'bg-white text-gray-500 hover:bg-gray-50' ?>">
                        <?= $i ?>
                    </a>
                <?php endfor; ?>
                
                <?php if ($page < $total_pages): ?>
                    <a href="?page=<?= $page + 1 ?>" class="px-3 py-2 rounded-r-md border border-gray-300 bg-white text-gray-500 hover:bg-gray-50">
                        Suivant
                    </a>
                <?php endif; ?>
            </nav>
        </div>
    <?php endif; ?>
</div>

<script>
function confirmDelete(bookId) {
    Swal.fire({
        title: 'Êtes-vous sûr ?',
        text: "Vous ne pourrez pas revenir en arrière !",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Oui, supprimer !',
        cancelButtonText: 'Annuler'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = `delete_book.php?id=${bookId}`;
        }
    });
}
</script>

<?php require_once 'templates/footer.php'; ?>
