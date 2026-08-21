<?php

    /**
     * Ajouter un livre
     * 
     * Cette page permet d'ajouter un nouveau livre à la base de données.
     */

    require_once 'includes/init.php';

    // Vérifier que l'utilisateur est connecté
    require_auth();

    $user = current_user();
    $errors = [];
    $book = [
        'title' => '',
        'author' => '',
        'genre' => '',
        'summary' => '',
        'liked' => '',
        'disliked' => ''
    ];

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Récupérer et nettoyer les données du formulaire
        $book['title'] = trim($_POST['title'] ?? '');
        $book['author'] = trim($_POST['author'] ?? '');
        $book['genre'] = trim($_POST['genre'] ?? '');
        $book['summary'] = trim($_POST['summary'] ?? '');
        $book['liked'] = trim($_POST['liked'] ?? '');
        $book['disliked'] = trim($_POST['disliked'] ?? '');
        
        // Validation
        if (empty($book['title'])) {
            $errors['title'] = 'Le titre est obligatoire';
        } elseif (strlen($book['title']) > 255) {
            $errors['title'] = 'Le titre ne doit pas dépasser 255 caractères';
        }
        
        if (empty($book['author'])) {
            $errors['author'] = 'L\'auteur est obligatoire';
        }
        
        if (!empty($book['genre']) && strlen($book['genre']) > 100) {
            $errors['genre'] = 'Le genre ne doit pas dépasser 100 caractères';
        }
        
        // Traitement de l'image
        $image_path = null;
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            try {
                $image_path = upload_image($_FILES['image']);
            } catch (Exception $e) {
                $errors['image'] = $e->getMessage();
            }
        } else if (isset($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
            $errors['image'] = 'Une erreur est survenue lors du téléchargement de l\'image';
        }
        
        // Si pas d'erreurs, enregistrer le livre
        if (empty($errors)) {
            try {
                $pdo->beginTransaction();
                
                $stmt = $pdo->prepare("
                    INSERT INTO books (user_id, title, author, genre, summary, liked, disliked, image, status)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                
                $stmt->execute([
                    $user['id'],
                    $book['title'],
                    $book['author'],
                    $book['genre'] ?: null,
                    $book['summary'] ?: null,
                    $book['liked'] ?: null,
                    $book['disliked'] ?: null,
                    $image_path,
                    'approved'
                ]);
                
                $book_id = $pdo->lastInsertId();
                $pdo->commit();
                
                set_flash('Votre livre a été ajouté avec succès !');
                redirect('book.php?id=' . $book_id);
                
            } catch (PDOException $e) {
                $pdo->rollBack();
                error_log('Erreur lors de l\'ajout du livre : ' . $e->getMessage());
                $errors['general'] = 'Une erreur est survenue lors de l\'ajout du livre. Veuillez réessayer.';
            }
        }
    }

    $page_title = 'Ajouter un livre';
    require_once 'templates/header.php';
?>

<div class="max-w-4xl mx-auto bg-white p-8 rounded-lg shadow-md">
    <h1 class="text-2xl font-bold mb-6">Ajouter un nouveau livre</h1>
    
    <?php if (!empty($errors['general'])): ?>
        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6" role="alert">
            <p><?= htmlspecialchars($errors['general']) ?></p>
        </div>
    <?php endif; ?>
    
    <form method="POST" action="" enctype="multipart/form-data" class="space-y-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Colonne de gauche -->
            <div class="space-y-6">
                <!-- Titre -->
                <div>
                    <label for="title" class="block text-sm font-medium text-gray-700 mb-1">Titre <span class="text-red-500">*</span></label>
                    <input type="text" id="title" name="title" value="<?= htmlspecialchars($book['title']) ?>" 
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent <?= !empty($errors['title']) ? 'border-red-500' : '' ?>"
                           required>
                    <?php if (!empty($errors['title'])): ?>
                        <p class="mt-1 text-sm text-red-600"><?= htmlspecialchars($errors['title']) ?></p>
                    <?php endif; ?>
                </div>
                
                <!-- Auteur -->
                <div>
                    <label for="author" class="block text-sm font-medium text-gray-700 mb-1">Auteur <span class="text-red-500">*</span></label>
                    <input type="text" id="author" name="author" value="<?= htmlspecialchars($book['author']) ?>" 
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent <?= !empty($errors['author']) ? 'border-red-500' : '' ?>"
                           required>
                    <?php if (!empty($errors['author'])): ?>
                        <p class="mt-1 text-sm text-red-600"><?= htmlspecialchars($errors['author']) ?></p>
                    <?php endif; ?>
                </div>
                
                <!-- Genre -->
                <div>
                    <label for="genre" class="block text-sm font-medium text-gray-700 mb-1">Genre</label>
                    <input type="text" id="genre" name="genre" value="<?= htmlspecialchars($book['genre']) ?>" 
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent <?= !empty($errors['genre']) ? 'border-red-500' : '' ?>">
                    <?php if (!empty($errors['genre'])): ?>
                        <p class="mt-1 text-sm text-red-600"><?= htmlspecialchars($errors['genre']) ?></p>
                    <?php endif; ?>
                </div>
                
                <!-- Image de couverture -->
                <div>
                    <label for="image" class="block text-sm font-medium text-gray-700 mb-1">Image de couverture</label>
                    <div class="mt-1 flex items-center">
                        <input type="file" id="image" name="image" accept="image/*" 
                               class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                    </div>
                    <p class="mt-1 text-xs text-gray-500">Formats acceptés : JPG, PNG, GIF (max 5MB)</p>
                    <?php if (!empty($errors['image'])): ?>
                        <p class="mt-1 text-sm text-red-600"><?= htmlspecialchars($errors['image']) ?></p>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Colonne de droite -->
            <div class="space-y-6">
                <!-- Résumé -->
                <div class="h-full">
                    <label for="summary" class="block text-sm font-medium text-gray-700 mb-1">Résumé</label>
                    <textarea id="summary" name="summary" rows="8" 
                              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent h-full"><?= htmlspecialchars($book['summary']) ?></textarea>
                </div>
            </div>
        </div>
        
        <!-- Avis personnel -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-6">
            <div>
                <label for="liked" class="block text-sm font-medium text-gray-700 mb-1">Ce que j'ai aimé</label>
                <textarea id="liked" name="liked" rows="4" 
                          class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                          placeholder="Points forts, passages marquants..."><?= htmlspecialchars($book['liked']) ?></textarea>
            </div>
            <div>
                <label for="disliked" class="block text-sm font-medium text-gray-700 mb-1">Moins aimé</label>
                <textarea id="disliked" name="disliked" rows="4" 
                          class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                          placeholder="Points faibles, critiques..."><?= htmlspecialchars($book['disliked']) ?></textarea>
            </div>
        </div>
        
        <div class="flex justify-end space-x-4 pt-6 border-t border-gray-200">
            <a href="dashboard.php" class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                Annuler
            </a>
            <button type="submit" class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                Enregistrer le livre
            </button>
        </div>
    </form>
</div>

<script>
// Aperçu de l'image avant upload
const imageInput = document.getElementById('image');
const imagePreview = document.createElement('div');
imagePreview.className = 'mt-2';
imageInput.parentNode.insertBefore(imagePreview, imageInput.nextSibling);

imageInput.addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            // Supprimer l'ancien aperçu s'il existe
            const oldPreview = document.getElementById('image-preview');
            if (oldPreview) {
                oldPreview.remove();
            }
            
            // Créer et afficher le nouvel aperçu
            const img = document.createElement('img');
            img.id = 'image-preview';
            img.src = e.target.result;
            img.alt = 'Aperçu de l\'image';
            img.className = 'mt-2 rounded-md border border-gray-300 max-h-48';
            imagePreview.appendChild(img);
        };
        reader.readAsDataURL(file);
    }
});
</script>

<?php require_once 'templates/footer.php'; ?>
