<?php
spl_autoload_register(function ($class) {
    $map = [
        'DepartementDAO' => __DIR__ . '/../daophp/DepartementDAO.php',
        'EmployeeDAO' => __DIR__ . '/../daophp/EmployeeDAO.php',
        'ProductsDAO' => __DIR__ . '/../daophp/ProductsDAO.php',
        'SalesDAO' => __DIR__ . '/../daophp/SalesDAO.php',
        'DebtsDAO' => __DIR__ . '/../daophp/DebtsDAO.php',
        'Salary_reportsDAO' => __DIR__ . '/../daophp/Salary_reportsDAO.php',
        'Stock_movementDAO' => __DIR__ . '/../daophp/Stock_movementDAO.php',
        'Sale_itemsDAO' => __DIR__ . '/../daophp/Sale_itemsDAO.php',
        'UserDAO' => __DIR__ . '/../daophp/UserDAO.php',
        'Database' => __DIR__ . '/../daophp/Database.php',
        'ReportService' => __DIR__ . '/ReportService.php',
    ];
    if (isset($map[$class])) {
        require_once $map[$class];
    }
});

class ManagerService {
    protected $departementDao;
    protected $employeeDao;
    protected $productsDao;
    protected $salesDao;
    protected $debtsDao;
    protected $salaryReportDao;
    protected $stockMovementDao;
    protected $saleItemsDao;
    protected $userDao;
    protected $reportService;
    protected  $db;

    // nouvelle propriété pour stocker le département courant
    protected $currentDepartementId;

    public function __construct($departementId = null) {
        $this->currentDepartementId = $departementId;
        $this->departementDao = new DepartementDAO();
        $this->employeeDao = new EmployeeDAO();
        $this->productsDao = new ProductsDAO();
        $this->salesDao = new SalesDAO();
        $this->debtsDao = new DebtsDAO();
        $this->salaryReportDao = new Salary_reportsDAO();
        $this->stockMovementDao = new Stock_movementDAO();
        $this->saleItemsDao = new Sale_itemsDAO();
        $this->userDao = new UserDAO();
        $this->reportService = new ReportService();
        $this->db = Database::getInstance();
    }

    public function getDepartementInfo($departementId) {
        return $this->departementDao->findById($departementId);
    }

    protected function assertProductInDepartement($productId, $departementId) {
        $product = $this->productsDao->findById($productId);
        if (!$product || (int)$product['departement_id'] !== (int)$departementId) {
            throw new InvalidArgumentException('Produit introuvable dans ce département');
        }
        return $product;
    }

    // Méthode dashboard améliorée : utilise le département stocké si aucun argument n’est passé
    public function getDashboardSummary($departementId = null) {
        $deptId = $departementId ?? $this->currentDepartementId;

        if ($deptId === null) {
            // Vue globale (pas de département) – attention, peut retourner des totaux
            $employeesCount = $this->employeeDao->count();
            $reportsCount = $this->salaryReportDao->count();
            $pendingDebts = $this->debtsDao->getSummary(null)['totalOutstanding'] ?? 0;
            $dailyRevenue = $this->salesDao->calculateRevenue(null, date('Y-m-d') . ' 00:00:00', date('Y-m-d') . ' 23:59:59')['totalRevenue'] ?? 0;
            $productsCount = $this->productsDao->count();
            $lowStock = count($this->productsDao->findLowStock(null));

            return [
                'departementsCount' => $this->departementDao->count(),
                'employeesCount' => $employeesCount,
                'reportsCount' => $reportsCount,
                'pendingDebts' => (float)$pendingDebts,
                'dailyRevenue' => (float)$dailyRevenue,
                'productsCount' => $productsCount,
                'lowStockProductsCount' => $lowStock,
                'recentEmployee' => $this->getRecentEmployee(),
                'recentReport' => $this->getRecentReport(),
                'recentSale' => $this->getRecentSale(),
            ];
        }

        // Vue filtrée par département
        $overview = $this->departementDao->getDepartementOverview($deptId);
        if (!$overview) {
            return [
                'departementsCount' => 0,
                'employeesCount' => 0,
                'reportsCount' => 0,
                'pendingDebts' => 0,
                'dailyRevenue' => 0,
                'productsCount' => 0,
                'lowStockProductsCount' => 0,
                'recentEmployee' => null,
                'recentReport' => null,
                'recentSale' => null,
            ];
        }

        return [
            'departementsCount' => 1,
            'employeesCount' => $overview['employeesCount'],
            'reportsCount' => $overview['salaryReportsCount'],
            'pendingDebts' => $overview['debtSummary']['totalOutstanding'] ?? 0,
            'dailyRevenue' => $overview['totalRevenue'],
            'productsCount' => $overview['productsCount'],
            'lowStockProductsCount' => $overview['lowStockProductsCount'],
            'recentEmployee' => $this->getRecentEmployee($deptId),
            'recentReport' => $this->getRecentReport($deptId),
            'recentSale' => $this->getRecentSale($deptId),
        ];
    }

    public function getProductsByDepartement($departementId = null) {
        return $this->productsDao->findByDepartement($departementId);
    }

    public function getProduct($id) {
        return $this->productsDao->findById($id);
    }

    public function createProduct(array $data) {
        $product = [
            'name' => trim($data['name'] ?? ''),
            'description' => trim($data['description'] ?? ''),
            'unit_price' => isset($data['unitPrice']) ? floatval($data['unitPrice']) : floatval($data['unit_price'] ?? 0),
            'current_stock' => isset($data['currentStock']) ? intval($data['currentStock']) : intval($data['current_stock'] ?? 0),
            'low_stock_threshold' => isset($data['lowStockThreshold']) ? intval($data['lowStockThreshold']) : intval($data['low_stock_threshold'] ?? 5),
            'departement_id' => isset($data['departementId']) ? intval($data['departementId']) : (isset($data['departement_id']) ? intval($data['departement_id']) : null),
        ];

        if ($product['name'] === '' || $product['departement_id'] === null) {
            throw new InvalidArgumentException('Les champs name et departementId sont requis');
        }

        $id = $this->productsDao->create($product);
        if ($product['current_stock'] > 0) {
            $this->stockMovementDao->create([
                'product_id' => (int)$id,
                'type' => 'IN',
                'quantity' => $product['current_stock'],
                'reason' => 'Stock initial',
                'created_by' => $data['createdBy'] ?? null,
            ]);
        }
        return $id;
    }

    public function updateProduct($id, array $data) {
        $updates = [];
        if (isset($data['name'])) {
            $updates['name'] = trim($data['name']);
        }
        if (isset($data['description'])) {
            $updates['description'] = trim($data['description']);
        }
        if (isset($data['unitPrice'])) {
            $updates['unit_price'] = floatval($data['unitPrice']);
        }
        if (isset($data['currentStock'])) {
            $updates['current_stock'] = intval($data['currentStock']);
        }
        if (isset($data['lowStockThreshold'])) {
            $updates['low_stock_threshold'] = intval($data['lowStockThreshold']);
        }
        if (isset($data['departementId'])) {
            $updates['departement_id'] = intval($data['departementId']);
        }

        if (empty($updates)) {
            throw new InvalidArgumentException('Aucune donnée valide fournie pour la mise à jour');
        }

        return $this->productsDao->update($id, $updates);
    }

    public function deleteProduct($id) {
        return $this->productsDao->delete($id);
    }

    public function getDepartements($managerDepartementId = null) {
        if ($managerDepartementId !== null) {
            $departement = $this->departementDao->findByIdWithManager($managerDepartementId);
            return $departement ? [$departement] : [];
        }
        return $this->departementDao->findAllWithManager('name ASC');
    }

    public function getEmployeesByDepartement($departementId) {
        return $this->employeeDao->getEmployeesWithUser($departementId);
    }

    public function getDebtList($departementId = null, $status = null) {
        return $this->debtsDao->findAllWithDetails($departementId, null, null, $status);
    }

    public function recordDebtPayment($debtId, $paidAmount, $departementId = null) {
        $debt = $this->debtsDao->findById($debtId);
        if (!$debt) {
            throw new InvalidArgumentException('Dette introuvable');
        }
        if ($departementId !== null) {
            $row = $this->db->fetchOne(
                "SELECT d.id FROM debts d
                 INNER JOIN sale_items si ON d.sale_item_id = si.id
                 INNER JOIN sales s ON si.sale_id = s.id
                 WHERE d.id = ? AND s.departement_id = ?",
                [$debtId, $departementId]
            );
            if (!$row) {
                throw new InvalidArgumentException('Dette hors de votre département');
            }
        }

        $paidAmount = floatval($paidAmount);
        if ($paidAmount <= 0) {
            throw new InvalidArgumentException('Le montant payé doit être positif');
        }

        $newPaid = min($debt['amount'], ($debt['paid_amount'] ?? 0) + $paidAmount);
        $status = $newPaid >= $debt['amount'] ? 'paid' : 'partial';

        $this->debtsDao->updatePaidAmount($debtId, $newPaid, $status);
        return $this->debtsDao->findById($debtId);
    }

    public function getRecentEmployee($departementId = null) {
        $sql = "SELECT e.*, u.first_name, u.last_name
                FROM employees e
                LEFT JOIN users u ON e.user_id = u.id
                WHERE 1=1";
        $params = [];
        if ($departementId !== null) {
            $sql .= " AND e.departement_id = ?";
            $params[] = $departementId;
        }
        $sql .= " ORDER BY e.id DESC LIMIT 1";
        return $this->db->fetchOne($sql, $params);
    }

    public function getRecentReport($departementId = null) {
        $sql = "SELECT sr.* FROM salary_reports sr WHERE 1=1";
        $params = [];
        if ($departementId !== null) {
            $sql .= " AND sr.departement_id = ?";
            $params[] = $departementId;
        }
        $sql .= " ORDER BY sr.id DESC LIMIT 1";
        return $this->db->fetchOne($sql, $params);
    }

    public function getRecentSale($departementId = null) {
    $sql = "SELECT s.*, p.name AS product_name, si.quantity, si.unit_price
            FROM sales s
            LEFT JOIN sale_items si ON si.sale_id = s.id
            LEFT JOIN products p ON si.product_id = p.id
            WHERE 1=1";
    $params = [];
    if ($departementId !== null) {
        $sql .= " AND s.departement_id = ?";
        $params[] = $departementId;
    }
    $sql .= " ORDER BY s.sold_at DESC LIMIT 1";
    return $this->db->fetchOne($sql, $params);
}

    public function getSalesSummaryForPeriod($departementId, $startDate = null, $endDate = null) {
        return $this->reportService->getSalesSummary(
            $departementId,
            $startDate ? $startDate . ' 00:00:00' : null,
            $endDate ? $endDate . ' 23:59:59' : null
        );
    }

    public function getSaleLinesHistory($departementId, $startDate = null, $endDate = null) {
    $sql = "SELECT s.id AS sale_id, s.sold_at, p.name AS product_name, si.quantity, si.unit_price,
                   (si.quantity * si.unit_price) AS line_total
            FROM sales s
            INNER JOIN sale_items si ON si.sale_id = s.id
            INNER JOIN products p ON si.product_id = p.id
            WHERE s.departement_id = ?";
    $params = [$departementId];
    if ($startDate) {
        $sql .= " AND s.sold_at >= ?";
        $params[] = $startDate . ' 00:00:00';
    }
    if ($endDate) {
        $sql .= " AND s.sold_at <= ?";
        $params[] = $endDate . ' 23:59:59';
    }
    $sql .= " ORDER BY s.sold_at DESC, s.id DESC";
    return $this->db->fetchAll($sql, $params);
}
    
    public function createSale($departementId, $userId, array $data) {
    $productId = (int)($data['productId'] ?? $data['product_id'] ?? 0);
    $quantity = (int)($data['quantity'] ?? 0);
    $unitPrice = isset($data['unitPrice']) ? floatval($data['unitPrice']) : floatval($data['unit_price'] ?? 0);
    $soldAt = trim($data['soldAt'] ?? $data['sold_at'] ?? $data['date'] ?? date('Y-m-d H:i:s'));

    if ($productId <= 0 || $quantity <= 0) {
        throw new InvalidArgumentException('Produit et quantité requis');
    }

    $product = $this->assertProductInDepartement($productId, $departementId);
    if ($unitPrice <= 0) {
        $unitPrice = (float)$product['unit_price'];
    }
    if ((int)$product['current_stock'] < $quantity) {
        throw new InvalidArgumentException('Stock insuffisant pour ce produit');
    }

    $totalAmount = $quantity * $unitPrice;
    if (strpos($soldAt, ' ') === false) {
        $soldAt .= ' ' . date('H:i:s');
    }

    $this->db->beginTransaction();
    try {
        $saleId = $this->salesDao->create([
            'departement_id' => $departementId,
            'total_amount' => $totalAmount,
            'sold_at' => $soldAt,
            'created_by' => $userId,
        ]);

        // Correction : ne pas utiliser product_name
        $this->saleItemsDao->create([
            'sale_id' => (int)$saleId,
            'product_id' => $productId,
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'is_paid' => 1,
        ]);

        $this->productsDao->adjustStock($productId, -$quantity);
        $this->stockMovementDao->create([
            'product_id' => $productId,
            'type' => 'OUT',
            'quantity' => $quantity,
            'reason' => 'Vente #' . $saleId,
            'reference_id' => (int)$saleId,
            'created_by' => $userId,
        ]);

        $this->db->commit();
        return $this->salesDao->findWithDetails((int)$saleId);
    } catch (Throwable $e) {
        $this->db->rollback();
        throw $e;
    }
}

    public function getStockMovementsByDepartement($departementId, $type = null, $startDate = null, $endDate = null) {
        $sql = "SELECT sm.*, p.name AS product_name
                FROM stock_movements sm
                INNER JOIN products p ON sm.product_id = p.id
                WHERE p.departement_id = ?";
        $params = [$departementId];
        if ($type !== null) {
            $sql .= " AND sm.type = ?";
            $params[] = $type;
        }
        if ($startDate !== null) {
            $sql .= " AND sm.created_at >= ?";
            $params[] = $startDate . ' 00:00:00';
        }
        if ($endDate !== null) {
            $sql .= " AND sm.created_at <= ?";
            $params[] = $endDate . ' 23:59:59';
        }
        $sql .= " ORDER BY sm.id DESC";
        return $this->db->fetchAll($sql, $params);
    }

    public function adjustStock($departementId, $userId, array $data) {
        $productId = (int)($data['productId'] ?? $data['product_id'] ?? 0);
        $quantity = (int)($data['quantity'] ?? 0);
        $movementType = strtolower(trim($data['type'] ?? $data['movementType'] ?? ''));
        $reason = trim($data['reason'] ?? $data['motif'] ?? '');

        if ($productId <= 0 || $quantity <= 0) {
            throw new InvalidArgumentException('Produit et quantité requis');
        }
        if (!in_array($movementType, ['in', 'out'], true)) {
            throw new InvalidArgumentException('Type de mouvement invalide (in ou out)');
        }

        $product = $this->assertProductInDepartement($productId, $departementId);
        $dbType = $movementType === 'in' ? 'IN' : 'OUT';
        $delta = $movementType === 'in' ? $quantity : -$quantity;

        if ($movementType === 'out' && (int)$product['current_stock'] < $quantity) {
            throw new InvalidArgumentException('Stock insuffisant');
        }

        $this->db->beginTransaction();
        try {
            $this->productsDao->adjustStock($productId, $delta);
            $movementId = $this->stockMovementDao->create([
                'product_id' => $productId,
                'type' => $dbType,
                'quantity' => $quantity,
                'reason' => $reason !== '' ? $reason : ($dbType === 'IN' ? 'Entrée manuelle' : 'Sortie manuelle'),
                'created_by' => $userId,
            ]);
            $this->db->commit();
            return ['movementId' => $movementId, 'product' => $this->productsDao->findById($productId)];
        } catch (Throwable $e) {
            $this->db->rollback();
            throw $e;
        }
    }

    public function getDebtSummary($departementId) {
        $summary = $this->debtsDao->getSummaryFiltered($departementId, null, null, null);
        $monthStart = date('Y-m-01');
        $monthEnd = date('Y-m-t');
        $recovery = $this->debtsDao->getSummaryFiltered($departementId, $monthStart, $monthEnd, null);
        return [
            'totalOutstanding' => (float)($summary['totalOutstanding'] ?? 0),
            'totalPaid' => (float)($summary['totalPaid'] ?? 0),
            'totalAmount' => (float)($summary['totalAmount'] ?? 0),
            'totalDebts' => (int)($summary['totalDebts'] ?? 0),
            'recoveryThisMonth' => (float)($recovery['totalPaid'] ?? 0),
        ];
    }

    public function createEmployee($departementId, array $data) {
        $firstName = trim($data['first_name'] ?? $data['firstName'] ?? $data['prenom'] ?? '');
        $lastName = trim($data['last_name'] ?? $data['lastName'] ?? $data['nom'] ?? '');
        $email = trim($data['email'] ?? '');
        $position = trim($data['position'] ?? $data['poste'] ?? 'employe');
        $salary = (float)($data['salary'] ?? 0);
        $hiredAt = trim($data['hired_at'] ?? $data['hiredAt'] ?? date('Y-m-d'));

        if ($firstName === '' || $lastName === '' || $email === '') {
            throw new InvalidArgumentException('Nom, prénom et email requis');
        }

        $userId = $this->userDao->create([
            'first_name' => $firstName,
            'last_name' => $lastName,
            'email' => $email,
            'password' => password_hash('12345678', PASSWORD_DEFAULT),
            'role' => 'employe',
            'is_active' => 1,
        ]);

        $employeeId = $this->employeeDao->create([
            'user_id' => (int)$userId,
            'departement_id' => $departementId,
            'position' => $position,
            'salary' => $salary,
            'hired_at' => $hiredAt,
        ]);

        return $this->employeeDao->findByIdWithUser($employeeId);
    }

    public function deleteEmployee($employeeId, $departementId) {
        $employee = $this->employeeDao->findById($employeeId);
        if (!$employee || (int)$employee['departement_id'] !== (int)$departementId) {
            throw new InvalidArgumentException('Employé introuvable');
        }
        return $this->employeeDao->delete($employeeId);
    }

    public function getSalaryReports($departementId, $year = null, $month = null) {
        return $this->reportService->getSalaryReport($departementId, $year, $month);
    }

    public static function stockStatusLabel(array $product): string {
        $stock = (int)($product['current_stock'] ?? 0);
        $threshold = (int)($product['low_stock_threshold'] ?? 5);
        if ($stock <= 0) {
            return 'rupture';
        }
        if ($stock <= $threshold) {
            return 'low';
        }
        return 'ok';
    }
}

