<?php
require_once 'config.php';

try {
    $pdo = getDbConnection();
    
    // Vérifier s'il y a des administrateurs
    $stmt = $pdo->query("SELECT * FROM users WHERE role = 'admin'");
    $admins = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($admins)) {
        // Créer un administrateur par défaut
        $stmt = $pdo->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)");
        $stmt->execute([
            'admin',
            'admin@example.com',
            password_hash('password', PASSWORD_DEFAULT),
            'admin'
        ]);
        
        echo "Administrateur créé avec succès !\n";
        echo "Username: admin\n";
        echo "Password: password\n";
    } else {
        echo "Administrateurs existants:\n";
        foreach ($admins as $admin) {
            echo "- Username: " . $admin['username'] . "\n";
        }
    }
} catch (PDOException $e) {
    die("Erreur: " . $e->getMessage());
}
?>
