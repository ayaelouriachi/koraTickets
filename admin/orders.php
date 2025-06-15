<?php
require_once '../config.php';

// Check admin access
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../index.php');
    exit;
}

try {
    $pdo = getDbConnection();
    
    // Get all orders with details
    $stmt = $pdo->query("
        SELECT o.*, u.username, 
               GROUP_CONCAT(DISTINCT CONCAT(
                   tc.name, ' (', od.quantity, ' x ', FORMAT(tc.price, 2), ' MAD)'
               ) SEPARATOR '<br>') as ticket_details
        FROM orders o
        LEFT JOIN users u ON o.user_id = u.id
        LEFT JOIN order_details od ON o.id = od.order_id
        LEFT JOIN ticket_categories tc ON od.ticket_category_id = tc.id
        GROUP BY o.id
        ORDER BY o.created_at DESC
    ");
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Erreur de base de données : " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Commandes - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-blue: #003366;
            --accent-green: #4CAF50;
            --action-orange: #FF9800;
            --bg-light: #F5F5F5;
            --bg-white: #FFFFFF;
            --text-primary: #212121;
            --text-secondary: #666666;
            --error-red: #E53935;
            --success-green: #43A047;
            --border-color: #E0E0E0;
            --shadow-md: 0 4px 8px rgba(0, 51, 102, 0.15);
            --border-radius: 12px;
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        body { font-family: 'Inter', sans-serif; background: var(--bg-light); color: var(--text-primary); }
        .sidebar { position: fixed; top: 0; left: 0; bottom: 0; width: 250px; background: var(--primary-blue); color: white; padding-top: 1rem; z-index: 100; }
        .sidebar-header { padding: 0 1.5rem 1.5rem; border-bottom: 1px solid rgba(255, 255, 255, 0.1); }
        .sidebar-title { font-weight: 700; font-size: 1.25rem; color: white; }
        .nav-link { color: rgba(255, 255, 255, 0.8); padding: 0.75rem 1.5rem; margin: 0.25rem 0; border-radius: 6px; transition: var(--transition); }
        .nav-link:hover { color: white; background: rgba(255, 255, 255, 0.1); }
        .nav-link.active { color: white; background: var(--action-orange); font-weight: 500; }
        .nav-link i { width: 24px; margin-right: 0.75rem; text-align: center; }
        .main-content { margin-left: 250px; padding: 2rem; }
        .card { border: none; border-radius: var(--border-radius); box-shadow: var(--shadow-md); animation: fadeIn 0.5s ease-out; }
        .card-header { background: var(--bg-white); border-bottom: 1px solid var(--border-color); padding: 1.25rem 1.5rem; font-weight: 600; }
        .table th { font-weight: 600; color: var(--text-secondary); text-transform: uppercase; font-size: 0.75rem; letter-spacing: 0.5px; }
        .table td { vertical-align: middle; }
        .badge { padding: 0.5em 0.75em; font-weight: 500; font-size: 0.75rem; border-radius: 50px; }
        .amount-cell { font-weight: 600; color: var(--primary-blue); }
        .transaction-cell { font-family: monospace; font-size: 0.85rem; }
        .ticket-details { font-size: 0.9rem; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
        @media (max-width: 992px) { .main-content { margin-left: 0; } }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="sidebar-header"><h4 class="sidebar-title">Admin Panel</h4></div>
        <ul class="nav flex-column">
            <li class="nav-item"><a class="nav-link" href="dashboard.php"><i class="bi bi-speedometer2"></i> Dashboard</a></li>
            <li class="nav-item"><a class="nav-link" href="matches.php"><i class="bi bi-calendar-event"></i> Gestion des Matchs</a></li>
            <li class="nav-item"><a class="nav-link" href="tickets.php"><i class="bi bi-ticket-perforated"></i> Gestion des Billets</a></li>
            <li class="nav-item"><a class="nav-link active" href="orders.php"><i class="bi bi-receipt"></i> Commandes</a></li>
            <li class="nav-item"><a class="nav-link" href="users.php"><i class="bi bi-people"></i> Utilisateurs</a></li>
            <li class="nav-item mt-4"><a class="nav-link" href="../index.php"><i class="bi bi-box-arrow-left"></i> Retour au site</a></li>
        </ul>
    </div>

    <div class="main-content">
        <h2 class="mb-4">Toutes les Commandes</h2>
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Historique des commandes</h5>
                <div class="w-25">
                    <input type="text" class="form-control" id="searchInput" placeholder="Rechercher...">
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0" id="ordersTable">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Utilisateur</th>
                                <th>Détails des billets</th>
                                <th>Montant Total</th>
                                <th>Statut</th>
                                <th>Date</th>
                                <th>ID Transaction</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($orders)): ?>
                                <tr><td colspan="7" class="text-center text-muted p-4">Aucune commande trouvée.</td></tr>
                            <?php else: ?>
                                <?php foreach($orders as $order): ?>
                                <tr>
                                    <td>#<?php echo $order['id']; ?></td>
                                    <td><?php echo htmlspecialchars($order['username'] ?? 'Invité'); ?></td>
                                    <td class="ticket-details"><?php echo $order['ticket_details']; ?></td>
                                    <td class="amount-cell"><?php echo number_format($order['total_amount'], 2, ',', ' '); ?> MAD</td>
                                    <td>
                                        <span class="badge bg-<?php echo $order['payment_status'] === 'completed' ? 'success' : 'warning'; ?>">
                                            <?php echo htmlspecialchars(ucfirst($order['payment_status'])); ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('d/m/Y H:i', strtotime($order['created_at'])); ?></td>
                                    <td class="transaction-cell"><?php echo htmlspecialchars($order['paypal_transaction_id'] ?? 'N/A'); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer text-muted">
                Total de <?php echo count($orders); ?> commande(s).
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('searchInput');
            if(searchInput) {
                searchInput.addEventListener('keyup', function() {
                    const filter = this.value.toLowerCase();
                    const rows = document.querySelectorAll('#ordersTable tbody tr');
                    rows.forEach(row => {
                        row.style.display = row.textContent.toLowerCase().includes(filter) ? '' : 'none';
                    });
                });
            }
        });
    </script>
</body>
</html>