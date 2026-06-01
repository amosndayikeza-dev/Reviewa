<?php

include_once "DAO.php";
class ProductsDAO extends DAO{

    protected $table = 'products';
    protected $primaryKey = 'id';

    /**
     * Récupère les produits d'un département.
     *
     * @param int|null $departementId
     * @param string|null $orderBy
     * @return array
     */
    public function findByDepartement($departementId = null, $orderBy = null) {
        $sql = "SELECT * FROM {$this->table}";
        $params = [];

        if ($departementId !== null) {
            $sql .= " WHERE departement_id = ?";
            $params[] = $departementId;
        }

        if ($orderBy) {
            $sql .= " ORDER BY {$orderBy}";
        }

        return $this->db->fetchAll($sql, $params);
    }

    /**
     * Récupère les produits en stock faible.
     *
     * @param int|null $departementId
     * @return array
     */
    public function findLowStock($departementId = null) {
        $sql = "SELECT * FROM {$this->table} WHERE current_stock <= low_stock_threshold";
        $params = [];

        if ($departementId !== null) {
            $sql .= " AND departement_id = ?";
            $params[] = $departementId;
        }

        return $this->db->fetchAll($sql, $params);
    }

    /**
     * Recherche les produits par nom ou description.
     *
     * @param string $query
     * @param int|null $departementId
     * @return array
     */
    public function searchProducts($query, $departementId = null) {
        $sql = "SELECT * FROM {$this->table} WHERE (name LIKE ? OR description LIKE ?)";
        $params = ["%{$query}%", "%{$query}%"];

        if ($departementId !== null) {
            $sql .= " AND departement_id = ?";
            $params[] = $departementId;
        }

        return $this->db->fetchAll($sql, $params);
    }

    /**
     * Récupère l'historique des mouvements de stock pour un produit.
     *
     * @param int $productId
     * @param string|null $startDate
     * @param string|null $endDate
     * @return array
     */
    public function getStockMovements($productId, $startDate = null, $endDate = null) {
        $sql = "SELECT sm.* FROM stock_movements sm WHERE sm.product_id = ?";
        $params = [$productId];

        if ($startDate !== null) {
            $sql .= " AND sm.created_at >= ?";
            $params[] = $startDate;
        }
        if ($endDate !== null) {
            $sql .= " AND sm.created_at <= ?";
            $params[] = $endDate;
        }

        return $this->db->fetchAll($sql, $params);
    }

    /**
     * Retourne les statistiques de ventes pour un produit.
     *
     * @param int $productId
     * @param string|null $startDate
     * @param string|null $endDate
     * @return array|null
     */
    public function getSalesStats($productId, $startDate = null, $endDate = null) {
        $sql = "SELECT COUNT(si.id) AS itemsSold,
                       COALESCE(SUM(si.quantity), 0) AS totalQuantity,
                       COALESCE(SUM(si.quantity * si.unitPrice), 0) AS totalRevenue
                FROM sale_items si
                JOIN sales s ON si.sale_id = s.id
                WHERE si.productId = ?";
        $params = [$productId];

        if ($startDate !== null) {
            $sql .= " AND s.sold_at >= ?";
            $params[] = $startDate;
        }
        if ($endDate !== null) {
            $sql .= " AND s.sold_at <= ?";
            $params[] = $endDate;
        }

        return $this->db->fetchOne($sql, $params);
    }

    /**
     * Met à jour le stock d'un produit en appliquant une quantité delta.
     *
     * @param int $productId
     * @param int $quantity
     * @return int
     */
    public function adjustStock($productId, $quantity) {
        $sql = "UPDATE {$this->table} SET current_stock = current_stock + ? WHERE id = ?";
        return $this->db->query($sql, [$quantity, $productId])->rowCount();
    }

    /**
     * Retourne un produit avec les informations de son département.
     *
     * @param int $id
     * @return array|null
     */
    public function findWithDepartment($id) {
        $sql = "SELECT p.*, d.name AS departement_name, d.description AS departementDescription
                FROM {$this->table} p
                LEFT JOIN departements d ON p.departement_id = d.id
                WHERE p.id = ?";
        return $this->db->fetchOne($sql, [$id]);
    }

}


?>





















