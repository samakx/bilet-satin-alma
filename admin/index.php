<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

requireRole('admin');

// Get statistics
$stats = [
    'companies' => $db->querySingle("SELECT COUNT(*) FROM companies"),
    'trips' => $db->querySingle("SELECT COUNT(*) FROM trips"),
    'tickets' => $db->querySingle("SELECT COUNT(*) FROM tickets WHERE status = 'active'"),
    'users' => $db->querySingle("SELECT COUNT(*) FROM users WHERE role = 'user'"),
    'revenue' => $db->querySingle("SELECT SUM(price) FROM tickets WHERE status = 'active'")
];

// Get all companies for details
$allCompanies = $db->query("SELECT * FROM companies ORDER BY name ASC");

// Get all trips for details
$allTrips = $db->query("
    SELECT t.*, c.name as company_name,
           COUNT(tk.id) as ticket_count,
           SUM(CASE WHEN tk.status = 'active' THEN 1 ELSE 0 END) as active_tickets
    FROM trips t
    LEFT JOIN companies c ON t.company_id = c.id
    LEFT JOIN tickets tk ON t.id = tk.trip_id
    GROUP BY t.id
    ORDER BY t.departure_date DESC
");

// Get all active tickets for details
$allTickets = $db->query("
    SELECT tk.*, u.name as user_name, t.departure_city, t.arrival_city,
           t.departure_date, t.departure_time, c.name as company_name
    FROM tickets tk
    JOIN users u ON tk.user_id = u.id
    JOIN trips t ON tk.trip_id = t.id
    JOIN companies c ON t.company_id = c.id
    WHERE tk.status = 'active'
    ORDER BY tk.booking_date DESC
");

// Get all users for details
$allUsers = $db->query("
    SELECT * FROM users
    WHERE role = 'user'
    ORDER BY name ASC
");

// Get revenue details
$revenueDetails = $db->query("
    SELECT tk.*, u.name as user_name, t.departure_city, t.arrival_city,
           t.departure_date, t.departure_time, c.name as company_name
    FROM tickets tk
    JOIN users u ON tk.user_id = u.id
    JOIN trips t ON tk.trip_id = t.id
    JOIN companies c ON t.company_id = c.id
    WHERE tk.status = 'active'
    ORDER BY tk.booking_date DESC
");

// Get recent tickets
$recentTickets = $db->query("
    SELECT tk.*, u.name as user_name, t.departure_city, t.arrival_city,
           t.departure_date, c.name as company_name
    FROM tickets tk
    JOIN users u ON tk.user_id = u.id
    JOIN trips t ON tk.trip_id = t.id
    JOIN companies c ON t.company_id = c.id
    ORDER BY tk.booking_date DESC
    LIMIT 10
");
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Paneli</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <div class="container">
        <div class="admin-header">
            <h2>Admin Paneli</h2>
            <div class="admin-nav">
                <a href="companies.php" class="btn btn-primary">Firmalar</a>
                <a href="company-admins.php" class="btn btn-primary">Firma Adminleri</a>
                <a href="coupons.php" class="btn btn-primary">Kuponlar</a>
            </div>
        </div>
        
        <div class="stats-grid">
            <div class="stat-card clickable" onclick="toggleDetail('companies-detail')">
                <div class="stat-icon">🏢</div>
                <div class="stat-info">
                    <h3><?= $stats['companies'] ?></h3>
                    <p>Firma</p>
                </div>
                <div class="stat-arrow">▼</div>
            </div>

            <div class="stat-card clickable" onclick="toggleDetail('trips-detail')">
                <div class="stat-icon">🚌</div>
                <div class="stat-info">
                    <h3><?= $stats['trips'] ?></h3>
                    <p>Sefer</p>
                </div>
                <div class="stat-arrow">▼</div>
            </div>

            <div class="stat-card clickable" onclick="toggleDetail('tickets-detail')">
                <div class="stat-icon">🎫</div>
                <div class="stat-info">
                    <h3><?= $stats['tickets'] ?></h3>
                    <p>Aktif Bilet</p>
                </div>
                <div class="stat-arrow">▼</div>
            </div>

            <div class="stat-card clickable" onclick="toggleDetail('users-detail')">
                <div class="stat-icon">👥</div>
                <div class="stat-info">
                    <h3><?= $stats['users'] ?></h3>
                    <p>Kullanıcı</p>
                </div>
                <div class="stat-arrow">▼</div>
            </div>

            <div class="stat-card clickable" onclick="toggleDetail('revenue-detail')">
                <div class="stat-icon">💰</div>
                <div class="stat-info">
                    <h3><?= formatPrice($stats['revenue'] ?: 0) ?></h3>
                    <p>Toplam Gelir</p>
                </div>
                <div class="stat-arrow">▼</div>
            </div>
        </div>

        <!-- Companies Detail Section -->
        <div id="companies-detail" class="detail-section" style="display: none;">
            <div class="admin-section">
                <h3>Tüm Firmalar</h3>
                <div class="table-responsive">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Firma Adı</th>
                                <th>Licensefor Temsilcisi</th>
                                <th>Telefon</th>
                                <th>Email</th>
                                <th>Oluşturulma Tarihi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $allCompanies->reset(); while ($company = $allCompanies->fetchArray(SQLITE3_ASSOC)): ?>
                            <tr>
                                <td><?= htmlspecialchars($company['name']) ?></td>
                                <td><?= htmlspecialchars($company['representative_name'] ?? '-') ?></td>
                                <td><?= htmlspecialchars($company['phone'] ?? '-') ?></td>
                                <td><?= htmlspecialchars($company['email'] ?? '-') ?></td>
                                <td><?= htmlspecialchars($company['created_at'] ?? '-') ?></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Trips Detail Section -->
        <div id="trips-detail" class="detail-section" style="display: none;">
            <div class="admin-section">
                <h3>Tüm Seferler</h3>
                <div class="table-responsive">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Kalkış Şehri</th>
                                <th>Varış Şehri</th>
                                <th>Tarih</th>
                                <th>Saat</th>
                                <th>Fiyat</th>
                                <th>Firma</th>
                                <th>Bilet Sayısı</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $allTrips->reset(); while ($trip = $allTrips->fetchArray(SQLITE3_ASSOC)): ?>
                            <tr>
                                <td><?= htmlspecialchars($trip['departure_city']) ?></td>
                                <td><?= htmlspecialchars($trip['arrival_city']) ?></td>
                                <td><?= formatDate($trip['departure_date']) ?></td>
                                <td><?= formatTime($trip['departure_time']) ?></td>
                                <td><?= formatPrice($trip['price']) ?></td>
                                <td><?= htmlspecialchars($trip['company_name']) ?></td>
                                <td><?= $trip['active_tickets'] ?> / <?= $trip['ticket_count'] ?></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Tickets Detail Section -->
        <div id="tickets-detail" class="detail-section" style="display: none;">
            <div class="admin-section">
                <h3>Tüm Aktif Biletler</h3>
                <div class="table-responsive">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>PNR</th>
                                <th>Yolcu</th>
                                <th>Firma</th>
                                <th>Güzergah</th>
                                <th>Tarih</th>
                                <th>Koltuk</th>
                                <th>Fiyat</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $allTickets->reset(); while ($ticket = $allTickets->fetchArray(SQLITE3_ASSOC)): ?>
                            <tr>
                                <td>BLT<?= str_pad($ticket['id'], 6, '0', STR_PAD_LEFT) ?></td>
                                <td><?= htmlspecialchars($ticket['user_name']) ?></td>
                                <td><?= htmlspecialchars($ticket['company_name']) ?></td>
                                <td><?= htmlspecialchars($ticket['departure_city']) ?> → <?= htmlspecialchars($ticket['arrival_city']) ?></td>
                                <td><?= formatDate($ticket['departure_date']) ?> <?= formatTime($ticket['departure_time']) ?></td>
                                <td><?= $ticket['seat_number'] ?></td>
                                <td><?= formatPrice($ticket['price']) ?></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Users Detail Section -->
        <div id="users-detail" class="detail-section" style="display: none;">
            <div class="admin-section">
                <h3>Tüm Kullanıcılar</h3>
                <div class="table-responsive">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Ad Soyad</th>
                                <th>Email</th>
                                <th>Telefon</th>
                                <th>Kayıt Tarihi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $allUsers->reset(); while ($user = $allUsers->fetchArray(SQLITE3_ASSOC)): ?>
                            <tr>
                                <td><?= $user['id'] ?></td>
                                <td><?= htmlspecialchars($user['name']) ?></td>
                                <td><?= htmlspecialchars($user['email']) ?></td>
                                <td><?= htmlspecialchars($user['phone'] ?? '-') ?></td>
                                <td><?= htmlspecialchars($user['created_at'] ?? '-') ?></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Revenue Detail Section -->
        <div id="revenue-detail" class="detail-section" style="display: none;">
            <div class="admin-section">
                <h3>Gelir Detayları</h3>
                <div class="table-responsive">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>PNR</th>
                                <th>Yolcu</th>
                                <th>Firma</th>
                                <th>Güzergah</th>
                                <th>Tarih</th>
                                <th>Tutar</th>
                                <th>Rezervasyon Tarihi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $revenueDetails->reset(); while ($ticket = $revenueDetails->fetchArray(SQLITE3_ASSOC)): ?>
                            <tr>
                                <td>BLT<?= str_pad($ticket['id'], 6, '0', STR_PAD_LEFT) ?></td>
                                <td><?= htmlspecialchars($ticket['user_name']) ?></td>
                                <td><?= htmlspecialchars($ticket['company_name']) ?></td>
                                <td><?= htmlspecialchars($ticket['departure_city']) ?> → <?= htmlspecialchars($ticket['arrival_city']) ?></td>
                                <td><?= formatDate($ticket['departure_date']) ?> <?= formatTime($ticket['departure_time']) ?></td>
                                <td><strong><?= formatPrice($ticket['price']) ?></strong></td>
                                <td><?= formatDate(substr($ticket['booking_date'], 0, 10)) ?></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <div class="admin-section">
            <h3>Son Bilet Satışları</h3>
            <div class="table-responsive">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>PNR</th>
                            <th>Yolcu</th>
                            <th>Firma</th>
                            <th>Güzergah</th>
                            <th>Tarih</th>
                            <th>Fiyat</th>
                            <th>Durum</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($ticket = $recentTickets->fetchArray(SQLITE3_ASSOC)): ?>
                        <tr>
                            <td>BLT<?= str_pad($ticket['id'], 6, '0', STR_PAD_LEFT) ?></td>
                            <td><?= htmlspecialchars($ticket['user_name']) ?></td>
                            <td><?= htmlspecialchars($ticket['company_name']) ?></td>
                            <td><?= htmlspecialchars($ticket['departure_city']) ?> → <?= htmlspecialchars($ticket['arrival_city']) ?></td>
                            <td><?= formatDate($ticket['departure_date']) ?></td>
                            <td><?= formatPrice($ticket['price']) ?></td>
                            <td><span class="badge badge-<?= $ticket['status'] ?>"><?= $ticket['status'] ?></span></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
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