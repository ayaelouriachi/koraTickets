<?php
// Démarrer la mise en mémoire tampon
ob_start();

session_start();
require_once 'config.php';
require_once 'includes/functions.php';
require_once 'vendor/autoload.php';

// Importer TCPDF correctement
use \TCPDF;

// Désactiver l'affichage des erreurs pour la génération du PDF
error_reporting(0);

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Vérifier si l'ID de commande est fourni
if (!isset($_GET['order_id'])) {
    $_SESSION['error'] = "ID de commande manquant";
    header('Location: my_orders.php');
    exit;
}

try {
    // Nettoyer la mémoire tampon avant de générer le PDF
    ob_clean();
    
    $order_id = (int)$_GET['order_id'];
    
    // Récupérer les détails de la commande avec les informations du match
    $pdo = getDbConnection();
    $stmt = $pdo->prepare("
        SELECT o.*, u.email, u.username,
               CONCAT(m.home_team, ' vs ', m.away_team) as match_name,
               m.match_date, m.home_team, m.away_team,
               m.stadium as stadium_name,
               tc.name as category_name, tc.price
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
    
    // Créer une nouvelle instance de TCPDF
    $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
    
    // Configurer le document
    $pdf->SetCreator('Football Tickets');
    $pdf->SetAuthor('Football Tickets');
    $pdf->SetTitle('Ticket - ' . $order['match_name']);
    
    // Supprimer les en-têtes et pieds de page par défaut
    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);
    
    // Définir les marges
    $pdf->SetMargins(15, 15, 15);
    
    // Ajouter une page
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
    
    // Ajouter un code QR unique
    $qr_style = array(
        'border' => false,
        'vpadding' => 'auto',
        'hpadding' => 'auto',
        'fgcolor' => array(0,0,0),
        'bgcolor' => false,
        'module_width' => 1,
        'module_height' => 1
    );
    $pdf->write2DBarcode($order_id, 'QRCODE,H', 15, $pdf->GetY(), 50, 50, $qr_style, 'N');
    
    // Envoyer le PDF au navigateur
    $pdf->Output('ticket_' . $order_id . '.pdf', 'D');
    exit;
    
} catch (Exception $e) {
    error_log("Erreur lors de l'export PDF: " . $e->getMessage());
    $_SESSION['error'] = "Erreur de traitement: " . $e->getMessage();
    header('Location: my_orders.php');
    exit;
}
