<?php
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    try {
        $pdo = getDbConnection();
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password'])) {
            // Regenerate session ID for security
            session_regenerate_id(true);
            
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            
            // If user was a guest, migrate cart items
            if (isset($_SESSION['guest_cart'])) {
                $pdo->beginTransaction();
                try {
                    // First, insert cart items into database
                    foreach ($_SESSION['guest_cart'] as $item) {
                        $stmt = $pdo->prepare("INSERT INTO cart_items (session_id, ticket_category_id, quantity) VALUES (?, ?, ?)");
                        $stmt->execute([session_id(), $item['ticket_category_id'], $item['quantity']]);
                    }
                    $pdo->commit();
                } catch (Exception $e) {
                    $pdo->rollBack();
                }
            }
            
            header('Location: index.php');
            exit;
        } else {
            $error = "Email ou mot de passe incorrect";
        }
    } catch (PDOException $e) {
        $error = "Erreur lors de la connexion";
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion - Billetterie Football Maroc</title>
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
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .login-container {
            display: flex;
            flex: 1;
            align-items: center;
            justify-content: center;
            padding: 2rem 1rem;
        }

        .login-card {
            width: 100%;
            max-width: 500px;
            background: var(--bg-white);
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-xl);
            overflow: hidden;
            border: none;
            transition: var(--transition);
        }

        .login-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(0, 51, 102, 0.2);
        }

        .login-header {
            background: var(--gradient-primary);
            color: white;
            padding: 2rem;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .login-header::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -20%;
            width: 200px;
            height: 200px;
            background: radial-gradient(circle, rgba(76, 175, 80, 0.2) 0%, transparent 70%);
            border-radius: 50%;
        }

        .login-title {
            font-size: 2rem;
            font-weight: 800;
            margin-bottom: 0.5rem;
            position: relative;
            z-index: 10;
            text-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
        }

        .login-subtitle {
            font-size: 1rem;
            opacity: 0.9;
            font-weight: 400;
            position: relative;
            z-index: 10;
        }

        .login-body {
            padding: 2rem;
        }

        .form-label {
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 0.5rem;
        }

        .form-control {
            padding: 0.75rem 1rem;
            border-radius: var(--border-radius);
            border: 1px solid var(--border-color);
            transition: var(--transition);
        }

        .form-control:focus {
            border-color: var(--accent-green);
            box-shadow: 0 0 0 3px rgba(76, 175, 80, 0.2);
        }

        .btn-login {
            width: 100%;
            padding: 1rem;
            background: var(--gradient-action);
            border: none;
            border-radius: var(--border-radius);
            color: white;
            font-weight: 600;
            transition: var(--transition);
        }

        .btn-login:hover {
            background: linear-gradient(135deg, #F57C00 0%, #FF9800 100%);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(255, 152, 0, 0.4);
        }

        .login-footer {
            text-align: center;
            margin-top: 1.5rem;
            color: var(--text-secondary);
        }

        .login-link {
            color: var(--primary-blue);
            font-weight: 600;
            text-decoration: none;
            transition: var(--transition);
        }

        .login-link:hover {
            color: var(--action-orange);
            text-decoration: underline;
        }

        .alert {
            border: none;
            border-radius: var(--border-radius);
            padding: 1rem;
            margin-bottom: 1.5rem;
            border-left: 4px solid;
        }

        .alert-danger {
            background: rgba(229, 57, 53, 0.1);
            color: var(--error-red);
            border-left-color: var(--error-red);
        }

        /* Floating football animation */
        .floating-football {
            position: absolute;
            color: rgba(255, 255, 255, 0.2);
            animation: floatFootball 8s ease-in-out infinite;
            z-index: 1;
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

        @keyframes floatFootball {
            0%, 100% { transform: translateY(0px) rotate(0deg); opacity: 0.2; }
            25% { transform: translateY(-15px) rotate(90deg); opacity: 0.3; }
            50% { transform: translateY(-30px) rotate(180deg); opacity: 0.2; }
            75% { transform: translateY(-15px) rotate(270deg); opacity: 0.3; }
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .login-container {
                padding: 1rem;
            }
            
            .login-title {
                font-size: 1.8rem;
            }
            
            .login-body {
                padding: 1.5rem;
            }
        }

        @media (max-width: 480px) {
            .login-header {
                padding: 1.5rem;
            }
            
            .login-title {
                font-size: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <!-- Ballons de football flottants -->
                <div class="floating-football floating-football-1">
                    <i class="bi bi-dribbble"></i>
                </div>
                <div class="floating-football floating-football-2">
                    <i class="bi bi-dribbble"></i>
                </div>
                
                <h1 class="login-title">Connexion</h1>
                <p class="login-subtitle">Accédez à votre compte</p>
            </div>
            
            <div class="login-body">
                <?php if(isset($error)): ?>
                <div class="alert alert-danger">
                    <i class="bi bi-exclamation-triangle me-2"></i>
                    <?php echo htmlspecialchars($error); ?>
                </div>
                <?php endif; ?>

                <form method="POST" action="">
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" name="email" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="password" class="form-label">Mot de passe</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                    
                    <button type="submit" class="btn btn-login">
                        <i class="bi bi-box-arrow-in-right"></i> Se connecter
                    </button>
                </form>
                
                <div class="login-footer">
                    Pas encore inscrit ? <a href="register.php" class="login-link">Créer un compte</a>
                </div>
            </div>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>