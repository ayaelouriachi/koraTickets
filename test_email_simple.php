<?php
require_once 'vendor/autoload.php';
require_once 'config.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Activer l'affichage des erreurs
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Test d'envoi d'email simple\n";
echo "------------------------\n\n";

try {
    $mail = new PHPMailer(true);

    // Debug mode
    $mail->SMTPDebug = SMTP::DEBUG_SERVER;
    $mail->Debugoutput = 'echo';

    // Configuration SMTP
    $mail->isSMTP();
    $mail->Host = SMTP_HOST;
    $mail->SMTPAuth = true;
    $mail->Username = SMTP_USERNAME;
    $mail->Password = SMTP_PASSWORD;
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = SMTP_PORT;
    
    // Pour les serveurs locaux
    $mail->SMTPOptions = array(
        'ssl' => array(
            'verify_peer' => false,
            'verify_peer_name' => false,
            'allow_self_signed' => true
        )
    );

    // Expéditeur et destinataire
    $mail->setFrom(SMTP_FROM_EMAIL, 'Test KoraTickets');
    $mail->addAddress($_SESSION['user_email']); // Email de l'utilisateur connecté

    // Contenu
    $mail->isHTML(true);
    $mail->Subject = 'Test - KoraTickets';
    $mail->Body = '
        <h2>Test d\'envoi d\'email</h2>
        <p>Ceci est un test d\'envoi d\'email depuis KoraTickets.</p>
        <p>Si vous recevez cet email, cela signifie que la configuration SMTP fonctionne correctement.</p>
    ';

    // Envoyer l'email
    $mail->send();
    echo "\nEmail envoyé avec succès !\n";

} catch (Exception $e) {
    echo "\nErreur lors de l'envoi de l'email : " . $mail->ErrorInfo . "\n";
    error_log("Erreur d'envoi d'email : " . $mail->ErrorInfo);
} 