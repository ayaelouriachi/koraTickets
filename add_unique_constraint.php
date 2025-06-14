<?php
require_once 'config.php';

try {
    $pdo = getDbConnection();
    
    // Ajouter une contrainte d'unicité sur la combinaison des colonnes
    $pdo->exec("ALTER TABLE matches ADD UNIQUE INDEX idx_unique_match (home_team, away_team, match_date, stadium)");
    
    echo "Contrainte d'unicité ajoutée avec succès !";
} catch (PDOException $e) {
    die("Erreur: " . $e->getMessage());
}
?>
