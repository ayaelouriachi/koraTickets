<?php
require_once 'config.php';
require_once 'includes/functions.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Veuillez vous connecter']);
    exit;
}

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);
$ticketCategoryId = filter_var($data['ticket_category_id'] ?? '', FILTER_SANITIZE_NUMBER_INT);

if (!$ticketCategoryId) {
    echo json_encode(['success' => false, 'error' => 'Paramètre manquant ou invalide']);
    exit;
}

try {
    $pdo = getDbConnection();
    
    // Start transaction
    $pdo->beginTransaction();
    
    try {
        // Get cart item details
        $stmt = $pdo->prepare("SELECT ci.quantity, tc.price
            FROM cart_items ci
            JOIN ticket_categories tc ON ci.ticket_category_id = tc.id
            WHERE ci.session_id = ? AND ci.ticket_category_id = ?");
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
        
        // Calculate new total price
        $stmt = $pdo->prepare("SELECT SUM(ci.quantity * tc.price) as total_price
            FROM cart_items ci
            JOIN ticket_categories tc ON ci.ticket_category_id = tc.id
            WHERE ci.session_id = ?");
        $stmt->execute([session_id()]);
        $newTotal = $stmt->fetch(PDO::FETCH_ASSOC)['total_price'] ?? 0;
        
        // Get new cart count
        $stmt = $pdo->prepare("SELECT SUM(quantity) as total FROM cart_items WHERE session_id = ?");
        $stmt->execute([session_id()]);
        $cartCount = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
        
        // Update session cart count
        $_SESSION['cart_count'] = $cartCount;
        
        $pdo->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Article retiré du panier',
            'new_total' => number_format($newTotal, 2, ',', ' '),
            'cart_count' => $cartCount
        ]);
    } catch (PDOException $e) {
        $pdo->rollBack();
        error_log("Error removing from cart (PDO): " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'error' => 'Erreur de base de données: ' . $e->getMessage()
        ]);
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Error removing from cart: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
} catch (Exception $e) {
    error_log("Error removing from cart (outer): " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Erreur inattendue: ' . $e->getMessage()
    ]);
}
?>
