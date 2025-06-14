<?php
require_once 'config.php';

// Simuler une connexion
try {
    $pdo = getDbConnection();
    
    // Vérifier les identifiants
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute(['admin']);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user && password_verify('password', $user['password'])) {
        // Créer la session
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];
        
        echo "Connexion réussie !\n";
        echo "Session:\n";
        print_r($_SESSION);
        
        // Rediriger vers la page d'accueil
        header('Location: index.php');
        exit;
    } else {
        echo "Erreur: Identifiants incorrects";
    }
} catch (PDOException $e) {
    echo "Erreur: " . $e->getMessage();
}
?>
