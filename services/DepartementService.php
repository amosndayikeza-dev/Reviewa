<?php

include_once __DIR__ . '/../daophp/DepartementDAO.php';
include_once __DIR__ . '/../daophp/EmployeeDAO.php';
include_once __DIR__ . '/../daophp/SalesDAO.php';
include_once __DIR__ . '/../daophp/Salary_reportsDAO.php';

/**
 * Service métier pour la gestion des départements.
 *
 * Ce service centralise les opérations liées aux départements, aux employés
 * et aux statistiques associées.
 */
class DepartementService {
    protected $departementDao;
    protected $employeeDao;
    protected $salesDao;
    protected $salaryReportDao;

    public function __construct() {
        $this->departementDao = new DepartementDAO();
        $this->employeeDao = new EmployeeDAO();
        $this->salesDao = new SalesDAO();
        $this->salaryReportDao = new Salary_reportsDAO();
    }

    /**
     * Récupère un département par ID.
     *
     * @param int $id
     * @return array|null
     */
    public function getDepartementById($id) {
        return $this->departementDao->findById($id);
    }

    /**
     * Récupère la liste de tous les départements.
     *
     * @return array
     */
    public function getAllDepartments() {
        return $this->departementDao->findAll();
    }

    /**
     * Récupère le résumé de performance d'un département.
     *
     * @param int $departmentId
     * @return array|null
     */
    public function getDepartementSummary($departementId) {
        $departement = $this->getDepartementById($departementId);
        if (!$departement) {
            return null;
        }

        $employees = $this->employeeDao->findByDepartement($departementId);
        $sales = $this->salesDao->findByDepartement($departementId);
        $salaryReports = $this->salaryReportDao->findByDepartement($departementId);

        $totalSales = array_sum(array_map(function ($sale) {
            return $sale['total'] ?? 0;
        }, $sales));

        return [
            'departement' => $departement,
            'employeeCount' => count($employees),
            'salesCount' => count($sales),
            'totalSales' => round($totalSales, 2),
            'salaryReports' => $salaryReports,
        ];
    }
}
