<?php
require_once '../config.php';

// Check if admin is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../index.php');
    exit;
}

try {
    $pdo = getDbConnection();
    
    // Handle user deletion
    if (isset($_POST['action']) && $_POST['action'] === 'delete' && isset($_POST['id'])) {
        // CSRF Token Validation
        if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
            $_SESSION['error'] = 'Token de sécurité invalide.';
        } else {
            $user_id_to_delete = $_POST['id'];
            // Prevent admin from deleting themselves
            if ($user_id_to_delete != $_SESSION['user_id']) {
                $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
                $stmt->execute([$user_id_to_delete]);
                $_SESSION['message'] = 'Utilisateur supprimé avec succès.';
            } else {
                $_SESSION['error'] = 'Vous ne pouvez pas supprimer votre propre compte.';
            }
        }
        header('Location: users.php');
        exit;
    }
    
    // Generate a new CSRF token for the forms
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

    // Get all users except the current admin
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id != :current_user_id ORDER BY created_at DESC");
    $stmt->execute(['current_user_id' => $_SESSION['user_id']]);
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $error = "Erreur de base de données : " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Utilisateurs - Admin Panel</title>
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
        .table th { font-weight: 600; color: var(--text-secondary); text-transform: uppercase; font-size: 0.75rem; letter-spacing: 0.5px; }
        .table td { vertical-align: middle; }
        .badge { padding: 0.5em 0.75em; font-weight: 500; font-size: 0.75rem; border-radius: 50px; }
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
        <h2 class="mb-4">Gestion des Utilisateurs</h2>

        <?php if (isset($_SESSION['message'])): ?>
            <div class="alert alert-success"><?php echo $_SESSION['message']; unset($_SESSION['message']); ?></div>
        <?php endif; ?>
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
        <?php endif; ?>
        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Liste des utilisateurs inscrits</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Nom d'utilisateur</th>
                                <th>Email</th>
                                <th>Rôle</th>
                                <th>Inscrit le</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($users)): ?>
                                <tr><td colspan="5" class="text-center text-muted p-4">Aucun autre utilisateur trouvé.</td></tr>
                            <?php else: ?>
                                <?php foreach ($users as $user): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($user['username']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                                    <td>
                                        <span class="badge bg-<?php echo ($user['role'] === 'admin') ? 'primary' : 'secondary'; ?>">
                                            <?php echo htmlspecialchars(ucfirst($user['role'] ?? 'user')); ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('d/m/Y', strtotime($user['created_at'])); ?></td>
                                    <td>
                                        <a href="edit_user.php?id=<?php echo $user['id']; ?>" class="btn btn-sm btn-outline-primary" title="Modifier">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <form method="POST" action="users.php" class="d-inline delete-form">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?php echo $user['id']; ?>">
                                            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Supprimer">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('.delete-form').forEach(form => {
            form.addEventListener('submit', function(e) {
                if (!confirm('Êtes-vous sûr de vouloir supprimer cet utilisateur ? Cette action est irréversible.')) {
                    e.preventDefault();
                }
            });
        });
    });
    </script>
</body>
</html>