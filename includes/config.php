<?php
// Configuration file for XAMPP/Apache paths

// Base URL - XAMPP için güncelle
define('BASE_URL', 'http://localhost/bilet-satin-alma');

// URL Helper function
function url($path = '') {
    return BASE_URL . '/' . ltrim($path, '/');
}

// Redirect helper
function redirect($path, $queryParams = []) {
    $url = url($path);
    
    if (!empty($queryParams)) {
        $url .= '?' . http_build_query($queryParams);
    }
    
    header('Location: ' . $url);
    exit;
}

// Current page URL
function current_url() {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    return $protocol . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
}

// Asset URL helper
function asset($path) {
    return BASE_URL . '/assets/' . ltrim($path, '/');
}
?>