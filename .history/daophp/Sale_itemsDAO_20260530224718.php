<?php

include_once "DAO.php";

class Sale_itemsDAO extends DAO{

    protected $table = 'sale_items';
    protected $primaryKey = 'id';

    /**
     * Récupère les items d'une vente.
     *
     * @param int $saleId
     * @return array
     */
    public function findBySale($saleId) {
        $sql = "SELECT * FROM {$this->table} WHERE sale_id = ?";
        return $this->db->fetchAll($sql, [$saleId]);
    }

    /**
     * Récupère les items pour un produit.
     *
     * @param int $productId
     * @param string|null $orderBy
     * @return array
     */
    public function findByProduct($productId, $orderBy = null) {
        $sql = "SELECT * FROM {$this->table} WHERE product_id = ?";
        if ($orderBy) {
            $sql .= " ORDER BY {$orderBy}";
        }
        return $this->db->fetchAll($sql, [$productId]);
    }

    /**
     * Récupère les items avec statut de paiement spécifique.
     *
     * @param bool $isPaid
     * @param int|null $saleId
     * @return array
     */
    public function findByPaymentStatus($isPaid, $saleId = null) {
        $sql = "SELECT * FROM {$this->table} WHERE is_paid = ?";
        $params = [$isPaid ? 1 : 0];

        if ($saleId !== null) {
            $sql .= " AND sale_id = ?";
            $params[] = $saleId;
        }

        return $this->db->fetchAll($sql, $params);
    }

    /**
     * Récupère les items non payés.
     *
     * @param int|null $saleId
     * @return array
     */
    public function findUnpaid($saleId = null) {
        return $this->findByPaymentStatus(false, $saleId);
    }

    /**
     * Calcule le montant total des items d'une vente.
     *
     * @param int $saleId
     * @return array|null
     */
    public function calculateSaleTotal($saleId) {
        $sql = "SELECT COUNT(*) AS itemCount, COALESCE(SUM(quantity), 0) AS totalQuantity, COALESCE(SUM(quantity * unitPrice), 0) AS totalAmount
                FROM {$this->table}
                WHERE sale_id = ?";
        return $this->db->fetchOne($sql, [$saleId]);
    }

    /**
     * Calcule le total des items payés et non payés.
     *
     * @param int $saleId
     * @return array|null
     */
    public function calculatePaymentSplit($saleId) {
        $sql = "SELECT COUNT(*) AS totalItems,
                       SUM(CASE WHEN is_paid = 1 THEN 1 ELSE 0 END) AS paidItems,
                       SUM(CASE WHEN is_paid = 0 THEN 1 ELSE 0 END) AS unpaidItems,
                       COALESCE(SUM(CASE WHEN is_paid = 1 THEN quantity * unit_price ELSE 0 END), 0) AS paidAmount,
                       COALESCE(SUM(CASE WHEN is_paid = 0 THEN quantity * unit_price ELSE 0 END), 0) AS unpaidAmount
                FROM {$this->table}
                WHERE sale_id = ?";
        return $this->db->fetchOne($sql, [$saleId]);
    }

    /**
     * Récupère les items avec infos produit.
     *
     * @param int $saleId
     * @return array
     */
    public function findWithProduct($saleId) {
        $sql = "SELECT si.*, p.name AS productName, p.description, p.departement_id
                FROM {$this->table} si
                LEFT JOIN products p ON si.product_id = p.id
                WHERE si.sale_id = ?";
        return $this->db->fetchAll($sql, [$saleId]);
    }

    /**
     * Marque un item comme payé ou non.
     *
     * @param int $id
     * @param bool $isPaid
     * @return int
     */
    public function updatePaymentStatus($id, $isPaid) {
        return $this->update($id, ['is_paid' => $isPaid ? 1 : 0]);
    }

    /**
     * Récupère les items avec les infos de la vente (client).
     *
     * @param int $id
     * @return array|null
     */
    public function findWithSaleInfo($id) {
        $sql = "SELECT si.*, s.departement_id, s.sold_at, s.created_by
                FROM {$this->table} si
                LEFT JOIN sales s ON si.sale_id = s.id
                WHERE si.id = ?";
        return $this->db->fetchOne($sql, [$id]);
    }

}









