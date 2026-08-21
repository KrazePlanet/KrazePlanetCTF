
<?php

/*
 * Gestion des commentaires signalés
 * 
 * Affiche les commentaires signalés et permet de les gérer
 */

require_once __DIR__ . '/../includes/init.php';

// Vérifier que l'utilisateur est administrateur
if (!is_admin()) {
    header('Location: ' . url('login.php')); 
    exit;
}

// Paramètres de pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 20;

// Récupérer les commentaires signalés
$reported_comments = get_reported_comments($page, $per_page);
$total_reports = get_reported_comments_count();
$total_pages = ceil($total_reports / $per_page);

// Traitement des actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    // Vérifier le jeton CSRF
    if (!isset($_POST['_token']) || !verify_csrf_token($_POST['_token'])) {
        die('Erreur de sécurité : jeton CSRF invalide.');
    }
    
    $comment_id = (int)($_POST['comment_id'] ?? 0);
    
    if ($comment_id <= 0) {
        $_SESSION['error'] = 'ID de commentaire invalide.';
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }
    
    try {
        switch ($_POST['action']) {
            case 'delete_comment':
                if (delete_comment($comment_id, $_SESSION['user_id'], true)) {
                    $_SESSION['success'] = 'Le commentaire a été supprimé avec succès.';
                } else {
                    throw new Exception('Échec de la suppression du commentaire.');
                }
                break;
                
            case 'resolve_report':
                $report_id = (int)($_POST['report_id'] ?? 0);
                if ($report_id > 0 && resolve_comment_report($report_id, $_SESSION['user_id'])) {
                    $_SESSION['success'] = 'Le signalement a été marqué comme résolu.';
                } else {
                    throw new Exception('Échec du traitement du signalement.');
                }
                break;
                
            case 'reject_report':
                $report_id = (int)($_POST['report_id'] ?? 0);
                if ($report_id > 0 && reject_comment_report($report_id, $_SESSION['user_id'])) {
                    $_SESSION['success'] = 'Le signalement a été rejeté.';
                } else {
                    throw new Exception('Échec du rejet du signalement.');
                }
                break;
        }
        
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    } catch (Exception $e) {
        $_SESSION['error'] = $e->getMessage();
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }
}

// Inclure l'en-tête d'administration
$page_title = 'Gestion des commentaires signalés';

// Chemin vers le header
$headerPath = __DIR__ . '/../templates/header.php'; 

// Vérifier que le fichier existe
if (!file_exists($headerPath) || !is_readable($headerPath)) {
    die('Erreur : Le fichier header.php est introuvable. Vérifiez que le fichier existe bien à l\'emplacement : ' . $headerPath);
}

// Inclure l'en-tête
include $headerPath;
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">
            <i class="bi bi-flag-fill text-danger me-2"></i>
            Commentaires signalés
            <?php if ($total_reports > 0): ?>
                <span class="badge bg-danger ms-2"><?= $total_reports ?></span>
            <?php endif; ?>
        </h1>
        <div>
            <a href="#" class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#helpModal">
                <i class="bi bi-question-circle"></i> Aide
            </a>
        </div>
    </div>
    
    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <?= htmlspecialchars($_SESSION['success']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fermer"></button>
        </div>
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <?= htmlspecialchars($_SESSION['error']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fermer"></button>
        </div>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>
    
    <?php if (empty($reported_comments)): ?>
        <div class="card">
            <div class="card-body text-center py-5">
                <div class="display-4 text-muted mb-3">
                    <i class="bi bi-check-circle"></i>
                </div>
                <h3 class="h4 text-muted">Aucun commentaire signalé</h3>
                <p class="text-muted">Tous les commentaires sont propres !</p>
            </div>
        </div>
    <?php else: ?>
        <div class="card shadow-sm">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 60px;">ID</th>
                            <th>Livre</th>
                            <th>Auteur</th>
                            <th>Contenu</th>
                            <th style="width: 100px;">Signalements</th>
                            <th style="width: 150px;">Dernier signalement</th>
                            <th style="width: 150px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($reported_comments as $comment): 
                            $is_deleted = !empty($comment['is_deleted']);
                            $row_class = $is_deleted ? 'table-secondary text-muted' : '';
                        ?>
                            <tr class="<?= $row_class ?>">
                                <td class="text-muted">#<?= $comment['id'] ?></td>
                                <td>
                                    <a href="<?= url('book.php?id=' . $comment['book_id']) ?>" class="text-decoration-none">
                                        <div class="d-flex align-items-center">
                                            <div class="me-2">
                                                <i class="bi bi-book text-primary"></i>
                                            </div>
                                            <div class="text-truncate" style="max-width: 200px;" data-bs-toggle="tooltip" title="<?= htmlspecialchars($comment['book_title']) ?>">
                                                <?= htmlspecialchars($comment['book_title']) ?>
                                            </div>
                                        </div>
                                    </a>
                                </td>
                                <td>
                                    <a href="<?= url('profile.php?id=' . $comment['user_id']) ?>" class="text-decoration-none">
                                        <div class="d-flex align-items-center">
                                            <div class="me-2">
                                                <i class="bi bi-person-circle text-secondary"></i>
                                            </div>
                                            <span class="text-truncate" style="max-width: 150px;">
                                                <?= htmlspecialchars($comment['author_name']) ?>
                                            </span>
                                        </div>
                                    </a>
                                </td>
                                <td>
                                    <div class="comment-preview text-truncate" style="max-width: 250px;" data-bs-toggle="tooltip" title="<?= htmlspecialchars($comment['content']) ?>">
                                        <?= nl2br(htmlspecialchars(substr($comment['content'], 0, 50))) ?><?= strlen($comment['content']) > 50 ? '...' : '' ?>
                                    </div>
                                    <button type="button" class="btn btn-sm btn-link p-0 text-decoration-none" data-bs-toggle="modal" data-bs-target="#commentModal<?= $comment['id'] ?>">
                                        <small><i class="bi bi-zoom-in me-1"></i>Voir plus</small>
                                    </button>
                                </td>
                                <td>
                                    <span class="badge bg-danger rounded-pill">
                                        <i class="bi bi-flag"></i> <?= $comment['report_count'] ?>
                                    </span>
                                    <?php if (!empty($comment['reports'])): ?>
                                        <button type="button" class="btn btn-sm btn-outline-secondary ms-1" data-bs-toggle="modal" data-bs-target="#reportsModal<?= $comment['id'] ?>">
                                            <i class="bi bi-list-ul"></i>
                                        </button>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="text-muted small" data-bs-toggle="tooltip" title="<?= format_date($comment['last_reported_at'], 'd/m/Y H:i:s') ?>">
                                        <i class="bi bi-clock-history me-1"></i>
                                        <?= time_elapsed_string($comment['last_reported_at']) ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm" role="group">
                                        <a href="<?= url('book.php?id=' . $comment['book_id'] . '#comment-' . $comment['id']) ?>" 
                                           class="btn btn-outline-primary" 
                                           data-bs-toggle="tooltip" 
                                           title="Voir dans le contexte"
                                           target="_blank">
                                            <i class="bi bi-box-arrow-up-right"></i>
                                        </a>
                                        
                                        <?php if (!$is_deleted): ?>
                                            <button type="button" 
                                                    class="btn btn-outline-danger" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#deleteModal<?= $comment['id'] ?>"
                                                    title="Supprimer le commentaire">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                            
                                            <!-- Modal de confirmation de suppression -->
                                            <div class="modal fade" id="deleteModal<?= $comment['id'] ?>" tabindex="-1" aria-hidden="true">
                                                <div class="modal-dialog">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title">Confirmer la suppression</h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <p>Êtes-vous sûr de vouloir supprimer ce commentaire ? Cette action est irréversible.</p>
                                                            <div class="form-check mb-3">
                                                                <input class="form-check-input" type="checkbox" id="blockUser<?= $comment['id'] ?>">
                                                                <label class="form-check-label" for="blockUser<?= $comment['id'] ?>">
                                                                    Bloquer également l'utilisateur
                                                                </label>
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                                                            <form method="post" class="d-inline">
                                                                <?= csrf_field() ?>
                                                                <input type="hidden" name="comment_id" value="<?= $comment['id'] ?>">
                                                                <button type="submit" name="action" value="delete_comment" class="btn btn-danger">
                                                                    <i class="bi bi-trash me-1"></i> Supprimer
                                                                </button>
                                                            </form>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Supprimé</span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                    <button type="button" 
                                            class="btn btn-warning" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#reportsModal<?= $comment['id'] ?>"
                                            title="Voir les signalements">
                                        <i class="bi-flag"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        
                        <!-- Modal pour voir le commentaire complet -->
                        <div class="modal fade" id="commentModal<?= $comment['id'] ?>" tabindex="-1" aria-hidden="true">
                            <div class="modal-dialog modal-lg">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title">Commentaire #<?= $comment['id'] ?></h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                                    </div>
                                    <div class="modal-body">
                                        <div class="mb-3">
                                            <h6>Livre :</h6>
                                            <p>
                                                <a href="<?= url('book.php?id=' . $comment['book_id']) ?>">
                                                    <?= htmlspecialchars($comment['book_title']) ?>
                                                </a>
                                            </p>
                                        </div>
                                        <div class="mb-3">
                                            <h6>Auteur :</h6>
                                            <p>
                                                <a href="<?= url('profile.php?id=' . $comment['user_id']) ?>">
                                                    <?= htmlspecialchars($comment['author_name']) ?>
                                                </a>
                                            </p>
                                        </div>
                                        <div class="mb-3">
                                            <h6>Contenu :</h6>
                                            <div class="p-3 bg-light rounded">
                                                <?= nl2br(htmlspecialchars($comment['content'])) ?>
                                            </div>
                                        </div>
                                        <div class="mb-3">
                                            <h6>Date de publication :</h6>
                                            <p>
                                                <i class="bi bi-calendar3 me-2"></i>
                                                <?php 
                                                $created_at = !empty($comment['created_at']) ? $comment['created_at'] : null;
                                                echo $created_at ? format_date($created_at, 'd/m/Y à H:i') : 'Date inconnue';
                                                ?>
                                            </p>
                                        </div>
                                        <?php if (!empty($comment['last_reported_at'])): ?>
                                        <div class="mb-3">
                                            <h6>Dernier signalement :</h6>
                                            <p class="text-muted small">
                                                <i class="bi bi-clock-history me-2"></i>
                                                <?= format_date($comment['last_reported_at'], 'd/m/Y à H:i') ?>
                                                <span class="badge bg-danger ms-2">
                                                    <i class="bi bi-flag"></i> <?= $comment['report_count'] ?> signalement(s)
                                                </span>
                                            </p>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Modal pour voir les signalements -->
                        <div class="modal fade" id="reportsModal<?= $comment['id'] ?>" tabindex="-1" aria-hidden="true">
                            <div class="modal-dialog modal-lg">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title">Signalements pour le commentaire #<?= $comment['id'] ?></h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                                    </div>
                                    <div class="modal-body">
                                        <?php if (!empty($comment['reports'])): ?>
                                            <div class="list-group">
                                                <?php foreach ($comment['reports'] as $report): ?>
                                                    <div class="list-group-item">
                                                        <div class="d-flex w-100 justify-content-between">
                                                            <h6 class="mb-1">
                                                                <a href="<?= url('profile.php?id=' . $report['user_id']) ?>">
                                                                    <?= htmlspecialchars($report['reporter_name']) ?>
                                                                </a>
                                                            </h6>
                                                            <small class="text-muted">
                                                                <?= format_date($report['created_at'], true) ?>
                                                                <?php if ($report['status'] === 'resolved'): ?>
                                                                    <span class="badge bg-success">Résolu</span>
                                                                <?php elseif ($report['status'] === 'rejected'): ?>
                                                                    <span class="badge bg-danger">Rejeté</span>
                                                                <?php else: ?>
                                                                    <span class="badge bg-warning">En attente</span>
                                                                <?php endif; ?>
                                                            </small>
                                                        </div>
                                                        <p class="mb-1">
                                                            <strong>Raison :</strong> <?= htmlspecialchars($report['reason']) ?>
                                                        </p>
                                                        <?php if (!empty($report['resolved_at'])): ?>
                                                            <small class="text-muted">
                                                                Traité par <?= htmlspecialchars($report['resolved_by_name'] ?? 'Administrateur') ?> 
                                                                le <?= format_date($report['resolved_at'], true) ?>
                                                            </small>
                                                        <?php endif; ?>
                                                        
                                                        <?php if ($report['status'] === 'pending'): ?>
                                                            <div class="mt-2">
                                                                <form method="post" class="d-inline me-1">
                                                                    <?= csrf_field() ?>
                                                                    <input type="hidden" name="comment_id" value="<?= $comment['id'] ?>">
                                                                    <input type="hidden" name="report_id" value="<?= $report['id'] ?>">
                                                                    <button type="submit" name="action" value="resolve_report" class="btn btn-sm btn-success">
                                                                        <i class="bi bi-check-lg"></i> Résoudre
                                                                    </button>
                                                                </form>
                                                                <form method="post" class="d-inline">
                                                                    <?= csrf_field() ?>
                                                                    <input type="hidden" name="comment_id" value="<?= $comment['id'] ?>">
                                                                    <input type="hidden" name="report_id" value="<?= $report['id'] ?>">
                                                                    <button type="submit" name="action" value="reject_report" class="btn btn-sm btn-warning">
                                                                        <i class="bi bi-x-lg"></i> Rejeter
                                                                    </button>
                                                                </form>
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php else: ?>
                                            <div class="alert alert-info">Aucun détail de signalement disponible.</div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
            <nav aria-label="Navigation des pages">
                <ul class="pagination justify-content-center">
                    <?php if ($page > 1): ?>
                        <li class="page-item">
                            <a class="page-link" href="?page=<?= $page - 1 ?>">Précédent</a>
                        </li>
                    <?php endif; ?>
                    
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                            <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
                        </li>
                    <?php endfor; ?>
                    
                    <?php if ($page < $total_pages): ?>
                        <li class="page-item">
                            <a class="page-link" href="?page=<?= $page + 1 ?>">Suivant</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </nav>
        <?php endif; ?>
    <?php endif; ?>
</div>

<!-- Modal d'aide -->
<div class="modal fade" id="helpModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-question-circle me-2"></i>Aide - Gestion des commentaires signalés</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <div class="modal-body">
                <h6 class="fw-bold mb-3">Comment gérer les commentaires signalés :</h6>
                <ul class="list-unstyled">
                    <li class="mb-2">
                        <span class="badge bg-primary me-2"><i class="bi bi-box-arrow-up-right"></i></span>
                        <span>Voir le commentaire dans son contexte</span>
                    </li>
                    <li class="mb-2">
                        <span class="badge bg-danger me-2"><i class="bi bi-trash"></i></span>
                        <span>Supprimer un commentaire inapproprié</span>
                    </li>
                    <li class="mb-2">
                        <span class="badge bg-secondary me-2"><i class="bi bi-list-ul"></i></span>
                        <span>Voir la liste des signalements pour un commentaire</span>
                    </li>
                </ul>
                <div class="alert alert-info">
                    <i class="bi bi-info-circle me-2"></i>
                    Les commentaires sont automatiquement masqués après 3 signalements.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" data-bs-dismiss="modal">J'ai compris</button>
            </div>
        </div>
    </div>
</div>

<?php 
// Chemin vers le footer
$footerPath = __DIR__ . '/../templates/footer.php'; 

// Inclure le footer
include $footerPath;
?>

<!-- JavaScript pour les interactions -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Activer les tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Confirmation avant suppression
    window.confirmDelete = function(commentId) {
        if (confirm('Êtes-vous sûr de vouloir supprimer ce commentaire ?')) {
            const blockUser = document.getElementById('blockUser' + commentId)?.checked || false;
            // Ajouter un champ caché pour bloquer l'utilisateur si nécessaire
            if (blockUser) {
                const form = document.createElement('form');
                form.method = 'post';
                form.innerHTML = `
                    <input type="hidden" name="action" value="block_user">
                    <input type="hidden" name="comment_id" value="${commentId}">
                    <input type="hidden" name="block_user" value="1">
                    ${document.querySelector('input[name="csrf_token"]').outerHTML}
                `;
                document.body.appendChild(form);
                form.submit();
                return false;
            }
            return true;
        }
        return false;
    }

    // Rafraîchir la page toutes les 5 minutes pour voir les nouveaux signalements
    setTimeout(function() {
        window.location.reload();
    }, 300000); // 5 minutes
});
</script>
