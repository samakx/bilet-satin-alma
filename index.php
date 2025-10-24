<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

$cities = getAllCities($db);
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bu Bilet</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="hero">
        <div class="container">
            <h1>Otobüs Bileti Ara</h1>
            <p>En uygun fiyatlı otobüs biletlerini bulun</p>
            
            <form action="search.php" method="GET" class="search-form">
                <div class="form-row">
                    <div class="form-group">
                        <label for="from">Nereden</label>
                        <select name="from" id="from" required>
                            <option value="">Şehir Seçin</option>
                            <?php foreach ($cities as $city): ?>
                                <option value="<?= htmlspecialchars($city) ?>"><?= htmlspecialchars($city) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="to">Nereye</label>
                        <select name="to" id="to" required>
                            <option value="">Şehir Seçin</option>
                            <?php foreach ($cities as $city): ?>
                                <option value="<?= htmlspecialchars($city) ?>"><?= htmlspecialchars($city) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="date">Tarih</label>
                        <input type="date" name="date" id="date" 
                               min="<?= date('Y-m-d') ?>" 
                               value="<?= date('Y-m-d') ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <button type="submit" class="btn btn-primary">Sefer Ara</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    
    <div class="container features">
        <h2>Neden Bizi Tercih Etmelisiniz?</h2>
        <div class="feature-grid">
            <div class="feature-card">
                <div class="feature-icon">🎫</div>
                <h3>Kolay Bilet Alımı</h3>
                <p>Hızlı ve güvenli bilet satın alma işlemi</p>
            </div>
            
            <div class="feature-card">
                <div class="feature-icon">💰</div>
                <h3>En Uygun Fiyat</h3>
                <p>Tüm firmalardan en iyi fiyatları karşılaştırın</p>
            </div>
            
            <div class="feature-card">
                <div class="feature-icon">🎟️</div>
                <h3>İndirim Kuponları</h3>
                <p>Kupon kodları ile avantajlı bilet fiyatları</p>
            </div>
            
            <div class="feature-card">
                <div class="feature-icon">📱</div>
                <h3>Mobil Bilet</h3>
                <p>Biletinizi PDF olarak indirin</p>
            </div>
        </div>
    </div>
    
    <?php include 'includes/footer.php'; ?>
</body>
</html>