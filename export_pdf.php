<?php
session_start();
require_once 'config.php';
require_once 'includes/functions.php';
require_once 'includes/pdf_generator.php';

// Initialiser la connexion à la base de données
$pdo = getDbConnection();
if (!$pdo) {
    error_log("[ERROR] Échec de la connexion à la base de données");
    header("Location: payment_error.php?error=" . urlencode("Erreur de connexion à la base de données"));
    exit;
}

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Récupérer l'ID de la commande
$order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;

if ($order_id <= 0) {
    header("Location: payment_error.php?error=" . urlencode("ID de commande invalide"));
    exit;
}

try {
    // Vérifier si la commande appartient à l'utilisateur
    $stmt = $pdo->prepare("
        SELECT * FROM orders 
        WHERE id = ? AND user_id = ?
    ");
    $stmt->execute([$order_id, $_SESSION['user_id']]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$order) {
        throw new Exception("Commande non trouvée ou non autorisée");
    }

    // Générer le PDF
    $filename = generateTicketPDF($order_id);
    
    // Rediriger vers le fichier PDF
    header("Location: tickets/$filename");
    exit;

} catch (Exception $e) {
    error_log("[ERROR] Échec de l'export PDF: " . $e->getMessage());
    header("Location: payment_error.php?error=" . urlencode($e->getMessage()));
    exit;
}
