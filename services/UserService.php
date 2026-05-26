<?php

include_once __DIR__ . '/../daophp/UserDAO.php';
include_once __DIR__ .'/../daophp/DAO.php';
/**
 * Service métier pour la gestion des utilisateurs.
 *
 * Cette classe encapsule la logique applicative autour de la table `users`.
 */
class UserService {
    protected $userDao;
    

    public function __construct() {
        $this->userDao = new UserDAO();
    }

    /**
     * Récupère tous les utilisateurs, avec un filtre d'activation.
     *
     * @param bool|null $active
     * @return array
     */
    public function getAllUsers($active = null) {
        return $this->userDao->findByActiveStatus($active, 'last_name ASC');
    }

    /**
     * Récupère un utilisateur par identifiant.
     *
     * @param int $id
     * @return array|null
     */
    public function getUserById($id) {
        return $this->userDao->findById($id);
    }

    /**
     * Crée un nouvel utilisateur.
     *
     * @param array $data
     * @return mixed ID du nouvel utilisateur ou false
     */
    public function createUser(array $data) {
        if (isset($data['password'])) {
            $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
        }
        return $this->userDao->create($data);
    }

    /**
     * Met à jour un utilisateur.
     *
     * @param int $id
     * @param array $data
     * @return int Nombre de lignes affectées
     */
    public function updateUser($id, array $data) {
        if (isset($data['password'])) {
            $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
        }
        return $this->userDao->update($id, $data);
    }

    /**
     * Supprime un utilisateur.
     *
     * @param int $id
     * @return int Nombre de lignes supprimées
     */
     public function deleteUser($id) {
        return $this->userDao->delete($id);
      }
    

    /**
     * Active ou désactive un utilisateur.
     *
     * @param int $id
     * @param bool $active
     * @return int
     */
    public function setActive($id, $active) {
        return $this->userDao->update($id, ['is_active' => $active ? 1 : 0]);
    }

    /**
     * Récupère les utilisateurs par rôle.
     *
     * @param string $role
     * @param bool|null $active
     * @return array
     */
    public function getUsersByRole($role, $active = null) {
        return $this->userDao->findByRole($role, $active);
    }

    /**
     * Récupère les utilisateurs avec des informations associées comme le département.
     *
     * @param int|null $departmentId
     * @return array
     */
    public function getUsersWithEmployeeInfo($departementId = null) {
        return $this->userDao->getUsersWithEmployeeInfo($departementId);
    }
}
