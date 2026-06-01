<?php
require_once __DIR__ . '/../bootstrap.php';

try {
    $ctx = mgrAuthContext();
    $service = $ctx['service'];
    $departementId = $ctx['departementId'];
    $id = mgrPathId();
    $position = $_GET['position'] ?? $_GET['poste'] ?? null;

    switch ($_SERVER['REQUEST_METHOD']) {
        case 'GET':
            $employees = $service->getEmployeesByDepartement($departementId);
            if ($position !== null && $position !== '') {
                $employees = array_values(array_filter($employees, function ($e) use ($position) {
                    return strtolower((string)($e['position'] ?? '')) === strtolower($position);
                }));
            }
            mgrRespond(200, ['success' => true, 'data' => $employees]);
            break;

        case 'POST':
            $employee = $service->createEmployee($departementId, mgrReadJson());
            mgrRespond(201, ['success' => true, 'message' => 'Employé créé', 'data' => $employee]);
            break;

        case 'DELETE':
            if ($id === null) {
                mgrRespond(400, ['success' => false, 'message' => 'ID requis']);
            }
            $service->deleteEmployee($id, $departementId);
            mgrRespond(200, ['success' => true, 'message' => 'Employé supprimé']);
            break;

        default:
            mgrRespond(405, ['success' => false, 'message' => 'Méthode non autorisée']);
    }
} catch (InvalidArgumentException $e) {
    mgrRespond(400, ['success' => false, 'message' => $e->getMessage()]);
} catch (Throwable $e) {
    mgrRespond(500, ['success' => false, 'message' => $e->getMessage()]);
}
