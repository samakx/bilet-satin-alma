# 🎫 BU BİLET - Otobüs Bilet Satın Alma Sistemi

Otobüs sektöründe modern ve güvenli bilet satış çözümü sunan, entegre yönetim ve operasyon sistemi.

---

## 📌 Proje Tanımı

**BU BİLET**, yolcular ve otobüs işletmecileri için tasarlanan kapsamlı bir bilet satın alma ve yönetim platformudur. Platform, kullanıcı dostu arayüzü, gerçek zamanlı sefer takibi, akıllı koltuk yönetimi ve işletme operasyonlarını basitleştiren araçlarla otobüs sektöründe hizmet kalitesi artışı sağlar.

---

## ✨ Özellikler


### 🏢 Firma Admin Özellikleri

- **Sefer Yönetimi**: Yeni sefer ekleme, mevcut seferleri düzenleme ve silme
- **Otobüs Yapılandırması**: Farklı otobüs tiplerini yönetme (2+1, 2+2 vb.)
- **Sefer İstatistikleri**: Tüm seferler ve detaylarını liste halinde görüntüleme
- **Satış Takibi**: Her sefer için satılan biletler ve geliri izleme
- **Aktif Bilet Kontrolü**: Satılan biletleri ve yolcu bilgilerini görüntüleme
- **Gelir Analizi**: Şirket tarafından elde edilen toplam geliri detaylı olarak takip etme
- **İşletmeye Özel Kuponlar**: Şirketinin seferleri için promosyon kodları oluşturma
- **Dashboard**: Gerçek zamanlı sefer, bilet ve gelir istatistiklerini görüntüleme

### 👨‍💼 Admin Özellikleri

- **Firma Yönetimi**: Yeni otobüs şirketleri ekleme, düzenleme ve yönetme
- **Firma Admin Yönetimi**: Şirket yetkilileri için kullanıcı hesapları oluşturma ve yönetme
- **Kullanıcı Kontrolü**: Platform kullanıcılarını görüntüleme ve yönetme
- **Platform İstatistikleri**: Toplam firma, sefer, bilet ve aktif kullanıcı sayılarını izleme
- **Gelir Takibi**: Platform üzerinden elde edilen toplam geliri görüntüleme
- **Global Kupon Yönetimi**: Tüm platform için geçerli promosyon kodları oluşturma ve düzenleme
- **İstatistik Detayları**: İstatistik kartlarına tıklayarak detaylı listeleri görüntüleme
- **Sistem Kontrolü**: Genel sistem sağlığını ve performansını izleme

### 👤 Kullanıcı Özellikleri

- **Hesap Yönetimi**: Kayıt olma, giriş yapma, şifre sıfırlama ve profil güncelleme
- **Sefer Arama**: Kalkış-varış şehri ve tarih seçimi ile gelişmiş sefer arama
- **Koltuk Seçimi**: İnteraktif koltuk haritası ile gerçek zamanlı koltuk seçimi
- **Kupon Uygulaması**: İndirim kodlarını satın alma sırasında kullanma
- **Bilet Satın Alma**: Güvenli ödeme işlemi ve anında bilet oluşturma
- **PDF Bilet İndirme**: Satın alınan biletleri PDF formatında indirme ve yazdırma
- **Bilet Geçmişi**: Tüm geçmiş bilet alımlarını tarih sırasında görüntüleme
- **Bakiye Yönetimi**: Hesap bakiyesi görüntüleme ve işlemleri takip etme

---

## 🛠 Teknolojiler

| Katman | Teknoloji |
|--------|-----------|
| **Backend** | PHP 8.2 |
| **Veritabanı** | SQLite 3 |
| **Web Sunucusu** | Apache 2.4 |
| **Frontend** | HTML5, CSS3, JavaScript (ES6+) |
| **Containerization** | Docker & Docker Compose |
| **PDF Oluşturma** | DomPDF |
| **Güvenlik** | CSRF Tokens, Prepared Statements, bcrypt Hashing |
| **Kimlik Doğrulama** | Session-based Authentication |

---

## 📊 Veritabanı Şeması

```
users (Yolcu ve Personel)
├── id, email, password, name, phone
├── role (user / company_admin / admin)
├── balance (Hesap Bakiyesi)
└── company_id (İşletmeye Ait Personel)

companies (Otobüs Şirketleri)
├── id, name, logo, phone, email
└── created_at

trips (Seferler)
├── id, company_id, departure_city, arrival_city
├── departure_date, departure_time, arrival_time
├── price, total_seats, available_seats
└── bus_plate

tickets (Satın Alınan Biletler)
├── id, user_id, trip_id, seat_number, price
├── status (active/cancelled)
├── coupon_code, discount_amount
└── booking_date

coupons (İndirim Kodları)
├── id, code, discount_percentage
├── company_id (NULL = Global)
├── usage_limit, used_count
└── expiry_date
```

---

## 🚀 Docker ile Kurulum

### Ön Gereksinimler
- Docker 20.10+
- Docker Compose 1.29+

### Adım Adım Kurulum

**1. Depoyu klonlayın:**
```bash
git clone https://github.com/yourusername/bilet.git
cd bilet
```

**2. Container'ı başlatın:**
```bash
docker-compose up -d
```

**3. Uygulamaya erişin:**
```
http://localhost:8080
```

**4. Container'ı durdurmak için:**
```bash
docker-compose down
```


## 💻 Manuel Kurulum (XAMPP)

**1. Dosyaları htdocs dizinine kopyalayın:**
```bash
cp -r bilet/ /path/to/xampp/htdocs/
```

**2. Veritabanı dizini oluşturun:**
```bash
mkdir -p /path/to/xampp/htdocs/bilet/database
chmod 755 /path/to/xampp/htdocs/bilet/database
```

**3. Uygulamaya erişin:**
```
http://localhost/bilet
```

---

## 👥 Demo Hesapları

### 🔐 Admin Hesabı
```
E-posta: admin@platform.com
Şifre: admin123
Rol: Platform Yöneticisi
```

### 🏢 Firma Admin Hesabı
```
E-posta: metro@turizm.com
Şifre: firma123
Rol: Firma Yetkililisi
```

### 👤 Kullanıcı Hesabı
```
E-posta: ahmet@email.com
Şifre: user123
Rol: Standart Yolcu
```

---

## 📋 Temel İş Akışları

### 1️⃣ Bilet Satın Alma

```
1. Platformda kaydolun veya giriş yapın
2. Ana sayfadaki sefer arama formunu doldur (kalkış, varış, tarih)
3. Uygun bir seferi seçin
4. İnteraktif koltuk haritasından koltuk seçin
5. İsteğe bağlı olarak kupon kodu uygulayın
6. Bilet satın almayı tamamlayın
7. PDF bilet indir ve yazdırın
```

### 2️⃣ İşletme Sefer Yönetimi

```
1. Firma admin hesabıyla giriş yapın
2. Firma Admin Paneli → Seferler bölümüne gidin
3. Yeni sefer ekle butonuna tıklayın
4. Sefer detaylarını girin (rota, saat, fiyat, kapasite)
5. Seferi kaydedin
6. Dashboard'da satış takibi yapın
7. Gerekirse kupon kodları oluşturun
```

### 3️⃣ Platform Yönetimi

```
1. Admin hesabıyla giriş yapın
2. Admin Paneli açılacaktır
3. İstatistik kartlarına tıklayarak detaylı listeleri görüntüleyin:
   - 🏢 Firma: Tüm firmalar listesi
   - 🚌 Sefer: Tüm seferler
   - 🎫 Aktif Bilet: Satılmış biletler
   - 👥 Kullanıcı: Platform kullanıcıları
   - 💰 Gelir: Detaylı gelir analizi
4. Firmalar sekmesinde yeni şirket ekleyin
5. Global kupon kodları oluşturun
```

---

## 🔑 Önemli Özellikler


### 📊 Gerçek Zamanlı İstatistikler
- **Admin Dashboard**: Platform üzerindeki tüm aktiviteleri izle
- **Firma Dashboard**: Şirketine ait sefer ve satış verilerini takip et
- **İnteraktif Kartlar**: Her istatistik kartına tıklayarak detayları görüntüle

### 🎟 Kupon & İndirim Sistemi
- **Global Kuponlar**: Tüm seferler için geçerli promosyon kodları
- **İşletmeye Özel Kuponlar**: Belirli şirkete ait seferler için özel fiyatlandırma
- **Zaman Sınırlı**: Her kupon için sona erme tarihi belirtilir
- **Kullanım Limiti**: Maksimum kullanım sayısı kontrol edilir

### 🔐 Güvenlik Özellikleri
✅ **SQL Injection Koruması**: Prepared statements kullanımı
✅ **CSRF Koruması**: Token üretimi ve doğrulama
✅ **Şifre Güvenliği**: bcrypt hashing ile depolama
✅ **Oturum Yönetimi**: Güvenli session kullanımı
✅ **Veri Doğrulama**: Sunucu tarafı input kontrolü
✅ **Access Control**: Role-based yetkilendirme sistemi

---

## 📁 Dosya Yapısı

```
bilet/
├── admin/                      # Admin yönetim paneli
│   ├── index.php              # Dashboard & istatistikler
│   ├── companies.php          # Firma yönetimi
│   ├── company-admins.php     # Firma admin yönetimi
│   └── coupons.php            # Global kupon yönetimi
│
├── firma-admin/               # Firma yönetim paneli
│   ├── index.php              # Firma dashboard
│   ├── trips.php              # Sefer yönetimi
│   └── coupons.php            # Firma kuponları
│
├── includes/                  # Temel dosyalar
│   ├── db.php                 # Veritabanı bağlantısı
│   ├── auth.php               # Kimlik doğrulama
│   ├── functions.php          # Yardımcı fonksiyonlar
│   ├── header.php             # Navigasyon şablonu
│   ├── footer.php             # Alt sayfa
│   ├── config.php             # Konfigürasyon
│   └── init-db.php            # Veritabanı şeması
│
├── assets/
│   └── css/style.css          # Tüm stiller (CSS)
│
├── database/
│   └── tickets.db             # SQLite veritabanı dosyası
│
├── Root Pages (Ana Sayfalar)
│   ├── index.php              # Ana sayfa
│   ├── login.php              # Giriş sayfası
│   ├── register.php           # Kayıt sayfası
│   ├── search.php             # Sefer arama sonuçları
│   ├── trip-details.php       # Sefer detayları
│   ├── buy-ticket.php         # Bilet satın alma
│   ├── my-tickets.php         # Biletlerim
│   ├── profile.php            # Profil sayfası
│   ├── cancel-ticket.php      # Bilet iptali
│   ├── download-pdf.php       # PDF indirme
│   └── check-coupon.php       # Kupon doğrulama
│
├── Dockerfile                 # Docker image yapısı
├── docker-compose.yml         # Docker Compose konfigürasyonu
├── .dockerignore              # Docker'ı yoksaydığı dosyalar
├── DOCKER_README.md           # Docker dokümantasyonu
└── README.md                  # Bu dosya
```

---

## 🐛 Sorun Giderme

### Port 8080 Zaten Kullanımda
Farklı bir port kullanmak için `docker-compose.yml` dosyasını düzenleyin:
```yaml
ports:
  - "8081:80"  # Portu değiştir
```

### Veritabanı İzin Hatası
```bash
docker-compose exec web chmod -R 755 /var/www/html/database
docker-compose exec web chown -R www-data:www-data /var/www/html
```

### Container Loglarını Görüntüle
```bash
docker-compose logs -f web
```

### Veritabanı Sorunları
```bash
# Container'a giriş yap
docker-compose exec web bash

# SQLite veritabanını kontrol et
sqlite3 /var/www/html/database/tickets.db

# Veritabanını sil ve yeniden oluştur
rm /var/www/html/database/tickets.db
# Uygulamayı yeniden başlat
```

---

## 📖 API Endpoints

### Kullanıcı İşlemleri
```
GET  /index.php                # Ana sayfa
POST /login.php                # Giriş yap
POST /register.php             # Kayıt ol
GET  /logout.php               # Çıkış yap
GET  /profile.php              # Profil sayfası
```

### Sefer & Bilet İşlemleri
```
GET  /search.php               # Sefer ara
GET  /trip-details.php         # Sefer detayları
POST /buy-ticket.php           # Bilet satın al
POST /check-coupon.php         # Kupon doğrula
GET  /my-tickets.php           # Biletlerim
POST /cancel-ticket.php        # Bilet iptal et
POST /download-pdf.php         # PDF indir
```

### Yönetim Panelleri
```
GET  /admin/index.php          # Admin dashboard
GET  /admin/companies.php      # Firma yönetimi
GET  /admin/company-admins.php # Admin yönetimi
GET  /admin/coupons.php        # Global kupon yönetimi

GET  /firma-admin/index.php    # Firma dashboard
GET  /firma-admin/trips.php    # Sefer yönetimi
GET  /firma-admin/coupons.php  # Firma kuponları
```

---

## 🔄 Sistem Akışı

```
Ziyaretçi
   ↓
[Kayıt / Giriş]
   ↓
Kullanıcı Paneli
   ├→ Sefer Ara → Sefer Detayları → Koltuk Seç → Kupon Uygula → Bilet Satın Al
   ├→ Biletlerim
   ├→ Profil
   └→ Çıkış

Firma Yöneticisi
   ↓
[Giriş]
   ↓
Firma Paneli
   ├→ Dashboard (İstatistikler)
   ├→ Sefer Yönetimi (Ekle/Düzenle/Sil)
   ├→ Satış Takibi
   └→ Kupon Yönetimi

Platform Admin
   ↓
[Giriş]
   ↓
Admin Paneli
   ├→ Dashboard (Tüm İstatistikler)
   ├→ Firma Yönetimi
   ├→ Firma Admin Yönetimi
   └→ Global Kupon Yönetimi
```

---

## 📝 Lisans

Bu proje SiberVatan Programı kapsamında eğitim amaçlı geliştirilmiştir.

---

## 📞 İletişim & Destek

Sorular ve desteklemek için GitHub issues sekmesini kullanabilirsiniz.

**Proje Bilgileri:**
- **Son Güncelleme**: 2025-10-24
- **Versiyon**: 1.0.0
- **PHP Versiyon**: 8.2+
- **SQLite Versiyon**: 3.x+
- **Docker Compose**: 1.29+
