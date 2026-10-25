<?php
require_once __DIR__ . '/ImageUploader.php';

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

        $this->title   = htmlspecialchars(strip_tags((string) $this->title));
        $this->details = htmlspecialchars(strip_tags((string) $this->details));

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

    // TRANSACTION 1 - Create an announcement and notify every other user.
    public function createWithNotifications($authorId, $uploadedFiles = null)
    {
        $validatedFiles = [];
        if (!empty($uploadedFiles)) {
            $multiRes = ImageUploader::validateMultiple($uploadedFiles, 'announcements', 5);
            if (!$multiRes['valid']) {
                throw new RuntimeException($multiRes['error']);
            }
            $validatedFiles = $multiRes['files'];
        }

        $movedFiles = [];

        try {
            $this->conn->beginTransaction();

            $query = "INSERT INTO {$this->table}
                        (title, details, status, is_urgent, user_id, category_id)
                      VALUES
                        (:title, :details, :status, :is_urgent, :user_id, :category_id)";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([
                ':title' => htmlspecialchars(strip_tags((string) $this->title)),
                ':details' => htmlspecialchars(strip_tags((string) $this->details)),
                ':status' => $this->status,
                ':is_urgent' => (int) $this->is_urgent,
                ':user_id' => (int) $authorId,
                ':category_id' => (int) $this->category_id,
            ]);

            $announcementId = (int) $this->conn->lastInsertId();
            if ($announcementId < 1) {
                throw new RuntimeException('The announcement insert did not return an ID.');
            }

            if (!empty($validatedFiles)) {
                $attachStmt = $this->conn->prepare(
                    "INSERT INTO attachments (file_type, file_path, date_uploaded, announcement_id)
                     VALUES (:file_type, :file_path, NOW(), :announcement_id)"
                );
                foreach ($validatedFiles as $vFile) {
                    $attachStmt->execute([
                        ':file_type'       => $vFile['mime'],
                        ':file_path'       => $vFile['relative_path'],
                        ':announcement_id' => $announcementId,
                    ]);
                }
            }

            $this->insertNotificationsForOtherUsers(
                $announcementId,
                $authorId,
                'New announcement: ' . $this->title
            );

            foreach ($validatedFiles as $vFile) {
                ImageUploader::moveFile($vFile);
                $movedFiles[] = $vFile['target_path'];
            }

            $this->conn->commit();
            return $announcementId;
        } catch (Throwable $e) {
            if ($this->conn->inTransaction()) {
                $this->conn->rollBack();
            }
            foreach ($movedFiles as $targetPath) {
                if (file_exists($targetPath)) {
                    @unlink($targetPath);
                }
            }
            throw $e;
        }
    }

    // Helper: Efficiently fetch all images for visible announcements in ONE single query
    public function attachImagesToAnnouncements(array &$announcements)
    {
        if (empty($announcements)) {
            return;
        }

        $announcementIds = array_values(array_unique(array_filter(array_column($announcements, 'announcement_id'))));
        if (empty($announcementIds)) {
            return;
        }

        $placeholders = implode(',', array_fill(0, count($announcementIds), '?'));
        $query = "SELECT attachment_id, file_path, file_type, announcement_id
                  FROM attachments
                  WHERE announcement_id IN ($placeholders)
                  ORDER BY attachment_id ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute($announcementIds);
        $allAttachments = $stmt->fetchAll();

        $grouped = [];
        foreach ($allAttachments as $att) {
            $grouped[$att['announcement_id']][] = $att;
        }

        foreach ($announcements as &$ann) {
            $aid = $ann['announcement_id'];
            $ann['images'] = $grouped[$aid] ?? [];
            $ann['image_path'] = !empty($ann['images']) ? $ann['images'][0]['file_path'] : null;
        }
        unset($ann);
    }

    public function getAttachmentsByAnnouncementId($announcementId)
    {
        $query = "SELECT attachment_id, file_path, file_type, date_uploaded
                  FROM attachments
                  WHERE announcement_id = :id
                  ORDER BY attachment_id ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([':id' => (int) $announcementId]);
        return $stmt->fetchAll();
    }

    // READ (all) with author avatar, grouped image attachments, and reaction counts
    public function getAnnouncements($currentUserId = 0)
    {
        $query = "SELECT a.*, c.category_name,
                         CONCAT(u.first_name, ' ', u.last_name) AS posted_by_name,
                         u.avatar_path,
                         COALESCE(r_count.total_reactions, 0) AS reaction_count,
                         CASE WHEN r_user.user_id IS NOT NULL THEN 1 ELSE 0 END AS user_reacted
                  FROM {$this->table} a
                  LEFT JOIN categories c ON a.category_id = c.category_id
                  LEFT JOIN users u ON a.user_id = u.user_id
                  LEFT JOIN (
                      SELECT announcement_id, COUNT(*) AS total_reactions
                      FROM reactions
                      GROUP BY announcement_id
                  ) r_count ON a.announcement_id = r_count.announcement_id
                  LEFT JOIN reactions r_user ON a.announcement_id = r_user.announcement_id AND r_user.user_id = :current_user_id
                  ORDER BY a.date_posted DESC, a.announcement_id DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(':current_user_id', (int) $currentUserId, PDO::PARAM_INT);
        $stmt->execute();
        $announcements = $stmt->fetchAll();

        $this->attachImagesToAnnouncements($announcements);
        return $announcements;
    }

    // READ (single) — used to pre-fill the update form
    public function getAnnouncementById($id)
    {
        $query = "SELECT a.*, c.category_name
                  FROM {$this->table} a
                  LEFT JOIN categories c ON a.category_id = c.category_id
                  WHERE a.announcement_id = :id
                  LIMIT 1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch();
        if ($row) {
            $row['images'] = $this->getAttachmentsByAnnouncementId($id);
            $row['image_path'] = !empty($row['images']) ? $row['images'][0]['file_path'] : null;
        }
        return $row;
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

        $this->title   = htmlspecialchars(strip_tags((string) $this->title));
        $this->details = htmlspecialchars(strip_tags((string) $this->details));

        $stmt->bindParam(':title', $this->title);
        $stmt->bindParam(':details', $this->details);
        $stmt->bindParam(':status', $this->status);
        $stmt->bindParam(':is_urgent', $this->is_urgent, PDO::PARAM_INT);
        $stmt->bindParam(':user_id', $this->user_id, PDO::PARAM_INT);
        $stmt->bindParam(':category_id', $this->category_id, PDO::PARAM_INT);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);

        return $stmt->execute();
    }

    // TRANSACTION 2 - Update an announcement, attachments, and notify every other user.
    public function updateWithNotifications($id, $authorId, $uploadedFiles = null, $removedAttachmentIds = [])
    {
        $existing = $this->getAttachmentsByAnnouncementId($id);
        $removedAttachmentIds = array_map('intval', (array) $removedAttachmentIds);
        $filesToDelete = [];
        $keptCount = 0;

        foreach ($existing as $att) {
            if (in_array((int)$att['attachment_id'], $removedAttachmentIds, true)) {
                if (!empty($att['file_path'])) {
                    $filesToDelete[] = $att['file_path'];
                }
            } else {
                $keptCount++;
            }
        }

        $normalizedNew = ImageUploader::normalizeFiles($uploadedFiles);
        $newCount = count($normalizedNew);

        if ($keptCount + $newCount > 5) {
            throw new RuntimeException("Maximum 5 images allowed per announcement. You have {$keptCount} existing and selected {$newCount} new image(s).");
        }

        $validatedNewFiles = [];
        if ($newCount > 0) {
            $valRes = ImageUploader::validateMultiple($uploadedFiles, 'announcements', 5);
            if (!$valRes['valid']) {
                throw new RuntimeException($valRes['error']);
            }
            $validatedNewFiles = $valRes['files'];
        }

        $movedFiles = [];

        try {
            $this->conn->beginTransaction();

            $check = $this->conn->prepare(
                "SELECT announcement_id FROM {$this->table} WHERE announcement_id = :id FOR UPDATE"
            );
            $check->execute([':id' => (int) $id]);
            if (!$check->fetchColumn()) {
                throw new RuntimeException('The announcement no longer exists.');
            }

            $query = "UPDATE {$this->table}
                      SET title = :title,
                          details = :details,
                          status = :status,
                          is_urgent = :is_urgent,
                          user_id = :user_id,
                          category_id = :category_id
                      WHERE announcement_id = :id";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([
                ':title'       => htmlspecialchars(strip_tags((string) $this->title)),
                ':details'     => htmlspecialchars(strip_tags((string) $this->details)),
                ':status'      => $this->status,
                ':is_urgent'   => (int) $this->is_urgent,
                ':user_id'     => (int) $authorId,
                ':category_id' => (int) $this->category_id,
                ':id'          => (int) $id,
            ]);

            if (!empty($removedAttachmentIds)) {
                $delPlaceholders = implode(',', array_fill(0, count($removedAttachmentIds), '?'));
                $delStmt = $this->conn->prepare(
                    "DELETE FROM attachments WHERE announcement_id = ? AND attachment_id IN ($delPlaceholders)"
                );
                $delStmt->execute(array_merge([(int) $id], $removedAttachmentIds));
            }

            if (!empty($validatedNewFiles)) {
                $insAttach = $this->conn->prepare(
                    "INSERT INTO attachments (file_type, file_path, date_uploaded, announcement_id)
                     VALUES (:file_type, :file_path, NOW(), :id)"
                );
                foreach ($validatedNewFiles as $vFile) {
                    $insAttach->execute([
                        ':file_type' => $vFile['mime'],
                        ':file_path' => $vFile['relative_path'],
                        ':id'        => (int) $id,
                    ]);
                }
            }

            $this->insertNotificationsForOtherUsers(
                (int) $id,
                $authorId,
                'Announcement updated: ' . $this->title
            );

            foreach ($validatedNewFiles as $vFile) {
                ImageUploader::moveFile($vFile);
                $movedFiles[] = $vFile['target_path'];
            }

            $this->conn->commit();

            foreach ($filesToDelete as $oldFile) {
                ImageUploader::deleteFile($oldFile);
            }

            return true;
        } catch (Throwable $e) {
            if ($this->conn->inTransaction()) {
                $this->conn->rollBack();
            }
            foreach ($movedFiles as $targetPath) {
                if (file_exists($targetPath)) {
                    @unlink($targetPath);
                }
            }
            throw $e;
        }
    }

    // DELETE
    public function deleteAnnouncement($id)
    {
        $query = "DELETE FROM {$this->table} WHERE announcement_id = :id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    // TRANSACTION 3 - Delete an announcement's notifications, reactions, attachments and the announcement.
    public function deleteWithNotifications($id)
    {
        $filesToDelete = [];

        try {
            $this->conn->beginTransaction();

            $attachStmt = $this->conn->prepare(
                "SELECT file_path FROM attachments WHERE announcement_id = :id"
            );
            $attachStmt->execute([':id' => (int) $id]);
            $attachments = $attachStmt->fetchAll();
            foreach ($attachments as $att) {
                if (!empty($att['file_path'])) {
                    $filesToDelete[] = $att['file_path'];
                }
            }

            $deleteNotifications = $this->conn->prepare(
                "DELETE FROM notifications WHERE announcement_id = :id"
            );
            $deleteNotifications->execute([':id' => (int) $id]);

            $deleteReactions = $this->conn->prepare(
                "DELETE FROM reactions WHERE announcement_id = :id"
            );
            $deleteReactions->execute([':id' => (int) $id]);

            $deleteAttachments = $this->conn->prepare(
                "DELETE FROM attachments WHERE announcement_id = :id"
            );
            $deleteAttachments->execute([':id' => (int) $id]);

            $deleteAnnouncement = $this->conn->prepare(
                "DELETE FROM {$this->table} WHERE announcement_id = :id"
            );
            $deleteAnnouncement->execute([':id' => (int) $id]);
            if ($deleteAnnouncement->rowCount() !== 1) {
                throw new RuntimeException('The announcement no longer exists.');
            }

            $this->conn->commit();

            foreach ($filesToDelete as $filePath) {
                ImageUploader::deleteFile($filePath);
            }

            return true;
        } catch (Throwable $e) {
            if ($this->conn->inTransaction()) {
                $this->conn->rollBack();
            }
            throw $e;
        }
    }

    protected function insertNotificationsForOtherUsers($announcementId, $authorId, $message)
    {
        $query = "INSERT INTO notifications
                    (is_read, sent_at, announcement_id, user_id, message)
                  SELECT 0, NOW(), :announcement_id, user_id, :message
                  FROM users
                  WHERE user_id <> :author_id";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([
            ':announcement_id' => (int) $announcementId,
            ':message' => $message,
            ':author_id' => (int) $authorId,
        ]);
    }

    // SEARCH with author avatar, grouped image attachments, and reaction counts
    public function searchAnnouncements($keyword, $currentUserId = 0)
    {
        $query = "SELECT a.*, c.category_name,
                         CONCAT(u.first_name, ' ', u.last_name) AS posted_by_name,
                         u.avatar_path,
                         COALESCE(r_count.total_reactions, 0) AS reaction_count,
                         CASE WHEN r_user.user_id IS NOT NULL THEN 1 ELSE 0 END AS user_reacted
                  FROM {$this->table} a
                  LEFT JOIN categories c ON a.category_id = c.category_id
                  LEFT JOIN users u ON a.user_id = u.user_id
                  LEFT JOIN (
                      SELECT announcement_id, COUNT(*) AS total_reactions
                      FROM reactions
                      GROUP BY announcement_id
                  ) r_count ON a.announcement_id = r_count.announcement_id
                  LEFT JOIN reactions r_user ON a.announcement_id = r_user.announcement_id AND r_user.user_id = :current_user_id
                  WHERE a.title LIKE :kw1
                     OR a.details LIKE :kw2
                     OR a.status LIKE :kw3
                     OR c.category_name LIKE :kw4
                     OR u.first_name LIKE :kw5
                     OR u.last_name LIKE :kw6
                  ORDER BY a.date_posted DESC, a.announcement_id DESC";

        $stmt = $this->conn->prepare($query);
        $searchTerm = "%" . $keyword . "%";
        $stmt->bindValue(':current_user_id', (int) $currentUserId, PDO::PARAM_INT);
        $stmt->bindParam(':kw1', $searchTerm, PDO::PARAM_STR);
        $stmt->bindParam(':kw2', $searchTerm, PDO::PARAM_STR);
        $stmt->bindParam(':kw3', $searchTerm, PDO::PARAM_STR);
        $stmt->bindParam(':kw4', $searchTerm, PDO::PARAM_STR);
        $stmt->bindParam(':kw5', $searchTerm, PDO::PARAM_STR);
        $stmt->bindParam(':kw6', $searchTerm, PDO::PARAM_STR);
        $stmt->execute();
        $announcements = $stmt->fetchAll();

        $this->attachImagesToAnnouncements($announcements);
        return $announcements;
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
