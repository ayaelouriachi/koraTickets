<?php
// Créer une classe PDF simple
require_once __DIR__ . '/../tcpdf/tcpdf/TCPDF-main/tcpdf.php';

class TicketPDF extends TCPDF {
    public function __construct($orientation = 'P', $unit = 'mm', $format = 'A4', $unicode = true, $encoding = 'UTF-8', $diskcache = false) {
        parent::__construct($orientation, $unit, $format, $unicode, $encoding, $diskcache);
        
        // Configuration TCPDF
        $this->setPrintHeader(false);
        $this->setPrintFooter(false);
        $this->setFontSubsetting(true);
    }
    // Header
    public function Header() {
        $this->SetFont('freesans', 'B', 16);
        $this->Cell(0, 10, 'TICKET DE FOOTBALL', 0, 1, 'C');
        $this->Ln(10);
    }

    // Footer
    public function Footer() {
        $this->SetY(-15);
        $this->SetFont('freesans', 'I', 8);
        $this->Cell(0, 10, 'Football Tickets - ' . date('Y'), 0, 0, 'C');
    }
}

function generateTicketPDF($order_id) {
    error_log("[DEBUG] Génération du PDF pour l'ordre: $order_id");
    
    try {
        // Créer une nouvelle instance de notre classe TicketPDF
        $pdf = new TicketPDF('P', 'mm', 'A4', true, 'UTF-8', false);
        
        // Configuration de la page
        $pdf->SetCreator('Football Tickets');
        $pdf->SetAuthor('Football Tickets');
        $pdf->SetTitle('Ticket de Football - ' . $order_id);
        $pdf->SetSubject('Ticket de Football');
        
        // Marges
        $pdf->SetMargins(15, 27, 15);
        $pdf->SetHeaderMargin(10);
        $pdf->SetFooterMargin(15);
        
        // Police
        $pdf->SetFont('freesans', '', 10);
        
        // Ajouter une page
        $pdf->AddPage();
        
        // Connexion à la base de données
        $pdo = getDbConnection();
        if (!$pdo) {
            error_log("[ERROR] Impossible de se connecter à la base de données");
            throw new Exception("Erreur de connexion à la base de données");
        }
        
        // Récupérer les détails de la commande
        $stmt = $pdo->prepare("
            SELECT o.*, u.email, u.phone_number as phone, 
                   tc.name as ticket_name, tc.price,
                   m.name as match_name, m.date as match_date,
                   m.stadium as stadium, m.city as city
            FROM orders o
            JOIN users u ON o.user_id = u.id
            JOIN order_details od ON o.id = od.order_id
            JOIN ticket_categories tc ON od.ticket_category_id = tc.id
            JOIN matches m ON tc.match_id = m.id
            WHERE o.id = ?
        ");
        
        if (!$stmt->execute([$order_id])) {
            $errorInfo = $stmt->errorInfo();
            error_log("[ERROR] Échec de la requête SQL: " . $errorInfo[2]);
            throw new Exception("Échec de la requête SQL: " . $errorInfo[2]);
        }
        
        $tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($tickets)) {
            error_log("[ERROR] Aucun ticket trouvé pour l'ordre: $order_id");
            throw new Exception("Aucun ticket trouvé pour cette commande");
        }
        
        $first_ticket = $tickets[0];
        
        // Générer le PDF pour chaque ticket
        foreach ($tickets as $ticket) {
            try {
                // En-tête
                $pdf->SetFont('helvetica', 'B', 16);
                $pdf->Cell(0, 10, 'TICKET DE FOOTBALL', 0, 1, 'C');
                $pdf->Ln(10);
                
                // Informations du match
                $pdf->SetFont('helvetica', 'B', 12);
                $pdf->Cell(0, 10, $ticket['match_name'], 0, 1, 'C');
                
                $pdf->SetFont('helvetica', '', 10);
                $pdf->Cell(0, 10, 'Date: ' . date('d/m/Y H:i', strtotime($ticket['match_date'])), 0, 1, 'C');
                $pdf->Cell(0, 10, 'Stade: ' . $ticket['stadium'] . ' (' . $ticket['city'] . ')', 0, 1, 'C');
                
                // Ligne de séparation
                $pdf->Ln(10);
                $pdf->Cell(0, 0.5, '', 'T', 1);
                $pdf->Ln(10);
                
                // Informations du ticket
                $pdf->SetFont('helvetica', 'B', 12);
                $pdf->Cell(0, 10, 'Détails du ticket', 0, 1, 'C');
                
                $pdf->SetFont('helvetica', '', 10);
                $pdf->Cell(0, 10, 'Catégorie: ' . $ticket['ticket_name'], 0, 1);
                $pdf->Cell(0, 10, 'Prix: ' . number_format($ticket['price'], 2) . ' MAD', 0, 1);
                
                // Ligne de séparation
                $pdf->Ln(10);
                $pdf->Cell(0, 0.5, '', 'T', 1);
                $pdf->Ln(10);
                
                // Informations du titulaire
                $pdf->SetFont('helvetica', 'B', 12);
                $pdf->Cell(0, 10, 'Informations du titulaire', 0, 1, 'C');
                
                $pdf->SetFont('helvetica', '', 10);
                $pdf->Cell(0, 10, 'Email: ' . $ticket['email'], 0, 1);
                $pdf->Cell(0, 10, 'Téléphone: ' . $ticket['phone'], 0, 1);
                
                // Ligne de séparation
                $pdf->Ln(10);
                $pdf->Cell(0, 0.5, '', 'T', 1);
                $pdf->Ln(10);
                
                // Code QR
                $pdf->SetFont('helvetica', 'B', 12);
                $pdf->Cell(0, 10, 'Code QR', 0, 1, 'C');
                
                // Générer le code QR
                $qr_data = "TICKET:" . $ticket['id'] . ":" . $ticket['paypal_transaction_id'];
                $pdf->write2DBarcode($qr_data, 'QRCODE,H', 80, 150, 60, 60, '', 'N');
                
                // Footer
                $pdf->SetFont('helvetica', 'B', 8);
                $pdf->SetY(-15);
                $pdf->Cell(0, 10, 'Football Tickets - ' . date('Y'), 0, 0, 'C');
                
                // Nouvelle page pour le prochain ticket
                if ($ticket !== end($tickets)) {
                    $pdf->AddPage();
                }
            } catch (Exception $e) {
                error_log("[ERROR] Échec de la génération du ticket: " . $e->getMessage());
                throw new Exception("Échec de la génération du ticket: " . $e->getMessage());
            }
        }
        
        // Générer le nom du fichier
        $filename = 'ticket_' . $order_id . '_' . date('Ymd_His') . '.pdf';
        
        // Créer le dossier tickets s'il n'existe pas
        if (!file_exists('tickets')) {
            mkdir('tickets', 0777, true);
        }
        
        // Sauvegarder le PDF
        try {
            $pdf->Output('tickets/' . $filename, 'F');
            error_log("[DEBUG] PDF sauvegardé avec succès: $filename");
            return $filename;
        } catch (Exception $e) {
            error_log("[ERROR] Échec de la sauvegarde du PDF: " . $e->getMessage());
            throw new Exception("Échec de la sauvegarde du PDF: " . $e->getMessage());
        }
        
    } catch (Exception $e) {
        error_log("[ERROR] Erreur lors de la génération du PDF: " . $e->getMessage());
        throw new Exception("Erreur lors de la génération du PDF: " . $e->getMessage());
    }
}
