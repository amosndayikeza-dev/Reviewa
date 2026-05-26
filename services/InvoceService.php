<?php

include_once __DIR__ . '/../daophp/SalesDAO.php';
include_once __DIR__ . '/../daophp/Sale_itemsDAO.php';
include_once __DIR__ . '/../daophp/ProductsDAO.php';
include_once __DIR__ . '/../daophp/UserDAO.php';
include_once __DIR__ . '/../daophp/DepartementDAO.php';

/**
 * Service de gestion et de génération des factures.
 *
 * Ce service prépare les données de facturation à partir d'une vente existante.
 */
class InvoiceService {
    protected $salesDao;
    protected $saleItemsDao;
    protected $productsDao;
    protected $userDao;
    protected $departementDao;

    public function __construct() {
        $this->salesDao = new SalesDAO();
        $this->saleItemsDao = new Sale_itemsDAO();
        $this->productsDao = new ProductsDAO();
        $this->userDao = new UserDAO();
        $this->departementDao = new DepartementDAO();
    }

    /**
     * Récupère les informations de facture pour une vente.
     *
     * @param int $saleId
     * @return array|null
     */
    public function getInvoiceData($saleId) {
        $sale = $this->salesDao->findById($saleId);
        if (!$sale) {
            return null;
        }

        $items = $this->saleItemsDao->findWithProduct($saleId);
        $creator = $this->userDao->findById($sale['created_by'] ?? $sale['createdBy'] ?? null);
        $departement = $this->departementDao->findById($sale['departement_id'] ?? $sale['departementId'] ?? null);

        $totalQuantity = 0;
        $totalAmount = 0.0;
        foreach ($items as &$item) {
            $item['lineTotal'] = ($item['quantity'] ?? 0) * ($item['unit_price'] ?? $item['unitPrice'] ?? 0);
            $totalQuantity += $item['quantity'] ?? 0;
            $totalAmount += $item['lineTotal'];
        }
        unset($item);

        $invoice = [
            'invoiceNumber' => $this->formatInvoiceNumber($saleId, $sale),
            'saleId' => (int)$saleId,
            'soldAt' => $sale['sold_at'] ?? $sale['soldAt'] ?? null,
            'departement' => $departement,
            'createdBy' => $creator,
            'totalQuantity' => $totalQuantity,
            'totalAmount' => round($totalAmount, 2),
            'items' => $items,
            'sale' => $sale,
        ];

        return $invoice;
    }

    /**
     * Construit le numéro de facture à partir de la vente.
     *
     * @param int $saleId
     * @param array $sale
     * @return string
     */
    protected function formatInvoiceNumber($saleId, array $sale) {
        $datePart = '';
        if (!empty($sale['sold_at'])) {
            $datePart = date('Ymd', strtotime($sale['sold_at']));
        } elseif (!empty($sale['soldAt'])) {
            $datePart = date('Ymd', strtotime($sale['soldAt']));
        }
        return 'INV-' . ($datePart ?: date('Ymd')) . '-' . str_pad($saleId, 6, '0', STR_PAD_LEFT);
    }

    /**
     * Prépare un document facture prêt à être affiché ou exporté.
     *
     * @param int $saleId
     * @return array|null
     */
    public function generateInvoice($saleId) {
        $invoiceData = $this->getInvoiceData($saleId);
        if (!$invoiceData) {
            return null;
        }

        $invoiceData['currency'] = 'FBU';
        $invoiceData['generatedAt'] = date('Y-m-d H:i:s');
        $invoiceData['taxRate'] = 0.0; // à ajuster selon besoin
        $invoiceData['taxAmount'] = 0.0;
        $invoiceData['grandTotal'] = $invoiceData['totalAmount'] + $invoiceData['taxAmount'];

        return $invoiceData;
    }
}
