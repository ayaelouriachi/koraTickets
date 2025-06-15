<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config.php';
require_once 'includes/functions.php';
require_once 'includes/mail_config.php';
require_once 'vendor/autoload.php';

// Fonction pour logger les messages
function debug_log($message, $type = 'INFO') {
    $log_file = __DIR__ . '/logs/debug.log';
    $date = date('Y-m-d H:i:s');
    $log_message = "[$date][$type] $message\n";
    file_put_contents($log_file, $log_message, FILE_APPEND);
    echo htmlspecialchars($log_message) . "<br>";
}

// Test de la configuration du serveur
debug_log("=== Test de la configuration du serveur ===");
debug_log("PHP version: " . phpversion());
debug_log("sendmail_path: " . ini_get('sendmail_path'));
debug_log("SMTP: " . ini_get('SMTP'));
debug_log("smtp_port: " . ini_get('smtp_port'));

// Test des extensions PHP
debug_log("\n=== Test des extensions PHP ===");
$required_extensions = ['gd', 'pdo', 'pdo_mysql', 'openssl'];
foreach ($required_extensions as $ext) {
    debug_log("Extension $ext: " . (extension_loaded($ext) ? "OK" : "MANQUANTE"), 
              extension_loaded($ext) ? "INFO" : "ERROR");
}

// Test de la connexion à la base de données
debug_log("\n=== Test de la connexion à la base de données ===");
try {
    $pdo = getDbConnection();
    $pdo->query("SELECT 1");
    debug_log("Connexion DB: OK");
    
    // Test de la requête de commande
    $stmt = $pdo->prepare("
        SELECT o.*, u.email, u.username,
               CONCAT(m.home_team, ' vs ', m.away_team) as match_name,
               m.match_date, m.venue as stadium_name,
               tc.name as category_name
        FROM orders o
        JOIN users u ON o.user_id = u.id
        LEFT JOIN order_details od ON o.id = od.order_id
        LEFT JOIN ticket_categories tc ON od.ticket_category_id = tc.id
        LEFT JOIN matches m ON tc.match_id = m.id
        WHERE o.payment_status = 'completed'
        LIMIT 1
    ");
    $stmt->execute();
    $test_order = $stmt->fetch(PDO::FETCH_ASSOC);
    debug_log("Test requête commande: " . ($test_order ? "OK" : "ERREUR"));
    if ($test_order) {
        debug_log("Données de test trouvées: Order #" . $test_order['id']);
    }
} catch (PDOException $e) {
    debug_log("Erreur DB: " . $e->getMessage(), "ERROR");
}

// Test de génération PDF
debug_log("\n=== Test de génération PDF ===");
try {
    $pdf = new TCPDF();
    $pdf->AddPage();
    $pdf->SetFont('helvetica', '', 12);
    $pdf->Cell(0, 10, 'Test PDF', 0, 1, 'C');
    
    $temp_dir = __DIR__ . '/temp';
    if (!file_exists($temp_dir)) {
        mkdir($temp_dir, 0777, true);
    }
    
    $test_pdf = $temp_dir . '/test.pdf';
    $pdf->Output($test_pdf, 'F');
    
    debug_log("Génération PDF: " . (file_exists($test_pdf) ? "OK" : "ERREUR"));
    if (file_exists($test_pdf)) {
        unlink($test_pdf);
    }
} catch (Exception $e) {
    debug_log("Erreur PDF: " . $e->getMessage(), "ERROR");
}

// Test d'envoi d'email simple
debug_log("\n=== Test d'envoi d'email simple ===");
try {
    // Test avec mail() natif
    $to = "test@example.com";
    $subject = "Test email";
    $message = "Ceci est un test.";
    $headers = "From: noreply@footballtickets.com";
    
    if (@mail($to, $subject, $message, $headers)) {
        debug_log("Test mail() natif: OK");
    } else {
        debug_log("Test mail() natif: ERREUR", "ERROR");
    }
    
    // Test avec PHPMailer
    $result = sendEmail($to, $subject, $message, []);
    debug_log("Test PHPMailer: " . ($result['success'] ? "OK" : "ERREUR - " . $result['message']));
    
} catch (Exception $e) {
    debug_log("Erreur email: " . $e->getMessage(), "ERROR");
}

// Vérification des permissions
debug_log("\n=== Test des permissions ===");
$dirs_to_check = ['temp', 'logs', 'tickets'];
foreach ($dirs_to_check as $dir) {
    $full_path = __DIR__ . '/' . $dir;
    if (!file_exists($full_path)) {
        mkdir($full_path, 0777, true);
    }
    debug_log("Dossier $dir: " . (is_writable($full_path) ? "Accessible en écriture" : "NON accessible en écriture"));
}

// Affichage du rapport final
debug_log("\n=== Rapport final ===");
debug_log("Tests terminés. Vérifiez les erreurs ci-dessus pour identifier les problèmes.");
?> 