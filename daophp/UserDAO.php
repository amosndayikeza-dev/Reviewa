<?php

include_once "DAO.php";

Class UserDAO extends DAO {
    protected $table = 'users';
    protected $primaryKey = 'id';

    /**
     * Récupère un utilisateur par email.
     *
     * @param string $email
     * @return array|null
     */
    public function findByEmail($email) {
        $sql = "SELECT * FROM {$this->table} WHERE email = ?";
        return $this->db->fetchOne($sql, [$email]);
    }

    /**
     * Récupère les utilisateurs actifs ou inactifs.
     *
     * @param bool|null $active
     * @param string|null $orderBy
     * @return array
     */
    public function findByActiveStatus($active = null, $orderBy = null) {
        $sql = "SELECT * FROM {$this->table}";
        $params = [];

        if ($active !== null) {
            $sql .= " WHERE is_active = ?";
            $params[] = $active ? 1 : 0;
        }

        if ($orderBy) {
            $sql .= " ORDER BY {$orderBy}";
        }

        return $this->db->fetchAll($sql, $params);
    }

    /**
     * Récupère les utilisateurs par rôle.
     *
     * @param string $role
     * @param bool|null $active
     * @return array
     */
    public function findByRole($role, $active = null) {
        $sql = "SELECT * FROM {$this->table} WHERE role = ?";
        $params = [$role];

        if ($active !== null) {
            $sql .= " AND active = ?";
            $params[] = $active ? 1 : 0;
        }

        return $this->db->fetchAll($sql, $params);
    }

    /**
     * Récupère les utilisateurs avec leurs départements et postes.
     *
     * @param int|null $departementId
     * @return array
     */
    public function getUsersWithEmployeeInfo($departementId = null) {
        $sql = "SELECT u.*, e.departement_id, e.position, e.salary, e.hired_at
                FROM {$this->table} u
                LEFT JOIN employees e ON u.id = e.user_id";
        $params = [];

        if ($departementId !== null) {
            $sql .= " WHERE e.departement_id = ?";
            $params[] = $departementId;
        }

        return $this->db->fetchAll($sql, $params);
    }

}






?>