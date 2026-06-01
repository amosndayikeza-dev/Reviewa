<?php

include_once __DIR__ . '/../daophp/UserDAO.php';
include_once __DIR__ . '/../daophp/EmployeeDAO.php';

class AuthService {
    protected $userDao;
    protected $employeeDao;

    public function __construct() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $this->userDao = new UserDAO();
        $this->employeeDao = new EmployeeDAO();
    }

    /**
     * Authentifie un utilisateur par email + mot de passe.
     * Retourne le contexte utilisateur (id, email, role, departmentId) ou false.
     */
    public function login(string $email, string $password) {
        $user = $this->userDao->findByEmail($email);
        if (!$user) {
            return false;
        }
        // password field expected to be hashed via password_hash
        if (!isset($user['password']) || !password_verify($password, $user['password'])) {
            return false;
        }
        if ((isset($user['active']) && !$user['active']) || (isset($user['is_active']) && !$user['is_active'])) {
            return false;
        }

        // try to find an employee record to get departmentId if any
        $departementId = null;
        $employee = $this->employeeDao->findByUserId($user['id']);
        if ($employee && isset($employee['departement_id'])) {
            $departementId = $employee['departement_id'];
        }

        $context = [
            'id' => (int)$user['id'],
            'email' => $user['email'],
            'role' => $user['role'] ?? null,
            'departmentId' => $departementId,
            'departement_id' => $departementId,
            'firstName' => $user['first_name'] ?? null,
            'lastName' => $user['last_name'] ?? null,
        ];

        // store minimal user context in session
        $_SESSION['user'] = $context;

        return $context;
    }

    /**
     * Logout: détruit la session utilisateur.
     */
    public function logout(int $userId = null) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (isset($_SESSION['user'])) {
            unset($_SESSION['user']);
        }
    }

    public function getCurrentUser(): ?array {
        return $_SESSION['user'] ?? null;
    }

    public function isAuthenticated(): bool {
        return !empty($_SESSION['user']);
    }

    public function getUserId(): ?int {
        return $this->isAuthenticated() ? (int)$_SESSION['user']['id'] : null;
    }

    public function getUserRole(): ?string {
        return $this->isAuthenticated() ? ($_SESSION['user']['role'] ?? null) : null;
    }

    public function getUserDepartementId(): ?int {
        return $this->isAuthenticated() ? ($_SESSION['user']['departementId'] ?? $_SESSION['user']['departement_id'] ?? null) : null;
    }


    /*public function getUserDepartementId(): ?int {
        return $this->getUserDepartementId();
    }*/

    public function isAdmin(): bool {
        return $this->hasRole('admin');
    }

    public function isPatron(): bool {
        return $this->hasRole('patron');
    }

    public function isManager(): bool {
        return $this->hasRole('manager');
    }

    public function hasRole(string $role): bool {
        $r = $this->getUserRole();
        return $r !== null && strtolower($r) === strtolower($role);
    }

    /**
     * Vérifie si l'utilisateur courant peut effectuer une action sur une ressource.
     * Ressource attendue ex: ['departement_id' => 3]
     */
    public function can(string $action, array $resource = null): bool {
        // admins can everything
        if ($this->isAdmin()) return true;

        switch ($action) {
            case 'view_report':
                if ($this->isPatron()) return true; // patron sees all reports
                if ($this->isManager()) {
                    if (!$resource || !isset($resource['departement_id'])) return false;
                    return $this->getUserDepartementId() === (int)$resource['departement_id'];
                }
                return false;

            case 'manage_users':
                return $this->isAdmin();

            case 'print_invoice':
                // patron and manager can print (manager only for own dept)
                if ($this->isPatron()) return true;
                if ($this->isManager()) {
                    if (!$resource || !isset($resource['departement_id'])) return false;
                    return $this->getUserDepartementId() === (int)$resource['departement_id'];
                }
                return false;

            default:
                return false;
        }
    }

    // helper to enforce auth on API endpoints
    public function requireAuth(): void {
        if (!$this->isAuthenticated()) {
            header('HTTP/1.1 401 Unauthorized');
            header('Content-Type: application/json');
            echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
            exit;
        }
    }

}
