
<?php

/*
 * Gestion des livres supprimés
 * 
 * Affiche les livres supprimés et permet de les restaurer
 */

 require_once __DIR__ . '/../includes/init.php';

// Vérifier les droits d'administration
if (!is_admin()) {
    set_flash('Accès non autorisé.', 'error');
    redirect('../index.php');
}

$page_title = 'Livres supprimés';
$current_page = 'deleted_books';

// Récupérer les livres supprimés avec pagination
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$per_page = 10;
$books_data = get_books($page, $per_page, '', '', null, true); // true pour inclure les supprimés

// Les livres sont déjà filtrés côté serveur
$deleted_books = $books_data['books'];

// Traitement de la restauration d'un livre
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['restore_book'])) {
    $book_id = (int)$_POST['book_id'];
    if (restore_book($book_id)) {
        set_flash('Le livre a été restauré avec succès.', 'success');
        redirect('deleted_books.php');
    }
}

require_once '../templates/header.php';
?>

<div class="container mx-auto px-4 py-8">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-900">Livres supprimés</h1>
        <a href="index.php" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded">
            <i class="fas fa-arrow-left mr-2"></i> Retour au tableau de bord
        </a>
    </div>

    <?php if (empty($deleted_books)): ?>
        <div class="bg-white rounded-lg shadow p-6 text-center">
            <p class="text-gray-500">Aucun livre supprimé pour le moment.</p>
        </div>
    <?php else: ?>
        <div class="bg-white shadow overflow-hidden sm:rounded-md">
            <ul class="divide-y divide-gray-200">
                <?php foreach ($deleted_books as $book): ?>
                    <li class="p-4 hover:bg-gray-50">
                        <div class="flex items-center justify-between">
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-blue-600 truncate">
                                    <?= htmlspecialchars($book['title']) ?>
                                </p>
                                <p class="text-sm text-gray-500">
                                    Par <?= htmlspecialchars($book['author']) ?>
                                    <?php if (!empty($book['genre'])): ?>
                                        <span class="mx-1">•</span>
                                        <span class="text-gray-600"><?= htmlspecialchars($book['genre']) ?></span>
                                    <?php endif; ?>
                                </p>
                                <p class="text-xs text-gray-400 mt-1">
                                    Supprimé le <?= format_date($book['deleted_at']) ?>
                                    <?php if (!empty($book['deleted_by'])): ?>
                                        par <?= htmlspecialchars(get_username($book['deleted_by'])) ?>
                                    <?php endif; ?>
                                </p>
                            </div>
                            <div class="ml-4 flex-shrink-0">
                                <form method="POST" class="inline" onsubmit="return confirm('Êtes-vous sûr de vouloir restaurer ce livre ?');">
                                    <input type="hidden" name="book_id" value="<?= $book['id'] ?>">
                                    <button type="submit" name="restore_book" class="text-green-600 hover:text-green-900">
                                        <i class="fas fa-undo mr-1"></i> Restaurer
                                    </button>
                                </form>
                            </div>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>

        <!-- Pagination -->
        <?php if ($books_data['pagination']['total_pages'] > 1): ?>
            <div class="mt-6 flex justify-center">
                <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                    <?php if ($page > 1): ?>
                        <a href="?page=<?= $page - 1 ?>" class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                            <span class="sr-only">Précédent</span>
                            <i class="fas fa-chevron-left"></i>
                        </a>
                    <?php endif; ?>
                    
                    <?php for ($i = 1; $i <= $books_data['pagination']['total_pages']; $i++): ?>
                        <a href="?page=<?= $i ?>" class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium <?= $i == $page ? 'text-blue-600 bg-blue-50' : 'text-gray-700 hover:bg-gray-50' ?>">
                            <?= $i ?>
                        </a>
                    <?php endfor; ?>
                    
                    <?php if ($page < $books_data['pagination']['total_pages']): ?>
                        <a href="?page=<?= $page + 1 ?>" class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                            <span class="sr-only">Suivant</span>
                            <i class="fas fa-chevron-right"></i>
                        </a>
                    <?php endif; ?>
                </nav>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<?php require_once '../templates/footer.php'; ?>
