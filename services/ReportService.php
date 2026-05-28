<?php

include_once __DIR__ . '/../daophp/SalesDAO.php';
include_once __DIR__ . '/../daophp/DebtsDAO.php';
include_once __DIR__ . '/../daophp/Salary_reportsDAO.php';
include_once __DIR__ . '/../daophp/ProductsDAO.php';
include_once __DIR__ . '/../daophp/DepartementDAO.php';

class ReportService {
    protected $salesDao;
    protected $debtsDao;
    protected $salaryReportsDao;
    protected $productsDao;
    protected $departementDao;

    public function __construct() {
        $this->salesDao = new SalesDAO();
        $this->debtsDao = new DebtsDAO();
        $this->salaryReportsDao = new Salary_reportsDAO();
        $this->productsDao = new ProductsDAO();
        $this->departementDao = new DepartementDAO();
    }

    /**
     * Retourne un rapport de ventes filtré par département et période.
     */
    public function getSalesReport($departementId = null, $startDate = null, $endDate = null) {
        return $this->salesDao->findByDateRange($startDate, $endDate, $departementId);
    }

    /**
     * Retourne un résumé global des ventes pour un département et une période.
     */
    public function getSalesSummary($departementId = null, $startDate = null, $endDate = null) {
        return $this->salesDao->calculateRevenue($departementId, $startDate, $endDate);
    }

    /**
     * Retourne un rapport de dettes avec filtre département, période et statut.
     *
     * @param int|null    $departementId
     * @param string|null $startDate   YYYY-MM-DD
     * @param string|null $endDate     YYYY-MM-DD
     * @param string|null $status      unpaid | paid | overdue
     * @return array
     */
    public function getDebtReport($departementId = null, $startDate = null, $endDate = null, $status = null) {
        return $this->debtsDao->findAllWithDetails($departementId, $startDate, $endDate, $status);
    }

    /**
     * Retourne le résumé (totaux) des dettes avec les mêmes filtres.
     *
     * @param int|null    $departementId
     * @param string|null $startDate
     * @param string|null $endDate
     * @param string|null $status
     * @return array|null
     */
    public function getDebtSummary($departementId = null, $startDate = null, $endDate = null, $status = null) {
        return $this->debtsDao->getSummaryFiltered($departementId, $startDate, $endDate, $status);
    }

    /**
     * Retourne les rapports de salaire pour un département.
     */
    public function getSalaryReport($departementId = null, $year = null, $month = null) {
        if ($departementId !== null) {
            return $this->salaryReportsDao->findByDepartementWithDetails($departementId, $year, $month);
        }
        return $this->salaryReportsDao->findByPeriodWithDetails($year ?? date('Y'), $month ?? date('n'));
    }

    /**
     * Retourne les rapports de salaire filtrés par période (startDate/endDate),
     * avec détails (département + manager).
     *
     * @param int|null    $departementId
     * @param string|null $startDate
     * @param string|null $endDate
     * @return array
     */
    public function getSalaryReportByDateRange($departementId = null, $startDate = null, $endDate = null) {
        return $this->salaryReportsDao->findByDateRangeWithDetails($startDate, $endDate, $departementId);
    }

    /**
     * Retourne le stock du département, et les produits à seuil bas.
     */
    public function getInventoryReport($departementId = null) {
        return [
            'products' => $this->productsDao->findByDepartement($departementId),
            'low_stock_threshold' => $this->productsDao->findLowStock($departementId)
        ];
    }

    /**
     * Retourne la liste des départements, utile pour enrichir un rapport.
     */
    public function getDepartements() {
        return $this->departementDao->findAllWithManager('name ASC');
    }
}
