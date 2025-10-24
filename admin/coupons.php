<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

requireRole('admin');

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'add') {
        $code = strtoupper(sanitizeInput($_POST['code']));
        $discount = (int)$_POST['discount_percentage'];
        $usageLimit = (int)$_POST['usage_limit'];
        $expiryDate = sanitizeInput($_POST['expiry_date']);

        $stmt = $db->prepare("INSERT INTO coupons (code, discount_percentage, usage_limit, expiry_date)
                             VALUES (:code, :discount, :limit, :expiry)");
        $stmt->bindValue(':code', $code, SQLITE3_TEXT);
        $stmt->bindValue(':discount', $discount, SQLITE3_INTEGER);
        $stmt->bindValue(':limit', $usageLimit, SQLITE3_INTEGER);
        $stmt->bindValue(':expiry', $expiryDate, SQLITE3_TEXT);

        if ($stmt->execute()) {
            $success = 'Kupon başarıyla eklendi!';
        } else {
            $error = 'Bu kupon kodu zaten kullanılıyor!';
        }
    } elseif (isset($_POST['action']) && $_POST['action'] === 'edit') {
        $id = (int)$_POST['id'];
        $code = strtoupper(sanitizeInput($_POST['code']));
        $discount = (int)$_POST['discount_percentage'];
        $usageLimit = (int)$_POST['usage_limit'];
        $expiryDate = sanitizeInput($_POST['expiry_date']);

        // Check if code is already used by another coupon
        $checkStmt = $db->prepare("SELECT id FROM coupons WHERE code = :code AND id != :id");
        $checkStmt->bindValue(':code', $code, SQLITE3_TEXT);
        $checkStmt->bindValue(':id', $id, SQLITE3_INTEGER);
        $result = $checkStmt->execute();

        if ($result->fetchArray()) {
            $error = 'Bu kupon kodu başka bir kupon tarafından kullanılıyor!';
        } else {
            $stmt = $db->prepare("UPDATE coupons SET code = :code, discount_percentage = :discount,
                                 usage_limit = :limit, expiry_date = :expiry WHERE id = :id");
            $stmt->bindValue(':code', $code, SQLITE3_TEXT);
            $stmt->bindValue(':discount', $discount, SQLITE3_INTEGER);
            $stmt->bindValue(':limit', $usageLimit, SQLITE3_INTEGER);
            $stmt->bindValue(':expiry', $expiryDate, SQLITE3_TEXT);
            $stmt->bindValue(':id', $id, SQLITE3_INTEGER);

            if ($stmt->execute()) {
                $success = 'Kupon başarıyla güncellendi!';
            } else {
                $error = 'Güncelleme sırasında bir hata oluştu!';
            }
        }
    } elseif (isset($_POST['action']) && $_POST['action'] === 'delete') {
        $id = (int)$_POST['id'];
        $stmt = $db->prepare("DELETE FROM coupons WHERE id = :id");
        $stmt->bindValue(':id', $id, SQLITE3_INTEGER);
        $stmt->execute();
        $success = 'Kupon silindi!';
    }
}

$coupons = $db->query("SELECT c.*, co.name as company_name FROM coupons c 
                       LEFT JOIN companies co ON c.company_id = co.id 
                       WHERE c.company_id IS NULL 
                       ORDER BY c.created_at DESC");
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kupon Yönetimi - Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <div class="container">
        <div class="admin-header">
            <h2>Genel Kupon Yönetimi</h2>
            <a href="index.php" class="btn btn-secondary">← Admin Paneli</a>
        </div>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>
        
        <div class="admin-section">
            <h3>Yeni Kupon Ekle (Tüm Firmalar)</h3>
            <form method="POST" class="admin-form">
                <input type="hidden" name="action" value="add">
                <div class="form-row">
                    <div class="form-group">
                        <label for="code">Kupon Kodu</label>
                        <input type="text" name="code" id="code" required>
                    </div>
                    <div class="form-group">
                        <label for="discount_percentage">İndirim Oranı (%)</label>
                        <input type="number" name="discount_percentage" id="discount_percentage" min="1" max="100" required>
                    </div>
                    <div class="form-group">
                        <label for="usage_limit">Kullanım Limiti</label>
                        <input type="number" name="usage_limit" id="usage_limit" min="1" value="100" required>
                    </div>
                    <div class="form-group">
                        <label for="expiry_date">Son Kullanma Tarihi</label>
                        <input type="date" name="expiry_date" id="expiry_date" min="<?= date('Y-m-d') ?>" required>
                    </div>
                    <div class="form-group">
                        <button type="submit" class="btn btn-primary">Ekle</button>
                    </div>
                </div>
            </form>
        </div>
        
        <div class="admin-section">
            <h3>Mevcut Kuponlar</h3>
            <div class="table-responsive">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Kod</th>
                            <th>İndirim</th>
                            <th>Kullanım</th>
                            <th>Son Tarih</th>
                            <th>İşlemler</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($coupon = $coupons->fetchArray(SQLITE3_ASSOC)): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($coupon['code']) ?></strong></td>
                            <td>%<?= $coupon['discount_percentage'] ?></td>
                            <td><?= $coupon['used_count'] ?> / <?= $coupon['usage_limit'] ?></td>
                            <td><?= formatDate($coupon['expiry_date']) ?></td>
                            <td class="action-buttons">
                                <button class="btn btn-warning btn-sm" onclick="editCoupon(<?= htmlspecialchars(json_encode($coupon)) ?>)">Düzenle</button>
                                <form method="POST" style="display:inline;"
                                      onsubmit="return confirm('Bu kuponu silmek istediğinizden emin misiniz?')">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= $coupon['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-danger">Sil</button>
                                </form>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Edit Modal -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeEditModal()">&times;</span>
            <h3>Kupon Düzenle</h3>
            <form method="POST" id="editForm">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="id" id="edit_id">
                <div class="form-group">
                    <label for="edit_code">Kupon Kodu</label>
                    <input type="text" name="code" id="edit_code" required>
                </div>
                <div class="form-group">
                    <label for="edit_discount_percentage">İndirim Oranı (%)</label>
                    <input type="number" name="discount_percentage" id="edit_discount_percentage" min="1" max="100" required>
                </div>
                <div class="form-group">
                    <label for="edit_usage_limit">Kullanım Limiti</label>
                    <input type="number" name="usage_limit" id="edit_usage_limit" min="1" required>
                </div>
                <div class="form-group">
                    <label for="edit_expiry_date">Son Kullanma Tarihi</label>
                    <input type="date" name="expiry_date" id="edit_expiry_date" required>
                </div>
                <div class="form-group">
                    <button type="submit" class="btn btn-primary">Güncelle</button>
                    <button type="button" class="btn btn-secondary" onclick="closeEditModal()">İptal</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function editCoupon(coupon) {
            document.getElementById('edit_id').value = coupon.id;
            document.getElementById('edit_code').value = coupon.code;
            document.getElementById('edit_discount_percentage').value = coupon.discount_percentage;
            document.getElementById('edit_usage_limit').value = coupon.usage_limit;
            document.getElementById('edit_expiry_date').value = coupon.expiry_date;
            document.getElementById('editModal').style.display = 'block';
        }

        function closeEditModal() {
            document.getElementById('editModal').style.display = 'none';
        }

        window.onclick = function(event) {
            const modal = document.getElementById('editModal');
            if (event.target == modal) {
                closeEditModal();
            }
        }
    </script>

    <?php include '../includes/footer.php'; ?>
</body>
</html>