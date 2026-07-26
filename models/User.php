<?php
/**
 * User Model
 * Handles user authentication and management
 * 
 * @package Alokpath\Models
 */
class User {
    private $conn;
    private $table = 'users';

    public function __construct() {
        global $db;
        $this->conn = $db;
    }

    /**
     * Authenticate user
     * 
     * @param string $username
     * @param string $password
     * @return array|false
     */
    public function authenticate($username, $password) {
        try {
            // Hash password with MD5 (temporary - upgrade to bcrypt later)
            $hashed_password = HASH_ALGORITHM === 'md5' ? md5($password) : $password;
            
            $sql = "SELECT * FROM " . $this->table . " 
                    WHERE username = :username 
                    AND password = :password 
                    AND status = 'active' 
                    LIMIT 1";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':username', $username);
            $stmt->bindParam(':password', $hashed_password);
            $stmt->execute();
            
            if ($stmt->rowCount() > 0) {
                $user = $stmt->fetch();
                
                // Update last login
                $this->updateLastLogin($user['id']);
                
                return $user;
            }
            
            return false;
        } catch(PDOException $e) {
            error_log("Authentication Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get user by ID
     * 
     * @param int $id
     * @return array|false
     */
    public function getById($id) {
        try {
            $sql = "SELECT * FROM " . $this->table . " WHERE id = :id LIMIT 1";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':id', $id);
            $stmt->execute();
            
            return $stmt->fetch();
        } catch(PDOException $e) {
            error_log("Get User Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get user by username
     * 
     * @param string $username
     * @return array|false
     */
    public function getByUsername($username) {
        try {
            $sql = "SELECT * FROM " . $this->table . " WHERE username = :username LIMIT 1";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':username', $username);
            $stmt->execute();
            
            return $stmt->fetch();
        } catch(PDOException $e) {
            error_log("Get User By Username Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get user by full name
     * 
     * @param string $full_name
     * @return array|false
     */
    public function getByFullName($full_name) {
        try {
            // Use exact match or replace hyphens with spaces for better URL matching
            $name_spaced = str_replace('-', ' ', $full_name);
            $sql = "SELECT * FROM " . $this->table . " WHERE full_name = :full_name OR full_name = :name_spaced LIMIT 1";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':full_name', $full_name);
            $stmt->bindParam(':name_spaced', $name_spaced);
            $stmt->execute();
            
            return $stmt->fetch();
        } catch(PDOException $e) {
            error_log("Get User By Full Name Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Create new user
     * 
     * @param array $data
     * @return bool|int
     */
    public function create($data) {
        try {
            $sql = "INSERT INTO " . $this->table . " 
                    (username, email, password, full_name, role, avatar, status, bio, phone, facebook_url, twitter_url, youtube_url) 
                    VALUES 
                    (:username, :email, :password, :full_name, :role, :avatar, :status, :bio, :phone, :facebook_url, :twitter_url, :youtube_url)";
            
            $stmt = $this->conn->prepare($sql);
            
            // Hash password
            $password = HASH_ALGORITHM === 'md5' ? md5($data['password']) : $data['password'];
            
            $stmt->bindValue(':username', sanitize($data['username']));
            $stmt->bindValue(':email', sanitize($data['email']));
            $stmt->bindValue(':password', $password);
            $stmt->bindValue(':full_name', sanitize($data['full_name']));
            $stmt->bindValue(':role', sanitize($data['role']));
            $stmt->bindValue(':avatar', isset($data['avatar']) ? sanitize($data['avatar']) : null);
            $stmt->bindValue(':status', isset($data['status']) ? sanitize($data['status']) : 'active');
            $stmt->bindValue(':bio', isset($data['bio']) ? sanitize($data['bio']) : null);
            $stmt->bindValue(':phone', isset($data['phone']) ? sanitize($data['phone']) : null);
            $stmt->bindValue(':facebook_url', isset($data['facebook_url']) ? sanitize($data['facebook_url']) : null);
            $stmt->bindValue(':twitter_url', isset($data['twitter_url']) ? sanitize($data['twitter_url']) : null);
            $stmt->bindValue(':youtube_url', isset($data['youtube_url']) ? sanitize($data['youtube_url']) : null);
            
            if ($stmt->execute()) {
                return $this->conn->lastInsertId();
            }
            
            return false;
        } catch(PDOException $e) {
            error_log("Create User Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Update user
     * 
     * @param int $id
     * @param array $data
     * @return bool
     */
    public function update($id, $data) {
        try {
            $fields = [];
            $params = [':id' => $id];
            
            $allowed_fields = ['username', 'email', 'password', 'full_name', 'role', 'avatar', 'status', 'bio', 'phone', 'facebook_url', 'twitter_url', 'youtube_url'];
            
            foreach ($allowed_fields as $field) {
                if (isset($data[$field])) {
                    if ($field === 'password' && !empty($data[$field])) {
                        $fields[] = "password = :password";
                        $params[':password'] = HASH_ALGORITHM === 'md5' ? md5($data[$field]) : $data[$field];
                    } else {
                        $fields[] = $field . " = :" . $field;
                        $params[':' . $field] = sanitize($data[$field]);
                    }
                }
            }
            
            if (empty($fields)) {
                return false;
            }
            
            $sql = "UPDATE " . $this->table . " SET " . implode(', ', $fields) . " WHERE id = :id";
            $stmt = $this->conn->prepare($sql);
            
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            
            return $stmt->execute();
        } catch(PDOException $e) {
            error_log("Update User Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Delete user
     * 
     * @param int $id
     * @return bool
     */
    public function delete($id) {
        try {
            $sql = "DELETE FROM " . $this->table . " WHERE id = :id";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':id', $id);
            return $stmt->execute();
        } catch(PDOException $e) {
            error_log("Delete User Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get all users with pagination
     * 
     * @param int $limit
     * @param int $offset
     * @return array
     */
    public function getAll($limit = 20, $offset = 0) {
        try {
            $sql = "SELECT id, username, email, full_name, role, avatar, status, bio, phone, facebook_url, twitter_url, youtube_url, created_at, updated_at, last_login 
                    FROM " . $this->table . " 
                    ORDER BY created_at DESC 
                    LIMIT :limit OFFSET :offset";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll();
        } catch(PDOException $e) {
            error_log("Get All Users Error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get total user count
     * 
     * @return int
     */
    public function getCount() {
        try {
            $sql = "SELECT COUNT(*) as total FROM " . $this->table;
            $stmt = $this->conn->prepare($sql);
            $stmt->execute();
            $result = $stmt->fetch();
            return $result['total'];
        } catch(PDOException $e) {
            error_log("Get Count Error: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Update last login timestamp
     * 
     * @param int $id
     * @return bool
     */
    private function updateLastLogin($id) {
        try {
            $sql = "UPDATE " . $this->table . " SET last_login = NOW() WHERE id = :id";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':id', $id);
            return $stmt->execute();
        } catch(PDOException $e) {
            error_log("Update Last Login Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Check if user has specific role
     * 
     * @param int $userId
     * @param string $role
     * @return bool
     */
    public function hasRole($userId, $role) {
        try {
            $sql = "SELECT role FROM " . $this->table . " WHERE id = :id LIMIT 1";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':id', $userId);
            $stmt->execute();
            $user = $stmt->fetch();
            
            return $user && $user['role'] === $role;
        } catch(PDOException $e) {
            error_log("Has Role Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get users by role
     * 
     * @param string $role
     * @return array
     */
    public function getByRole($role) {
        try {
            $sql = "SELECT * FROM " . $this->table . " WHERE role = :role ORDER BY full_name ASC";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':role', $role);
            $stmt->execute();
            
            return $stmt->fetchAll();
        } catch(PDOException $e) {
            error_log("Get By Role Error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get users with generated ID cards
     * 
     * @return array
     */
    public function getIdCardUsers() {
        try {
            $sql = "SELECT * FROM " . $this->table . " WHERE id_card_generated = 1 ORDER BY employee_number ASC";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch(PDOException $e) {
            error_log("Get ID Card Users Error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Generate next employee number (ALP-0001 format)
     * 
     * @return string
     */
    public function generateEmployeeNumber() {
        try {
            $sql = "SELECT employee_number FROM " . $this->table . " 
                    WHERE employee_number IS NOT NULL 
                    ORDER BY employee_number DESC LIMIT 1";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute();
            $last = $stmt->fetch();
            
            if ($last && !empty($last['employee_number'])) {
                $num = (int) substr($last['employee_number'], 4);
                $next = $num + 1;
            } else {
                $next = 1;
            }
            
            return 'ALP-' . str_pad($next, 4, '0', STR_PAD_LEFT);
        } catch(PDOException $e) {
            error_log("Generate Employee Number Error: " . $e->getMessage());
            return 'ALP-0001';
        }
    }

    /**
     * Set ID card for a user
     * 
     * @param int $userId
     * @param string $role
     * @param string $employeeNumber
     * @return bool
     */
    public function setIdCard($userId, $role, $employeeNumber) {
        try {
            $sql = "UPDATE " . $this->table . " 
                    SET id_card_role = :role, 
                        employee_number = :emp_no, 
                        id_card_generated = 1 
                    WHERE id = :id";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':role', $role);
            $stmt->bindParam(':emp_no', $employeeNumber);
            $stmt->bindParam(':id', $userId);
            return $stmt->execute();
        } catch(PDOException $e) {
            error_log("Set ID Card Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Revoke ID card for a user
     * 
     * @param int $userId
     * @return bool
     */
    public function revokeIdCard($userId) {
        try {
            $sql = "UPDATE " . $this->table . " 
                    SET id_card_role = NULL, 
                        employee_number = NULL, 
                        id_card_generated = 0 
                    WHERE id = :id";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':id', $userId);
            return $stmt->execute();
        } catch(PDOException $e) {
            error_log("Revoke ID Card Error: " . $e->getMessage());
            return false;
        }
    }
}
