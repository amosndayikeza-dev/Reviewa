<?php
/*
 * Endpoint d'impression générique pour les documents de facturation.
 *
 * Actions :
 *  - invoice : préparer l'impression d'une facture existante
 */

header('Content-Type: application/json');

include_once __DIR__ . '/../services/AuthService.php';
include_once __DIR__ . '/../services/PrintService.php';

$auth = new AuthService();
$printService = new PrintService();

function respond($code, $payload) {
    http_response_code($code);
    header('Content-Type: application/json');
    echo json_encode($payload);
    exit;
}

$auth->requireAuth();
$action = $_GET['action'] ?? 'invoice';
$saleId = isset($_GET['saleId']) ? (int)$_GET['saleId'] : null;

if (!$saleId) {
    respond(400, ['status' => 'error', 'message' => 'saleId parameter is required']);
}

switch ($action) {
    case 'invoice':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            respond(405, ['status' => 'error', 'message' => 'Method not allowed']);
        }

        include_once __DIR__ . '/../services/InvoceService.php';
        $invoiceService = new InvoiceService();
        $invoice = $invoiceService->getInvoiceData($saleId);
        if (!$invoice) {
            respond(404, ['status' => 'error', 'message' => 'Invoice data not found']);
        }

        $resource = ['departement_id' => $invoice['sale']['departement_id'] ?? $invoice['sale']['departementId'] ?? null];
        if (!$auth->can('print_invoice', $resource)) {
            respond(403, ['status' => 'error', 'message' => 'Forbidden']);
        }

        $payload = $printService->prepareInvoiceForPrinter($saleId);
        if (!$payload) {
            respond(404, ['status' => 'error', 'message' => 'Unable to prepare invoice print data']);
        }

        respond(200, ['status' => 'success', 'data' => $payload]);
        break;

    default:
        respond(400, ['status' => 'error', 'message' => 'Invalid action']);
}
