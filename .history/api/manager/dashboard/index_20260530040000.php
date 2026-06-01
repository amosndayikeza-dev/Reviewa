<?php
// Démarrer la session AVANT toute sortie
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Fonction de réponse
function mgrRespond($code, $data) {
    http_response_code($code);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

// Vérifier l'authentification
if (!isset($_SESSION['user'])) {
    mgrRespond(401, ['success' => false, 'message' => 'Non authentifié - session vide']);
}
$user = $_SESSION['user'];
if (!in_array($user['role'] ?? '', ['manager', 'admin'])) {
    mgrRespond(403, ['success' => false, 'message' => 'Accès interdit, rôle: ' . ($user['role'] ?? 'aucun')]);
}

try {
    require_once __DIR__ . '/../services/ManagerService.php';
    $service = new ManagerService();

    // Récupérer le département
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

    // Récupérer les données du dashboard
    $data = $service->getDashboardSummary($departementId);
    $dept = $service->getDepartementInfo($departementId);

    $recentSale = $data['recentSale'] ?? null;
    $lowStock = $service->getProductsByDepartement($departementId);
    $lowCount = 0;
    if (is_array($lowStock)) {
        foreach ($lowStock as $p) {
            if (ManagerService::stockStatusLabel($p) !== 'ok') {
                $lowCount++;
            }
        }
    }

    $recovery = $service->getDebtSummary($departementId);

    mgrRespond(200, [
        'success' => true,
        'departement' => $dept,
        'data' => $data,
        'ui' => [
            'salesDay' => $data['dailyRevenue'] ?? 0,
            'productsCount' => $data['productsCount'] ?? 0,
            'pendingDebts' => $data['pendingDebts'] ?? 0,
            'employeesCount' => $data['employeesCount'] ?? 0,
            'lowStockCount' => $lowCount,
            'lastSaleLabel' => $recentSale ? (($recentSale['product_name'] ?? 'Vente') . ' — ' . number_format((float)($recentSale['total_amount'] ?? 0), 0, ',', ' ') . ' FBU') : '—',
            'lastSaleDate' => $recentSale['sold_at'] ?? '—',
            'stockAlert' => $lowCount > 0 ? ($lowCount . ' produit(s) en alerte') : 'Aucune alerte',
            'recoveryMonth' => (float)($recovery['recoveryThisMonth'] ?? 0),
        ],
    ]);

} catch (Throwable $e) {
    mgrRespond(500, ['success' => false, 'message' => $e->getMessage()]);
}
?>