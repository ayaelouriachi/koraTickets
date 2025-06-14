<?php
require_once 'config.php';

try {
    $pdo = getDbConnection();
    
    // Désactiver les clés étrangères temporairement
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
    
    // Supprimer la contrainte d'unicité existante
    $pdo->exec("ALTER TABLE matches DROP INDEX IF EXISTS idx_unique_match");
    
    // Supprimer les doublons en gardant le plus ancien match
    $stmt = $pdo->prepare("
        SELECT m1.id FROM matches m1
        JOIN matches m2 ON m1.home_team = m2.home_team 
        AND m1.away_team = m2.away_team 
        AND DATE(m1.match_date) = DATE(m2.match_date)
        AND m1.stadium = m2.stadium
        AND m1.description = m2.description
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
    
    // Ajouter une nouvelle contrainte d'unicité plus stricte
    $pdo->exec("ALTER TABLE matches ADD UNIQUE INDEX idx_unique_match (home_team, away_team, match_date, stadium, description)");
    
    // Réactiver les clés étrangères
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
    
    echo "Base de données nettoyée avec succès !\n";
    echo "Contrainte d'unicité ajoutée pour empêcher les doublons.\n";
    echo "Tous les doublons existants ont été supprimés.\n";
} catch (PDOException $e) {
    // Réactiver les clés étrangères en cas d'erreur
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
    die("Erreur: " . $e->getMessage());
}
?>
