<?php
session_start();
require_once 'config.php';
require_once 'includes/functions.php';

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Récupérer les billets de l'utilisateur
$pdo = getDbConnection();
$stmt = $pdo->prepare("
    SELECT o.*, 
           m.name as match_name, 
           m.date as match_date, 
           s.name as stadium, 
           tc.category_name,
           od.quantity,
           od.price as ticket_price
    FROM orders o
    JOIN order_details od ON o.id = od.order_id
    JOIN ticket_categories tc ON od.ticket_category_id = tc.id
    JOIN matches m ON tc.match_id = m.id
    JOIN stadiums s ON m.stadium_id = s.id
    WHERE o.user_id = ?
    ORDER BY o.created_at DESC
");
$stmt->execute([$_SESSION['user_id']]);
$tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mes billets - KoraTickets</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container">
        <h1>Mes billets</h1>
        
        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="alert success">
                <?php 
                echo $_SESSION['success_message'];
                unset($_SESSION['success_message']);
                ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert error">
                <?php 
                echo $_SESSION['error'];
                unset($_SESSION['error']);
                ?>
            </div>
        <?php endif; ?>
        
        <?php if (empty($tickets)): ?>
            <p>Aucun billet trouvé.</p>
        <?php else: ?>
            <div class="tickets-grid">
                <?php foreach ($tickets as $ticket): ?>
                    <div class="ticket-card">
                        <h3><?php echo htmlspecialchars($ticket['match_name']); ?></h3>
                        <p>Stade: <?php echo htmlspecialchars($ticket['stadium']); ?></p>
                        <p>Date: <?php echo date('d/m/Y H:i', strtotime($ticket['match_date'])); ?></p>
                        <p>Catégorie: <?php echo htmlspecialchars($ticket['category_name']); ?></p>
                        <p>Quantité: <?php echo $ticket['quantity']; ?></p>
                        <p>Prix: <?php echo $ticket['ticket_price'] * $ticket['quantity']; ?> DZD</p>
                        
                        <form method="POST" action="send_ticket_form.php" class="ticket-form">
                            <input type="hidden" name="order_id" value="<?php echo $ticket['id']; ?>">
                            <div class="form-group">
                                <label for="email_<?php echo $ticket['id']; ?>">Email de destination</label>
                                <input type="email" id="email_<?php echo $ticket['id']; ?>" name="email" required>
                            </div>
                            <button type="submit" class="btn btn-primary">Envoyer le ticket</button>
                        </form>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
