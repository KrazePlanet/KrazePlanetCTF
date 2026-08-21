<?php
/**
 * Page de détail d'un livre
 * Affiche les informations détaillées d'un livre et ses commentaires
 */

// Inclure le fichier d'initialisation
require_once __DIR__ . '/includes/init.php';

// Vérifier que l'ID du livre est fourni
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    set_flash('Livre non trouvé', 'error');
    redirect('index.php');
}

$book_id = (int)$_GET['id'];
$book = get_book($book_id);

// Vérifier si le livre existe
if (!$book) {
    set_flash('Livre non trouvé', 'error');
    // redirect('index.php');
    redirect('includes/error_pages/404.php');
}

// Vérifier si l'utilisateur a déjà aimé ce livre
$user_has_liked = false;
if (is_logged_in()) {
    $user_has_liked = has_user_liked_book($book_id, $_SESSION['user_id']);
}

// Récupérer les commentaires du livre avec pagination
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$per_page = 10; // Nombre de commentaires par page
$comments_data = get_comments($book_id, $page, $per_page);

// Extraire les commentaires du résultat
$comments = $comments_data['comments'] ?? [];

// Si ce n'est pas un tableau, on initialise un tableau vide
if (!is_array($comments)) {
    $comments = [];
}

// Traitement de l'ajout d'un commentaire
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_comment'])) {
    require_auth();
    
    $content = trim($_POST['content'] ?? '');
    $errors = [];
    
    // Validation
    if (empty($content)) {
        $errors['content'] = 'Le commentaire ne peut pas être vide';
    } elseif (strlen($content) > 1000) {
        $errors['content'] = 'Le commentaire ne doit pas dépasser 1000 caractères';
    }
    
    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO comments (book_id, user_id, content)
                VALUES (?, ?, ?)
            ");
            
            $stmt->execute([
                $book_id,
                $_SESSION['user_id'],
                $content
            ]);
            
            set_flash('Votre commentaire a été ajouté avec succès !');
            redirect('book.php?id=' . $book_id);
            
        } catch (PDOException $e) {
            error_log('Erreur lors de l\'ajout du commentaire : ' . $e->getMessage());
            $errors['general'] = 'Une erreur est survenue lors de l\'ajout du commentaire. Veuillez réessayer.';
        }
    }
}

$page_title = htmlspecialchars($book['title']) . ' - ' . htmlspecialchars($book['author']);
require_once 'templates/header.php';
?>

<div class="max-w-5xl mx-auto">
    <!-- En-tête du livre -->
    <div class="bg-white rounded-lg shadow-md overflow-hidden mb-8">
        <div class="md:flex">
            <!-- Image de couverture -->
            <div class="md:w-1/3 lg:w-1/4 bg-gray-200 flex items-center justify-center p-6">
                <?php if (!empty($book['image'])): ?>
                    <img src="uploads/<?= htmlspecialchars($book['image']) ?>" 
                         alt="<?= htmlspecialchars($book['title']) ?>" 
                         class="max-h-96 w-auto object-cover rounded">
                <?php else: ?>
                    <div class="text-gray-400 text-6xl">
                        <i class="fas fa-book"></i>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Détails du livre -->
            <div class="p-6 md:w-2/3 lg:w-3/4">
                <div class="flex justify-between items-start">
                    <div>
                        <h1 class="text-3xl font-bold text-gray-900 mb-2"><?= htmlspecialchars($book['title']) ?></h1>
                        <!-- Bouton J'aime -->
                        <div class="mt-4 flex items-center space-x-2">
                            <button id="likeButton" 
                                    class="flex items-center space-x-1 px-4 py-2 rounded-full border <?= $user_has_liked ? 'bg-red-100 text-red-600 border-red-200' : 'bg-white text-gray-700 border-gray-300' ?> hover:bg-red-50 transition-colors duration-200"
                                    data-book-id="<?= $book_id ?>"
                                    data-liked="<?= $user_has_liked ? 'true' : 'false' ?>">
                                <i class="fas fa-heart <?= $user_has_liked ? 'fas' : 'far' ?>" id="heartIcon"></i>
                                <span id="likeCount"><?= $book['like_count'] ?? 0 ?></span>
                            </button>
                            <span class="text-sm text-gray-500">
                                <?= ($book['like_count'] ?? 0) === 1 ? '1 personne aime' : ($book['like_count'] ?? 0) . ' personnes aiment' ?> ce livre
                            </span>
                        </div>
                        <p class="text-xl text-gray-600 mb-4"><?= htmlspecialchars($book['author']) ?></p>
                        
                        <?php if (!empty($book['genre'])): ?>
                            <span class="inline-block bg-blue-100 text-blue-800 text-xs font-semibold px-2.5 py-0.5 rounded mb-4">
                                <?= htmlspecialchars($book['genre']) ?>
                            </span>
                        <?php endif; ?>
                    </div>
                    
                    <div class="text-sm text-gray-500">
                        <div class="flex items-center">
                            <i class="fas fa-user mr-1"></i>
                            <span><?= htmlspecialchars($book['username']) ?></span>
                        </div>
                        <div class="flex items-center mt-1">
                            <i class="far fa-calendar-alt mr-1"></i>
                            <span><?= date('d/m/Y', strtotime($book['created_at'])) ?></span>
                        </div>
                    </div>
                </div>
                
                <!-- Actions -->
                <div class="mt-6 flex space-x-3">
                    <?php if (is_logged_in() && ($book['user_id'] === $_SESSION['user_id'] || is_admin())): 
                        $is_admin_edit = is_admin() && $book['user_id'] !== $_SESSION['user_id'];
                        $edit_url = 'edit_book.php?id=' . $book['id'] . ($is_admin_edit ? '&admin_edit=1' : '');
                    ?>
                        <a href="<?= $edit_url ?>" 
                           class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            <i class="fas fa-edit mr-2"></i> <?= $is_admin_edit ? 'Modifier (Admin)' : 'Modifier' ?>
                        </a>
                        <a href="#" 
                           onclick="confirmDelete(<?= $book['id'] ?>, <?= is_admin() && $book['user_id'] !== $_SESSION['user_id'] ? 'true' : 'false' ?>)"
                           class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                            <i class="fas fa-trash mr-2"></i> Supprimer
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Résumé -->
        <?php if (!empty($book['summary'])): ?>
            <div class="border-t border-gray-200 px-6 py-4">
                <h2 class="text-lg font-medium text-gray-900 mb-2">Résumé</h2>
                <div class="prose max-w-none">
                    <?= nl2br(htmlspecialchars($book['summary'])) ?>
                </div>
            </div>
        <?php endif; ?>
        
        <!-- Avis personnel -->
        <?php if (!empty($book['liked']) || !empty($book['disliked'])): ?>
            <div class="border-t border-gray-200 px-6 py-4">
                <h2 class="text-lg font-medium text-gray-900 mb-3">Avis de <?= htmlspecialchars($book['username']) ?></h2>
                
                <div class="grid md:grid-cols-2 gap-6">
                    <?php if (!empty($book['liked'])): ?>
                        <div>
                            <div class="flex items-center text-green-600 mb-1">
                                <i class="fas fa-thumbs-up mr-2"></i>
                                <span class="font-medium">J'ai aimé</span>
                            </div>
                            <div class="bg-green-50 p-3 rounded-md">
                                <?= nl2br(htmlspecialchars($book['liked'])) ?>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($book['disliked'])): ?>
                        <div>
                            <div class="flex items-center text-red-600 mb-1">
                                <i class="fas fa-thumbs-down mr-2"></i>
                                <span class="font-medium">J'ai moins aimé</span>
                            </div>
                            <div class="bg-red-50 p-3 rounded-md">
                                <?= nl2br(htmlspecialchars($book['disliked'])) ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Section commentaires -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-8">
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-xl font-bold text-gray-900">
                Commentaires
                <?php if (!empty($comments_data['pagination']['total_items'])): ?>
                    <span class="ml-2 bg-blue-100 text-blue-800 text-xs font-medium px-2.5 py-0.5 rounded">
                        <?= $comments_data['pagination']['total_items'] ?>
                    </span>
                <?php endif; ?>
            </h2>
        </div>
        
        <!-- Formulaire d'ajout de commentaire -->
        <?php if (is_logged_in()): ?>
            <div class="mb-8">
                <form method="POST" action="" class="space-y-4">
                    <div>
                        <label for="content" class="block text-sm font-medium text-gray-700 mb-1">
                            Ajouter un commentaire
                        </label>
                        <textarea id="content" name="content" rows="3"
                                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                  placeholder="Partagez votre avis sur ce livre..." required></textarea>
                        <?php if (!empty($errors['content'])): ?>
                            <p class="mt-1 text-sm text-red-600"><?= htmlspecialchars($errors['content']) ?></p>
                        <?php endif; ?>
                    </div>
                    <div class="flex justify-end">
                        <button type="submit" name="add_comment"
                                class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            Publier le commentaire
                        </button>
                    </div>
                </form>
            </div>
        <?php else: ?>
            <div class="bg-blue-50 border-l-4 border-blue-400 p-4 mb-6">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <i class="fas fa-info-circle text-blue-400 text-xl"></i>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm text-blue-700">
                            <a href="login.php" class="font-medium text-blue-700 underline hover:text-blue-600">Connectez-vous</a>
                            ou 
                            <a href="signup.php" class="font-medium text-blue-700 underline hover:text-blue-600">créez un compte</a>
                            pour laisser un commentaire.
                        </p>
                    </div>
                </div>
            </div>
        <?php endif; ?>
        
        <!-- Liste des commentaires -->
        <?php if (empty($comments)): ?>
            <div class="text-center py-8">
                <i class="far fa-comment-dots text-4xl text-gray-300 mb-2"></i>
                <p class="text-gray-500">Aucun commentaire pour le moment. Soyez le premier à donner votre avis !</p>
            </div>
        <?php else: ?>
            <div class="space-y-6">
                <?php foreach ($comments as $comment): ?>
                    <div class="border-b border-gray-100 pb-6 last:border-0 last:pb-0">
                        <div class="flex items-start">
                            <div class="flex-shrink-0">
                                <div class="h-10 w-10 rounded-full bg-gray-200 flex items-center justify-center text-gray-500">
                                    <i class="fas fa-user"></i>
                                </div>
                            </div>
                            <div class="ml-3 flex-1 min-w-0">
                                <div class="flex justify-between items-start">
                                    <p class="text-sm font-medium text-gray-900">
                                        <?= htmlspecialchars($comment['username']) ?>
                                    </p>
                                    <p class="text-xs text-gray-500">
                                        <?= date('d/m/Y à H:i', strtotime($comment['created_at'])) ?>
                                    </p>
                                </div>
                                <div class="mt-1 text-sm text-gray-700">
                                    <?php if ((isset($comment['is_deleted']) && $comment['is_deleted']) || (isset($comment['is_admin_deleted']) && $comment['is_admin_deleted'])): ?>
                                        <p class="text-gray-400 italic">
                                            Ce commentaire a été supprimé<?= isset($comment['deleted_by_username']) ? ' par un modérateur' : '' ?>.
                                        </p>
                                    <?php elseif (empty($comment['content'])): ?>
                                        <p class="text-gray-400 italic">Contenu non disponible.</p>
                                    <?php else: ?>
                                        <?= nl2br(htmlspecialchars($comment['content'])) ?>
                                        
                                        <?php if (is_logged_in()): ?>
                                            <div class="mt-2 flex flex-wrap items-center gap-2">
                                                <!-- Bouton Like -->
                                                <button type="button" 
                                                        onclick="toggleLike(<?= $comment['id'] ?>)" 
                                                        class="flex items-center text-gray-500 hover:text-blue-600 transition-colors <?= $comment['is_liked'] ? 'text-blue-600' : '' ?>">
                                                    <i class="far fa-thumbs-up mr-1"></i>
                                                    <span id="like-count-<?= $comment['id'] ?>"><?= $comment['like_count'] ?? 0 ?></span>
                                                </button>
                                                
                                                <?php if ($comment['user_id'] == $_SESSION['user_id'] || is_admin()): ?>
                                                    <?php if ($comment['user_id'] == $_SESSION['user_id']): ?>
                                                        <a href="#" 
                                                            class="inline-flex items-center text-xs text-blue-600 hover:text-blue-800"
                                                            onclick='editComment(<?= $comment['id'] ?>, `<?= addslashes(htmlspecialchars($comment['content'], ENT_QUOTES, 'UTF-8')) ?>`)'>
                                                            <i class="fas fa-edit mr-1"></i> Modifier
                                                        </a>
                                                    <?php endif; ?>
                                                    <a href="#" 
                                                       class="inline-flex items-center text-xs text-red-600 hover:text-red-800"
                                                       onclick="deleteComment(<?= $comment['id'] ?>, <?= is_admin() && $comment['user_id'] != $_SESSION['user_id'] ? 'true' : 'false' ?>)">
                                                        <i class="fas fa-trash-alt mr-1"></i> <?= is_admin() && $comment['user_id'] != $_SESSION['user_id'] ? 'Supprimer (Admin)' : 'Supprimer' ?>
                                                    </a>
                                                <?php endif; ?>
                                                
                                                <?php if ($comment['user_id'] != $_SESSION['user_id']): ?>
                                                    <button type="button" 
                                                            onclick="reportComment(<?= $comment['id'] ?>)" 
                                                            class="text-gray-500 hover:text-red-600 transition-colors">
                                                        <i class="far fa-flag mr-1"></i> Signaler
                                                    </button>
                                                <?php endif; ?>
                                                
                                                <?php if (is_admin() && $comment['report_count'] > 0): ?>
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-800">
                                                        <i class="fas fa-flag mr-1"></i> Signalé (<?= $comment['report_count'] ?>)
                                                        <a href="<?= url('admin/comments/reported.php?comment_id=' . $comment['id']) ?>" 
                                                           class="ml-1 text-red-600 hover:text-red-800"
                                                           title="Voir les signalements">
                                                            <i class="fas fa-external-link-alt text-xs"></i>
                                                        </a>
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
function confirmDelete(bookId, isAdminAction = false) {
    const title = isAdminAction ? 'Supprimer ce livre en tant qu\'administrateur ?' : 'Êtes-vous sûr ?';
    const text = isAdminAction 
        ? 'En tant qu\'administrateur, vous êtes sur le point de supprimer ce livre. Cette action est irréversible.'
        : 'Vous ne pourrez pas revenir en arrière !';
    
    Swal.fire({
        title: title,
        text: text,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: isAdminAction ? '#f59e0b' : '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: isAdminAction ? 'Oui, supprimer (Admin)' : 'Oui, supprimer',
        cancelButtonText: 'Annuler'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = 'delete_book.php?id=' + bookId + (isAdminAction ? '&admin_action=1' : '');
        }
    });
}

function deleteComment(commentId, isAdminAction = false) {
    const title = isAdminAction ? 'Supprimer ce commentaire en tant qu\'administrateur ?' : 'Supprimer ce commentaire ?';
    const text = isAdminAction 
        ? 'En tant qu\'administrateur, vous êtes sur le point de supprimer ce commentaire. Cette action est irréversible.'
        : 'Cette action marquera le commentaire comme supprimé. Vous ne pourrez pas revenir en arrière !';
    
    Swal.fire({
        title: title,
        text: text,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: isAdminAction ? '#f59e0b' : '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: isAdminAction ? 'Oui, supprimer (Admin)' : 'Oui, supprimer',
        cancelButtonText: 'Annuler',
        showLoaderOnConfirm: true,
        preConfirm: () => {
            // Créer un objet FormData pour envoyer les données
            const formData = new FormData();
            formData.append('action', 'delete');
            formData.append('comment_id', commentId);
            if (isAdminAction) {
                formData.append('admin_action', '1');
            }
            
            // Ajouter un token CSRF si disponible
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
            if (csrfToken) {
                formData.append('csrf_token', csrfToken);
            }
            
            return fetch('includes/comment_actions.php', {
                method: 'POST',
                body: new URLSearchParams(formData),
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Erreur réseau');
                }
                return response.json();
            })
            .then(data => {
                if (!data.success) {
                    throw new Error(data.message || 'Erreur lors de la suppression du commentaire');
                }
                return data;
            });
        },
        allowOutsideClick: () => !Swal.isLoading()
    })
    .then((result) => {
        if (result.isConfirmed) {
            // Afficher un message de succès
            Swal.fire({
                title: 'Succès !',
                text: result.value.message || 'Le commentaire a été supprimé avec succès.',
                icon: 'success',
                timer: 1500,
                showConfirmButton: false
            }).then(() => {
                // Recharger la page après un court délai
                window.location.reload();
            });
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
        Swal.fire({
            title: 'Erreur',
            text: error.message || 'Une erreur est survenue lors de la suppression du commentaire.',
            icon: 'error'
        });
    });
}

function editComment(commentId, currentContent) {
    Swal.fire({
        title: 'Modifier le commentaire',
        html: `
            <div class="text-left">
                <p class="text-sm text-gray-600 mb-3">Modifiez votre commentaire ci-dessous :</p>
                <textarea 
                    id="editCommentTextarea" 
                    class="w-full p-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" 
                    rows="5" 
                    style="min-height: 120px;"
                    placeholder="Écrivez votre commentaire ici..."
                    required
                    minlength="5"
                    maxlength="1000"
                >${currentContent.replace(/</g, '&lt;').replace(/>/g, '&gt;')}</textarea>
                <div class="text-xs text-gray-500 mt-1 text-right">
                    <span id="charCount">${currentContent.length}</span>/1000 caractères
                </div>
            </div>
        `,
        showCancelButton: true,
        confirmButtonText: 'Enregistrer',
        cancelButtonText: 'Annuler',
        showLoaderOnConfirm: true,
        preConfirm: () => {
            const content = document.getElementById('editCommentTextarea').value.trim();
            
            // Validation côté client
            if (!content) {
                throw new Error('Le commentaire ne peut pas être vide');
            }
            if (content.length < 5) {
                throw new Error('Le commentaire doit contenir au moins 5 caractères');
            }
            if (content.length > 1000) {
                throw new Error('Le commentaire ne doit pas dépasser 1000 caractères');
            }
            
            // Créer un objet FormData pour envoyer les données
            const formData = new FormData();
            formData.append('action', 'update');
            formData.append('comment_id', commentId);
            formData.append('content', content);
            
            // Ajouter un token CSRF si disponible
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
            if (csrfToken) {
                formData.append('csrf_token', csrfToken);
            }
            
            return fetch('includes/comment_actions.php', {
                method: 'POST',
                body: new URLSearchParams(formData),
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Erreur réseau');
                }
                return response.json();
            })
            .then(data => {
                if (!data.success) {
                    throw new Error(data.message || 'Erreur lors de la mise à jour du commentaire');
                }
                return data;
            });
        },
        allowOutsideClick: () => !Swal.isLoading(),
        didOpen: () => {
            // Gestion du compteur de caractères
            const textarea = document.getElementById('editCommentTextarea');
            const charCount = document.getElementById('charCount');
            
            if (textarea && charCount) {
                textarea.addEventListener('input', function() {
                    const remaining = this.value.length;
                    charCount.textContent = remaining;
                    
                    // Changer la couleur si on approche de la limite
                    if (remaining > 900) {
                        charCount.classList.add('text-red-600', 'font-semibold');
                    } else {
                        charCount.classList.remove('text-red-600', 'font-semibold');
                    }
                });
            }
        }
    })
    .then((result) => {
        if (result.isConfirmed) {
            // Afficher un message de succès
            Swal.fire({
                title: 'Succès !',
                text: result.value.message || 'Votre commentaire a été mis à jour avec succès.',
                icon: 'success',
                timer: 1500,
                showConfirmButton: false
            }).then(() => {
                // Recharger la page après un court délai
                window.location.reload();
            });
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
        // Ne pas afficher d'erreur si l'utilisateur a annulé
        if (error === 'cancel' || error === 'backdrop' || error === 'close' || error === 'esc') {
            return;
        }
        
        Swal.fire({
            title: 'Erreur',
            html: error.message || 'Une erreur est survenue lors de la mise à jour du commentaire.',
            icon: 'error',
            confirmButtonText: 'OK'
        });
    });
}

/**
 * Fonction pour signaler un commentaire inapproprié
 * @param {number} commentId - ID du commentaire à signaler
 */
/**
 * Fonction pour aimer ou ne plus aimer un commentaire
 * @param {number} commentId - ID du commentaire à aimer/ne plus aimer
 */
async function toggleLike(commentId) {
    try {
        const response = await fetch('api/toggle_comment_like.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `comment_id=${commentId}`
        });
        
        const result = await response.json();
        
        if (result.success) {
            // Mettre à jour l'interface utilisateur
            const likeButton = document.querySelector(`button[onclick="toggleLike(${commentId})"]`);
            const likeCount = document.getElementById(`like-count-${commentId}`);
            
            if (result.liked) {
                likeButton.classList.add('text-blue-600');
                likeButton.classList.remove('text-gray-500');
            } else {
                likeButton.classList.remove('text-blue-600');
                likeButton.classList.add('text-gray-500');
            }
            
            if (likeCount) {
                likeCount.textContent = result.like_count;
            }
        } else {
            // Afficher un message d'erreur
            Swal.fire({
                title: 'Erreur',
                text: result.message || 'Une erreur est survenue',
                icon: 'error',
                confirmButtonText: 'OK'
            });
        }
    } catch (error) {
        console.error('Erreur lors de la mise à jour du like:', error);
        Swal.fire({
            title: 'Erreur',
            text: 'Une erreur est survenue lors de la mise à jour du like',
            icon: 'error',
            confirmButtonText: 'OK'
        });
    }
}

/**
 * Fonction pour signaler un commentaire inapproprié
 * @param {number} commentId - ID du commentaire à signaler
 */
// Gestion des likes de livres
function toggleBookLike(button) {
    if (!<?= is_logged_in() ? 'true' : 'false' ?>) {
        window.location.href = '<?= url('login.php') ?>?redirect=' + encodeURIComponent(window.location.pathname + window.location.search);
        return;
    }

    const bookId = button.dataset.bookId;
    const isLiked = button.dataset.liked === 'true';
    const heartIcon = button.querySelector('i');
    const likeCount = button.querySelector('#likeCount');
    
    // Animation visuelle immédiate
    button.disabled = true;
    button.classList.remove('bg-white', 'border-gray-300', 'text-gray-700', 'bg-red-100', 'border-red-200', 'text-red-600');
    button.classList.add('bg-gray-100', 'border-gray-200');
    
    // Envoyer la requête AJAX
    fetch('<?= url('api/toggle_book_like.php') ?>', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            book_id: bookId
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Mettre à jour l'interface
            button.dataset.liked = data.liked;
            likeCount.textContent = data.like_count;
            
            if (data.liked) {
                button.classList.add('bg-red-100', 'border-red-200', 'text-red-600');
                heartIcon.classList.remove('far');
                heartIcon.classList.add('fas');
            } else {
                button.classList.add('bg-white', 'border-gray-300', 'text-gray-700');
                heartIcon.classList.remove('fas');
                heartIcon.classList.add('far');
            }
            
            // Mettre à jour le texte du compteur
            const likeText = document.querySelector('#likeButton + span');
            if (likeText) {
                likeText.textContent = data.like_count === 1 ? 
                    '1 personne aime ce livre' : 
                    data.like_count + ' personnes aiment ce livre';
            }
        } else {
            // Afficher une erreur
            alert(data.error || 'Une erreur est survenue');
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
        alert('Une erreur est survenue lors de la mise à jour du like');
    })
    .finally(() => {
        button.disabled = false;
        button.classList.remove('bg-gray-100', 'border-gray-200');
    });
}

// Ajouter l'événement au bouton like
document.addEventListener('DOMContentLoaded', function() {
    const likeButton = document.getElementById('likeButton');
    if (likeButton) {
        likeButton.addEventListener('click', function() {
            toggleBookLike(this);
        });
    }
});

function reportComment(commentId) {
    Swal.fire({
        title: 'Signaler ce commentaire',
        html: `
            <div class="text-left">
                <p class="text-sm text-gray-600 mb-3">Pourquoi signalez-vous ce commentaire ?</p>
                <p class="text-xs text-gray-500 mb-2">Veuillez fournir une raison détaillée (10 à 500 caractères) :</p>
                <textarea 
                    id="reportReasonTextarea" 
                    class="w-full p-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" 
                    rows="4" 
                    style="min-height: 100px;"
                    placeholder="Décrivez la raison de votre signalement..."
                    required
                    minlength="10"
                    maxlength="500"
                ></textarea>
                <div class="text-xs text-gray-500 mt-1 text-right">
                    <span id="reportCharCount">0</span>/500 caractères
                </div>
            </div>
        `,
        showCancelButton: true,
        confirmButtonText: 'Signaler',
        cancelButtonText: 'Annuler',
        showLoaderOnConfirm: true,
        preConfirm: () => {
            const reason = document.getElementById('reportReasonTextarea').value.trim();
            
            // Validation côté client
            if (!reason) {
                throw new Error('Veuillez indiquer la raison du signalement');
            }
            if (reason.length < 10) {
                throw new Error('Veuillez fournir une raison plus détaillée (au moins 10 caractères)');
            }
            if (reason.length > 500) {
                throw new Error('La raison ne doit pas dépasser 500 caractères');
            }
            
            // Créer un objet FormData pour envoyer les données
            const formData = new FormData();
            formData.append('action', 'report');
            formData.append('comment_id', commentId);
            formData.append('reason', reason);
            
            // Ajouter un token CSRF si disponible
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
            if (csrfToken) {
                formData.append('csrf_token', csrfToken);
            }
            
            return fetch('includes/comment_actions.php', {
                method: 'POST',
                body: new URLSearchParams(formData),
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Erreur réseau');
                }
                return response.json();
            })
            .then(data => {
                if (!data.success) {
                    throw new Error(data.message || 'Erreur lors du signalement du commentaire');
                }
                return data;
            });
        },
        allowOutsideClick: () => !Swal.isLoading(),
        didOpen: () => {
            // Gestion du compteur de caractères
            const textarea = document.getElementById('reportReasonTextarea');
            const charCount = document.getElementById('reportCharCount');
            
            if (textarea && charCount) {
                textarea.addEventListener('input', function() {
                    const count = this.value.length;
                    charCount.textContent = count;
                    
                    // Changer la couleur si on approche de la limite
                    if (count < 10) {
                        charCount.className = 'text-red-600 font-semibold';
                    } else if (count > 400) {
                        charCount.className = 'text-orange-500 font-semibold';
                    } else {
                        charCount.className = 'text-gray-500';
                    }
                });
                
                // Déclencher l'événement input pour mettre à jour le compteur
                textarea.dispatchEvent(new Event('input'));
            }
        }
    })
    .then((result) => {
        if (result.isConfirmed) {
            // Mettre à jour l'interface utilisateur
            const reportButton = document.querySelector(`button[onclick="reportComment(${commentId})"]`);
            if (reportButton) {
                reportButton.disabled = true;
                reportButton.innerHTML = '<i class="fas fa-flag-checkered mr-1"></i> Signalé';
                reportButton.classList.remove('text-orange-600', 'hover:text-orange-800');
                reportButton.classList.add('text-gray-500', 'cursor-default');
            }
            
            // Afficher un message de succès
            return Swal.fire({
                title: 'Signalement envoyé',
                html: result.value.message || 'Merci pour votre signalement. Notre équipe va examiner ce commentaire.',
                icon: 'success',
                confirmButtonText: 'OK',
                timer: 3000,
                timerProgressBar: true
            });
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
        // Ne pas afficher d'erreur si l'utilisateur a annulé
        if (error === 'cancel' || error === 'backdrop' || error === 'close' || error === 'esc') {
            return;
        }
        
        Swal.fire({
            title: 'Erreur',
            html: error.message || 'Une erreur est survenue lors du signalement du commentaire.',
            icon: 'error',
            confirmButtonText: 'OK'
        });
    });
}
</script>

<?php require_once 'templates/footer.php'; ?>
