<?php
/**
 * Page de connexion
 */

// Initialisation de l'application
require_once __DIR__ . '/includes/init.php';

// Vérifier que les constantes sont bien définies
if (!defined('BASE_PATH')) {
    // Si BASE_PATH n'est pas défini, essayer de le définir
    $base_path = rtrim(dirname($_SERVER['PHP_SELF']), '/\\');
    define('BASE_PATH', $base_path);
}

// Rediriger les utilisateurs déjà connectés vers la page d'accueil
if (is_logged_in()) {
    redirect(BASE_PATH . '/index.php');
    exit;
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'] ?? '';
    $remember = isset($_POST['remember_me']);
    
    if (authenticate_user($email, $password, $remember)) {
        // Rediriger vers la page demandée ou la page d'accueil
        $redirect = $_SESSION['redirect_after_login'] ?? 'index.php';
        unset($_SESSION['redirect_after_login']);
        header('Location: ' . BASE_PATH . '/' . ltrim($redirect, '/'));
        exit;
    } else {
        $errors = $_SESSION['form_errors'] ?? ['general' => 'Adresse email ou mot de passe incorrect'];
        unset($_SESSION['form_errors']);
    }
}

$page_title = 'Connexion';
require_once 'templates/auth_header.php';
?>

<form method="POST" action="login.php" class="space-y-4">
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
        <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Adresse email</label>
        <input type="email" id="email" name="email" required
               class="w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
               value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
        <?php if (!empty($errors['email'])): ?>
            <p class="mt-1 text-sm text-red-600"><?= htmlspecialchars($errors['email']) ?></p>
        <?php endif; ?>
    </div>
    
    <div>
        <div class="flex items-center justify-between mb-1">
            <label for="password" class="block text-sm font-medium text-gray-700">Mot de passe</label>
            <a href="forgot-password.php" class="text-xs text-blue-600 hover:text-blue-500">Mot de passe oublié ?</a>
        </div>
        <input type="password" id="password" name="password" required
               class="w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
        <?php if (!empty($errors['password'])): ?>
            <p class="mt-1 text-sm text-red-600"><?= htmlspecialchars($errors['password']) ?></p>
        <?php endif; ?>
    </div>
    
    <div class="flex items-center">
        <input type="checkbox" id="remember_me" name="remember_me" 
               class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
        <label for="remember_me" class="ml-2 block text-sm text-gray-700">Se souvenir de moi</label>
    </div>
    
    <div class="pt-2">
        <button type="submit" 
                class="w-full flex justify-center py-2.5 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors duration-200">
            Se connecter
        </button>
    </div>
</form>

<?php require_once 'templates/auth_footer.php'; ?>