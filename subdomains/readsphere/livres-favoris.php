<?php

/**
 * Page des livres aimés
 */

require_once __DIR__ . '/includes/init.php';    

// Vérifier si l'utilisateur est connecté
if (!is_logged_in()) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 12;

// Récupérer les livres aimés
try {
    global $pdo;
    
    // Compter le nombre total de livres aimés
    $stmt = $pdo->prepare("
        SELECT COUNT(*) 
        FROM book_likes bl
        JOIN books b ON bl.book_id = b.id
        WHERE bl.user_id = ? AND (b.is_deleted = 0 OR b.is_deleted IS NULL)
    ");
    $stmt->execute([$user_id]);
    $total_books = $stmt->fetchColumn();
    
    // Calculer la pagination
    $total_pages = ceil($total_books / $per_page);
    $offset = ($page - 1) * $per_page;
    
    // Récupérer les livres aimés avec pagination
    $stmt = $pdo->prepare("
        SELECT b.*, 
               u.username as author_username,
               (SELECT COUNT(*) FROM comments c WHERE c.book_id = b.id AND (c.is_deleted = 0 OR c.is_deleted IS NULL)) as comment_count,
               (SELECT COUNT(*) FROM book_likes bl2 WHERE bl2.book_id = b.id) as like_count
        FROM book_likes bl
        JOIN books b ON bl.book_id = b.id
        LEFT JOIN users u ON b.user_id = u.id
        WHERE bl.user_id = ? AND (b.is_deleted = 0 OR b.is_deleted IS NULL)
        ORDER BY bl.created_at DESC
        LIMIT ? OFFSET ?
    ");
    $stmt->execute([$user_id, $per_page, $offset]);
    $books = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    $error = $e->getMessage();
}

$page_title = "Mes livres aimés";
include_once __DIR__ . '/templates/header.php';
?>

<div class="container mx-auto py-8 px-4">
    <h1 class="text-3xl font-bold mb-6">Mes livres aimés</h1>
    
    <?php if (isset($error)): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
            <span class="block sm:inline"><?= htmlspecialchars($error) ?></span>
        </div>
    <?php elseif (empty($books)): ?>
        <div class="bg-white p-6 rounded-lg shadow-md text-center">
            <p class="text-gray-600">Vous n'avez pas encore aimé de livre.</p>
            <a href="index.php" class="mt-4 inline-block bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 transition">
                Découvrir des livres
            </a>
        </div>
    <?php else: ?>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
            <?php foreach ($books as $book): ?>
                <div class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-lg transition-shadow duration-300">
                    <div class="h-48 bg-gray-200 overflow-hidden">
                        <?php if (!empty($book['cover_image'])): ?>
                            <img src="<?= htmlspecialchars($book['cover_image']) ?>" 
                                 alt="<?= htmlspecialchars($book['title']) ?>" 
                                 class="w-full h-full object-cover">
                        <?php else: ?>
                            <div class="w-full h-full flex items-center justify-center bg-gray-100">
                                <i class="fas fa-book text-6xl text-gray-400"></i>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="p-4">
                        <h2 class="text-xl font-semibold mb-2">
                            <a href="book.php?id=<?= $book['id'] ?>" class="hover:text-blue-600 transition">
                                <?= htmlspecialchars($book['title']) ?>
                            </a>
                        </h2>
                        <p class="text-gray-600 text-sm mb-2">
                            Par <a href="profile.php?id=<?= $book['user_id'] ?>" class="text-blue-600 hover:underline">
                                <?= htmlspecialchars($book['author_username']) ?>
                            </a>
                        </p>
                        <div class="flex items-center justify-between mt-4 text-sm text-gray-500">
                            <span class="flex items-center">
                                <i class="fas fa-comment mr-1"></i> <?= $book['comment_count'] ?>
                            </span>
                            <span class="flex items-center <?= has_user_liked_book($book['id'], $user_id) ? 'text-red-500' : 'text-gray-500' ?>">
                                <i class="fas fa-heart mr-1"></i> <?= $book['like_count'] ?>
                            </span>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
            <div class="mt-8 flex justify-center">
                <nav class="flex items-center space-x-1">
                    <?php if ($page > 1): ?>
                        <a href="?page=<?= $page - 1 ?>" class="px-3 py-2 rounded-l-md border border-gray-300 bg-white text-gray-500 hover:bg-gray-50">
                            Précédent
                        </a>
                    <?php endif; ?>
                    
                    <?php 
                    // Afficher un nombre limité de pages autour de la page courante
                    $start = max(1, $page - 2);
                    $end = min($total_pages, $page + 2);
                    
                    if ($start > 1) {
                        echo '<span class="px-3 py-2">...</span>';
                    }
                    
                    for ($i = $start; $i <= $end; $i++): ?>
                        <a href="?page=<?= $i ?>" 
                           class="px-3 py-2 border border-gray-300 <?= $i === $page ? 'bg-blue-600 text-white' : 'bg-white text-gray-500 hover:bg-gray-50' ?>">
                            <?= $i ?>
                        </a>
                    <?php endfor; ?>
                    
                    <?php if ($end < $total_pages): ?>
                        <span class="px-3 py-2">...</span>
                    <?php endif; ?>
                    
                    <?php if ($page < $total_pages): ?>
                        <a href="?page=<?= $page + 1 ?>" class="px-3 py-2 rounded-r-md border border-gray-300 bg-white text-gray-500 hover:bg-gray-50">
                            Suivant
                        </a>
                    <?php endif; ?>
                </nav>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<?php include_once __DIR__ . '/templates/footer.php'; ?>