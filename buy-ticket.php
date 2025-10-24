<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

requireLogin();

$tripId = isset($_GET['trip_id']) ? (int)$_GET['trip_id'] : 0;
$seatNumber = isset($_GET['seat']) ? (int)$_GET['seat'] : 0;

$trip = getTripDetails($db, $tripId);

if (!$trip) {
    header('Location: ' . url('index.php'));
    exit;
}

// Validate seat parameter
if (!$seatNumber || !isSeatAvailable($db, $tripId, $seatNumber)) {
    header('Location: ' . url('trip-details.php?id=' . $tripId));
    exit;
}

$user = getCurrentUser();
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $couponCode = isset($_POST['coupon']) ? strtoupper(trim($_POST['coupon'])) : '';

    // Validate seat again before purchase
    if (!isSeatAvailable($db, $tripId, $seatNumber)) {
        $error = 'Seçilen koltuk müsait değil!';
    } else {
        $finalPrice = $trip['price'];
        $discountAmount = 0;

        // Apply coupon if provided
        if ($couponCode) {
            $coupon = validateCoupon($db, $couponCode, $trip['company_id']);
            if ($coupon) {
                $discount = applyCouponDiscount($trip['price'], $coupon['discount_percentage']);
                $finalPrice = $discount['final_price'];
                $discountAmount = $discount['discount'];

                // Update coupon usage
                $stmt = $db->prepare("UPDATE coupons SET used_count = used_count + 1 WHERE id = :id");
                $stmt->bindValue(':id', $coupon['id'], SQLITE3_INTEGER);
                $stmt->execute();
            } else {
                $error = 'Geçersiz veya süresi dolmuş kupon kodu!';
            }
        }
        
        if (!$error) {
            // Check if user has enough balance
            if ($user['balance'] < $finalPrice) {
                $error = 'Yetersiz bakiye! Bakiyeniz: ' . formatPrice($user['balance']);
            } else {
                // Start transaction
                $db->exec('BEGIN');
                
                try {
                    // Insert ticket
                    $stmt = $db->prepare("INSERT INTO tickets (user_id, trip_id, seat_number, price, coupon_code, discount_amount) 
                                         VALUES (:user_id, :trip_id, :seat, :price, :coupon, :discount)");
                    $stmt->bindValue(':user_id', $user['id'], SQLITE3_INTEGER);
                    $stmt->bindValue(':trip_id', $tripId, SQLITE3_INTEGER);
                    $stmt->bindValue(':seat', $seatNumber, SQLITE3_INTEGER);
                    $stmt->bindValue(':price', $finalPrice, SQLITE3_FLOAT);
                    $stmt->bindValue(':coupon', $couponCode, SQLITE3_TEXT);
                    $stmt->bindValue(':discount', $discountAmount, SQLITE3_FLOAT);
                    $stmt->execute();
                    
                    // Update user balance
                    $stmt = $db->prepare("UPDATE users SET balance = balance - :price WHERE id = :id");
                    $stmt->bindValue(':price', $finalPrice, SQLITE3_FLOAT);
                    $stmt->bindValue(':id', $user['id'], SQLITE3_INTEGER);
                    $stmt->execute();
                    
                    // Update available seats
                    $stmt = $db->prepare("UPDATE trips SET available_seats = available_seats - 1 WHERE id = :id");
                    $stmt->bindValue(':id', $tripId, SQLITE3_INTEGER);
                    $stmt->execute();
                    
                    $db->exec('COMMIT');
                    
                    header('Location: ' . url('my-tickets.php?success=1'));
                    exit;
                } catch (Exception $e) {
                    $db->exec('ROLLBACK');
                    $error = 'Bilet alımı sırasında bir hata oluştu!';
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bilet Satın Al</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container">
        <h2>Bilet Satın Al</h2>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <div class="ticket-purchase">
            <div class="trip-summary">
                <h3>Sefer Bilgileri</h3>
                <p><strong><?= htmlspecialchars($trip['company_name']) ?></strong></p>
                <p><?= htmlspecialchars($trip['departure_city']) ?> → <?= htmlspecialchars($trip['arrival_city']) ?></p>
                <p><?= formatDate($trip['departure_date']) ?> | <?= formatTime($trip['departure_time']) ?></p>
                <p><strong>Seçilen Koltuk:</strong> <span class="selected-seat-badge"><?= $seatNumber ?></span></p>
                <p class="price">Fiyat: <span id="originalPrice"><?= formatPrice($trip['price']) ?></span></p>
                <div id="discountInfo" style="display: none; color: #28a745; font-weight: bold; margin-top: 10px;">
                    <p>İndirim: <span id="discountAmount"></span></p>
                    <p>Ödenecek Tutar: <span id="finalPrice"></span></p>
                </div>
                <p>Bakiyeniz: <strong><?= formatPrice($user['balance']) ?></strong></p>
            </div>

            <form method="POST" class="purchase-form">
                
                <div class="form-group">
                    <label for="coupon">İndirim Kuponu (Opsiyonel)</label>
                    <div style="display: flex; gap: 10px;">
                        <input type="text" name="coupon" id="coupon" placeholder="Kupon kodunu girin" style="flex: 1;">
                        <button type="button" id="checkCoupon" class="btn btn-secondary">Uygula</button>
                    </div>
                    <div id="couponMessage" style="margin-top: 5px;"></div>
                </div>

                <button type="submit" class="btn btn-primary btn-large">Satın Al</button>
            </form>
        </div>
    </div>
    
    <?php include 'includes/footer.php'; ?>
    
    <script>
        const tripPrice = <?= $trip['price'] ?>;
        const companyId = <?= $trip['company_id'] ?>;

        // Check coupon
        document.getElementById('checkCoupon').addEventListener('click', function() {
            const couponCode = document.getElementById('coupon').value.trim();
            const messageDiv = document.getElementById('couponMessage');
            const discountInfo = document.getElementById('discountInfo');
            const originalPriceElement = document.getElementById('originalPrice');

            if (!couponCode) {
                messageDiv.innerHTML = '<span style="color: #dc3545;">Lütfen kupon kodu girin!</span>';
                discountInfo.style.display = 'none';
                return;
            }

            // Send AJAX request to validate coupon
            fetch('check-coupon.php?code=' + encodeURIComponent(couponCode) + '&company_id=' + companyId)
                .then(response => response.json())
                .then(data => {
                    if (data.valid) {
                        const discount = (tripPrice * data.discount) / 100;
                        const finalPrice = tripPrice - discount;

                        messageDiv.innerHTML = '<span style="color: #28a745;">✓ Kupon geçerli! %' + data.discount + ' indirim uygulandı.</span>';
                        document.getElementById('discountAmount').textContent = '-' + discount.toFixed(2).replace('.', ',') + ' ₺';
                        document.getElementById('finalPrice').textContent = finalPrice.toFixed(2).replace('.', ',') + ' ₺';
                        discountInfo.style.display = 'block';
                    } else {
                        messageDiv.innerHTML = '<span style="color: #dc3545;">✗ ' + data.message + '</span>';
                        discountInfo.style.display = 'none';
                    }
                })
                .catch(error => {
                    messageDiv.innerHTML = '<span style="color: #dc3545;">Kupon kontrolü sırasında hata oluştu!</span>';
                    discountInfo.style.display = 'none';
                });
        });
    </script>
</body>
</html>