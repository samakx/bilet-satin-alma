<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

requireLogin();

$user = getCurrentUser();
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';
$statusFilter = ($filter === 'active') ? 'active' : (($filter === 'cancelled') ? 'cancelled' : null);
$tickets = getUserTickets($db, $user['id'], $statusFilter);
$success = isset($_GET['success']) ? true : false;
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Biletlerim</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container">
        <h2>Biletlerim</h2>

        <?php if ($success): ?>
            <div class="alert alert-success">Biletiniz başarıyla satın alındı!</div>
        <?php endif; ?>

        <div class="user-info-card">
            <h3><?= htmlspecialchars($user['name']) ?></h3>
            <p>Email: <?= htmlspecialchars($user['email']) ?></p>
            <p>Bakiye: <strong><?= formatPrice($user['balance']) ?></strong></p>
        </div>

        <div class="ticket-filters">
            <a href="my-tickets.php?filter=all" class="filter-btn <?= $filter === 'all' ? 'active' : '' ?>">Tüm Biletler</a>
            <a href="my-tickets.php?filter=active" class="filter-btn <?= $filter === 'active' ? 'active' : '' ?>">Aktif Biletler</a>
            <a href="my-tickets.php?filter=cancelled" class="filter-btn <?= $filter === 'cancelled' ? 'active' : '' ?>">İptal Edilenler</a>
        </div>
        
        <?php if (empty($tickets)): ?>
            <div class="alert alert-info">
                <p>Henüz biletiniz bulunmamaktadır.</p>
                <a href="index.php" class="btn btn-primary">Sefer Ara</a>
            </div>
        <?php else: ?>
            <div class="tickets-list">
                <?php foreach ($tickets as $ticket): ?>
                    <?php $canCancel = canCancelTicket($db, $ticket['id']); ?>
                    <div class="ticket-card <?= $ticket['status'] === 'cancelled' ? 'cancelled' : '' ?>">
                        <div class="ticket-header">
                            <h3><?= htmlspecialchars($ticket['company_name']) ?></h3>
                            <span class="ticket-status status-<?= $ticket['status'] ?>">
                                <?= $ticket['status'] === 'active' ? 'Aktif' : 'İptal Edildi' ?>
                            </span>
                        </div>
                        
                        <div class="ticket-body">
                            <div class="ticket-route">
                                <div class="route-point">
                                    <strong><?= htmlspecialchars($ticket['departure_city']) ?></strong>
                                    <span><?= formatTime($ticket['departure_time']) ?></span>
                                </div>
                                <div class="route-arrow">→</div>
                                <div class="route-point">
                                    <strong><?= htmlspecialchars($ticket['arrival_city']) ?></strong>
                                    <span><?= formatTime($ticket['arrival_time']) ?></span>
                                </div>
                            </div>
                            
                            <div class="ticket-details">
                                <p><strong>Tarih:</strong> <?= formatDate($ticket['departure_date']) ?></p>
                                <p><strong>Koltuk:</strong> <?= $ticket['seat_number'] ?></p>
                                <p><strong>Fiyat:</strong> <?= formatPrice($ticket['price']) ?></p>
                                <?php if ($ticket['coupon_code']): ?>
                                    <p><strong>Kupon:</strong> <?= htmlspecialchars($ticket['coupon_code']) ?> 
                                       (<?= formatPrice($ticket['discount_amount']) ?> indirim)</p>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <?php if ($ticket['status'] === 'active'): ?>
                            <div class="ticket-actions">
                                <a href="download-pdf.php?id=<?= $ticket['id'] ?>" class="btn btn-secondary" target="_blank">
                                    PDF İndir
                                </a>
                                <?php if ($canCancel): ?>
                                    <a href="cancel-ticket.php?id=<?= $ticket['id'] ?>" 
                                       class="btn btn-danger"
                                       onclick="return confirm('Bu bileti iptal etmek istediğinizden emin misiniz?')">
                                        İptal Et
                                    </a>
                                <?php else: ?>
                                    <span class="text-muted">İptal süresi geçti</span>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    
    <?php include 'includes/footer.php'; ?>
</body>
</html>