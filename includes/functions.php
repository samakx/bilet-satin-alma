<?php
// Helper functions for the application

// Format price in Turkish Lira
function formatPrice($price) {
    return number_format($price, 2, ',', '.') . ' ₺';
}

// Format date in Turkish format
function formatDate($date) {
    $months = [
        'January' => 'Ocak', 'February' => 'Şubat', 'March' => 'Mart',
        'April' => 'Nisan', 'May' => 'Mayıs', 'June' => 'Haziran',
        'July' => 'Temmuz', 'August' => 'Ağustos', 'September' => 'Eylül',
        'October' => 'Ekim', 'November' => 'Kasım', 'December' => 'Aralık'
    ];
    
    $timestamp = strtotime($date);
    $formatted = date('d F Y', $timestamp);
    
    foreach ($months as $eng => $tr) {
        $formatted = str_replace($eng, $tr, $formatted);
    }
    
    return $formatted;
}

// Format time
function formatTime($time) {
    return date('H:i', strtotime($time));
}

// Get occupied seats for a trip
function getOccupiedSeats($db, $tripId) {
    $stmt = $db->prepare("SELECT seat_number FROM tickets 
                          WHERE trip_id = :trip_id AND status = 'active'");
    $stmt->bindValue(':trip_id', $tripId, SQLITE3_INTEGER);
    $result = $stmt->execute();
    
    $seats = [];
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $seats[] = $row['seat_number'];
    }
    
    return $seats;
}

// Check if seat is available
function isSeatAvailable($db, $tripId, $seatNumber) {
    $occupiedSeats = getOccupiedSeats($db, $tripId);
    return !in_array($seatNumber, $occupiedSeats);
}

// Validate coupon
function validateCoupon($db, $code, $companyId = null) {
    $query = "SELECT * FROM coupons 
              WHERE code = :code 
              AND (company_id IS NULL OR company_id = :company_id)
              AND used_count < usage_limit 
              AND expiry_date >= DATE('now')";
    
    $stmt = $db->prepare($query);
    $stmt->bindValue(':code', $code, SQLITE3_TEXT);
    $stmt->bindValue(':company_id', $companyId, SQLITE3_INTEGER);
    $result = $stmt->execute();
    
    return $result->fetchArray(SQLITE3_ASSOC);
}

// Apply coupon discount
function applyCouponDiscount($price, $discountPercentage) {
    $discount = ($price * $discountPercentage) / 100;
    return [
        'original_price' => $price,
        'discount' => $discount,
        'final_price' => $price - $discount
    ];
}

// Can cancel ticket (1 hour before departure)
function canCancelTicket($db, $ticketId) {
    $stmt = $db->prepare("
        SELECT t.departure_date, t.departure_time 
        FROM tickets tk
        JOIN trips t ON tk.trip_id = t.id
        WHERE tk.id = :ticket_id
    ");
    $stmt->bindValue(':ticket_id', $ticketId, SQLITE3_INTEGER);
    $result = $stmt->execute();
    $trip = $result->fetchArray(SQLITE3_ASSOC);
    
    if (!$trip) {
        return false;
    }
    
    $departureDateTime = strtotime($trip['departure_date'] . ' ' . $trip['departure_time']);
    $currentTime = time();
    $oneHourBefore = $departureDateTime - 3600;
    
    return $currentTime < $oneHourBefore;
}

// Get trip details
function getTripDetails($db, $tripId) {
    $stmt = $db->prepare("
        SELECT t.*, c.name as company_name, c.logo as company_logo
        FROM trips t
        JOIN companies c ON t.company_id = c.id
        WHERE t.id = :id
    ");
    $stmt->bindValue(':id', $tripId, SQLITE3_INTEGER);
    $result = $stmt->execute();
    return $result->fetchArray(SQLITE3_ASSOC);
}

// Get user tickets
function getUserTickets($db, $userId, $status = null) {
    $query = "
        SELECT tk.*, t.departure_city, t.arrival_city, t.departure_date,
               t.departure_time, t.arrival_time, c.name as company_name
        FROM tickets tk
        JOIN trips t ON tk.trip_id = t.id
        JOIN companies c ON t.company_id = c.id
        WHERE tk.user_id = :user_id
    ";

    if ($status) {
        $query .= " AND tk.status = :status";
    }

    $query .= " ORDER BY tk.id DESC";

    $stmt = $db->prepare($query);
    $stmt->bindValue(':user_id', $userId, SQLITE3_INTEGER);

    if ($status) {
        $stmt->bindValue(':status', $status, SQLITE3_TEXT);
    }

    $result = $stmt->execute();

    $tickets = [];
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $tickets[] = $row;
    }

    return $tickets;
}

// Search trips
function searchTrips($db, $from, $to, $date) {
    $stmt = $db->prepare("
        SELECT t.*, c.name as company_name, c.logo as company_logo
        FROM trips t
        JOIN companies c ON t.company_id = c.id
        WHERE t.departure_city = :from 
        AND t.arrival_city = :to 
        AND t.departure_date = :date
        AND t.available_seats > 0
        ORDER BY t.departure_time ASC
    ");
    $stmt->bindValue(':from', $from, SQLITE3_TEXT);
    $stmt->bindValue(':to', $to, SQLITE3_TEXT);
    $stmt->bindValue(':date', $date, SQLITE3_TEXT);
    $result = $stmt->execute();
    
    $trips = [];
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $trips[] = $row;
    }
    
    return $trips;
}

// Get all cities (unique)
function getAllCities($db) {
    $result = $db->query("
        SELECT DISTINCT departure_city as city FROM trips
        UNION
        SELECT DISTINCT arrival_city as city FROM trips
        ORDER BY city ASC
    ");
    
    $cities = [];
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $cities[] = $row['city'];
    }
    
    return $cities;
}

// Calculate trip duration
function calculateDuration($departureTime, $arrivalTime) {
    $start = strtotime($departureTime);
    $end = strtotime($arrivalTime);
    
    if ($end < $start) {
        $end += 86400; // Add 24 hours if arrival is next day
    }
    
    $diff = $end - $start;
    $hours = floor($diff / 3600);
    $minutes = floor(($diff % 3600) / 60);
    
    return sprintf('%d sa %d dk', $hours, $minutes);
}
?>