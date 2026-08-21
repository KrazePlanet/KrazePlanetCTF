<?php
/**
 * Script de test pour l'envoi d'emails
 * 
 * Ce script permet de tester la configuration d'envoi d'emails
 * en utilisant les paramètres définis dans le fichier .env
 */

// Activer l'affichage des erreurs
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Inclure l'initialisation de l'application
require_once 'includes/init.php';

// Vérifier si les variables d'environnement nécessaires sont définies
$requiredEnvVars = [
    'MAIL_HOST',
    'MAIL_USERNAME',
    'MAIL_PASSWORD',
    'MAIL_PORT'
];

echo "<h2>Test de configuration d'envoi d'emails</h2>";

echo "<h3>Vérification des variables d'environnement :</h3>";
$allVarsPresent = true;

foreach ($requiredEnvVars as $var) {
    if (!getenv($var)) {
        echo "<p style='color:red'>✗ Variable manquante : $var</p>";
        $allVarsPresent = false;
    } else {
        // Masquer les informations sensibles
        $value = $var === 'MAIL_PASSWORD' ? '********' : getenv($var);
        echo "<p style='color:green'>✓ $var = $value</p>";
    }
}

if (!$allVarsPresent) {
    die("<p style='color:red'>Erreur : Des variables d'environnement requises sont manquantes.</p>");
}

// Inclure la classe Mailer
require_once 'includes/Mailer.php';

// Tester la connexion SMTP
echo "<h3>Test de connexion SMTP :</h3>";
try {
    $smtp = @fsockopen(getenv('MAIL_HOST'), getenv('MAIL_PORT'), $errno, $errstr, 10);
    if ($smtp) {
        echo "<p style='color:green'>✓ Connexion SMTP réussie à " . getenv('MAIL_HOST') . ":" . getenv('MAIL_PORT') . "</p>";
        fclose($smtp);
    } else {
        echo "<p style='color:red'>✗ Impossible de se connecter au serveur SMTP : $errstr (Code: $errno)</p>";
    }
} catch (Exception $e) {
    echo "<p style='color:red'>✗ Erreur lors de la connexion SMTP : " . htmlspecialchars($e->getMessage()) . "</p>";
}

// Adresse email de test (utiliser le meme test du fichier .env)
$testEmail = getenv('MAIL_USERNAME');

// Préparer le contenu de l'email
$subject = 'Test d\'envoi d\'email - ' . date('Y-m-d H:i:s');
$body = '<h1>Test d\'envoi d\'email</h1>'
    . '<p>Ceci est un email de test envoyé depuis ' . getenv('APP_NAME') . '.</p>'
    . '<p>Date et heure : ' . date('Y-m-d H:i:s') . '</p>';
$altBody = 'Test d\'envoi d\'email - ' . date('Y-m-d H:i:s');

// Envoyer l'email
try {
    // Utiliser la fonction send_email() du fichier Mailer.php
    $result = send_email(
        $testEmail,
        $subject,
        $body,
        $altBody
    );

    if ($result) {
        echo "<div style='color:green; margin: 20px 0; padding: 15px; background: #e8f5e9; border: 1px solid #4caf50; border-radius: 4px;'>";
        echo "<h3>✅ Test réussi !</h3>";
        echo "<p>L'email de test a été envoyé avec succès à : " . htmlspecialchars($testEmail) . "</p>";
    } else {
        echo "<div style='color:#8a6d3b; margin: 20px 0; padding: 15px; background: #fcf8e3; border: 1px solid #faebcc; border-radius: 4px;'>";
        echo "<h3>⚠️ Avertissement</h3>";
        echo "<p>L'email a été traité mais le statut d'envoi n'a pas pu être vérifié.</p>";
    }
    
    // Afficher des informations de débogage
    echo "<h4>Détails de l'envoi :</h4>";
    echo "<div style='background: #f5f5f5; padding: 10px; border-radius: 4px; font-family: monospace;'>";
    echo "Serveur SMTP : " . htmlspecialchars(getenv('MAIL_HOST')) . " (Port: " . htmlspecialchars(getenv('MAIL_PORT')) . ")<br>";
    echo "Expéditeur : " . htmlspecialchars(getenv('MAIL_USERNAME')) . " (" . htmlspecialchars(getenv('MAIL_USERNAME')) . ")<br>";
    echo "Destinataire : " . htmlspecialchars($testEmail) . "<br>";
    echo "Sécurité : " . (getenv('MAIL_SECURE') ? htmlspecialchars(getenv('MAIL_SECURE')) : 'Aucune') . "<br>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div style='color:#a94442; margin: 20px 0; padding: 15px; background: #f2dede; border: 1px solid #ebccd1; border-radius: 4px;'>";
    echo "<h3>❌ Échec de l'envoi</h3>";
    echo "<p>Erreur lors de l'envoi de l'email :</p>";
    echo "<pre style='white-space: pre-wrap;'>" . htmlspecialchars($e->getMessage()) . "</pre>";
    
    // Afficher plus de détails de débogage si disponibles
    if (isset($mail) && is_object($mail) && isset($mail->ErrorInfo)) {
        echo "<p>Détails de débogage :</p>";
        echo "<pre style='white-space: pre-wrap;'>" . htmlspecialchars($mail->ErrorInfo) . "</pre>";
    }
} finally {
    if (isset($result) || isset($e)) {
        echo "</div>"; // Fermer la div d'alerte
    }
}

echo "<p style='margin-top: 30px; font-size: 0.9em; color: #666;'>";
echo "<strong>Note :</strong> Assurez-vous que l'adresse email de test est valide et que le serveur SMTP est correctement configuré.";
echo "</p>";
