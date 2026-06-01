<?php
require_once __DIR__ . '/../bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    mgrRespond(405, ['success' => false, 'message' => 'Method not allowed']);
}

try {
    $ctx = mgrAuthContext();
    $year = isset($_GET['year']) ? (int)$_GET['year'] : (int)date('Y');
    $month = isset($_GET['month']) ? (int)$_GET['month'] : (int)date('n');
    if (!empty($_GET['period'])) {
        $parts = explode('-', $_GET['period']);
        if (count($parts) >= 2) {
            $year = (int)$parts[0];
            $month = (int)$parts[1];
        }
    }
    $data = $ctx['service']->getSalaryReports($ctx['departementId'], $year, $month);
    mgrRespond(200, ['success' => true, 'data' => $data]);
} catch (Throwable $e) {
    mgrRespond(500, ['success' => false, 'message' => $e->getMessage()]);
}
