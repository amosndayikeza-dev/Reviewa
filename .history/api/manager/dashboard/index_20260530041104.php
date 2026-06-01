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
    $service = new ManagerService();

    // --- FORCER LA RÉCUPÉRATION DU DÉPARTEMENT ---
    $departementId = null;
    $pdo = new PDO('mysql:host=localhost;dbname=1000saveurs;charset=utf8mb4', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Chercher le département de l'utilisateur dans employees
    $stmt = $pdo->prepare("SELECT departement_id FROM employees WHERE user_id = ?");
    $stmt->execute([$user['id']]);
    $emp = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($emp && $emp['departement_id']) {
        $departementId = $emp['departement_id'];
        // Mettre à jour la session
        $_SESSION['user']['departmentId'] = $departementId;
        $_SESSION['user']['departement_id'] = $departementId;
    } else {
        // Aucun département trouvé -> on prend le premier département existant (pour test)
        $stmt = $pdo->query("SELECT id FROM departements LIMIT 1");
        $firstDept = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($firstDept) {
            $departementId = $firstDept['id'];
            // On peut aussi assigner ce département à l'utilisateur automatiquement
            $pdo->prepare("INSERT INTO employees (user_id, departement_id, position, salary, hired_at) VALUES (?, ?, 'Manager', 0, CURDATE()) ON DUPLICATE KEY UPDATE departement_id = ?")
                ->execute([$user['id'], $departementId, $departementId]);
            $_SESSION['user']['departmentId'] = $departementId;
        }
    }

    // Si toujours pas de département, on retourne une erreur claire
    if (!$departementId) {
        mgrRespond(200, ['success' => true, 'ui' => [
            'salesDay' => 0, 'productsCount' => 0, 'pendingDebts' => 0, 'employeesCount' => 0,
            'lowStockCount' => 0, 'lastSaleLabel' => 'Aucun département associé', 'lastSaleDate' => '',
            'stockAlert' => 'Aucun département', 'recoveryMonth' => 0
        ]]);
        exit;
    }

    // Maintenant, on utilise le service avec le département trouvé
    $data = $service->getDashboardSummary($departementId);
    $dept = $service->getDepartementInfo($departementId);
    $recentSale = $data['recentSale'] ?? null;
    $lowStock = $service->getProductsByDepartement($departementId);
    $lowCount = 0;
    if (is_array($lowStock)) {
        foreach ($lowStock as $p) {
            if (ManagerService::stockStatusLabel($p) !== 'ok') $lowCount++;
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