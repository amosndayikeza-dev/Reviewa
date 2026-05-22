<?php

include_once "DAO.php";
class DebtsDAO extends DAO{

    protected $table = 'debts';
    protected $primaryKey = 'id';

    /**
     * Récupère les dettes par saleItemId.
     *
     * @param int $saleItemId
     * @return array|null
     */
    public function findBySaleItemId($saleItemId) {
        $sql = "SELECT * FROM {$this->table} WHERE sale_item_id = ?";
        return $this->db->fetchOne($sql, [$saleItemId]);
    }

    /**
     * Récupère les dettes pour un débiteur donné.
     *
     * @param string $debtorType
     * @param string $debtorName
     * @param string|null $status
     * @return array
     */
    public function findByDebtor($debtorType, $debtorName, $status = null) {
        $sql = "SELECT * FROM {$this->table} WHERE debtor_type = ? AND debtor_name = ?";
        $params = [$debtorType, $debtorName];

        if ($status !== null) {
            $sql .= " AND status = ?";
            $params[] = $status;
        }

        return $this->db->fetchAll($sql, $params);
    }

    /**
     * Récupère les dettes non réglées.
     *
     * @param string|null $debtorType
     * @param string|null $status
     * @return array
     */
    public function findUnpaid($debtorType = null, $status = 'unpaid') {
        $sql = "SELECT * FROM {$this->table} WHERE paid_amount < amount";
        $params = [];

        if ($debtorType !== null) {
            $sql .= " AND debtor_type = ?";
            $params[] = $debtorType;
        }
        if ($status !== null) {
            $sql .= " AND status = ?";
            $params[] = $status;
        }

        return $this->db->fetchAll($sql, $params);
    }

    /**
     * Récupère les dettes en retard.
     *
     * @param string|null $status
     * @return array
     */
    public function findOverdue($status = 'overdue') {
        $sql = "SELECT * FROM {$this->table} WHERE due_date < CURDATE() AND paid_amount < amount";
        if ($status !== null) {
            $sql .= " AND status = ?";
            return $this->db->fetchAll($sql, [$status]);
        }

        return $this->db->fetchAll($sql);
    }

    /**
     * Calcule le montant total des dettes et le solde restant.
     *
     * @param string|null $status
     * @return array|null
     */
    public function getSummary($status = null) {
        $sql = "SELECT COUNT(*) AS totalDebts,
                       COALESCE(SUM(amount), 0) AS totalAmount,
                       COALESCE(SUM(paid_amount), 0) AS totalPaid,
                       COALESCE(SUM(amount - paid_amount), 0) AS totalOutstanding
                FROM {$this->table}";
        $params = [];

        if ($status !== null) {
            $sql .= " WHERE status = ?";
            $params[] = $status;
        }

        return $this->db->fetchOne($sql, $params);
    }

    /**
     * Marque une dette comme payée ou met à jour le montant payé.
     *
     * @param int $id
     * @param float $paidAmount
     * @param string|null $status
     * @return int
     */
    public function updatePaidAmount($id, $paidAmount, $status = null) {
        $data = ['paid_amount' => $paidAmount];
        if ($status !== null) {
            $data['status'] = $status;
        }
        return $this->update($id, $data);
    }

    /**
     * Récupère les dettes liées à un département via sale_items -> sales.
     *
     * @param int $departementId
     * @param string|null $status
     * @return array
     */
    public function findByDepartement($departementId, $status = null) {
        $sql = "SELECT d.*
                FROM {$this->table} d
                JOIN sale_items si ON d.sale_item_id = si.id
                JOIN sales s ON si.sale_id = s.id
                WHERE s.departement_id = ?";
        $params = [$departementId];

        if ($status !== null) {
            $sql .= " AND d.status = ?";
            $params[] = $status;
        }

        return $this->db->fetchAll($sql, $params);
    }

    /**
     * Récupère un rapport de synthèse des dettes par département.
     *
     * @return array
     */
    public function getDepartementDebtSummary() {
        $sql = "SELECT s.departement_id,
                       COUNT(d.id) AS debtsCount,
                       COALESCE(SUM(d.amount), 0) AS totalAmount,
                       COALESCE(SUM(d.paid_amount), 0) AS totalPaid,
                       COALESCE(SUM(d.amount - d.paid_amount), 0) AS totalOutstanding
                FROM {$this->table} d
                JOIN sale_items si ON d.sale_item_id = si.id
                JOIN sales s ON si.sale_id = s.id
                GROUP BY s.departement_id";
        return $this->db->fetchAll($sql);
    }
}















?>