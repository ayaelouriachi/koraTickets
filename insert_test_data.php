<?php
require_once 'config.php';

try {
    $pdo = getDbConnection();
    
    // Insert test matches
    $matches = [
        [
            'home_team' => 'Raja Casablanca',
            'away_team' => 'Wydad Casablanca',
            'match_date' => date('Y-m-d H:i:s', strtotime('+1 week')),
            'stadium' => 'Stade Mohamed V',
            'description' => 'Derby de Casablanca',
            'image_url' => ''
        ],
        [
            'home_team' => 'FUS Rabat',
            'away_team' => 'Moghreb Tétouan',
            'match_date' => date('Y-m-d H:i:s', strtotime('+2 weeks')), 
            'stadium' => 'Complexe Moulay Abdellah',
            'description' => 'Match de championnat',
            'image_url' => ''
        ]
    ];

    // Insert matches
    $stmt = $pdo->prepare("INSERT INTO matches (home_team, away_team, match_date, stadium, description, image_url) VALUES (?, ?, ?, ?, ?, ?)");

    foreach ($matches as $match) {
        $stmt->execute([
            $match['home_team'],
            $match['away_team'],
            $match['match_date'],
            $match['stadium'],
            $match['description'],
            $match['image_url']
        ]);
        
        // Insert ticket categories for each match
        $match_id = $pdo->lastInsertId();
        $ticket_categories = [
            ['name' => 'Adulte - Catégorie 1', 'price' => 300, 'quantity' => 500],
            ['name' => 'Adulte - Catégorie 2', 'price' => 200, 'quantity' => 1000],
            ['name' => 'Enfant - Catégorie 1', 'price' => 150, 'quantity' => 200],
            ['name' => 'VIP', 'price' => 500, 'quantity' => 100]
        ];

        $stmt = $pdo->prepare("INSERT INTO ticket_categories (match_id, name, price, total_quantity, available_quantity) VALUES (?, ?, ?, ?, ?)");

        foreach ($ticket_categories as $category) {
            $stmt->execute([
                $match_id,
                $category['name'],
                $category['price'],
                $category['quantity'],
                $category['quantity']
            ]);
        }
    }

    echo "Données de test insérées avec succès !";
} catch (PDOException $e) {
    die("Erreur: " . $e->getMessage());
}
?>
