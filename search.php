<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

$from = isset($_GET['from']) ? sanitizeInput($_GET['from']) : '';
$to = isset($_GET['to']) ? sanitizeInput($_GET['to']) : '';
$date = isset($_GET['date']) ? sanitizeInput($_GET['date']) : date('Y-m-d');

$trips = [];
if ($from && $to && $date) {
    $trips = searchTrips($db, $from, $to, $date);
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sefer Arama Sonuçları</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container">
        <div class="search-result-header">
            <div class="route-info">
                <h2><?= htmlspecialchars($from) ?> <span class="arrow">→</span> <?= htmlspecialchars($to) ?></h2>
                <p class="search-date"><?= formatDate($date) ?></p>
            </div>
            <a href="index.php" class="btn btn-secondary">Yeni Arama</a>
        </div>

        <?php if (empty($trips)): ?>
            <div class="no-results">
                <div class="no-results-icon">🔍</div>
                <h3>Sefer Bulunamadı</h3>
                <p>Bu kriterlere uygun sefer bulunamadı.</p>
                <a href="index.php" class="btn btn-primary">Yeni Arama Yap</a>
            </div>
        <?php else: ?>
            <div class="trips-result-list">
                <?php foreach ($trips as $trip): ?>
                    <div class="trip-result-card" onclick="openTripDetails(<?= $trip['id'] ?>)">
                        <div class="trip-result-header">
                            <div class="company-logo">
                                <?php if (!empty($trip['company_logo'])): ?>
                                    <img src="<?= htmlspecialchars($trip['company_logo']) ?>" alt="<?= htmlspecialchars($trip['company_name']) ?>">
                                <?php else: ?>
                                    <div class="company-initial"><?= strtoupper(substr($trip['company_name'], 0, 1)) ?></div>
                                <?php endif; ?>
                            </div>
                            <div class="company-info">
                                <h3 class="company-name"><?= htmlspecialchars($trip['company_name']) ?></h3>
                            </div>
                            <div class="trip-badges">
                                <span class="badge-icon" title="Wifi">📶</span>
                                <span class="badge-icon" title="İkram">🍽️</span>
                                <span class="badge-icon" title="USB">🔌</span>
                                <span class="badge-icon" title="Klima">❄️</span>
                            </div>
                        </div>

                        <div class="trip-result-body">
                            <div class="trip-times">
                                <div class="time-point">
                                    <div class="time-clock">
                                        <span class="clock-icon">🕐</span>
                                        <span class="time-value"><?= formatTime($trip['departure_time']) ?></span>
                                    </div>
                                    <div class="time-location"><?= htmlspecialchars($trip['departure_city']) ?> Otogarı</div>
                                    <div class="time-date">(<?= date('d M', strtotime($trip['departure_date'])) ?> <?= date('D', strtotime($trip['departure_date'])) === 'Sat' ? 'Cumartesi' : (date('D', strtotime($trip['departure_date'])) === 'Sun' ? 'Pazar' : 'Hafta İçi') ?>)</div>
                                </div>

                                <div class="trip-journey">
                                    <div class="journey-duration">
                                        <span class="duration-icon">⏱</span>
                                        <span><?= calculateDuration($trip['departure_time'], $trip['arrival_time']) ?></span>
                                    </div>
                                    <div class="journey-line">
                                        <div class="line-dot"></div>
                                        <div class="line"></div>
                                        <div class="line-dot"></div>
                                    </div>
                                    <div class="journey-type">
                                        <span class="type-badge">2+1</span>
                                    </div>
                                </div>

                                <div class="time-point">
                                    <div class="time-clock">
                                        <span class="clock-icon">🕐</span>
                                        <span class="time-value"><?= formatTime($trip['arrival_time']) ?></span>
                                    </div>
                                    <div class="time-location"><?= htmlspecialchars($trip['arrival_city']) ?> Otogarı</div>
                                    <div class="time-date">Varış</div>
                                </div>
                            </div>

                            <div class="trip-result-footer">
                                <div class="trip-features">
                                    <span class="feature-item">🚌 Şehir İçi Servis</span>
                                    <span class="feature-item">📋 İncele</span>
                                </div>
                                <div class="trip-action">
                                    <div class="price-info">
                                        <span class="price-value"><?= formatPrice($trip['price']) ?></span>
                                    </div>
                                    <button class="btn-select-seat" onclick="event.stopPropagation(); openTripDetails(<?= $trip['id'] ?>)">
                                        KOLTUK SEÇ
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <script>
        function openTripDetails(tripId) {
            window.location.href = 'trip-details.php?id=' + tripId;
        }
    </script>
    
    <?php include 'includes/footer.php'; ?>
</body>
</html>