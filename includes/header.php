<header>
    <div class="container">
        <h1>🎫 Bu Bilet</h1>
        <nav>
            <a href="<?= $_SERVER['REQUEST_SCHEME'] ?>://<?= $_SERVER['HTTP_HOST'] ?>/bilet-satin-alma/index.php">Ana Sayfa</a>
            
            <?php if (isLoggedIn()): ?>
                <?php if (hasRole('admin')): ?>
                    <a href="<?= $_SERVER['REQUEST_SCHEME'] ?>://<?= $_SERVER['HTTP_HOST'] ?>/bilet-satin-alma/admin/index.php">Admin Paneli</a>
                <?php elseif (hasRole('company_admin')): ?>
                    <a href="<?= $_SERVER['REQUEST_SCHEME'] ?>://<?= $_SERVER['HTTP_HOST'] ?>/bilet-satin-alma/firma-admin/index.php">Firma Paneli</a>
                <?php else: ?>
                    <a href="<?= $_SERVER['REQUEST_SCHEME'] ?>://<?= $_SERVER['HTTP_HOST'] ?>/bilet-satin-alma/my-tickets.php">Biletlerim</a>
                <?php endif; ?>

                <a href="<?= $_SERVER['REQUEST_SCHEME'] ?>://<?= $_SERVER['HTTP_HOST'] ?>/bilet-satin-alma/profile.php">Profil</a>
                <a href="<?= $_SERVER['REQUEST_SCHEME'] ?>://<?= $_SERVER['HTTP_HOST'] ?>/bilet-satin-alma/logout.php">Çıkış (<?= htmlspecialchars($_SESSION['name']) ?>)</a>
            <?php else: ?>
                <a href="<?= $_SERVER['REQUEST_SCHEME'] ?>://<?= $_SERVER['HTTP_HOST'] ?>/bilet-satin-alma/login.php">Giriş Yap</a>
                <a href="<?= $_SERVER['REQUEST_SCHEME'] ?>://<?= $_SERVER['HTTP_HOST'] ?>/bilet-satin-alma/register.php">Kayıt Ol</a>
            <?php endif; ?>
        </nav>
    </div>
</header>