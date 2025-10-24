<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

requireRole('company_admin');

$user = getCurrentUser();
$companyId = $user['company_id'];
$error = '';
$success = '';

// Get company info
$stmt = $db->prepare("SELECT * FROM companies WHERE id = :id");
$stmt->bindValue(':id', $companyId, SQLITE3_INTEGER);
$result = $stmt->execute();
$company = $result->fetchArray(SQLITE3_ASSOC);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'add' || $_POST['action'] === 'edit') {
            $departureCity = sanitizeInput($_POST['departure_city']);
            $arrivalCity = sanitizeInput($_POST['arrival_city']);
            $departureDate = sanitizeInput($_POST['departure_date']);
            $departureTime = sanitizeInput($_POST['departure_time']);
            $arrivalTime = sanitizeInput($_POST['arrival_time']);
            $price = (float)$_POST['price'];
            $totalSeats = (int)$_POST['total_seats'];
            $busPlate = sanitizeInput($_POST['bus_plate']);

            if ($_POST['action'] === 'add') {
                $stmt = $db->prepare("INSERT INTO trips (company_id, departure_city, arrival_city,
                                      departure_date, departure_time, arrival_time, price, total_seats,
                                      available_seats, bus_plate)
                                      VALUES (:company_id, :dep_city, :arr_city, :dep_date, :dep_time,
                                      :arr_time, :price, :total_seats, :total_seats, :bus_plate)");
                $stmt->bindValue(':company_id', $companyId, SQLITE3_INTEGER);
                $stmt->bindValue(':dep_city', $departureCity, SQLITE3_TEXT);
                $stmt->bindValue(':arr_city', $arrivalCity, SQLITE3_TEXT);
                $stmt->bindValue(':dep_date', $departureDate, SQLITE3_TEXT);
                $stmt->bindValue(':dep_time', $departureTime, SQLITE3_TEXT);
                $stmt->bindValue(':arr_time', $arrivalTime, SQLITE3_TEXT);
                $stmt->bindValue(':price', $price, SQLITE3_FLOAT);
                $stmt->bindValue(':total_seats', $totalSeats, SQLITE3_INTEGER);
                $stmt->bindValue(':bus_plate', $busPlate, SQLITE3_TEXT);

                if ($stmt->execute()) {
                    $success = 'Sefer başarıyla eklendi!';
                } else {
                    $error = 'Sefer eklenirken hata oluştu!';
                }
            } else {
                $tripId = (int)$_POST['trip_id'];

                // Verify trip belongs to this company
                $checkStmt = $db->prepare("SELECT company_id FROM trips WHERE id = :id");
                $checkStmt->bindValue(':id', $tripId, SQLITE3_INTEGER);
                $checkResult = $checkStmt->execute();
                $tripCheck = $checkResult->fetchArray(SQLITE3_ASSOC);

                if ($tripCheck && $tripCheck['company_id'] == $companyId) {
                    $stmt = $db->prepare("UPDATE trips SET departure_city = :dep_city,
                                          arrival_city = :arr_city, departure_date = :dep_date,
                                          departure_time = :dep_time, arrival_time = :arr_time,
                                          price = :price, bus_plate = :bus_plate
                                          WHERE id = :id");
                    $stmt->bindValue(':dep_city', $departureCity, SQLITE3_TEXT);
                    $stmt->bindValue(':arr_city', $arrivalCity, SQLITE3_TEXT);
                    $stmt->bindValue(':dep_date', $departureDate, SQLITE3_TEXT);
                    $stmt->bindValue(':dep_time', $departureTime, SQLITE3_TEXT);
                    $stmt->bindValue(':arr_time', $arrivalTime, SQLITE3_TEXT);
                    $stmt->bindValue(':price', $price, SQLITE3_FLOAT);
                    $stmt->bindValue(':bus_plate', $busPlate, SQLITE3_TEXT);
                    $stmt->bindValue(':id', $tripId, SQLITE3_INTEGER);

                    if ($stmt->execute()) {
                        $success = 'Sefer başarıyla güncellendi!';
                    } else {
                        $error = 'Sefer güncellenirken hata oluştu!';
                    }
                } else {
                    $error = 'Bu seferi düzenleme yetkiniz yok!';
                }
            }
        } elseif ($_POST['action'] === 'delete') {
            $tripId = (int)$_POST['trip_id'];

            // Verify trip belongs to this company
            $checkStmt = $db->prepare("SELECT company_id FROM trips WHERE id = :id");
            $checkStmt->bindValue(':id', $tripId, SQLITE3_INTEGER);
            $checkResult = $checkStmt->execute();
            $tripCheck = $checkResult->fetchArray(SQLITE3_ASSOC);

            if ($tripCheck && $tripCheck['company_id'] == $companyId) {
                // Check if there are active tickets
                $ticketCheck = $db->prepare("SELECT COUNT(*) as count FROM tickets
                                             WHERE trip_id = :id AND status = 'active'");
                $ticketCheck->bindValue(':id', $tripId, SQLITE3_INTEGER);
                $ticketResult = $ticketCheck->execute();
                $ticketCount = $ticketResult->fetchArray(SQLITE3_ASSOC);

                if ($ticketCount['count'] > 0) {
                    $error = 'Bu sefere ait aktif biletler var, silinemez!';
                } else {
                    $stmt = $db->prepare("DELETE FROM trips WHERE id = :id");
                    $stmt->bindValue(':id', $tripId, SQLITE3_INTEGER);

                    if ($stmt->execute()) {
                        $success = 'Sefer başarıyla silindi!';
                    } else {
                        $error = 'Sefer silinirken hata oluştu!';
                    }
                }
            } else {
                $error = 'Bu seferi silme yetkiniz yok!';
            }
        }
    }
}

// Get trip to edit
$editTrip = null;
if (isset($_GET['edit'])) {
    $editId = (int)$_GET['edit'];
    $stmt = $db->prepare("SELECT * FROM trips WHERE id = :id AND company_id = :company_id");
    $stmt->bindValue(':id', $editId, SQLITE3_INTEGER);
    $stmt->bindValue(':company_id', $companyId, SQLITE3_INTEGER);
    $result = $stmt->execute();
    $editTrip = $result->fetchArray(SQLITE3_ASSOC);
}

// Get all trips for this company
$stmt = $db->prepare("SELECT * FROM trips WHERE company_id = :company_id
                      ORDER BY departure_date DESC, departure_time DESC");
$stmt->bindValue(':company_id', $companyId, SQLITE3_INTEGER);
$result = $stmt->execute();

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
    <title>Sefer Yönetimi</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php include '../includes/header.php'; ?>

    <div class="container">
        <div class="admin-header">
            <h2>Sefer Yönetimi - <?= htmlspecialchars($company['name']) ?></h2>
            <div class="admin-nav">
                <a href="index.php" class="btn btn-secondary">← Geri</a>
            </div>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <div class="admin-section">
            <h3><?= $editTrip ? 'Sefer Düzenle' : 'Yeni Sefer Ekle' ?></h3>

            <form method="POST" class="admin-form">
                <input type="hidden" name="action" value="<?= $editTrip ? 'edit' : 'add' ?>">
                <?php if ($editTrip): ?>
                    <input type="hidden" name="trip_id" value="<?= $editTrip['id'] ?>">
                <?php endif; ?>

                <div class="form-row">
                    <div class="form-group">
                        <label for="departure_city">Kalkış Şehri</label>
                        <input type="text" name="departure_city" id="departure_city"
                               value="<?= $editTrip ? htmlspecialchars($editTrip['departure_city']) : '' ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="arrival_city">Varış Şehri</label>
                        <input type="text" name="arrival_city" id="arrival_city"
                               value="<?= $editTrip ? htmlspecialchars($editTrip['arrival_city']) : '' ?>" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="departure_date">Kalkış Tarihi</label>
                        <input type="date" name="departure_date" id="departure_date"
                               value="<?= $editTrip ? $editTrip['departure_date'] : '' ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="departure_time">Kalkış Saati</label>
                        <input type="time" name="departure_time" id="departure_time"
                               value="<?= $editTrip ? $editTrip['departure_time'] : '' ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="arrival_time">Varış Saati</label>
                        <input type="time" name="arrival_time" id="arrival_time"
                               value="<?= $editTrip ? $editTrip['arrival_time'] : '' ?>" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="price">Fiyat (₺)</label>
                        <input type="number" step="0.01" name="price" id="price"
                               value="<?= $editTrip ? $editTrip['price'] : '' ?>" required>
                    </div>

                    <?php if (!$editTrip): ?>
                    <div class="form-group">
                        <label for="total_seats">Toplam Koltuk Sayısı</label>
                        <input type="number" name="total_seats" id="total_seats"
                               value="40" min="1" max="60" required>
                    </div>
                    <?php endif; ?>

                    <div class="form-group">
                        <label for="bus_plate">Otobüs Plakası</label>
                        <input type="text" name="bus_plate" id="bus_plate"
                               value="<?= $editTrip ? htmlspecialchars($editTrip['bus_plate']) : '' ?>" required>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary">
                    <?= $editTrip ? 'Güncelle' : 'Ekle' ?>
                </button>

                <?php if ($editTrip): ?>
                    <a href="trips.php" class="btn btn-secondary">İptal</a>
                <?php endif; ?>
            </form>
        </div>

        <div class="admin-section">
            <h3>Tüm Seferler</h3>

            <?php if (empty($trips)): ?>
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
                                <th>Plaka</th>
                                <th>Koltuk</th>
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
                                    <td><?= htmlspecialchars($trip['bus_plate']) ?></td>
                                    <td><?= $trip['available_seats'] ?> / <?= $trip['total_seats'] ?></td>
                                    <td class="action-buttons">
                                        <a href="?edit=<?= $trip['id'] ?>" class="btn btn-sm btn-secondary">Düzenle</a>
                                        <form method="POST" style="display: inline;"
                                              onsubmit="return confirm('Bu seferi silmek istediğinizden emin misiniz?');">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="trip_id" value="<?= $trip['id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-danger">Sil</button>
                                        </form>
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
</body>
</html>
