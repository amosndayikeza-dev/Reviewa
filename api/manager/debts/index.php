<?php
require_once __DIR__ . '/../bootstrap.php';

try {
    $ctx = mgrAuthContext();
    $service = $ctx['service'];
    $departementId = $ctx['departementId'];
    $id = mgrPathId();
    $status = $_GET['status'] ?? null;
    if ($status === '') {
        $status = null;
    }

    switch ($_SERVER['REQUEST_METHOD']) {
        case 'GET':
            $debts = $service->getDebtList($departementId, $status);
            $summary = $service->getDebtSummary($departementId);
            mgrRespond(200, [
                'success' => true,
                'data' => $debts,
                'summary' => $summary,
            ]);
            break;

        case 'POST':
            if ($id === null) {
                mgrRespond(400, ['success' => false, 'message' => 'ID dette requis']);
            }
            $body = mgrReadJson();
            $paid = $body['paidAmount'] ?? $body['paid_amount'] ?? $body['amount'] ?? null;
            if ($paid === null) {
                mgrRespond(400, ['success' => false, 'message' => 'Montant payé requis']);
            }
            $debt = $service->recordDebtPayment($id, $paid, $departementId);
            mgrRespond(200, ['success' => true, 'message' => 'Paiement enregistré', 'debt' => $debt]);
            break;

        default:
            mgrRespond(405, ['success' => false, 'message' => 'Méthode non autorisée']);
    }
} catch (InvalidArgumentException $e) {
    mgrRespond(400, ['success' => false, 'message' => $e->getMessage()]);
} catch (Throwable $e) {
    mgrRespond(500, ['success' => false, 'message' => $e->getMessage()]);
}
