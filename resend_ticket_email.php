<?php
session_start();
require_once 'config.php';
require_once 'includes/functions.php';
require_once 'includes/TicketGenerator.php';
require_once 'includes/EmailSender.php';

header('Content-Type: application/json');

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Utilisateur non connecté']);
    exit;
}

// Vérifier les paramètres
if (!isset($_POST['order_id']) || !isset($_POST['paypal_order_id'])) {
    echo json_encode(['success' => false, 'message' => 'Paramètres manquants']);
    exit;
}

$order_id = $_POST['order_id'];
$paypal_order_id = $_POST['paypal_order_id'];

try {
    error_log("[DEBUG] Tentative de renvoi d'email - Order ID: $order_id, PayPal Order ID: $paypal_order_id");
    
    // Récupérer les informations de la commande
    $pdo = getDbConnection();
    $stmt = $pdo->prepare("
        SELECT o.*, u.email, u.name
        FROM orders o
        JOIN users u ON o.user_id = u.id
        WHERE o.id = ? AND o.paypal_transaction_id = ? AND o.user_id = ?
    ");
    $stmt->execute([$order_id, $paypal_order_id, $_SESSION['user_id']]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$order) {
        error_log("[ERROR] Commande non trouvée ou non autorisée");
        echo json_encode(['success' => false, 'message' => 'Commande non trouvée']);
        exit;
    }
    
    error_log("[DEBUG] Commande trouvée: " . print_r($order, true));
    
    // Récupérer les détails de la commande
    $stmt = $pdo->prepare("
        SELECT od.*, tc.category_name, m.name as match_name, 
               m.date as match_date, s.name as stadium
        FROM order_details od
        JOIN ticket_categories tc ON od.ticket_category_id = tc.id
        JOIN matches m ON tc.match_id = m.id
        JOIN stadiums s ON m.stadium_id = s.id
        WHERE od.order_id = ?
    ");
    $stmt->execute([$order_id]);
    $orderDetails = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($orderDetails)) {
        error_log("[ERROR] Aucun détail trouvé pour la commande");
        echo json_encode(['success' => false, 'message' => 'Détails de la commande non trouvés']);
        exit;
    }
    
    error_log("[DEBUG] Détails de la commande: " . print_r($orderDetails, true));
    
    // Générer le PDF du ticket
    $ticketGenerator = new TicketGenerator($order, [
        'name' => $order['name'],
        'email' => $order['email']
    ]);
    $pdfPath = $ticketGenerator->generate();
    
    if (!file_exists($pdfPath)) {
        error_log("[ERROR] Le fichier PDF n'a pas été généré: $pdfPath");
        echo json_encode(['success' => false, 'message' => 'Erreur lors de la génération du ticket']);
        exit;
    }
    
    error_log("[DEBUG] PDF généré: $pdfPath");
    
    // Envoyer l'email
    $emailSender = new EmailSender();
    $emailSender->sendTicketEmail($order['email'], $order, $orderDetails, $pdfPath);
    
    error_log("[DEBUG] Email envoyé avec succès à " . $order['email']);
    echo json_encode(['success' => true, 'message' => 'Email envoyé avec succès']);
    
} catch (Exception $e) {
    error_log("[ERROR] Erreur lors du renvoi de l'email: " . $e->getMessage());
    error_log("[ERROR] Trace: " . $e->getTraceAsString());
    echo json_encode(['success' => false, 'message' => 'Une erreur est survenue lors de l\'envoi de l\'email']);
} 