<?php
require_once 'config.php';

try {
    $pdo = getDbConnection();
    
    // Afficher la structure de la table ticket_categories
    echo "Structure de la table ticket_categories:\n";
    $stmt = $pdo->query("DESCRIBE ticket_categories");
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
    print_r($categories);
    
    // Afficher les index de la table
    echo "\nIndex de la table ticket_categories:\n";
    $stmt = $pdo->query("SHOW INDEX FROM ticket_categories");
    $indexes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    print_r($indexes);
    
    // Afficher les contraintes de la table
    echo "\nContraintes de la table ticket_categories:\n";
    $stmt = $pdo->query("SELECT * FROM information_schema.table_constraints WHERE table_name = 'ticket_categories' AND table_schema = DATABASE()");
    $constraints = $stmt->fetchAll(PDO::FETCH_ASSOC);
    print_r($constraints);
} catch (PDOException $e) {
    die("Erreur: " . $e->getMessage());
}
?>
