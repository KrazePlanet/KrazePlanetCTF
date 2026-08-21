<?php
/**
 * Gestionnaire d'envoi d'emails
 * 
 * Ce fichier fournit des fonctions pour envoyer des emails à partir de l'application.
 * Utilise PHPMailer pour l'envoi des emails.
 * 
 * Configuration requise :
 * - PHPMailer doit être installé dans le dossier /PHPMailer-master/ à la racine du projet
 * - Les paramètres SMTP doivent être configurés dans la fonction send_email()
 */

// inclure et configurer les variable .env
require_once ROOT_PATH . '/includes/config.php';

// Définir le chemin vers PHPMailer
$phpmailer_path = ROOT_PATH . '/PHPMailer-master/src/';

// Vérification de l'existence des fichiers requis
if (!file_exists($phpmailer_path . 'PHPMailer.php')) {
    $error = "PHPMailer n'est pas correctement installé.\n" .
             "Veuillez télécharger PHPMailer (https://github.com/PHPMailer/PHPMailer) " .
             "et l'extraire dans le dossier " . ROOT_PATH . "/PHPMailer-master/";
    error_log($error);
    throw new Exception($error);
}

// Inclure les fichiers PHPMailer
require_once $phpmailer_path . 'Exception.php';
require_once $phpmailer_path . 'PHPMailer.php';
require_once $phpmailer_path . 'SMTP.php';

// Utilisation des classes avec leur espace de noms complet
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as PHPMailerException;
use PHPMailer\PHPMailer\SMTP as PHPMailerSMTP;

/**
 * Envoie un email
 * 
 * @param string|array $to Adresse email du destinataire ou tableau contenant 'email' et 'name'
 * @param string $subject Sujet de l'email
 * @param string $body Corps de l'email (HTML)
 * @param string $altBody Version texte de l'email (optionnel)
 * @param array $attachments Tableau de fichiers à joindre (chemin complet)
 * @return bool True si l'email a été envoyé avec succès, false sinon
 */
function send_email($to, $subject, $body, $altBody = '', $attachments = []) {
    $mail = new PHPMailer(true);
    
    try {
        // Configuration du serveur SMTP
        $mail->isSMTP();
        $mail->Host = $_ENV['MAIL_HOST']; // Serveur SMTP de Gmail
        $mail->SMTPAuth = true;
        $mail->Username = $_ENV['MAIL_USERNAME']; // Votre adresse Gmail
        $mail->Password = $_ENV['MAIL_PASSWORD']; // Votre mot de passe d'application Gmail
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = $_ENV['MAIL_PORT'];
        
        // Activer le débogage en développement (0 = désactivé, 2 = mode verbeux)
        $mail->SMTPDebug = 0;
        
        // Expéditeur
        $mail->setFrom($_ENV['MAIL_USERNAME'], 'ReadSphere');
        $mail->addReplyTo($_ENV['MAIL_USERNAME'], 'Support ReadSphere');
        
        // Destinataire
        if (is_array($to)) {
            $mail->addAddress($to['email'], $to['name'] ?? '');
        } else {
            $mail->addAddress($to);
        }
        
        // Contenu
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $body;
        $mail->AltBody = $altBody ?: strip_tags($body);
        
        // Pièces jointes
        foreach ($attachments as $attachment) {
            if (is_string($attachment) && file_exists($attachment)) {
                $mail->addAttachment($attachment);
            } elseif (is_array($attachment) && isset($attachment['path']) && file_exists($attachment['path'])) {
                $name = $attachment['name'] ?? basename($attachment['path']);
                $mail->addAttachment($attachment['path'], $name);
            }
        }
        
        // Envoyer l'email
        $result = $mail->send();
        
        if (!$result) {
            error_log("Échec de l'envoi de l'email à $to : " . $mail->ErrorInfo);
        }
        
        return $result;
        
    } catch (Exception $e) {
        error_log("Erreur d'envoi d'email à $to : " . $e->getMessage());
        return false;
    }
}

/**
 * Envoie un email de bienvenue à un nouvel utilisateur
 * 
 * @param array $user Tableau contenant les informations de l'utilisateur
 * @return bool True si l'email a été envoyé avec succès, false sinon
 */
function send_welcome_email($user) {
    $subject = 'Bienvenue sur Read Sphere !';
    
    $body = '<!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>Bienvenue sur ReadSphere</title>
    </head>
    <body>
        <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px;">
            <h1 style="color: #2563eb;">Bienvenue sur ReadSphere, ' . htmlspecialchars($user['username']) . ' !</h1>
            <p>Merci de vous être inscrit sur notre plateforme. Nous sommes ravis de vous compter parmi nous.</p>
            <p>Vous pouvez dès maintenant :</p>
            <ul>
                <li>Ajouter vos livres préférés</li>
                <li>Partager vos avis avec la communauté</li>
                <li>Découvrir de nouvelles lectures</li>
            </ul>
            <p>Si vous avez des questions, n\'hésitez pas à répondre à cet email.</p>
            <p>À bientôt sur ReadSphere !</p>
            <p>L\'équipe ReadSphere</p>
        </div>
    </body>
    </html>';
    
    return send_email($user['email'], $subject, $body);
}

/**
 * Envoie un email de réinitialisation de mot de passe
 * 
 * @param array $user Tableau contenant les informations de l'utilisateur
 * @param string $token Jeton de réinitialisation
 * @return bool True si l'email a été envoyé avec succès, false sinon
 */
function send_password_reset_email($user, $token) {
    $reset_url = ($_ENV['APP_URL'] ?? 'http://localhost') . "/reset-password?token=" . urlencode($token);
    
    $subject = 'Réinitialisation de votre mot de passe ReadSphere';
    
    $body = '<!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>Réinitialisation de votre mot de passe</title>
    </head>
    <body>
        <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px;">
            <h1 style="color: #2563eb;">Réinitialisation de votre mot de passe</h1>
            <p>Bonjour ' . htmlspecialchars($user['username']) . ',</p>
            <p>Vous avez demandé à réinitialiser votre mot de passe. Cliquez sur le bouton ci-dessous pour en choisir un nouveau :</p>
            <p style="text-align: center; margin: 30px 0;">
                <a href="' . $reset_url . '" style="background-color: #2563eb; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block;">
                    Réinitialiser mon mot de passe
                </a>
            </p>
            <p>Si vous n\'avez pas demandé de réinitialisation de mot de passe, vous pouvez ignorer cet email en toute sécurité.</p>
            <p>Ce lien expirera dans 1 heure.</p>
            <p>L\'équipe ReadSphere</p>
        </div>
    </body>
    </html>';
    
    $altBody = "Bonjour " . $user['username'] . ",\n\n" .
              "Vous avez demandé à réinitialiser votre mot de passe. Veuillez cliquer sur le lien ci-dessous pour en choisir un nouveau :\n\n" .
              $reset_url . "\n\n" .
              "Si vous n'avez pas demandé de réinitialisation de mot de passe, vous pouvez ignorer cet email en toute sécurité.\n\n" .
              "Ce lien expirera dans 1 heure.\n\n" .
              "Cordialement,\nL'équipe ReadSphere";
    
    return send_email($user['email'], $subject, $body, $altBody);
}

/**
 * Envoie une notification de nouveau commentaire
 * 
 * @param array $commentateur Utilisateur qui a posté le commentaire
 * @param array $proprietaire Propriétaire du livre commenté
 * @param array $livre Livre commenté
 * @param string $commentaire Contenu du commentaire
 * @return bool True si l'email a été envoyé avec succès, false sinon
 */
function send_comment_notification($commentateur, $proprietaire, $livre, $commentaire) {
    $livre_url = ($_ENV['APP_URL'] ?? 'http://localhost') . "/book/" . $livre['id'] . '-' . create_slug($livre['titre']);
    
    $subject = 'Nouveau commentaire sur votre livre : ' . $livre['titre'];
    
    $body = '<!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>Nouveau commentaire</title>
    </head>
    <body>
        <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px;">
            <h1 style="color: #2563eb;">Nouveau commentaire sur votre livre</h1>
            <p>Bonjour ' . htmlspecialchars($proprietaire['username']) . ',</p>
            <p><strong>' . htmlspecialchars($commentateur['username']) . '</strong> a commenté votre livre <strong>' . htmlspecialchars($livre['titre']) . '</strong> :</p>
            <div style="background-color: #f3f4f6; padding: 15px; border-left: 4px solid #2563eb; margin: 15px 0;">
                ' . nl2br(htmlspecialchars($commentaire)) . '
            </div>
            <p style="text-align: center; margin: 30px 0;">
                <a href="' . $livre_url . '" style="background-color: #2563eb; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block;">
                    Voir le commentaire
                </a>
            </p>
            <p>Merci de partager vos lectures avec notre communauté !</p>
            <p>L\'équipe ReadSphere</p>
        </div>
    </body>
    </html>';
    
    $altBody = "Bonjour " . $proprietaire['username'] . ",\n\n" .
              $commentateur['username'] . " a commenté votre livre " . $livre['titre'] . " :\n\n" .
              $commentaire . "\n\n" .
              "Voir le commentaire : " . $livre_url . "\n\n" .
              "Merci de partager vos lectures avec notre communauté !\n\n" .
              "Cordialement,\nL'équipe ReadSphere";
    
    return send_email($proprietaire['email'], $subject, $body, $altBody);
}

/**
 * Crée un slug à partir d'une chaîne de caractères
 * 
 * @param string $string Chaîne à convertir en slug
 * @return string Slug généré
 */
function create_slug($string) {
    $slug = preg_replace('/[^\p{L}0-9]+/u', '-', $string);
    $slug = trim($slug, '-');
    $slug = iconv('utf-8', 'us-ascii//TRANSLIT', $slug);
    $slug = strtolower($slug);
    $slug = preg_replace('/[^-a-z0-9]+/', '', $slug);
    return $slug;
}
