<?php
require_once '../config.php';

// Check admin access
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../index.php');
    exit;
}

try {
    $pdo = getDbConnection();
    
    // Get statistics
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM matches");
    $matches_count = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM ticket_categories");
    $tickets_count = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM users");
    $users_count = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    $stmt = $pdo->query("SELECT SUM(total_amount) as total FROM orders WHERE payment_status = 'completed'");
    $total_revenue = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
} catch (PDOException $e) {
    die("Erreur de base de données");
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - Billetterie Football Maroc</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
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
            --gradient-primary: linear-gradient(135deg, #003366 0%, #004080 100%);
            --gradient-action: linear-gradient(135deg, #FF9800 0%, #FFB74D 100%);
            --shadow-sm: 0 2px 4px rgba(0, 51, 102, 0.1);
            --shadow-md: 0 4px 8px rgba(0, 51, 102, 0.15);
            --shadow-lg: 0 8px 16px rgba(0, 51, 102, 0.2);
            --shadow-xl: 0 12px 24px rgba(0, 51, 102, 0.25);
            --border-radius: 12px;
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: var(--bg-light);
            color: var(--text-primary);
            line-height: 1.6;
            font-weight: 400;
        }

        /* Sidebar */
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            bottom: 0;
            width: 250px;
            background: var(--primary-blue);
            color: white;
            padding-top: 1rem;
            overflow-y: auto;
            z-index: 1000;
        }

        .sidebar-header {
            padding: 0 1.5rem 1.5rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .sidebar-title {
            font-weight: 700;
            font-size: 1.25rem;
            color: white;
        }

        .nav-link {
            color: rgba(255, 255, 255, 0.8);
            padding: 0.75rem 1.5rem;
            margin: 0.25rem 0;
            border-radius: 6px;
            transition: var(--transition);
        }

        .nav-link:hover {
            color: white;
            background: rgba(255, 255, 255, 0.1);
        }

        .nav-link.active {
            color: white;
            background: var(--action-orange);
            font-weight: 500;
        }

        .nav-link i {
            width: 24px;
            margin-right: 0.75rem;
            text-align: center;
        }

        /* Main Content */
        .main-content {
            margin-left: 250px;
            padding: 2rem;
            min-height: 100vh;
            background: var(--bg-light);
        }

        /* Dashboard Cards */
        .stat-card {
            border: none;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-md);
            transition: var(--transition);
            overflow: hidden;
            position: relative;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-lg);
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: rgba(255, 255, 255, 0.3);
        }

        .stat-card-primary {
            background: var(--gradient-primary);
            color: white;
        }

        .stat-card-success {
            background: linear-gradient(135deg, var(--accent-green) 0%, #66BB6A 100%);
            color: white;
        }

        .stat-card-info {
            background: linear-gradient(135deg, #29B6F6 0%, #4FC3F7 100%);
            color: white;
        }

        .stat-card-warning {
            background: var(--gradient-action);
            color: white;
        }

        .stat-card-body {
            padding: 1.5rem;
            position: relative;
            z-index: 10;
        }

        .stat-card-title {
            font-size: 1rem;
            font-weight: 500;
            margin-bottom: 0.5rem;
            opacity: 0.9;
        }

        .stat-card-value {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 0;
        }

        .stat-card-icon {
            position: absolute;
            top: 1.5rem;
            right: 1.5rem;
            font-size: 2.5rem;
            opacity: 0.2;
        }

        /* Recent Orders Card */
        .recent-orders {
            border: none;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-md);
            overflow: hidden;
        }

        .card-header {
            background: var(--bg-white);
            border-bottom: 1px solid var(--border-color);
            padding: 1.25rem 1.5rem;
            font-weight: 600;
        }

        .table {
            margin-bottom: 0;
        }

        .table th {
            border-top: none;
            font-weight: 600;
            color: var(--text-secondary);
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 0.5px;
        }

        .table td {
            vertical-align: middle;
        }

        .badge {
            padding: 0.35em 0.65em;
            font-weight: 500;
            border-radius: 50px;
            font-size: 0.75rem;
        }

        /* Responsive Design */
        @media (max-width: 992px) {
            .sidebar {
                width: 220px;
            }
            
            .main-content {
                margin-left: 220px;
            }
            
            .stat-card-value {
                font-size: 1.75rem;
            }
        }

        @media (max-width: 768px) {
            .sidebar {
                width: 100%;
                position: static;
                height: auto;
            }
            
            .main-content {
                margin-left: 0;
                padding: 1.5rem;
            }
            
            .stat-card {
                margin-bottom: 1rem;
            }
        }

        @media (max-width: 576px) {
            .main-content {
                padding: 1rem;
            }
            
            .stat-card-title {
                font-size: 0.875rem;
            }
            
            .stat-card-value {
                font-size: 1.5rem;
            }
            
            .stat-card-icon {
                font-size: 2rem;
            }
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="sidebar-header">
            <h4 class="sidebar-title">Admin Panel</h4>
        </div>
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link active" href="dashboard.php">
                    <i class="bi bi-speedometer2"></i> Dashboard
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="matches.php">
                    <i class="bi bi-calendar-event"></i> Gestion des Matchs
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="tickets.php">
                    <i class="bi bi-ticket-perforated"></i> Gestion des Billets
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="orders.php">
                    <i class="bi bi-receipt"></i> Commandes
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="users.php">
                    <i class="bi bi-people"></i> Utilisateurs
                </a>
            </li>
            <li class="nav-item mt-4">
                <a class="nav-link" href="../index.php">
                    <i class="bi bi-box-arrow-left"></i> Retour au site
                </a>
            </li>
        </ul>
    </div>

    <div class="main-content">
        <h2 class="mb-4">Dashboard</h2>
        
        <div class="row">
            <div class="col-md-6 col-lg-3 mb-4">
                <div class="stat-card stat-card-primary">
                    <div class="stat-card-body">
                        <h5 class="stat-card-title">Matchs</h5>
                        <p class="stat-card-value"><?php echo $matches_count; ?></p>
                        <i class="bi bi-calendar-event stat-card-icon"></i>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6 col-lg-3 mb-4">
                <div class="stat-card stat-card-success">
                    <div class="stat-card-body">
                        <h5 class="stat-card-title">Billets</h5>
                        <p class="stat-card-value"><?php echo $tickets_count; ?></p>
                        <i class="bi bi-ticket-perforated stat-card-icon"></i>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6 col-lg-3 mb-4">
                <div class="stat-card stat-card-info">
                    <div class="stat-card-body">
                        <h5 class="stat-card-title">Utilisateurs</h5>
                        <p class="stat-card-value"><?php echo $users_count; ?></p>
                        <i class="bi bi-people stat-card-icon"></i>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6 col-lg-3 mb-4">
                <div class="stat-card stat-card-warning">
                    <div class="stat-card-body">
                        <h5 class="stat-card-title">Revenu</h5>
                        <p class="stat-card-value"><?php echo number_format($total_revenue, 2, ',', ' '); ?> MAD</p>
                        <i class="bi bi-currency-euro stat-card-icon"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="recent-orders mt-4">
            <div class="card-header">
                <h5 class="mb-0">Commandes récentes</h5>
            </div>
            <div class="card-body">
                <?php
                $stmt = $pdo->query("SELECT o.*, u.username 
                                    FROM orders o 
                                    LEFT JOIN users u ON o.user_id = u.id 
                                    ORDER BY o.created_at DESC 
                                    LIMIT 5");
                $recent_orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
                ?>
                
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Utilisateur</th>
                                <th>Montant</th>
                                <th>Statut</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($recent_orders as $order): ?>
                            <tr>
                                <td><?php echo $order['id']; ?></td>
                                <td><?php echo htmlspecialchars($order['username'] ?? 'Invité'); ?></td>
                                <td><?php echo number_format($order['total_amount'], 2, ',', ' '); ?> MAD</td>
                                <td>
                                    <span class="badge bg-<?php echo $order['payment_status'] === 'completed' ? 'success' : 'warning'; ?>">
                                        <?php echo ucfirst($order['payment_status']); ?>
                                    </span>
                                </td>
                                <td><?php echo date('d/m/Y H:i', strtotime($order['created_at'])); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>