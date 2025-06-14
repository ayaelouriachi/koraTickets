<?php
require_once 'config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Process cart updates
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'update') {
        $pdo = getDbConnection();
        $pdo->beginTransaction();
        try {
            // Clear existing cart items
            $stmt = $pdo->prepare("DELETE FROM cart_items WHERE session_id = ?");
            $stmt->execute([session_id()]);
            
            // Add new items
            foreach ($_POST['quantities'] as $ticket_category_id => $quantity) {
                if ($quantity > 0) {
                    $stmt = $pdo->prepare("INSERT INTO cart_items (session_id, ticket_category_id, quantity) VALUES (?, ?, ?)");
                    $stmt->execute([session_id(), $ticket_category_id, $quantity]);
                }
            }
            
            $pdo->commit();
            $_SESSION['cart_updated'] = true;
        } catch (Exception $e) {
            $pdo->rollBack();
        }
    }
}

try {
    $pdo = getDbConnection();
    
    // Get cart items - CORRECTION: Ajout de tc.available_quantity
    $stmt = $pdo->prepare("
        SELECT ci.*, tc.name as category_name, tc.price, tc.available_quantity, m.home_team, m.away_team
        FROM cart_items ci
        JOIN ticket_categories tc ON ci.ticket_category_id = tc.id
        JOIN matches m ON tc.match_id = m.id
        WHERE ci.session_id = ?
    ");
    $stmt->execute([session_id()]);
    $cart_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Calculate total
    $total = 0;
    foreach ($cart_items as $item) {
        $total += $item['price'] * $item['quantity'];
    }
} catch (PDOException $e) {
    die("Erreur de base de données");
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panier - Football Tickets</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="container mt-4">
        <h2>Panier</h2>
        
        <?php if(empty($cart_items)): ?>
        <div class="alert alert-info">
            Votre panier est vide. <a href="index.php">Ajoutez des billets</a> à votre panier.
        </div>
        <?php else: ?>
        <form method="POST" action="">
            <input type="hidden" name="action" value="update">
            
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Match</th>
                            <th>Catégorie</th>
                            <th>Prix unitaire</th>
                            <th>Quantité</th>
                            <th>Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($cart_items as $item): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($item['home_team']); ?> vs <?php echo htmlspecialchars($item['away_team']); ?></td>
                            <td><?php echo htmlspecialchars($item['category_name']); ?></td>
                            <td><?php echo number_format($item['price'], 2, ',', ' '); ?> MAD</td>
                            <td>
                                <div class="input-group">
                                    <input type="number" 
                                           name="quantities[<?php echo $item['ticket_category_id']; ?>]" 
                                           value="<?php echo $item['quantity']; ?>" 
                                           min="0" 
                                           max="<?php echo $item['available_quantity']; ?>" 
                                           class="form-control w-50">
                                    <button type="button" class="btn btn-danger btn-sm remove-item" data-id="<?php echo $item['ticket_category_id']; ?>">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                            </td>
                            <td><?php echo number_format($item['price'] * $item['quantity'], 2, ',', ' '); ?> MAD</td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <div class="d-flex justify-content-between align-items-center mt-4">
                <button type="submit" class="btn btn-primary">Mettre à jour le panier</button>
                <div class="total-price">
                    <h4>Total: <?php echo number_format($total, 2, ',', ' '); ?> MAD</h4>
                </div>
            </div>
            
            <div class="mt-4">
                <a href="checkout.php" class="btn btn-success btn-lg w-100">Procéder au paiement</a>
            </div>
        </form>
        <?php endif; ?>
    </div>

    <?php include 'includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Handle item removal
            const removeButtons = document.querySelectorAll('.remove-item');
            removeButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const ticketCategoryId = this.dataset.id;
                    
                    // Send AJAX request to remove item
                    fetch('remove_from_cart.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            ticket_category_id: ticketCategoryId
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Remove row from table
                            this.closest('tr').remove();
                            // Update cart count
                            const cartCount = document.querySelector('.cart-count');
                            if (cartCount) {
                                cartCount.textContent = data.cart_count;
                            }
                        } else {
                            alert(data.error);
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('Erreur lors de la suppression');
                    });
                });
            });
        });
    </script>
</body>
</html>