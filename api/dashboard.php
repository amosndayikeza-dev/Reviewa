<?php
header('Content-Type: application/json; charset=utf-8');

include_once __DIR__ . '/../services/AuthService.php';
include_once __DIR__ . '/../services/DashboardService.php';

$auth = new AuthService();
$dashboardService = new DashboardService();

function respond($code, $payload) {
    http_response_code($code);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

$auth->requireAuth();

$departementId = isset($_GET['departement_id']) ? (int)$_GET['departement_id'] : null;
$status = $_GET['status'] ?? null;

do {
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        respond(405, ['success' => false, 'message' => 'Méthode non autorisée']);
    }

    $data = $dashboardService->getDashboardSummary();

    if ($departementId !== null) {
        $data['dailyRevenue'] = $dashboardService->getDailyRevenue($departementId);
    }
    if ($status !== null) {
        $data['totalDebtsAmount'] = $dashboardService->getTotalDebtsAmount($status);
    }

    respond(200, [
        'success' => true,
        'type' => 'dashboard_summary',
        'data' => $data,
    ]);
} while (false);
