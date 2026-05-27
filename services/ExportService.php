<?php

include_once __DIR__ . '/../daophp/SalesDAO.php';
include_once __DIR__ . '/../daophp/UserDAO.php';
include_once __DIR__ . '/../daophp/DebtsDAO.php';
include_once __DIR__ . '/../daophp/Salary_reportsDAO.php';
include_once __DIR__ . '/../exports/CsvExporter.php';
include_once __DIR__ . '/../exports/ExcelExporter.php';
include_once __DIR__ . '/AuthService.php';

/**
 * Service d'export des données (CSV/JSON).
 */
class ExportService {
    protected $salesDao;
    protected $userDao;
    protected $auth;
    protected $debtsDao;
    protected $salaryDao;

    public function __construct() {
        $this->salesDao = new SalesDAO();
        $this->userDao = new UserDAO();
        $this->debtsDao = new DebtsDAO();
        $this->salaryDao = new Salary_reportsDAO();
        $this->auth = new AuthService();
    }

    /**
     * Récupère les ventes pour la période et le département donnés.
     * @param string|null $startDate
     * @param string|null $endDate
     * @param int|null $departmentId
     * @return array
     */
    public function getSalesData($startDate = null, $endDate = null, $departmentId = null) {
        // delegate to DAO: SalesDAO::findByDateRange
        return $this->salesDao->findByDateRange($startDate, $endDate, $departmentId);
    }

    /**
     * Récupère les utilisateurs (avec info employé si present)
     * @param int|null $departmentId
     * @return array
     */
    public function getUsersData($departmentId = null) {
        return $this->userDao->getUsersWithEmployeeInfo($departmentId);
    }

    /**
     * Récupère les dettes, filtrées par département ou status.
     * @param int|null $departmentId
     * @param string|null $status
     * @param string|null $debtorType
     * @return array
     */
    public function getDebtsData($departmentId = null, $status = null, $debtorType = null) {
        if ($departmentId !== null) {
            return $this->debtsDao->findByDepartement($departmentId, $status);
        }
        if ($status === 'unpaid' || $status === null) {
            return $this->debtsDao->findUnpaid($debtorType, $status);
        }
        // fallback to all debts
        return $this->debtsDao->findAll();
    }

    /**
     * Récupère les rapports de salaire pour une période.
     * @param int|null $year
     * @param int|null $month
     * @param int|null $departmentId
     * @return array
     */
    public function getSalaryData($year = null, $month = null, $departmentId = null) {
        if ($year !== null) {
            return $this->salaryDao->findByPeriod($year, $month, $departmentId);
        }
        if ($departmentId !== null) {
            return $this->salaryDao->findByDepartement($departmentId);
        }
        return $this->salaryDao->findAll();
    }

    /**
     * Export rows as CSV string
     * @param array $rows
     * @param array|null $headers
     * @return string
     */
    public function exportRowsToCsv(array $rows, array $headers = null) {
        return CsvExporter::toCsv($rows, $headers);
    }

    /**
     * Export rows to Excel 2003 XML string.
     * @param array $rows
     * @param array|null $headers
     * @return string
     */
    public function exportRowsToExcel(array $rows, array $headers = null) {
        return ExcelExporter::toExcelXml($rows, $headers);
    }

    /**
     * Helper that checks permission for department-limited exports for managers
     */
    public function canExportDepartment($departmentId = null) {
        // admins and patron can export any dept
        if ($this->auth->isAdmin() || $this->auth->isPatron()) return true;
        if ($this->auth->isManager()) {
            $myDept = $this->auth->getUserDepartementId();
            return $departmentId === null || $myDept === (int)$departmentId;
        }
        return false;
    }
}
