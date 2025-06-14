<?php
session_start();
require_once 'config.php';
require_once 'includes/functions.php';

$order_id = $_GET['order_id'] ?? '';

if (empty($order_id)) {
    header("Location: index.php");
    exit;
}

try {
    $pdo = getDbConnection();
    
    // Récupérer les détails de la commande
    $stmt = $pdo->prepare("
        SELECT 
            o.*, 
            od.ticket_category_id,
            od.quantity,
            od.unit_price,
            tc.name as ticket_name
        FROM orders o
        LEFT JOIN order_details od ON o.order_id = od.order_id
        LEFT JOIN ticket_categories tc ON od.ticket_category_id = tc.id
        WHERE o.order_id = ?
        ORDER BY od.id
    ");
    $stmt->execute([$order_id]);
    $order_details = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($order_details)) {
        throw new Exception("Commande non trouvée");
    }
    
    // Calculer le total
    $total = 0;
    foreach ($order_details as $detail) {
        $total += $detail['quantity'] * $detail['unit_price'];
    }
    
} catch (Exception $e) {
    logError("Erreur lors de la récupération des détails de la commande", $e->getMessage());
    header("Location: index.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirmation de commande - Football Tickets</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header bg-success text-white">
                        <h3 class="mb-0">Paiement effectué avec succès !</h3>
                    </div>
                    <div class="card-body">
                        <div class="mb-4">
                            <h4>Votre commande</h4>
                            <p class="mb-0">ID de commande : <?= htmlspecialchars($order_id) ?></p>
                            <p class="mb-0">Montant total : <?= formatPrice($total) ?> MAD</p>
                        </div>
                        
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Ticket</th>
                                        <th>Quantité</th>
                                        <th>Prix unitaire</th>
                                        <th>Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($order_details as $detail): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($detail['ticket_name']) ?></td>
                                        <td><?= htmlspecialchars($detail['quantity']) ?></td>
                                        <td><?= formatPrice($detail['unit_price']) ?> MAD</td>
                                        <td><?= formatPrice($detail['quantity'] * $detail['unit_price']) ?> MAD</td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <div class="alert alert-info mt-4">
                            <p class="mb-0">Un email de confirmation vous a été envoyé.</p>
                            <p class="mb-0">Vous pouvez suivre votre commande dans votre espace client.</p>
                        </div>
                        
                        <div class="mt-4">
                            <a href="index.php" class="btn btn-primary">
                                <i class="bi bi-arrow-left"></i> Retour à l'accueil
                            </a>
                            <a href="profile.php" class="btn btn-secondary ms-2">
                                <i class="bi bi-person"></i> Mon espace client
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
