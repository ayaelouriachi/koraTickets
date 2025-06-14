<?php
require_once 'config.php';

try {
    $pdo = getDbConnection();
    
    // Afficher la structure de la table orders
    echo "Structure de la table orders:\n";
    $stmt = $pdo->query("DESCRIBE orders");
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    print_r($orders);
    
    // Afficher les index de la table
    echo "\nIndex de la table orders:\n";
    $stmt = $pdo->query("SHOW INDEX FROM orders");
    $indexes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    print_r($indexes);
    
    // Afficher les contraintes de la table
    echo "\nContraintes de la table orders:\n";
    $stmt = $pdo->query("SELECT * FROM information_schema.table_constraints WHERE table_name = 'orders' AND table_schema = DATABASE()");
    $constraints = $stmt->fetchAll(PDO::FETCH_ASSOC);
    print_r($constraints);
} catch (PDOException $e) {
    die("Erreur: " . $e->getMessage());
}
?>
