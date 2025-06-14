<?php
require_once 'config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    $_SESSION['error'] = "Veuillez vous connecter pour ajouter des billets au panier";
    header('Location: login.php');
    exit;
}

// Get POST parameters
$ticket_category_id = $_POST['ticket_category_id'] ?? null;
$quantity = $_POST['quantity'] ?? null;
$match_id = $_POST['match_id'] ?? null;

if (!$ticket_category_id || !$quantity || !$match_id) {
    $_SESSION['error'] = "Paramètres manquants";
    header('Location: match.php?id=' . $match_id);
    exit;
}

try {
    $pdo = getDbConnection();
    
    // Check if ticket category exists and has enough quantity
    $stmt = $pdo->prepare("SELECT * FROM ticket_categories WHERE id = ? AND available_quantity >= ?");
    $stmt->execute([$ticket_category_id, $quantity]);
    $category = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$category) {
        $_SESSION['error'] = "Catégorie de billet non disponible";
        header('Location: match.php?id=' . $match_id);
        exit;
    }
    
    // Start transaction
    $pdo->beginTransaction();
    
    try {
        // Insert into cart
        $stmt = $pdo->prepare("INSERT INTO cart_items (session_id, ticket_category_id, quantity) VALUES (?, ?, ?)");
        $stmt->execute([session_id(), $ticket_category_id, $quantity]);
        
        // Update available quantity
        $stmt = $pdo->prepare("UPDATE ticket_categories SET available_quantity = available_quantity - ? WHERE id = ?");
        $stmt->execute([$quantity, $ticket_category_id]);
        
        // Commit transaction
        $pdo->commit();
        
        // Update cart count in session
        $stmt = $pdo->prepare("SELECT SUM(quantity) as total FROM cart_items WHERE session_id = ?");
        $stmt->execute([session_id()]);
        $cart_total = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
        $_SESSION['cart_count'] = $cart_total;
        
        $_SESSION['success'] = "Billets ajoutés au panier avec succès";
    } catch (Exception $e) {
        // Rollback transaction in case of error
        $pdo->rollBack();
        $_SESSION['error'] = "Erreur lors de l'ajout au panier: " . $e->getMessage();
    }
} catch (PDOException $e) {
    $_SESSION['error'] = "Erreur de base de données: " . $e->getMessage();
}

header('Location: match.php?id=' . $match_id);
exit;
?>
