<?php
require_once 'config.php';
require_once 'includes/functions.php';
require_once 'includes/TicketGenerator.php';
require_once 'includes/EmailSender.php';

error_log("[TEST] Début du test complet");

try {
    // Simuler une commande
    $order = [
        'id' => 'TEST-' . time(),
        'user_id' => 1,
        'total_amount' => 100,
        'name' => 'Test User',
        'email' => SMTP_FROM_EMAIL // Utiliser votre email pour le test
    ];

    // Simuler les détails de la commande
    $orderDetails = [
        [
            'match_name' => 'Match Test',
            'match_date' => '2024-06-15 20:00:00',
            'stadium' => 'Stade Test',
            'category_name' => 'Catégorie VIP',
            'quantity' => 2,
            'price' => 50
        ]
    ];

    error_log("[TEST] Génération du ticket PDF");
    
    // Créer le dossier tickets s'il n'existe pas
    if (!is_dir('tickets')) {
        mkdir('tickets', 0755, true);
        error_log("[TEST] Dossier tickets créé");
    }

    // Générer le PDF
    $ticketGenerator = new TicketGenerator($order, [
        'name' => $order['name'],
        'email' => $order['email']
    ], $orderDetails);
    
    $pdfPath = $ticketGenerator->generate();
    error_log("[TEST] PDF généré : " . $pdfPath);

    if (!file_exists($pdfPath)) {
        throw new Exception("Le fichier PDF n'a pas été créé : " . $pdfPath);
    }

    error_log("[TEST] Envoi de l'email");
    
    // Envoyer l'email
    $emailSender = new EmailSender();
    $emailSender->sendTicketEmail($order['email'], $order, $orderDetails, $pdfPath);
    
    error_log("[TEST] Email envoyé avec succès");
    
    echo "Test réussi ! Vérifiez votre email et les logs pour plus de détails.";

} catch (Exception $e) {
    error_log("[TEST][ERROR] Une erreur est survenue : " . $e->getMessage());
    error_log("[TEST][ERROR] Trace : " . $e->getTraceAsString());
    echo "Erreur pendant le test. Vérifiez les logs pour plus de détails.";
} 