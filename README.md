# Anonim İtiraf / Mesaj Sitesi

Minimal ve güvenli bir anonim mesaj uygulaması.

## Özellikler
- Tam anonim mesaj gönderimi (kayıt yok).
- XSS, SQL Injection, CSRF ve spam önleme.
- 60 saniyede 1 mesaj sınırı.
- 1 kullanıcı 1 beğeni.
- Admin paneli ile yönetim ve loglama.

## Dosya Yapısı
```
assets/
  app.js
  style.css
config/
  config.php
lib/
  csrf.php
  db.php
  security.php
public/
  index.php
  admin/
    index.php
  api/
    like.php
    messages.php
    post.php
database/
  schema.sql
README.md
```

## Kurulum Adımları
1. Veritabanını oluşturun ve `database/schema.sql` dosyasını içe aktarın.
2. `config/config.php` içindeki veritabanı bilgilerini ve `ip_salt` değerini güncelleyin.
3. Admin kullanıcı ekleyin:
   ```sql
   INSERT INTO admin (username, password_hash)
   VALUES ('admin', '$2y$10$ornekhashburaya');
   ```
   Hash üretmek için:
   ```bash
   php -r "echo password_hash('SIFRENIZ', PASSWORD_DEFAULT);"
   ```
4. Web sunucusunu `public/` dizinine yönlendirin.
5. Yönetim paneli: `/admin`.
