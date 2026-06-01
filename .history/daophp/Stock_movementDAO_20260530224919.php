<?php
include_once "DAO.php";
class Stock_movementDAO extends DAO{

    protected $table = 'stock_movements';
    protected $primaryKey = 'id';

    /**
     * Récupère les mouvements de stock pour un produit.
     *
     * @param int $productId
     * @param string|null $orderBy
     * @return array
     */
    public function findByProduct($productId, $orderBy = null) {
        $sql = "SELECT * FROM {$this->table} WHERE product_id = ?";
        if ($orderBy) {
            $sql .= " ORDER BY {$orderBy}";
        } else {
            $sql .= " ORDER BY created_at DESC";
        }
        return $this->db->fetchAll($sql, [$productId]);
    }

    /**
     * Récupère les mouvements par type (IN, OUT, ADJUSTMENT).
     *
     * @param string $type
     * @param int|null $productId
     * @return array
     */
    public function findByType($type, $productId = null) {
        $sql = "SELECT * FROM {$this->table} WHERE type = ?";
        $params = [$type];

        if ($productId !== null) {
            $sql .= " AND product_id = ?";
            $params[] = $productId;
        }

        return $this->db->fetchAll($sql, $params);
    }

    /**
     * Récupère les mouvements par raison.
     *
     * @param string $reason
     * @param int|null $productId
     * @return array
     */
    public function findByReason($reason, $productId = null) {
        $sql = "SELECT * FROM {$this->table} WHERE reason = ?";
        $params = [$reason];

        if ($productId !== null) {
            $sql .= " AND productId = ?";
            $params[] = $productId;
        }

        return $this->db->fetchAll($sql, $params);
    }

    /**
     * Récupère les mouvements entre deux dates.
     *
     * @param string $startDate
     * @param string $endDate
     * @param int|null $productId
     * @param string|null $type
     * @return array
     */
    public function findByDateRange($startDate, $endDate, $productId = null, $type = null) {
        $sql = "SELECT * FROM {$this->table} WHERE created_at BETWEEN ? AND ?";
        $params = [$startDate, $endDate];

        if ($productId !== null) {
            $sql .= " AND product_id = ?";
            $params[] = $productId;
        }
        if ($type !== null) {
            $sql .= " AND type = ?";
            $params[] = $type;
        }

        return $this->db->fetchAll($sql, $params);
    }

    /**
     * Récupère les mouvements créés par un utilisateur.
     *
     * @param int $userId
     * @param int|null $productId
     * @return array
     */
    public function findByCreatedBy($userId, $productId = null) {
        $sql = "SELECT * FROM {$this->table} WHERE created_by = ?";
        $params = [$userId];

        if ($productId !== null) {
            $sql .= " AND product_id = ?";
            $params[] = $productId;
        }

        return $this->db->fetchAll($sql, $params);
    }

    /**
     * Calcule la quantité nette d'un produit basée sur les mouvements.
     *
     * @param int $productId
     * @param string|null $untilDate
     * @return array|null
     */
    public function calculateNetMovement($productId, $untilDate = null) {
        $sql = "SELECT COALESCE(SUM(CASE WHEN type = 'IN' THEN quantity WHEN type = 'OUT' THEN -quantity ELSE quantity END), 0) AS netQuantity,
                       COUNT(*) AS movementCount
                FROM {$this->table}
                WHERE product_id = ?";
        $params = [$productId];

        if ($untilDate !== null) {
            $sql .= " AND created_at <= ?";
            $params[] = $untilDate;
        }

        return $this->db->fetchOne($sql, $params);
    }

    /**
     * Récupère un résumé des mouvements par type pour un produit.
     *
     * @param int $productId
     * @return array
     */
    public function getMovementSummaryByType($productId) {
        $sql = "SELECT type, COUNT(*) AS count, COALESCE(SUM(quantity), 0) AS totalQuantity
                FROM {$this->table}
                WHERE product_id = ?
                GROUP BY type";
        return $this->db->fetchAll($sql, [$productId]);
    }

    /**
     * Récupère un résumé des mouvements par raison.
     *
     * @param int $productId
     * @return array
     */
    public function getMovementSummaryByReason($productId) {
        $sql = "SELECT reason, COUNT(*) AS count, COALESCE(SUM(quantity), 0) AS totalQuantity
                FROM {$this->table}
                WHERE product_id = ?
                GROUP BY reason";
        return $this->db->fetchAll($sql, [$productId]);
    }

    /**
     * Récupère les mouvements liés à une vente ou une référence.
     *
     * @param int $referenceId
     * @param string|null $type
     * @return array
     */
    public function findByReference($referenceId, $type = null) {
        $sql = "SELECT * FROM {$this->table} WHERE reference_id = ?";
        $params = [$referenceId];

        if ($type !== null) {
            $sql .= " AND type = ?";
            $params[] = $type;
        }

        return $this->db->fetchAll($sql, $params);
    }

    /**
     * Récupère un historique complet avec infos produit et département.
     *
     * @param int $productId
     * @return array
     */
    public function getMovementHistory($productId) {
        $sql = "SELECT sm.*, p.name AS productName, p.departement_id, d.name AS departementName
                FROM {$this->table} sm
                LEFT JOIN products p ON sm.product_id = p.id
                LEFT JOIN departements d ON p.departement_id = d.id
                WHERE sm.product_id = ?
                ORDER BY sm.createdAt DESC";
        return $this->db->fetchAll($sql, [$productId]);
    }

}













