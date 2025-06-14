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
    
    // Initialiser le tableau des soumissions récentes s'il n'existe pas
    if (!isset($_SESSION['recent_submissions'])) {
        $_SESSION['recent_submissions'] = [];
    }
    
    // Nettoyer les anciennes soumissions (plus de 60 secondes)
    $currentTime = time();
    foreach ($_SESSION['recent_submissions'] as $subKey => $submission) {
        if ($currentTime - $submission['timestamp'] > 60) {
            unset($_SESSION['recent_submissions'][$subKey]);
        }
    }
    
    // Vérifier si cette soumission existe déjà avec les mêmes données
    if (isset($_SESSION['recent_submissions'][$key])) {
        $previousSubmission = $_SESSION['recent_submissions'][$key];
        // Comparer les données exactes pour détecter les vrais doubles
        if (json_encode($previousSubmission['data']) === json_encode($data)) {
            return true;
        }
    }
    
    // Enregistrer cette soumission avec les données
    $_SESSION['recent_submissions'][$key] = [
        'timestamp' => $currentTime,
        'data' => $data
    ];
    return false;
}

// Fonction pour valider les données du match
function validateMatchData($data) {
    $errors = [];
    
    // Validation des champs requis
    $requiredFields = ['home_team', 'away_team', 'match_date', 'stadium'];
    foreach ($requiredFields as $field) {
        if (empty(trim($data[$field] ?? ''))) {
            $errors[] = "Le champ " . ucfirst(str_replace('_', ' ', $field)) . " est requis.";
        }
    }
    
    // Validation de la longueur des champs
    if (strlen(trim($data['home_team'] ?? '')) > 100) {
        $errors[] = "Le nom de l'équipe domicile ne peut pas dépasser 100 caractères.";
    }
    
    if (strlen(trim($data['away_team'] ?? '')) > 100) {
        $errors[] = "Le nom de l'équipe extérieure ne peut pas dépasser 100 caractères.";
    }
    
    if (strlen(trim($data['stadium'] ?? '')) > 200) {
        $errors[] = "Le nom du stade ne peut pas dépasser 200 caractères.";
    }
    
    if (strlen(trim($data['description'] ?? '')) > 1000) {
        $errors[] = "La description ne peut pas dépasser 1000 caractères.";
    }
    
    if (!empty($data['image_url']) && strlen(trim($data['image_url'])) > 500) {
        $errors[] = "L'URL de l'image ne peut pas dépasser 500 caractères.";
    }
    
    // Validation que les équipes sont différentes
    if (!empty($data['home_team']) && !empty($data['away_team'])) {
        if (strtolower(trim($data['home_team'])) === strtolower(trim($data['away_team']))) {
            $errors[] = "L'équipe domicile et l'équipe extérieure doivent être différentes.";
        }
    }
    
    // Validation de la date
    if (!empty($data['match_date'])) {
        $matchDate = DateTime::createFromFormat('Y-m-d\TH:i', $data['match_date']);
        if (!$matchDate) {
            $errors[] = "Format de date invalide.";
        } else {
            // Vérifier que la date n'est pas dans le passé
            if ($matchDate <= new DateTime()) {
                $errors[] = "La date du match doit être dans le futur.";
            }
            
            // Vérifier que la date n'est pas trop loin dans le futur (2 ans maximum)
            $maxDate = new DateTime();
            $maxDate->add(new DateInterval('P2Y'));
            if ($matchDate > $maxDate) {
                $errors[] = "La date du match ne peut pas être programmée plus de 2 ans à l'avance.";
            }
        }
    }
    
    // Validation de l'URL de l'image si fournie
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
            // Créer un ID unique pour cette soumission
            $submissionData = [
                'action' => $action,
                'home_team' => trim($_POST['home_team'] ?? ''),
                'away_team' => trim($_POST['away_team'] ?? ''),
                'match_date' => $_POST['match_date'] ?? '',
                'stadium' => trim($_POST['stadium'] ?? ''),
                'description' => trim($_POST['description'] ?? '')
            ];
            
            $submissionId = createSubmissionId($submissionData);
            
            // Vérifier les doubles soumissions
            if (isDuplicateSubmission($submissionId, $submissionData)) {
                $_SESSION['error'] = 'Soumission en double détectée. Veuillez ne pas soumettre le formulaire plusieurs fois.';
                header('Location: matches.php');
                exit;
            }
            
            // Validation des données
            $validationErrors = validateMatchData($_POST);
            if (!empty($validationErrors)) {
                $_SESSION['error'] = implode('<br>', $validationErrors);
                header('Location: matches.php');
                exit;
            }
            
            // Démarrer une transaction
            $pdo->beginTransaction();
            try {
                // Vérifier si le match existe déjà avec une requête plus stricte
                $stmt = $pdo->prepare("
                    SELECT id, home_team, away_team, match_date, stadium, description 
                    FROM matches 
                    WHERE home_team = ? 
                    AND away_team = ? 
                    AND DATE(match_date) = DATE(?) 
                    AND TIME(match_date) = TIME(?)
                    AND stadium = ?
                    LIMIT 1
                ");
                $stmt->execute([
                    trim($_POST['home_team']),
                    trim($_POST['away_team']),
                    $_POST['match_date'],
                    $_POST['match_date'],
                    trim($_POST['stadium'])
                ]);
                
                $existing_match = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($existing_match) {
                    // Annuler la transaction
                    $pdo->rollBack();
                    
                    $_SESSION['error'] = sprintf(
                        'Ce match existe déjà dans la base de données.<br>Match existant : <strong>%s vs %s</strong> le <strong>%s</strong> au <strong>%s</strong><br><small>Veuillez vérifier les informations avant de soumettre.</small>',
                        htmlspecialchars($existing_match['home_team']),
                        htmlspecialchars($existing_match['away_team']),
                        date('d/m/Y à H:i', strtotime($existing_match['match_date'])),
                        htmlspecialchars($existing_match['stadium'])
                    );
                    
                    header('Location: matches.php');
                    exit;
                }
                
                // Si le match n'existe pas, l'ajouter
                $stmt = $pdo->prepare(
                    "INSERT INTO matches (home_team, away_team, match_date, stadium, description, image_url, created_at)
                    VALUES (?, ?, ?, ?, ?, ?, NOW())"
                );
                $stmt->execute([
                    trim($_POST['home_team']),
                    trim($_POST['away_team']),
                    $_POST['match_date'],
                    trim($_POST['stadium']),
                    trim($_POST['description']),
                    trim($_POST['image_url'] ?? '')
                ]);
                
                // Récupérer l'ID du match nouvellement créé
                $match_id = $pdo->lastInsertId();
                
                // Ajouter les catégories de billets par défaut
                $stmt = $pdo->prepare("
                    INSERT INTO ticket_categories (match_id, name, price, total_quantity, available_quantity, created_at)
                    VALUES 
                    (?, 'Catégorie 1', 100, 100, 100, NOW()),
                    (?, 'Catégorie 2', 150, 100, 100, NOW()),
                    (?, 'Catégorie 3', 200, 100, 100, NOW())
                ");
                $stmt->execute([$match_id, $match_id, $match_id]);
                
                // Valider la transaction
                $pdo->commit();
                
                $_SESSION['message'] = sprintf(
                    'Match ajouté avec succès : <strong>%s vs %s</strong> le <strong>%s</strong> au <strong>%s</strong>.<br>Les catégories de billets ont été créées automatiquement.',
                    htmlspecialchars($_POST['home_team']),
                    htmlspecialchars($_POST['away_team']),
                    date('d/m/Y à H:i', strtotime($_POST['match_date'])),
                    htmlspecialchars($_POST['stadium'])
                );
                
                // Régénérer le token CSRF après succès
                $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
                
                // Redirection après succès
                header('Location: matches.php');
                exit;
                
            } catch (PDOException $e) {
                // Annuler la transaction en cas d'erreur
                $pdo->rollBack();
                error_log("Database error in match creation: " . $e->getMessage());
                $_SESSION['error'] = 'Erreur lors de l\'ajout du match. Veuillez réessayer.';
                header('Location: matches.php');
                exit;
            }
        }
        
        if ($action === 'update') {
            // Validation CSRF pour les modifications
            if (!validateCSRFToken()) {
                $_SESSION['error'] = 'Token de sécurité invalide. Veuillez actualiser la page et réessayer.';
                header('Location: matches.php');
                exit;
            }
            
            // Validation des données
            $validationErrors = validateMatchData($_POST);
            if (!empty($validationErrors)) {
                $_SESSION['error'] = implode('<br>', $validationErrors);
                header('Location: matches.php');
                exit;
            }
            
            // Vérifier si le match existe
            $stmt = $pdo->prepare("SELECT id FROM matches WHERE id = :id");
            $stmt->execute(['id' => $_POST['id']]);
            if (!$stmt->fetch(PDO::FETCH_ASSOC)) {
                $_SESSION['error'] = 'Match non trouvé.';
                header('Location: matches.php');
                exit;
            }
            
            // Commencer la transaction
            $pdo->beginTransaction();
            
            try {
                // Mettre à jour le match
                $stmt = $pdo->prepare("
                    UPDATE matches 
                    SET home_team = :home_team, 
                        away_team = :away_team, 
                        match_date = :match_date, 
                        stadium = :stadium, 
                        description = :description, 
                        image_url = :image_url
                    WHERE id = :id
                ");
                $stmt->execute([
                    'id' => $_POST['id'],
                    'home_team' => trim($_POST['home_team']),
                    'away_team' => trim($_POST['away_team']),
                    'match_date' => $_POST['match_date'],
                    'stadium' => trim($_POST['stadium']),
                    'description' => trim($_POST['description']),
                    'image_url' => $_POST['image_url'] ?? ''
                ]);
                
                // Valider la transaction
                $pdo->commit();
                
                // Régénérer le token CSRF après une action réussie
                $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
                
                $_SESSION['message'] = 'Match modifié avec succès.';
                header('Location: matches.php');
                exit;
            } catch (PDOException $e) {
                // Annuler la transaction en cas d'erreur
                $pdo->rollBack();
                error_log("Error updating match: " . $e->getMessage());
                $_SESSION['error'] = 'Erreur lors de la modification du match. Veuillez réessayer.';
                header('Location: matches.php');
                exit;
            }
        }
        
        if ($action === 'delete') {
            // Log détaillé pour le débogage
            error_log("Delete action triggered with POST data: " . print_r($_POST, true));
            error_log("Session CSRF token: " . ($_SESSION['csrf_token'] ?? 'undefined'));
            error_log("Posted CSRF token: " . ($_POST['csrf_token'] ?? 'undefined'));
            
            // Validation CSRF pour la suppression
            if (!validateCSRFToken()) {
                error_log("CSRF token validation failed for delete action. Session token: " . $_SESSION['csrf_token'] . ", Posted token: " . ($_POST['csrf_token'] ?? ''));
                $_SESSION['error'] = 'Token de sécurité invalide. Veuillez actualiser la page et réessayer.';
                header('Location: matches.php');
                exit;
            }
            
            $id = $_POST['id'] ?? '';
            if (!$id) {
                error_log("Invalid match ID provided for deletion");
                $_SESSION['error'] = 'ID invalide.';
                header('Location: matches.php');
                exit;
            }
            
            // Log l'ID du match à supprimer
            error_log("Attempting to delete match with ID: " . $id);
            
            // Vérifier si le match existe avant la suppression
            $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM matches WHERE id = :id");
            $stmt->execute(['id' => $id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            error_log("Match found before deletion: " . ($result['count'] ? 'yes' : 'no'));
            
            if (!$result['count']) {
                error_log("Match not found before deletion attempt");
                $_SESSION['error'] = 'Match non trouvé.';
                header('Location: matches.php');
                exit;
            }
            
            // Commencer la transaction
            $pdo->beginTransaction();
            
            try {
                // Supprimer les catégories de tickets liées
                $stmt = $pdo->prepare("DELETE FROM ticket_categories WHERE match_id = :id");
                $stmt->execute(['id' => $id]);
                $deletedCategories = $stmt->rowCount();
                error_log("Deleted ticket categories: " . $deletedCategories);
                
                // Supprimer le match
                $stmt = $pdo->prepare("DELETE FROM matches WHERE id = :id");
                $stmt->execute(['id' => $id]);
                $deletedMatch = $stmt->rowCount();
                error_log("Deleted match: " . $deletedMatch);
                
                // Vérifier si la suppression a réussi
                if ($deletedMatch === 0) {
                    error_log("No match found with ID: " . $id);
                    throw new Exception("Match not found");
                }
                
                // Valider la transaction
                $pdo->commit();
                
                // Régénérer le token CSRF après une action réussie
                $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
                
                error_log("Match successfully deleted with ID: " . $id);
                error_log("Deleted ticket categories: " . $deletedCategories);
                error_log("Deleted match: " . $deletedMatch);
                
                $_SESSION['message'] = 'Match et ses catégories de billets supprimés avec succès.';
                header('Location: matches.php');
                exit;
            } catch (PDOException $e) {
                // Annuler la transaction en cas d'erreur
                $pdo->rollBack();
                error_log("Error deleting match: " . $e->getMessage());
                error_log("Error code: " . $e->getCode());
                error_log("Error info: " . print_r($e->errorInfo, true));
                
                $_SESSION['error'] = 'Erreur lors de la suppression du match. Veuillez réessayer.';
                header('Location: matches.php');
                exit;
            } catch (Exception $e) {
                // Annuler la transaction en cas d'erreur
                $pdo->rollBack();
                error_log("Error during match deletion: " . $e->getMessage());
                
                $_SESSION['error'] = 'Erreur lors de la suppression du match. Veuillez réessayer.';
                header('Location: matches.php');
                exit;
            }
        }
        
    } catch (PDOException $e) {
        error_log("General database error: " . $e->getMessage());
        $_SESSION['error'] = 'Erreur de base de données. Veuillez réessayer.';
        header('Location: matches.php');
        exit;
    }
}

try {
    $pdo = getDbConnection();
    $stmt = $pdo->query("SELECT * FROM matches ORDER BY match_date DESC");
    $matches = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching matches: " . $e->getMessage());
    $_SESSION['error'] = 'Erreur lors du chargement des matchs.';
    $matches = [];
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Matchs - Football Tickets</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="container-fluid">
        <div class="row">
            <?php include 'includes/sidebar.php'; ?>

            <div class="col-md-9 col-lg-10 p-4">
                <h2>Gestion des Matchs</h2>
                
                <?php if(isset($_SESSION['message'])): ?>
                <div class="alert alert-success"><?php echo $_SESSION['message']; ?></div>
                <?php unset($_SESSION['message']); ?>
                <?php endif; ?>
                
                <?php if(isset($_SESSION['error'])): ?>
                <div class="alert alert-danger"><?php echo $_SESSION['error']; ?></div>
                <?php unset($_SESSION['error']); ?>
                <?php endif; ?>

                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Ajouter un nouveau match</h5>
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addMatchModal">
                            <i class="bi bi-plus-circle"></i> Nouveau match
                        </button>
                    </div>
                    <div class="card-body">
                        <form id="addMatchForm" method="POST" action="" novalidate>
                            <input type="hidden" name="action" value="add">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Équipe domicile</label>
                                        <input type="text" class="form-control" name="home_team" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Équipe extérieur</label>
                                        <input type="text" class="form-control" name="away_team" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Date et heure</label>
                                        <input type="datetime-local" class="form-control" name="match_date" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Stade</label>
                                        <input type="text" class="form-control" name="stadium" required>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="mb-3">
                                        <label class="form-label">Description</label>
                                        <textarea class="form-control" name="description" rows="3"></textarea>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="mb-3">
                                        <label class="form-label">URL de l'image</label>
                                        <input type="url" class="form-control" name="image_url">
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="card-footer text-end">
                        <button type="submit" form="addMatchForm" class="btn btn-primary">Ajouter</button>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Liste des matchs</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Match</th>
                                        <th>Date</th>
                                        <th>Stade</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($matches as $match): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($match['home_team']); ?> vs <?php echo htmlspecialchars($match['away_team']); ?></td>
                                        <td><?php echo date('d/m/Y H:i', strtotime($match['match_date'])); ?></td>
                                        <td><?php echo htmlspecialchars($match['stadium']); ?></td>
                                        <td>
                                            <button class="btn btn-sm btn-primary edit-match-btn" 
                                                    data-id="<?php echo $match['id']; ?>"
                                                    data-home="<?php echo htmlspecialchars($match['home_team']); ?>"
                                                    data-away="<?php echo htmlspecialchars($match['away_team']); ?>"
                                                    data-date="<?php echo date('Y-m-d\TH:i', strtotime($match['match_date'])); ?>"
                                                    data-stadium="<?php echo htmlspecialchars($match['stadium']); ?>"
                                                    data-description="<?php echo htmlspecialchars($match['description']); ?>"
                                                    data-image="<?php echo htmlspecialchars($match['image_url']); ?>">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            <!-- Formulaire de suppression -->
                                            <form method="POST" action="matches.php" class="d-inline delete-form">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="id" value="<?= $match['id'] ?>">
                                                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                                <button type="button" class="btn btn-sm btn-danger delete-btn"
                                                        data-home="<?= htmlspecialchars($match['home_team']) ?>"
                                                        data-away="<?= htmlspecialchars($match['away_team']) ?>"
                                                        data-date="<?= date('d/m/Y H:i', strtotime($match['match_date'])) ?>"
                                                        data-stadium="<?= htmlspecialchars($match['stadium']) ?>">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Match Modal -->
    <div class="modal fade" id="editMatchModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Modifier le match</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="editMatchForm" method="POST" action="matches.php" enctype="multipart/form-data">
                        <input type="hidden" name="action" value="update">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                        <input type="hidden" name="id" id="editMatchId">
                        <div class="mb-3">
                            <label class="form-label">Équipe domicile</label>
                            <input type="text" class="form-control" name="home_team" id="editHomeTeam" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Équipe extérieur</label>
                            <input type="text" class="form-control" name="away_team" id="editAwayTeam" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Date et heure</label>
                            <input type="datetime-local" class="form-control" name="match_date" id="editMatchDate" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Stade</label>
                            <input type="text" class="form-control" name="stadium" id="editStadium" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea class="form-control" name="description" id="editDescription" rows="3"></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">URL de l'image</label>
                            <input type="url" class="form-control" name="image_url" id="editImageUrl">
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                    <button type="submit" form="editMatchForm" class="btn btn-primary">Modifier</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Initialize Bootstrap modal
        const editModal = new bootstrap.Modal(document.getElementById('editMatchModal'));

        // Handle edit button click
        document.querySelectorAll('.edit-match-btn').forEach(button => {
            button.addEventListener('click', function() {
                const id = this.dataset.id;
                const home = this.dataset.home;
                const away = this.dataset.away;
                const date = this.dataset.date;
                const stadium = this.dataset.stadium;
                const description = this.dataset.description;
                const image = this.dataset.image;

                document.getElementById('editMatchId').value = id;
                document.getElementById('editHomeTeam').value = home;
                document.getElementById('editAwayTeam').value = away;
                document.getElementById('editMatchDate').value = date;
                document.getElementById('editStadium').value = stadium;
                document.getElementById('editDescription').value = description;
                document.getElementById('editImageUrl').value = image;

                editModal.show();
            });
        });

        // Handle form submission
        document.getElementById('addMatchForm').addEventListener('submit', function(e) {
            const form = e.target;
            const homeTeam = form.querySelector('[name="home_team"]').value.trim();
            const awayTeam = form.querySelector('[name="away_team"]').value.trim();
            const matchDate = form.querySelector('[name="match_date"]').value;
            const stadium = form.querySelector('[name="stadium"]').value.trim();
            const description = form.querySelector('[name="description"]').value.trim();
            const imageUrl = form.querySelector('[name="image_url"]').value.trim();

            // Basic client-side validation
            if (!homeTeam || !awayTeam || !matchDate || !stadium) {
                alert('Veuillez remplir tous les champs obligatoires.');
                e.preventDefault();
                return;
            }

            if (homeTeam.toLowerCase() === awayTeam.toLowerCase()) {
                alert('Les équipes doivent être différentes.');
                e.preventDefault();
                return;
            }

            if (homeTeam.length > 100 || awayTeam.length > 100) {
                alert('Le nom des équipes ne doit pas dépasser 100 caractères.');
                e.preventDefault();
                return;
            }

            if (stadium.length > 200) {
                alert('Le nom du stade ne doit pas dépasser 200 caractères.');
                e.preventDefault();
                return;
            }

            if (description && description.length > 1000) {
                alert('La description ne doit pas dépasser 1000 caractères.');
                e.preventDefault();
                return;
            }

            if (imageUrl && imageUrl.length > 500) {
                alert('L\'URL de l\'image ne doit pas dépasser 500 caractères.');
                e.preventDefault();
                return;
            }

            // Disable submit button during submission
            const submitBtn = form.querySelector('[type="submit"]');
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Envoi...';
        });

        // Gestion des suppressions avec formulaire
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.delete-form').forEach(form => {
                const button = form.querySelector('.delete-btn');
                if (!button) {
                    console.error('Bouton de suppression non trouvé');
                    return;
                }

                button.addEventListener('click', function(e) {
                    e.preventDefault();
                    
                    try {
                        console.log('Bouton de suppression cliqué');
                        
                        const home = this.dataset.home || 'Équipe inconnue';
                        const away = this.dataset.away || 'Équipe inconnue';
                        const date = this.dataset.date || 'Date inconnue';
                        const stadium = this.dataset.stadium || 'Stade inconnue';
                        
                        // Créer la boîte de dialogue Bootstrap
                        const dialog = bootstrap.Modal.getOrCreateInstance(document.createElement('div'));
                        const modalContent = `
                            <div class="modal fade" tabindex="-1" role="dialog">
                                <div class="modal-dialog" role="document">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title">Confirmation de suppression</h5>
                                        </div>
                                        <div class="modal-body">
                                            <p>Supprimer ce match ?</p>
                                            <p><strong>${home} vs ${away}</strong></p>
                                            <p><small>${date}</small></p>
                                            <p><small>${stadium}</small></p>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                                            <button type="button" class="btn btn-danger" onclick="confirmDelete(${this.closest('form').querySelector('input[name=\'id\']').value})">Supprimer</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        `;
                        
                        // Ajouter la boîte de dialogue au DOM
                        const modalDiv = document.createElement('div');
                        modalDiv.innerHTML = modalContent;
                        document.body.appendChild(modalDiv);
                        
                        // Initialiser et afficher la modal
                        const modal = new bootstrap.Modal(modalDiv.querySelector('.modal'));
                        modal.show();
                        
                        // Fonction pour gérer la suppression confirmée
                        window.confirmDelete = function(id) {
                            console.log('Confirmation acceptée, démarage de la suppression');
                            
                            // Trouver le formulaire correspondant
                            const form = document.querySelector(`form.delete-form input[name='id'][value='${id}']`).closest('form');
                            const button = form.querySelector('.delete-btn');
                            
                            if (button) {
                                // Désactiver le bouton et afficher le spinner
                                button.disabled = true;
                                button.innerHTML = '<div class="spinner-container"><span class="spinner-border spinner-border-sm"></span> Suppression...</div>';
                                
                                // Soumettre le formulaire
                                console.log('Soumission du formulaire');
                                form.submit();
                            }
                        };
                    } catch (error) {
                        console.error('Erreur lors de la suppression:', error);
                        alert('Une erreur est survenue lors de la suppression. Veuillez réessayer.');
                    }
                });
            });
        });

        // Style pour le spinner
        const style = document.createElement('style');
        style.textContent = `
            .spinner-container {
                display: flex;
                align-items: center;
                gap: 8px;
            }
            .spinner-border-sm {
                width: 1rem;
                height: 1rem;
            }
        `;
        document.head.appendChild(style);

        // Reset form and enable button after submission
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('addMatchForm');
            if (form) {
                form.reset();
                const submitBtn = form.querySelector('[type="submit"]');
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = 'Ajouter';
                }
            }
        });
    </script>
</body>
</html>