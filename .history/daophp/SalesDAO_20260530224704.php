<?php

include_once "DAO.php";
class SalesDAO extends DAO{

    protected $table = 'sales';
    protected $primaryKey = 'id';

    /**
     * Récupère les ventes d'un département.
     *
     * @param int $departementId
     * @param string|null $orderBy
     * @return array
     */
    public function findByDepartement($departementId, $orderBy = null) {
        $sql = "SELECT * FROM {$this->table} WHERE departement_id = ?";
        if ($orderBy) {
            $sql .= " ORDER BY {$orderBy}";
        }
        return $this->db->fetchAll($sql, [$departementId]);
    }

    /**
     * Récupère les ventes entre deux dates.
     *
     * @param string $startDate
     * @param string $endDate
     * @param int|null $departementId
     * @return array
     */
    public function findByDateRange($startDate, $endDate, $departementId = null) {
        $sql = "SELECT * FROM {$this->table} WHERE sold_at BETWEEN ? AND ?";
        $params = [$startDate, $endDate];

        if ($departementId !== null) {
            $sql .= " AND departement_id = ?";
            $params[] = $departementId;
        }

        return $this->db->fetchAll($sql, $params);
    }

    /**
     * Récupère les ventes créées par un utilisateur.
     *
     * @param int $userId
     * @param int|null $departementId
     * @return array
     */
    public function findByCreatedBy($userId, $departementId = null) {
        $sql = "SELECT * FROM {$this->table} WHERE created_by = ?";
        $params = [$userId];

        if ($departementId !== null) {
            $sql .= " AND departement_id = ?";
            $params[] = $departementId;
        }

        return $this->db->fetchAll($sql, $params);
    }

    /**
     * Récupère les lignes de vente d'une vente.
     *
     * @param int $saleId
     * @return array
     */
    public function getSaleItems($saleId) {
        $sql = "SELECT * FROM sale_items WHERE saleId = ?";
        return $this->db->fetchAll($sql, [$saleId]);
    }

    /**
     * Récupère une vente avec ses lignes de vente et les informations des produits.
     *
     * @param int $id
     * @return array|null
     */
    public function findWithDetails($id) {
        $sale = $this->findById($id);
        if (!$sale) {
            return null;
        }

        $sql = "SELECT si.*, p.name AS productName, p.unit_price AS productUnitPrice
                FROM sale_items si
                LEFT JOIN products p ON si.product_id = p.id
                WHERE si.sale_id = ?";
        $items = $this->db->fetchAll($sql, [$id]);

        $sale['items'] = $items;
        return $sale;
    }

    /**
     * Calcule le chiffre d'affaires total pour une période.
     *
     * @param int|null $departementId
     * @param string|null $startDate
     * @param string|null $endDate
     * @return array|null
     */
    public function calculateRevenue($departementId = null, $startDate = null, $endDate = null) {
        $sql = "SELECT COUNT(*) AS salesCount, COALESCE(SUM(total_amount), 0) AS totalRevenue
                FROM {$this->table}";
        $params = [];
        $conditions = [];

        if ($departementId !== null) {
            $conditions[] = "departement_id = ?";
            $params[] = $departementId;
        }
        if ($startDate !== null) {
            $conditions[] = "sold_at >= ?";
            $params[] = $startDate;
        }
        if ($endDate !== null) {
            $conditions[] = "sold_at <= ?";
            $params[] = $endDate;
        }

        if (!empty($conditions)) {
            $sql .= " WHERE " . implode(" AND ", $conditions);
        }

        return $this->db->fetchOne($sql, $params);
    }

    /**
     * Récupère un résumé des ventes par département.
     *
     * @param string|null $startDate
     * @param string|null $endDate
     * @return array
     */
    public function getRevenueSummary($startDate = null, $endDate = null) {
        $sql = "SELECT d.id AS departementId, d.name AS departementName,
                       COUNT(s.id) AS salesCount,
                       COALESCE(SUM(s.total_amount), 0) AS totalRevenue
                FROM {$this->table} s
                LEFT JOIN departements d ON s.departement_id = d.id";
        $params = [];

        if ($startDate !== null || $endDate !== null) {
            $conditions = [];
            if ($startDate !== null) {
                $conditions[] = "s.sold_at >= ?";
                $params[] = $startDate;
            }
            if ($endDate !== null) {
                $conditions[] = "s.sold_at <= ?";
                $params[] = $endDate;
            }
            $sql .= " WHERE " . implode(" AND ", $conditions);
        }

        $sql .= " GROUP BY d.id, d.name";
        return $this->db->fetchAll($sql, $params);
    }

    /**
     * Récupère les ventes avec items payés vs non payés.
     *
     * @param int|null $departementId
     * @return array
     */
    public function getSalesWithPaymentStatus($departementId = null) {
        $sql = "SELECT s.*,
                       COUNT(si.id) AS totalItems,
                       SUM(CASE WHEN si.is_paid = 1 THEN 1 ELSE 0 END) AS paidItems,
                       SUM(CASE WHEN si.is_paid = 0 THEN 1 ELSE 0 END) AS unpaidItems
                FROM {$this->table} s
                LEFT JOIN sale_items si ON s.id = si.sale_id";
        $params = [];

        if ($departementId !== null) {
            $sql .= " WHERE s.departement_id = ?";
            $params[] = $departementId;
        }

        $sql .= " GROUP BY s.id";
        return $this->db->fetchAll($sql, $params);
    }

}










