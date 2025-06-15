<?php
require_once 'config.php';
require_once 'includes/EmailSender.php';

error_log("[TEST] Début du test de configuration SMTP");

try {
    // Créer une instance de EmailSender
    $emailSender = new EmailSender();
    
    // Données de test
    $order = [
        'id' => 'TEST-' . date('YmdHis'),
        'user_id' => 1,
        'user_name' => 'Test User',
        'total_amount' => 100.00
    ];
    
    $orderDetails = [
        [
            'match_name' => 'Match Test',
            'match_date' => date('Y-m-d H:i:s', strtotime('+1 week')),
            'stadium' => 'Stade Test',
            'category_name' => 'VIP'
        ]
    ];
    
    // Créer un fichier PDF de test
    $testPdfPath = __DIR__ . '/tickets/test_ticket.pdf';
    if (!is_dir(__DIR__ . '/tickets')) {
        mkdir(__DIR__ . '/tickets', 0777, true);
    }
    
    // Créer un PDF simple pour le test
    $pdf = new TCPDF();
    $pdf->AddPage();
    $pdf->SetFont('helvetica', '', 12);
    $pdf->Cell(0, 10, 'Test Ticket', 0, 1, 'C');
    $pdf->Output($testPdfPath, 'F');
    
    // Envoyer l'email de test
    $emailSender->sendTicketEmail(
        SMTP_USERNAME, // Utiliser l'email de l'expéditeur comme destinataire pour le test
        $order,
        $orderDetails,
        $testPdfPath
    );
    
    echo "Test réussi ! Email envoyé à " . SMTP_USERNAME . "\n";
    error_log("[TEST] Test SMTP réussi !");
    
} catch (Exception $e) {
    echo "Erreur : " . $e->getMessage() . "\n";
    error_log("[ERROR] Test SMTP échoué : " . $e->getMessage());
    error_log("[ERROR] Trace : " . $e->getTraceAsString());
} 