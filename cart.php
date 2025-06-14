<?php
require_once 'config.php';
require_once 'includes/functions.php';

// Initialize variables
$error = '';
$cart_items = [];
$total = 0;

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Process cart updates
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = filter_input(INPUT_POST, 'action', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?? '';
    
    if ($action === 'update') {
        try {
            $pdo = getDbConnection();
            $pdo->beginTransaction();
            
            // Clear existing cart items for this session
            $stmt = $pdo->prepare("DELETE FROM cart_items WHERE session_id = ?");
            $stmt->execute([session_id()]);
            
            // Get and validate quantities
            $quantities = filter_input_array(INPUT_POST, [
                'quantities' => [
                    'filter' => FILTER_VALIDATE_INT,
                    'flags' => FILTER_REQUIRE_ARRAY,
                    'options' => ['min_range' => 0]
                ]
            ]);
            
            if ($quantities && isset($quantities['quantities']) && is_array($quantities['quantities'])) {
                foreach ($quantities['quantities'] as $ticket_category_id => $quantity) {
                    $ticket_category_id = (int)$ticket_category_id;
                    $quantity = (int)$quantity;
                    
                    if ($quantity > 0 && $ticket_category_id > 0) {
                        // Verify ticket category exists and has sufficient quantity
                        $checkStmt = $pdo->prepare("SELECT available_quantity FROM ticket_categories WHERE id = ?");
                        $checkStmt->execute([$ticket_category_id]);
                        $category = $checkStmt->fetch(PDO::FETCH_ASSOC);
                        
                        if (!$category) {
                            throw new Exception("Catégorie de billet invalide: $ticket_category_id");
                        }
                        
                        if ($quantity > $category['available_quantity']) {
                            throw new Exception("Quantité demandée ($quantity) supérieure à la disponibilité ({$category['available_quantity']})");
                        }
                        
                        $stmt = $pdo->prepare("INSERT INTO cart_items (session_id, ticket_category_id, quantity) VALUES (?, ?, ?)");
                        $stmt->execute([session_id(), $ticket_category_id, $quantity]);
                    }
                }
            }
            
            $pdo->commit();
            $_SESSION['cart_updated'] = true;
            
            // Redirect to avoid form resubmission
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit;
        } catch (PDOException $e) {
            if (isset($pdo)) {
                $pdo->rollBack();
            }
            logError("Erreur PDO lors de la mise à jour du panier", $e->getMessage());
            $error = "Erreur lors de la mise à jour du panier. Veuillez réessayer.";
        } catch (Exception $e) {
            if (isset($pdo)) {
                $pdo->rollBack();
            }
            logError("Erreur lors de la mise à jour du panier", $e->getMessage());
            $error = $e->getMessage();
        }
    }
}

// Get cart items
try {
    $pdo = getDbConnection();
    
    // Get cart items with details
    $stmt = $pdo->prepare("
        SELECT 
            ci.ticket_category_id,
            SUM(ci.quantity) as quantity,
            tc.name as category_name,
            tc.price,
            tc.available_quantity,
            tc.total_quantity,
            m.home_team,
            m.away_team,
            m.match_date,
            tc.id as category_id,
            tc.match_id,
            (SUM(ci.quantity) * tc.price) as total_price
        FROM cart_items ci
        JOIN ticket_categories tc ON ci.ticket_category_id = tc.id
        JOIN matches m ON tc.match_id = m.id
        WHERE ci.session_id = ?
        GROUP BY 
            ci.ticket_category_id,
            tc.name,
            tc.price,
            tc.available_quantity,
            tc.total_quantity,
            m.home_team,
            m.away_team,
            m.match_date,
            tc.id,
            tc.match_id
        ORDER BY m.match_date ASC, tc.name ASC
    ");
    $stmt->execute([session_id()]);
    $cart_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Calculate total
    $total = 0;
    foreach ($cart_items as $item) {
        if (isset($item['total_price']) && is_numeric($item['total_price'])) {
            $total += (float)$item['total_price'];
        }
    }
    
    // Update session cart count
    $_SESSION['cart_count'] = array_sum(array_column($cart_items, 'quantity'));
    
} catch (PDOException $e) {
    logError("Erreur de base de données lors du chargement du panier", $e->getMessage());
    $error = "Erreur lors du chargement du panier. Veuillez réessayer.";
    $cart_items = [];
    $total = 0;
} catch (Exception $e) {
    logError("Erreur lors du chargement du panier", $e->getMessage());
    $error = "Une erreur inattendue s'est produite. Veuillez réessayer.";
    $cart_items = [];
    $total = 0;
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panier - Football Tickets</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="container mt-4">
        <h2>Panier</h2>
        
        <?php if (!empty($error)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?php echo htmlspecialchars($error); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['cart_updated']) && $_SESSION['cart_updated']): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            Panier mis à jour avec succès!
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php 
        unset($_SESSION['cart_updated']); 
        endif; ?>
        
        <?php if (empty($cart_items)): ?>
        <div class="alert alert-info">
            Votre panier est vide. <a href="index.php" class="alert-link">Ajoutez des billets</a> à votre panier.
        </div>
        <?php else: ?>
        <form method="POST" action="">
            <input type="hidden" name="action" value="update">
            
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead class="table-dark">
                        <tr>
                            <th>Match</th>
                            <th>Date</th>
                            <th>Catégorie</th>
                            <th>Prix unitaire</th>
                            <th>Quantité</th>
                            <th>Total</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($cart_items as $item): ?>
                        <tr>
                            <td>
                                <strong><?php echo htmlspecialchars($item['home_team']); ?></strong>
                                <br><small class="text-muted">vs <?php echo htmlspecialchars($item['away_team']); ?></small>
                            </td>
                            <td><?php echo date('d/m/Y H:i', strtotime($item['match_date'])); ?></td>
                            <td><?php echo htmlspecialchars($item['category_name']); ?></td>
                            <td><?php echo formatPrice($item['price']); ?> MAD</td>
                            <td>
                                <input type="number" 
                                       name="quantities[<?php echo (int)$item['ticket_category_id']; ?>]" 
                                       value="<?php echo (int)$item['quantity']; ?>" 
                                       min="0" 
                                       max="<?php echo (int)$item['available_quantity']; ?>" 
                                       class="form-control quantity-input"
                                       style="width: 80px;"
                                       data-price="<?php echo (float)$item['price']; ?>">
                                <small class="text-muted">Max: <?php echo (int)$item['available_quantity']; ?></small>
                            </td>
                            <td class="item-total"><?php echo formatPrice($item['price'] * $item['quantity']); ?> MAD</td>
                            <td>
                                <button type="button" class="btn btn-danger btn-sm remove-item" 
                                        data-id="<?php echo (int)$item['ticket_category_id']; ?>"
                                        title="Supprimer">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot class="table-dark">
                        <tr>
                            <th colspan="5" class="text-end">Total général:</th>
                            <th class="grand-total"><?php echo formatPrice($total); ?> MAD</th>
                            <th></th>
                        </tr>
                    </tfoot>
                </table>
            </div>
            
            <div class="row mt-4">
                <div class="col-md-6">
                    <button type="submit" class="btn btn-primary btn-lg">
                        <i class="bi bi-arrow-clockwise"></i> Mettre à jour le panier
                    </button>
                    <a href="index.php" class="btn btn-secondary btn-lg ms-2">
                        <i class="bi bi-arrow-left"></i> Continuer les achats
                    </a>
                </div>
                <div class="col-md-6 text-end">
                    <a href="checkout.php" class="btn btn-success btn-lg">
                        <i class="bi bi-credit-card"></i> Procéder au paiement
                    </a>
                </div>
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
                button.addEventListener('click', function(e) {
                    e.preventDefault();
                    const ticketCategoryId = this.dataset.id;
                    
                    if (!confirm('Êtes-vous sûr de vouloir supprimer cet article du panier ?')) {
                        return;
                    }
                    
                    // Set quantity to 0 and submit form
                    const quantityInput = document.querySelector(`input[name="quantities[${ticketCategoryId}]"]`);
                    if (quantityInput) {
                        quantityInput.value = 0;
                        document.querySelector('form').submit();
                    }
                });
            });

            // Handle quantity changes with real-time total update
            const quantityInputs = document.querySelectorAll('.quantity-input');
            quantityInputs.forEach(input => {
                input.addEventListener('input', function() {
                    const quantity = parseInt(this.value) || 0;
                    const max = parseInt(this.max) || 0;
                    const price = parseFloat(this.dataset.price) || 0;
                    
                    // Validate quantity
                    if (quantity > max) {
                        this.value = max;
                        showAlert('warning', `La quantité maximale est ${max}`);
                    } else if (quantity < 0) {
                        this.value = 0;
                    }
                    
                    // Update item total
                    const itemTotal = this.closest('tr').querySelector('.item-total');
                    if (itemTotal) {
                        const total = price * parseInt(this.value);
                        itemTotal.textContent = formatPrice(total) + ' MAD';
                    }
                    
                    // Update grand total
                    updateGrandTotal();
                });
            });
            
            function updateGrandTotal() {
                let grandTotal = 0;
                document.querySelectorAll('.quantity-input').forEach(input => {
                    const quantity = parseInt(input.value) || 0;
                    const price = parseFloat(input.dataset.price) || 0;
                    grandTotal += quantity * price;
                });
                
                const grandTotalElement = document.querySelector('.grand-total');
                if (grandTotalElement) {
                    grandTotalElement.textContent = formatPrice(grandTotal) + ' MAD';
                }
            }
            
            function formatPrice(number) {
                return new Intl.NumberFormat('fr-FR', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                }).format(number);
            }
            
            function showAlert(type, message) {
                const alertDiv = document.createElement('div');
                alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
                alertDiv.role = 'alert';
                alertDiv.innerHTML = `
                    ${message}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                `;
                document.querySelector('.container').insertBefore(alertDiv, document.querySelector('.container h2').nextElementSibling);
                setTimeout(() => alertDiv.remove(), 3000);
            }
        });
    </script>
</body>
</html>