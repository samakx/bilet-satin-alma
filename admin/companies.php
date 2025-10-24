<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

requireRole('admin');

$error = '';
$success = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'add') {
            $name = sanitizeInput($_POST['name']);
            $phone = sanitizeInput($_POST['phone']);
            $email = sanitizeInput($_POST['email']);
            
            $stmt = $db->prepare("INSERT INTO companies (name, phone, email) VALUES (:name, :phone, :email)");
            $stmt->bindValue(':name', $name, SQLITE3_TEXT);
            $stmt->bindValue(':phone', $phone, SQLITE3_TEXT);
            $stmt->bindValue(':email', $email, SQLITE3_TEXT);
            
            if ($stmt->execute()) {
                $success = 'Firma başarıyla eklendi!';
            } else {
                $error = 'Firma eklenirken hata oluştu!';
            }
        } elseif ($_POST['action'] === 'edit') {
            $id = (int)$_POST['id'];
            $name = sanitizeInput($_POST['name']);
            $phone = sanitizeInput($_POST['phone']);
            $email = sanitizeInput($_POST['email']);
            
            $stmt = $db->prepare("UPDATE companies SET name = :name, phone = :phone, email = :email WHERE id = :id");
            $stmt->bindValue(':id', $id, SQLITE3_INTEGER);
            $stmt->bindValue(':name', $name, SQLITE3_TEXT);
            $stmt->bindValue(':phone', $phone, SQLITE3_TEXT);
            $stmt->bindValue(':email', $email, SQLITE3_TEXT);
            
            if ($stmt->execute()) {
                $success = 'Firma başarıyla güncellendi!';
            } else {
                $error = 'Firma güncellenirken hata oluştu!';
            }
        } elseif ($_POST['action'] === 'delete') {
            $id = (int)$_POST['id'];
            
            $stmt = $db->prepare("DELETE FROM companies WHERE id = :id");
            $stmt->bindValue(':id', $id, SQLITE3_INTEGER);
            
            if ($stmt->execute()) {
                $success = 'Firma başarıyla silindi!';
            } else {
                $error = 'Firma silinirken hata oluştu!';
            }
        }
    }
}

// Get all companies
$companies = $db->query("SELECT * FROM companies ORDER BY name ASC");
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Firma Yönetimi - Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <div class="container">
        <div class="admin-header">
            <h2>Firma Yönetimi</h2>
            <a href="index.php" class="btn btn-secondary">← Admin Paneli</a>
        </div>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>
        
        <div class="admin-section">
            <h3>Yeni Firma Ekle</h3>
            <form method="POST" class="admin-form">
                <input type="hidden" name="action" value="add">
                <div class="form-row">
                    <div class="form-group">
                        <label for="name">Firma Adı</label>
                        <input type="text" name="name" id="name" required>
                    </div>
                    <div class="form-group">
                        <label for="phone">Telefon</label>
                        <input type="tel" name="phone" id="phone">
                    </div>
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" name="email" id="email">
                    </div>
                    <div class="form-group">
                        <button type="submit" class="btn btn-primary">Ekle</button>
                    </div>
                </div>
            </form>
        </div>
        
        <div class="admin-section">
            <h3>Mevcut Firmalar</h3>
            <div class="table-responsive">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Firma Adı</th>
                            <th>Telefon</th>
                            <th>Email</th>
                            <th>İşlemler</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($company = $companies->fetchArray(SQLITE3_ASSOC)): ?>
                        <tr>
                            <td><?= $company['id'] ?></td>
                            <td><?= htmlspecialchars($company['name']) ?></td>
                            <td><?= htmlspecialchars($company['phone']) ?></td>
                            <td><?= htmlspecialchars($company['email']) ?></td>
                            <td>
                                <button onclick="editCompany(<?= htmlspecialchars(json_encode($company)) ?>)" 
                                        class="btn btn-sm btn-secondary">Düzenle</button>
                                <form method="POST" style="display:inline;" 
                                      onsubmit="return confirm('Bu firmayı silmek istediğinizden emin misiniz?')">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= $company['id'] ?>">
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
    <div id="editModal" class="modal" style="display:none;">
        <div class="modal-content">
            <span class="close" onclick="closeModal()">&times;</span>
            <h3>Firma Düzenle</h3>
            <form method="POST" id="editForm">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="id" id="edit_id">
                <div class="form-group">
                    <label for="edit_name">Firma Adı</label>
                    <input type="text" name="name" id="edit_name" required>
                </div>
                <div class="form-group">
                    <label for="edit_phone">Telefon</label>
                    <input type="tel" name="phone" id="edit_phone">
                </div>
                <div class="form-group">
                    <label for="edit_email">Email</label>
                    <input type="email" name="email" id="edit_email">
                </div>
                <button type="submit" class="btn btn-primary">Güncelle</button>
            </form>
        </div>
    </div>
    
    <?php include '../includes/footer.php'; ?>
    
    <script>
        function editCompany(company) {
            document.getElementById('edit_id').value = company.id;
            document.getElementById('edit_name').value = company.name;
            document.getElementById('edit_phone').value = company.phone || '';
            document.getElementById('edit_email').value = company.email || '';
            document.getElementById('editModal').style.display = 'block';
        }
        
        function closeModal() {
            document.getElementById('editModal').style.display = 'none';
        }
    </script>
</body>
</html>