<?php
require_once 'config.php';

// Check if user is logged in
$isLoggedIn = isset($_SESSION['user_id']);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Billetterie Football Maroc</title>
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
            --gradient-hero: linear-gradient(135deg, #003366 0%, #4CAF50 100%);
            --gradient-action: linear-gradient(135deg, #FF9800 0%, #FFB74D 100%);
            --gradient-card: linear-gradient(135deg, #FFFFFF 0%, #F8F9FA 100%);
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

        /* Header avec navigation moderne */
        .navbar {
            background: var(--bg-white);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid var(--border-color);
            box-shadow: var(--shadow-sm);
            padding: 1rem 0;
        }

        .navbar-brand {
            font-weight: 800;
            font-size: 1.75rem;
            color: var(--primary-blue) !important;
            text-decoration: none;
        }

        .navbar-nav .nav-link {
            font-weight: 500;
            color: var(--text-secondary) !important;
            margin: 0 0.5rem;
            padding: 0.5rem 1rem !important;
            border-radius: 8px;
            transition: var(--transition);
        }

        .navbar-nav .nav-link:hover {
            color: var(--primary-blue) !important;
            background: rgba(0, 51, 102, 0.1);
        }

        /* Section Hero */
        .hero-section {
            background: var(--gradient-hero);
            padding: 6rem 0 4rem;
            position: relative;
            overflow: hidden;
            margin-bottom: 4rem;
        }

        .hero-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grid" width="10" height="10" patternUnits="userSpaceOnUse"><path d="M 10 0 L 0 0 0 10" fill="none" stroke="rgba(255,255,255,0.1)" stroke-width="0.5"/></pattern></defs><rect width="100" height="100" fill="url(%23grid)"/></svg>');
            opacity: 0.3;
        }

        .hero-section::after {
            content: '';
            position: absolute;
            top: -50%;
            right: -20%;
            width: 300px;
            height: 300px;
            background: radial-gradient(circle, rgba(255, 152, 0, 0.2) 0%, transparent 70%);
            border-radius: 50%;
            animation: float 6s ease-in-out infinite;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            50% { transform: translateY(-20px) rotate(180deg); }
        }

        .hero-content {
            position: relative;
            z-index: 10;
            text-align: center;
            color: white;
        }

        .hero-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(10px);
            padding: 0.75rem 1.5rem;
            border-radius: 30px;
            font-size: 0.875rem;
            font-weight: 600;
            margin-bottom: 2rem;
            border: 1px solid rgba(255, 255, 255, 0.3);
        }

        .hero-title {
            font-size: 4rem;
            font-weight: 900;
            line-height: 1.1;
            margin-bottom: 1.5rem;
            text-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
            letter-spacing: -0.02em;
        }

        .hero-subtitle {
            font-size: 1.25rem;
            margin-bottom: 3rem;
            opacity: 0.9;
            max-width: 600px;
            margin-left: auto;
            margin-right: auto;
            font-weight: 400;
        }

        .hero-actions {
            display: flex;
            gap: 1rem;
            justify-content: center;
            flex-wrap: wrap;
            margin-bottom: 4rem;
        }

        .hero-btn {
            padding: 1rem 2rem;
            border-radius: 12px;
            font-weight: 600;
            text-decoration: none;
            transition: var(--transition);
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            min-width: 180px;
            justify-content: center;
        }

        .hero-btn-primary {
            background: var(--gradient-action);
            color: white;
            box-shadow: 0 4px 16px rgba(255, 152, 0, 0.4);
        }

        .hero-btn-primary:hover {
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(255, 152, 0, 0.5);
        }

        .hero-btn-secondary {
            background: rgba(255, 255, 255, 0.2);
            color: white;
            border: 2px solid rgba(255, 255, 255, 0.3);
            backdrop-filter: blur(10px);
        }

        .hero-btn-secondary:hover {
            background: rgba(255, 255, 255, 0.3);
            color: white;
            border-color: rgba(255, 255, 255, 0.5);
            transform: translateY(-2px);
        }

        .hero-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 2rem;
            max-width: 800px;
            margin: 0 auto;
        }

        .hero-stat {
            text-align: center;
            padding: 1.5rem;
            background: rgba(255, 255, 255, 0.1);
            border-radius: var(--border-radius);
            border: 1px solid rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(10px);
        }

        .hero-stat-number {
            font-size: 2.5rem;
            font-weight: 800;
            color: var(--action-orange);
            display: block;
            text-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
        }

        .hero-stat-label {
            font-size: 0.875rem;
            opacity: 0.9;
            font-weight: 500;
            margin-top: 0.5rem;
        }

        /* Floating football animation */
        .floating-football {
            position: absolute;
            color: rgba(255, 255, 255, 0.2);
            animation: floatFootball 8s ease-in-out infinite;
        }

        .floating-football-1 {
            top: 20%;
            left: 10%;
            font-size: 2rem;
            animation-delay: 0s;
        }

        .floating-football-2 {
            top: 60%;
            right: 15%;
            font-size: 1.5rem;
            animation-delay: 2s;
        }

        .floating-football-3 {
            top: 40%;
            left: 80%;
            font-size: 1.8rem;
            animation-delay: 4s;
        }

        @keyframes floatFootball {
            0%, 100% { transform: translateY(0px) rotate(0deg); opacity: 0.2; }
            25% { transform: translateY(-15px) rotate(90deg); opacity: 0.3; }
            50% { transform: translateY(-30px) rotate(180deg); opacity: 0.2; }
            75% { transform: translateY(-15px) rotate(270deg); opacity: 0.3; }
        }

        /* Container principal */
        .main-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 2rem 1rem;
        }

        /* En-tête de la page stylisée */
        .page-header {
            background: var(--gradient-primary);
            border-radius: var(--border-radius);
            padding: 3rem 2rem;
            margin-bottom: 3rem;
            box-shadow: var(--shadow-xl);
            position: relative;
            overflow: hidden;
        }

        .page-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><circle cx="50" cy="50" r="40" fill="none" stroke="rgba(255,255,255,0.1)" stroke-width="2"/><circle cx="50" cy="50" r="20" fill="none" stroke="rgba(255,255,255,0.1)" stroke-width="1"/></svg>') center/100px 100px;
            opacity: 0.3;
        }

        .page-header::after {
            content: '';
            position: absolute;
            top: -50%;
            right: -20%;
            width: 200px;
            height: 200px;
            background: radial-gradient(circle, rgba(76, 175, 80, 0.2) 0%, transparent 70%);
            border-radius: 50%;
        }

        .page-title {
            font-size: 3rem;
            font-weight: 800;
            color: white;
            margin: 0;
            text-align: center;
            position: relative;
            z-index: 10;
            text-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
            letter-spacing: -0.02em;
        }

        .page-subtitle {
            font-size: 1.1rem;
            color: rgba(255, 255, 255, 0.9);
            text-align: center;
            margin-top: 0.5rem;
            position: relative;
            z-index: 10;
            font-weight: 400;
        }

        /* Icône de football animée */
        .football-icon {
            position: absolute;
            top: 1rem;
            left: 2rem;
            font-size: 2rem;
            color: var(--accent-green);
            animation: bounce 2s infinite;
            z-index: 10;
        }

        @keyframes bounce {
            0%, 20%, 50%, 80%, 100% {
                transform: translateY(0);
            }
            40% {
                transform: translateY(-10px);
            }
            60% {
                transform: translateY(-5px);
            }
        }

        /* Badge de count des matchs */
        .matches-count {
            position: absolute;
            top: 1rem;
            right: 2rem;
            background: var(--action-orange);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.875rem;
            font-weight: 600;
            z-index: 10;
            box-shadow: 0 2px 8px rgba(255, 152, 0, 0.3);
        }

        /* Grille des matchs */
        .matches-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(400px, 1fr));
            gap: 2rem;
            margin-top: 2rem;
        }

        /* Cartes de matchs - Design sportif */
        .match-card {
            background: var(--bg-white);
            border-radius: var(--border-radius);
            overflow: hidden;
            box-shadow: var(--shadow-md);
            border: 1px solid var(--border-color);
            transition: var(--transition);
            position: relative;
        }

        .match-card:hover {
            transform: translateY(-8px);
            box-shadow: var(--shadow-xl);
            border-color: var(--accent-green);
        }

        /* Image du match avec overlays */
        .match-image-container {
            position: relative;
            height: 200px;
            overflow: hidden;
            background: var(--gradient-primary);
        }

        .match-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: var(--transition);
        }

        .match-card:hover .match-image {
            transform: scale(1.05);
        }

        .image-placeholder {
            height: 100%;
            background: var(--gradient-primary);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 3rem;
        }

        /* Overlay avec les équipes */
        .teams-overlay {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background: linear-gradient(transparent, rgba(0, 51, 102, 0.9));
            padding: 2rem 1.5rem 1.5rem;
            color: white;
        }

        .teams-display {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
        }

        .team-info {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            flex: 1;
        }

        .team-logo {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--accent-green);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            color: white;
            font-size: 0.875rem;
        }

        .team-name {
            font-weight: 600;
            font-size: 1rem;
        }

        .vs-separator {
            background: var(--action-orange);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-weight: 700;
            font-size: 0.875rem;
            box-shadow: 0 2px 8px rgba(255, 152, 0, 0.3);
        }

        /* Contenu de la carte */
        .card-content {
            padding: 1.5rem;
        }

        .match-details {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .detail-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.875rem;
            color: var(--text-secondary);
        }

        .detail-icon {
            color: var(--accent-green);
            font-size: 1rem;
        }

        .detail-value {
            font-weight: 500;
            color: var(--text-primary);
        }

        /* Section prix et billets */
        .pricing-section {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 1.5rem;
            background: var(--bg-light);
            border-top: 1px solid var(--border-color);
        }

        .price-range {
            display: flex;
            flex-direction: column;
        }

        .price-label {
            font-size: 0.75rem;
            color: var(--text-secondary);
            font-weight: 500;
        }

        .price-value {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--primary-blue);
        }

        .tickets-info {
            text-align: right;
        }

        .tickets-available {
            font-size: 0.75rem;
            color: var(--success-green);
            font-weight: 600;
        }

        .tickets-limited {
            color: var(--action-orange);
        }

        .tickets-sold-out {
            color: var(--error-red);
        }

        /* Bouton de réservation */
        .btn-book {
            width: 100%;
            padding: 1rem 1.5rem;
            background: var(--gradient-action);
            border: none;
            border-radius: 8px;
            color: white;
            font-weight: 600;
            text-decoration: none;
            text-align: center;
            transition: var(--transition);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            margin-top: 1rem;
            box-shadow: 0 2px 8px rgba(255, 152, 0, 0.3);
        }

        .btn-book:hover {
            background: linear-gradient(135deg, #F57C00 0%, #FF9800 100%);
            color: white;
            text-decoration: none;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(255, 152, 0, 0.4);
        }

        .btn-book:disabled {
            background: var(--text-secondary);
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }

        /* Badge de statut */
        .status-badge {
            position: absolute;
            top: 1rem;
            right: 1rem;
            padding: 0.25rem 0.75rem;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            z-index: 10;
        }

        .status-available {
            background: var(--success-green);
            color: white;
        }

        .status-limited {
            background: var(--action-orange);
            color: white;
        }

        .status-sold-out {
            background: var(--error-red);
            color: white;
        }

        /* Alertes */
        .alert {
            border: none;
            border-radius: var(--border-radius);
            padding: 1.5rem;
            margin-bottom: 2rem;
            border-left: 4px solid;
        }

        .alert-info {
            background: rgba(76, 175, 80, 0.1);
            color: var(--primary-blue);
            border-left-color: var(--accent-green);
        }

        .alert-danger {
            background: rgba(229, 57, 53, 0.1);
            color: var(--error-red);
            border-left-color: var(--error-red);
        }

        /* Responsive Design */
        @media (max-width: 992px) {
            .hero-title {
                font-size: 3rem;
            }
            
            .hero-subtitle {
                font-size: 1.1rem;
            }
            
            .hero-stats {
                grid-template-columns: repeat(2, 1fr);
                gap: 1rem;
            }
            
            .matches-grid {
                grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
                gap: 1.5rem;
            }
        }

        @media (max-width: 768px) {
            .hero-section {
                padding: 4rem 0 3rem;
            }
            
            .hero-title {
                font-size: 2.5rem;
            }
            
            .hero-subtitle {
                font-size: 1rem;
                margin-bottom: 2rem;
            }
            
            .hero-actions {
                flex-direction: column;
                align-items: center;
                margin-bottom: 3rem;
            }
            
            .hero-btn {
                width: 100%;
                max-width: 300px;
            }
            
            .hero-stats {
                grid-template-columns: 1fr;
            }
            
            .main-container {
                padding: 1rem;
            }

            .page-header {
                padding: 2rem 1.5rem;
                margin-bottom: 2rem;
            }

            .page-title {
                font-size: 2.2rem;
            }

            .page-subtitle {
                font-size: 1rem;
            }

            .football-icon {
                top: 0.8rem;
                left: 1rem;
                font-size: 1.5rem;
            }

            .matches-count {
                top: 0.8rem;
                right: 1rem;
                padding: 0.4rem 0.8rem;
                font-size: 0.8rem;
            }

            .matches-grid {
                grid-template-columns: 1fr;
                gap: 1rem;
            }

            .teams-display {
                flex-direction: column;
                gap: 0.5rem;
                text-align: center;
            }

            .match-details {
                grid-template-columns: 1fr;
                gap: 0.75rem;
            }
        }

        @media (max-width: 480px) {
            .hero-title {
                font-size: 2rem;
            }
            
            .hero-stat-number {
                font-size: 2rem;
            }
            
            .page-header {
                padding: 1.5rem 1rem;
            }

            .page-title {
                font-size: 1.8rem;
            }

            .page-subtitle {
                font-size: 0.9rem;
            }

            .football-icon,
            .matches-count {
                position: static;
                display: none;
            }

            .pricing-section {
                flex-direction: column;
                gap: 1rem;
                text-align: center;
            }

            .team-name {
                font-size: 0.875rem;
            }

            .match-image-container {
                height: 160px;
            }
        }

        /* Animations et micro-interactions */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .match-card {
            animation: fadeInUp 0.6s ease-out;
        }

        .match-card:nth-child(1) { animation-delay: 0.1s; }
        .match-card:nth-child(2) { animation-delay: 0.2s; }
        .match-card:nth-child(3) { animation-delay: 0.3s; }
        .match-card:nth-child(4) { animation-delay: 0.4s; }
        .match-card:nth-child(5) { animation-delay: 0.5s; }
        .match-card:nth-child(6) { animation-delay: 0.6s; }

        /* États de focus pour l'accessibilité */
        .btn-book:focus,
        .hero-btn:focus {
            outline: none;
            box-shadow: 0 0 0 3px rgba(255, 152, 0, 0.3);
        }

        /* Effets de survol améliorés */
        .match-card:hover .team-logo {
            transform: scale(1.1);
        }

        .match-card:hover .vs-separator {
            transform: scale(1.05);
        }

        /* Gradient de loading pour les images */
        .image-placeholder {
            background: linear-gradient(45deg, var(--primary-blue), var(--accent-green));
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <!-- Section Hero -->
    <section class="hero-section">
        <!-- Ballons de football flottants -->
        <div class="floating-football floating-football-1">
            <i class="bi bi-dribbble"></i>
        </div>
        <div class="floating-football floating-football-2">
            <i class="bi bi-dribbble"></i>
        </div>
        <div class="floating-football floating-football-3">
            <i class="bi bi-dribbble"></i>
        </div>
        
        <div class="container">
            <div class="hero-content">
                <div class="hero-badge">
                    <i class="bi bi-lightning-fill"></i>
                    Billetterie Officielle
                </div>
                
                <h1 class="hero-title">
                    Vivez la Passion du<br>
                    <span style="color: var(--action-orange);">Football Marocain</span>
                </h1>
                
                <p class="hero-subtitle">
                    Découvrez tous les matchs de la Botola Pro et réservez vos places pour vivre des moments inoubliables dans les stades du Maroc.
                </p>
                
                <div class="hero-actions">
                    <a href="#matches" class="hero-btn hero-btn-primary">
                        <i class="bi bi-ticket-perforated"></i>
                        Voir les Matchs
                    </a>
                    <a href="event.html" class="hero-btn hero-btn-secondary">
                    <i class="bi bi-info-circle"></i>
                        Hôtels & Événements
                    </a>
                </div>
                
                <div class="hero-stats">
                    <div class="hero-stat">
                        <span class="hero-stat-number">25+</span>
                        <span class="hero-stat-label">Matchs disponibles</span>
                    </div>
                    <div class="hero-stat">
                        <span class="hero-stat-number">50K+</span>
                        <span class="hero-stat-label">Billets vendus</span>
                    </div>
                    <div class="hero-stat">
                        <span class="hero-stat-number">15</span>
                        <span class="hero-stat-label">Stades partenaires</span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <div class="main-container" id="matches">
        <!-- En-tête stylisée -->
        <div class="page-header">
            <i class="bi bi-dribbble football-icon"></i>
            <div class="matches-count">
                <i class="bi bi-calendar-event me-1"></i>
                <?php echo count($matches ?? []); ?> matchs
            </div>
            <h1 class="page-title">Matchs à venir</h1>
            <p class="page-subtitle">Découvrez les prochains matchs de football et réservez vos places</p>
        </div>
        
        <?php
        try {
            $pdo = getDbConnection();
            $stmt = $pdo->query("SELECT * FROM matches WHERE match_date > NOW() ORDER BY match_date");
            $matches = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            echo "<div class='alert alert-danger'>
                    <i class='bi bi-exclamation-triangle me-2'></i>
                    Erreur lors du chargement des matchs
                  </div>";
        }
        ?>

        <?php if(empty($matches)): ?>
        <div class="alert alert-info">
            <i class="bi bi-info-circle me-2"></i>
            Aucun match à venir pour le moment. Revenez bientôt pour découvrir les prochains matchs !
        </div>
        <?php else: ?>
        <div class="matches-grid">
            <?php foreach($matches as $match): ?>
            <article class="match-card">
                <div class="status-badge status-available">Disponible</div>
                
                <div class="match-image-container">
                    <?php if(!empty($match['image_url'])): ?>
                        <img src="<?php echo htmlspecialchars($match['image_url']); ?>" 
                             class="match-image" 
                             alt="Match <?php echo htmlspecialchars($match['home_team'] . ' vs ' . $match['away_team']); ?>">
                    <?php else: ?>
                        <div class="image-placeholder">
                            <i class="bi bi-camera"></i>
                        </div>
                    <?php endif; ?>
                    
                    <div class="teams-overlay">
                        <div class="teams-display">
                            <div class="team-info">
                                <div class="team-logo">
                                    <?php echo strtoupper(substr($match['home_team'], 0, 3)); ?>
                                </div>
                                <span class="team-name"><?php echo htmlspecialchars($match['home_team']); ?></span>
                            </div>
                            
                            <div class="vs-separator">VS</div>
                            
                            <div class="team-info">
                                <span class="team-name"><?php echo htmlspecialchars($match['away_team']); ?></span>
                                <div class="team-logo">
                                    <?php echo strtoupper(substr($match['away_team'], 0, 3)); ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="card-content">
                    <div class="match-details">
                        <div class="detail-item">
                            <i class="bi bi-calendar-event detail-icon"></i>
                            <span class="detail-value"><?php echo date('d/m/Y', strtotime($match['match_date'])); ?></span>
                        </div>
                        
                        <div class="detail-item">
                            <i class="bi bi-clock detail-icon"></i>
                            <span class="detail-value"><?php echo date('H:i', strtotime($match['match_date'])); ?></span>
                        </div>
                        
                        <div class="detail-item">
                            <i class="bi bi-geo-alt detail-icon"></i>
                            <span class="detail-value"><?php echo htmlspecialchars($match['stadium']); ?></span>
                        </div>
                        
                        <div class="detail-item">
                            <i class="bi bi-trophy detail-icon"></i>
                            <span class="detail-value">Botola Pro</span>
                        </div>
                    </div>
                </div>
                
                <div class="pricing-section">
                    <div class="price-range">
                        <span class="price-label">À partir de</span>
                        <span class="price-value">150 MAD</span>
                    </div>
                    <div class="tickets-info">
                        <div class="tickets-available">Billets disponibles</div>
                        <small class="text-muted">1,250 / 2,000 vendus</small>
                    </div>
                </div>
                
                <div style="padding: 1rem 1.5rem 1.5rem;">
                    <a href="match.php?id=<?php echo $match['id']; ?>" class="btn-book">
                        <i class="bi bi-ticket-perforated"></i>
                        Voir les billets
                    </a>
                </div>
            </article>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

    <?php include 'includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Smooth scroll pour le bouton "Voir les Matchs"
        document.addEventListener('DOMContentLoaded', function() {
            const heroBtn = document.querySelector('a[href="#matches"]');
            if (heroBtn) {
                heroBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    const target = document.querySelector('#matches');
                    if (target) {
                        target.scrollIntoView({
                            behavior: 'smooth',
                            block: 'start'
                        });
                    }
                });
            }
        });
    </script>
</body>
</html>