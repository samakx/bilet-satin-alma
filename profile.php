<?php
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

requireLogin();

$user = getCurrentUser();
$error = '';
$success = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitizeInput($_POST['name']);
    $email = sanitizeInput($_POST['email']);
    $phone = sanitizeInput($_POST['phone']);

    if (!validateEmail($email)) {
        $error = 'Geçerli bir email adresi girin!';
    } else {
        // Check if email is already used by another user
        $checkStmt = $db->prepare("SELECT id FROM users WHERE email = :email AND id != :id");
        $checkStmt->bindValue(':email', $email, SQLITE3_TEXT);
        $checkStmt->bindValue(':id', $user['id'], SQLITE3_INTEGER);
        $result = $checkStmt->execute();

        if ($result->fetchArray()) {
            $error = 'Bu email adresi başka bir kullanıcı tarafından kullanılıyor!';
        } else {
            // Update user info
            if (!empty($_POST['password'])) {
                $password = $_POST['password'];
                $confirmPassword = $_POST['confirm_password'];

                if (strlen($password) < 6) {
                    $error = 'Şifre en az 6 karakter olmalıdır!';
                } elseif ($password !== $confirmPassword) {
                    $error = 'Şifreler eşleşmiyor!';
                } else {
                    $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
                    $stmt = $db->prepare("UPDATE users SET email = :email, password = :password, name = :name,
                                         phone = :phone WHERE id = :id");
                    $stmt->bindValue(':password', $hashedPassword, SQLITE3_TEXT);
                    $stmt->bindValue(':email', $email, SQLITE3_TEXT);
                    $stmt->bindValue(':name', $name, SQLITE3_TEXT);
                    $stmt->bindValue(':phone', $phone, SQLITE3_TEXT);
                    $stmt->bindValue(':id', $user['id'], SQLITE3_INTEGER);

                    if ($stmt->execute()) {
                        $success = 'Profil bilgileriniz başarıyla güncellendi!';
                        // Update session
                        $_SESSION['user'] = getUserById($user['id']);
                        $user = $_SESSION['user'];
                    } else {
                        $error = 'Güncelleme sırasında bir hata oluştu!';
                    }
                }
            } else {
                // Update without password
                $stmt = $db->prepare("UPDATE users SET email = :email, name = :name, phone = :phone WHERE id = :id");
                $stmt->bindValue(':email', $email, SQLITE3_TEXT);
                $stmt->bindValue(':name', $name, SQLITE3_TEXT);
                $stmt->bindValue(':phone', $phone, SQLITE3_TEXT);
                $stmt->bindValue(':id', $user['id'], SQLITE3_INTEGER);

                if ($stmt->execute()) {
                    $success = 'Profil bilgileriniz başarıyla güncellendi!';
                    // Update session
                    $_SESSION['user'] = getUserById($user['id']);
                    $user = $_SESSION['user'];
                } else {
                    $error = 'Güncelleme sırasında bir hata oluştu!';
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil Ayarları</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="container">
        <div class="page-header">
            <h2>Profil Ayarları</h2>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <div class="profile-container">
            <div class="profile-info">
                <h3>Hesap Bilgileri</h3>
                <div class="info-grid">
                    <div class="info-item">
                        <span class="label">Hesap Türü:</span>
                        <span class="value">
                            <?php
                            if ($user['role'] === 'admin') {
                                echo 'Sistem Yöneticisi';
                            } elseif ($user['role'] === 'company_admin') {
                                echo 'Firma Yöneticisi';
                            } else {
                                echo 'Kullanıcı';
                            }
                            ?>
                        </span>
                    </div>
                    <div class="info-item">
                        <span class="label">Bakiye:</span>
                        <span class="value"><?= formatPrice($user['balance']) ?></span>
                    </div>
                    <div class="info-item">
                        <span class="label">Kayıt Tarihi:</span>
                        <span class="value"><?= formatDate($user['created_at']) ?></span>
                    </div>
                </div>
            </div>

            <div class="profile-edit">
                <h3>Profil Bilgilerini Düzenle</h3>
                <form method="POST" class="profile-form">
                    <div class="form-group">
                        <label for="name">Ad Soyad</label>
                        <input type="text" name="name" id="name" value="<?= htmlspecialchars($user['name']) ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" name="email" id="email" value="<?= htmlspecialchars($user['email']) ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="phone">Telefon</label>
                        <input type="tel" name="phone" id="phone" value="<?= htmlspecialchars($user['phone']) ?>">
                    </div>

                    <hr style="margin: 20px 0; border: 1px solid #e0e0e0;">

                    <h4>Şifre Değiştir (Opsiyonel)</h4>
                    <p style="color: #666; font-size: 14px; margin-bottom: 15px;">Şifrenizi değiştirmek istemiyorsanız bu alanları boş bırakın.</p>

                    <div class="form-group">
                        <label for="password">Yeni Şifre</label>
                        <input type="password" name="password" id="password" minlength="6"
                               placeholder="En az 6 karakter">
                    </div>
                    <div class="form-group">
                        <label for="confirm_password">Yeni Şifre (Tekrar)</label>
                        <input type="password" name="confirm_password" id="confirm_password" minlength="6">
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">Güncelle</button>
                        <a href="index.php" class="btn btn-secondary">İptal</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>
</body>
</html>
