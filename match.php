<?php
require_once 'config.php';

$match_id = $_GET['id'] ?? null;
if (!$match_id) {
    header('Location: index.php');
    exit;
}

try {
    $pdo = getDbConnection();
    
    // Get match details
    $stmt = $pdo->prepare("SELECT * FROM matches WHERE id = ?");
    $stmt->execute([$match_id]);
    $match = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$match) {
        header('Location: index.php');
        exit;
    }
    
    // Get ticket categories
    $stmt = $pdo->prepare("SELECT * FROM ticket_categories WHERE match_id = ?");
    $stmt->execute([$match_id]);
    $ticket_categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Erreur de base de données");
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($match['home_team']); ?> vs <?php echo htmlspecialchars($match['away_team']); ?> - Billetterie Football Maroc</title>
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

        .main-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 2rem 1rem;
        }

        /* Header du match */
        .match-header {
            background: var(--gradient-primary);
            border-radius: var(--border-radius);
            padding: 3rem 2rem;
            margin-bottom: 3rem;
            box-shadow: var(--shadow-xl);
            position: relative;
            overflow: hidden;
            color: white;
        }

        .match-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><circle cx="50" cy="50" r="40" fill="none" stroke="rgba(255,255,255,0.1)" stroke-width="2"/><circle cx="50" cy="50" r="20" fill="none" stroke="rgba(255,255,255,0.1)" stroke-width="1"/></svg>') center/100px 100px;
            opacity: 0.3;
        }

        .match-title {
            font-size: 2.5rem;
            font-weight: 800;
            margin-bottom: 1rem;
            text-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
            letter-spacing: -0.02em;
        }

        .match-subtitle {
            font-size: 1.1rem;
            opacity: 0.9;
            font-weight: 400;
        }

        .teams-display {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 2rem;
            margin: 2rem 0;
        }

        .team-info {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 1rem;
        }

        .team-logo {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: var(--accent-green);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            color: white;
            font-size: 1.5rem;
            box-shadow: var(--shadow-md);
        }

        .team-name {
            font-weight: 700;
            font-size: 1.5rem;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
        }

        .vs-separator {
            background: var(--action-orange);
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: 30px;
            font-weight: 800;
            font-size: 1.25rem;
            box-shadow: 0 4px 12px rgba(255, 152, 0, 0.3);
        }

        /* Détails du match */
        .match-details-container {
            background: var(--bg-white);
            border-radius: var(--border-radius);
            padding: 2rem;
            box-shadow: var(--shadow-md);
            margin-bottom: 2rem;
        }

        .detail-item {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1.5rem;
            padding-bottom: 1.5rem;
            border-bottom: 1px solid var(--border-color);
        }

        .detail-item:last-child {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }

        .detail-icon {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            background: rgba(76, 175, 80, 0.1);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--accent-green);
            font-size: 1.25rem;
        }

        .detail-content h4 {
            font-weight: 600;
            margin-bottom: 0.25rem;
        }

        .detail-content p {
            color: var(--text-secondary);
            margin-bottom: 0;
        }

        /* Section des billets */
        .tickets-container {
            background: var(--bg-white);
            border-radius: var(--border-radius);
            padding: 2rem;
            box-shadow: var(--shadow-md);
        }

        .tickets-title {
            font-size: 1.75rem;
            font-weight: 700;
            margin-bottom: 1.5rem;
            color: var(--primary-blue);
            position: relative;
            padding-bottom: 0.75rem;
        }

        .tickets-title::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 60px;
            height: 3px;
            background: var(--action-orange);
        }

        .ticket-category {
            background: var(--bg-light);
            border-radius: var(--border-radius);
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            border: 1px solid var(--border-color);
            transition: var(--transition);
        }

        .ticket-category:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-lg);
            border-color: var(--accent-green);
        }

        .ticket-category h4 {
            font-weight: 600;
            color: var(--primary-blue);
            margin-bottom: 0.75rem;
        }

        .ticket-price {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--action-orange);
            margin-bottom: 0.5rem;
        }

        .ticket-available {
            font-size: 0.875rem;
            color: var(--success-green);
            font-weight: 600;
            margin-bottom: 1rem;
        }

        .ticket-sold-out {
            font-size: 0.875rem;
            color: var(--error-red);
            font-weight: 600;
            margin-bottom: 1rem;
        }

        .btn-book {
            padding: 0.75rem 1.5rem;
            background: var(--gradient-action);
            border: none;
            border-radius: 8px;
            color: white;
            font-weight: 600;
            text-decoration: none;
            transition: var(--transition);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            box-shadow: 0 2px 8px rgba(255, 152, 0, 0.3);
            width: 100%;
        }

        .btn-book:hover {
            background: linear-gradient(135deg, #F57C00 0%, #FF9800 100%);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(255, 152, 0, 0.4);
        }

        .form-control {
            border-radius: 8px;
            padding: 0.75rem 1rem;
            border: 1px solid var(--border-color);
            transition: var(--transition);
        }

        .form-control:focus {
            border-color: var(--accent-green);
            box-shadow: 0 0 0 3px rgba(76, 175, 80, 0.2);
        }

        .input-group {
            gap: 0.5rem;
        }

        /* Description du match */
        .match-description {
            background: var(--bg-white);
            border-radius: var(--border-radius);
            padding: 2rem;
            box-shadow: var(--shadow-md);
            margin-bottom: 2rem;
        }

        .match-description h3 {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 1rem;
            color: var(--primary-blue);
            position: relative;
            padding-bottom: 0.5rem;
        }

        .match-description h3::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 60px;
            height: 3px;
            background: var(--action-orange);
        }

        .match-description p {
            line-height: 1.8;
            color: var(--text-secondary);
        }

        /* Responsive Design */
        @media (max-width: 992px) {
            .match-title {
                font-size: 2rem;
            }
            
            .team-logo {
                width: 60px;
                height: 60px;
                font-size: 1.25rem;
            }
            
            .team-name {
                font-size: 1.25rem;
            }
            
            .vs-separator {
                padding: 0.5rem 1rem;
                font-size: 1rem;
            }
        }

        @media (max-width: 768px) {
            .main-container {
                padding: 1rem;
            }
            
            .match-header {
                padding: 2rem 1.5rem;
                margin-bottom: 2rem;
            }
            
            .match-title {
                font-size: 1.8rem;
            }
            
            .teams-display {
                flex-direction: column;
                gap: 1rem;
            }
            
            .vs-separator {
                transform: rotate(90deg);
            }
        }

        @media (max-width: 480px) {
            .match-header {
                padding: 1.5rem 1rem;
            }
            
            .match-title {
                font-size: 1.5rem;
            }
            
            .team-logo {
                width: 50px;
                height: 50px;
                font-size: 1rem;
            }
            
            .team-name {
                font-size: 1.1rem;
            }
            
            .detail-item {
                flex-direction: column;
                align-items: flex-start;
                gap: 0.75rem;
            }
            
            .detail-icon {
                width: 40px;
                height: 40px;
                font-size: 1rem;
            }
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="main-container">
        <!-- Header du match -->
        <div class="match-header">
            <h1 class="match-title"><?php echo htmlspecialchars($match['home_team']); ?> vs <?php echo htmlspecialchars($match['away_team']); ?></h1>
            <p class="match-subtitle"><?php echo date('d/m/Y à H:i', strtotime($match['match_date'])); ?> - <?php echo htmlspecialchars($match['stadium']); ?></p>
            
            <div class="teams-display">
                <div class="team-info">
                    <div class="team-logo">
                        <?php echo strtoupper(substr($match['home_team'], 0, 3)); ?>
                    </div>
                    <span class="team-name"><?php echo htmlspecialchars($match['home_team']); ?></span>
                </div>
                
                <div class="vs-separator">VS</div>
                
                <div class="team-info">
                    <div class="team-logo">
                        <?php echo strtoupper(substr($match['away_team'], 0, 3)); ?>
                    </div>
                    <span class="team-name"><?php echo htmlspecialchars($match['away_team']); ?></span>
                </div>
            </div>
        </div>
        
        <div class="row">
            <div class="col-lg-8">
                <?php if($match['description']): ?>
                <div class="match-description">
                    <h3>Description du match</h3>
                    <p><?php echo nl2br(htmlspecialchars($match['description'])); ?></p>
                </div>
                <?php endif; ?>
                
                <div class="match-details-container">
                    <div class="detail-item">
                        <div class="detail-icon">
                            <i class="bi bi-calendar-event"></i>
                        </div>
                        <div class="detail-content">
                            <h4>Date du match</h4>
                            <p><?php echo date('l d F Y', strtotime($match['match_date'])); ?></p>
                        </div>
                    </div>
                    
                    <div class="detail-item">
                        <div class="detail-icon">
                            <i class="bi bi-clock"></i>
                        </div>
                        <div class="detail-content">
                            <h4>Heure de début</h4>
                            <p><?php echo date('H:i', strtotime($match['match_date'])); ?></p>
                        </div>
                    </div>
                    
                    <div class="detail-item">
                        <div class="detail-icon">
                            <i class="bi bi-geo-alt"></i>
                        </div>
                        <div class="detail-content">
                            <h4>Stade</h4>
                            <p><?php echo htmlspecialchars($match['stadium']); ?></p>
                        </div>
                    </div>
                    
                    <div class="detail-item">
                        <div class="detail-icon">
                            <i class="bi bi-trophy"></i>
                        </div>
                        <div class="detail-content">
                            <h4>Compétition</h4>
                            <p>Botola Pro</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-4">
                <div class="tickets-container">
                    <h3 class="tickets-title">Billets disponibles</h3>
                    
                    <?php foreach($ticket_categories as $category): ?>
                    <div class="ticket-category">
                        <h4><?php echo htmlspecialchars($category['name']); ?></h4>
                        <div class="ticket-price"><?php echo number_format($category['price'], 2, ',', ' '); ?> MAD</div>
                        
                        <?php if($category['available_quantity'] > 0): ?>
                        <div class="ticket-available">
                            <i class="bi bi-check-circle"></i> <?php echo $category['available_quantity']; ?> billets disponibles
                        </div>
                        <form action="add_to_cart.php" method="POST">
                            <input type="hidden" name="ticket_category_id" value="<?php echo $category['id']; ?>">
                            <input type="hidden" name="match_id" value="<?php echo $match['id']; ?>">
                            <div class="input-group mb-3">
                                <input type="number" class="form-control" name="quantity" min="1" max="<?php echo $category['available_quantity']; ?>" value="1">
                                <button type="submit" class="btn-book">
                                    <i class="bi bi-cart-plus"></i> Ajouter
                                </button>
                            </div>
                        </form>
                        <?php else: ?>
                        <div class="ticket-sold-out">
                            <i class="bi bi-x-circle"></i> Complet
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>