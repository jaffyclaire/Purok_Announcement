<?php
require_once __DIR__ . '/ImageUploader.php';

class Event
{
    private $conn;
    private $table = "events";

    public $event_id;
    public $title;
    public $description;
    public $event_date;
    public $location;
    public $image_path;
    public $created_by;
    public $created_at;

    public function __construct($db)
    {
        $this->conn = $db;
    }

    // Next 3 events where event_date >= NOW(), ordered soonest first
    public function getUpcomingStrip($limit = 3)
    {
        $query = "SELECT e.*, CONCAT(u.first_name, ' ', u.last_name) AS creator_name
                  FROM {$this->table} e
                  LEFT JOIN users u ON e.created_by = u.user_id
                  WHERE e.event_date >= NOW()
                  ORDER BY e.event_date ASC
                  LIMIT :lim";
        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(':lim', (int) $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    // All upcoming events where event_date >= NOW(), ordered soonest first
    public function getAllUpcoming()
    {
        $query = "SELECT e.*, CONCAT(u.first_name, ' ', u.last_name) AS creator_name
                  FROM {$this->table} e
                  LEFT JOIN users u ON e.created_by = u.user_id
                  WHERE e.event_date >= NOW()
                  ORDER BY e.event_date ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    // Past events where event_date < NOW(), ordered most recent past first
    public function getPastEvents()
    {
        $query = "SELECT e.*, CONCAT(u.first_name, ' ', u.last_name) AS creator_name
                  FROM {$this->table} e
                  LEFT JOIN users u ON e.created_by = u.user_id
                  WHERE e.event_date < NOW()
                  ORDER BY e.event_date DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getEventById($id)
    {
        $query = "SELECT e.*, CONCAT(u.first_name, ' ', u.last_name) AS creator_name
                  FROM {$this->table} e
                  LEFT JOIN users u ON e.created_by = u.user_id
                  WHERE e.event_id = :id
                  LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch();
    }

    // TRANSACTION - event: Create event and notify other users
    public function createWithNotifications($authorId, $uploadedFile = null)
    {
        $imageValidation = null;
        if ($uploadedFile && isset($uploadedFile['error']) && $uploadedFile['error'] !== UPLOAD_ERR_NO_FILE) {
            $imageValidation = ImageUploader::validate($uploadedFile, 'events');
            if (!$imageValidation['valid']) {
                throw new RuntimeException($imageValidation['error']);
            }
        }

        $movedFilePath = null;

        // TRANSACTION - event
        try {
            $this->conn->beginTransaction();

            $imageRelPath = ($imageValidation && $imageValidation['valid']) ? $imageValidation['relative_path'] : null;

            $query = "INSERT INTO {$this->table}
                        (title, description, event_date, location, image_path, created_by)
                      VALUES
                        (:title, :description, :event_date, :location, :image_path, :created_by)";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([
                ':title'       => htmlspecialchars(strip_tags((string) $this->title)),
                ':description' => htmlspecialchars(strip_tags((string) $this->description)),
                ':event_date'  => $this->event_date,
                ':location'    => htmlspecialchars(strip_tags((string) $this->location)),
                ':image_path'  => $imageRelPath,
                ':created_by'  => (int) $authorId,
            ]);

            $eventId = (int) $this->conn->lastInsertId();
            if ($eventId < 1) {
                throw new RuntimeException('The event insert did not return an ID.');
            }

            // Notify other users using notifications code and message column
            $message = 'Upcoming event: ' . $this->title . ' on ' . date('M d, Y', strtotime($this->event_date));
            $notifQuery = "INSERT INTO notifications
                            (is_read, sent_at, event_id, user_id, message)
                           SELECT 0, NOW(), :event_id, user_id, :message
                           FROM users
                           WHERE user_id <> :author_id";
            $notifStmt = $this->conn->prepare($notifQuery);
            $notifStmt->execute([
                ':event_id'  => $eventId,
                ':message'   => $message,
                ':author_id' => (int) $authorId,
            ]);

            if ($imageValidation && $imageValidation['valid']) {
                ImageUploader::moveFile($imageValidation);
                $movedFilePath = $imageValidation['target_path'];
            }

            $this->conn->commit();
            return $eventId;
        } catch (Throwable $e) {
            if ($this->conn->inTransaction()) {
                $this->conn->rollBack();
            }
            if ($movedFilePath && file_exists($movedFilePath)) {
                @unlink($movedFilePath);
            }
            throw $e;
        }
    }

    public function updateEvent($id, $authorId, $uploadedFile = null)
    {
        $imageValidation = null;
        if ($uploadedFile && isset($uploadedFile['error']) && $uploadedFile['error'] !== UPLOAD_ERR_NO_FILE) {
            $imageValidation = ImageUploader::validate($uploadedFile, 'events');
            if (!$imageValidation['valid']) {
                throw new RuntimeException($imageValidation['error']);
            }
        }

        $movedFilePath = null;
        $oldFileToDelete = null;

        try {
            $this->conn->beginTransaction();

            $existing = $this->getEventById($id);
            if (!$existing) {
                throw new RuntimeException('The event no longer exists.');
            }

            $imageRelPath = $existing['image_path'];
            if ($imageValidation && $imageValidation['valid']) {
                $imageRelPath = $imageValidation['relative_path'];
                $oldFileToDelete = $existing['image_path'];
            }

            $query = "UPDATE {$this->table}
                      SET title = :title,
                          description = :description,
                          event_date = :event_date,
                          location = :location,
                          image_path = :image_path
                      WHERE event_id = :id";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([
                ':title'       => htmlspecialchars(strip_tags((string) $this->title)),
                ':description' => htmlspecialchars(strip_tags((string) $this->description)),
                ':event_date'  => $this->event_date,
                ':location'    => htmlspecialchars(strip_tags((string) $this->location)),
                ':image_path'  => $imageRelPath,
                ':id'          => (int) $id,
            ]);

            if ($imageValidation && $imageValidation['valid']) {
                ImageUploader::moveFile($imageValidation);
                $movedFilePath = $imageValidation['target_path'];
            }

            $this->conn->commit();

            if ($oldFileToDelete) {
                ImageUploader::deleteFile($oldFileToDelete);
            }

            return true;
        } catch (Throwable $e) {
            if ($this->conn->inTransaction()) {
                $this->conn->rollBack();
            }
            if ($movedFilePath && file_exists($movedFilePath)) {
                @unlink($movedFilePath);
            }
            throw $e;
        }
    }

    // TRANSACTION - event: Delete event and related notifications
    public function deleteWithNotifications($id)
    {
        $fileToDelete = null;

        // TRANSACTION - event
        try {
            $this->conn->beginTransaction();

            $existing = $this->getEventById($id);
            if ($existing && !empty($existing['image_path'])) {
                $fileToDelete = $existing['image_path'];
            }

            $deleteNotifications = $this->conn->prepare(
                "DELETE FROM notifications WHERE event_id = :id"
            );
            $deleteNotifications->execute([':id' => (int) $id]);

            $deleteEvent = $this->conn->prepare(
                "DELETE FROM {$this->table} WHERE event_id = :id"
            );
            $deleteEvent->execute([':id' => (int) $id]);
            if ($deleteEvent->rowCount() !== 1) {
                throw new RuntimeException('The event no longer exists.');
            }

            $this->conn->commit();

            if ($fileToDelete) {
                ImageUploader::deleteFile($fileToDelete);
            }

            return true;
        } catch (Throwable $e) {
            if ($this->conn->inTransaction()) {
                $this->conn->rollBack();
            }
            throw $e;
        }
    }
}
