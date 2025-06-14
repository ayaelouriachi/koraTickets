<?php
require_once 'config.php';

try {
    $pdo = getDbConnection();
    
    // D'abord identifier les matchs à supprimer
    $stmt = $pdo->prepare("
        SELECT m1.id FROM matches m1
        JOIN matches m2 ON m1.home_team = m2.home_team 
        AND m1.away_team = m2.away_team 
        AND m1.match_date = m2.match_date 
        AND m1.stadium = m2.stadium
        WHERE m1.id > m2.id
    ");
    $stmt->execute();
    $duplicates = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (!empty($duplicates)) {
        // Supprimer les catégories de billets des matchs en double
        $stmt = $pdo->prepare("DELETE FROM ticket_categories WHERE match_id IN (?)");
        $stmt->execute([implode(',', $duplicates)]);
        
        // Supprimer les matchs en double
        $stmt = $pdo->prepare("DELETE FROM matches WHERE id IN (?)");
        $stmt->execute([implode(',', $duplicates)]);
    }
    
    echo "Doublons supprimés avec succès !";
} catch (PDOException $e) {
    die("Erreur: " . $e->getMessage());
}
?>
