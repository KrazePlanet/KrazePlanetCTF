<?php

/**
 * Modifier un livre
 * 
 * Cette page permet de modifier un livre.
 */

require_once 'includes/init.php';

// Vérifier que l'utilisateur est connecté
require_auth();

// Vérifier que l'ID du livre est fourni
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    set_flash('Livre non trouvé', 'error');
    // redirect('dashboard.php');
    redirect('includes/error_pages/404.php');
}

$book_id = (int)$_GET['id'];
$user_id = $_SESSION['user_id'];

// Vérifier si l'utilisateur est admin
$is_admin = is_admin();
$is_admin_edit = $is_admin && isset($_GET['admin_edit']) && $_GET['admin_edit'] == 1;

// Vérifier que le livre existe et que l'utilisateur a les droits
$book_query = $is_admin_edit 
    ? "SELECT * FROM books WHERE id = ?" 
    : "SELECT * FROM books WHERE id = ? AND user_id = ?";

$book_stmt = $pdo->prepare($book_query);
$book_stmt->execute($is_admin_edit ? [$book_id] : [$book_id, $user_id]);
$book = $book_stmt->fetch();

if (!$book) {
    set_flash('Livre non trouvé ou accès non autorisé', 'error');
    redirect('includes/error_pages/404.php');
}

// Vérifier les permissions si ce n'est pas une édition admin
if (!$is_admin_edit && $book['user_id'] != $user_id) {
    set_flash('Vous n\'êtes pas autorisé à modifier ce livre', 'error');
    redirect('index.php');
}

$errors = [];
$success = false;

// Traitement du formulaire de mise à jour
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Récupérer et nettoyer les données du formulaire
    $title = trim($_POST['title'] ?? '');
    $author = trim($_POST['author'] ?? '');
    $genre = trim($_POST['genre'] ?? '');
    $summary = trim($_POST['summary'] ?? '');
    $liked = trim($_POST['liked'] ?? '');
    $disliked = trim($_POST['disliked'] ?? '');
    
    // Validation
    if (empty($title)) {
        $errors['title'] = 'Le titre est obligatoire';
    } elseif (strlen($title) > 255) {
        $errors['title'] = 'Le titre ne doit pas dépasser 255 caractères';
    }
    
    if (empty($author)) {
        $errors['author'] = 'L\'auteur est obligatoire';
    }
    
    if (!empty($genre) && strlen($genre) > 100) {
        $errors['genre'] = 'Le genre ne doit pas dépasser 100 caractères';
    }
    
    // Traitement de l'image
    $image_path = $book['image'];
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        try {
            // Supprimer l'ancienne image si elle existe
            if (!empty($book['image']) && file_exists('uploads/' . $book['image'])) {
                unlink('uploads/' . $book['image']);
            }
            
            // Télécharger la nouvelle image
            $image_path = upload_image($_FILES['image']);
        } catch (Exception $e) {
            $errors['image'] = $e->getMessage();
        }
    } else if (isset($_POST['remove_image']) && $book['image']) {
        // Supprimer l'image si la case est cochée
        if (file_exists('uploads/' . $book['image'])) {
            unlink('uploads/' . $book['image']);
        }
        $image_path = null;
    }
    
    // Si pas d'erreurs, mettre à jour le livre
    if (empty($errors)) {
        try {
            $pdo->beginTransaction();
            
            // Préparer les champs de mise à jour
            $update_fields = [
                'title = ?', 'author = ?', 'genre = ?', 'summary = ?', 
                'liked = ?', 'disliked = ?', 'updated_at = NOW()'
            ];
            
            // Ajouter l'image si elle a été modifiée
            $params = [$title, $author, $genre, $summary, $liked, $disliked];
            
            if ($image_path !== $book['image']) {
                $update_fields[] = 'image = ?';
                $params[] = $image_path;
            }
            
            // Construire la requête de mise à jour
            $update_query = "UPDATE books SET " . implode(', ', $update_fields) . " ";
            $update_query .= $is_admin_edit 
                ? "WHERE id = ?" 
                : "WHERE id = ? AND user_id = ?";
            
            // Ajouter les paramètres de condition
            $params[] = $book_id;
            if (!$is_admin_edit) {
                $params[] = $user_id;
            }
            
            // Exécuter la mise à jour
            $stmt = $pdo->prepare($update_query);
            $stmt->execute($params);
            
            // Valider la transaction
            $pdo->commit();
            
            set_flash('Le livre a été mis à jour avec succès !');
            redirect('book.php?id=' . $book_id);
            
        } catch (PDOException $e) {
            // Annuler la transaction en cas d'erreur
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            
            // Journaliser l'erreur
            error_log('Erreur lors de la mise à jour du livre #' . $book_id . ': ' . $e->getMessage());
            
            // Afficher un message d'erreur approprié
            if (strpos($e->getMessage(), 'SQLSTATE[23000]') !== false) {
                $errors[] = 'Une erreur est survenue. Vérifiez que les données saisies sont valides.';
            } else {
                $errors[] = 'Une erreur est survenue lors de la mise à jour du livre. Veuillez réessayer.';
            }
        }
    }
}

$page_title = 'Modifier le livre : ' . htmlspecialchars($book['title']);
require_once 'templates/header.php';
?>

<div class="max-w-4xl mx-auto bg-white p-8 rounded-lg shadow-md">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold">Modifier le livre</h1>
        <a href="book.php?id=<?= $book_id ?>" class="text-blue-600 hover:underline">
            <i class="fas fa-arrow-left mr-1"></i> Retour au livre
        </a>
    </div>
    
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
                    <input type="text" id="genre" name="genre" value="<?= htmlspecialchars($book['genre'] ?? '') ?>" 
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent <?= !empty($errors['genre']) ? 'border-red-500' : '' ?>">
                    <?php if (!empty($errors['genre'])): ?>
                        <p class="mt-1 text-sm text-red-600"><?= htmlspecialchars($errors['genre']) ?></p>
                    <?php endif; ?>
                </div>
                
                <!-- Image de couverture -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Image de couverture</label>
                    
                    <?php if (!empty($book['image'])): ?>
                        <div class="mb-3">
                            <img src="uploads/<?= htmlspecialchars($book['image']) ?>" 
                                 alt="Couverture actuelle" 
                                 class="h-48 w-auto object-cover rounded border border-gray-300">
                            <div class="mt-2 flex items-center">
                                <input id="remove_image" name="remove_image" type="checkbox" 
                                       class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                                <label for="remove_image" class="ml-2 block text-sm text-gray-700">
                                    Supprimer l'image
                                </label>
                            </div>
                        </div>
                    <?php endif; ?>
                    
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
                              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent h-full"><?= htmlspecialchars($book['summary'] ?? '') ?></textarea>
                </div>
            </div>
        </div>
        
        <!-- Avis personnel -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-6">
            <div>
                <label for="liked" class="block text-sm font-medium text-gray-700 mb-1">Ce que j'ai aimé</label>
                <textarea id="liked" name="liked" rows="4" 
                          class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                          placeholder="Points forts, passages marquants..."><?= htmlspecialchars($book['liked'] ?? '') ?></textarea>
            </div>
            <div>
                <label for="disliked" class="block text-sm font-medium text-gray-700 mb-1">Moins aimé</label>
                <textarea id="disliked" name="disliked" rows="4" 
                          class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                          placeholder="Points faibles, critiques..."><?= htmlspecialchars($book['disliked'] ?? '') ?></textarea>
            </div>
        </div>
        
        <div class="flex justify-end space-x-4 pt-6 border-t border-gray-200">
            <a href="book.php?id=<?= $book_id ?>" class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                Annuler
            </a>
            <button type="submit" class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                Enregistrer les modifications
            </button>
        </div>
    </form>
</div>

<script>
// Aperçu de l'image avant upload
const imageInput = document.getElementById('image');
if (imageInput) {
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
                
                // Insérer avant la case à cocher de suppression si elle existe
                const removeCheckbox = document.getElementById('remove_image');
                if (removeCheckbox) {
                    removeCheckbox.parentNode.parentNode.insertBefore(img, removeCheckbox.parentNode);
                } else {
                    imagePreview.appendChild(img);
                }
            };
            reader.readAsDataURL(file);
        }
    });
}

// Gérer la suppression de l'image
const removeImageCheckbox = document.getElementById('remove_image');
if (removeImageCheckbox) {
    removeImageCheckbox.addEventListener('change', function() {
        const currentImage = document.querySelector('img[alt="Couverture actuelle"]');
        if (this.checked && currentImage) {
            currentImage.style.opacity = '0.5';
        } else if (currentImage) {
            currentImage.style.opacity = '1';
        }
    });
}
</script>

<?php require_once 'templates/footer.php'; ?>
