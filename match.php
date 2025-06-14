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
    <title><?php echo htmlspecialchars($match['home_team']); ?> vs <?php echo htmlspecialchars($match['away_team']); ?> - Football Tickets</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="container mt-4">
        <div class="row">
            <div class="col-md-8">
                <div class="match-details">
                    <h1><?php echo htmlspecialchars($match['home_team']); ?> vs <?php echo htmlspecialchars($match['away_team']); ?></h1>
                    <div class="match-info">
                        <p><i class="bi bi-calendar"></i> <?php echo date('d/m/Y', strtotime($match['match_date'])); ?></p>
                        <p><i class="bi bi-clock"></i> <?php echo date('H:i', strtotime($match['match_date'])); ?></p>
                        <p><i class="bi bi-building"></i> <?php echo htmlspecialchars($match['stadium']); ?></p>
                    </div>
                    
                    <?php if($match['description']): ?>
                    <div class="match-description mt-4">
                        <h3>Description</h3>
                        <p><?php echo nl2br(htmlspecialchars($match['description'])); ?></p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="tickets-section">
                    <h3>Billets disponibles</h3>
                    
                    <?php foreach($ticket_categories as $category): ?>
                    <div class="ticket-category">
                        <h4><?php echo htmlspecialchars($category['name']); ?></h4>
                        <p>Prix: <?php echo number_format($category['price'], 2, ',', ' '); ?> MAD</p>
                        <p>Disponible: <?php echo $category['available_quantity']; ?> billets</p>
                        
                        <?php if($category['available_quantity'] > 0): ?>
                        <form action="add_to_cart.php" method="POST" class="mt-3">
                            <input type="hidden" name="ticket_category_id" value="<?php echo $category['id']; ?>">
                            <input type="hidden" name="match_id" value="<?php echo $match['id']; ?>">
                            <div class="input-group">
                                <input type="number" class="form-control" name="quantity" min="1" max="<?php echo $category['available_quantity']; ?>" value="1">
                                <button type="submit" class="btn btn-primary">Ajouter au panier</button>
                            </div>
                        </form>
                        <?php else: ?>
                        <div class="alert alert-warning">Indisponible</div>
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
