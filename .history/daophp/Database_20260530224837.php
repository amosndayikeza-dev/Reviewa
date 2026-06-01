<?php
/**
 * Classe Database - Gestionnaire de connexion à la base de données
 * 
 * Implémente le pattern Singleton pour garantir une seule connexion à la BDD.
 * Fournit une interface simplifiée pour les opérations CRUD et transactions.
 * 
 * @author Bridge School Team
 * @version 1.0.0
 */
class Database {
    /** @var Database|null Instance unique de la classe (Singleton) */
    private static $instance = null;
    
    /** @var PDO Instance de la connexion PDO */
    private $pdo;
    
    // Configuration de la base de données
    /** @var string Hôte du serveur MySQL */
    private $host = 'localhost';
    
    /** @var string Nom de la base de données */
    private $dbname ='1000saveurs';
    
    /** @var string Nom d'utilisateur MySQL */
    private $username = 'root';
    
    /** @var string Mot de passe MySQL */
    private $password = '';
    
    /** @var string Encodage des caractères */
    private $charset = 'utf8mb4';
    
    /**
     * Constructeur privé - Pattern Singleton
     * 
     * Initialise la connexion PDO avec des options de sécurité optimales.
     * Privé pour empêcher l'instanciation directe de la classe.
     * 
     * @throws PDOException En cas d'échec de connexion
     */
    public function __construct() {
        try {
            $dsn = "mysql:host={$this->host};dbname={$this->dbname};charset={$this->charset}";
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,        // Exceptions pour les erreurs
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,   // Tableaux associatifs par défaut
                PDO::ATTR_EMULATE_PREPARES => false                  // Requêtes préparées natives
            ];
            $this->pdo = new PDO($dsn, $this->username, $this->password, $options);
        } catch (PDOException $e) {
            throw $e;
        }
    }
    
    /**
     * Récupère l'instance unique de la classe (Singleton)
     * 
     * @return Database Instance de la connexion à la base de données
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Récupère l'objet PDO brut pour les requêtes avancées
     * 
     * @return PDO Instance PDO de la connexion
     */
    public function getConnection() {
        return $this->pdo;
    }
    
    // ==================== MÉTHODES DE REQUÊTES ====================
    
    /**
     * Exécute une requête SQL avec paramètres
     * 
     * @param string $sql Requête SQL à exécuter
     * @param array $params Paramètres liés à la requête (prévention injection SQL)
     * @return PDOStatement Statement PDO pour récupérer les résultats
     */
    public function query($sql, $params = []) {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }
    
    /**
     * Récupère toutes les lignes d'une requête
     * 
     * @param string $sql Requête SQL
     * @param array $params Paramètres de la requête
     * @return array Tableau de tous les résultats
     */
    public function fetchAll($sql, $params = []) {
        return $this->query($sql, $params)->fetchAll();
    }
    
    /**
     * Récupère une seule ligne d'une requête
     * 
     * @param string $sql Requête SQL
     * @param array $params Paramètres de la requête
     * @return array|null Ligne trouvée ou null
     */
    public function fetchOne($sql, $params = []) {
        return $this->query($sql, $params)->fetch();
    }

    /**
     * Alias de fetchOne pour compatibilité
     * 
     * @param string $sql Requête SQL
     * @param array $params Paramètres de la requête
     * @return array|null Ligne trouvée ou null
     */
    public function fetch($sql, $params = []) {
        return $this->fetchOne($sql, $params);
    }
    
    // ==================== MÉTHODES CRUD ====================
    
    /**
     * Insère une nouvelle ligne dans une table
     * 
     * @param string $table Nom de la table
     * @param array $data Données à insérer (associatif: colonne => valeur)
     * @return string ID de la dernière ligne insérée
     */
    public function insert($table, $data) {
        $fields = array_keys($data);
        $placeholders = array_fill(0, count($fields), '?');
        $sql = "INSERT INTO {$table} (" . implode(', ', $fields) . ") 
                VALUES (" . implode(', ', $placeholders) . ")";
        $this->query($sql, array_values($data));
        return $this->pdo->lastInsertId();
    }
    
    /**
     * Met à jour des lignes dans une table
     * 
     * @param string $table Nom de la table
     * @param array $data Données à mettre à jour (associatif: colonne => valeur)
     * @param string $where Clause WHERE pour filtrer les lignes à modifier
     * @param array $whereParams Paramètres pour la clause WHERE
     * @return int Nombre de lignes affectées
     */
    public function update($table, $data, $where, $whereParams = []) {
        $set = array_map(fn($field) => "{$field} = ?", array_keys($data));
        $sql = "UPDATE {$table} SET " . implode(', ', $set) . " WHERE {$where}";
        $params = array_merge(array_values($data), $whereParams);
        return $this->query($sql, $params)->rowCount();
    }
    
    /**
     * Supprime des lignes d'une table
     * 
     * @param string $table Nom de la table
     * @param string $where Clause WHERE pour filtrer les lignes à supprimer
     * @param array $params Paramètres pour la clause WHERE
     * @return int Nombre de lignes supprimées
     */
    public function delete($table, $where, $params = []) {
        $sql = "DELETE FROM {$table} WHERE {$where}";
        return $this->query($sql, $params)->rowCount();
    }
    
    // ==================== GESTION DES TRANSACTIONS ====================
    
    /**
     * Démarre une transaction
     * 
     * @return bool True si la transaction a démarré avec succès
     */
    public function beginTransaction() {
        return $this->pdo->beginTransaction();
    }
    
    /**
     * Valide une transaction en cours
     * 
     * @return bool True si la transaction a été validée avec succès
     */
    public function commit() {
        return $this->pdo->commit();
    }
    
    /**
     * Annule une transaction en cours
     * 
     * @return bool True si la transaction a été annulée avec succès
     */
    public function rollback() {
        return $this->pdo->rollBack();
    }
}
