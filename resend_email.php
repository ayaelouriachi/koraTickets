<?php
session_start();
require_once 'config.php';
require_once 'includes/functions.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

if (!isset($_GET['order_id'])) {
    header('Location: my_orders.php');
    exit;
}

$order_id = $_GET['order_id'];

try {
    $pdo = getDbConnection();
    
    // Récupérer les détails de la commande
    $stmt = $pdo->prepare("
        SELECT o.*, u.email, u.first_name, u.last_name
        FROM orders o
        JOIN users u ON o.user_id = u.id
        WHERE o.id = ? AND o.user_id = ?
    ");
    
    $stmt->execute([$order_id, $_SESSION['user_id']]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$order) {
        throw new Exception("Commande non trouvée");
    }
    
    // Récupérer les détails du ticket
    $stmt = $pdo->prepare("
        SELECT oi.*, tc.price, tc.available_quantity, m.home_team, m.away_team, m.date_time, m.stadium
        FROM order_items oi
        JOIN ticket_categories tc ON oi.ticket_category_id = tc.id
        JOIN matches m ON tc.match_id = m.id
        WHERE oi.order_id = ?
    ");
    
    $stmt->execute([$order_id]);
    $cart_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Générer le PDF
    $pdf_filename = generateTicketPDF($order_id, $_SESSION['user_id'], $order['total_amount'], $cart_items);
    
    // Envoyer l'email
    $emailSent = sendConfirmationEmail($order_id, $order['total_amount'], $pdf_filename);
    
    if ($emailSent) {
        header('Location: payment_success.php?order_id=' . $order_id . '&amount=' . $order['total_amount']);
        exit;
    } else {
        throw new Exception("Échec de l'envoi de l'email");
    }
} catch (Exception $e) {
    error_log("Erreur lors de la réexpédition d'email: " . $e->getMessage());
    header('Location: payment_error.php?error=' . urlencode($e->getMessage()));
    exit;
}
