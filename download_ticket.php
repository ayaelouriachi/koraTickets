<?php
session_start();
require_once 'config.php';
require_once 'includes/functions.php';

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Vérifier si l'ID de commande est fourni
if (!isset($_GET['order_id'])) {
    header('Location: my_orders.php');
    exit;
}

try {
    $order_id = $_GET['order_id'];
    
    // Vérifier si l'utilisateur est propriétaire de la commande
    $pdo = getDbConnection();
    $stmt = $pdo->prepare("
        SELECT * FROM orders 
        WHERE id = ? AND user_id = ?
    ");
    $stmt->execute([$order_id, $_SESSION['user_id']]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$order) {
        throw new Exception("Cette commande ne vous appartient pas");
    }
    
    // Vérifier si le PDF existe
    if (empty($order['pdf_filename'])) {
        throw new Exception("Aucun PDF n'a été généré pour cette commande");
    }
    
    // Chemin complet vers le fichier PDF
    $pdf_path = __DIR__ . '/tickets/' . $order['pdf_filename'];
    
    if (!file_exists($pdf_path)) {
        throw new Exception("Le fichier PDF n'existe pas sur le serveur");
    }
    
    // Définir les en-têtes pour le téléchargement
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="' . $order['pdf_filename'] . '"');
    header('Content-Length: ' . filesize($pdf_path));
    
    // Lire et envoyer le fichier
    readfile($pdf_path);
    exit;
    
} catch (Exception $e) {
    error_log("Erreur lors du téléchargement du ticket: " . $e->getMessage());
    $_SESSION['error'] = "Erreur lors du téléchargement du ticket: " . $e->getMessage();
    header('Location: my_orders.php');
    exit;
}
