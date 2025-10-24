<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

requireLogin();

$ticketId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$user = getCurrentUser();

// Get ticket details
$stmt = $db->prepare("
    SELECT tk.*, t.departure_city, t.arrival_city, t.departure_date, 
           t.departure_time, t.arrival_time, t.bus_plate,
           c.name as company_name, c.phone as company_phone,
           u.name as passenger_name, u.phone as passenger_phone
    FROM tickets tk
    JOIN trips t ON tk.trip_id = t.id
    JOIN companies c ON t.company_id = c.id
    JOIN users u ON tk.user_id = u.id
    WHERE tk.id = :id AND tk.user_id = :user_id AND tk.status = 'active'
");
$stmt->bindValue(':id', $ticketId, SQLITE3_INTEGER);
$stmt->bindValue(':user_id', $user['id'], SQLITE3_INTEGER);
$result = $stmt->execute();
$ticket = $result->fetchArray(SQLITE3_ASSOC);

if (!$ticket) {
    die('Bilet bulunamadi!');
}

// Türkçe karakterleri düzelt
function turkishToAscii($str) {
    $turkish = array('ş','Ş','ı','İ','ğ','Ğ','ü','Ü','ö','Ö','ç','Ç','₺');
    $english = array('s','S','i','I','g','G','u','U','o','O','c','C','TL');
    return str_replace($turkish, $english, $str);
}

// Prepare data
$companyName = turkishToAscii($ticket['company_name']);
$passengerName = turkishToAscii($ticket['passenger_name']);
$departureCity = turkishToAscii($ticket['departure_city']);
$arrivalCity = turkishToAscii($ticket['arrival_city']);
$departureDate = turkishToAscii(formatDate($ticket['departure_date']));
$departureTime = formatTime($ticket['departure_time']);
$arrivalTime = formatTime($ticket['arrival_time']);
$seatNumber = $ticket['seat_number'];
$busPlate = $ticket['bus_plate'];
$price = formatPrice($ticket['price']);
$price = turkishToAscii($price);
$pnr = str_pad($ticketId, 6, '0', STR_PAD_LEFT);
$bookingDate = date('d.m.Y H:i', strtotime($ticket['booking_date']));
$passengerPhone = $ticket['passenger_phone'];

// Set headers for PDF download
header('Content-Type: application/pdf');
header('Content-Disposition: inline; filename="bilet-' . $ticketId . '.pdf"');

// Generate PDF content
$pdfContent = <<<PDF
%PDF-1.4
1 0 obj
<<
/Type /Catalog
/Pages 2 0 R
>>
endobj
2 0 obj
<<
/Type /Pages
/Kids [3 0 R]
/Count 1
>>
endobj
3 0 obj
<<
/Type /Page
/Parent 2 0 R
/MediaBox [0 0 595 842]
/Contents 4 0 R
/Resources <<
/Font <<
/F1 5 0 R
>>
>>
>>
endobj
4 0 obj
<<
/Length 1200
>>
stream
BT
/F1 24 Tf
100 750 Td
(OTOBUS BILETI) Tj
0 -50 Td
/F1 12 Tf
(========================================) Tj
0 -30 Td
(Firma: $companyName) Tj
0 -25 Td
(Yolcu: $passengerName) Tj
0 -25 Td
(Telefon: $passengerPhone) Tj
0 -30 Td
(========================================) Tj
0 -30 Td
(Kalkis: $departureCity) Tj
0 -25 Td
(Varis: $arrivalCity) Tj
0 -25 Td
(Tarih: $departureDate) Tj
0 -25 Td
(Kalkis Saati: $departureTime) Tj
0 -25 Td
(Varis Saati: $arrivalTime) Tj
0 -30 Td
(========================================) Tj
0 -30 Td
(Koltuk No: $seatNumber) Tj
0 -25 Td
(Plaka: $busPlate) Tj
0 -25 Td
(Ucret: $price) Tj
0 -30 Td
(========================================) Tj
0 -30 Td
(PNR: BLT$pnr) Tj
0 -25 Td
(Rezervasyon: $bookingDate) Tj
0 -30 Td
(========================================) Tj
0 -30 Td
/F1 10 Tf
(Lutfen seyahat sirasinda bu bileti yaninda bulundurunuz.) Tj
0 -20 Td
(Iyi yolculuklar dileriz!) Tj
ET
endstream
endobj
5 0 obj
<<
/Type /Font
/Subtype /Type1
/BaseFont /Courier
>>
endobj
xref
0 6
0000000000 65535 f
0000000009 00000 n
0000000058 00000 n
0000000115 00000 n
0000000274 00000 n
0000001526 00000 n
trailer
<<
/Size 6
/Root 1 0 R
>>
startxref
1612
%%EOF
PDF;

echo $pdfContent;
?>