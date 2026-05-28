<?php

include_once 
class DepartementDAO extends DAO{

    protected $table = 'departements';
    protected $primaryKey = 'id';

    // Méthodes spécifiques à l'entité Debts peuvent être ajoutées ici

    /**
     * Retourne un département avec les informations de son manager.
     *
     * @param int $id
     * @return array|null
     */
    public function findByIdWithManager($id) {
        $sql = "SELECT d.*, u.first_name AS managerFirstName, u.last_name AS managerLastName, u.email AS managerEmail
                FROM {$this->table} d
                LEFT JOIN users u ON d.managerId = u.id
                WHERE d.{$this->primaryKey} = ?";
        return $this->db->fetchOne($sql, [$id]);
    }

    /**
     * Retourne tous les départements avec les informations de leurs managers.
     *
     * @param string|null $orderBy
     * @return array
     */
    public function findAllWithManager($orderBy = null) {
        $sql = "SELECT d.*, u.first_name AS managerFirstName, u.last_name AS managerLastName, u.email AS managerEmail
                FROM {$this->table} d
                LEFT JOIN users u ON d.managerId = u.id";
        if ($orderBy) {
            $sql .= " ORDER BY {$orderBy}";
        }
        return $this->db->fetchAll($sql);
    }

    /**
     * Retourne les produits d'un département.
     *
     * @param int $departementId
     * @param bool $onlyLowStock
     * @return array
     */
    public function getProducts($departementId, $onlyLowStock = false) {
        $sql = "SELECT * FROM products WHERE departement_id = ?";
        $params = [$departementId];

        if ($onlyLowStock) {
            $sql .= " AND current_stock <= low_stock_threshold";
        }

        return $this->db->fetchAll($sql, $params);
    }

    /**
     * Retourne les produits en rupture ou en seuil bas d'un département.
     *
     * @param int $departementId
     * @return array
     */
    public function getLowStockProducts($departementId) {
        return $this->getProducts($departementId, true);
    }

    /**
     * Retourne les employés rattachés à un département.
     *
     * @param int $departementId
     * @param string|null $orderBy
     * @return array
     */
    public function getEmployees($departementId, $orderBy = null) {
        $sql = "SELECT e.*, u.first_name, u.last_name, u.email
                FROM employees e
                LEFT JOIN users u ON e.userId = u.id
                WHERE e.departement_id = ?";
        if ($orderBy) {
            $sql .= " ORDER BY {$orderBy}";
        }
        return $this->db->fetchAll($sql, [$departementId]);
    }

    /**
     * Retourne les ventes d'un département avec filtre de date facultatif.
     *
     * @param int $departementId
     * @param string|null $startDate
     * @param string|null $endDate
     * @return array
     */
    public function getSales($departementId, $startDate = null, $endDate = null) {
        $sql = "SELECT * FROM sales WHERE departement_id = ?";
        $params = [$departementId];
        $this->buildDateRangeClause($sql, $params, 'soldAt', $startDate, $endDate);
        return $this->db->fetchAll($sql, $params);
    }

    /**
     * Retourne les lignes de vente d'un département.
     *
     * @param int $departementId
     * @param string|null $startDate
     * @param string|null $endDate
     * @return array
     */
    public function getSaleItems($departementId, $startDate = null, $endDate = null) {
        $sql = "SELECT si.*
                FROM sale_items si
                JOIN sales s ON si.sale_id = s.id
                WHERE s.departement_id = ?";
        $params = [$departementId];
        $this->buildDateRangeClause($sql, $params, 'si.created_at', $startDate, $endDate, 'AND');
        return $this->db->fetchAll($sql, $params);
    }

    /**
     * Retourne les mouvements de stock pour un département.
     *
     * @param int $departementId
     * @param int|null $productId
     * @param string|null $type
     * @param string|null $startDate
     * @param string|null $endDate
     * @return array
     */
    public function getStockMovements($departementId, $productId = null, $type = null, $startDate = null, $endDate = null) {
        $sql = "SELECT sm.*
                FROM stock_movements sm
                JOIN products p ON sm.product_id = p.id
                WHERE p.departement_id = ?";
        $params = [$departementId];

        if ($productId !== null) {
            $sql .= " AND sm.product_id = ?";
            $params[] = $productId;
        }
        if ($type !== null) {
            $sql .= " AND sm.type = ?";
            $params[] = $type;
        }

        $this->buildDateRangeClause($sql, $params, 'sm.createdAt', $startDate, $endDate, 'AND');
        return $this->db->fetchAll($sql, $params);
    }

    /**
     * Retourne les rapports de salaire pour un département.
     *
     * @param int $departementId
     * @param int|null $year
     * @param int|null $month
     * @return array
     */
    public function getSalaryReports($departementId, $year = null, $month = null) {
        $sql = "SELECT * FROM salary_reports WHERE departement_id = ?";
        $params = [$departementId];

        if ($year !== null) {
            $sql .= " AND year = ?";
            $params[] = $year;
        }
        if ($month !== null) {
            $sql .= " AND month = ?";
            $params[] = $month;
        }

        return $this->db->fetchAll($sql, $params);
    }

    /**
     * Retourne un résumé des dettes liées au département.
     *
     * @param int $departementId
     * @return array|null
     */
    public function getDebtSummary($departementId) {
        $sql = "SELECT COUNT(d.id) AS totalDebts,
                       COALESCE(SUM(d.amount), 0) AS totalAmount,
                       COALESCE(SUM(d.paid_amount), 0) AS totalPaid,
                       COALESCE(SUM(d.amount - d.paid_amount), 0) AS totalOutstanding
                FROM debts d
                JOIN sale_items si ON d.sale_item_id = si.id
                JOIN sales s ON si.sale_id = s.id
                WHERE s.departement_id = ?";
        return $this->db->fetchOne($sql, [$departementId]);
    }

    /**
     * Calcule le chiffre d'affaires d'un département.
     *
     * @param int $departementId
     * @param string|null $startDate
     * @param string|null $endDate
     * @return array|null
     */
    public function calculateRevenue($departementId, $startDate = null, $endDate = null) {
        $sql = "SELECT COUNT(*) AS salesCount, COALESCE(SUM(total_amount), 0) AS totalRevenue
                FROM sales
                WHERE departement_id = ?";
        $params = [$departementId];
        $this->buildDateRangeClause($sql, $params, 'sold_at', $startDate, $endDate, 'AND');
        return $this->db->fetchOne($sql, $params);
    }

    /**
     * Calcule le total des salaires déclarés pour un département.
     *
     * @param int $departementId
     * @param int|null $year
     * @param int|null $month
     * @return array|null
     */
    public function calculatePayroll($departementId, $year = null, $month = null) {
        $sql = "SELECT COUNT(*) AS reportCount, COALESCE(SUM(total_salary), 0) AS totalSalary
                FROM salary_reports
                WHERE departement_id = ?";
        $params = [$departementId];

        if ($year !== null) {
            $sql .= " AND year = ?";
            $params[] = $year;
        }
        if ($month !== null) {
            $sql .= " AND month = ?";
            $params[] = $month;
        }

        return $this->db->fetchOne($sql, $params);
    }

    /**
     * Retourne un tableau de bord synthétique pour un département.
     *
     * @param int $departementId
     * @return array|null
     */
    public function getDepartementOverview($departementId) {
        $departement = $this->findByIdWithManager($departementId);
        if (!$departement) {
            return null;
        }

        $employeeCount = $this->db->fetchOne("SELECT COUNT(*) AS total FROM employees WHERE departement_id = ?", [$departementId]);
        $productCount = $this->db->fetchOne("SELECT COUNT(*) AS total FROM products WHERE departement_id = ?", [$departementId]);
        $lowStockCount = $this->db->fetchOne("SELECT COUNT(*) AS total FROM products WHERE departement_id = ? AND current_stock <= low_stock_threshold", [$departementId]);
        $revenue = $this->calculateRevenue($departementId);
        $debtSummary = $this->getDebtSummary($departementId);
        $salaryReports = $this->calculatePayroll($departementId);

        return [
            'departement' => $departement,
            'employeesCount' => (int)$employeeCount['total'],
            'productsCount' => (int)$productCount['total'],
            'lowStockProductsCount' => (int)$lowStockCount['total'],
            'salesCount' => (int)$revenue['salesCount'],
            'totalRevenue' => (float)$revenue['totalRevenue'],
            'debtSummary' => $debtSummary,
            'salaryReportsCount' => (int)$salaryReports['reportCount'],
            'totalSalary' => (float)$salaryReports['totalSalary'],
        ];
    }

    /**
     * Ajoute une clause de plage de dates à une requête SQL.
     *
     * @param string &$sql
     * @param array &$params
     * @param string $field
     * @param string|null $startDate
     * @param string|null $endDate
     * @param string $prefix
     * @return void
     */
    private function buildDateRangeClause(&$sql, &$params, $field, $startDate, $endDate, $prefix = 'AND') {
        if ($startDate !== null) {
            $sql .= " {$prefix} {$field} >= ?";
            $params[] = $startDate;
            $prefix = 'AND';
        }
        if ($endDate !== null) {
            $sql .= " {$prefix} {$field} <= ?";
            $params[] = $endDate;
        }
    }

    
}














?>