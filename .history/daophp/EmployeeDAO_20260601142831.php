<?php

include_once "DAO.php";
class EmployeeDAO extends DAO{

    protected $table = 'employees';
    protected $primaryKey = 'id';

    /**
     * Récupère un employé par son userId.
     *
     * @param int $userId
     * @return array|null
     */
    public function findByUserId($userId) {
        $sql = "SELECT * FROM {$this->table} WHERE user_id = ?";
        return $this->db->fetchOne($sql, [$userId]);
    }

    /**
     * Récupère les employés d'un département.
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
     * Récupère les employés par position et département .
     *
     * @param string $position
     * @param int|null $departementId
     * @return array
     */
    public function findByPosition($position, $departementId = null) {
        $sql = "SELECT * FROM {$this->table} WHERE position = ?";
        $params = [$position];

        if ($departementId !== null) {
            $sql .= " AND departement_id = ?";
            $params[] = $departementId;
        }

        return $this->db->fetchAll($sql, $params);
    }

    /**
     * Récupère les employés avec les informations utilisateur jointes.
     *
     * @param int|null $departementId
     * @param string|null $orderBy
     * @return array
     */
    public function getEmployeesWithUser($departementId = null, $orderBy = null) {
        $sql = "SELECT e.*, u.first_name, u.last_name, u.email, u.role,position, u.is_active AS active
                FROM {$this->table} e
                LEFT JOIN users u ON e.user_id = u.id";
        $params = [];

        if ($departementId !== null) {
            $sql .= " WHERE e.departement_id = ?";
            $params[] = $departementId;
        }
        if ($orderBy) {
            $sql .= " ORDER BY {$orderBy}";
        }

        return $this->db->fetchAll($sql, $params);
    }

    /**
     * Récupère un employé avec les informations utilisateur.
     *
     * @param int $id
     * @return array|null
     */
    public function findByIdWithUser($id) {
        $sql = "SELECT e.*, u.first_name, u.last_name, u.email, u.role, u.is_active AS active
                FROM {$this->table} e
                LEFT JOIN users u ON e.user_id = u.id
                WHERE e.{$this->primaryKey} = ?";
        return $this->db->fetchOne($sql, [$id]);
    }

    /**
     * Récupère les employés embauchés entre deux dates.
     *
     * @param string $startDate
     * @param string $endDate
     * @param int|null $departementId
     * @return array
     */
    public function getHiredBetween($startDate, $endDate, $departementId = null) {
        $sql = "SELECT * FROM {$this->table} WHERE hired_at BETWEEN ? AND ?";
        $params = [$startDate, $endDate];

        if ($departementId !== null) {
            $sql .= " AND departement_id = ?";
            $params[] = $departementId;
        }

        return $this->db->fetchAll($sql, $params);
    }

    /**
     * Calcule la masse salariale d'un département.
     *
     * @param int $departementId
     * @return array|null
     */
    public function calculateDepartementPayroll($departementId) {
        $sql = "SELECT COUNT(*) AS employeeCount, COALESCE(SUM(salary), 0) AS totalSalary, COALESCE(AVG(salary), 0) AS averageSalary
                FROM {$this->table}
                WHERE departement_id = ?";
        return $this->db->fetchOne($sql, [$departementId]);
    }

    /**
     * Récupère un résumé des employés avec les informations de département.
     *
     * @param int|null $departementId
     * @return array
     */
    public function getEmployeeOverview($departementId = null) {
        $sql = "SELECT d.id AS departementId, d.name AS departementName,
                       COUNT(e.id) AS employeeCount,
                       COALESCE(SUM(e.salary), 0) AS totalSalary,
                       COALESCE(AVG(e.salary), 0) AS averageSalary
                FROM {$this->table} e
                LEFT JOIN departements d ON e.departement_id = d.id";
        $params = [];

        if ($departementId !== null) {
            $sql .= " WHERE e.departement_id = ?";
            $params[] = $departementId;
        }

        $sql .= " GROUP BY d.id, d.name";
        return $this->db->fetchAll($sql, $params);
    }
}
















