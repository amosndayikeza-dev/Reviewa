<?php
/*
 * API endpoint minimal pour l'authentification
 * Actions supportées (via paramètre `action`):
 *  - login  : POST  -> {email, password}
 *  - logout : POST  -> (invalide la session)
 *  - me     : GET   -> renvoie l'utilisateur courant
 *
 * Ce fichier délègue la logique métier à `services/AuthService.php`.
 */

header('Content-Type: application/json');

include_once __DIR__ . '/../services/AuthService.php';

$auth = new AuthService();
$action = $_GET['action'] ?? null;

/**
 * Réponse JSON helper et arrêt du script.
 *
 * @param int $code HTTP status code
 * @param array $payload Payload JSON serializable
 */
function respond($code, $payload) {
    http_response_code($code);
    header('Content-Type: application/json');
    echo json_encode($payload);
    exit;
}

// Dispatcher principal: utilise switch pour clarté et extensibilité
switch ($action) {
    case 'login':
        // login doit être une requête POST
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            respond(405, ['status' => 'error', 'message' => 'Method not allowed']);
        }

        // Lecture des données JSON ou form-data
        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        $email = isset($input['email']) ? trim($input['email']) : '';
        $password = $input['password'] ?? '';

        if ($email === '' || $password === '') {
            respond(400, ['status' => 'error', 'message' => 'Email and password required']);
        }

        // Appel du service d'authentification
        $user = $auth->login($email, $password);
        if (!$user) {
            respond(401, ['status' => 'error', 'message' => 'Invalid credentials']);
        }

        // Retourne le contexte utilisateur (sans mot de passe)
        respond(200, ['status' => 'success', 'user' => $user]);
        break;

    case 'logout':
        // logout doit être une requête POST
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            respond(405, ['status' => 'error', 'message' => 'Method not allowed']);
        }

        $auth->logout();
        respond(200, ['status' => 'success', 'message' => 'Logged out']);
        break;

    case 'me':
        // récupération de l'utilisateur courant
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            respond(405, ['status' => 'error', 'message' => 'Method not allowed']);
        }

        $user = $auth->getCurrentUser();
        if (!$user) {
            respond(401, ['status' => 'error', 'message' => 'Not authenticated']);
        }
        respond(200, ['status' => 'success', 'user' => $user]);
        break;

    default:
        respond(400, ['status' => 'error', 'message' => 'Invalid action. Use ?action=login|logout|me']);
}
