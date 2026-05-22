<?php

include_once "DAO.php";

class NotificationsDAO extends DAO{

    protected $table = 'notifications';
    protected $primaryKey = 'id';

    /**
     * Récupère les notifications d'un utilisateur.
     *
     * @param int $userId
     * @param string|null $orderBy
     * @return array
     */
    public function findByUser($userId, $orderBy = null) {
        $sql = "SELECT * FROM {$this->table} WHERE user_id = ?";
        if ($orderBy) {
            $sql .= " ORDER BY {$orderBy}";
        }
        return $this->db->fetchAll($sql, [$userId]);
    }

    /**
     * Récupère les notifications non lues.
     *
     * @param int|null $userId
     * @return array
     */
    public function findUnread($userId = null) {
        $sql = "SELECT * FROM {$this->table} WHERE is_read = 0";
        $params = [];

        if ($userId !== null) {
            $sql .= " AND user_id = ?";
            $params[] = $userId;
        }

        return $this->db->fetchAll($sql, $params);
    }

    /**
     * Marque une notification comme lue.
     *
     * @param int $id
     * @return int
     */
    public function markAsRead($id) {
        return $this->update($id, ['is_read' => 1]);
    }

    /**
     * Marque toutes les notifications d'un utilisateur comme lues.
     *
     * @param int $userId
     * @return int
     */
    public function markAllAsRead($userId) {
        return $this->db->update($this->table, ['is_read' => 1], 'user_id = ?', [$userId]);
    }

    /**
     * Récupère un résumé par utilisateur.
     *
     * @param int|null $userId
     * @return array|null
     */
    public function getNotificationSummary($userId = null) {
        $sql = "SELECT COUNT(*) AS total,
                       SUM(CASE WHEN is_read = 0 THEN 1 ELSE 0 END) AS unreadCount
                FROM {$this->table}";
        $params = [];

        if ($userId !== null) {
            $sql .= " WHERE user_id = ?";
            $params[] = $userId;
        }

        return $this->db->fetchOne($sql, $params);
    }

    /**
     * Récupère les notifications actives ou par type.
     *
     * @param int|null $userId
     * @param string|null $type
     * @return array
     */
    public function findByType($userId = null, $type = null) {
        $sql = "SELECT * FROM {$this->table}";
        $params = [];
        $conditions = [];

        if ($userId !== null) {
            $conditions[] = "user_id = ?";
            $params[] = $userId;
        }
        if ($type !== null) {
            $conditions[] = "type = ?";
            $params[] = $type;
        }

        if (!empty($conditions)) {
            $sql .= " WHERE " . implode(' AND ', $conditions);
        }

        return $this->db->fetchAll($sql, $params);
    }

}















?>














