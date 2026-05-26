<?php
/*
 * Endpoint d'API pour la génération et l'affichage de factures.
 *
 * Actions :
 *  - view     : voir les données de facture
 *  - generate : générer la facture complète
 *  - print    : préparer le document d'impression
 */

header('Content-Type: application/json');

include_once __DIR__ . '/../services/AuthService.php';
include_once __DIR__ . '/../services/InvoceService.php';
include_once __DIR__ . '/../services/PrintService.php';

$auth = new AuthService();
$invoiceService = new InvoiceService();
$printService = new PrintService();

function respond($code, $payload) {
    http_response_code($code);
    header('Content-Type: application/json');
    echo json_encode($payload);
    exit;
}

$auth->requireAuth();
$action = $_GET['action'] ?? 'view';
$saleId = isset($_GET['saleId']) ? (int)$_GET['saleId'] : null;

if (!$saleId) {
    respond(400, ['status' => 'error', 'message' => 'saleId parameter is required']);
}

switch ($action) {
    case 'view':
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            respond(405, ['status' => 'error', 'message' => 'Method not allowed']);
        }
        $invoice = $invoiceService->getInvoiceData($saleId);
        if (!$invoice) {
            respond(404, ['status' => 'error', 'message' => 'Invoice data not found']);
        }
        respond(200, ['status' => 'success', 'data' => $invoice]);
        break;

    case 'generate':
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            respond(405, ['status' => 'error', 'message' => 'Method not allowed']);
        }
        $invoice = $invoiceService->generateInvoice($saleId);
        if (!$invoice) {
            respond(404, ['status' => 'error', 'message' => 'Invoice could not be generated']);
        }
        respond(200, ['status' => 'success', 'data' => $invoice]);
        break;

    case 'print':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            respond(405, ['status' => 'error', 'message' => 'Method not allowed']);
        }
        $invoice = $invoiceService->getInvoiceData($saleId);
        if (!$invoice) {
            respond(404, ['status' => 'error', 'message' => 'Invoice data not found']);
        }

        $resource = ['departement_id' => $invoice['sale']['departement_id'] ?? $invoice['sale']['departementId'] ?? null];
        if (!$auth->can('print_invoice', $resource)) {
            respond(403, ['status' => 'error', 'message' => 'Forbidden']);
        }

        $printPayload = $printService->prepareInvoiceForPrinter($saleId);
        if (!$printPayload) {
            respond(500, ['status' => 'error', 'message' => 'Unable to prepare print payload']);
        }
        respond(200, ['status' => 'success', 'data' => $printPayload]);
        break;

    default:
        respond(400, ['status' => 'error', 'message' => 'Invalid action']);
}
