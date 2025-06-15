<?php
require_once '../config.php';

// Check if admin is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../index.php');
    exit;
}

$user_id = $_GET['id'] ?? null;
if (!$user_id) {
    header('Location: users.php');
    exit;
}

// Generate CSRF token if it doesn't exist
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$error = '';
$success = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $error = 'Erreur de sécurité. Veuillez réessayer.';
    } else {
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $role = $_POST['role'] ?? 'user';

        // Basic validation
        if (empty($username) || empty($email) || empty($role)) {
            $error = 'Tous les champs sont obligatoires.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Format d\'email invalide.';
        } else {
            try {
                $pdo = getDbConnection();
                $stmt = $pdo->prepare("UPDATE users SET username = ?, email = ?, role = ? WHERE id = ?");
                $stmt->execute([$username, $email, $role, $user_id]);
                $success = 'Utilisateur mis à jour avec succès.';
            } catch (PDOException $e) {
                // Check for duplicate entry
                if ($e->getCode() == 23000) {
                     $error = 'Ce nom d\'utilisateur ou cet email est déjà utilisé par un autre compte.';
                } else {
                    $error = "Erreur de base de données : " . $e->getMessage();
                }
            }
        }
    }
    // Regenerate token after submission
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Fetch user data for the form
try {
    $pdo = getDbConnection();
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        $_SESSION['error'] = 'Utilisateur non trouvé.';
        header('Location: users.php');
        exit;
    }
} catch (PDOException $e) {
    $error = "Erreur lors de la récupération des données de l'utilisateur.";
}

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifier Utilisateur - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-blue: #003366;
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
        .card { border: none; border-radius: var(--border-radius); box-shadow: var(--shadow-md); }
        .card-header { background: var(--bg-white); border-bottom: 1px solid var(--border-color); padding: 1.25rem 1.5rem; font-weight: 600; }
        .form-label { font-weight: 500; color: var(--text-secondary); }
        .btn-primary { background-color: var(--primary-blue); border-color: var(--primary-blue); }
        .btn-primary:hover { background-color: #002244; border-color: #002244; }
        .alert-success, .alert-danger { color: white; border: none; border-radius: var(--border-radius); }
        .alert-success { background-color: var(--success-green); }
        .alert-danger { background-color: var(--error-red); }
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
            <li class="nav-item"><a class="nav-link" href="orders.php"><i class="bi bi-receipt"></i> Commandes</a></li>
            <li class="nav-item"><a class="nav-link active" href="users.php"><i class="bi bi-people"></i> Utilisateurs</a></li>
            <li class="nav-item mt-4"><a class="nav-link" href="../index.php"><i class="bi bi-box-arrow-left"></i> Retour au site</a></li>
        </ul>
    </div>

    <div class="main-content">
        <h2 class="mb-4">Modifier un Utilisateur</h2>

        <?php if ($error): ?>
            <div class="alert alert-danger"><i class="bi bi-exclamation-triangle-fill me-2"></i><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success"><i class="bi bi-check-circle-fill me-2"></i><?php echo $success; ?></div>
        <?php endif; ?>

        <?php if ($user): ?>
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Détails du compte de "<?php echo htmlspecialchars($user['username']); ?>"</h5>
            </div>
            <div class="card-body p-4">
                <form method="POST" action="edit_user.php?id=<?php echo $user_id; ?>">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="username" class="form-label">Nom d'utilisateur</label>
                            <input type="text" class="form-control" id="username" name="username" value="<?php echo htmlspecialchars($user['username']); ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label for="email" class="form-label">Adresse Email</label>
                            <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label for="role" class="form-label">Rôle</label>
                            <select class="form-select" id="role" name="role" required>
                                <option value="user" <?php echo ($user['role'] === 'user') ? 'selected' : ''; ?>>Utilisateur</option>
                                <option value="admin" <?php echo ($user['role'] === 'admin') ? 'selected' : ''; ?>>Administrateur</option>
                            </select>
                        </div>
                        <div class="col-12 mt-4">
                            <button type="submit" class="btn btn-primary px-4">Enregistrer les modifications</button>
                            <a href="users.php" class="btn btn-secondary px-4">Retour</a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        <?php endif; ?>
    </div>
</body>
</html>