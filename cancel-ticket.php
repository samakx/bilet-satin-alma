<?php
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';
require_once 'includes/config.php';

requireLogin();

$ticketId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$user = getCurrentUser();

// Get ticket details
$stmt = $db->prepare("SELECT tk.*, t.departure_date, t.departure_time, t.id as trip_id
                      FROM tickets tk
                      JOIN trips t ON tk.trip_id = t.id
                      WHERE tk.id = :id AND tk.user_id = :user_id");
$stmt->bindValue(':id', $ticketId, SQLITE3_INTEGER);
$stmt->bindValue(':user_id', $user['id'], SQLITE3_INTEGER);
$result = $stmt->execute();
$ticket = $result->fetchArray(SQLITE3_ASSOC);

if (!$ticket || $ticket['status'] === 'cancelled') {
    header('Location: ' . url('my-tickets.php'));
    exit;
}

if (!canCancelTicket($db, $ticketId)) {
    header('Location: ' . url('my-tickets.php?error=time'));
    exit;
}

// Process cancellation
$db->exec('BEGIN');

try {
    // Update ticket status
    $stmt = $db->prepare("UPDATE tickets SET status = 'cancelled' WHERE id = :id");
    $stmt->bindValue(':id', $ticketId, SQLITE3_INTEGER);
    $stmt->execute();
    
    // Refund user
    $stmt = $db->prepare("UPDATE users SET balance = balance + :price WHERE id = :id");
    $stmt->bindValue(':price', $ticket['price'], SQLITE3_FLOAT);
    $stmt->bindValue(':id', $user['id'], SQLITE3_INTEGER);
    $stmt->execute();
    
    // Update available seats
    $stmt = $db->prepare("UPDATE trips SET available_seats = available_seats + 1 WHERE id = :id");
    $stmt->bindValue(':id', $ticket['trip_id'], SQLITE3_INTEGER);
    $stmt->execute();
    
    $db->exec('COMMIT');
    
    header('Location: ' . url('my-tickets.php?cancelled=1'));
    exit;
} catch (Exception $e) {
    $db->exec('ROLLBACK');
    header('Location: ' . url('my-tickets.php?error=1'));
    exit;
}
?>