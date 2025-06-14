<?php
require_once 'config.php';

try {
    $pdo = getDbConnection();
    
    // Ajouter la colonne order_id si elle n'existe pas
    $stmt = $pdo->query("SHOW COLUMNS FROM orders LIKE 'order_id'");
    if (!$stmt->fetch()) {
        $pdo->exec("ALTER TABLE orders ADD COLUMN order_id VARCHAR(100) NOT NULL UNIQUE");
    }
    
    echo "La base de données a été mise à jour avec succès !\n";
} catch (PDOException $e) {
    echo "Erreur lors de la mise à jour de la base de données: " . $e->getMessage() . "\n";
}
?>
