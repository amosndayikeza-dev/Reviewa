<?php
require_once __DIR__ . '/../bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    mgrRespond(405, ['success' => false, 'message' => 'Method not allowed']);
}

try {
    // 1. Vérifier l'authentification
    if (!isset($_SESSION['user'])) {
        mgrRespond(401, ['success' => false, 'message' => 'Non authentifié']);
    }
    $user = $_SESSION['user'];
    $role = $user['role'] ?? '';
    if ($role !== 'manager' && $role !== 'admin') {
        mgrRespond(403, ['success' => false, 'message' => 'Accès interdit']);
    }

    // 2. Récupérer le département depuis la session ou la base
    $departementId = $user['departmentId'] ?? $user['departement_id'] ?? null;
    if (!$departementId && isset($user['id'])) {
        $pdo = new PDO('mysql:host=localhost;dbname=1000saveurs;charset=utf8mb4', 'root', '');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $stmt = $pdo->prepare("SELECT departement_id FROM employees WHERE user_id = ?");
        $stmt->execute([$user['id']]);
        $emp = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($emp && $emp['departement_id']) {
            $departementId = $emp['departement_id'];
            // Mettre à jour la session pour les prochains appels
            $_SESSION['user']['departmentId'] = $departementId;
            $_SESSION['user']['departement_id'] = $departementId;
        }
    }

    // 3. Appel au service Manager (existant)
    //    On passe le $departementId (peut être null)
    $ctx = mgrAuthContext(); // cette fonction peut déjà s'occuper de l'auth mais on a déjà vérifié
    // Attention: mgrAuthContext() utilise peut-être aussi le département. On va plutôt utiliser notre propre contexte.
    // Pour éviter les doublons, on utilise le service directement.
    require_once __DIR__ . '/../services/ManagerService.php';
    $service = new ManagerService();

    // Récupération des données (en utilisant le département trouvé)
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

    // 4. Construction de la réponse UI (identique à l'original)
    $ui = [
        'salesDay'      => $data['dailyRevenue'] ?? 0,
        'productsCount' => $data['productsCount'] ?? 0,
        'pendingDebts'  => $data['pendingDebts'] ?? 0,
        'employeesCount'=> $data['employeesCount'] ?? 0,
        'lowStockCount' => $lowCount,
        'lastSaleLabel' => $recentSale
            ? (($recentSale['product_name'] ?? 'Vente') . ' — ' . number_format((float)($recentSale['total_amount'] ?? 0), 0, ',', ' ') . ' FBU')
            : '—',
        'lastSaleDate'  => $recentSale['sold_at'] ?? '—',
        'stockAlert'    => $lowCount > 0 ? ($lowCount . ' produit(s) en alerte') : 'Aucune alerte',
        'recoveryMonth' => ($service->getDebtSummary($departementId)['recoveryThisMonth'] ?? 0),
    ];

    mgrRespond(200, [
        'success'     => true,
        'departement' => $dept,
        'data'        => $data,
        'ui'          => $ui,
    ]);

} catch (Throwable $e) {
    mgrRespond(500, ['success' => false, 'message' => $e->getMessage()]);
}
?>