<?php
/**
 * Page 404
 * 
 * Cette page est affichée lorsqu'une page est introuvable.
 * Peut être incluse de n'importe où dans l'application.
 */

// Définir le code de statut HTTP 404
http_response_code(404);

// Définir le type de contenu
header('Content-Type: text/html; charset=utf-8');

// Vérifier si les constantes nécessaires sont définies
defined('ROOT_PATH') or define('ROOT_PATH', dirname(dirname(__DIR__)));

// Définir le titre de la page
$page_title = '404 - ReadSphere';

// Vérifier si on est déjà dans un layout qui inclut header/footer
$standalone = !(isset($no_layout) && $no_layout === false);

// Inclure l'en-tête si nécessaire
if ($standalone) {
    // Inclure l'initialisation si nécessaire
    if (!function_exists('url')) {
        require_once ROOT_PATH . '/includes/init.php';
    }
    
    // Inclure le header
    require_once ROOT_PATH . '/templates/header.php';
}
?>

<div class="min-h-screen bg-gray-50 flex flex-col justify-center items-center px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8 text-center">
        <!-- Icône ou image d'erreur -->
        <div class="text-indigo-600">
            <svg class="mx-auto h-16 w-16" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
        </div>
        
        <!-- Titre -->
        <h1 class="text-4xl font-extrabold text-gray-900 sm:text-5xl">404</h1>
        <h2 class="mt-2 text-2xl font-bold text-gray-900">Page non trouvée</h2>
        
        <!-- Message -->
        <p class="mt-4 text-gray-600">
            Désolé, nous n'avons pas trouvé la page que vous cherchez.
        </p>
        
        <!-- Bouton de retour -->
        <div class="mt-8">
            <a href="<?php echo function_exists('url') ? url('/') : '/'; ?>" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors duration-200">
                <svg class="-ml-1 mr-2 h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm.707-10.293a1 1 0 00-1.414-1.414l-3 3a1 1 0 000 1.414l3 3a1 1 0 001.414-1.414L9.414 11H13a1 1 0 100-2H9.414l1.293-1.293z" clip-rule="evenodd" />
                </svg>
                Retour à l'accueil
            </a>
        </div>
    </div>
</div>

<?php 
// Inclure le pied de page si nécessaire
if ($standalone) {
    require_once ROOT_PATH . '/templates/footer.php';
}
?>
