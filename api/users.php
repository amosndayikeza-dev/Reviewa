<?php
/*
 * Endpoint pour la gestion des utilisateurs.
 *
 * Actions supportées :
 *  - list   : GET
 *  - get    : GET
 *  - create : POST
 *  - update : PUT/PATCH
 *  - delete : DELETE
 *  - activate / deactivate : POST
 */

header('Content-Type: application/json');

include_once __DIR__ . '/../services/AuthService.php';
include_once __DIR__ . '/../services/UserService.php';


$auth = new AuthService();
$userService = new UserService();

function respond($code, $payload) {
    http_response_code($code);
    header('Content-Type: application/json');
    echo json_encode($payload);
    exit;
}

$auth->requireAuth();
$action = $_GET['action'] ?? 'list';
$id = isset($_GET['id']) ? (int)$_GET['id'] : null;

switch ($action) {
    case 'list':
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            respond(405, ['status' => 'error', 'message' => 'Method not allowed']);
        }

        $active = isset($_GET['active']) ? ($_GET['active'] === '1' || strtolower($_GET['active']) === 'true') : null;

        if ($auth->isManager()) {
            $departmentId = $auth->getUserDepartementId();
            $users = $userService->getUsersWithEmployeeInfo($departmentId);
            respond(200, ['status' => 'success', 'data' => $users]);
        }

        $role = $_GET['role'] ?? null;
        $users = $role ? $userService->getUsersByRole($role, $active) : $userService->getAllUsers($active);
        respond(200, ['status' => 'success', 'data' => $users]);
        break;

    case 'get':
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            respond(405, ['status' => 'error', 'message' => 'Method not allowed']);
        }
        if (!$id) {
            respond(400, ['status' => 'error', 'message' => 'User id required']);
        }
        $user = $userService->getUserById($id);
        if (!$user) {
            respond(404, ['status' => 'error', 'message' => 'User not found']);
        }
        respond(200, ['status' => 'success', 'user' => $user]);
        break;

    case 'create':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            respond(405, ['status' => 'error', 'message' => 'Method not allowed']);
        }
        if (!$auth->isAdmin()) {
            respond(403, ['status' => 'error', 'message' => 'Forbidden']);
        }

        $data = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        if (empty($data['email']) || empty($data['password'])) {
            respond(400, ['status' => 'error', 'message' => 'Email and password are required']);
        }

        $id = $userService->createUser($data);
        respond(201, ['status' => 'success', 'message' => 'User created', 'id' => $id]);
        break;

    case 'update':
        if (!in_array($_SERVER['REQUEST_METHOD'], ['PUT', 'PATCH'], true)) {
            respond(405, ['status' => 'error', 'message' => 'Method not allowed']);
        }
        if (!$id) {
            respond(400, ['status' => 'error', 'message' => 'User id required']);
        }
        if (!$auth->isAdmin()) {
            respond(403, ['status' => 'error', 'message' => 'Forbidden']);
        }

        $data = json_decode(file_get_contents('php://input'), true) ?? [];
        $rows = $userService->updateUser($id, $data);
        respond(200, ['status' => 'success', 'message' => 'User updated', 'rows' => $rows]);
        break;

    case 'delete':
        if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
            respond(405, ['status' => 'error', 'message' => 'Method not allowed']);
        }
        if (!$id) {
            respond(400, ['status' => 'error', 'message' => 'User id required']);
        }
        if (!$auth->isAdmin()) {
            respond(403, ['status' => 'error', 'message' => 'Forbidden']);
        }
        $rows = $userService->deleteUser($id);
        respond(200, ['status' => 'success', 'message' => 'User deleted', 'rows' => $rows]);
        break;

    case 'activate':
    case 'deactivate':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            respond(405, ['status' => 'error', 'message' => 'Method not allowed']);
        }
        if (!$id) {
            respond(400, ['status' => 'error', 'message' => 'User id required']);
        }
        if (!$auth->isAdmin()) {
            respond(403, ['status' => 'error', 'message' => 'Forbidden']);
        }
        $active = ($action === 'activate');
        $rows = $userService->setActive($id, $active);
        respond(200, ['status' => 'success', 'message' => 'User status updated', 'rows' => $rows]);
        break;

    default:
        respond(400, ['status' => 'error', 'message' => 'Invalid action']);
}
