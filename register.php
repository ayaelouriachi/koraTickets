<?php
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    $errors = [];

    // Validation
    if (empty($username)) {
        $errors[] = "Le nom d'utilisateur est requis";
    }
    
    if (empty($email)) {
        $errors[] = "L'email est requis";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Email invalide";
    }
    
    if (empty($password)) {
        $errors[] = "Le mot de passe est requis";
    } elseif (strlen($password) < 8) {
        $errors[] = "Le mot de passe doit contenir au moins 8 caractères";
    }
    
    if ($password !== $confirm_password) {
        $errors[] = "Les mots de passe ne correspondent pas";
    }

    if (empty($errors)) {
        try {
            $pdo = getDbConnection();
            
            // Check if email already exists
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $errors[] = "Cet email est déjà utilisé";
            }
            
            // Check if username already exists
            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
            $stmt->execute([$username]);
            if ($stmt->fetch()) {
                $errors[] = "Ce nom d'utilisateur est déjà utilisé";
            }
        } catch (PDOException $e) {
            $errors[] = "Erreur lors de la vérification";
        }
    }

    if (empty($errors)) {
        try {
            $pdo = getDbConnection();
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            $stmt = $pdo->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
            $stmt->execute([$username, $email, $hashed_password]);
            
            header('Location: login.php?registration=success');
            exit;
        } catch (PDOException $e) {
            $errors[] = "Erreur lors de l'inscription";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inscription - Billetterie Football Maroc</title>
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

        .register-container {
            display: flex;
            flex: 1;
            align-items: center;
            justify-content: center;
            padding: 2rem 1rem;
        }

        .register-card {
            width: 100%;
            max-width: 500px;
            background: var(--bg-white);
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-xl);
            overflow: hidden;
            border: none;
            transition: var(--transition);
        }

        .register-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(0, 51, 102, 0.2);
        }

        .register-header {
            background: var(--gradient-primary);
            color: white;
            padding: 2rem;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .register-header::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -20%;
            width: 200px;
            height: 200px;
            background: radial-gradient(circle, rgba(76, 175, 80, 0.2) 0%, transparent 70%);
            border-radius: 50%;
        }

        .register-title {
            font-size: 2rem;
            font-weight: 800;
            margin-bottom: 0.5rem;
            position: relative;
            z-index: 10;
            text-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
        }

        .register-subtitle {
            font-size: 1rem;
            opacity: 0.9;
            font-weight: 400;
            position: relative;
            z-index: 10;
        }

        .register-body {
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

        .btn-register {
            width: 100%;
            padding: 1rem;
            background: var(--gradient-action);
            border: none;
            border-radius: var(--border-radius);
            color: white;
            font-weight: 600;
            transition: var(--transition);
        }

        .btn-register:hover {
            background: linear-gradient(135deg, #F57C00 0%, #FF9800 100%);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(255, 152, 0, 0.4);
        }

        .register-footer {
            text-align: center;
            margin-top: 1.5rem;
            color: var(--text-secondary);
        }

        .register-link {
            color: var(--primary-blue);
            font-weight: 600;
            text-decoration: none;
            transition: var(--transition);
        }

        .register-link:hover {
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

        .alert-success {
            background: rgba(67, 160, 71, 0.1);
            color: var(--success-green);
            border-left-color: var(--success-green);
        }

        /* Password strength indicator */
        .password-strength {
            height: 4px;
            background: var(--bg-light);
            border-radius: 2px;
            margin-top: 0.5rem;
            overflow: hidden;
        }

        .strength-meter {
            height: 100%;
            width: 0;
            transition: width 0.3s ease;
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
            .register-container {
                padding: 1rem;
            }
            
            .register-title {
                font-size: 1.8rem;
            }
            
            .register-body {
                padding: 1.5rem;
            }
        }

        @media (max-width: 480px) {
            .register-header {
                padding: 1.5rem;
            }
            
            .register-title {
                font-size: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="register-container">
        <div class="register-card">
            <div class="register-header">
                <!-- Ballons de football flottants -->
                <div class="floating-football floating-football-1">
                    <i class="bi bi-dribbble"></i>
                </div>
                <div class="floating-football floating-football-2">
                    <i class="bi bi-dribbble"></i>
                </div>
                
                <h1 class="register-title">Inscription</h1>
                <p class="register-subtitle">Créez votre compte</p>
            </div>
            
            <div class="register-body">
                <?php if(isset($_GET['registration']) && $_GET['registration'] === 'success'): ?>
                <div class="alert alert-success">
                    <i class="bi bi-check-circle me-2"></i>
                    Inscription réussie ! Vous pouvez maintenant vous connecter.
                </div>
                <?php endif; ?>
                
                <?php if(!empty($errors)): ?>
                <div class="alert alert-danger">
                    <i class="bi bi-exclamation-triangle me-2"></i>
                    <?php foreach($errors as $error): ?>
                    <p><?php echo htmlspecialchars($error); ?></p>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>

                <form method="POST" action="">
                    <div class="mb-3">
                        <label for="username" class="form-label">Nom d'utilisateur</label>
                        <input type="text" class="form-control" id="username" name="username" 
                               value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" name="email" 
                               value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="password" class="form-label">Mot de passe</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                        <div class="password-strength">
                            <div class="strength-meter" id="strength-meter"></div>
                        </div>
                        <small class="text-muted">Minimum 8 caractères</small>
                    </div>
                    
                    <div class="mb-3">
                        <label for="confirm_password" class="form-label">Confirmer le mot de passe</label>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                    </div>
                    
                    <button type="submit" class="btn btn-register">
                        <i class="bi bi-person-plus"></i> S'inscrire
                    </button>
                </form>
                
                <div class="register-footer">
                    Déjà inscrit ? <a href="login.php" class="register-link">Se connecter</a>
                </div>
            </div>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Password strength indicator
        document.getElementById('password').addEventListener('input', function() {
            const password = this.value;
            const strengthMeter = document.getElementById('strength-meter');
            let strength = 0;
            
            // Check for length
            if (password.length >= 8) strength += 1;
            if (password.length >= 12) strength += 1;
            
            // Check for uppercase letters
            if (/[A-Z]/.test(password)) strength += 1;
            
            // Check for numbers
            if (/[0-9]/.test(password)) strength += 1;
            
            // Check for special characters
            if (/[^A-Za-z0-9]/.test(password)) strength += 1;
            
            // Update strength meter
            const width = strength * 20;
            strengthMeter.style.width = `${width}%`;
            
            // Update color
            if (strength <= 1) {
                strengthMeter.style.backgroundColor = var('--error-red');
            } else if (strength <= 3) {
                strengthMeter.style.backgroundColor = var('--action-orange');
            } else {
                strengthMeter.style.backgroundColor = var('--success-green');
            }
        });
    </script>
</body>
</html>