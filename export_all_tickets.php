<?php
// Désactiver l'affichage des erreurs
error_reporting(0);
ini_set('display_errors', 0);

// Désactiver les avertissements
ini_set('error_reporting', E_ALL ^ E_WARNING);

session_start();
require_once 'config.php';
require_once 'includes/functions.php';
require_once 'includes/pdf_generator.php';

// Désactiver l'output buffering
while (ob_get_level()) {
    ob_end_clean();
}

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

try {
    $pdo = getDbConnection();
    
    // Récupérer toutes les commandes payées de l'utilisateur
    $stmt = $pdo->prepare("
        SELECT o.*, tc.name as ticket_name, m.home_team, m.away_team, m.match_date
        FROM orders o
        JOIN order_details od ON o.id = od.order_id
        JOIN ticket_categories tc ON od.ticket_category_id = tc.id
        JOIN matches m ON tc.match_id = m.id
        WHERE o.user_id = ? AND o.payment_status = 'completed'
        ORDER BY o.created_at DESC
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($orders)) {
        throw new Exception("Aucune commande payée trouvée");
    }
    
    // Créer un PDF avec tous les tickets
    $pdf = new TicketPDF('P', 'mm', 'A4', true, 'UTF-8', false);
    $pdf->SetCreator('Football Tickets');
    $pdf->SetAuthor('Football Tickets');
    $pdf->SetTitle('Mes Tickets de Football');
    $pdf->SetSubject('Tickets de Football');
    
    // Marges
    $pdf->SetMargins(15, 27, 15);
    $pdf->SetHeaderMargin(10);
    $pdf->SetFooterMargin(15);
    
    // Police
    $pdf->SetFont('freesans', '', 10);
    
    // Ajouter une page de couverture
    $pdf->AddPage();
    $pdf->SetFont('freesans', 'B', 20);
    $pdf->Cell(0, 10, 'MES TICKETS DE FOOTBALL', 0, 1, 'C');
    $pdf->Ln(20);
    
    $pdf->SetFont('freesans', '', 12);
    $pdf->MultiCell(0, 10, "Voici tous vos tickets de football. Chaque ticket est valide pour une entrée au stade.\nVeuillez conserver ce document pour référence.", 0, 'C');
    $pdf->Ln(20);
    
    // Générer une page pour chaque ticket
    foreach ($orders as $order) {
        $pdf->AddPage();
        
        // Ajouter les informations du match
        $pdf->SetFont('freesans', 'B', 14);
        $pdf->Cell(0, 10, $order['home_team'] . ' vs ' . $order['away_team'], 0, 1, 'C');
        $pdf->Ln(10);
        
        $pdf->SetFont('freesans', '', 12);
        $pdf->Cell(0, 10, 'Date : ' . date('d/m/Y H:i', strtotime($order['match_date'])), 0, 1, 'C');
        $pdf->Ln(10);
        
        // Ajouter les informations du ticket
        $pdf->SetFont('freesans', 'B', 12);
        $pdf->Cell(0, 10, 'Catégorie : ' . $order['ticket_name'], 0, 1, 'L');
        
        $pdf->SetFont('freesans', '', 12);
        $pdf->Cell(0, 10, 'ID de commande : ' . $order['order_id'], 0, 1, 'L');
        $pdf->Ln(10);
        
        // Ajouter un code QR unique
        $pdf->SetFont('freesans', 'B', 12);
        $pdf->Cell(0, 10, 'Code QR :', 0, 1, 'L');
        $pdf->write2DBarcode($order['order_id'], 'QRCODE,H', 15, $pdf->GetY(), 50, 50, array(), 'N');
        $pdf->Ln(20);
    }
    
    // Générer le nom du fichier PDF
    $filename = 'tickets_' . date('Y-m-d_H-i-s') . '_' . $_SESSION['user_id'] . '.pdf';
    
    // Définir les en-têtes pour le téléchargement
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: private, max-age=0, must-revalidate');
    header('Pragma: public');
    
    // Envoyer le PDF
    $pdf->Output($filename, 'D');
    
} catch (Exception $e) {
    error_log("Erreur lors de l'export PDF: " . $e->getMessage());
    $_SESSION['error'] = "Erreur lors de l'export PDF: " . $e->getMessage();
    header('Location: my_orders.php');
    exit;
}
?>
