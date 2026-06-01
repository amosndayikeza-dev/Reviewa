<?php
require_once __DIR__ . '/../bootstrap.php';

try {
    $ctx = mgrAuthContext();
    $service = $ctx['service'];
    $departementId = $ctx['departementId'];

    switch ($_SERVER['REQUEST_METHOD']) {
        case 'GET':
            $start = $_GET['startDate'] ?? $_GET['start'] ?? null;
            $end = $_GET['endDate'] ?? $_GET['end'] ?? null;
            $lines = $service->getSaleLinesHistory($departementId, $start, $end);
            $summary = $service->getSalesSummaryForPeriod($departementId, $start, $end);
            mgrRespond(200, [
                'success' => true,
                'data' => $lines,
                'summary' => $summary,
            ]);
            break;

        case 'POST':
            $sale = $service->createSale($departementId, $ctx['userId'], mgrReadJson());
            mgrRespond(201, ['success' => true, 'message' => 'Vente enregistrée', 'data' => $sale]);
            break;

        default:
            mgrRespond(405, ['success' => false, 'message' => 'Méthode non autorisée']);
    }
} catch (InvalidArgumentException $e) {
    mgrRespond(400, ['success' => false, 'message' => $e->getMessage()]);
} catch (Throwable $e) {
    mgrRespond(500, ['success' => false, 'message' => $e->getMessage()]);
}
