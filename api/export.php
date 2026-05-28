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

    case 'debts':
        $status = $_GET['status'] ?? null; // unpaid/overdue/paid
        $debtorType = $_GET['debtorType'] ?? null;
        $rows = $exportService->getDebtsData($departmentId, $status, $debtorType);
        break;

    case 'salary':
    case 'salaries':
        $startDate = $_GET['startDate'] ?? null;
        $endDate   = $_GET['endDate'] ?? null;

        if ($startDate !== null || $endDate !== null) {
            $rows = $exportService->getSalaryDataByDateRange($startDate, $endDate, $departmentId);
        } else {
            $year = isset($_GET['year']) ? (int)$_GET['year'] : null;
            $month = isset($_GET['month']) ? (int)$_GET['month'] : null;
            $rows = $exportService->getSalaryData($year, $month, $departmentId);
        }
        break;

    case 'users':
        $rows = $exportService->getUsersData($departmentId);
        break;

    default:
        respond(400, ['status' => 'error', 'message' => 'Invalid action']);
}

// preview as simple HTML table for quick inspection in browser/Postman
if (isset($_GET['preview']) && $_GET['preview'] === '1') {
    header('Content-Type: text/html; charset=utf-8');
    echo "<table border=1 cellpadding=6 cellspacing=0 style=border-collapse:collapse;>";
    if (!empty($rows)) {
        // headers
        $first = reset($rows);
        echo '<thead><tr>';
        foreach (array_keys($first) as $h) {
            echo '<th>' . htmlspecialchars($h) . '</th>';
        }
        echo '</tr></thead>';
        echo '<tbody>';
        foreach ($rows as $r) {
            echo '<tr>';
            foreach ($r as $c) {
                echo '<td>' . htmlspecialchars((string)$c) . '</td>';
            }
            echo '</tr>';
        }
        echo '</tbody>';
    }
    echo '</table>';
    exit;
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
