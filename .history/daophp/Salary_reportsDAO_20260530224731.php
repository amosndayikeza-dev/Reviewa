<?php

include_once "DAO.php";

class Salary_reportsDAO extends DAO{

    protected $table = 'salary_reports';
    protected $primaryKey = 'id';

    /**
     * Extrait (year, month) depuis une date YYYY-MM-DD.
     * @param string|null $dateStr
     * @return array{year:int|null, month:int|null}
     */
    private function parseYearMonthFromDate($dateStr) {
        if ($dateStr === null) {
            return ['year' => null, 'month' => null];
        }
        $dateStr = (string)$dateStr;
        if (strlen($dateStr) < 7) {
            return ['year' => null, 'month' => null];
        }
        $year  = (int)substr($dateStr, 0, 4);
        $month = (int)substr($dateStr, 5, 2);
        return ['year' => $year ?: null, 'month' => $month ?: null];
    }

    /**
     * Récupère les rapports de salaire d'un département.
     *
     * @param int $departmentId
     * @param int|null $year
     * @param int|null $month
     * @return array
     */
    public function findByDepartement($departmentId, $year = null, $month = null) {
        $sql = "SELECT * FROM {$this->table} WHERE departement_id = ?";
        $params = [$departmentId];

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
     * Récupère les rapports d'un manager.
     *
     * @param int $managerId
     * @param int|null $year
     * @param int|null $month
     * @return array
     */
    public function findByManager($managerId, $year = null, $month = null) {
        $sql = "SELECT * FROM {$this->table} WHERE manager_id = ?";
        $params = [$managerId];

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
     * Récupère les rapports pour une période année/mois.
     *
     * @param int $year
     * @param int $month
     * @param int|null $departmentId
     * @return array
     */
    public function findByPeriod($year, $month, $departementId = null) {
        $sql = "SELECT * FROM {$this->table} WHERE year = ? AND month = ?";
        $params = [$year, $month];

        if ($departementId !== null) {
            $sql .= " AND departement_id = ?";
            $params[] = $departementId;
        }

        return $this->db->fetchAll($sql, $params);
    }

    /**
     * Récupère les rapports par statut.
     *
     * @param string $status
     * @param int|null $departmentId
     * @return array
     */
    public function findByStatus($status, $departmentId = null) {
        $sql = "SELECT * FROM {$this->table} WHERE status = ?";
        $params = [$status];

        if ($departmentId !== null) {
            $sql .= " AND departement_id = ?";
            $params[] = $departmentId;
        }

        return $this->db->fetchAll($sql, $params);
    }

    /**
     * Calcule le résumé des salaires pour un département et une période.
     *
     * @param int $departmentId
     * @param int $year
     * @param int|null $month
     * @return array|null
     */
    public function calculateSalarySummary($departementId, $year, $month = null) {
        $sql = "SELECT COUNT(*) AS reportCount, COALESCE(SUM(totalSalary), 0) AS totalSalary
                FROM {$this->table}
                WHERE departement_id = ? AND year = ?";
        $params = [$departementId, $year];

        if ($month !== null) {
            $sql .= " AND month = ?";
            $params[] = $month;
        }

        return $this->db->fetchOne($sql, $params);
    }

    /**
     * Récupère un rapport avec les infos du département et du manager.
     *
     * @param int $id
     * @return array|null
     */
    public function findWithDetails($id) {
        $sql = "SELECT sr.*, d.name AS departementName, u.first_name, u.last_name, u.email
                FROM {$this->table} sr
                LEFT JOIN departements d ON sr.departement_id = d.id
                LEFT JOIN users u ON sr.manager_id = u.id
                WHERE sr.id = ?";
        return $this->db->fetchOne($sql, [$id]);
    }

    /**
     * Rapports de salaire d'un département, avec détails (département + manager).
     *
     * @param int      $departmentId
     * @param int|null $year
     * @param int|null $month
     * @return array
     */
    public function findByDepartementWithDetails($departmentId, $year = null, $month = null) {
        $sql = "SELECT sr.*,
                       d.name AS departement_name,
                       u.first_name AS manager_first_name,
                       u.last_name  AS manager_last_name,
                       u.email      AS manager_email
                FROM {$this->table} sr
                LEFT JOIN departements d ON sr.departement_id = d.id
                LEFT JOIN users u        ON sr.manager_id = u.id
                WHERE sr.departement_id = ?";
        $params = [$departmentId];

        if ($year !== null) {
            $sql .= " AND sr.year = ?";
            $params[] = $year;
        }
        if ($month !== null) {
            $sql .= " AND sr.month = ?";
            $params[] = $month;
        }

        $sql .= " ORDER BY sr.year DESC, sr.month DESC, sr.id DESC";
        return $this->db->fetchAll($sql, $params);
    }

    /**
     * Rapports de salaire pour une période (year/month), avec détails.
     *
     * @param int      $year
     * @param int      $month
     * @param int|null $departmentId
     * @return array
     */
    public function findByPeriodWithDetails($year, $month, $departmentId = null) {
        $sql = "SELECT sr.*,
                       d.name AS departement_name,
                       u.first_name AS manager_first_name,
                       u.last_name  AS manager_last_name,
                       u.email      AS manager_email
                FROM {$this->table} sr
                LEFT JOIN departements d ON sr.departement_id = d.id
                LEFT JOIN users u        ON sr.manager_id = u.id
                WHERE sr.year = ? AND sr.month = ?";
        $params = [$year, $month];

        if ($departmentId !== null) {
            $sql .= " AND sr.departement_id = ?";
            $params[] = $departmentId;
        }

        $sql .= " ORDER BY sr.id DESC";
        return $this->db->fetchAll($sql, $params);
    }

    /**
     * Rapports de salaire sur une plage startDate/endDate (comparaison via year/month),
     * avec détails (département + manager).
     *
     * @param string|null $startDate   YYYY-MM-DD
     * @param string|null $endDate     YYYY-MM-DD
     * @param int|null    $departmentId
     * @return array
     */
    public function findByDateRangeWithDetails($startDate = null, $endDate = null, $departmentId = null) {
        $sql = "SELECT sr.*,
                       d.name AS departement_name,
                       u.first_name AS manager_first_name,
                       u.last_name  AS manager_last_name,
                       u.email      AS manager_email
                FROM {$this->table} sr
                LEFT JOIN departements d ON sr.departement_id = d.id
                LEFT JOIN users u        ON sr.manager_id = u.id
                WHERE 1=1";
        $params = [];

        if ($departmentId !== null) {
            $sql .= " AND sr.departement_id = ?";
            $params[] = $departmentId;
        }

        $startYM = $this->parseYearMonthFromDate($startDate);
        $endYM   = $this->parseYearMonthFromDate($endDate);

        // Condition inclusive sur year/month
        if ($startYM['year'] !== null && $startYM['month'] !== null) {
            $sql .= " AND (sr.year > ? OR (sr.year = ? AND sr.month >= ?))";
            $params[] = $startYM['year'];
            $params[] = $startYM['year'];
            $params[] = $startYM['month'];
        }
        if ($endYM['year'] !== null && $endYM['month'] !== null) {
            $sql .= " AND (sr.year < ? OR (sr.year = ? AND sr.month <= ?))";
            $params[] = $endYM['year'];
            $params[] = $endYM['year'];
            $params[] = $endYM['month'];
        }

        $sql .= " ORDER BY sr.year DESC, sr.month DESC, sr.id DESC";
        return $this->db->fetchAll($sql, $params);
    }

    /**
     * Récupère les rapports non traités (pending/draft).
     *
     * @param int|null $departmentId
     * @return array
     */
    public function getPendingReports($departementId = null) {
        $sql = "SELECT * FROM {$this->table} WHERE status IN ('pending', 'draft')";
        $params = [];

        if ($departementId !== null) {
            $sql .= " AND departement_id = ?";
            $params[] = $departementId;
        }

        return $this->db->fetchAll($sql, $params);
    }

}
















