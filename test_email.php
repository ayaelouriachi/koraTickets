<?php
require_once 'vendor/autoload.php';
require_once 'config.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

$mail = new PHPMailer(true);

try {
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
    
    // Configuration de l'expéditeur
    $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
    
    // Ajouter un destinataire
    $mail->addAddress('armyb4810@gmail.com', 'Nom du destinataire');
    
    // Contenu de l'email
    $mail->isHTML(true);
    $mail->Subject = 'Test d\'envoi d\'email - KoraTickets';
    $mail->Body = 'Ceci est un test d\'envoi d\'email pour KoraTickets.\n\nVeuillez vérifier que vous recevez ce message.';
    
    // Activer le débogage SMTP
    $mail->SMTPDebug = SMTP::DEBUG_SERVER;
    
    $mail->send();
    echo '✅ Email envoyé avec succès!';
    
} catch (Exception $e) {
    echo "❌ Erreur: " . $mail->ErrorInfo;
    error_log("Erreur d'envoi d'email: " . $e->getMessage());
}
?>
