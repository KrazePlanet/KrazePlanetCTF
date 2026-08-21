<?php
// Inclure le fichier d'initialisation
require_once __DIR__ . '/includes/init.php';

// Tester la journalisation de base
log_message("Ceci est un message d'information de test", 'info');
log_message("Ceci est un avertissement de test", 'warning');
log_message("Ceci est une erreur de test", 'error');

// Tester la journalisation d'exception
try {
    // Générer une exception de test
    throw new Exception("Ceci est une exception de test");
} catch (Exception $e) {
    log_exception($e);
}

// Tester une erreur PHP (sera capturée par le gestionnaire d'erreurs)
// Méthode plus sûre pour tester la journalisation des erreurs
trigger_error("Ceci est une erreur de test générée manuellement", E_USER_WARNING);

echo "<h1>Test de journalisation terminé</h1>";
echo "<p>Vérifiez le fichier de logs dans le dossier /logs/</p>";
?>
