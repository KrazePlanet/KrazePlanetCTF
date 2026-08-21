
<?php

/*
 * Gestion des logs de modération
 * 
 * Affiche les logs de modération et permet de les filtrer
 */

 // Activer l'affichage des erreurs
// error_reporting(E_ALL);
// ini_set('display_errors', 1);

require_once __DIR__ . '/../includes/init.php'; 

// Vérifier que l'utilisateur est administrateur
if (!is_admin()) {  
    header('Location: ' . BASE_PATH . '/login.php');
    exit;
}

// Nombre d'éléments par page
$per_page = 20;

// Récupérer le numéro de page actuel
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$page = max(1, $page);

// Récupérer les filtres
$user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : null;
$action_type = isset($_GET['action_type']) ? $_GET['action_type'] : '';
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : '';
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : '';

// Récupérer les logs de modération
$logs = get_moderation_logs($page, $per_page, $user_id, $action_type, $start_date, $end_date);
$total_logs = get_moderation_logs_count($user_id, $action_type, $start_date, $end_date);
$total_pages = ceil($total_logs / $per_page);

// Récupérer la liste des types d'action pour le filtre
$action_types = [
    'delete' => 'Suppression de commentaire',
    'ignore' => 'Signalement ignoré',
    'warn_user' => 'Avertissement utilisateur',
    'ban_user' => 'Utilisateur banni'
];

// Titre de la page
$page_title = 'Historique des actions de modération';

// Inclure l'en-tête
require_once __DIR__ . '/../templates/header.php';
?>

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <h1 class="h3 mb-0"><?= htmlspecialchars($page_title) ?></h1>
                <a href="comments_dashboard.php" class="btn btn-outline-primary">
                    <i class="fas fa-arrow-left me-1"></i> Retour au tableau de bord
                </a>
            </div>
        </div>
    </div>

    <!-- Filtres -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0">Filtres</h5>
        </div>
        <div class="card-body">
            <form id="filter-form" method="get" class="row g-3">
                <div class="col-md-3">
                    <label for="user_id" class="form-label">ID Utilisateur</label>
                    <input type="number" class="form-control" id="user_id" name="user_id" 
                           value="<?= htmlspecialchars($user_id ?? '') ?>">
                </div>
                <div class="col-md-3">
                    <label for="action_type" class="form-label">Type d'action</label>
                    <select class="form-select" id="action_type" name="action_type">
                        <option value="">Tous les types</option>
                        <?php foreach ($action_types as $value => $label): ?>
                            <option value="<?= htmlspecialchars($value) ?>" 
                                <?= $action_type === $value ? 'selected' : '' ?>>
                                <?= htmlspecialchars($label) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="start_date" class="form-label">Date de début</label>
                    <input type="date" class="form-control" id="start_date" name="start_date" 
                           value="<?= htmlspecialchars($start_date) ?>">
                </div>
                <div class="col-md-3">
                    <label for="end_date" class="form-label">Date de fin</label>
                    <input type="date" class="form-control" id="end_date" name="end_date" 
                           value="<?= htmlspecialchars($end_date) ?>">
                </div>
                <div class="col-12">
                    <button type="submit" class="btn btn-primary me-2">
                        <i class="fas fa-filter me-1"></i> Filtrer
                    </button>
                    <button type="button" id="reset-filters" class="btn btn-outline-secondary">
                        <i class="fas fa-undo me-1"></i> Réinitialiser
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Tableau des logs -->
    <div class="card">
        <div class="card-body p-0">
            <?php if (empty($logs)): ?>
                <div class="text-center py-5">
                    <div class="mb-3">
                        <i class="fas fa-inbox fa-3x text-muted"></i>
                    </div>
                    <h5 class="text-muted">Aucune action de modération trouvée</h5>
                    <p class="text-muted">Aucune action ne correspond à vos critères de recherche.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>ID</th>
                                <th>Date</th>
                                <th>Modérateur</th>
                                <th>Utilisateur</th>
                                <th>Action</th>
                                <th>Livre</th>
                                <th>Détails</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($logs as $log): ?>
                                <tr>
                                    <td><?= $log['id'] ?></td>
                                    <td>
                                        <span data-bs-toggle="tooltip" title="<?= date('d/m/Y H:i:s', strtotime($log['created_at'])) ?>">
                                            <?= time_elapsed_string($log['created_at']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="<?= url("/profile.php?id=" . $log['moderator_id']) ?>">
                                            <?= htmlspecialchars($log['moderator_username'] ?? 'Modérateur #' . $log['moderator_id']) ?>
                                        </a>
                                    </td> 
                                    <td>
                                        <?php if (!empty($log['username'])): ?>
                                            <a href="<?= url("/profile.php?id=" . $log['user_id']) ?>">
                                                <?= htmlspecialchars($log['username']) ?>
                                            </a>
                                        <?php else: ?>
                                            <span class="text-muted">Utilisateur inconnu</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?= get_action_badge_class($log['action_type']) ?> me-1">
                                            <?= htmlspecialchars(ucfirst(str_replace('_', ' ', $log['action_type']))) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if (!empty($log['book_id'])): ?>
                                            <a href="<?= url("/book.php?id=" . $log['book_id']) ?>">
                                                <?= htmlspecialchars($log['book_title'] ?? 'Livre #' . $log['book_id']) ?>
                                            </a>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (!empty($log['comment_content'])): ?>
                                            <button type="button" class="btn btn-sm btn-outline-secondary" 
                                                    data-bs-toggle="popover" data-bs-placement="left"
                                                    title="Commentaire"
                                                    data-bs-content="<?= htmlspecialchars($log['comment_content']) ?>">
                                                <i class="fas fa-comment"></i> Voir
                                            </button>
                                        <?php endif; ?>
                                        <?php if (!empty($log['reason'])): ?>
                                            <button type="button" class="btn btn-sm btn-outline-secondary ms-1" 
                                                    data-bs-toggle="popover" data-bs-placement="left"
                                                    title="Raison"
                                                    data-bs-content="<?= htmlspecialchars($log['reason']) ?>">
                                                <i class="fas fa-info-circle"></i> Raison
                                            </button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                    <div class="card-footer d-flex justify-content-center">
                        <nav aria-label="Pagination">
                            <ul class="pagination mb-0">
                                <?php if ($page > 1): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => 1])) ?>" 
                                           aria-label="Première page">
                                            <span aria-hidden="true">&laquo;&laquo;</span>
                                        </a>
                                    </li>
                                    <li class="page-item">
                                        <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>" 
                                           aria-label="Précédent">
                                            <span aria-hidden="true">&laquo;</span>
                                        </a>
                                    </li>
                                <?php endif; ?>

                                <?php
                                // Afficher les numéros de page
                                $start_page = max(1, $page - 2);
                                $end_page = min($total_pages, $page + 2);

                                if ($start_page > 1) {
                                    echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                                }

                                for ($i = $start_page; $i <= $end_page; $i++): ?>
                                    <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                                        <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>">
                                            <?= $i ?>
                                        </a>
                                    </li>
                                <?php endfor;

                                if ($end_page < $total_pages) {
                                    echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                                }
                                ?>

                                <?php if ($page < $total_pages): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>" 
                                           aria-label="Suivant">
                                            <span aria-hidden="true">&raquo;</span>
                                        </a>
                                    </li>
                                    <li class="page-item">
                                        <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $total_pages])) ?>" 
                                           aria-label="Dernière page">
                                            <span aria-hidden="true">&raquo;&raquo;</span>
                                        </a>
                                    </li>
                                <?php endif; ?>
                            </ul>
                        </nav>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Activer les tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Gérer la soumission du formulaire de filtres
    document.getElementById('filter-form').addEventListener('submit', function(e) {
        // Réinitialiser la page à 1 lors de l'application des filtres
        var pageInput = document.querySelector('input[name="page"]');
        if (pageInput) {
            pageInput.value = 1;
        }
    });

    // Réinitialiser les filtres
    document.getElementById('reset-filters').addEventListener('click', function(e) {
        e.preventDefault();
        window.location.href = 'moderation_logs.php';
    });
});
</script>

<?php
/**
 * Retourne la classe CSS pour le badge en fonction du type d'action
 * 
 * @param string $action_type Type d'action
 * @return string Classe CSS Bootstrap pour le badge
 */
function get_action_badge_class($action_type) {
    switch ($action_type) {
        case 'delete':
            return 'danger';
        case 'warn_user':
            return 'warning';
        case 'ban_user':
            return 'dark';
        case 'ignore':
            return 'secondary';
        default:
            return 'info';
    }
}

// Inclure le pied de page
require_once __DIR__ . '/../templates/footer.php';
?>
