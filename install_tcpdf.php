<?php
// Script d'installation de TCPDF

// Créer le dossier tcpdf s'il n'existe pas déjà
$tcpdf_dir = __DIR__ . '/tcpdf/tcpdf';
if (!file_exists($tcpdf_dir)) {
    mkdir($tcpdf_dir, 0777, true);
}

// Télécharger TCPDF depuis le site officiel
$url = 'https://tcpdf.org/downloads/tcpdf.zip';
$zip_file = __DIR__ . '/tcpdf.zip';

// Télécharger le fichier
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$zip_content = curl_exec($ch);
curl_close($ch);

// Sauvegarder le fichier zip
file_put_contents($zip_file, $zip_content);

// Extraire le contenu
$zip = new ZipArchive();
if ($zip->open($zip_file) === true) {
    $zip->extractTo($tcpdf_dir);
    $zip->close();
    
    // Supprimer l'archive
    unlink($zip_file);
    
    echo "TCPDF installé avec succès !";
} else {
    echo "Erreur lors de l'extraction de l'archive";
}
?>
