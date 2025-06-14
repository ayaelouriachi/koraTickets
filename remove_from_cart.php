<?php
require_once 'config.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Veuillez vous connecter']);
    exit;
}

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);
$ticketCategoryId = $data['ticket_category_id'] ?? null;

if (!$ticketCategoryId) {
    echo json_encode(['success' => false, 'error' => 'Paramètre manquant']);
    exit;
}

try {
    $pdo = getDbConnection();
    
    // Start transaction
    $pdo->beginTransaction();
    
    try {
        // Get quantity from cart
        $stmt = $pdo->prepare("SELECT quantity FROM cart_items WHERE session_id = ? AND ticket_category_id = ?");
        $stmt->execute([session_id(), $ticketCategoryId]);
        $cartItem = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$cartItem) {
            throw new Exception("Item non trouvé dans le panier");
        }
        
        // Update available quantity
        $stmt = $pdo->prepare("UPDATE ticket_categories SET available_quantity = available_quantity + ? WHERE id = ?");
        $stmt->execute([$cartItem['quantity'], $ticketCategoryId]);
        
        // Remove from cart
        $stmt = $pdo->prepare("DELETE FROM cart_items WHERE session_id = ? AND ticket_category_id = ?");
        $stmt->execute([session_id(), $ticketCategoryId]);
        
        // Commit transaction
        $pdo->commit();
        
        // Update cart count in session
        $stmt = $pdo->prepare("SELECT SUM(quantity) as total FROM cart_items WHERE session_id = ?");
        $stmt->execute([session_id()]);
        $cartTotal = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
        $_SESSION['cart_count'] = $cartTotal;
        
        echo json_encode(['success' => true, 'cart_count' => $cartTotal]);
    } catch (Exception $e) {
        // Rollback transaction in case of error
        $pdo->rollBack();
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => "Erreur de base de données: " . $e->getMessage()]);
}
?>
