<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

requireRole('admin');

$error = '';
$success = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'add') {
        $email = sanitizeInput($_POST['email']);
        $password = $_POST['password'];
        $name = sanitizeInput($_POST['name']);
        $phone = sanitizeInput($_POST['phone']);
        $companyId = (int)$_POST['company_id'];

        if (!validateEmail($email)) {
            $error = 'Geçerli bir email adresi girin!';
        } elseif (strlen($password) < 6) {
            $error = 'Şifre en az 6 karakter olmalıdır!';
        } else {
            $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

            $stmt = $db->prepare("INSERT INTO users (email, password, name, phone, role, company_id, balance)
                                 VALUES (:email, :password, :name, :phone, 'company_admin', :company_id, 5000)");
            $stmt->bindValue(':email', $email, SQLITE3_TEXT);
            $stmt->bindValue(':password', $hashedPassword, SQLITE3_TEXT);
            $stmt->bindValue(':name', $name, SQLITE3_TEXT);
            $stmt->bindValue(':phone', $phone, SQLITE3_TEXT);
            $stmt->bindValue(':company_id', $companyId, SQLITE3_INTEGER);

            if ($stmt->execute()) {
                $success = 'Firma admin başarıyla eklendi!';
            } else {
                $error = 'Bu email adresi zaten kullanılıyor!';
            }
        }
    } elseif (isset($_POST['action']) && $_POST['action'] === 'edit') {
        $id = (int)$_POST['id'];
        $email = sanitizeInput($_POST['email']);
        $name = sanitizeInput($_POST['name']);
        $phone = sanitizeInput($_POST['phone']);
        $companyId = (int)$_POST['company_id'];

        if (!validateEmail($email)) {
            $error = 'Geçerli bir email adresi girin!';
        } else {
            // Check if email is already used by another user
            $checkStmt = $db->prepare("SELECT id FROM users WHERE email = :email AND id != :id");
            $checkStmt->bindValue(':email', $email, SQLITE3_TEXT);
            $checkStmt->bindValue(':id', $id, SQLITE3_INTEGER);
            $result = $checkStmt->execute();

            if ($result->fetchArray()) {
                $error = 'Bu email adresi başka bir kullanıcı tarafından kullanılıyor!';
            } else {
                // Update user info
                if (!empty($_POST['password'])) {
                    $password = $_POST['password'];
                    if (strlen($password) < 6) {
                        $error = 'Şifre en az 6 karakter olmalıdır!';
                    } else {
                        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
                        $stmt = $db->prepare("UPDATE users SET email = :email, password = :password, name = :name,
                                             phone = :phone, company_id = :company_id WHERE id = :id");
                        $stmt->bindValue(':password', $hashedPassword, SQLITE3_TEXT);
                        $stmt->bindValue(':email', $email, SQLITE3_TEXT);
                        $stmt->bindValue(':name', $name, SQLITE3_TEXT);
                        $stmt->bindValue(':phone', $phone, SQLITE3_TEXT);
                        $stmt->bindValue(':company_id', $companyId, SQLITE3_INTEGER);
                        $stmt->bindValue(':id', $id, SQLITE3_INTEGER);

                        if ($stmt->execute()) {
                            $success = 'Firma admin başarıyla güncellendi!';
                        } else {
                            $error = 'Güncelleme sırasında bir hata oluştu!';
                        }
                    }
                } else {
                    // Update without password
                    $stmt = $db->prepare("UPDATE users SET email = :email, name = :name, phone = :phone,
                                         company_id = :company_id WHERE id = :id");
                    $stmt->bindValue(':email', $email, SQLITE3_TEXT);
                    $stmt->bindValue(':name', $name, SQLITE3_TEXT);
                    $stmt->bindValue(':phone', $phone, SQLITE3_TEXT);
                    $stmt->bindValue(':company_id', $companyId, SQLITE3_INTEGER);
                    $stmt->bindValue(':id', $id, SQLITE3_INTEGER);

                    if ($stmt->execute()) {
                        $success = 'Firma admin başarıyla güncellendi!';
                    } else {
                        $error = 'Güncelleme sırasında bir hata oluştu!';
                    }
                }
            }
        }
    } elseif (isset($_POST['action']) && $_POST['action'] === 'delete') {
        $id = (int)$_POST['id'];

        // Check if admin has any active trips
        $checkStmt = $db->prepare("SELECT COUNT(*) as count FROM trips WHERE company_id IN
                                   (SELECT company_id FROM users WHERE id = :id)");
        $checkStmt->bindValue(':id', $id, SQLITE3_INTEGER);
        $result = $checkStmt->execute();
        $row = $result->fetchArray(SQLITE3_ASSOC);

        if ($row['count'] > 0) {
            $error = 'Bu firma admini silinemez! Firma\'ya ait aktif seferler var.';
        } else {
            $stmt = $db->prepare("DELETE FROM users WHERE id = :id AND role = 'company_admin'");
            $stmt->bindValue(':id', $id, SQLITE3_INTEGER);

            if ($stmt->execute()) {
                $success = 'Firma admin başarıyla silindi!';
            } else {
                $error = 'Silme işlemi sırasında bir hata oluştu!';
            }
        }
    }
}

// Get all company admins
$companyAdmins = $db->query("
    SELECT u.*, c.name as company_name 
    FROM users u
    LEFT JOIN companies c ON u.company_id = c.id
    WHERE u.role = 'company_admin'
    ORDER BY u.name ASC
");

// Get all companies for dropdown
$companies = $db->query("SELECT * FROM companies ORDER BY name ASC");
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Firma Admin Yönetimi</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <div class="container">
        <div class="admin-header">
            <h2>Firma Admin Yönetimi</h2>
            <a href="index.php" class="btn btn-secondary">← Admin Paneli</a>
        </div>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>
        
        <div class="admin-section">
            <h3>Yeni Firma Admin Ekle</h3>
            <form method="POST" class="admin-form">
                <input type="hidden" name="action" value="add">
                <div class="form-row">
                    <div class="form-group">
                        <label for="name">Ad Soyad</label>
                        <input type="text" name="name" id="name" required>
                    </div>
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" name="email" id="email" required>
                    </div>
                    <div class="form-group">
                        <label for="password">Şifre</label>
                        <input type="password" name="password" id="password" required minlength="6">
                    </div>
                    <div class="form-group">
                        <label for="phone">Telefon</label>
                        <input type="tel" name="phone" id="phone">
                    </div>
                    <div class="form-group">
                        <label for="company_id">Firma</label>
                        <select name="company_id" id="company_id" required>
                            <option value="">Firma Seçin</option>
                            <?php 
                            $companies->reset();
                            while ($company = $companies->fetchArray(SQLITE3_ASSOC)): 
                            ?>
                                <option value="<?= $company['id'] ?>"><?= htmlspecialchars($company['name']) ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <button type="submit" class="btn btn-primary">Ekle</button>
                    </div>
                </div>
            </form>
        </div>
        
        <div class="admin-section">
            <h3>Mevcut Firma Adminleri</h3>
            <div class="table-responsive">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Ad Soyad</th>
                            <th>Email</th>
                            <th>Telefon</th>
                            <th>Firma</th>
                            <th>Bakiye</th>
                            <th>İşlemler</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($admin = $companyAdmins->fetchArray(SQLITE3_ASSOC)): ?>
                        <tr>
                            <td><?= $admin['id'] ?></td>
                            <td><?= htmlspecialchars($admin['name']) ?></td>
                            <td><?= htmlspecialchars($admin['email']) ?></td>
                            <td><?= htmlspecialchars($admin['phone']) ?></td>
                            <td><?= htmlspecialchars($admin['company_name']) ?></td>
                            <td><?= formatPrice($admin['balance']) ?></td>
                            <td class="action-buttons">
                                <button class="btn btn-warning btn-sm" onclick="editAdmin(<?= htmlspecialchars(json_encode($admin)) ?>)">Düzenle</button>
                                <form method="POST" style="display: inline;" onsubmit="return confirm('Bu firma adminini silmek istediğinizden emin misiniz?');">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= $admin['id'] ?>">
                                    <button type="submit" class="btn btn-danger btn-sm">Sil</button>
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
            <h3>Firma Admin Düzenle</h3>
            <form method="POST" id="editForm">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="id" id="edit_id">
                <div class="form-group">
                    <label for="edit_name">Ad Soyad</label>
                    <input type="text" name="name" id="edit_name" required>
                </div>
                <div class="form-group">
                    <label for="edit_email">Email</label>
                    <input type="email" name="email" id="edit_email" required>
                </div>
                <div class="form-group">
                    <label for="edit_password">Yeni Şifre (boş bırakın değiştirmek istemiyorsanız)</label>
                    <input type="password" name="password" id="edit_password" minlength="6">
                </div>
                <div class="form-group">
                    <label for="edit_phone">Telefon</label>
                    <input type="tel" name="phone" id="edit_phone">
                </div>
                <div class="form-group">
                    <label for="edit_company_id">Firma</label>
                    <select name="company_id" id="edit_company_id" required>
                        <?php
                        $companies->reset();
                        while ($company = $companies->fetchArray(SQLITE3_ASSOC)):
                        ?>
                            <option value="<?= $company['id'] ?>"><?= htmlspecialchars($company['name']) ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="form-group">
                    <button type="submit" class="btn btn-primary">Güncelle</button>
                    <button type="button" class="btn btn-secondary" onclick="closeEditModal()">İptal</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function editAdmin(admin) {
            document.getElementById('edit_id').value = admin.id;
            document.getElementById('edit_name').value = admin.name;
            document.getElementById('edit_email').value = admin.email;
            document.getElementById('edit_phone').value = admin.phone || '';
            document.getElementById('edit_company_id').value = admin.company_id;
            document.getElementById('edit_password').value = '';
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