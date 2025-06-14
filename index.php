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
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="container mt-4">
        <h1 class="mb-4">Matchs à venir</h1>
        
        <?php
        try {
            $pdo = getDbConnection();
            $stmt = $pdo->query("SELECT * FROM matches WHERE match_date > NOW() ORDER BY match_date");
            $matches = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            echo "<div class='alert alert-danger'>Erreur lors du chargement des matchs</div>";
        }
        ?>

        <?php if(empty($matches)): ?>
        <div class="alert alert-info">
            Aucun match à venir pour le moment.
        </div>
        <?php else: ?>
        <div class="row">
            <?php foreach($matches as $match): ?>
            <div class="col-md-4 mb-4">
                <div class="card h-100">
                    <?php if($match['image_url']): ?>
                    <img src="<?php echo htmlspecialchars($match['image_url']); ?>" class="card-img-top" alt="Match">
                    <?php endif; ?>
                    <div class="card-body">
                        <h5 class="card-title">
                            <?php echo htmlspecialchars($match['home_team']); ?> vs 
                            <?php echo htmlspecialchars($match['away_team']); ?>
                        </h5>
                        <p class="card-text">
                            <i class="bi bi-calendar"></i> <?php echo date('d/m/Y', strtotime($match['match_date'])); ?>
                            <br>
                            <i class="bi bi-clock"></i> <?php echo date('H:i', strtotime($match['match_date'])); ?>
                            <br>
                            <i class="bi bi-building"></i> <?php echo htmlspecialchars($match['stadium']); ?>
                        </p>
                        <a href="match.php?id=<?php echo $match['id']; ?>" class="btn btn-primary">Voir les billets</a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

    <?php include 'includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
