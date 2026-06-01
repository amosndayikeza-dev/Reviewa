<?php

header('Content-Type: application/json; charset=utf-8');

include_once __DIR__ . '/../../services/AuthService.php';
include_once __DIR__ . '/../../services/ManagerService.php';

function mgrRespond(int $code, $payload): void {
    http_response_code($code);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function mgrReadJson(): array {
    $raw = file_get_contents('php://input');
    if ($raw === false || trim($raw) === '') {
        return $_POST ?: [];
    }
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

function mgrPathId(): ?int {
    if (isset($_GET['id']) && $_GET['id'] !== '') {
        $fromQuery = (int)$_GET['id'];
        if ($fromQuery > 0) {
            return $fromQuery;
        }
    }
    $pathInfo = $_SERVER['PATH_INFO'] ?? '';
    if ($pathInfo === '' && isset($_SERVER['REQUEST_URI'], $_SERVER['SCRIPT_NAME'])) {
        $requestUri = strtok($_SERVER['REQUEST_URI'], '?');
        $script = $_SERVER['SCRIPT_NAME'];
        if (strpos($requestUri, $script) === 0) {
            $pathInfo = substr($requestUri, strlen($script));
        }
    }
    $path = trim($pathInfo, '/');
    if ($path === '') {
        return null;
    }
    $id = (int)$path;
    return $id > 0 ? $id : null;
}

function mgrAuthContext(): array {
    $auth = new AuthService();
    $auth->requireManager();
    return [
        'auth' => $auth,
        'service' => new ManagerService(),
        'departementId' => (int)$auth->getUserDepartementId(),
        'userId' => (int)$auth->getUserId(),
    ];
}
