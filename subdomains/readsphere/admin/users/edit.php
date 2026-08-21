<?php

/**
 * Éditer un utilisateur
 */

// Vérifier si l'utilisateur est connecté et est un administrateur
require_once __DIR__ . '/../../includes/init.php';

if (!is_logged_in() || !is_admin()) {
    header('Location: ' . BASE_PATH . '/login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    exit;
}

// Vérifier si l'ID de l'utilisateur est fourni
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    set_flash('ID utilisateur invalide', 'error');
    header('Location: ' . BASE_PATH . '/admin/users/');
    exit;
}

$user_id = (int)$_GET['id'];

// Récupérer les informations de l'utilisateur
$user = get_user_by_id($user_id);
if (!$user) {
    set_flash('Utilisateur non trouvé', 'error');
    header('Location:  ' . BASE_PATH . '/admin/users/');
    exit;
}

// Traitement du formulaire de mise à jour
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $errors = [];
    
    // Récupérer les données du formulaire
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $role = $_POST['role'] ?? 'user';
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    $reset_password = isset($_POST['reset_password']);
    
    // Validation
    if (empty($username)) {
        $errors['username'] = 'Le nom d\'utilisateur est requis';
    } elseif (strlen($username) < 3 || strlen($username) > 50) {
        $errors['username'] = 'Le nom d\'utilisateur doit contenir entre 3 et 50 caractères';
    }
    
    if (empty($email)) {
        $errors['email'] = 'L\'adresse email est requise';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'L\'adresse email n\'est pas valide';
    }
    
    if (!in_array($role, ['user', 'moderator', 'admin'])) {
        $errors['role'] = 'Rôle invalide';
    }
    
    // Vérifier si l'email est déjà utilisé par un autre utilisateur
    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $stmt->execute([$email, $user_id]);
        if ($stmt->fetch()) {
            $errors['email'] = 'Cette adresse email est déjà utilisée';
        }
    }
    
    // Mise à jour des informations de l'utilisateur
    if (empty($errors)) {
        try {
            $pdo->beginTransaction();
            
            // Mise à jour des informations de base
            $stmt = $pdo->prepare("
                UPDATE users 
                SET username = ?, email = ?, role = ?, is_active = ?
                WHERE id = ?
            ");
            $stmt->execute([$username, $email, $role, $is_active, $user_id]);
            
            // Réinitialisation du mot de passe si demandé
            if ($reset_password) {
                $temp_password = bin2hex(random_bytes(8));
                $hashed_password = password_hash($temp_password, PASSWORD_DEFAULT);
                
                $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                $stmt->execute([$hashed_password, $user_id]);
                
                // Envoyer un email avec le nouveau mot de passe (à implémenter)
                // send_password_reset_email($email, $username, $temp_password);
                
                set_flash("Le mot de passe a été réinitialisé. Le nouveau mot de passe est : $temp_password", 'info');
            }
            
            $pdo->commit();
            
            set_flash('Les informations de l\'utilisateur ont été mises à jour avec succès');
            header('Location: ' . BASE_PATH . '/admin/users/');
            exit;
            
        } catch (PDOException $e) {
            $pdo->rollBack();
            error_log('Erreur lors de la mise à jour de l\'utilisateur : ' . $e->getMessage());
            $errors[] = 'Une erreur est survenue lors de la mise à jour de l\'utilisateur';
        }
    }
}

$page_title = 'Éditer l\'utilisateur : ' . htmlspecialchars($user['username']);
require_once __DIR__ . '/../../templates/header.php';
?>

<div class="container mx-auto px-4 py-8">
    <div class="mb-6">
        <a href="<?= BASE_PATH ?>/admin/users/" class="text-blue-600 hover:text-blue-800">
            &larr; Retour à la liste des utilisateurs
        </a>
    </div>
    
    <div class="bg-white shadow overflow-hidden sm:rounded-lg">
        <div class="px-4 py-5 sm:px-6 border-b border-gray-200">
            <h3 class="text-lg leading-6 font-medium text-gray-900">
                Éditer l'utilisateur : <?= htmlspecialchars($user['username']) ?>
            </h3>
            <p class="mt-1 max-w-2xl text-sm text-gray-500">
                Modifiez les informations de l'utilisateur ci-dessous.
            </p>
        </div>
        
        <div class="px-4 py-5 sm:p-6">
            <?php if (!empty($errors)): ?>
                <div class="mb-4 bg-red-50 border-l-4 border-red-400 p-4">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-red-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <h3 class="text-sm font-medium text-red-800">
                                Des erreurs sont présentes dans le formulaire
                            </h3>
                            <div class="mt-2 text-sm text-red-700">
                                <ul class="list-disc pl-5 space-y-1">
                                    <?php foreach ($errors as $error): ?>
                                        <li><?= htmlspecialchars($error) ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
            
            <form method="POST" class="space-y-6">
                <div class="grid grid-cols-1 gap-y-6 gap-x-4 sm:grid-cols-6">
                    <div class="sm:col-span-3">
                        <label for="username" class="block text-sm font-medium text-gray-700">
                            Nom d'utilisateur <span class="text-red-500">*</span>
                        </label>
                        <div class="mt-1">
                            <input type="text" name="username" id="username" 
                                   value="<?= htmlspecialchars($user['username']) ?>"
                                   class="shadow-sm focus:ring-blue-500 focus:border-blue-500 block w-full sm:text-sm border-gray-300 rounded-md">
                        </div>
                        <?php if (isset($errors['username'])): ?>
                            <p class="mt-1 text-sm text-red-600"><?= htmlspecialchars($errors['username']) ?></p>
                        <?php endif; ?>
                    </div>
                    
                    <div class="sm:col-span-4">
                        <label for="email" class="block text-sm font-medium text-gray-700">
                            Adresse email <span class="text-red-500">*</span>
                        </label>
                        <div class="mt-1">
                            <input type="email" name="email" id="email" 
                                   value="<?= htmlspecialchars($user['email']) ?>"
                                   class="shadow-sm focus:ring-blue-500 focus:border-blue-500 block w-full sm:text-sm border-gray-300 rounded-md">
                        </div>
                        <?php if (isset($errors['email'])): ?>
                            <p class="mt-1 text-sm text-red-600"><?= htmlspecialchars($errors['email']) ?></p>
                        <?php endif; ?>
                    </div>
                    
                    <div class="sm:col-span-3">
                        <label for="role" class="block text-sm font-medium text-gray-700">
                            Rôle <span class="text-red-500">*</span>
                        </label>
                        <div class="mt-1">
                            <select id="role" name="role" class="shadow-sm focus:ring-blue-500 focus:border-blue-500 block w-full sm:text-sm border-gray-300 rounded-md">
                                <option value="user" <?= $user['role'] === 'user' ? 'selected' : '' ?>>Utilisateur</option>
                                <option value="moderator" <?= $user['role'] === 'moderator' ? 'selected' : '' ?>>Modérateur</option>
                                <option value="admin" <?= $user['role'] === 'admin' ? 'selected' : '' ?>>Administrateur</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="sm:col-span-6">
                        <div class="flex items-start">
                            <div class="flex items-center h-5">
                                <input id="is_active" name="is_active" type="checkbox" 
                                       class="focus:ring-blue-500 h-4 w-4 text-blue-600 border-gray-300 rounded"
                                       <?= $user['is_active'] ? 'checked' : '' ?>>
                            </div>
                            <div class="ml-3 text-sm">
                                <label for="is_active" class="font-medium text-gray-700">Compte actif</label>
                                <p class="text-gray-500">Si désactivé, l'utilisateur ne pourra plus se connecter.</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="sm:col-span-6">
                        <div class="flex items-start">
                            <div class="flex items-center h-5">
                                <input id="reset_password" name="reset_password" type="checkbox" 
                                       class="focus:ring-blue-500 h-4 w-4 text-blue-600 border-gray-300 rounded">
                            </div>
                            <div class="ml-3 text-sm">
                                <label for="reset_password" class="font-medium text-gray-700">Réinitialiser le mot de passe</label>
                                <p class="text-gray-500">Un nouveau mot de passe sera généré et affiché à l'écran.</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="pt-5">
                    <div class="flex justify-end">
                        <a href="<?= BASE_PATH ?>/admin/users/" class="bg-white py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            Annuler
                        </a>
                        <button type="submit" class="ml-3 inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            Enregistrer les modifications
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../templates/footer.php'; ?>
