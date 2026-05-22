<?php
include_once 'Database.php';

/**
 * Classe DAO (Data Access Object) abstraite
 * 
 * Fournit les opérations CRUD de base pour toutes les entités de la base de données.
 * Cette classe doit être étendue par les DAO spécifiques à chaque entité.
 * 
 * @author Bridge School Team
 * @version 1.0.0
 */
abstract class DAO {
    /** @var mixed Instance de la connexion à la base de données */
    protected $db;
    
    /** @var string Nom de la table de la base de données */
    protected $table;
    
    /** @var string Nom de la clé primaire (par défaut: 'id') */
    protected $primaryKey = 'id';
    
    /**
     * Constructeur - Initialise la connexion à la base de données
     * 
     * Récupère l'instance singleton de la base de données pour éviter
     * les connexions multiples et optimiser les performances.
     */
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    // ==================== CRUD DE BASE ====================
    
    /**
     * Récupère tous les enregistrements de la table
     * 
     * @param string|null $orderBy Clause ORDER BY optionnelle (ex: "nom ASC")
     * @return array Liste de tous les enregistrements
     */
    public function findAll($orderBy = null) {
        $sql = "SELECT * FROM {$this->table}";
        if ($orderBy) {
            $sql .= " ORDER BY {$orderBy}";
        }
        return $this->db->fetchAll($sql);
    }
    
    /**
     * Récupère un enregistrement par son identifiant
     * 
     * @param mixed $id Identifiant de l'enregistrement à récupérer
     * @return array|null Enregistrement trouvé ou null si non trouvé
     */
    public function findById($id) {
        $sql = "SELECT * FROM {$this->table} WHERE {$this->primaryKey} = ?";
        return $this->db->fetchOne($sql, [$id]);
    }

    /**
     * Alias de la méthode findById pour compatibilité
     * 
     * @param mixed $id Identifiant de l'enregistrement à récupérer
     * @return array|null Enregistrement trouvé ou null si non trouvé
     */
    public function find($id) {
        return $this->findById($id);
    }
    
    /**
     * Crée un nouvel enregistrement dans la table
     * 
     * @param array $data Données à insérer (associatif: colonne => valeur)
     * @return mixed ID du nouvel enregistrement créé
     */
    public function create($data) {
        return $this->db->insert($this->table, $data);
    }
    
    /**
     * Met à jour un enregistrement existant
     * 
     * @param mixed $id Identifiant de l'enregistrement à modifier
     * @param array $data Données à mettre à jour (associatif: colonne => valeur)
     * @return int Nombre de lignes affectées par la mise à jour
     */
    public function update($id, $data) {
        return $this->db->update($this->table, $data, "{$this->primaryKey} = ?", [$id]);
    }
    
    /**
     * Supprime un enregistrement de la table
     * 
     * @param mixed $id Identifiant de l'enregistrement à supprimer
     * @return int Nombre de lignes affectées par la suppression
     */
    public function delete($id) {
        return $this->db->delete($this->table, "{$this->primaryKey} = ?", [$id]);
    }
    
    // ==================== MÉTHODES UTILITAIRES ====================
    
    /**
     * Compte le nombre d'enregistrements dans la table
     * 
     * @param string|null $where Clause WHERE optionnelle pour filtrer les résultats
     * @param array $params Paramètres pour la clause WHERE (prévention injection SQL)
     * @return int Nombre total d'enregistrements
     */
    public function count($where = null, $params = []) {
        $sql = "SELECT COUNT(*) as total FROM {$this->table}";
        if ($where) {
            $sql .= " WHERE {$where}";
        }
        $result = $this->db->fetchOne($sql, $params);
        return (int)$result['total'];
    }
    
    /**
     * Récupère les enregistrements avec pagination
     * 
     * @param int $page Numéro de la page (commence à 1)
     * @param int $perPage Nombre d'enregistrements par page
     * @param string|null $where Clause WHERE optionnelle pour filtrer les résultats
     * @param array $params Paramètres pour la clause WHERE
     * @return array Tableau contenant les données paginées et les métadonnées
     */
    public function paginate($page = 1, $perPage = 10, $where = null, $params = []) {
        $offset = ($page - 1) * $perPage;
        $sql = "SELECT * FROM {$this->table}";
        if ($where) {
            $sql .= " WHERE {$where}";
        }
        $sql .= " LIMIT {$perPage} OFFSET {$offset}";
        $data = $this->db->fetchAll($sql, $params);
        $total = $this->count($where, $params);
        
        return [
            'data' => $data,
            'current_page' => $page,
            'per_page' => $perPage,
            'total' => $total,
            'last_page' => ceil($total / $perPage)
        ];
    }
}
?>