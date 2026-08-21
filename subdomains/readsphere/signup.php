<?php

/**
 * Page d'inscription
 */

// Désactiver l'affichage des erreurs en production
// error_reporting(E_ALL);
// ini_set('display_errors', 1);

require_once 'includes/init.php';

// Initialisation des variables
$errors = [];
$form_data = [
    'username' => '',
    'email' => ''
];

// Vérification si l'utilisateur est déjà connecté
if (is_logged_in()) {
    redirect('index.php');
}

// Traitement du formulaire d'inscription
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $form_data = [
        'username' => trim($_POST['username'] ?? ''),
        'email' => filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL),
        'password' => $_POST['password'] ?? '',
        'password_confirm' => $_POST['password_confirm'] ?? ''
    ];
    
    // Validation des données
    if (empty($form_data['username'])) {
        $errors['username'] = 'Le nom d\'utilisateur est requis';
    } elseif (strlen($form_data['username']) < 3) {
        $errors['username'] = 'Le nom d\'utilisateur doit contenir au moins 3 caractères';
    } elseif (username_exists($form_data['username'])) {
        $errors['username'] = 'Ce nom d\'utilisateur est déjà pris';
    }
    
    if (empty($form_data['email'])) {
        $errors['email'] = 'L\'adresse email est requise';
    } elseif (!filter_var($form_data['email'], FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'L\'adresse email n\'est pas valide';
    } elseif (email_exists($form_data['email'])) {
        $errors['email'] = 'Cette adresse email est déjà utilisée';
    }
    
    if (empty($form_data['password'])) {
        $errors['password'] = 'Le mot de passe est requis';
    } elseif (strlen($form_data['password']) < 8) {
        $errors['password'] = 'Le mot de passe doit contenir au moins 8 caractères';
    } elseif ($form_data['password'] !== $form_data['password_confirm']) {
        $errors['password_confirm'] = 'Les mots de passe ne correspondent pas';
    }
    
    // Si pas d'erreurs, création du compte
    if (empty($errors)) {
        $user_id = create_user($form_data);
        
        if ($user_id) {
            // Connexion automatique après inscription
            if (authenticate_user($form_data['email'], $form_data['password'])) {
                set_flash('Votre compte a été créé avec succès ! Un email de vérification a été envoyé à votre adresse email.', 'success');
                redirect('index.php');
            }
        } else {
            $errors['general'] = 'Une erreur est survenue lors de la création du compte. Veuillez réessayer.';
        }
    }
}

// Affichage du formulaire
$page_title = 'Inscription';
require_once 'templates/auth_header.php';
?>

<form method="POST" action="signup.php" class="space-y-4">
    <?php if (!empty($errors['general'])): ?>
        <div class="bg-red-50 border-l-4 border-red-500 text-red-700 p-4 mb-4 rounded">
            <div class="flex">
                <div class="flex-shrink-0">
                    <i class="fas fa-exclamation-circle h-5 w-5 text-red-500"></i>
                </div>
                <div class="ml-3">
                    <p class="text-sm"><?= htmlspecialchars($errors['general']) ?></p>
                </div>
            </div>
        </div>
    <?php endif; ?>
    
    <div>
        <label for="username" class="block text-sm font-medium text-gray-700 mb-1">Nom d'utilisateur</label>
        <input type="text" id="username" name="username" value="<?= htmlspecialchars($form_data['username']) ?>" 
               class="w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500" 
               required>
        <?php if (!empty($errors['username'])): ?>
            <p class="mt-1 text-sm text-red-600"><?= htmlspecialchars($errors['username']) ?></p>
        <?php endif; ?>
    </div>
    
    <div>
        <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Adresse email</label>
        <input type="email" id="email" name="email" value="<?= htmlspecialchars($form_data['email']) ?>" 
               class="w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500" 
               required>
        <?php if (!empty($errors['email'])): ?>
            <p class="mt-1 text-sm text-red-600"><?= htmlspecialchars($errors['email']) ?></p>
        <?php endif; ?>
    </div>
    
    <div>
        <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Mot de passe</label>
        <div class="relative">
            <input type="password" id="password" name="password" 
                   class="w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500" 
                   required>
        </div>
        <p class="mt-1 text-xs text-gray-500">Le mot de passe doit contenir au moins 8 caractères.</p>
        <?php if (!empty($errors['password'])): ?>
            <p class="mt-1 text-sm text-red-600"><?= htmlspecialchars($errors['password']) ?></p>
        <?php endif; ?>
    </div>
    
    <div>
        <label for="password_confirm" class="block text-sm font-medium text-gray-700 mb-1">Confirmer le mot de passe</label>
        <input type="password" id="password_confirm" name="password_confirm" 
               class="w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500" 
               required>
        <?php if (!empty($errors['password_confirm'])): ?>
            <p class="mt-1 text-sm text-red-600"><?= htmlspecialchars($errors['password_confirm']) ?></p>
        <?php endif; ?>
    </div>
    
    <div class="pt-2">
        <button type="submit" 
                class="w-full flex justify-center py-2.5 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors duration-200">
            Créer mon compte
        </button>
    </div>
    
    <div class="text-xs text-gray-500 mt-3 text-center">
        En vous inscrivant, vous acceptez nos <a href="<?= url('terms.php') ?>" class="text-blue-600 hover:text-blue-500 transition-colors" target="_blank">Conditions d'utilisation</a> 
        et notre <a href="<?= url('privacy.php') ?>" class="text-blue-600 hover:text-blue-500 transition-colors" target="_blank">Politique de confidentialité</a>.
    </div>
</form>

<?php require_once 'templates/auth_footer.php'; ?>