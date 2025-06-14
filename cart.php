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
                    <div id="payment-section" class="paypal-section">
                        <button id="proceed-payment" class="btn btn-success btn-lg" type="button">
                            <i class="bi bi-credit-card"></i> Procéder au paiement
                        </button>
                        
                        <div id="paypal-widget" class="paypal-container mt-4" style="display: none;">
                            <div id="paypal-button-container"></div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
        <?php endif; ?>
    </div>

    <?php include 'includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <style>
        .paypal-section {
            max-width: 600px;
            margin: 0 auto;
        }

        .paypal-container {
            max-width: 500px;
            margin: 20px auto;
            padding: 24px;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            background-color: #fff;
        }

        #paypal-button-container {
            width: 100%;
            margin-top: 1rem;
        }

        .paypal-loading {
            text-align: center;
            padding: 2rem;
        }

        .paypal-loading .spinner {
            width: 40px;
            height: 40px;
            border: 4px solid #f3f3f3;
            border-top: 4px solid #3498db;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: 0 auto 1rem;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .alert-payment {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1050;
            min-width: 300px;
        }
    </style>

    <script>
        // Configuration PayPal - REMPLACEZ 'YOUR_ACTUAL_CLIENT_ID' par votre vrai client ID
        const PAYPAL_CONFIG = {
            clientId: 'ATCTf1xlbiVLApSBMv7SERrYgJufculjfQb1X3qlK9ZEfH3mJex8xs7jR7oa5jeIRf5tSbQdJBNqjeyi', // Remplacez par votre client ID réel
            currency: 'EUR',
            intent: 'capture'
        };

        // Variables globales
        let paypalScriptLoaded = false;
        let paypalButtonRendered = false;

        // Fonctions utilitaires
        function showAlert(type, message) {
            // Supprimer les anciennes alertes
            const existingAlerts = document.querySelectorAll('.alert-payment');
            existingAlerts.forEach(alert => alert.remove());

            const alertDiv = document.createElement('div');
            alertDiv.className = `alert alert-${type} alert-dismissible fade show alert-payment`;
            alertDiv.role = 'alert';
            alertDiv.innerHTML = `
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            `;
            document.body.appendChild(alertDiv);
            
            // Auto-supprimer après 5 secondes
            setTimeout(() => {
                if (alertDiv.parentNode) {
                    alertDiv.remove();
                }
            }, 5000);
        }

        function formatPrice(number) {
            return new Intl.NumberFormat('fr-FR', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            }).format(number);
        }

        // Fonction pour charger le SDK PayPal
        function loadPayPalSDK() {
            return new Promise((resolve, reject) => {
                if (window.paypal) {
                    paypalScriptLoaded = true;
                    resolve();
                    return;
                }

                const script = document.createElement('script');
                script.src = `https://www.paypal.com/sdk/js?client-id=${PAYPAL_CONFIG.clientId}&currency=${PAYPAL_CONFIG.currency}&intent=${PAYPAL_CONFIG.intent}`;
                
                script.onload = () => {
                    paypalScriptLoaded = true;
                    console.log('SDK PayPal chargé avec succès');
                    resolve();
                };
                
                script.onerror = () => {
                    console.error('Erreur lors du chargement du SDK PayPal');
                    reject(new Error('Impossible de charger PayPal'));
                };
                
                document.head.appendChild(script);
            });
        }

        // Fonction pour rendre le bouton PayPal
        function renderPayPalButton(amount) {
            const container = document.getElementById('paypal-button-container');
            
            if (!container) {
                console.error('Container PayPal introuvable');
                return;
            }

            // Nettoyer le container
            container.innerHTML = '';

            if (!window.paypal) {
                console.error('SDK PayPal non disponible');
                showAlert('danger', 'PayPal n\'est pas disponible. Veuillez recharger la page.');
                return;
            }

            try {
                window.paypal.Buttons({
                    style: {
                        layout: 'vertical',
                        color: 'blue',
                        shape: 'pill',
                        label: 'pay',
                        height: 40
                    },
                    
                    createOrder: function(data, actions) {
                        console.log('Création de la commande PayPal pour:', amount, PAYPAL_CONFIG.currency);
                        return actions.order.create({
                            purchase_units: [{
                                amount: {
                                    value: amount.toFixed(2),
                                    currency_code: PAYPAL_CONFIG.currency
                                },
                                description: 'Achat de billets de football'
                            }]
                        });
                    },
                    
                    onApprove: function(data, actions) {
                        console.log('Paiement approuvé:', data);
                        showAlert('info', 'Traitement du paiement en cours...');
                        
                        return actions.order.capture().then(function(details) {
                            console.log('Paiement capturé:', details);
                            showAlert('success', 'Paiement réussi! Redirection en cours...');
                            
                            // Redirection vers la page de succès
                            setTimeout(() => {
                                window.location.href = `payment_success.php?order_id=${details.id}&amount=${amount}`;
                            }, 2000);
                        }).catch(function(error) {
                            console.error('Erreur lors de la capture:', error);
                            showAlert('danger', 'Erreur lors du traitement du paiement.');
                        });
                    },
                    
                    onError: function(err) {
                        console.error('Erreur PayPal:', err);
                        showAlert('danger', 'Une erreur est survenue avec PayPal. Veuillez réessayer.');
                        resetPaymentButton();
                    },
                    
                    onCancel: function(data) {
                        console.log('Paiement annulé:', data);
                        showAlert('warning', 'Paiement annulé.');
                        resetPaymentButton();
                    }
                }).render(container).then(() => {
                    paypalButtonRendered = true;
                    console.log('Bouton PayPal rendu avec succès');
                }).catch((error) => {
                    console.error('Erreur lors du rendu du bouton PayPal:', error);
                    showAlert('danger', 'Impossible d\'afficher les options de paiement PayPal.');
                    resetPaymentButton();
                });
                
            } catch (error) {
                console.error('Erreur lors de la création du bouton PayPal:', error);
                showAlert('danger', 'Erreur lors de l\'initialisation de PayPal.');
                resetPaymentButton();
            }
        }

        // Fonction pour réinitialiser le bouton de paiement
        function resetPaymentButton() {
            const proceedButton = document.getElementById('proceed-payment');
            const paypalWidget = document.getElementById('paypal-widget');
            
            if (proceedButton) {
                proceedButton.style.display = 'inline-block';
                proceedButton.disabled = false;
            }
            
            if (paypalWidget) {
                paypalWidget.style.display = 'none';
            }
            
            paypalButtonRendered = false;
        }

        // Fonction principale pour afficher le paiement PayPal
        async function showPayPalPayment() {
            try {
                const total = <?php echo json_encode((float)$total); ?>;
                
                console.log('Initialisation du paiement pour:', total);
                
                // Vérifier le montant
                if (!total || total <= 0) {
                    showAlert('warning', 'Votre panier est vide ou le montant est invalide.');
                    return;
                }

                // Désactiver le bouton et afficher le chargement
                const proceedButton = document.getElementById('proceed-payment');
                const paypalWidget = document.getElementById('paypal-widget');
                const container = document.getElementById('paypal-button-container');
                
                if (proceedButton) {
                    proceedButton.disabled = true;
                    proceedButton.innerHTML = '<i class="bi bi-hourglass-split"></i> Chargement...';
                }

                if (paypalWidget) {
                    paypalWidget.style.display = 'block';
                }

                if (container) {
                    container.innerHTML = `
                        <div class="paypal-loading">
                            <div class="spinner"></div>
                            <p>Initialisation de PayPal...</p>
                        </div>
                    `;
                }

                // Charger le SDK PayPal si nécessaire
                if (!paypalScriptLoaded) {
                    await loadPayPalSDK();
                }

                // Attendre un peu pour s'assurer que le SDK est prêt
                setTimeout(() => {
                    if (proceedButton) {
                        proceedButton.style.display = 'none';
                    }
                    renderPayPalButton(total);
                }, 500);

            } catch (error) {
                console.error('Erreur lors de l\'initialisation PayPal:', error);
                showAlert('danger', 'Impossible de charger PayPal. Veuillez réessayer plus tard.');
                resetPaymentButton();
            }
        }

        // Initialisation au chargement de la page
        document.addEventListener('DOMContentLoaded', function() {
            console.log('Page chargée, initialisation des événements');
            
            // Gestionnaire pour le bouton "Procéder au paiement"
            const proceedButton = document.getElementById('proceed-payment');
            if (proceedButton) {
                proceedButton.addEventListener('click', function(e) {
                    e.preventDefault();
                    console.log('Bouton "Procéder au paiement" cliqué');
                    showPayPalPayment();
                });
            }

            // Gestionnaire pour la suppression d'articles
            const removeButtons = document.querySelectorAll('.remove-item');
            removeButtons.forEach(button => {
                button.addEventListener('click', function(e) {
                    e.preventDefault();
                    const ticketCategoryId = this.dataset.id;
                    
                    if (!confirm('Êtes-vous sûr de vouloir supprimer cet article du panier ?')) {
                        return;
                    }
                    
                    const quantityInput = document.querySelector(`input[name="quantities[${ticketCategoryId}]"]`);
                    if (quantityInput) {
                        quantityInput.value = 0;
                        document.querySelector('form').submit();
                    }
                });
            });

            // Gestionnaire pour les changements de quantité
            const quantityInputs = document.querySelectorAll('.quantity-input');
            quantityInputs.forEach(input => {
                input.addEventListener('input', function() {
                    const quantity = parseInt(this.value) || 0;
                    const max = parseInt(this.max) || 0;
                    const price = parseFloat(this.dataset.price) || 0;
                    
                    // Validation de la quantité
                    if (quantity > max) {
                        this.value = max;
                        showAlert('warning', `La quantité maximale est ${max}`);
                    } else if (quantity < 0) {
                        this.value = 0;
                    }
                    
                    // Mise à jour du total de l'article
                    const itemTotal = this.closest('tr').querySelector('.item-total');
                    if (itemTotal) {
                        const total = price * parseInt(this.value);
                        itemTotal.textContent = formatPrice(total) + ' MAD';
                    }
                    
                    // Mise à jour du total général
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

            // Rendre les fonctions disponibles globalement
            window.showAlert = showAlert;
            window.formatPrice = formatPrice;
            window.showPayPalPayment = showPayPalPayment;
        });
    </script>
</body>
</html>