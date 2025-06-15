<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once 'config.php';
require_once 'includes/functions.php';
require_once 'includes/mail_config.php';
require_once 'vendor/autoload.php';

use TCPDF;

// Vérification de la session et CSRF
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Vérification de la méthode et de l'ID de commande
$order_id = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;
if (!$order_id) {
    $_SESSION['error'] = "ID de commande invalide";
    header('Location: my_orders.php');
    exit;
}

try {
    $pdo = getDbConnection();
    
    // Vérification que l'utilisateur a le droit d'accéder à cette commande
    $stmt = $pdo->prepare("
        SELECT o.*, u.email, u.username,
               CONCAT(m.home_team, ' vs ', m.away_team) as match_name,
               m.match_date, m.stadium as stadium_name,
               tc.name as category_name
        FROM orders o
        JOIN users u ON o.user_id = u.id
        LEFT JOIN order_details od ON o.id = od.order_id
        LEFT JOIN ticket_categories tc ON od.ticket_category_id = tc.id
        LEFT JOIN matches m ON tc.match_id = m.id
        WHERE o.id = ? AND o.user_id = ? AND o.payment_status = 'completed'
        LIMIT 1
    ");
    
    if (!$stmt->execute([$order_id, $_SESSION['user_id']])) {
        throw new Exception("Erreur lors de la récupération de la commande: " . implode(", ", $stmt->errorInfo()));
    }
    
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$order) {
        throw new Exception("Commande introuvable ou accès non autorisé");
    }

    // Si le nom du stade n'est pas défini, utiliser une valeur par défaut
    $stadium_name = !empty($order['stadium_name']) ? $order['stadium_name'] : 'Stade Municipal';
    
    // Création du dossier temporaire si nécessaire
    $temp_dir = __DIR__ . '/temp';
    if (!file_exists($temp_dir)) {
        if (!mkdir($temp_dir, 0777, true)) {
            throw new Exception("Impossible de créer le dossier temporaire");
        }
    }
    
    if (!is_writable($temp_dir)) {
        throw new Exception("Le dossier temporaire n'est pas accessible en écriture");
    }
    
    $pdf_path = $temp_dir . '/ticket_' . $order_id . '_' . time() . '.pdf';
    
    // Génération du PDF avec TCPDF
    $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
    $pdf->SetCreator('Football Tickets');
    $pdf->SetAuthor('Football Tickets');
    $pdf->SetTitle('Ticket - ' . $order['match_name']);
    
    // En-tête et pied de page
    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);
    
    // Ajout d'une page
    $pdf->AddPage();
    
    // Style CSS pour le ticket
    $style = '
    <style>
        .ticket {
            border: 2px solid #000;
            padding: 15px;
            margin-bottom: 20px;
            font-family: Arial, sans-serif;
        }
        .ticket-header {
            text-align: center;
            border-bottom: 1px solid #ccc;
            padding-bottom: 10px;
            margin-bottom: 15px;
        }
        .ticket-info {
            margin: 10px 0;
        }
        .match-name {
            font-size: 18px;
            font-weight: bold;
            color: #003366;
            margin: 10px 0;
        }
        .ticket-number {
            background-color: #f8f9fa;
            padding: 5px;
            margin: 10px 0;
            text-align: center;
        }
    </style>';
    
    // Contenu du PDF
    $ticket_number = strtoupper(substr(md5($order_id . time()), 0, 10));
    $html = $style . '
    <div class="ticket">
        <div class="ticket-header">
            <h1>Football Tickets</h1>
            <div class="match-name">' . htmlspecialchars($order['match_name']) . '</div>
        </div>
        <div class="ticket-info">
            <p><strong>Stade:</strong> ' . htmlspecialchars($stadium_name) . '</p>
            <p><strong>Date:</strong> ' . date('d/m/Y H:i', strtotime($order['match_date'])) . '</p>
            <p><strong>Catégorie:</strong> ' . htmlspecialchars($order['category_name']) . '</p>
            <p><strong>Client:</strong> ' . htmlspecialchars($order['username']) . '</p>
            <p><strong>Email:</strong> ' . htmlspecialchars($order['email']) . '</p>
            <p><strong>Commande:</strong> #' . $order_id . '</p>
            <p><strong>Montant:</strong> ' . number_format($order['total_amount'], 2) . ' MAD</p>
        </div>
        <div class="ticket-number">
            <strong>N° Ticket:</strong> ' . $ticket_number . '
        </div>
    </div>';
    
    $pdf->writeHTML($html, true, false, true, false, '');
    
    // Sauvegarde du PDF
    $pdf->Output($pdf_path, 'F');
    
    // Tentative d'envoi par PHPMailer
    try {
        // Envoi de l'email
        $subject = "Vos tickets - " . $order['match_name'];
        $body = '
        <html>
        <body style="font-family: Arial, sans-serif; line-height: 1.6;">
            <h2>Vos tickets de match</h2>
            <p>Bonjour ' . htmlspecialchars($order['username']) . ',</p>
            <p>Vous trouverez ci-joint vos tickets pour le match :</p>
            <div style="background: #f5f5f5; padding: 15px; margin: 20px 0;">
                <p><strong>' . htmlspecialchars($order['match_name']) . '</strong></p>
                <p>Date : ' . date('d/m/Y H:i', strtotime($order['match_date'])) . '</p>
                <p>Numéro de commande : #' . $order_id . '</p>
            </div>
            <p>Nous vous souhaitons un excellent match !</p>
            <p>Cordialement,<br>L\'équipe Football Tickets</p>
        </body>
        </html>';
        
        $attachments = [
            [
                'path' => $pdf_path,
                'name' => 'ticket_' . $order_id . '.pdf'
            ]
        ];
        
        $result = sendEmail($order['email'], $subject, $body, $attachments);
        
        if (!$result['success']) {
            // Si PHPMailer échoue, essayer avec mail() natif
            $boundary = md5(time());
            $headers = array(
                'MIME-Version: 1.0',
                'Content-Type: multipart/mixed; boundary=' . $boundary,
                'From: Football Tickets <noreply@footballtickets.com>'
            );

            $message = '--' . $boundary . "\r\n";
            $message .= 'Content-Type: text/html; charset=utf-8' . "\r\n\r\n";
            $message .= $body . "\r\n\r\n";

            // Ajouter le PDF en pièce jointe
            if (file_exists($pdf_path)) {
                $attachment = chunk_split(base64_encode(file_get_contents($pdf_path)));
                $message .= '--' . $boundary . "\r\n";
                $message .= 'Content-Type: application/pdf; name="ticket_' . $order_id . '.pdf"' . "\r\n";
                $message .= 'Content-Disposition: attachment; filename="ticket_' . $order_id . '.pdf"' . "\r\n";
                $message .= 'Content-Transfer-Encoding: base64' . "\r\n\r\n";
                $message .= $attachment . "\r\n";
            }

            $message .= '--' . $boundary . '--';

            // Envoyer l'email avec mail() natif
            if (!mail($order['email'], $subject, $message, implode("\r\n", $headers))) {
                throw new Exception("Échec de l'envoi de l'email avec mail() natif");
            }
        }
        
        $_SESSION['success'] = "Les tickets ont été envoyés à votre adresse email";
        
    } catch (Exception $e) {
        error_log("Erreur lors de l'envoi de l'email : " . $e->getMessage());
        throw new Exception("Erreur lors de l'envoi de l'email : " . $e->getMessage());
    }
    
    // Nettoyage du fichier temporaire
    if (file_exists($pdf_path)) {
        unlink($pdf_path);
    }
    
} catch (Exception $e) {
    error_log("Erreur lors de l'envoi du ticket : " . $e->getMessage());
    $_SESSION['error'] = "Une erreur est survenue lors de l'envoi du ticket : " . $e->getMessage();
}

header('Location: my_orders.php');
exit;
