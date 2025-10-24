<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

$tripId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$trip = getTripDetails($db, $tripId);

if (!$trip) {
    header('Location: ' . url('index.php'));
    exit;
}

$occupiedSeats = getOccupiedSeats($db, $tripId);
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sefer Detayları</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container">
        <div class="trip-details-wrapper">
            <!-- Trip Info Header -->
            <div class="trip-details-header">
                <div class="company-section">
                    <div class="company-logo-large">
                        <?php if (!empty($trip['company_logo'])): ?>
                            <img src="<?= htmlspecialchars($trip['company_logo']) ?>" alt="<?= htmlspecialchars($trip['company_name']) ?>">
                        <?php else: ?>
                            <div class="company-initial-large"><?= strtoupper(substr($trip['company_name'], 0, 1)) ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="company-details">
                        <h2 class="company-title"><?= htmlspecialchars($trip['company_name']) ?></h2>
                        <div class="trip-features-badges">
                            <span class="feature-badge">📶 Wifi</span>
                            <span class="feature-badge">🍽️ İkram</span>
                            <span class="feature-badge">🔌 USB</span>
                            <span class="feature-badge">❄️ Klima</span>
                        </div>
                    </div>
                </div>

                <div class="price-section">
                    <div class="price-label">Bilet Fiyatı</div>
                    <div class="price-amount"><?= formatPrice($trip['price']) ?></div>
                </div>
            </div>

            <!-- Trip Journey Info -->
            <div class="trip-journey-info">
                <div class="journey-point">
                    <div class="journey-time"><?= formatTime($trip['departure_time']) ?></div>
                    <div class="journey-city"><?= htmlspecialchars($trip['departure_city']) ?> Otogarı</div>
                    <div class="journey-date"><?= formatDate($trip['departure_date']) ?></div>
                    <div class="journey-day">(<?= date('d M', strtotime($trip['departure_date'])) ?> <?= date('D', strtotime($trip['departure_date'])) === 'Sat' ? 'Cumartesi' : (date('D', strtotime($trip['departure_date'])) === 'Sun' ? 'Pazar' : 'Hafta İçi') ?>)*</div>
                </div>

                <div class="journey-middle">
                    <div class="journey-duration-text">
                        <span class="duration-icon">⏱</span>
                        <span><?= calculateDuration($trip['departure_time'], $trip['arrival_time']) ?></span>
                    </div>
                    <div class="journey-arrow">→</div>
                </div>

                <div class="journey-point">
                    <div class="journey-time"><?= formatTime($trip['arrival_time']) ?></div>
                    <div class="journey-city"><?= htmlspecialchars($trip['arrival_city']) ?> Otogarı</div>
                    <div class="journey-date">Varış</div>
                </div>
            </div>

            <!-- Service Info -->
            <div class="service-info-section">
                <div class="service-info-item">
                    <span class="info-icon">🚌</span>
                    <span>Şehir İçi Servis</span>
                </div>
                <div class="service-info-item">
                    <span class="info-icon">ℹ️</span>
                    <span>Salı'yı Çarşamba'ya bağlayan gece kalkacaktır.</span>
                </div>
            </div>

            <!-- Seat Selection Section -->
            <div class="seat-selection-section">
                <div class="seat-selection-header">
                    <h3>Lütfen soldan koltuk seçin.</h3>
                    <div class="seat-info-note">
                        <span class="warning-icon">⚠️</span>
                        <span>Tekli koltuklar tükeniyor. Acele et!</span>
                    </div>
                </div>

                <div class="seat-map-container">
                    <div class="seat-map">
                        <?php
                        $totalSeats = $trip['total_seats'];
                        $rows = ceil($totalSeats / 4); // 2+2 düzen için 4 koltuk per row
                        $seatNumber = 1;

                        for ($row = 1; $row <= $rows; $row++):
                        ?>
                            <div class="seat-row">
                                <!-- Sol 2 koltuk -->
                                <?php for ($col = 1; $col <= 2; $col++): ?>
                                    <?php if ($seatNumber <= $totalSeats): ?>
                                        <?php
                                        $isOccupied = in_array($seatNumber, $occupiedSeats);
                                        $seatClass = $isOccupied ? 'seat occupied' : 'seat available';
                                        ?>
                                        <div class="<?= $seatClass ?>" data-seat="<?= $seatNumber ?>" onclick="<?= !$isOccupied ? 'toggleSeat(this)' : '' ?>">
                                            <?= $seatNumber ?>
                                        </div>
                                        <?php $seatNumber++; ?>
                                    <?php endif; ?>
                                <?php endfor; ?>

                                <!-- Koridor -->
                                <div class="seat-corridor"></div>

                                <!-- Sağ 2 koltuk -->
                                <?php for ($col = 1; $col <= 2; $col++): ?>
                                    <?php if ($seatNumber <= $totalSeats): ?>
                                        <?php
                                        $isOccupied = in_array($seatNumber, $occupiedSeats);
                                        $seatClass = $isOccupied ? 'seat occupied' : 'seat available';
                                        ?>
                                        <div class="<?= $seatClass ?>" data-seat="<?= $seatNumber ?>" onclick="<?= !$isOccupied ? 'toggleSeat(this)' : '' ?>">
                                            <?= $seatNumber ?>
                                        </div>
                                        <?php $seatNumber++; ?>
                                    <?php endif; ?>
                                <?php endfor; ?>
                            </div>
                        <?php endfor; ?>
                    </div>

                    <div class="seat-legend">
                        <div class="legend-item">
                            <div class="seat available"></div>
                            <span>Boş Koltuk</span>
                        </div>
                        <div class="legend-item">
                            <div class="seat occupied"></div>
                            <span>Dolu Koltuk</span>
                        </div>
                        <div class="legend-item">
                            <div class="seat selected"></div>
                            <span>Seçilen Koltuk</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Action Section -->
            <div class="trip-action-section">
                <?php if ($trip['available_seats'] > 0): ?>
                    <?php if (isLoggedIn()): ?>
                        <button class="btn-continue" onclick="proceedToBuy()">ONAYLA VE DEVAM ET</button>
                    <?php else: ?>
                        <div class="login-required">
                            <p>⚠️ Bilet satın almak için lütfen giriş yapın.</p>
                            <a href="login.php?redirect=<?= urlencode($_SERVER['REQUEST_URI']) ?>" class="btn-login">
                                Giriş Yap
                            </a>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="no-seats-available">
                        <p>❌ Bu sefer için müsait koltuk bulunmamaktadır.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        let selectedSeats = [];

        function toggleSeat(element) {
            const seatNumber = element.getAttribute('data-seat');

            if (element.classList.contains('selected')) {
                element.classList.remove('selected');
                selectedSeats = selectedSeats.filter(s => s !== seatNumber);
            } else {
                element.classList.add('selected');
                selectedSeats.push(seatNumber);
            }

            console.log('Seçilen koltuklar:', selectedSeats);
        }

        function proceedToBuy() {
            if (selectedSeats.length === 0) {
                alert('Lütfen en az bir koltuk seçin!');
                return;
            }

            // İlk seçilen koltuğu gönder (tek koltuk seçimi için)
            window.location.href = 'buy-ticket.php?trip_id=<?= $trip['id'] ?>&seat=' + selectedSeats[0];
        }
    </script>
    
    <?php include 'includes/footer.php'; ?>
</body>
</html>