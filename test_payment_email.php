<?php
session_start();
require_once 'vendor/autoload.php';
require_once 'config.php';
require_once 'includes/EmailSender.php';
require_once 'includes/TicketGenerator.php';

// Activer l'affichage des erreurs
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Test d'envoi d'email après paiement\n";
echo "--------------------------------\n\n";

// Simuler les données de commande
$order = [
    'id' => 'TEST-' . date('YmdHis'),
    'total_amount' => 100,
    'email' => SMTP_USERNAME, // Utiliser votre email pour le test
    'name' => 'Test User'
];

$orderDetails = [
    [
        'match_name' => 'Match Test',
        'match_date' => date('Y-m-d H:i:s'),
        'stadium' => 'Stade Test',
        'category_name' => 'Catégorie Test',
        'quantity' => 2,
        'price' => 50
    ]
];

try {
    // Créer une instance de EmailSender
    $emailSender = new EmailSender();
    
    // Générer le ticket avec TicketGenerator
    $ticketGenerator = new TicketGenerator($order, [
        'name' => $order['name'],
        'email' => $order['email']
    ]);
    $pdfPath = $ticketGenerator->generate();
    
    if (!file_exists($pdfPath)) {
        throw new Exception("Erreur lors de la génération du PDF");
    }
    
    echo "PDF généré : $pdfPath\n\n";
    
    // Envoyer l'email
    $result = $emailSender->sendTicketEmail($order['email'], $order, $orderDetails, $pdfPath);
    
    if ($result) {
        echo "\n✅ Email envoyé avec succès!\n";
    } else {
        echo "\n❌ Échec de l'envoi de l'email\n";
    }

} catch (Exception $e) {
    echo "\n❌ Erreur : " . $e->getMessage() . "\n";
    error_log("Erreur test email : " . $e->getMessage());
} 