<?php
class Announcement
{
    private $conn;
    private $table = "announcements";

    public $announcement_id;
    public $title;
    public $details;
    public $date_posted;
    public $status;
    public $is_urgent;
    public $user_id;
    public $category_id;

    public function __construct($db)
    {
        $this->conn = $db;
    }

    // CREATE
    public function addAnnouncement()
    {
        $query = "INSERT INTO {$this->table}
                    (title, details, status, is_urgent, user_id, category_id)
                  VALUES
                    (:title, :details, :status, :is_urgent, :user_id, :category_id)";

        $stmt = $this->conn->prepare($query);

        $this->title   = htmlspecialchars(strip_tags($this->title));
        $this->details = htmlspecialchars(strip_tags($this->details));

        $stmt->bindParam(':title', $this->title);
        $stmt->bindParam(':details', $this->details);
        $stmt->bindParam(':status', $this->status);
        $stmt->bindParam(':is_urgent', $this->is_urgent, PDO::PARAM_INT);
        $stmt->bindParam(':user_id', $this->user_id, PDO::PARAM_INT);
        $stmt->bindParam(':category_id', $this->category_id, PDO::PARAM_INT);

        if ($stmt->execute()) {
            return $this->conn->lastInsertId();
        }
        return false;
    }

    // READ (all)
    public function getAnnouncements()
    {
        $query = "SELECT a.*, c.category_name,
                         CONCAT(u.first_name, ' ', u.last_name) AS posted_by_name
                  FROM {$this->table} a
                  LEFT JOIN categories c ON a.category_id = c.category_id
                  LEFT JOIN users u ON a.user_id = u.user_id";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    // READ (single) — used to pre-fill the update form
    public function getAnnouncementById($id)
    {
        $query = "SELECT * FROM {$this->table} WHERE announcement_id = :id LIMIT 1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch();
    }

    // UPDATE
    public function updateAnnouncement($id)
    {
        $query = "UPDATE {$this->table}
                  SET title = :title,
                      details = :details,
                      status = :status,
                      is_urgent = :is_urgent,
                      user_id = :user_id,
                      category_id = :category_id
                  WHERE announcement_id = :id";

        $stmt = $this->conn->prepare($query);

        $this->title   = htmlspecialchars(strip_tags($this->title));
        $this->details = htmlspecialchars(strip_tags($this->details));

        $stmt->bindParam(':title', $this->title);
        $stmt->bindParam(':details', $this->details);
        $stmt->bindParam(':status', $this->status);
        $stmt->bindParam(':is_urgent', $this->is_urgent, PDO::PARAM_INT);
        $stmt->bindParam(':user_id', $this->user_id, PDO::PARAM_INT);
        $stmt->bindParam(':category_id', $this->category_id, PDO::PARAM_INT);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);

        return $stmt->execute();
    }

    // DELETE
    public function deleteAnnouncement($id)
    {
        $query = "DELETE FROM {$this->table} WHERE announcement_id = :id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    // SEARCH
       public function searchAnnouncements($keyword)
    {
        $query = "SELECT a.*, c.category_name,
                         CONCAT(u.first_name, ' ', u.last_name) AS posted_by_name
                  FROM {$this->table} a
                  LEFT JOIN categories c ON a.category_id = c.category_id
                  LEFT JOIN users u ON a.user_id = u.user_id
                  WHERE a.title LIKE :kw1
                     OR a.details LIKE :kw2
                     OR a.status LIKE :kw3
                     OR c.category_name LIKE :kw4
                     OR u.first_name LIKE :kw5
                     OR u.last_name LIKE :kw6
                  ORDER BY a.date_posted DESC";

        $stmt = $this->conn->prepare($query);
        $searchTerm = "%" . $keyword . "%";
        $stmt->bindParam(':kw1', $searchTerm, PDO::PARAM_STR);
        $stmt->bindParam(':kw2', $searchTerm, PDO::PARAM_STR);
        $stmt->bindParam(':kw3', $searchTerm, PDO::PARAM_STR);
        $stmt->bindParam(':kw4', $searchTerm, PDO::PARAM_STR);
        $stmt->bindParam(':kw5', $searchTerm, PDO::PARAM_STR);
        $stmt->bindParam(':kw6', $searchTerm, PDO::PARAM_STR);
        $stmt->execute();
        return $stmt->fetchAll();
    }

        // DASHBOARD: total count of active announcements
    public function getTotalActiveCount()
    {
        $query = "SELECT COUNT(*) AS total FROM {$this->table} WHERE status = 'Active'";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $result = $stmt->fetch();
        return $result['total'];
    }

    // DASHBOARD: count of announcements grouped by category
    public function getCountByCategory()
    {
        $query = "SELECT c.category_name, COUNT(a.announcement_id) AS total
                  FROM categories c
                  LEFT JOIN {$this->table} a ON a.category_id = c.category_id AND a.status = 'Active'
                  GROUP BY c.category_id, c.category_name
                  ORDER BY total DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}