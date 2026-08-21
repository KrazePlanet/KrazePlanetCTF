<?php

/**
 * Page des livres populaires
 */

require_once 'includes/init.php';   

$page_title = 'Livres populaires';
require_once 'templates/header.php';

// Récupérer les livres populaires
$popular_books = get_popular_books(24); // 24 livres les plus populaires
?>

<div class="container mx-auto px-4 py-8">
    <h1 class="text-3xl font-bold text-gray-900 mb-8">Livres populaires</h1>
    
    <?php if (empty($popular_books)): ?>
        <div class="bg-white rounded-lg shadow p-6 text-center">
            <p class="text-gray-500">Aucun livre populaire pour le moment.</p>
        </div>
    <?php else: ?>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
            <?php foreach ($popular_books as $book): ?>
                <div class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-lg transition-shadow duration-300">
                    <a href="<?= url('book.php?id=' . $book['id']) ?>" class="block">
                        <div class="h-48 bg-gray-200 overflow-hidden">
                            <?php if (!empty($book['image'])): ?>
                                <img src="uploads/<?= htmlspecialchars($book['image']) ?>" 
                                     alt="<?= htmlspecialchars($book['title']) ?>" 
                                     class="w-full h-full object-cover">
                            <?php else: ?>
                                <div class="w-full h-full flex items-center justify-center text-gray-400">
                                    <i class="fas fa-book text-6xl"></i>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="p-4">
                            <h3 class="font-bold text-lg text-gray-900 mb-1 truncate">
                                <?= htmlspecialchars($book['title']) ?>
                            </h3>
                            <p class="text-gray-600 text-sm mb-2">
                                <?= htmlspecialchars($book['author_name'] ?? 'Auteur inconnu') ?>
                            </p>
                            <div class="flex items-center text-sm text-gray-500">
                                <span class="flex items-center mr-4">
                                    <i class="fas fa-heart text-red-500 mr-1"></i>
                                    <?= $book['like_count'] ?? 0 ?>
                                </span>
                                <span class="flex items-center">
                                    <i class="fas fa-comment text-blue-500 mr-1"></i>
                                    <?= $book['comment_count'] ?? 0 ?>
                                </span>
                            </div>
                        </div>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once 'templates/footer.php'; ?>