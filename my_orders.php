<?php
session_start();
require_once 'config.php';
require_once 'includes/functions.php';

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Vérifier la connexion à la base de données
try {
    $pdo = getDbConnection();
    $pdo->query("SELECT 1"); // Test simple de la connexion
} catch (PDOException $e) {
    error_log("Erreur de connexion à la base de données: " . $e->getMessage());
    $error = "Impossible de se connecter à la base de données. Veuillez contacter l'administrateur.";
    goto display_page;
}

// Récupérer les commandes de l'utilisateur
try {
    $stmt = $pdo->prepare("
        SELECT o.*, 
               CONCAT(m.home_team, ' vs ', m.away_team) as match_name,
               m.match_date as match_date,
               tc.name as ticket_name
        FROM orders o
        LEFT JOIN order_details od ON o.id = od.order_id
        LEFT JOIN ticket_categories tc ON od.ticket_category_id = tc.id
        LEFT JOIN matches m ON tc.match_id = m.id
        WHERE o.user_id = ?
        ORDER BY o.created_at DESC
    ");
    
    if (!$stmt) {
        throw new Exception("Erreur de préparation de la requête: " . $pdo->errorInfo()[2]);
    }
    
    if (!$stmt->execute([$_SESSION['user_id']])) {
        throw new Exception("Erreur d'exécution de la requête: " . print_r($stmt->errorInfo(), true));
    }
    
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if ($stmt->errorCode() !== '00000') {
        throw new Exception("Erreur lors de la récupération des données: " . print_r($stmt->errorInfo(), true));
    }
} catch (Exception $e) {
    error_log("Erreur lors de la récupération des commandes: " . $e->getMessage());
    $error = "Une erreur est survenue lors de la récupération de vos commandes. " . $e->getMessage();
}

display_page:

// Afficher les commandes
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mes commandes - Football Tickets</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <link href="css/style.css" rel="stylesheet">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container mt-5">
        <h1 class="mb-4">Mes commandes</h1>
        
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php 
                echo htmlspecialchars($_SESSION['success']);
                unset($_SESSION['success']);
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php 
                echo htmlspecialchars($_SESSION['error']);
                unset($_SESSION['error']);
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        
        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <?php if (empty($orders)): ?>
            <div class="alert alert-info">
                Vous n'avez pas encore effectué de commandes.
                <a href="index.php" class="btn btn-primary">Découvrir les matchs</a>
            </div>
        <?php else: ?>
            <!-- Bouton d'export PDF pour toutes les commandes payées -->
            <?php /* Bouton d'export global supprimé */ ?>
            
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>ID de commande</th>
                            <th>Match</th>
                            <th>Montant</th>
                            <th>Date</th>
                            <th>État</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orders as $order): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($order['id'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($order['match_name'] ?? 'N/A'); ?></td>
                                <td><?php echo isset($order['total_amount']) ? formatPrice($order['total_amount']) : 'N/A'; ?> MAD</td>
                                <td><?php echo isset($order['created_at']) ? date('d/m/Y H:i', strtotime($order['created_at'])) : 'N/A'; ?></td>
                                <td>
                                    <?php if (isset($order['payment_status']) && $order['payment_status'] === 'completed'): ?>
                                        <span class="badge bg-success">Payé</span>
                                    <?php else: ?>
                                        <span class="badge bg-warning">En attente</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (isset($order['payment_status']) && $order['payment_status'] === 'completed'): ?>
                                        <div class="btn-group">
                                            <a href="#" onclick="openMailClient('<?php echo htmlspecialchars($order['id'] ?? ''); ?>')" class="btn btn-sm btn-warning">
                                                <i class="bi bi-envelope"></i> Gmail
                                            </a>
                                            <a href="export_ticket.php?order_id=<?php echo htmlspecialchars($order['id'] ?? ''); ?>" class="btn btn-sm btn-warning">
                                                <i class="bi bi-file-pdf"></i> PDF
                                            </a>
                                        </div>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    function openMailClient(orderId) {
        // Ouvrir une modal avec les instructions
        var modal = document.createElement('div');
        modal.className = 'modal fade';
        modal.id = 'emailInstructionsModal';
        modal.innerHTML = `
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Envoi du ticket par email</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <p>Pour envoyer votre ticket par email :</p>
                        <ol>
                            <li>Cliquez sur le bouton "Envoyer par email" ci-dessous</li>
                            <li>Le ticket sera automatiquement envoyé à votre adresse email</li>
                        </ol>
                    </div>
                    <div class="modal-footer">
                        <a href="send_ticket_email.php?order_id=${orderId}" class="btn btn-success">
                            Envoyer par email
                        </a>
                    </div>
                </div>
            </div>
        `;
        document.body.appendChild(modal);
        
        var modalInstance = new bootstrap.Modal(modal);
        modalInstance.show();
        
        // Nettoyer la modal après fermeture
        modal.addEventListener('hidden.bs.modal', function () {
            document.body.removeChild(modal);
        });
    }
    </script>
</body>
</html>
