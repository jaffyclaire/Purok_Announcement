<?php
class User
{
    private $conn;
    private $table = "users";

    public function __construct($db)
    {
        $this->conn = $db;
    }

    public function getAllUsers()
    {
        $query = "SELECT user_id, first_name, last_name, role, avatar_path
                  FROM {$this->table}
                  ORDER BY first_name ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getUserById($id)
    {
        $query = "SELECT user_id, first_name, last_name, email, password, role, avatar_path
                  FROM {$this->table}
                  WHERE user_id = :id
                  LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch();
    }

    public function updateName($id, $firstName, $lastName)
    {
        $query = "UPDATE {$this->table}
                  SET first_name = :first_name, last_name = :last_name
                  WHERE user_id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':first_name', $firstName);
        $stmt->bindParam(':last_name', $lastName);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    public function changePassword($id, $hashedPassword)
    {
        // TRANSACTION - change password
        try {
            $this->conn->beginTransaction();
            $query = "UPDATE {$this->table}
                      SET password = :password
                      WHERE user_id = :id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':password', $hashedPassword);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $result = $stmt->execute();
            $this->conn->commit();
            return $result;
        } catch (Throwable $e) {
            if ($this->conn->inTransaction()) {
                $this->conn->rollBack();
            }
            throw $e;
        }
    }

    public function updateAvatar($id, $avatarPath)
    {
        $query = "UPDATE {$this->table}
                  SET avatar_path = :avatar_path
                  WHERE user_id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':avatar_path', $avatarPath);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    public function registerUser($first_name, $last_name, $email, $password)
    {
        // Check if email already exists
        $checkQuery = "SELECT user_id FROM {$this->table} WHERE email = :email LIMIT 1";
        $checkStmt = $this->conn->prepare($checkQuery);
        $checkStmt->bindParam(':email', $email);
        $checkStmt->execute();

        if ($checkStmt->fetch()) {
            return 'duplicate';
        }

        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $role = 'Resident';

        $query = "INSERT INTO {$this->table} (first_name, last_name, email, password, role)
                  VALUES (:first_name, :last_name, :email, :password, :role)";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':first_name', $first_name);
        $stmt->bindParam(':last_name', $last_name);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':password', $hashedPassword);
        $stmt->bindParam(':role', $role);

        return $stmt->execute();
    }
}
