<?php

include_once __DIR__ . '/InvoceService.php';

/**
 * Service de préparation des documents d'impression.
 *
 * Ce service génère des données ou du texte destinés à être envoyés
 * à une imprimante de type ticket thermique comme Xprinter.
 */
class PrintService {
    protected $invoiceService;

    public function __construct() {
        $this->invoiceService = new InvoiceService();
    }

    /**
     * Crée un texte de facture imprimable.
     *
     * @param int $saleId
     * @return string|null
     */
    public function buildInvoiceText($saleId) {
        $invoice = $this->invoiceService->generateInvoice($saleId);
        if (!$invoice) {
            return null;
        }

        $lines = [];
        $lines[] = "FACTURE : " . $invoice['invoiceNumber'];
        $lines[] = "Date : " . $invoice['soldAt'];
        $lines[] = "Département : " . ($invoice['departement']['name'] ?? 'N/A');
        $lines[] = "Créé par : " . trim(($invoice['createdBy']['first_name'] ?? '') . ' ' . ($invoice['created_by']['last_name'] ?? ''));
        $lines[] = str_repeat('-', 32);
        $lines[] = sprintf("%-16s %5s %8s", 'Produit', 'Qté', 'Total');
        $lines[] = str_repeat('-', 32);

        foreach ($invoice['items'] as $item) {
            $name = substr($item['product_name'] ?? $item['productName'] ?? 'article', 0, 16);
            $quantity = $item['quantity'] ?? 0;
            $lineTotal = number_format($item['lineTotal'] ?? 0, 2, '.', '');
            $lines[] = sprintf("%-16s %5s %8s", $name, $quantity, $lineTotal);
        }

        $lines[] = str_repeat('-', 32);
        $lines[] = sprintf("%-22s %8.2f", 'Sous-total :', $invoice['totalAmount']);
        $lines[] = sprintf("%-22s %8.2f", 'Taxe :', $invoice['taxAmount']);
        $lines[] = sprintf("%-22s %8.2f", 'Total TTC :', $invoice['grandTotal']);
        $lines[] = str_repeat('-', 32);
        $lines[] = "Merci pour votre achat !";

        return implode("\n", $lines);
    }

    /**
     * Prépare le contenu prêt à l'impression pour Xprinter.
     *
     * @param int $saleId
     * @return array|null
     */
    public function prepareInvoiceForPrinter($saleId) {
        $text = $this->buildInvoiceText($saleId);
        if ($text === null) {
            return null;
        }

        return [
            'printer' => 'xprinter',
            'format' => 'text',
            'content' => $text,
            'generatedAt' => date('Y-m-d H:i:s'),
        ];
    }

    /**
     * Simule l'envoi du texte à l'imprimante.
     *
     * @param int $saleId
     * @return string|null
     */
    public function printInvoice($saleId) {
        $payload = $this->prepareInvoiceForPrinter($saleId);
        if (!$payload) {
            return null;
        }

        // Ici, on retourne le contenu qui sera envoyé au driver Xprinter.
        // Sur une vraie intégration, il faudra appeler le SDK/driver de l'imprimante.
        return $payload['content'];
    }
}
