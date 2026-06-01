<?php
if (session_status() === PHP_SESSION_NONE) session_start();

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
    require_once $_SERVER['DOCUMENT_ROOT'] . '/1000saveursproject/services/ManagerService.php';

    // 🔥 Récupération FORCÉE du département depuis la table employees
    $pdo = new PDO('mysql:host=localhost;dbname=1000saveurs;charset=utf8mb4', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $stmt = $pdo->prepare("SELECT departement_id FROM employees WHERE user_id = ?");
    $stmt->execute([$user['id']]);
    $emp = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$emp || !$emp['departement_id']) {
        mgrRespond(200, [
            'success' => false,
            'message' => 'Aucun département associé à ce manager',
            'ui' => [
                'salesDay' => 0, 'productsCount' => 0, 'pendingDebts' => 0,
                'employeesCount' => 0, 'lowStockCount' => 0,
                'lastSaleLabel' => 'Département manquant', 'lastSaleDate' => '',
                'stockAlert' => 'Aucun département', 'recoveryMonth' => 0
            ]
        ]);
        exit;
    }

    $departementId = (int)$emp['departement_id'];
    $_SESSION['user']['departmentId'] = $departementId;
    $_SESSION['user']['departement_id'] = $departementId;

    $service = new ManagerService($departementId);
    $data = $service->getDashboardSummary($departementId);
    $dept = $service->getDepartementInfo($departementId);
    $recentSale = $data['recentSale'] ?? null;
    $lowStock = $service->getProductsByDepartement($departementId);
    $lowCount = 0;
    foreach ($lowStock as $p) {
        if (ManagerService::stockStatusLabel($p) !== 'ok') $lowCount++;
    }
    $recovery = $service->getDebtSummary($departementId);

    mgrRespond(200, [
        'success' => true,
        'departement' => $dept,
        'data' => $data,
        'ui' => [
            'salesDay' => (float)($data['dailyRevenue'] ?? 0),
            'productsCount' => (int)($data['productsCount'] ?? 0),
            'pendingDebts' => (float)($data['pendingDebts'] ?? 0),
            'employeesCount' => (int)($data['employeesCount'] ?? 0),
            'lowStockCount' => (int)$lowCount,
            'lastSaleLabel' => $recentSale
                ? (($recentSale['product_name'] ?? 'Vente') . ' — ' . number_format((float)($recentSale['total_amount'] ?? 0), 0, ',', ' ') . ' FBU')
                : '—',
            'lastSaleDate' => $recentSale['sold_at'] ?? $recentSale['sale_date'] ?? '—',
            'stockAlert' => $lowCount > 0 ? "$lowCount produit(s) en alerte" : 'Aucune alerte',
            'recoveryMonth' => (float)($recovery['recoveryThisMonth'] ?? 0),
        ],
    ]);
} catch (Throwable $e) {
    mgrRespond(500, ['success' => false, 'message' => $e->getMessage()]);
}