<?php
/*
 * API pour l'export de données (sales/users).
 * Params:
 *  - action: sales|users
 *  - format: csv|json (default json)
 *  - startDate, endDate (for sales)
 *  - departmentId
 */

header('Content-Type: application/json');

include_once __DIR__ . '/../services/AuthService.php';
include_once __DIR__ . '/../services/ExportService.php';

$auth = new AuthService();
$exportService = new ExportService();

function respond($code, $payload) {
    http_response_code($code);
    header('Content-Type: application/json');
    echo json_encode($payload);
    exit;
}

$auth->requireAuth();

$action = $_GET['action'] ?? 'sales';
$format = strtolower($_GET['format'] ?? 'json');
$departmentId = isset($_GET['departmentId']) ? (int)$_GET['departmentId'] : null;

// permission check
if (!$exportService->canExportDepartment($departmentId)) {
    respond(403, ['status' => 'error', 'message' => 'Forbidden']);
}

switch ($action) {
    case 'sales':
        $startDate = $_GET['startDate'] ?? null;
        $endDate = $_GET['endDate'] ?? null;
        $rows = $exportService->getSalesData($startDate, $endDate, $departmentId);
        break;

    case 'users':
        $rows = $exportService->getUsersData($departmentId);
        break;

    default:
        respond(400, ['status' => 'error', 'message' => 'Invalid action']);
}

if ($format === 'csv') {
    $csv = $exportService->exportRowsToCsv($rows);
    // Send CSV download
    header('Content-Type: text/csv');
    $filename = sprintf('export_%s_%s.csv', $action, date('Ymd_His'));
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    echo $csv;
    exit;
}

if (in_array($format, ['excel', 'xls', 'xlsx'], true)) {
    $xml = $exportService->exportRowsToExcel($rows);
    header('Content-Type: application/vnd.ms-excel');
    $filename = sprintf('export_%s_%s.xls', $action, date('Ymd_His'));
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    echo $xml;
    exit;
}

// default: json
respond(200, ['status' => 'success', 'count' => count($rows), 'data' => $rows]);
