<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/auth.php';

$error = '';
$redirect = isset($_GET['redirect']) ? $_GET['redirect'] : url('index.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitizeInput($_POST['email']);
    $password = $_POST['password'];
    
    if (loginUser($email, $password)) {
        header('Location: ' . $redirect);
        exit;
    } else {
        $error = 'Email veya şifre hatalı!';
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Giriş Yap - Bilet Platformu</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container auth-container">
        <div class="auth-box">
            <h2>Giriş Yap</h2>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" name="email" id="email" required>
                </div>
                
                <div class="form-group">
                    <label for="password">Şifre</label>
                    <input type="password" name="password" id="password" required>
                </div>
                
                <button type="submit" class="btn btn-primary btn-block">Giriş Yap</button>
            </form>
                        <p class="auth-footer">
                Demo Admin: admin@platform.com | admin123
				Demo Firma Admin: metro@turizm.com | firma123
				Demo Kullanıcı: ahmet@email.com | user123
            </p>
            <p class="auth-footer">
                Hesabınız yok mu? <a href="register.php">Kayıt Olun</a>
            </p>
        </div>
    </div>
    
    <?php include 'includes/footer.php'; ?>
</body>
</html>