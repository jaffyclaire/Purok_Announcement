<?php
class Notification
{
    private $conn;
    private $table = "notifications";

    public function __construct($db)
    {
        $this->conn = $db;
    }

    // Create one notification row per existing user, for a given announcement
    public function createForAllUsers($announcement_id, $excludeUserId = null)
    {
        $query = "SELECT user_id FROM users";
        if ($excludeUserId) {
            $query .= " WHERE user_id != :excludeId";
        }
        $stmt = $this->conn->prepare($query);
        if ($excludeUserId) {
            $stmt->bindParam(':excludeId', $excludeUserId, PDO::PARAM_INT);
        }
        $stmt->execute();
        $allUsers = $stmt->fetchAll();

        $insertQuery = "INSERT INTO {$this->table} (is_read, sent_at, announcement_id, user_id)
                         VALUES (0, NOW(), :announcement_id, :user_id)";
        $insertStmt = $this->conn->prepare($insertQuery);

        foreach ($allUsers as $u) {
            $insertStmt->bindParam(':announcement_id', $announcement_id, PDO::PARAM_INT);
            $insertStmt->bindParam(':user_id', $u['user_id'], PDO::PARAM_INT);
            $insertStmt->execute();
        }
    }

    // Count how many unread notifications a specific user has
    public function getUnreadCount($user_id)
    {
        $query = "SELECT COUNT(*) AS total FROM {$this->table} WHERE user_id = :user_id AND is_read = 0";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch()['total'];
    }

    // Get the most recent notifications for the dropdown, joined with announcement info
    public function getRecentForUser($user_id, $limit = 5)
    {
        $query = "SELECT n.notification_id, n.is_read, n.sent_at, n.announcement_id, n.event_id, n.message,
                         a.title, a.is_urgent
                  FROM {$this->table} n
                  LEFT JOIN announcements a ON n.announcement_id = a.announcement_id
                  WHERE n.user_id = :user_id
                  ORDER BY n.sent_at DESC
                  LIMIT :lim";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->bindParam(':lim', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    // Get all notifications for a user, newest first
    public function getAllForUser($user_id)
    {
        $query = "SELECT n.notification_id, n.is_read, n.sent_at, n.announcement_id, n.event_id, n.message,
                         a.title, a.is_urgent
                  FROM {$this->table} n
                  LEFT JOIN announcements a ON n.announcement_id = a.announcement_id
                  WHERE n.user_id = :user_id
                  ORDER BY n.sent_at DESC, n.notification_id DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    // Mark a single notification as read
    public function markAsRead($notification_id, $user_id)
    {
        $query = "UPDATE {$this->table} SET is_read = 1 WHERE notification_id = :id AND user_id = :user_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $notification_id, PDO::PARAM_INT);
        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    // Mark all of a user's notifications as read
    public function markAllAsRead($user_id)
    {
        $query = "UPDATE {$this->table} SET is_read = 1 WHERE user_id = :user_id AND is_read = 0";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        return $stmt->execute();
    }
}
