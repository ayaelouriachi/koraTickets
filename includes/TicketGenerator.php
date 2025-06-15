<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config.php';

if (!defined('TICKETS_DIR')) {
    define('TICKETS_DIR', __DIR__ . '/../tickets');
}

class TicketGenerator {
    private $order;
    private $user;
    private $orderDetails;
    private $pdf;

    public function __construct($order, $user, $orderDetails = null) {
        error_log("[DEBUG] Initialisation de TicketGenerator - Order ID: " . $order['id']);
        error_log("[DEBUG] Données utilisateur: " . print_r($user, true));
        error_log("[DEBUG] Détails de la commande: " . print_r($orderDetails, true));
        $this->order = $order;
        $this->user = $user;
        $this->orderDetails = $orderDetails;
        $this->initializePDF();
    }

    private function initializePDF() {
        try {
            error_log("[DEBUG] Initialisation du PDF");
            $this->pdf = new \TCPDF('P', 'mm', 'A4', true, 'UTF-8');
            $this->pdf->SetCreator('KoraTickets');
            $this->pdf->SetAuthor('KoraTickets');
            $this->pdf->SetTitle('Ticket #' . $this->order['id']);
            
            // Supprime les en-têtes et pieds de page par défaut
            $this->pdf->setPrintHeader(false);
            $this->pdf->setPrintFooter(false);
            
            // Ajoute une nouvelle page
            $this->pdf->AddPage();
            error_log("[DEBUG] PDF initialisé avec succès");
        } catch (Exception $e) {
            error_log("[ERROR] Erreur lors de l'initialisation du PDF: " . $e->getMessage());
            throw $e;
        }
    }

    private function generateQRCode() {
        try {
            error_log("[DEBUG] Génération du QR Code pour la commande: " . $this->order['id']);
            $qrCode = new \Endroid\QrCode\QrCode($this->order['id']);
            $writer = new \Endroid\QrCode\Writer\PngWriter();
            $result = $writer->write($qrCode);
            
            if (!file_exists(TICKETS_DIR)) {
                error_log("[DEBUG] Création du répertoire TICKETS_DIR: " . TICKETS_DIR);
                mkdir(TICKETS_DIR, 0755, true);
            }
            
            $qrImagePath = TICKETS_DIR . '/qr_' . $this->order['id'] . '.png';
            $result->saveToFile($qrImagePath);
            error_log("[DEBUG] QR Code généré: " . $qrImagePath);
            
            return $qrImagePath;
        } catch (Exception $e) {
            error_log("[ERROR] Erreur lors de la génération du QR Code: " . $e->getMessage());
            throw $e;
        }
    }

    public function generate() {
        try {
            error_log("[DEBUG] Début de la génération du ticket PDF");
            
            // En-tête du ticket
            $this->pdf->SetFont('helvetica', 'B', 20);
            $this->pdf->Cell(0, 10, 'KoraTickets - Ticket officiel', 0, 1, 'C');
            
            // Informations du match
            $this->pdf->SetFont('helvetica', '', 12);
            $this->pdf->Ln(10);
            $this->pdf->Cell(0, 10, 'Numéro de commande: ' . $this->order['id'], 0, 1);
            $this->pdf->Cell(0, 10, 'Client: ' . $this->user['name'], 0, 1);
            $this->pdf->Cell(0, 10, 'Montant payé: ' . number_format($this->order['total_amount'], 2) . ' MAD', 0, 1);
            
            // Détails du match
            $this->pdf->Ln(10);
            $this->pdf->SetFont('helvetica', 'B', 14);
            $this->pdf->Cell(0, 10, 'Détails du match', 0, 1);
            $this->pdf->SetFont('helvetica', '', 12);
            
            if ($this->orderDetails) {
                foreach ($this->orderDetails as $detail) {
                    $this->pdf->Cell(0, 10, $detail['match_name'], 0, 1);
                    $this->pdf->Cell(0, 10, 'Date: ' . $detail['match_date'], 0, 1);
                    $this->pdf->Cell(0, 10, 'Stade: ' . $detail['stadium'], 0, 1);
                    $this->pdf->Cell(0, 10, 'Catégorie: ' . $detail['category_name'], 0, 1);
                    $this->pdf->Cell(0, 10, 'Quantité: ' . $detail['quantity'], 0, 1);
                    $this->pdf->Ln(5);
                }
            } else {
                error_log("[WARNING] Aucun détail de commande fourni");
                $this->pdf->Cell(0, 10, 'Détails non disponibles', 0, 1);
            }
            
            // QR Code
            $qrImagePath = $this->generateQRCode();
            $this->pdf->Image($qrImagePath, 80, 180, 50, 50);
            unlink($qrImagePath); // Supprime le fichier temporaire
            
            // Conditions d'utilisation
            $this->pdf->SetFont('helvetica', '', 8);
            $this->pdf->Ln(70);
            $this->pdf->MultiCell(0, 5, 'Conditions d\'utilisation : Ce ticket est personnel et non transférable. Il doit être présenté avec une pièce d\'identité à l\'entrée du stade. L\'accès peut être refusé en cas de non-respect des règles du stade.', 0, 'L');
            
            // Génère le fichier PDF
            if (!file_exists(TICKETS_DIR)) {
                error_log("[DEBUG] Création du répertoire TICKETS_DIR pour le PDF: " . TICKETS_DIR);
                mkdir(TICKETS_DIR, 0755, true);
            }
            
            $pdfPath = TICKETS_DIR . '/ticket_' . $this->order['id'] . '.pdf';
            error_log("[DEBUG] Tentative de sauvegarde du PDF: " . $pdfPath);
            
            $this->pdf->Output($pdfPath, 'F');
            error_log("[DEBUG] PDF généré avec succès: " . $pdfPath);
            
            return $pdfPath;
        } catch (Exception $e) {
            error_log("[ERROR] Erreur lors de la génération du PDF: " . $e->getMessage());
            throw $e;
        }
    }
} 