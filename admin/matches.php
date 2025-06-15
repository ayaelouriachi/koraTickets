<?php
require_once '../config.php';

// Check admin access
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../index.php');
    exit;
}

// Génération du token CSRF si nécessaire
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    error_log("New CSRF token generated: " . $_SESSION['csrf_token']);
}

// Fonction pour vérifier et valider le token CSRF
function validateCSRFToken() {
    if (!isset($_POST['csrf_token'])) {
        error_log("CSRF token missing in POST data");
        return false;
    }
    
    if (!isset($_SESSION['csrf_token'])) {
        error_log("CSRF token missing in session");
        return false;
    }
    
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        error_log("CSRF token mismatch. Session: " . $_SESSION['csrf_token'] . ", POST: " . $_POST['csrf_token']);
        return false;
    }
    
    return true;
}

// Fonction pour créer un identifiant unique de soumission
function createSubmissionId($data) {
    return md5(serialize($data) . $_SESSION['user_id'] . session_id());
}

// Fonction pour vérifier si une soumission est en double
function isDuplicateSubmission($submissionId, $data) {
    $key = 'submission_' . $submissionId;
    
    if (!isset($_SESSION['recent_submissions'])) {
        $_SESSION['recent_submissions'] = [];
    }
    
    $currentTime = time();
    foreach ($_SESSION['recent_submissions'] as $subKey => $submission) {
        if ($currentTime - $submission['timestamp'] > 60) {
            unset($_SESSION['recent_submissions'][$subKey]);
        }
    }
    
    if (isset($_SESSION['recent_submissions'][$key])) {
        $previousSubmission = $_SESSION['recent_submissions'][$key];
        if (json_encode($previousSubmission['data']) === json_encode($data)) {
            return true;
        }
    }
    
    $_SESSION['recent_submissions'][$key] = [
        'timestamp' => $currentTime,
        'data' => $data
    ];
    return false;
}

// Fonction pour valider les données du match
function validateMatchData($data) {
    $errors = [];
    
    $requiredFields = ['home_team', 'away_team', 'match_date', 'stadium'];
    foreach ($requiredFields as $field) {
        if (empty(trim($data[$field] ?? ''))) {
            $errors[] = "Le champ " . ucfirst(str_replace('_', ' ', $field)) . " est requis.";
        }
    }
    
    if (strlen(trim($data['home_team'] ?? '')) > 100) $errors[] = "Le nom de l'équipe domicile ne peut pas dépasser 100 caractères.";
    if (strlen(trim($data['away_team'] ?? '')) > 100) $errors[] = "Le nom de l'équipe extérieure ne peut pas dépasser 100 caractères.";
    if (strlen(trim($data['stadium'] ?? '')) > 200) $errors[] = "Le nom du stade ne peut pas dépasser 200 caractères.";
    if (strlen(trim($data['description'] ?? '')) > 1000) $errors[] = "La description ne peut pas dépasser 1000 caractères.";
    if (!empty($data['image_url']) && strlen(trim($data['image_url'])) > 500) $errors[] = "L'URL de l'image ne peut pas dépasser 500 caractères.";
    
    if (!empty($data['home_team']) && !empty($data['away_team']) && strtolower(trim($data['home_team'])) === strtolower(trim($data['away_team']))) {
        $errors[] = "L'équipe domicile et l'équipe extérieure doivent être différentes.";
    }
    
    if (!empty($data['match_date'])) {
        $matchDate = DateTime::createFromFormat('Y-m-d\TH:i', $data['match_date']);
        if (!$matchDate) {
            $errors[] = "Format de date invalide.";
        } else {
            if ($matchDate <= new DateTime()) $errors[] = "La date du match doit être dans le futur.";
            $maxDate = (new DateTime())->add(new DateInterval('P2Y'));
            if ($matchDate > $maxDate) $errors[] = "La date du match ne peut pas être programmée plus de 2 ans à l'avance.";
        }
    }
    
    if (!empty($data['image_url']) && !filter_var($data['image_url'], FILTER_VALIDATE_URL)) {
        $errors[] = "L'URL de l'image n'est pas valide.";
    }
    
    return $errors;
}

// Handle match operations
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    try {
        $pdo = getDbConnection();
        
        if ($action === 'add') {
            $submissionData = array_intersect_key($_POST, array_flip(['home_team', 'away_team', 'match_date', 'stadium', 'description']));
            $submissionId = createSubmissionId($submissionData);

            if (isDuplicateSubmission($submissionId, $submissionData)) {
                $_SESSION['error'] = 'Soumission en double détectée. Veuillez patienter avant de réessayer.';
                header('Location: matches.php');
                exit;
            }

            $validationErrors = validateMatchData($_POST);
            if (!empty($validationErrors)) {
                $_SESSION['error'] = implode('<br>', $validationErrors);
                header('Location: matches.php');
                exit;
            }

            $pdo->beginTransaction();
            try {
                $stmt = $pdo->prepare("SELECT id, home_team, away_team, match_date, stadium FROM matches WHERE home_team = ? AND away_team = ? AND DATE(match_date) = DATE(?) AND stadium = ? LIMIT 1");
                $stmt->execute([trim($_POST['home_team']), trim($_POST['away_team']), $_POST['match_date'], trim($_POST['stadium'])]);
                
                if ($existing_match = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $pdo->rollBack();
                    $_SESSION['error'] = sprintf('Un match similaire existe déjà : <strong>%s vs %s</strong> le <strong>%s</strong> au <strong>%s</strong>.', htmlspecialchars($existing_match['home_team']), htmlspecialchars($existing_match['away_team']), date('d/m/Y', strtotime($existing_match['match_date'])), htmlspecialchars($existing_match['stadium']));
                    header('Location: matches.php');
                    exit;
                }

                $stmt = $pdo->prepare("INSERT INTO matches (home_team, away_team, match_date, stadium, description, image_url, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
                $stmt->execute([trim($_POST['home_team']), trim($_POST['away_team']), $_POST['match_date'], trim($_POST['stadium']), trim($_POST['description']), trim($_POST['image_url'] ?? '')]);
                $match_id = $pdo->lastInsertId();

                $stmt = $pdo->prepare("INSERT INTO ticket_categories (match_id, name, price, total_quantity, available_quantity, created_at) VALUES (?, 'Catégorie 1', 100, 100, 100, NOW()), (?, 'Catégorie 2', 150, 100, 100, NOW()), (?, 'Catégorie 3', 200, 100, 100, NOW())");
                $stmt->execute([$match_id, $match_id, $match_id]);

                $pdo->commit();
                $_SESSION['message'] = 'Match ajouté avec succès. Les catégories de billets par défaut ont été créées.';
                $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
                header('Location: matches.php');
                exit;

            } catch (PDOException $e) {
                $pdo->rollBack();
                error_log("Database error in match creation: " . $e->getMessage());
                $_SESSION['error'] = 'Erreur lors de l\'ajout du match.';
                header('Location: matches.php');
                exit;
            }
        }
        
        if ($action === 'update') {
            if (!validateCSRFToken()) {
                $_SESSION['error'] = 'Token de sécurité invalide.';
                header('Location: matches.php');
                exit;
            }
            
            $validationErrors = validateMatchData($_POST);
            if (!empty($validationErrors)) {
                $_SESSION['error'] = implode('<br>', $validationErrors);
                header('Location: matches.php');
                exit;
            }
            
            $pdo->beginTransaction();
            try {
                $stmt = $pdo->prepare("UPDATE matches SET home_team = :home_team, away_team = :away_team, match_date = :match_date, stadium = :stadium, description = :description, image_url = :image_url WHERE id = :id");
                $stmt->execute(['id' => $_POST['id'], 'home_team' => trim($_POST['home_team']), 'away_team' => trim($_POST['away_team']), 'match_date' => $_POST['match_date'], 'stadium' => trim($_POST['stadium']), 'description' => trim($_POST['description']), 'image_url' => trim($_POST['image_url'] ?? '')]);
                $pdo->commit();
                
                $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
                $_SESSION['message'] = 'Match modifié avec succès.';
                header('Location: matches.php');
                exit;
            } catch (PDOException $e) {
                $pdo->rollBack();
                error_log("Error updating match: " . $e->getMessage());
                $_SESSION['error'] = 'Erreur lors de la modification du match.';
                header('Location: matches.php');
                exit;
            }
        }
        
        if ($action === 'delete') {
            if (!validateCSRFToken()) {
                $_SESSION['error'] = 'Token de sécurité invalide.';
                header('Location: matches.php');
                exit;
            }
            
            $id = $_POST['id'] ?? '';
            if (!$id) {
                $_SESSION['error'] = 'ID de match invalide.';
                header('Location: matches.php');
                exit;
            }
            
            $pdo->beginTransaction();
            try {
                $stmt = $pdo->prepare("DELETE FROM ticket_categories WHERE match_id = :id");
                $stmt->execute(['id' => $id]);
                
                $stmt = $pdo->prepare("DELETE FROM matches WHERE id = :id");
                $stmt->execute(['id' => $id]);

                if ($stmt->rowCount() === 0) {
                   throw new Exception("Match non trouvé pour la suppression.");
                }
                
                $pdo->commit();
                $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
                $_SESSION['message'] = 'Match et billets associés supprimés avec succès.';
                header('Location: matches.php');
                exit;
            } catch (Exception $e) {
                $pdo->rollBack();
                error_log("Error deleting match: " . $e->getMessage());
                $_SESSION['error'] = 'Erreur lors de la suppression du match.';
                header('Location: matches.php');
                exit;
            }
        }
    } catch (PDOException $e) {
        $_SESSION['error'] = 'Erreur de base de données.';
        header('Location: matches.php');
        exit;
    }
}

try {
    $pdo = getDbConnection();
    $stmt = $pdo->query("SELECT * FROM matches ORDER BY match_date DESC");
    $matches = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $_SESSION['error'] = 'Erreur lors du chargement des matchs.';
    $matches = [];
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Matchs - Admin Panel</title>
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
        body {
            font-family: 'Inter', sans-serif;
            background: var(--bg-light);
            color: var(--text-primary);
        }
        .sidebar {
            position: fixed;
            top: 0; left: 0; bottom: 0;
            width: 250px;
            background: var(--primary-blue);
            color: white;
            padding-top: 1rem;
        }
        .sidebar-header {
            padding: 0 1.5rem 1.5rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        .sidebar-title { font-weight: 700; font-size: 1.25rem; color: white; }
        .nav-link {
            color: rgba(255, 255, 255, 0.8);
            padding: 0.75rem 1.5rem;
            margin: 0.25rem 0;
            border-radius: 6px;
            transition: var(--transition);
        }
        .nav-link:hover { color: white; background: rgba(255, 255, 255, 0.1); }
        .nav-link.active { color: white; background: var(--action-orange); font-weight: 500; }
        .nav-link i { width: 24px; margin-right: 0.75rem; text-align: center; }
        .main-content { margin-left: 250px; padding: 2rem; }
        .card {
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
        .table th {
            border-top: none;
            font-weight: 600;
            color: var(--text-secondary);
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 0.5px;
        }
        .table td { vertical-align: middle; }
        .form-label { font-weight: 500; color: var(--text-secondary); }
        .btn-primary { background-color: var(--primary-blue); border-color: var(--primary-blue); }
        .btn-primary:hover { background-color: #002244; border-color: #002244; }
        .btn-danger { background-color: var(--error-red); border-color: var(--error-red); }
        .btn-danger:hover { background-color: #D32F2F; border-color: #D32F2F; }
        .modal-content { border-radius: var(--border-radius); border:none; }
        .alert-success { background-color: var(--success-green); color: white; border: none; }
        .alert-danger { background-color: var(--error-red); color: white; border: none; }
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
            <li class="nav-item"><a class="nav-link active" href="matches.php"><i class="bi bi-calendar-event"></i> Gestion des Matchs</a></li>
            <li class="nav-item"><a class="nav-link" href="tickets.php"><i class="bi bi-ticket-perforated"></i> Gestion des Billets</a></li>
            <li class="nav-item"><a class="nav-link" href="orders.php"><i class="bi bi-receipt"></i> Commandes</a></li>
            <li class="nav-item"><a class="nav-link" href="users.php"><i class="bi bi-people"></i> Utilisateurs</a></li>
            <li class="nav-item mt-4"><a class="nav-link" href="../index.php"><i class="bi bi-box-arrow-left"></i> Retour au site</a></li>
        </ul>
    </div>

    <div class="main-content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="mb-0">Gestion des Matchs</h2>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addMatchModal">
                <i class="bi bi-plus-circle me-2"></i> Ajouter un match
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
                <h5 class="mb-0">Liste des matchs programmés</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Match</th>
                                <th>Date & Heure</th>
                                <th>Stade</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($matches)): ?>
                                <tr><td colspan="4" class="text-center text-muted">Aucun match trouvé.</td></tr>
                            <?php else: ?>
                                <?php foreach($matches as $match): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($match['home_team']); ?></strong> vs <strong><?php echo htmlspecialchars($match['away_team']); ?></strong></td>
                                    <td><?php echo date('d/m/Y H:i', strtotime($match['match_date'])); ?></td>
                                    <td><?php echo htmlspecialchars($match['stadium']); ?></td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-primary edit-match-btn" 
                                                data-id="<?php echo $match['id']; ?>"
                                                data-home="<?php echo htmlspecialchars($match['home_team']); ?>"
                                                data-away="<?php echo htmlspecialchars($match['away_team']); ?>"
                                                data-date="<?php echo date('Y-m-d\TH:i', strtotime($match['match_date'])); ?>"
                                                data-stadium="<?php echo htmlspecialchars($match['stadium']); ?>"
                                                data-description="<?php echo htmlspecialchars($match['description']); ?>"
                                                data-image="<?php echo htmlspecialchars($match['image_url']); ?>">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <form method="POST" action="matches.php" class="d-inline delete-form">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?= $match['id'] ?>">
                                            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                            <button type="button" class="btn btn-sm btn-outline-danger delete-btn" data-bs-toggle="tooltip" title="Supprimer">
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

    <div class="modal fade" id="addMatchModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form id="addMatchForm" method="POST" action="matches.php">
                    <div class="modal-header">
                        <h5 class="modal-title">Ajouter un nouveau match</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                        <div class="row">
                            <div class="col-md-6 mb-3"><label class="form-label">Équipe domicile</label><input type="text" class="form-control" name="home_team" required></div>
                            <div class="col-md-6 mb-3"><label class="form-label">Équipe extérieur</label><input type="text" class="form-control" name="away_team" required></div>
                            <div class="col-md-6 mb-3"><label class="form-label">Date et heure</label><input type="datetime-local" class="form-control" name="match_date" required></div>
                            <div class="col-md-6 mb-3"><label class="form-label">Stade</label><input type="text" class="form-control" name="stadium" required></div>
                            <div class="col-12 mb-3"><label class="form-label">URL de l'image (Optionnel)</label><input type="url" class="form-control" name="image_url"></div>
                            <div class="col-12 mb-3"><label class="form-label">Description (Optionnel)</label><textarea class="form-control" name="description" rows="3"></textarea></div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-primary">Ajouter le match</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="editMatchModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form id="editMatchForm" method="POST" action="matches.php">
                    <div class="modal-header">
                        <h5 class="modal-title">Modifier le match</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="action" value="update">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                        <input type="hidden" name="id" id="editMatchId">
                        <div class="row">
                            <div class="col-md-6 mb-3"><label class="form-label">Équipe domicile</label><input type="text" class="form-control" id="editHomeTeam" name="home_team" required></div>
                            <div class="col-md-6 mb-3"><label class="form-label">Équipe extérieur</label><input type="text" class="form-control" id="editAwayTeam" name="away_team" required></div>
                            <div class="col-md-6 mb-3"><label class="form-label">Date et heure</label><input type="datetime-local" class="form-control" id="editMatchDate" name="match_date" required></div>
                            <div class="col-md-6 mb-3"><label class="form-label">Stade</label><input type="text" class="form-control" id="editStadium" name="stadium" required></div>
                            <div class="col-12 mb-3"><label class="form-label">URL de l'image</label><input type="url" class="form-control" id="editImageUrl" name="image_url"></div>
                            <div class="col-12 mb-3"><label class="form-label">Description</label><textarea class="form-control" id="editDescription" name="description" rows="3"></textarea></div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-primary">Sauvegarder les modifications</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        // Initialize Modals
        const editModal = new bootstrap.Modal(document.getElementById('editMatchModal'));

        // Handle Edit Button Clicks
        document.querySelectorAll('.edit-match-btn').forEach(button => {
            button.addEventListener('click', function() {
                document.getElementById('editMatchId').value = this.dataset.id;
                document.getElementById('editHomeTeam').value = this.dataset.home;
                document.getElementById('editAwayTeam').value = this.dataset.away;
                document.getElementById('editMatchDate').value = this.dataset.date;
                document.getElementById('editStadium').value = this.dataset.stadium;
                document.getElementById('editDescription').value = this.dataset.description;
                document.getElementById('editImageUrl').value = this.dataset.image;
                editModal.show();
            });
        });

        // Handle Delete Button Clicks with confirmation
        document.querySelectorAll('.delete-btn').forEach(button => {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                if (confirm('Êtes-vous sûr de vouloir supprimer ce match ? Cette action est irréversible et supprimera également tous les billets associés.')) {
                    this.closest('form').submit();
                }
            });
        });

        // Client-side validation helper
        const validateForm = (form) => {
            const homeTeam = form.querySelector('[name="home_team"]').value.trim();
            const awayTeam = form.querySelector('[name="away_team"]').value.trim();
            
            if (homeTeam.toLowerCase() === awayTeam.toLowerCase()) {
                alert('Les équipes domicile et extérieure doivent être différentes.');
                return false;
            }
            return true;
        };
        
        // Add form submission
        document.getElementById('addMatchForm').addEventListener('submit', function(e) {
            if (!validateForm(this)) {
                e.preventDefault();
            }
        });
        
        // Edit form submission
        document.getElementById('editMatchForm').addEventListener('submit', function(e) {
            if (!validateForm(this)) {
                e.preventDefault();
            }
        });
    });
    </script>
</body>
</html>