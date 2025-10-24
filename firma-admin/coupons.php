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
            $code = strtoupper(sanitizeInput($_POST['code']));
            $discount = (float)$_POST['discount'];
            $usageLimit = (int)$_POST['usage_limit'];
            $expiryDate = sanitizeInput($_POST['expiry_date']);

            if ($discount <= 0 || $discount > 100) {
                $error = 'İndirim oranı 1-100 arasında olmalıdır!';
            } else {
                if ($_POST['action'] === 'add') {
                    // Check if coupon code already exists
                    $checkStmt = $db->prepare("SELECT id FROM coupons WHERE code = :code");
                    $checkStmt->bindValue(':code', $code, SQLITE3_TEXT);
                    $checkResult = $checkStmt->execute();

                    if ($checkResult->fetchArray()) {
                        $error = 'Bu kupon kodu zaten kullanılıyor!';
                    } else {
                        $stmt = $db->prepare("INSERT INTO coupons (code, discount_percentage, usage_limit,
                                              used_count, expiry_date, company_id)
                                              VALUES (:code, :discount, :usage_limit, 0, :expiry_date, :company_id)");
                        $stmt->bindValue(':code', $code, SQLITE3_TEXT);
                        $stmt->bindValue(':discount', $discount, SQLITE3_FLOAT);
                        $stmt->bindValue(':usage_limit', $usageLimit, SQLITE3_INTEGER);
                        $stmt->bindValue(':expiry_date', $expiryDate, SQLITE3_TEXT);
                        $stmt->bindValue(':company_id', $companyId, SQLITE3_INTEGER);

                        if ($stmt->execute()) {
                            $success = 'Kupon başarıyla eklendi!';
                        } else {
                            $error = 'Kupon eklenirken hata oluştu!';
                        }
                    }
                } else {
                    $couponId = (int)$_POST['coupon_id'];

                    // Verify coupon belongs to this company
                    $checkStmt = $db->prepare("SELECT company_id FROM coupons WHERE id = :id");
                    $checkStmt->bindValue(':id', $couponId, SQLITE3_INTEGER);
                    $checkResult = $checkStmt->execute();
                    $couponCheck = $checkResult->fetchArray(SQLITE3_ASSOC);

                    if ($couponCheck && $couponCheck['company_id'] == $companyId) {
                        $stmt = $db->prepare("UPDATE coupons SET code = :code, discount_percentage = :discount,
                                              usage_limit = :usage_limit, expiry_date = :expiry_date
                                              WHERE id = :id");
                        $stmt->bindValue(':code', $code, SQLITE3_TEXT);
                        $stmt->bindValue(':discount', $discount, SQLITE3_FLOAT);
                        $stmt->bindValue(':usage_limit', $usageLimit, SQLITE3_INTEGER);
                        $stmt->bindValue(':expiry_date', $expiryDate, SQLITE3_TEXT);
                        $stmt->bindValue(':id', $couponId, SQLITE3_INTEGER);

                        if ($stmt->execute()) {
                            $success = 'Kupon başarıyla güncellendi!';
                        } else {
                            $error = 'Kupon güncellenirken hata oluştu!';
                        }
                    } else {
                        $error = 'Bu kuponu düzenleme yetkiniz yok!';
                    }
                }
            }
        } elseif ($_POST['action'] === 'delete') {
            $couponId = (int)$_POST['coupon_id'];

            // Verify coupon belongs to this company
            $checkStmt = $db->prepare("SELECT company_id FROM coupons WHERE id = :id");
            $checkStmt->bindValue(':id', $couponId, SQLITE3_INTEGER);
            $checkResult = $checkStmt->execute();
            $couponCheck = $checkResult->fetchArray(SQLITE3_ASSOC);

            if ($couponCheck && $couponCheck['company_id'] == $companyId) {
                $stmt = $db->prepare("DELETE FROM coupons WHERE id = :id");
                $stmt->bindValue(':id', $couponId, SQLITE3_INTEGER);

                if ($stmt->execute()) {
                    $success = 'Kupon başarıyla silindi!';
                } else {
                    $error = 'Kupon silinirken hata oluştu!';
                }
            } else {
                $error = 'Bu kuponu silme yetkiniz yok!';
            }
        }
    }
}

// Get coupon to edit
$editCoupon = null;
if (isset($_GET['edit'])) {
    $editId = (int)$_GET['edit'];
    $stmt = $db->prepare("SELECT * FROM coupons WHERE id = :id AND company_id = :company_id");
    $stmt->bindValue(':id', $editId, SQLITE3_INTEGER);
    $stmt->bindValue(':company_id', $companyId, SQLITE3_INTEGER);
    $result = $stmt->execute();
    $editCoupon = $result->fetchArray(SQLITE3_ASSOC);
}

// Get all coupons for this company
$stmt = $db->prepare("SELECT * FROM coupons WHERE company_id = :company_id
                      ORDER BY expiry_date DESC");
$stmt->bindValue(':company_id', $companyId, SQLITE3_INTEGER);
$result = $stmt->execute();

$coupons = [];
while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
    $coupons[] = $row;
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kupon Yönetimi</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php include '../includes/header.php'; ?>

    <div class="container">
        <div class="admin-header">
            <h2>Kupon Yönetimi - <?= htmlspecialchars($company['name']) ?></h2>
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
            <h3><?= $editCoupon ? 'Kupon Düzenle' : 'Yeni Kupon Ekle' ?></h3>

            <form method="POST" class="admin-form">
                <input type="hidden" name="action" value="<?= $editCoupon ? 'edit' : 'add' ?>">
                <?php if ($editCoupon): ?>
                    <input type="hidden" name="coupon_id" value="<?= $editCoupon['id'] ?>">
                <?php endif; ?>

                <div class="form-row">
                    <div class="form-group">
                        <label for="code">Kupon Kodu</label>
                        <input type="text" name="code" id="code"
                               value="<?= $editCoupon ? htmlspecialchars($editCoupon['code']) : '' ?>"
                               placeholder="örn: YILBASI2025" required>
                        <small>Kupon kodu büyük harfe dönüştürülecektir.</small>
                    </div>

                    <div class="form-group">
                        <label for="discount">İndirim Oranı (%)</label>
                        <input type="number" step="0.01" name="discount" id="discount"
                               value="<?= $editCoupon ? $editCoupon['discount_percentage'] : '' ?>"
                               min="1" max="100" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="usage_limit">Kullanım Limiti</label>
                        <input type="number" name="usage_limit" id="usage_limit"
                               value="<?= $editCoupon ? $editCoupon['usage_limit'] : '100' ?>"
                               min="1" required>
                    </div>

                    <div class="form-group">
                        <label for="expiry_date">Son Kullanma Tarihi</label>
                        <input type="date" name="expiry_date" id="expiry_date"
                               value="<?= $editCoupon ? $editCoupon['expiry_date'] : '' ?>" required>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary">
                    <?= $editCoupon ? 'Güncelle' : 'Ekle' ?>
                </button>

                <?php if ($editCoupon): ?>
                    <a href="coupons.php" class="btn btn-secondary">İptal</a>
                <?php endif; ?>
            </form>
        </div>

        <div class="admin-section">
            <h3>Tüm Kuponlar</h3>

            <?php if (empty($coupons)): ?>
                <p>Henüz kupon bulunmuyor.</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Kod</th>
                                <th>İndirim</th>
                                <th>Kullanım</th>
                                <th>Son Kullanma</th>
                                <th>Durum</th>
                                <th>İşlemler</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($coupons as $coupon): ?>
                                <?php
                                $isExpired = strtotime($coupon['expiry_date']) < time();
                                $isFull = $coupon['used_count'] >= $coupon['usage_limit'];
                                $isActive = !$isExpired && !$isFull;
                                ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($coupon['code']) ?></strong></td>
                                    <td>%<?= $coupon['discount_percentage'] ?></td>
                                    <td><?= $coupon['used_count'] ?> / <?= $coupon['usage_limit'] ?></td>
                                    <td><?= formatDate($coupon['expiry_date']) ?></td>
                                    <td>
                                        <?php if ($isActive): ?>
                                            <span class="badge badge-active">Aktif</span>
                                        <?php else: ?>
                                            <span class="badge badge-cancelled">
                                                <?= $isExpired ? 'Süresi Doldu' : 'Limit Doldu' ?>
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="action-buttons">
                                        <a href="?edit=<?= $coupon['id'] ?>" class="btn btn-sm btn-secondary">Düzenle</a>
                                        <form method="POST" style="display: inline;"
                                              onsubmit="return confirm('Bu kuponu silmek istediğinizden emin misiniz?');">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="coupon_id" value="<?= $coupon['id'] ?>">
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
