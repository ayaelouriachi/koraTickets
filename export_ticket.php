<?php
session_start();
require_once 'config.php';
require_once 'includes/functions.php';
require_once 'includes/pdf_generator.php';

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Vérifier si l'ID de commande est fourni
if (!isset($_GET['order_id'])) {
    header('Location: my_orders.php');
    exit;
}

try {
    $order_id = $_GET['order_id'];
    
    // Vérifier si l'utilisateur est propriétaire de la commande
    $pdo = getDbConnection();
    $stmt = $pdo->prepare("
        SELECT o.*, u.email, u.phone, 
               tc.name as ticket_name, tc.price,
               m.home_team, m.away_team, m.match_date
        FROM orders o
        JOIN order_details od ON o.id = od.order_id
        JOIN ticket_categories tc ON od.ticket_category_id = tc.id
        JOIN matches m ON tc.match_id = m.id
        JOIN users u ON o.user_id = u.id
        WHERE o.id = ? AND o.user_id = ?
    ");
    $stmt->execute([$order_id, $_SESSION['user_id']]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$order) {
        throw new Exception("Cette commande ne vous appartient pas");
    }
    
    // Générer le PDF
    $pdf = new TicketPDF('P', 'mm', 'A4', true, 'UTF-8', false);
    $pdf->SetCreator('Football Tickets');
    $pdf->SetAuthor('Football Tickets');
    $pdf->SetTitle('Ticket de Football - ' . $order_id);
    $pdf->SetSubject('Ticket de Football');
    
    // Marges
    $pdf->SetMargins(15, 27, 15);
    $pdf->SetHeaderMargin(10);
    $pdf->SetFooterMargin(15);
    
    // Police
    $pdf->SetFont('freesans', '', 10);
    
    // Ajouter une page
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
    $pdf->Cell(0, 10, 'Prix : ' . formatPrice($order['price']) . ' MAD', 0, 1, 'L');
    $pdf->Ln(10);
    
    // Ajouter les informations du titulaire
    $pdf->SetFont('freesans', 'B', 12);
    $pdf->Cell(0, 10, 'Titulaire', 0, 1, 'L');
    
    $pdf->SetFont('freesans', '', 12);
    $pdf->Cell(0, 10, 'Email : ' . $order['email'], 0, 1, 'L');
    $pdf->Cell(0, 10, 'Téléphone : ' . $order['phone'], 0, 1, 'L');
    $pdf->Ln(10);
    
    // Ajouter un code QR unique
    $pdf->SetFont('freesans', 'B', 12);
    $pdf->Cell(0, 10, 'Code QR :', 0, 1, 'L');
    $pdf->write2DBarcode($order['order_id'], 'QRCODE,H', 15, $pdf->GetY(), 50, 50, array(), 'N');
    $pdf->Ln(20);
    
    // Générer le nom du fichier PDF
    $filename = 'ticket_' . $order_id . '_' . $_SESSION['user_id'] . '.pdf';
    
    // Définir les en-têtes pour le téléchargement
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="ticket_' . $order_id . '.pdf"');
    header('Cache-Control: private, max-age=0, must-revalidate');
    header('Pragma: public');
    
    // Envoyer le PDF
    $pdf->Output();
    exit;
    
} catch (Exception $e) {
    error_log("Erreur lors de l'export PDF: " . $e->getMessage());
    $_SESSION['error'] = "Erreur lors de l'export PDF: " . $e->getMessage();
    header('Location: payment_success.php?order_id=' . $order_id);
    exit;
}
