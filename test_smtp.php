<?php
require_once 'vendor/autoload.php';
require_once 'config.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

echo "Test de configuration SMTP\n";
echo "-------------------------\n";

try {
    $mail = new PHPMailer(true);

    echo "Configuration actuelle:\n";
    echo "Host: " . SMTP_HOST . "\n";
    echo "Port: " . SMTP_PORT . "\n";
    echo "Username: " . SMTP_USERNAME . "\n";
    echo "From Email: " . SMTP_FROM_EMAIL . "\n";

    // Debug mode
    $mail->SMTPDebug = SMTP::DEBUG_SERVER;
    $mail->Debugoutput = function($str, $level) {
        echo "[$level] $str\n";
    };

    // Configuration SMTP
    $mail->isSMTP();
    $mail->Host = SMTP_HOST;
    $mail->SMTPAuth = true;
    $mail->Username = SMTP_USERNAME;
    $mail->Password = SMTP_PASSWORD;
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = SMTP_PORT;

    // Configuration pour les serveurs locaux/dev
    $mail->SMTPOptions = array(
        'ssl' => array(
            'verify_peer' => false,
            'verify_peer_name' => false,
            'allow_self_signed' => true
        )
    );

    // Expéditeur et destinataire
    $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
    $mail->addAddress(SMTP_USERNAME); // On envoie à la même adresse pour le test

    // Contenu
    $mail->isHTML(true);
    $mail->Subject = 'Test SMTP - KoraTickets';
    $mail->Body = '<h1>Test de configuration SMTP</h1><p>Si vous recevez cet email, la configuration SMTP fonctionne correctement.</p>';

    // Envoi
    $mail->send();
    echo "\nEmail envoyé avec succès!\n";

} catch (Exception $e) {
    echo "\nErreur lors de l'envoi de l'email: " . $mail->ErrorInfo . "\n";
    error_log("Erreur SMTP: " . $mail->ErrorInfo);
} 