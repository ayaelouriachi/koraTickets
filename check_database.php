<?php
require_once 'config.php';

try {
    $pdo = getDbConnection();
    
    // Get matches table structure
    echo "Structure de la table matches:\n";
    $stmt = $pdo->query("DESCRIBE matches");
    $matches = $stmt->fetchAll(PDO::FETCH_ASSOC);
    print_r($matches);
    
    // Get ticket_categories table structure
    echo "\nStructure de la table ticket_categories:\n";
    $stmt = $pdo->query("DESCRIBE ticket_categories");
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
    print_r($categories);
    
    // Get existing matches
    echo "\nMatchs existants:\n";
    $stmt = $pdo->query("SELECT * FROM matches");
    $matches = $stmt->fetchAll(PDO::FETCH_ASSOC);
    print_r($matches);
} catch (PDOException $e) {
    die("Erreur: " . $e->getMessage());
}
?>
