<?php
session_start();
require_once 'config.php';
require_once 'includes/functions.php';

// Récupérer l'erreur depuis l'URL
$error = isset($_GET['error']) ? $_GET['error'] : 'Une erreur est survenue lors du traitement de votre paiement.';
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Erreur de paiement - Football Tickets</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .error-icon {
            font-size: 100px;
            margin: 2rem auto;
            display: block;
        }
    </style>
</head>
<body class="bg-light">
    <div class="container py-5">
        <i class="bi bi-exclamation-circle error-icon text-danger"></i>
        <h1 class="mt-4">Erreur de paiement</h1>
        <div class="alert alert-danger">
            <strong>Erreur:</strong> <?php echo htmlspecialchars($error); ?>
        </div>
        <div class="mt-4">
            <a href="cart.php" class="btn btn-primary btn-lg">
                <i class="bi bi-cart"></i> Retour au panier
            </a>
            <a href="index.php" class="btn btn-secondary btn-lg ms-2">
                <i class="bi bi-house-door"></i> Retour à l'accueil
            </a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
