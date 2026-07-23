# Выкладка Product Web на сервер

Проект: `C:\product_grok` → Linux-сервер с **Docker Engine** (не обязательно Docker Desktop).

---

## 1. Что передавать

| Передавать | Не передавать |
|------------|----------------|
| Код (`app`, `resources`, `routes`, `docker`, …) | `.env` (секреты) |
| `composer.json` / `package.json` + lock-файлы | `vendor/`, `node_modules/` |
| `docker-compose.yml` | Docker volumes с ПК |
| `public/build` (если уже собрали) **или** собрать на сервере | локальные логи |
| `storage/dumps/*.sql` (дамп ERP) | пароли в открытом виде в чате |
| `.env.example` | папка Delphi `C:\Product` (не нужна на сервере) |

Фото (`files_product`) — **отдельно** (огромный каталог). На сервере смонтировать путь или FTP.

---

## 2. Упаковка на Windows (ПК разработки)

```powershell
cd C:\product_grok

# 1) Собрать фронт (если ещё не собран)
node .\node_modules\vite\bin\vite.js build
# или в Docker: docker compose --profile frontend run --rm node sh -c "npm ci && npm run build"

# 2) Упаковать
powershell -ExecutionPolicy Bypass -File .\scripts\package-for-server.ps1
```

Готовый архив:

`C:\product_grok_deploy\product_web_YYYYMMDD_HHMM.zip`

Без SQL-дампа (меньше размер):

```powershell
powershell -ExecutionPolicy Bypass -File .\scripts\package-for-server.ps1 -IncludeDump:$false
```

Размер с дампом `product_*.sql` — порядка **400+ МБ**.

---

## 3. Передача на сервер

### Вариант A — SCP / SFTP

```bash
# с Windows (OpenSSH) или с WSL:
scp C:\product_grok_deploy\product_web_XXXX.zip user@SERVER_IP:/opt/
```

WinSCP / FileZilla: загрузить zip в `/opt/`.

### Вариант B — git (если есть репозиторий)

```bash
git clone <url> /opt/product_grok
# дамп SQL и .env — отдельно, не в git
```

### Вариант C — общий диск / USB

Скопировать zip, на сервере распаковать.

---

## 4. На сервере (Linux)

Требования: Docker + Docker Compose plugin, 4+ ГБ RAM, открытый порт (80/443 или 8080).

```bash
cd /opt
sudo mkdir -p product_grok
sudo unzip -o product_web_XXXX.zip -d product_grok
cd product_grok

# Права на storage
sudo chown -R $USER:$USER .
mkdir -p storage/framework/{cache,sessions,views} storage/logs storage/app/private bootstrap/cache
chmod -R ug+rwx storage bootstrap/cache

# Конфиг
cp .env.example .env
nano .env   # см. раздел 5
```

Запуск:

```bash
docker compose up -d --build
# первый старт: composer install, migrate, seed (entrypoint)
docker compose ps
curl -I http://127.0.0.1:8080/login
```

Импорт ERP-дампа (если MySQL ERP внутри compose-контейнера `product`):

```bash
# пример — как у вас в scripts/db-setup.ps1, на Linux:
docker exec -i product_mysql mysql -uroot -proot product < storage/dumps/product_20260722.sql
# или ваш актуальный файл дампа
```

Если ERP-база **уже на другом сервере** — дамп в compose **не** грузить, в `.env` указать внешний `PRODUCT_DB_*`.

---

## 5. Обязательные переменные `.env` на сервере

```env
APP_NAME=Product
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.example
APP_KEY=   # сгенерируется entrypoint'ом или: docker compose exec app php artisan key:generate

# Внутренняя БД Laravel (контейнер mysql)
DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=product_app
DB_USERNAME=product
DB_PASSWORD=СЛОЖНЫЙ_ПАРОЛЬ

# ERP Product (истина данных)
# Вариант 1: та же MySQL-контейнер, БД product
PRODUCT_DB_HOST=mysql
PRODUCT_DB_PORT=3306
PRODUCT_DB_DATABASE=product
PRODUCT_DB_USERNAME=product
PRODUCT_DB_PASSWORD=СЛОЖНЫЙ_ПАРОЛЬ
PRODUCT_DB_ALLOW_WRITE=false   # true только когда готовы к записи в прод

# Вариант 2: внешний MySQL ERP
# PRODUCT_DB_HOST=10.0.0.5
# PRODUCT_DB_PORT=3306
# ...

APP_PORT=8080
# или 80 за reverse-proxy

REDIS_HOST=redis
SESSION_DRIVER=database
QUEUE_CONNECTION=redis

# Фото (если смонтированы на хосте)
PRODUCT_IMG_PATH=/mnt/files_product
# PRODUCT_IMG_HOST=/data/files_product

# FTP (если есть)
FTP_HOST=
FTP_USERNAME=
FTP_PASSWORD=
FTP_ROOT=/files_product
```

Пароли MySQL в compose по умолчанию `product`/`root` — **смените** для прода (через env compose или override).

---

## 6. Фото и файлы

Delphi: `Dir_Catalog_Img = ...\files_product\`

На сервере:

```bash
# пример
sudo mkdir -p /data/files_product
# скопировать jpg с файлового сервера / бэкапа
```

В `.env` / compose:

```env
PRODUCT_IMG_HOST=/data/files_product
PRODUCT_IMG_PATH=/mnt/files_product
```

Тома в `docker-compose.yml` уже умеют монтировать `${PRODUCT_IMG_HOST}` → `/mnt/files_product`.

---

## 7. Nginx / HTTPS снаружи (рекомендуется)

Docker слушает `APP_PORT` (8080). Снаружи:

- Caddy / nginx на хосте → proxy_pass `http://127.0.0.1:8080`
- сертификат Let's Encrypt

Не открывайте MySQL (3307) и Redis (6379) в интернет.

---

## 8. Чеклист после выкладки

- [ ] `docker compose ps` — все Up, mysql healthy  
- [ ] `https://домен/login` открывается  
- [ ] Логин admin (после seed / своего пользователя)  
- [ ] Дашборд: Product DB OK  
- [ ] `/store`, `/orders` — данные  
- [ ] `/settings` — для admin  
- [ ] `PRODUCT_DB_ALLOW_WRITE=false` пока не проверите запись  
- [ ] Бэкап: volume `mysql_data` + дампы SQL  

---

## 9. Обновление версии

```bash
# на сервере
cd /opt/product_grok
# распаковать новый zip поверх или git pull
docker compose build app
docker compose up -d
docker compose exec app php artisan migrate --force
# при необходимости: npm run build (или profile frontend)
```

---

## 10. Схема «что куда»

```
ПК (Windows)                    Сервер (Linux)
─────────────                   ──────────────
C:\product_grok  ──zip/scp──►  /opt/product_grok
storage/dumps/*.sql  ───────►  import → MySQL product
E:\files_product   ─────────►  /data/files_product (фото)
.env (локальный)   ── ✗ ──►   .env (новый, production)
vendor/node_modules ─ ✗ ──►   ставятся в Docker volumes
C:\Product (Delphi) ─ ✗ ──►   не нужен
```

---

## Краткая шпаргалка

```powershell
# ПК
cd C:\product_grok
powershell -File .\scripts\package-for-server.ps1
scp C:\product_grok_deploy\product_web_*.zip user@SERVER:/opt/
```

```bash
# Сервер
cd /opt && unzip product_web_*.zip -d product_grok && cd product_grok
cp .env.example .env && nano .env
docker compose up -d --build
docker exec -i product_mysql mysql -uroot -proot product < storage/dumps/product_XXXX.sql
```

Открыть: `http://SERVER:8080/login`
