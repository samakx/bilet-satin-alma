<?php
// Database connection and configuration

define('DB_PATH', __DIR__ . '/../database/tickets.db');

// Ensure database directory exists
$dbDir = dirname(DB_PATH);
if (!file_exists($dbDir)) {
    mkdir($dbDir, 0777, true);
}

try {
    $db = new SQLite3(DB_PATH);
    $db->busyTimeout(5000);
    
    // Enable foreign keys
    $db->exec('PRAGMA foreign_keys = ON');
    
    // Include initialization script
    require_once __DIR__ . '/init-db.php';
    
    // Initialize database if needed
    initializeDatabase($db);
    seedDatabase($db);
    
} catch (Exception $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Helper function for prepared statements
function executeQuery($db, $query, $params = []) {
    $stmt = $db->prepare($query);
    
    if ($stmt === false) {
        return false;
    }
    
    foreach ($params as $key => $value) {
        if (is_int($value)) {
            $stmt->bindValue($key, $value, SQLITE3_INTEGER);
        } elseif (is_float($value)) {
            $stmt->bindValue($key, $value, SQLITE3_FLOAT);
        } else {
            $stmt->bindValue($key, $value, SQLITE3_TEXT);
        }
    }
    
    return $stmt->execute();
}

// Helper function to get single row
function fetchOne($db, $query, $params = []) {
    $result = executeQuery($db, $query, $params);
    if ($result) {
        return $result->fetchArray(SQLITE3_ASSOC);
    }
    return null;
}

// Helper function to get all rows
function fetchAll($db, $query, $params = []) {
    $result = executeQuery($db, $query, $params);
    $rows = [];
    if ($result) {
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $rows[] = $row;
        }
    }
    return $rows;
}
?>