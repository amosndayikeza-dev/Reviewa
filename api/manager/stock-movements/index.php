<?php
require_once __DIR__ . '/../bootstrap.php';

try {
    $ctx = mgrAuthContext();
    $service = $ctx['service'];
    $departementId = $ctx['departementId'];

    switch ($_SERVER['REQUEST_METHOD']) {
        case 'GET':
            $type = $_GET['type'] ?? null;
            if ($type === 'sale') {
                $type = 'OUT';
            } elseif ($type === 'in') {
                $type = 'IN';
            } elseif ($type === 'out') {
                $type = 'OUT';
            }
            $start = $_GET['startDate'] ?? null;
            $end = $_GET['endDate'] ?? null;
            $movements = $service->getStockMovementsByDepartement($departementId, $type, $start, $end);
            mgrRespond(200, ['success' => true, 'data' => $movements]);
            break;

        case 'POST':
            $result = $service->adjustStock($departementId, $ctx['userId'], mgrReadJson());
            mgrRespond(201, ['success' => true, 'message' => 'Mouvement enregistré', 'data' => $result]);
            break;

        default:
            mgrRespond(405, ['success' => false, 'message' => 'Méthode non autorisée']);
    }
} catch (InvalidArgumentException $e) {
    mgrRespond(400, ['success' => false, 'message' => $e->getMessage()]);
} catch (Throwable $e) {
    mgrRespond(500, ['success' => false, 'message' => $e->getMessage()]);
}
