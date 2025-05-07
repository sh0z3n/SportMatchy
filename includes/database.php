<?php
require_once 'config.php';

class Database {
    private static $instance = null;
    private $pdo;
    private $statement;
    private $inTransaction = false;

    private function __construct() {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ];
            
            $this->pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            error_log("Database connection failed: " . $e->getMessage());
            throw new Exception("Database connection failed. Please try again later.");
        }
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function query($sql, $params = []) {
        try {
            $this->statement = $this->pdo->prepare($sql);
            $this->statement->execute($params);
            return $this->statement;
        } catch (PDOException $e) {
            error_log("Database query failed: " . $e->getMessage());
            throw new Exception("Database error occurred: " . $e->getMessage() . " | SQL: " . $sql);
        }
    }

    public function fetch() {
        return $this->statement->fetch();
    }

    public function fetchAll() {
        return $this->statement->fetchAll();
    }

    public function fetchColumn() {
        return $this->statement->fetchColumn();
    }

    public function rowCount() {
        return $this->statement->rowCount();
    }

    public function lastInsertId() {
        return $this->pdo->lastInsertId();
    }

    public function beginTransaction() {
        return $this->pdo->beginTransaction();
    }

    public function commit() {
        return $this->pdo->commit();
    }

    public function rollBack() {
        return $this->pdo->rollBack();
    }

    public function insert($table, $data) {
        $columns = implode(', ', array_keys($data));
        $values = implode(', ', array_fill(0, count($data), '?'));
        $sql = "INSERT INTO {$table} ({$columns}) VALUES ({$values})";
        
        $this->query($sql, array_values($data));
        return $this->lastInsertId();
    }

    public function update($table, $data, $where, $whereParams = []) {
        $set = implode(' = ?, ', array_keys($data)) . ' = ?';
        $sql = "UPDATE {$table} SET {$set} WHERE {$where}";
        
        $params = array_merge(array_values($data), $whereParams);
        $this->query($sql, $params);
        return $this->rowCount();
    }

    public function delete($table, $where, $params = []) {
        $sql = "DELETE FROM {$table} WHERE {$where}";
        $this->query($sql, $params);
        return $this->rowCount();
    }

    public function exists($table, $where, $params = []) {
        $sql = "SELECT EXISTS(SELECT 1 FROM {$table} WHERE {$where}) as exists";
        $result = $this->query($sql, $params)->fetch();
        return (bool) $result['exists'];
    }

    public function count($table, $where = '1', $params = []) {
        $sql = "SELECT COUNT(*) as count FROM {$table} WHERE {$where}";
        $result = $this->query($sql, $params)->fetch();
        return (int) $result['count'];
    }

    public function paginate($table, $page = 1, $perPage = 10, $where = '1', $params = [], $orderBy = 'id DESC') {
        $offset = ($page - 1) * $perPage;
        $total = $this->count($table, $where, $params);
        
        $sql = "SELECT * FROM {$table} WHERE {$where} ORDER BY {$orderBy} LIMIT {$perPage} OFFSET {$offset}";
        $items = $this->query($sql, $params)->fetchAll();
        
        return [
            'items' => $items,
            'total' => $total,
            'per_page' => $perPage,
            'current_page' => $page,
            'last_page' => ceil($total / $perPage)
        ];
    }

    public function search($table, $fields, $query, $where = '1', $params = [], $orderBy = 'id DESC') {
        $searchFields = array_map(function($field) {
            return "{$field} LIKE ?";
        }, $fields);
        
        $searchParams = array_fill(0, count($fields), "%{$query}%");
        $allParams = array_merge($searchParams, $params);
        
        $sql = "SELECT * FROM {$table} 
                WHERE ({$where}) AND (" . implode(' OR ', $searchFields) . ") 
                ORDER BY {$orderBy}";
        
        return $this->query($sql, $allParams)->fetchAll();
    }

    public function join($table, $joins, $fields = '*', $where = '1', $params = [], $orderBy = null) {
        $joinClause = '';
        foreach ($joins as $join) {
            $joinClause .= " {$join['type']} JOIN {$join['table']} ON {$join['condition']}";
        }
        
        $sql = "SELECT {$fields} FROM {$table}{$joinClause} WHERE {$where}";
        
        if ($orderBy) {
            $sql .= " ORDER BY {$orderBy}";
        }
        
        return $this->query($sql, $params)->fetchAll();
    }

    public function transaction(callable $callback) {
        try {
            $this->beginTransaction();
            $result = $callback($this);
            $this->commit();
            return $result;
        } catch (Exception $e) {
            $this->rollBack();
            throw $e;
        }
    }

    public function escape($value) {
        return substr($this->pdo->quote($value), 1, -1);
    }

    public function __destruct() {
        $this->statement = null;
        $this->pdo = null;
    }

    // Prevent cloning of the instance
    private function __clone() {}

    // Prevent unserializing of the instance
    public function __wakeup() {}
} 