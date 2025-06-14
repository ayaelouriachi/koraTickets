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

// Handle ticket operations
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    try {
        $pdo = getDbConnection();
        
        if ($action === 'add') {
            // Log des données reçues
            error_log("Received POST data for ticket addition: " . print_r($_POST, true));
            
            // Vérifier que tous les champs requis sont présents
            $requiredFields = ['match_id', 'name', 'price', 'total_quantity'];
            $missingFields = array_diff($requiredFields, array_keys($_POST));
            
            if (!empty($missingFields)) {
                error_log("Missing required fields: " . implode(', ', $missingFields));
                $_SESSION['error'] = 'Tous les champs requis ne sont pas remplis.';
                header('Location: tickets.php');
                exit;
            }
            
            // Vérifier que les valeurs sont valides
            if (!is_numeric($_POST['price']) || !is_numeric($_POST['total_quantity'])) {
                error_log("Invalid numeric values: price=" . $_POST['price'] . ", total_quantity=" . $_POST['total_quantity']);
                $_SESSION['error'] = 'Les valeurs du prix et de la quantité doivent être numériques.';
                header('Location: tickets.php');
                exit;
            }
            
            // Vérifier si le match existe
            $stmt = $pdo->prepare("SELECT id FROM matches WHERE id = ?");
            $stmt->execute([$_POST['match_id']]);
            if (!$stmt->fetch()) {
                error_log("Match not found with ID: " . $_POST['match_id']);
                $_SESSION['error'] = 'Match non trouvé.';
                header('Location: tickets.php');
                exit;
            }
            
            // Vérifier si la catégorie existe déjà
            $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM ticket_categories 
                WHERE match_id = ? AND name = ?");
            $stmt->execute([$_POST['match_id'], $_POST['name']]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result['count'] > 0) {
                error_log("Ticket category already exists for match_id=" . $_POST['match_id'] . ", name=" . $_POST['name']);
                $_SESSION['error'] = 'Cette catégorie existe déjà pour ce match.';
                header('Location: tickets.php');
                exit;
            }
            
            // Préparer et exécuter l'insertion
            $stmt = $pdo->prepare("INSERT INTO ticket_categories (match_id, name, price, total_quantity, available_quantity)
                VALUES (?, ?, ?, ?, ?)");
            
            try {
                $stmt->execute([
                    $_POST['match_id'],
                    $_POST['name'],
                    $_POST['price'],
                    $_POST['total_quantity'],
                    $_POST['total_quantity']
                ]);
                
                // Récupérer l'ID de la catégorie créée
                $lastInsertId = $pdo->lastInsertId();
                error_log("Successfully added ticket category with ID: " . $lastInsertId);
                
                // Régénérer le token CSRF après une action réussie
                $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
                
                $_SESSION['message'] = 'Catégorie de billet ajoutée avec succès';
            } catch (PDOException $e) {
                error_log("Error adding ticket category: " . $e->getMessage());
                error_log("Error code: " . $e->getCode());
                error_log("Error info: " . print_r($stmt->errorInfo(), true));
                
                if ($e->getCode() === 23000) { // Code d'erreur MySQL pour contrainte unique
                    $_SESSION['error'] = 'Cette catégorie existe déjà pour ce match.';
                } else {
                    $_SESSION['error'] = 'Erreur lors de l\'ajout de la catégorie. Veuillez réessayer.';
                }
                header('Location: tickets.php');
                exit;
            }
        }
        
        if ($action === 'update') {
            $stmt = $pdo->prepare("
                UPDATE ticket_categories 
                SET name = ?, price = ?, total_quantity = ?, available_quantity = ?
                WHERE id = ?
            ");
            $stmt->execute([
                $_POST['name'],
                $_POST['price'],
                $_POST['total_quantity'],
                $_POST['total_quantity'],
                $_POST['id']
            ]);
            $_SESSION['message'] = 'Catégorie de billet mise à jour avec succès';
        }
        
        if ($action === 'delete') {
            // Log détaillé pour le débogage
            error_log("Delete ticket category action triggered with POST data: " . print_r($_POST, true));
            error_log("Session CSRF token: " . ($_SESSION['csrf_token'] ?? 'undefined'));
            error_log("Posted CSRF token: " . ($_POST['csrf_token'] ?? 'undefined'));
            
            // Validation CSRF
            if (!validateCSRFToken()) {
                error_log("CSRF token validation failed for delete action. Session token: " . $_SESSION['csrf_token'] . ", Posted token: " . ($_POST['csrf_token'] ?? ''));
                $_SESSION['error'] = 'Token de sécurité invalide. Veuillez actualiser la page et réessayer.';
                header('Location: tickets.php');
                exit;
            }
            
            $id = $_POST['id'] ?? '';
            if (!$id) {
                error_log("Invalid ticket category ID provided for deletion");
                $_SESSION['error'] = 'ID invalide.';
                header('Location: tickets.php');
                exit;
            }
            
            // Log l'ID de la catégorie à supprimer
            error_log("Attempting to delete ticket category with ID: " . $id);
            
            // Vérifier si la catégorie existe avant la suppression
            $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM ticket_categories WHERE id = ?");
            $stmt->execute([$id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            error_log("Ticket category found before deletion: " . ($result['count'] ? 'yes' : 'no'));
            
            if (!$result['count']) {
                error_log("Ticket category not found before deletion attempt");
                $_SESSION['error'] = 'Catégorie non trouvée.';
                header('Location: tickets.php');
                exit;
            }
            
            // Supprimer la catégorie
            $stmt = $pdo->prepare("DELETE FROM ticket_categories WHERE id = ?");
            $stmt->execute([$id]);
            $deleted = $stmt->rowCount();
            error_log("Deleted ticket category: " . $deleted);
            
            if ($deleted === 0) {
                error_log("No ticket category found with ID: " . $id);
                throw new Exception("Catégorie non trouvée");
            }
            
            // Régénérer le token CSRF après une action réussie
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            
            $_SESSION['message'] = 'Catégorie de billet supprimée avec succès.';
            header('Location: tickets.php');
            exit;
        }
        
        header('Location: tickets.php');
        exit;
    } catch (PDOException $e) {
        $_SESSION['error'] = 'Erreur lors de l\'opération: ' . $e->getMessage();
        header('Location: tickets.php');
        exit;
    }
}

try {
    $pdo = getDbConnection();
    
    // Get matches for dropdown
    $stmt = $pdo->query("SELECT id, home_team, away_team FROM matches ORDER BY match_date DESC");
    $matches = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get all ticket categories
    $stmt = $pdo->query("
        SELECT tc.*, m.home_team, m.away_team 
        FROM ticket_categories tc 
        JOIN matches m ON tc.match_id = m.id
        ORDER BY m.match_date DESC
    ");
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
    <title>Gestion des Billets - Football Tickets</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="container-fluid">
        <div class="row">
            <?php include 'includes/sidebar.php'; ?>

            <div class="col-md-9 col-lg-10 p-4">
                <h2>Gestion des Billets</h2>
                
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
                        <h5 class="mb-0">Ajouter une nouvelle catégorie de billet</h5>
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addTicketModal">
                            <i class="bi bi-plus-circle"></i> Nouvelle catégorie
                        </button>
                    </div>
                    <div class="card-body">
                        <form id="addTicketForm" method="POST" action="">
                            <input type="hidden" name="action" value="add">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Match</label>
                                        <select class="form-select" name="match_id" required>
                                            <option value="">Sélectionnez un match</option>
                                            <?php foreach($matches as $match): ?>
                                            <option value="<?php echo $match['id']; ?>">
                                                <?php echo htmlspecialchars($match['home_team']); ?> vs <?php echo htmlspecialchars($match['away_team']); ?>
                                            </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Nom de la catégorie</label>
                                        <input type="text" class="form-control" name="name" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Prix (MAD)</label>
                                        <input type="number" class="form-control" name="price" step="0.01" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Quantité totale</label>
                                        <input type="number" class="form-control" name="total_quantity" min="1" required>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="card-footer text-end">
                        <button type="submit" form="addTicketForm" class="btn btn-primary">Ajouter</button>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Liste des catégories de billets</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Match</th>
                                        <th>Catégorie</th>
                                        <th>Prix</th>
                                        <th>Quantité totale</th>
                                        <th>Quantité disponible</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($ticket_categories as $category): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($category['home_team']); ?> vs <?php echo htmlspecialchars($category['away_team']); ?></td>
                                        <td><?php echo htmlspecialchars($category['name']); ?></td>
                                        <td><?php echo number_format($category['price'], 2, ',', ' '); ?> MAD</td>
                                        <td><?php echo $category['total_quantity']; ?></td>
                                        <td><?php echo $category['available_quantity']; ?></td>
                                        <td>
                                            <button class="btn btn-sm btn-primary edit-ticket-btn" 
                                                    data-id="<?php echo $category['id']; ?>"
                                                    data-match="<?php echo $category['match_id']; ?>"
                                                    data-name="<?php echo htmlspecialchars($category['name']); ?>"
                                                    data-price="<?php echo $category['price']; ?>"
                                                    data-quantity="<?php echo $category['total_quantity']; ?>">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                                                            <form method="POST" action="tickets.php" class="d-inline delete-form">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="id" value="<?php echo $category['id']; ?>">
                                                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                                <button type="button" class="btn btn-sm btn-danger delete-btn"
                                                        data-match="<?php echo htmlspecialchars($category['home_team']); ?> vs <?php echo htmlspecialchars($category['away_team']); ?>"
                                                        data-name="<?php echo htmlspecialchars($category['name']); ?>"
                                                        data-price="<?php echo number_format($category['price'], 2, ',', ' '); ?> MAD"
                                                        data-quantity="<?php echo $category['available_quantity']; ?>">
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

    <!-- Edit Ticket Modal -->
    <div class="modal fade" id="editTicketModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Modifier la catégorie de billet</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="editTicketForm" method="POST" action="">
                        <input type="hidden" name="action" value="update">
                        <input type="hidden" name="id" id="editTicketId">
                        <div class="mb-3">
                            <label class="form-label">Match</label>
                            <select class="form-select" name="match_id" id="editMatchSelect" required>
                                <option value="">Sélectionnez un match</option>
                                <?php foreach($matches as $match): ?>
                                <option value="<?php echo $match['id']; ?>">
                                    <?php echo htmlspecialchars($match['home_team']); ?> vs <?php echo htmlspecialchars($match['away_team']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Nom de la catégorie</label>
                            <input type="text" class="form-control" name="name" id="editTicketName" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Prix (MAD)</label>
                            <input type="number" class="form-control" name="price" id="editTicketPrice" step="0.01" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Quantité totale</label>
                            <input type="number" class="form-control" name="total_quantity" id="editTicketQuantity" min="1" required>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                    <button type="submit" form="editTicketForm" class="btn btn-primary">Modifier</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Initialize Bootstrap modal
        const editModal = new bootstrap.Modal(document.getElementById('editTicketModal'));

        // Handle edit button click
        document.querySelectorAll('.edit-ticket-btn').forEach(button => {
            button.addEventListener('click', function() {
                const form = document.getElementById('editTicketForm');
                form.querySelector('input[name="id"]').value = this.dataset.id;
                form.querySelector('select[name="match_id"]').value = this.dataset.match;
                form.querySelector('input[name="name"]').value = this.dataset.name;
                form.querySelector('input[name="price"]').value = this.dataset.price;
                form.querySelector('input[name="total_quantity"]').value = this.dataset.quantity;
                editModal.show();
            });
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
                        
                        const match = this.dataset.match || 'Match inconnu';
                        const name = this.dataset.name || 'Catégorie inconnue';
                        const price = this.dataset.price || 'Prix inconnu';
                        const quantity = this.dataset.quantity || 'Quantité inconnue';
                        
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
                                            <p>Supprimer cette catégorie de billet ?</p>
                                            <p><strong>${match}</strong></p>
                                            <p><strong>${name}</strong></p>
                                            <p><small>Prix: ${price}</small></p>
                                            <p><small>Quantité disponible: ${quantity}</small></p>
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

            // Reset form and enable button after submission
            const form = document.getElementById('addTicketForm');
            const submitBtn = form.querySelector('button[type="submit"]');
            form.addEventListener('submit', function() {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Envoi...';
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
        });
    </script>
</body>
</html>
