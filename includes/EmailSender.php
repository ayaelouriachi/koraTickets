<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

class EmailSender {
    private $mailer;

    public function __construct() {
        error_log("[DEBUG] Initialisation de EmailSender");
        $this->initializeMailer();
    }

    private function initializeMailer() {
        try {
            error_log("[DEBUG] Configuration du mailer SMTP");
            $this->mailer = new PHPMailer(true);
            
            // Configuration du serveur
            $this->mailer->isSMTP();
            
            // Débogage SMTP
            if (defined('SMTP_DEBUG') && SMTP_DEBUG) {
                $this->mailer->SMTPDebug = SMTP::DEBUG_SERVER;
                $this->mailer->Debugoutput = function($str, $level) {
                    error_log("[SMTP DEBUG] [$level] : $str");
                };
            }
            
            error_log("[DEBUG] Configuration SMTP - Host: " . SMTP_HOST . ", Port: " . SMTP_PORT);
            $this->mailer->Host = SMTP_HOST;
            $this->mailer->SMTPAuth = true;
            $this->mailer->Username = SMTP_USERNAME;
            $this->mailer->Password = SMTP_PASSWORD;
            $this->mailer->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $this->mailer->Port = SMTP_PORT;
            $this->mailer->CharSet = 'UTF-8';
            
            // Options SSL/TLS supplémentaires
            $this->mailer->SMTPOptions = array(
                'ssl' => array(
                    'verify_peer' => defined('SMTP_VERIFY_PEER') ? SMTP_VERIFY_PEER : true,
                    'verify_peer_name' => defined('SMTP_VERIFY_PEER_NAME') ? SMTP_VERIFY_PEER_NAME : true,
                    'allow_self_signed' => defined('SMTP_ALLOW_SELF_SIGNED') ? SMTP_ALLOW_SELF_SIGNED : false
                )
            );
            
            error_log("[DEBUG] Configuration de l'expéditeur: " . SMTP_FROM_EMAIL);
            // Expéditeur
            $this->mailer->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
            
            error_log("[DEBUG] Mailer initialisé avec succès");
        } catch (Exception $e) {
            error_log("[ERROR] Erreur lors de l'initialisation du mailer: " . $e->getMessage());
            throw $e;
        }
    }

    private function getEmailTemplate($user, $order, $orderDetails) {
        $baseUrl = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
        $baseUrl .= isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost';
        
        $matchDetails = '';
        foreach ($orderDetails as $detail) {
            $matchDetails .= '
                <tr>
                    <td style="padding: 10px; border: 1px solid #ddd;">' . htmlspecialchars($detail['match_name']) . '</td>
                    <td style="padding: 10px; border: 1px solid #ddd;">' . htmlspecialchars($detail['match_date']) . '</td>
                    <td style="padding: 10px; border: 1px solid #ddd;">' . htmlspecialchars($detail['stadium']) . '</td>
                    <td style="padding: 10px; border: 1px solid #ddd;">' . htmlspecialchars($detail['category_name']) . '</td>
                </tr>';
        }
        
        return '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background-color: #4CAF50; color: white; padding: 20px; text-align: center; }
                .content { padding: 20px; background-color: #f9f9f9; }
                .footer { text-align: center; padding: 20px; font-size: 12px; color: #666; }
                .button { display: inline-block; padding: 10px 20px; background-color: #4CAF50; color: white; text-decoration: none; border-radius: 5px; }
                table { width: 100%; border-collapse: collapse; margin: 20px 0; }
                th, td { padding: 10px; border: 1px solid #ddd; }
                th { background-color: #4CAF50; color: white; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h1>Confirmation de votre commande</h1>
                </div>
                <div class="content">
                    <p>Bonjour ' . htmlspecialchars($user['name']) . ',</p>
                    <p>Nous vous remercions pour votre commande sur KoraTickets.</p>
                    <h2>Détails de votre commande :</h2>
                    <ul>
                        <li>Numéro de commande : ' . htmlspecialchars($order['id']) . '</li>
                        <li>Montant total : ' . number_format($order['total_amount'], 2) . ' MAD</li>
                    </ul>
                    
                    <h3>Vos billets :</h3>
                    <table>
                        <thead>
                            <tr>
                                <th>Match</th>
                                <th>Date</th>
                                <th>Stade</th>
                                <th>Catégorie</th>
                            </tr>
                        </thead>
                        <tbody>
                            ' . $matchDetails . '
                        </tbody>
                    </table>
                    
                    <p>Vous trouverez ci-joint votre ticket au format PDF.</p>
                    <p>Pour accéder au stade :</p>
                    <ol>
                        <li>Présentez votre ticket (imprimé ou sur votre téléphone)</li>
                        <li>Ayez une pièce d\'identité valide</li>
                        <li>Arrivez au moins 1 heure avant le début du match</li>
                    </ol>
                    <p>
                        <a href="' . $baseUrl . '/download_ticket.php?order_id=' . urlencode($order['id']) . '&token=' . $this->generateDownloadToken($order) . '" class="button">
                            Télécharger le ticket
                        </a>
                    </p>
                </div>
                <div class="footer">
                    <p>Pour toute question, contactez notre support client.</p>
                    <p>© ' . date('Y') . ' KoraTickets. Tous droits réservés.</p>
                </div>
            </div>
        </body>
        </html>';
    }

    private function generateDownloadToken($order) {
        return hash('sha256', $order['id'] . $order['user_id'] . date('Ymd'));
    }

    public function sendTicketEmail($to, $order, $orderDetails, $ticketPath) {
        try {
            error_log("[DEBUG] Début de sendTicketEmail");
            error_log("[DEBUG] Destinataire: $to");
            error_log("[DEBUG] Commande: " . print_r($order, true));
            error_log("[DEBUG] Détails: " . print_r($orderDetails, true));
            error_log("[DEBUG] Chemin du ticket: $ticketPath");
            
            // Réinitialiser toutes les adresses
            $this->mailer->clearAddresses();
            $this->mailer->clearAttachments();
            
            // Destinataire
            $this->mailer->addAddress($to);
            
            // Contenu
            $this->mailer->isHTML(true);
            $this->mailer->Subject = 'Confirmation de commande - KoraTickets #' . $order['id'];
            $this->mailer->Body = $this->getEmailTemplate(
                ['name' => $order['name'] ?? 'Client', 'id' => $order['user_id']],
                $order,
                $orderDetails
            );
            $this->mailer->AltBody = strip_tags(str_replace(['<br>', '</p>'], "\n", $this->mailer->Body));
            
            // Pièce jointe
            if (file_exists($ticketPath)) {
                error_log("[DEBUG] Ajout de la pièce jointe : " . $ticketPath);
                $this->mailer->addAttachment($ticketPath, 'ticket_' . $order['id'] . '.pdf');
            } else {
                error_log("[ERROR] Le fichier de ticket n'existe pas : " . $ticketPath);
                throw new Exception("Le fichier de ticket n'existe pas : " . $ticketPath);
            }
            
            // Envoi
            $result = $this->mailer->send();
            error_log("[DEBUG] Email envoyé avec succès à : " . $to);
            return $result;
        } catch (Exception $e) {
            error_log("[ERROR] Erreur lors de l'envoi de l'email : " . $e->getMessage());
            error_log("[ERROR] Détails de l'erreur SMTP : " . $this->mailer->ErrorInfo);
            throw $e;
        }
    }
} 