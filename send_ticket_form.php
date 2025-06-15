<?php
session_start();
require_once 'config.php';
require_once 'includes/functions.php';
require_once 'includes/TicketGenerator.php';
require_once 'includes/EmailSender.php';

// Vérifier si le formulaire a été soumis
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Récupérer les données du formulaire
        $order_id = $_POST['order_id'];
        $email = $_POST['email'];
        
        // Vérifier si l'utilisateur est connecté
        if (!isset($_SESSION['user_id'])) {
            throw new Exception("Vous devez être connecté pour envoyer un ticket");
        }
        
        // Récupérer les informations de la commande
        $pdo = getDbConnection();
        $stmt = $pdo->prepare("
            SELECT o.*, u.name as user_name, u.email as user_email
            FROM orders o
            JOIN users u ON o.user_id = u.id
            WHERE o.id = ?
        ");
        $stmt->execute([$order_id]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$order) {
            throw new Exception("Commande non trouvée");
        }
        
        // Récupérer les détails de la commande
        $stmt = $pdo->prepare("
            SELECT od.*, tc.category_name, m.name as match_name, m.date as match_date, s.name as stadium
            FROM order_details od
            JOIN ticket_categories tc ON od.ticket_category_id = tc.id
            JOIN matches m ON tc.match_id = m.id
            JOIN stadiums s ON m.stadium_id = s.id
            WHERE od.order_id = ?
        ");
        $stmt->execute([$order['id']]);
        $orderDetails = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Générer le PDF du ticket
        $ticketGenerator = new TicketGenerator($order, ['name' => $order['user_name']]);
        $pdfPath = $ticketGenerator->generate();
        
        // Envoyer l'email
        $emailSender = new EmailSender();
        $emailSender->sendTicketEmail($email, $order, $orderDetails, $pdfPath);
        
        // Message de succès
        $_SESSION['success_message'] = "Le ticket a été envoyé avec succès à $email";
        header('Location: my_tickets.php');
        exit;
        
    } catch (Exception $e) {
        $_SESSION['error'] = $e->getMessage();
        header('Location: my_tickets.php');
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Envoyer un ticket - KoraTickets</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container">
        <h2>Envoyer un ticket par email</h2>
        
        <form method="POST" action="send_ticket_form.php">
            <div class="form-group">
                <label for="order_id">ID de la commande</label>
                <input type="text" id="order_id" name="order_id" required>
            </div>
            
            <div class="form-group">
                <label for="email">Email de destination</label>
                <input type="email" id="email" name="email" required>
            </div>
            
            <button type="submit" class="btn btn-primary">Envoyer le ticket</button>
        </form>
    </div>
</body>
</html>
