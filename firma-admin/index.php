<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

requireRole('company_admin');

$user = getCurrentUser();
$companyId = $user['company_id'];

// Get company info
$stmt = $db->prepare("SELECT * FROM companies WHERE id = :id");
$stmt->bindValue(':id', $companyId, SQLITE3_INTEGER);
$result = $stmt->execute();
$company = $result->fetchArray(SQLITE3_ASSOC);

// Get statistics
$statsQuery = "
    SELECT
        COUNT(DISTINCT t.id) as total_trips,
        COUNT(tk.id) as total_tickets,
        SUM(CASE WHEN tk.status = 'active' THEN tk.price ELSE 0 END) as total_revenue,
        COUNT(CASE WHEN tk.status = 'active' THEN 1 END) as active_tickets
    FROM trips t
    LEFT JOIN tickets tk ON t.id = tk.trip_id
    WHERE t.company_id = :company_id
";
$stmt = $db->prepare($statsQuery);
$stmt->bindValue(':company_id', $companyId, SQLITE3_INTEGER);
$result = $stmt->execute();
$stats = $result->fetchArray(SQLITE3_ASSOC);

// Get all trips for details
$allTripsQuery = $db->prepare("
    SELECT * FROM trips
    WHERE company_id = :company_id
    ORDER BY departure_date DESC, departure_time DESC
");
$allTripsQuery->bindValue(':company_id', $companyId, SQLITE3_INTEGER);
$result = $allTripsQuery->execute();

$allTrips = [];
while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
    $allTrips[] = $row;
}

// Get active tickets for details
$activeTicketsQuery = $db->prepare("
    SELECT tk.*, t.departure_city, t.arrival_city, t.departure_date, t.departure_time, u.name as passenger_name, u.email as passenger_email
    FROM tickets tk
    JOIN trips t ON tk.trip_id = t.id
    JOIN users u ON tk.user_id = u.id
    WHERE t.company_id = :company_id AND tk.status = 'active'
    ORDER BY t.departure_date DESC, t.departure_time DESC
");
$activeTicketsQuery->bindValue(':company_id', $companyId, SQLITE3_INTEGER);
$result = $activeTicketsQuery->execute();

$activeTickets = [];
while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
    $activeTickets[] = $row;
}

// Get revenue details
$revenueQuery = $db->prepare("
    SELECT tk.*, t.departure_city, t.arrival_city, t.departure_date, t.departure_time
    FROM tickets tk
    JOIN trips t ON tk.trip_id = t.id
    WHERE t.company_id = :company_id AND tk.status = 'active'
    ORDER BY tk.booking_date DESC
");
$revenueQuery->bindValue(':company_id', $companyId, SQLITE3_INTEGER);
$result = $revenueQuery->execute();

$revenueDetails = [];
while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
    $revenueDetails[] = $row;
}

// Get upcoming trips
$upcomingTrips = $db->prepare("
    SELECT * FROM trips
    WHERE company_id = :company_id
    AND departure_date >= DATE('now')
    ORDER BY departure_date ASC, departure_time ASC
    LIMIT 10
");
$upcomingTrips->bindValue(':company_id', $companyId, SQLITE3_INTEGER);
$result = $upcomingTrips->execute();

$trips = [];
while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
    $trips[] = $row;
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Firma Admin Paneli</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php include '../includes/header.php'; ?>

    <div class="container">
        <div class="admin-header">
            <h2>Firma Admin Paneli - <?= htmlspecialchars($company['name']) ?></h2>
            <div class="admin-nav">
                <a href="trips.php" class="btn btn-primary">Seferler</a>
                <a href="coupons.php" class="btn btn-secondary">Kuponlar</a>
            </div>
        </div>

        <div class="stats-grid">
            <div class="stat-card clickable" onclick="toggleDetail('trips-detail')">
                <div class="stat-icon">🚌</div>
                <div class="stat-info">
                    <h3><?= $stats['total_trips'] ?></h3>
                    <p>Toplam Sefer</p>
                </div>
                <div class="stat-arrow">▼</div>
            </div>

            <div class="stat-card clickable" onclick="toggleDetail('tickets-detail')">
                <div class="stat-icon">🎫</div>
                <div class="stat-info">
                    <h3><?= $stats['active_tickets'] ?></h3>
                    <p>Aktif Bilet</p>
                </div>
                <div class="stat-arrow">▼</div>
            </div>

            <div class="stat-card clickable" onclick="toggleDetail('revenue-detail')">
                <div class="stat-icon">💰</div>
                <div class="stat-info">
                    <h3><?= formatPrice($stats['total_revenue'] ?? 0) ?></h3>
                    <p>Toplam Gelir</p>
                </div>
                <div class="stat-arrow">▼</div>
            </div>
        </div>

        <!-- Trips Detail Section -->
        <div id="trips-detail" class="detail-section" style="display: none;">
            <div class="admin-section">
                <h3>Tüm Seferler</h3>
                <?php if (empty($allTrips)): ?>
                    <p>Henüz sefer bulunmuyor.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th>Kalkış</th>
                                    <th>Varış</th>
                                    <th>Tarih</th>
                                    <th>Saat</th>
                                    <th>Fiyat</th>
                                    <th>Koltuklar</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($allTrips as $trip): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($trip['departure_city']) ?></td>
                                        <td><?= htmlspecialchars($trip['arrival_city']) ?></td>
                                        <td><?= formatDate($trip['departure_date']) ?></td>
                                        <td><?= formatTime($trip['departure_time']) ?></td>
                                        <td><?= formatPrice($trip['price']) ?></td>
                                        <td><?= $trip['available_seats'] ?> / <?= $trip['total_seats'] ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Tickets Detail Section -->
        <div id="tickets-detail" class="detail-section" style="display: none;">
            <div class="admin-section">
                <h3>Aktif Biletler</h3>
                <?php if (empty($activeTickets)): ?>
                    <p>Henüz aktif bilet bulunmuyor.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th>Yolcu</th>
                                    <th>E-posta</th>
                                    <th>Sefer</th>
                                    <th>Tarih</th>
                                    <th>Koltuk</th>
                                    <th>Fiyat</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($activeTickets as $ticket): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($ticket['passenger_name']) ?></td>
                                        <td><?= htmlspecialchars($ticket['passenger_email']) ?></td>
                                        <td><?= htmlspecialchars($ticket['departure_city']) ?> → <?= htmlspecialchars($ticket['arrival_city']) ?></td>
                                        <td><?= formatDate($ticket['departure_date']) ?> <?= formatTime($ticket['departure_time']) ?></td>
                                        <td><?= $ticket['seat_number'] ?></td>
                                        <td><?= formatPrice($ticket['price']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Revenue Detail Section -->
        <div id="revenue-detail" class="detail-section" style="display: none;">
            <div class="admin-section">
                <h3>Gelir Detayları</h3>
                <?php if (empty($revenueDetails)): ?>
                    <p>Henüz gelir kaydı bulunmuyor.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th>Sefer</th>
                                    <th>Tarih</th>
                                    <th>Koltuk</th>
                                    <th>Rezervasyon Tarihi</th>
                                    <th>Tutar</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($revenueDetails as $ticket): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($ticket['departure_city']) ?> → <?= htmlspecialchars($ticket['arrival_city']) ?></td>
                                        <td><?= formatDate($ticket['departure_date']) ?> <?= formatTime($ticket['departure_time']) ?></td>
                                        <td><?= $ticket['seat_number'] ?></td>
                                        <td><?= formatDate(substr($ticket['booking_date'], 0, 10)) ?></td>
                                        <td><strong><?= formatPrice($ticket['price']) ?></strong></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="admin-section">
            <h3>Yaklaşan Seferler</h3>

            <?php if (empty($trips)): ?>
                <p>Henüz sefer bulunmuyor.</p>
                <a href="trips.php" class="btn btn-primary">Yeni Sefer Ekle</a>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Kalkış</th>
                                <th>Varış</th>
                                <th>Tarih</th>
                                <th>Saat</th>
                                <th>Fiyat</th>
                                <th>Müsait Koltuk</th>
                                <th>İşlemler</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($trips as $trip): ?>
                                <tr>
                                    <td><?= htmlspecialchars($trip['departure_city']) ?></td>
                                    <td><?= htmlspecialchars($trip['arrival_city']) ?></td>
                                    <td><?= formatDate($trip['departure_date']) ?></td>
                                    <td><?= formatTime($trip['departure_time']) ?></td>
                                    <td><?= formatPrice($trip['price']) ?></td>
                                    <td><?= $trip['available_seats'] ?> / <?= $trip['total_seats'] ?></td>
                                    <td>
                                        <a href="trips.php?edit=<?= $trip['id'] ?>" class="btn btn-sm btn-secondary">Düzenle</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <?php include '../includes/footer.php'; ?>

    <script>
        function toggleDetail(sectionId) {
            const section = document.getElementById(sectionId);
            const allSections = document.querySelectorAll('.detail-section');
            const allCards = document.querySelectorAll('.stat-card');

            // Hide all other sections
            allSections.forEach(s => {
                if (s.id !== sectionId) {
                    s.style.display = 'none';
                }
            });

            // Remove active class from all cards
            allCards.forEach(c => c.classList.remove('active'));

            // Toggle current section
            if (section.style.display === 'none') {
                section.style.display = 'block';
                // Add active class to clicked card
                event.target.closest('.stat-card').classList.add('active');
            } else {
                section.style.display = 'none';
            }
        }
    </script>

    <style>
        .stat-card {
            position: relative;
            transition: all 0.3s ease;
        }

        .stat-card.clickable {
            cursor: pointer;
        }

        .stat-card.clickable:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }

        .stat-card.active {
            border: 2px solid #007bff;
            box-shadow: 0 4px 12px rgba(0, 123, 255, 0.3);
        }

        .stat-arrow {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 12px;
            color: #666;
            transition: transform 0.3s ease;
        }

        .stat-card.active .stat-arrow {
            transform: translateY(-50%) rotate(180deg);
        }

        .detail-section {
            margin-top: 20px;
            animation: slideDown 0.3s ease;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
</body>
</html>
