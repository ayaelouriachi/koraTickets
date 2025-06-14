<?php
session_start();
require_once 'config.php';
require_once 'includes/functions.php';

// Fonction pour générer un ID de commande unique
function generateOrderId() {
    return 'ORD-' . date('Ymd') . '-' . str_pad(mt_rand(1, 999999), 6, '0', STR_PAD_LEFT);
}

// Fonction pour enregistrer la commande
function saveOrder($order_id, $amount) {
    try {
        // Debug: Afficher les informations de la commande
        error_log("[DEBUG] Tentative d'enregistrement de la commande: ID=$order_id, Montant=$amount");
        error_log("[DEBUG] Session: " . print_r($_SESSION, true));

        // Vérifier si l'utilisateur est connecté
        if (!isset($_SESSION['user_id'])) {
            error_log("[ERROR] Utilisateur non connecté - Session: " . print_r($_SESSION, true));
            throw new Exception("Utilisateur non connecté");
        }

        // Vérifier si le montant est valide
        if (!is_numeric($amount) || $amount <= 0) {
            error_log("[ERROR] Montant invalide: $amount");
            throw new Exception("Montant invalide");
        }

        $pdo = getDbConnection();
        if (!$pdo) {
            error_log("[ERROR] Impossible de se connecter à la base de données");
            throw new Exception("Erreur de connexion à la base de données");
        }
        
        error_log("[DEBUG] Connexion à la base de données établie");
        
        $pdo->beginTransaction();
        error_log("[DEBUG] Transaction démarrée");
        
        // Vérifier si l'ID de commande existe déjà (chercher par paypal_transaction_id)
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE paypal_transaction_id = ?");
        $stmt->execute([$order_id]);
        $count = $stmt->fetchColumn();
        
        if ($count > 0) {
            error_log("[ERROR] Commande déjà traitée: $order_id");
            throw new Exception("Commande déjà traitée");
        }
        
        // Insérer la commande
        $stmt = $pdo->prepare("
            INSERT INTO orders (id, user_id, total_amount, payment_status, paypal_transaction_id, created_at)
            VALUES (NULL, ?, ?, 'completed', ?, NOW())
        ");
        
        if (!$stmt->execute([$_SESSION['user_id'], $amount, $order_id])) {
            $errorInfo = $stmt->errorInfo();
            error_log("[ERROR] Échec de l'insertion de la commande: " . print_r($errorInfo, true));
            throw new Exception("Échec de l'insertion de la commande: " . $errorInfo[2]);
        }
        
        error_log("[DEBUG] Commande insérée avec succès");
        
        // Récupérer l'ID de la commande insérée
        $order_id_db = $pdo->lastInsertId();
        error_log("[DEBUG] ID de la commande insérée: $order_id_db");
        
        // Récupérer les items du panier
        $session_id = session_id();
        error_log("[DEBUG] Session ID utilisé: $session_id");
        
        $stmt = $pdo->prepare("
            SELECT ci.*, tc.price, tc.available_quantity
            FROM cart_items ci
            JOIN ticket_categories tc ON ci.ticket_category_id = tc.id
            WHERE ci.session_id = ?
        ");
        
        if (!$stmt->execute([$session_id])) {
            $errorInfo = $stmt->errorInfo();
            error_log("[ERROR] Échec de la récupération du panier: " . print_r($errorInfo, true));
            throw new Exception("Échec de la récupération du panier: " . $errorInfo[2]);
        }
        
        $cart_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
        error_log("[DEBUG] Items du panier: " . print_r($cart_items, true));
        
        if (empty($cart_items)) {
            error_log("[ERROR] Panier vide - peut-être déjà traité ou session expirée");
            // Si le panier est vide, on considère que la commande a déjà été traitée
            // On va juste créer une entrée basique dans la base
            $stmt = $pdo->prepare("
                INSERT INTO orders (id, user_id, total_amount, payment_status, paypal_transaction_id, created_at)
                VALUES (NULL, ?, ?, 'completed', ?, NOW())
            ");
            
            if (!$stmt->execute([$_SESSION['user_id'], $amount, $order_id])) {
                $errorInfo = $stmt->errorInfo();
                error_log("[ERROR] Échec de l'insertion de la commande basique: " . print_r($errorInfo, true));
                throw new Exception("Échec de l'insertion de la commande: " . $errorInfo[2]);
            }
            
            $pdo->commit();
            error_log("[DEBUG] Commande basique créée avec succès (panier vide)");
            return true;
        }
        
        // Vérifier si les quantités sont toujours disponibles
        foreach ($cart_items as $item) {
            if (!isset($item['quantity']) || !isset($item['available_quantity'])) {
                error_log("[ERROR] Données du panier invalides: " . print_r($item, true));
                throw new Exception("Données du panier invalides");
            }
            
            if ($item['quantity'] > $item['available_quantity']) {
                error_log("[ERROR] Quantité non disponible pour le ticket " . $item['ticket_category_id']);
                throw new Exception("Quantité non disponible pour le ticket " . $item['ticket_category_id']);
            }
        }
        
        // Insérer les détails de la commande
        foreach ($cart_items as $item) {
            $stmt = $pdo->prepare("
                INSERT INTO order_details (id, order_id, ticket_category_id, quantity, unit_price)
                VALUES (NULL, ?, ?, ?, ?)
            ");
            
            if (!$stmt->execute([$order_id_db, $item['ticket_category_id'], $item['quantity'], $item['price']])) {
                $errorInfo = $stmt->errorInfo();
                error_log("[ERROR] Échec de l'insertion des détails: " . print_r($errorInfo, true));
                throw new Exception("Échec de l'insertion des détails de la commande: " . $errorInfo[2]);
            }
            
            error_log("[DEBUG] Détails insérés pour le ticket " . $item['ticket_category_id']);
        }
        
        // Mettre à jour les quantités disponibles
        foreach ($cart_items as $item) {
            $stmt = $pdo->prepare("
                UPDATE ticket_categories 
                SET available_quantity = available_quantity - ? 
                WHERE id = ?
            ");
            
            if (!$stmt->execute([$item['quantity'], $item['ticket_category_id']])) {
                $errorInfo = $stmt->errorInfo();
                error_log("[ERROR] Échec de la mise à jour du stock: " . print_r($errorInfo, true));
                throw new Exception("Échec de la mise à jour du stock: " . $errorInfo[2]);
            }
            
            error_log("[DEBUG] Stock mis à jour pour le ticket " . $item['ticket_category_id']);
        }
        
        // Supprimer les items du panier
        $stmt = $pdo->prepare("DELETE FROM cart_items WHERE session_id = ?");
        
        if (!$stmt->execute([$session_id])) {
            $errorInfo = $stmt->errorInfo();
            error_log("[ERROR] Échec de la suppression du panier: " . print_r($errorInfo, true));
            throw new Exception("Échec de la suppression du panier: " . $errorInfo[2]);
        }
        
        $_SESSION['cart_count'] = 0;
        
        $pdo->commit();
        error_log("[DEBUG] Transaction validée avec succès");
        return true;
    } catch (Exception $e) {
        if (isset($pdo)) {
            $pdo->rollBack();
            error_log("[ERROR] Transaction annulée");
        }
        
        // Log détaillé de l'erreur
        error_log("[ERROR] Erreur lors de l'enregistrement de la commande: " . $e->getMessage());
        error_log("[ERROR] Trace de la pile: " . $e->getTraceAsString());
        
        return false;
    }
}

function sendConfirmationEmail($order_id, $amount) {
    $to = $_SESSION['user_email'] ?? $_SESSION['email'] ?? '';
    if (empty($to)) {
        error_log("[WARNING] Aucun email trouvé dans la session pour l'envoi de confirmation");
        return false;
    }
    
    // En environnement de développement, on simule l'envoi d'email
    if (isLocalEnvironment()) {
        error_log("[INFO] Email de confirmation simulé pour: $to");
        error_log("[INFO] Sujet: Confirmation de commande - TicketFoot");
        error_log("[INFO] Commande: $order_id, Montant: $amount MAD");
        return true; // Simulation réussie
    }
    
    $subject = "Confirmation de commande - TicketFoot";
    $message = "
        Bonjour,
        
        Merci pour votre achat sur TicketFoot !
        
        Détails de votre commande :
        ID de commande : $order_id
        Montant total : $amount MAD
        
        Vous recevrez vos billets par email quelques minutes après la validation de votre paiement.
        
        Cordialement,
        L'équipe TicketFoot
    ";
    
    $headers = "From: support@ticketfoot.com\r\n";
    $headers .= "Reply-To: support@ticketfoot.com\r\n";
    $headers .= "X-Mailer: PHP/" . phpversion();
    
    $result = mail($to, $subject, $message, $headers);
    if (!$result) {
        error_log("[ERROR] Échec de l'envoi de l'email de confirmation à: $to");
    }
    return $result;
}

// Fonction pour détecter l'environnement local
function isLocalEnvironment() {
    $localHosts = ['127.0.0.1', '::1', 'localhost'];
    return in_array($_SERVER['SERVER_NAME'] ?? '', $localHosts) || 
           in_array($_SERVER['HTTP_HOST'] ?? '', $localHosts) ||
           strpos($_SERVER['HTTP_HOST'] ?? '', 'localhost') !== false;
}

// Variables pour gérer les erreurs et les données
$error = null;
$order_id = null;
$amount = null;
$success = false;

// Si l'ID de commande et le montant sont fournis
if (isset($_GET['order_id']) && isset($_GET['amount'])) {
    $order_id = $_GET['order_id'];
    $amount = floatval($_GET['amount']);

    try {
        // Debug: Afficher les paramètres reçus
        error_log("[DEBUG] Paramètres reçus - order_id: $order_id, amount: $amount");
        error_log("[DEBUG] Session utilisateur: " . print_r($_SESSION, true));
        
        // Vérifier les prérequis
        if (!isset($_SESSION['user_id'])) {
            throw new Exception("Utilisateur non connecté - session expirée");
        }
        
        // Enregistrer la commande directement
        $saveResult = saveOrder($order_id, $amount);
        error_log("[DEBUG] Résultat saveOrder: " . ($saveResult ? 'SUCCESS' : 'FAILED'));
        
        if (!$saveResult) {
            throw new Exception("Erreur lors de l'enregistrement de la commande");
        }

        $success = true; // Marquer comme succès

        // Envoyer l'email de confirmation
        $emailSent = sendConfirmationEmail($order_id, $amount);
        if (!$emailSent && !isLocalEnvironment()) {
            error_log("[WARNING] Échec de l'envoi de l'email de confirmation");
        }

        // Succès - pas de redirection automatique, on affiche la page de confirmation
    } catch (Exception $e) {
        error_log("Erreur lors du traitement de la commande: " . $e->getMessage());
        $error = "Une erreur est survenue lors du traitement de votre commande. Veuillez réessayer plus tard.";
    } finally {
        // Fermer la connexion à la base de données
        if (isset($pdo)) {
            $pdo = null;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Paiement réussi - TicketFoot</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        .confirmation-container {
            max-width: 600px;
            margin: 4rem auto;
            padding: 2rem;
            text-align: center;
        }
        .confirmation-icon {
            font-size: 4rem;
            color: #198754;
        }
        .order-details {
            margin: 2rem 0;
            padding: 1.5rem;
            background-color: #f8f9fa;
            border-radius: 8px;
        }
    </style>
</head>
<body>
    <div class="confirmation-container">
        <?php if ($order_id && $amount && $success && !$error): ?>
            <i class="bi bi-check-circle confirmation-icon"></i>
            <h1 class="mt-4">Paiement effectué avec succès !</h1>
            
            <div class="order-details">
                <h3>Votre commande</h3>
                <p>ID de commande : <?php echo htmlspecialchars($order_id); ?></p>
                <p>Montant total : <?php echo function_exists('formatPrice') ? formatPrice($amount) : number_format($amount, 2); ?> MAD</p>
                <p>
                    <?php if (isLocalEnvironment()): ?>
                        <i class="bi bi-info-circle text-info"></i> Email de confirmation simulé (environnement de développement)
                    <?php else: ?>
                        Un email de confirmation vous a été envoyé.
                    <?php endif; ?>
                </p>
            </div>
            
            <div class="mt-4">
                <a href="index.php" class="btn btn-primary btn-lg">
                    <i class="bi bi-house-door"></i> Retour à l'accueil
                </a>
                <a href="my_orders.php" class="btn btn-secondary btn-lg ms-2">
                    <i class="bi bi-receipt"></i> Mes commandes
                </a>
            </div>
        <?php else: ?>
            <i class="bi bi-exclamation-triangle confirmation-icon text-warning"></i>
            <h1 class="mt-4">Erreur de traitement</h1>
            <p>Une erreur s'est produite lors du traitement de votre commande.</p>
            <p><strong>Debug:</strong> order_id=<?php echo htmlspecialchars($order_id ?? 'non défini'); ?>, amount=<?php echo htmlspecialchars($amount ?? 'non défini'); ?>, success=<?php echo $success ? 'true' : 'false'; ?></p>
            <div class="mt-4">
                <a href="index.php" class="btn btn-primary btn-lg">
                    <i class="bi bi-house-door"></i> Retour à l'accueil
                </a>
            </div>
        <?php endif; ?>

        <?php if (isset($error)): ?>
        <div class="alert alert-danger mt-4" role="alert">
            <strong>Erreur détaillée:</strong> <?php echo htmlspecialchars($error); ?>
        </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>