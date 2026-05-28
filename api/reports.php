<?php
/*
 * Endpoint de rapports métier.
 * Paramètres :
 *  - type: sales, debt, salary, inventory, departments
 *  - departementId: filtre par département
 *  - startDate, endDate: période pour les ventes
 *  - year, month: période pour les salaires
 *
 * Exemple : /api/reports.php?type=sales&departementId=3&startDate=2026-05-01&endDate=2026-05-21
 */

header('Content-Type: application/json');

include_once __DIR__ . '/../services/AuthService.php';
include_once __DIR__ . '/../services/ReportService.php';

$auth = new AuthService();
$reportService = new ReportService();

function respond($code, $payload) {
    http_response_code($code);
    header('Content-Type: application/json');
    echo json_encode($payload);
    exit;
}

// Auth obligatoire
$auth->requireAuth();

$type = strtolower($_GET['type'] ?? 'sales');
$departementId = isset($_GET['departement_id']) ? (int)$_GET['departement_id'] : null;
$startDate = $_GET['startDate'] ?? null;
$endDate = $_GET['endDate'] ?? null;
$year = isset($_GET['year']) ? (int)$_GET['year'] : null;
$month = isset($_GET['month']) ? (int)$_GET['month'] : null;

// Les managers ne peuvent consulter que leur propre département
if ($auth->isManager()) {
    $managerDepartementId = $auth->getUserDepartementId();
    if ($departementId !== null && $departementId !== $managerDepartementId) {
        respond(403, ['status' => 'error', 'message' => 'Forbidden']);
    }
    $departementId = $managerDepartementId;
}

// Les patrons et admins peuvent consulter tous les départements
switch ($type) {
    case 'sales':
        $data = $reportService->getSalesReport($departementId, $startDate, $endDate);
        $summary = $reportService->getSalesSummary($departementId, $startDate, $endDate);
        respond(200, ['status' => 'success', 'type' => 'sales', 'data' => $data, 'summary' => $summary]);
        break;

    case 'debt':
    case 'debts':
        $debtStatus = $_GET['status'] ?? null; // unpaid | paid | overdue
        $data    = $reportService->getDebtReport($departementId, $startDate, $endDate, $debtStatus);
        $summary = $reportService->getDebtSummary($departementId, $startDate, $endDate, $debtStatus);
        respond(200, ['status' => 'success', 'type' => 'debt', 'data' => $data, 'summary' => $summary]);
        break;

    case 'salary':
    case 'salary_report':
        if ($startDate !== null || $endDate !== null) {
            $data = $reportService->getSalaryReportByDateRange($departementId, $startDate, $endDate);
        } else {
            $data = $reportService->getSalaryReport($departementId, $year, $month);
        }
        respond(200, ['status' => 'success', 'type' => 'salary', 'data' => $data]);
        break;

    case 'inventory':
        $data = $reportService->getInventoryReport($departementId);
        respond(200, ['status' => 'success', 'type' => 'inventory', 'data' => $data]);
        break;

    case 'departements':
        $data = $reportService->getDepartements();
        respond(200, ['status' => 'success', 'type' => 'departements', 'data' => $data]);
        break;

    default:
        respond(400, ['status' => 'error', 'message' => 'Invalid report type']);
}
