<?php

include_once __DIR__ . '/../daophp/DepartementDAO.php';
include_once __DIR__ . '/../daophp/EmployeeDAO.php';
include_once __DIR__ . '/../daophp/DebtsDAO.php';
include_once __DIR__ . '/../daophp/SalesDAO.php';
include_once __DIR__ . '/../daophp/Salary_reportsDAO.php';
include_once __DIR__ . '/../daophp/Database.php';

class DashboardService {
    protected $departementDao;
    protected $employeeDao;
    protected $debtsDao;
    protected $salesDao;
    protected $salaryReportDao;
    protected $departementId = null;
    protected $db;

    public function __construct() {
        $this->departementDao = new DepartementDAO();
        $this->employeeDao = new EmployeeDAO();
        $this->debtsDao = new DebtsDAO();
        $this->salesDao = new SalesDAO();
        $this->salaryReportDao = new Salary_reportsDAO();
        $this->db = Database::getInstance();
        $this->departementId = $departementId;
    }

    /**
     * Retourne le nombre total de départements.
     *
     * @return int
     */
    public function getTotalDepartments() {
        return $this->departementDao->count();
    }

    /**
     * Retourne le nombre total d'employés.
     *
     * @return int
     */
    public function getTotalEmployees() {
        return $this->employeeDao->count();
    }

    /**
     * Retourne le nombre total de rapports de salaire.
     *
     * @return int
     */
    public function getTotalReports() {
        return $this->salaryReportDao->count();
    }

    /**
     * Retourne la somme totale des dettes.
     *
     * @param string|null $status Filtre optionnel par statut de dette
     * @return float
     */
    public function getTotalDebtsAmount($status = null) {
        $summary = $this->debtsDao->getSummary($status);
        return isset($summary['totalAmount']) ? (float)$summary['totalAmount'] : 0.0;
    }

    /**
     * Retourne le chiffre d'affaires du jour.
     *
     * @param int|null $departementId Filtre optionnel par département
     * @return float
     */
    public function getDailyRevenue($departementId = null) {
        $today = date('Y-m-d');
        $start = $today . ' 00:00:00';
        $end = $today . ' 23:59:59';
        $summary = $this->salesDao->calculateRevenue($departementId, $start, $end);
        return isset($summary['totalRevenue']) ? (float)$summary['totalRevenue'] : 0.0;
    }

    /**
     * Retourne le dernier employé enregistré.
     *
     * @return array|null
     */
    public function getLatestEmployee() {
        $sql = "SELECT e.*, u.first_name, u.last_name
                FROM employees e
                LEFT JOIN users u ON e.user_id = u.id
                ORDER BY e.id DESC
                LIMIT 1";
        return $this->db->fetchOne($sql);
    }

    /**
     * Retourne le dernier rapport de salaire envoyé.
     *
     * @return array|null
     */
    public function getLatestReport() {
        $sql = "SELECT sr.*
                FROM salary_reports sr
                ORDER BY sr.id DESC
                LIMIT 1";
        return $this->db->fetchOne($sql);
    }

    /**
     * Retourne le dernier département créé.
     *
     * @return array|null
     */
    public function getLatestDepartement() {
        $sql = "SELECT d.*
                FROM departements d
                ORDER BY d.id DESC
                LIMIT 1";
        return $this->db->fetchOne($sql);
    }

    /**
     * Retourne un ensemble d'indicateurs prêts pour le dashboard.
     *
     * @return array
     */
    public function getDashboardSummary() {
        return [
            'totalDepartments' => $this->getTotalDepartments(),
            'totalEmployees' => $this->getTotalEmployees(),
            'totalReports' => $this->getTotalReports(),
            'totalDebtsAmount' => $this->getTotalDebtsAmount(),
            'dailyRevenue' => $this->getDailyRevenue(),
            'latestEmployee' => $this->getLatestEmployee(),
            'latestReport' => $this->getLatestReport(),
            'latestDepartement' => $this->getLatestDepartement(),
        ];
    }
}
