<?php
require_once '../config.php';

// Check admin access
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../index.php');
    exit;
}

// Generate CSRF token if it doesn't exist
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Function to validate CSRF token
function validateCSRFToken() {
    if (empty($_POST['csrf_token']) || empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        return false;
    }
    return true;
}

// Handle ticket operations
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // Always validate CSRF for any POST action
    if (!validateCSRFToken()) {
        $_SESSION['error'] = 'Token de sécurité invalide. Veuillez actualiser la page et réessayer.';
        header('Location: tickets.php');
        exit;
    }

    try {
        $pdo = getDbConnection();

        if ($action === 'add') {
            $required = ['match_id', 'name', 'price', 'total_quantity'];
            foreach ($required as $field) {
                if (empty($_POST[$field])) {
                    $_SESSION['error'] = 'Veuillez remplir tous les champs obligatoires.';
                    header('Location: tickets.php');
                    exit;
                }
            }

            if (!is_numeric($_POST['price']) || !is_numeric($_POST['total_quantity']) || $_POST['price'] < 0 || $_POST['total_quantity'] < 1) {
                $_SESSION['error'] = 'Le prix et la quantité doivent être des nombres valides et positifs.';
                header('Location: tickets.php');
                exit;
            }

            // Check if category already exists for the same match
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM ticket_categories WHERE match_id = ? AND name = ?");
            $stmt->execute([$_POST['match_id'], trim($_POST['name'])]);
            if ($stmt->fetchColumn() > 0) {
                $_SESSION['error'] = 'Cette catégorie de billet existe déjà pour le match sélectionné.';
                header('Location: tickets.php');
                exit;
            }

            $stmt = $pdo->prepare("INSERT INTO ticket_categories (match_id, name, price, total_quantity, available_quantity) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([
                $_POST['match_id'],
                trim($_POST['name']),
                $_POST['price'],
                $_POST['total_quantity'],
                $_POST['total_quantity']
            ]);
            $_SESSION['message'] = 'Catégorie de billet ajoutée avec succès.';
        }

        if ($action === 'update') {
             $required = ['id', 'match_id', 'name', 'price', 'total_quantity'];
             foreach ($required as $field) {
                if (empty($_POST[$field])) {
                    $_SESSION['error'] = 'Données de mise à jour invalides.';
                    header('Location: tickets.php');
                    exit;
                }
            }
             if (!is_numeric($_POST['price']) || !is_numeric($_POST['total_quantity']) || $_POST['price'] < 0 || $_POST['total_quantity'] < 1) {
                $_SESSION['error'] = 'Le prix et la quantité doivent être des nombres valides et positifs.';
                header('Location: tickets.php');
                exit;
            }

            // You might want to adjust available_quantity based on tickets already sold
            $stmt = $pdo->prepare("UPDATE ticket_categories SET match_id = ?, name = ?, price = ?, total_quantity = ?, available_quantity = ? WHERE id = ?");
            $stmt->execute([
                $_POST['match_id'],
                trim($_POST['name']),
                $_POST['price'],
                $_POST['total_quantity'],
                $_POST['total_quantity'], // Simplified: resets available qty. A more complex logic would be needed in a real app.
                $_POST['id']
            ]);
            $_SESSION['message'] = 'Catégorie de billet mise à jour avec succès.';
        }

        if ($action === 'delete') {
            $id = $_POST['id'] ?? '';
            if (empty($id)) {
                 $_SESSION['error'] = 'ID de catégorie invalide.';
                 header('Location: tickets.php');
                 exit;
            }
            // Add check here if tickets have been sold for this category before deleting.
            $stmt = $pdo->prepare("DELETE FROM ticket_categories WHERE id = ?");
            $stmt->execute([$id]);
            $_SESSION['message'] = 'Catégorie de billet supprimée avec succès.';
        }

        // Regenerate CSRF token after successful action to prevent reuse
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        header('Location: tickets.php');
        exit;

    } catch (PDOException $e) {
        error_log("Ticket Management Error: " . $e->getMessage());
        $_SESSION['error'] = 'Une erreur de base de données est survenue.';
        header('Location: tickets.php');
        exit;
    }
}

// Fetch data for display
try {
    $pdo = getDbConnection();
    $matches = $pdo->query("SELECT id, home_team, away_team, match_date FROM matches WHERE match_date > NOW() ORDER BY match_date ASC")->fetchAll(PDO::FETCH_ASSOC);
    $ticket_categories = $pdo->query("SELECT tc.*, m.home_team, m.away_team FROM ticket_categories tc JOIN matches m ON tc.match_id = m.id ORDER BY m.match_date DESC, tc.name ASC")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Erreur de base de données lors de la récupération des données.");
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Billets - Admin Panel</title>
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
            --gradient-primary: linear-gradient(135deg, #003366 0%, #004080 100%);
            --shadow-sm: 0 2px 4px rgba(0, 51, 102, 0.1);
            --shadow-md: 0 4px 8px rgba(0, 51, 102, 0.15);
            --border-radius: 12px;
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        body { font-family: 'Inter', sans-serif; background: var(--bg-light); color: var(--text-primary); }
        .sidebar { position: fixed; top: 0; left: 0; bottom: 0; width: 250px; background: var(--primary-blue); color: white; padding-top: 1rem; }
        .sidebar-header { padding: 0 1.5rem 1.5rem; border-bottom: 1px solid rgba(255, 255, 255, 0.1); }
        .sidebar-title { font-weight: 700; font-size: 1.25rem; color: white; }
        .nav-link { color: rgba(255, 255, 255, 0.8); padding: 0.75rem 1.5rem; margin: 0.25rem 0; border-radius: 6px; transition: var(--transition); }
        .nav-link:hover { color: white; background: rgba(255, 255, 255, 0.1); }
        .nav-link.active { color: white; background: var(--action-orange); font-weight: 500; }
        .nav-link i { width: 24px; margin-right: 0.75rem; text-align: center; }
        .main-content { margin-left: 250px; padding: 2rem; }
        .card { border: none; border-radius: var(--border-radius); box-shadow: var(--shadow-md); overflow: hidden; }
        .card-header { background: var(--bg-white); border-bottom: 1px solid var(--border-color); padding: 1.25rem 1.5rem; font-weight: 600; }
        .table th { border-top: none; font-weight: 600; color: var(--text-secondary); text-transform: uppercase; font-size: 0.75rem; letter-spacing: 0.5px; }
        .table td { vertical-align: middle; }
        .form-label { font-weight: 500; color: var(--text-secondary); }
        .btn-primary { background-color: var(--primary-blue); border-color: var(--primary-blue); }
        .btn-primary:hover { background-color: #002244; border-color: #002244; }
        .modal-content { border-radius: var(--border-radius); border:none; }
        .alert-success, .alert-danger { color: white; border: none; border-radius: var(--border-radius); box-shadow: var(--shadow-sm); }
        .alert-success { background-color: var(--success-green); }
        .alert-danger { background-color: var(--error-red); }
        @media (max-width: 768px) {
            .sidebar { position: static; width: 100%; }
            .main-content { margin-left: 0; }
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="sidebar-header"><h4 class="sidebar-title">Admin Panel</h4></div>
        <ul class="nav flex-column">
            <li class="nav-item"><a class="nav-link" href="dashboard.php"><i class="bi bi-speedometer2"></i> Dashboard</a></li>
            <li class="nav-item"><a class="nav-link" href="matches.php"><i class="bi bi-calendar-event"></i> Gestion des Matchs</a></li>
            <li class="nav-item"><a class="nav-link active" href="tickets.php"><i class="bi bi-ticket-perforated"></i> Gestion des Billets</a></li>
            <li class="nav-item"><a class="nav-link" href="orders.php"><i class="bi bi-receipt"></i> Commandes</a></li>
            <li class="nav-item"><a class="nav-link" href="users.php"><i class="bi bi-people"></i> Utilisateurs</a></li>
            <li class="nav-item mt-4"><a class="nav-link" href="../index.php"><i class="bi bi-box-arrow-left"></i> Retour au site</a></li>
        </ul>
    </div>

    <div class="main-content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="mb-0">Gestion des Billets</h2>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addTicketModal">
                <i class="bi bi-plus-circle me-2"></i> Ajouter une catégorie
            </button>
        </div>
        
        <?php if(isset($_SESSION['message'])): ?>
            <div class="alert alert-success d-flex align-items-center" role="alert">
                <i class="bi bi-check-circle-fill me-2"></i>
                <div><?php echo $_SESSION['message']; unset($_SESSION['message']); ?></div>
            </div>
        <?php endif; ?>
        
        <?php if(isset($_SESSION['error'])): ?>
            <div class="alert alert-danger d-flex align-items-center" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                <div><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
            </div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Toutes les catégories de billets</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Match</th>
                                <th>Catégorie</th>
                                <th>Prix</th>
                                <th>Quantité (Totale / Dispo)</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($ticket_categories)): ?>
                                <tr><td colspan="5" class="text-center text-muted p-4">Aucune catégorie de billet n'a été créée.</td></tr>
                            <?php else: ?>
                                <?php foreach($ticket_categories as $category): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($category['home_team']); ?> vs <?php echo htmlspecialchars($category['away_team']); ?></td>
                                    <td><?php echo htmlspecialchars($category['name']); ?></td>
                                    <td><strong><?php echo number_format($category['price'], 2, ',', ' '); ?> MAD</strong></td>
                                    <td><?php echo $category['total_quantity']; ?> / <span class="fw-bold text-success"><?php echo $category['available_quantity']; ?></span></td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-primary edit-btn" title="Modifier"
                                                data-id="<?php echo $category['id']; ?>"
                                                data-match-id="<?php echo $category['match_id']; ?>"
                                                data-name="<?php echo htmlspecialchars($category['name']); ?>"
                                                data-price="<?php echo $category['price']; ?>"
                                                data-quantity="<?php echo $category['total_quantity']; ?>">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <form method="POST" action="tickets.php" class="d-inline delete-form">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?php echo $category['id']; ?>">
                                            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Supprimer"><i class="bi bi-trash"></i></button>
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

    <div class="modal fade" id="addTicketModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form id="addTicketForm" method="POST" action="tickets.php">
                    <div class="modal-header">
                        <h5 class="modal-title">Ajouter une catégorie de billet</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                        <div class="mb-3">
                            <label class="form-label">Match</label>
                            <select class="form-select" name="match_id" required>
                                <option value="" disabled selected>Sélectionnez un match à venir</option>
                                <?php foreach($matches as $match): ?>
                                    <option value="<?php echo $match['id']; ?>"><?php echo htmlspecialchars($match['home_team']) . ' vs ' . htmlspecialchars($match['away_team']) . ' (' . date('d/m/Y', strtotime($match['match_date'])) . ')'; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Nom de la catégorie (ex: Catégorie 1, VIP)</label>
                            <input type="text" class="form-control" name="name" required>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3"><label class="form-label">Prix (MAD)</label><input type="number" class="form-control" name="price" step="1" min="0" required></div>
                            <div class="col-md-6 mb-3"><label class="form-label">Quantité totale</label><input type="number" class="form-control" name="total_quantity" min="1" required></div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-primary">Ajouter la catégorie</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="editTicketModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form id="editTicketForm" method="POST" action="tickets.php">
                    <div class="modal-header">
                        <h5 class="modal-title">Modifier la catégorie de billet</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="action" value="update">
                        <input type="hidden" name="id" id="edit_id">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                        <div class="mb-3">
                            <label class="form-label">Match</label>
                            <select class="form-select" name="match_id" id="edit_match_id" required>
                                <?php foreach($matches as $match): ?>
                                    <option value="<?php echo $match['id']; ?>"><?php echo htmlspecialchars($match['home_team']) . ' vs ' . htmlspecialchars($match['away_team']) . ' (' . date('d/m/Y', strtotime($match['match_date'])) . ')'; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Nom de la catégorie</label>
                            <input type="text" class="form-control" name="name" id="edit_name" required>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3"><label class="form-label">Prix (MAD)</label><input type="number" class="form-control" name="price" id="edit_price" step="1" min="0" required></div>
                            <div class="col-md-6 mb-3"><label class="form-label">Quantité totale</label><input type="number" class="form-control" name="total_quantity" id="edit_quantity" min="1" required></div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-primary">Sauvegarder</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const editModal = new bootstrap.Modal(document.getElementById('editTicketModal'));

        // Handle Edit Button Clicks
        document.querySelectorAll('.edit-btn').forEach(button => {
            button.addEventListener('click', function() {
                document.getElementById('edit_id').value = this.dataset.id;
                document.getElementById('edit_match_id').value = this.dataset.matchId;
                document.getElementById('edit_name').value = this.dataset.name;
                document.getElementById('edit_price').value = this.dataset.price;
                document.getElementById('edit_quantity').value = this.dataset.quantity;
                editModal.show();
            });
        });

        // Handle Delete Form Submission with confirmation
        document.querySelectorAll('.delete-form').forEach(form => {
            form.addEventListener('submit', function(e) {
                if (!confirm('Êtes-vous sûr de vouloir supprimer cette catégorie ? Cette action est irréversible.')) {
                    e.preventDefault();
                }
            });
        });
    });
    </script>
</body>
</html>