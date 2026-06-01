<?php
require_once __DIR__ . '/../bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    mgrRespond(405, ['success' => false, 'message' => 'Method not allowed']);
}

try {
    $ctx = mgrAuthContext();
    $user = $ctx['auth']->getCurrentUser();
    $dept = $ctx['service']->getDepartementInfo($ctx['departementId']);
    mgrRespond(200, [
        'success' => true,
        'user' => $user,
        'departement' => $dept,
        'departementName' => $dept['name'] ?? 'Département',
    ]);
} catch (Throwable $e) {
    mgrRespond(500, ['success' => false, 'message' => $e->getMessage()]);
}
