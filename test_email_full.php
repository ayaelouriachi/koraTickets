<?php
require_once 'config.php';
require_once 'includes/functions.php';
require_once 'includes/TicketGenerator.php';
require_once 'includes/EmailSender.php';

error_log("[TEST] Début du test d'envoi d'email complet");

try {
    // 1. Créer une commande de test
    $order = [
        'id' => 'TEST-' . date('YmdHis'),
        'user_id' => 1,
        'user_name' => 'Test User',
        'total_amount' => 100.00,
        'details' => [
            [
                'match_name' => 'Match Test',
                'match_date' => date('Y-m-d H:i:s', strtotime('+1 week')),
                'stadium' => 'Stade Test',
                'category' => 'VIP',
                'category_name' => 'VIP'
            ]
        ]
    ];
    
    error_log("[TEST] Commande de test créée: " . print_r($order, true));
    
    // 2. Générer le PDF du ticket
    error_log("[TEST] Génération du PDF");
    $ticketGenerator = new TicketGenerator($order, ['name' => $order['user_name']]);
    $pdfPath = $ticketGenerator->generate();
    error_log("[TEST] PDF généré: " . $pdfPath);
    
    // 3. Envoyer l'email
    error_log("[TEST] Initialisation de l'envoi d'email");
    $emailSender = new EmailSender();
    
    // Email de test - REMPLACER PAR VOTRE EMAIL
    $testEmail = 'armyb4810@gmail.com';
    error_log("[TEST] Tentative d'envoi à: " . $testEmail);
    
    $emailSender->sendTicketEmail(
        $testEmail,
        $order,
        $order['details'],
        $pdfPath
    );
    
    error_log("[TEST] Test réussi !");
    echo "Test réussi ! Vérifiez les logs pour plus de détails.";
    
} catch (Exception $e) {
    error_log("[TEST ERROR] " . $e->getMessage());
    error_log("[TEST ERROR] Trace: " . $e->getTraceAsString());
    echo "Erreur lors du test. Vérifiez les logs pour plus de détails.";
} 