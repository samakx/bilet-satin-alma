<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/auth.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitizeInput($_POST['email']);
    $password = $_POST['password'];
    $confirmPassword = $_POST['confirm_password'];
    $name = sanitizeInput($_POST['name']);
    $phone = sanitizeInput($_POST['phone']);
    
    if (!validateEmail($email)) {
        $error = 'Geçerli bir email adresi girin!';
    } elseif ($password !== $confirmPassword) {
        $error = 'Şifreler eşleşmiyor!';
    } elseif (strlen($password) < 6) {
        $error = 'Şifre en az 6 karakter olmalıdır!';
    } else {
        if (registerUser($email, $password, $name, $phone)) {
            $success = 'Kayıt başarılı! Giriş yapabilirsiniz.';
        } else {
            $error = 'Bu email adresi zaten kullanılıyor!';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kayıt Ol - Bilet Platformu</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container auth-container">
        <div class="auth-box">
            <h2>Kayıt Ol</h2>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="form-group">
                    <label for="name">Ad Soyad</label>
                    <input type="text" name="name" id="name" required>
                </div>
                
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" name="email" id="email" required>
                </div>
                
                <div class="form-group">
                    <label for="phone">Telefon</label>
                    <input type="tel" name="phone" id="phone" placeholder="5551234567">
                </div>
                
                <div class="form-group">
                    <label for="password">Şifre</label>
                    <input type="password" name="password" id="password" required minlength="6">
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">Şifre Tekrar</label>
                    <input type="password" name="confirm_password" id="confirm_password" required>
                </div>
                
                <button type="submit" class="btn btn-primary btn-block">Kayıt Ol</button>
            </form>
            
            <p class="auth-footer">
                Zaten hesabınız var mı? <a href="login.php">Giriş Yapın</a>
            </p>
        </div>
    </div>
    
    <?php include 'includes/footer.php'; ?>
</body>
</html>