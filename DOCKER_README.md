# Docker Kurulum ve Çalıştırma Kılavuzu

Bu dokümantasyon, Bilet Satın Alma uygulamasını Docker container'ında çalıştırmanız için gerekli adımları anlatır.

## Gereksinimler

- Docker (version 20.10+)
- Docker Compose (version 1.29+)

## Kurulum

### 1. Docker Image Oluşturma

```bash
docker-compose build
```

### 2. Container'ı Başlatma

```bash
docker-compose up -d
```

Uygulama `http://localhost:8080` adresinde çalışacaktır.

### 3. Container'ı Durdurma

```bash
docker-compose down
```

## Detaylı Komutlar

### Image Oluştur
```bash
docker build -t bilet-satin-alma .
```

### Container'ı Çalıştır
```bash
docker run -d \
  -p 8080:80 \
  -v $(pwd):/var/www/html \
  -v $(pwd)/database:/var/www/html/database \
  --name bilet-satin-alma \
  bilet-satin-alma
```

### Container Logs'u Görüntüle
```bash
docker-compose logs -f web
```

### Container'a Bağlan
```bash
docker-compose exec web bash
```

### Database'e Erişim
```bash
docker-compose exec web sqlite3 /var/www/html/database/tickets.db
```

## Ortam Değişkenleri

`docker-compose.yml` dosyasında aşağıdaki ortam değişkenleri kullanılabilir:

- `PHP_MEMORY_LIMIT`: PHP hafıza limiti (varsayılan: 256M)
- `PHP_MAX_UPLOAD_SIZE`: Maksimum upload boyutu (varsayılan: 50M)
- `PHP_DISPLAY_ERRORS`: Hataları göster (varsayılan: 1)
- `PHP_ERROR_REPORTING`: Hata raporlama seviyesi (varsayılan: E_ALL)

## Sorun Giderme

### Permission Denied Hatası
```bash
docker-compose exec web chown -R www-data:www-data /var/www/html
```

### Database Dosyasına Yazılamıyor
```bash
docker-compose exec web chmod -R 755 /var/www/html/database
```

### Port 8080 Kullanımda
Farklı bir port kullanmak için `docker-compose.yml` dosyasında değiştirin:
```yaml
ports:
  - "8081:80"  # 8081 portunu kullan
```

## Production Deployment

Production ortamında kullanmak için aşağıdaki ayarlamaları yapın:

1. **Dockerfile'ı değiştir:**
```dockerfile
ENV PHP_DISPLAY_ERRORS=0
ENV PHP_ERROR_REPORTING=E_CRITICAL
```

2. **docker-compose.yml'de restart policy:**
```yaml
restart: always
```

3. **Volume mount'ları optimize et:**
```yaml
volumes:
  - ./database:/var/www/html/database:rw
```

## Volume Yönetimi

### Database Persistence
```bash
# Named volume oluştur
docker volume create bilet-db

# docker-compose.yml'de kullan
volumes:
  web:
    driver: local
```

### Backup Alma
```bash
docker run --rm \
  -v bilet-db:/data \
  -v $(pwd)/backups:/backup \
  busybox \
  tar czf /backup/database-$(date +%Y%m%d).tar.gz -C /data .
```

## Health Check

Container'ın sağlığını kontrol etmek:

```bash
docker-compose ps
docker stats bilet-satin-alma
```

## Network

Uygulama `bilet-network` adında bir bridge network üzerinde çalışır. Başka servisler eklemek için:

```yaml
services:
  database:
    image: sqlite:latest
    networks:
      - bilet-network

networks:
  bilet-network:
    driver: bridge
```

## Daha Fazla Bilgi

- [Docker Dokümantasyonu](https://docs.docker.com/)
- [Docker Compose Dokümantasyonu](https://docs.docker.com/compose/)
- [PHP Docker Resmi Image](https://hub.docker.com/_/php)
