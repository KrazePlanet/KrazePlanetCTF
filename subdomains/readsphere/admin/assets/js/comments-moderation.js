/**
 * Gestion des interactions du tableau de bord de modération des commentaires
 */

document.addEventListener('DOMContentLoaded', function() {
    // Initialiser les tooltips Bootstrap
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Gestion de la suppression d'un commentaire
    document.querySelectorAll('.delete-comment').forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const commentId = this.dataset.commentId;
            showDeleteModal(commentId);
        });
    });

    // Gestion de la résolution d'un signalement
    document.querySelectorAll('.resolve-reports').forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const commentId = this.dataset.commentId;
            resolveReports(commentId);
        });
    });

    // Gestion de l'affichage des signalements
    document.querySelectorAll('.view-reports').forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const commentId = this.dataset.commentId;
            loadReports(commentId);
        });
    });

    // Gestion de la confirmation de suppression
    const confirmDeleteBtn = document.getElementById('confirmDelete');
    if (confirmDeleteBtn) {
        confirmDeleteBtn.addEventListener('click', function() {
            const commentId = this.dataset.commentId;
            const reason = document.getElementById('deleteReason').value;
            deleteComment(commentId, reason);
        });
    }
});

/**
 * Affiche la modale de confirmation de suppression
 */
function showDeleteModal(commentId) {
    const modal = new bootstrap.Modal(document.getElementById('deleteConfirmModal'));
    const confirmBtn = document.getElementById('confirmDelete');
    confirmBtn.dataset.commentId = commentId;
    modal.show();
}

/**
 * Supprime un commentaire
 */
function deleteComment(commentId, reason) {
    const formData = new FormData();
    formData.append('action', 'delete_comment');
    formData.append('comment_id', commentId);
    formData.append('reason', reason);
    formData.append('csrf_token', document.querySelector('meta[name="csrf-token"]').getAttribute('content'));

    fetch('/ReadSphere/admin/actions/moderation_actions.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert('success', data.message);
            // Recharger la page après un court délai
            setTimeout(() => window.location.reload(), 1500);
        } else {
            showAlert('danger', data.message || 'Une erreur est survenue');
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
        showAlert('danger', 'Une erreur est survenue lors de la communication avec le serveur');
    })
    .finally(() => {
        // Fermer la modale
        const modal = bootstrap.Modal.getInstance(document.getElementById('deleteConfirmModal'));
        if (modal) {
            modal.hide();
        }
    });
}

/**
 * Résout tous les signalements d'un commentaire
 */
function resolveReports(commentId) {
    if (!confirm('Êtes-vous sûr de vouloir marquer tous les signalements de ce commentaire comme résolus ?')) {
        return;
    }

    const formData = new FormData();
    formData.append('action', 'resolve_comment');
    formData.append('comment_id', commentId);
    formData.append('csrf_token', document.querySelector('meta[name="csrf-token"]').getAttribute('content'));

    fetch('/ReadSphere/admin/actions/moderation_actions.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert('success', data.message);
            // Recharger la page après un court délai
            setTimeout(() => window.location.reload(), 1500);
        } else {
            showAlert('danger', data.message || 'Une erreur est survenue');
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
        showAlert('danger', 'Une erreur est survenue lors de la communication avec le serveur');
    });
}

/**
 * Charge les signalements d'un commentaire
 */
function loadReports(commentId) {
    fetch(`/ReadSphere/api/get_comment_reports.php?comment_id=${commentId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayReportsModal(commentId, data.reports);
            } else {
                showAlert('danger', data.message || 'Impossible de charger les signalements');
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            showAlert('danger', 'Une erreur est survenue lors du chargement des signalements');
        });
}

/**
 * Affiche les signalements dans une modale
 */
function displayReportsModal(commentId, reports) {
    const modal = new bootstrap.Modal(document.getElementById('reportsModal'));
    const modalBody = document.getElementById('reportsList');
    
    // Mettre à jour l'ID du commentaire dans le titre
    document.getElementById('commentId').textContent = commentId;
    
    // Vider le contenu précédent
    modalBody.innerHTML = '';
    
    if (reports.length === 0) {
        modalBody.innerHTML = '<div class="alert alert-info">Aucun signalement trouvé pour ce commentaire.</div>';
        modal.show();
        return;
    }
    
    // Créer la liste des signalements
    const listGroup = document.createElement('div');
    listGroup.className = 'list-group';
    
    reports.forEach(report => {
        const reportItem = document.createElement('div');
        reportItem.className = 'list-group-item';
        
        const reportDate = new Date(report.created_at).toLocaleString();
        let statusBadge = '';
        
        switch(report.status) {
            case 'resolved':
                statusBadge = '<span class="badge bg-success">Résolu</span>';
                break;
            case 'rejected':
                statusBadge = '<span class="badge bg-danger">Rejeté</span>';
                break;
            default:
                statusBadge = '<span class="badge bg-warning">En attente</span>';
        }
        
        reportItem.innerHTML = `
            <div class="d-flex w-100 justify-content-between">
                <h6 class="mb-1">${report.reporter_name || 'Utilisateur anonyme'}</h6>
                <small class="text-muted">${reportDate} ${statusBadge}</small>
            </div>
            <p class="mb-1">${escapeHtml(report.reason)}</p>
        `;
        
        listGroup.appendChild(reportItem);
    });
    
    modalBody.appendChild(listGroup);
    modal.show();
}

/**
 * Affiche une alerte
 */
function showAlert(type, message) {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
    alertDiv.role = 'alert';
    alertDiv.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fermer"></button>
    `;
    
    const container = document.querySelector('.container');
    container.insertBefore(alertDiv, container.firstChild);
    
    // Supprimer automatiquement l'alerte après 5 secondes
    setTimeout(() => {
        const alert = bootstrap.Alert.getOrCreateInstance(alertDiv);
        if (alert) {
            alert.close();
        }
    }, 5000);
}

/**
 * Échappe les caractères HTML pour éviter les injections XSS
 */
function escapeHtml(unsafe) {
    if (!unsafe) return '';
    return unsafe
        .toString()
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}
