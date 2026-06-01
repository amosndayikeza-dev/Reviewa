<?php
if (session_status() === PHP_SESSION_NONE) session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
function mgrRespond($code, $data) {
    http_response_code($code);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

if (!isset($_SESSION['user'])) {
    mgrRespond(401, ['success' => false, 'message' => 'Non authentifié']);
}
$user = $_SESSION['user'];
if (!in_array($user['role'] ?? '', ['manager', 'admin'])) {
    mgrRespond(403, ['success' => false, 'message' => 'Accès interdit']);
}

try {
    // Récupération du département du manager
    $departementId = $user['departmentId'] ?? $user['departement_id'] ?? null;
    if (!$departementId && isset($user['id'])) {
        $pdo = new PDO('mysql:host=localhost;dbname=1000saveurs;charset=utf8mb4', 'root', '');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $stmt = $pdo->prepare("SELECT departement_id FROM employees WHERE user_id = ?");
        $stmt->execute([$user['id']]);
        $emp = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($emp && $emp['departement_id']) {
            $departementId = $emp['departement_id'];
            $_SESSION['user']['departmentId'] = $departementId;
            $_SESSION['user']['departement_id'] = $departementId;
        }
    }

    // Inclusion et instanciation du service avec le département
    require_once $_SERVER['DOCUMENT_ROOT'] . '\1000saveursproject\services\ManagerService.php';
    $service = new ManagerService($departementId);

    // Récupération des données via la méthode qui accepte le département
    $data = $service->getDashboardSummary($departementId);
    $dept = $service->getDepartementInfo($departementId);
    $recentSale = $data['recentSale'] ?? null;
    $lowStock = $service->getProductsByDepartement($departementId);
    $lowCount = 0;
    foreach ($lowStock as $p) {
        if (ManagerService::stockStatusLabel($p) !== 'ok') {
            $lowCount++;
        }
    }
    $recovery = $service->getDebtSummary($departementId);

    $ui = [
        'salesDay'      => $data['dailyRevenue'] ?? 0,
        'productsCount' => $data['productsCount'] ?? 0,
        'pendingDebts'  => $data['pendingDebts'] ?? 0,
        'employeesCount'=> $data['employeesCount'] ?? 0,
        'lowStockCount' => $lowCount,
        'lastSaleLabel' => $recentSale
            ? (($recentSale['product_name'] ?? 'Vente') . ' — ' . number_format((float)($recentSale['total_amount'] ?? 0), 0, ',', ' ') . ' FBU')
            : '—',
        'lastSaleDate'  => $recentSale['sold_at'] ?? $recentSale['sale_date'] ?? '—',
        'stockAlert'    => $lowCount > 0 ? "$lowCount produit(s) en alerte" : 'Aucune alerte',
        'recoveryMonth' => (float)($recovery['recoveryThisMonth'] ?? 0),
    ];

    mgrRespond(200, [
        'success' => true,
        'departement' => $dept,
        'data' => $data,
        'ui' => $ui,
    ]);

} catch (Throwable $e) {
    mgrRespond(500, ['success' => false, 'message' => $e->getMessage()]);
}
?>