# Product Web (product_grok)

Web-клиент ERP **Product** — замена Delphi 7 + EhLib.

Стек: **Laravel 11 · Vue 3 · Inertia · Bootstrap 5 · Docker · MySQL 8 · Redis · FTP**

## Архитектура

| Компонент | Назначение |
|-----------|------------|
| `mysql` (internal) | Пользователи web, pravo-кэш, prefs UI, sessions, jobs |
| `product` connection | **Внешняя** MySQL — source of truth (SELECT + CALL SP) |
| FTP disk `product_ftp` | Структурированные файлы (`Img`, `Files`, `Zakaz`, `Finance`, `Routing`) |
| Delphi | Legacy UI; web уходит strangler-ом по модулям |

**Правило:** бизнес-данные не дублируются во внутренней БД. Запись во внешнюю — только через SP и только если `PRODUCT_DB_ALLOW_WRITE=true`.

```
Browser (Vue + Inertia)
    → Laravel
        → Domain/*          (операции)
        → ProductDb/*       (external MySQL)
        → Files/*           (FTP catalogs)
        → Models (internal) (users, pravo, prefs)
```

## Быстрый старт

1. Запустите **Docker Desktop**.
2. В каталоге проекта:

```powershell
cd C:\product_grok
copy .env.example .env
docker compose up -d --build
```

3. Откройте http://localhost:8080  

Учётки (локальные, после `scripts/db-setup.ps1`):

| Логин | Пароль | Роль |
|-------|--------|------|
| `admin` | `admin` | администратор (web + product.users) |
| `test`  | `test`  | оператор (ограниченные pravo) |
| `demo`  | `demo`  | то же, что test (web-only alias) |

Локальные БД в одном MySQL-контейнере:

| БД | Назначение |
|----|------------|
| `product_app` | Laravel (сессии, web-users) |
| `product` | ERP dump + SP (склад, номенклатура, заказы) |

Импорт дампа: `powershell -File .\scripts\db-setup.ps1 -Force`  
Дамп: `C:\product_grok\storage\dumps\product_YYYYMMDD.sql` (берётся самый новый)  
Текущий прод: `product_20260722.sql`  
(`C:\Product` — только эталон Delphi.)

Первый запуск `app` контейнера сам сделает `composer install`, `key:generate`, `migrate`, `db:seed`.  
Vite dev-сервер: контейнер `node` на порту **5173**.

### Подключение внешней БД Product

В `.env`:

```env
PRODUCT_DB_HOST=host.docker.internal   # или IP сервера MySQL
PRODUCT_DB_PORT=3306
PRODUCT_DB_DATABASE=product
PRODUCT_DB_USERNAME=...
PRODUCT_DB_PASSWORD=...
PRODUCT_DB_ALLOW_WRITE=false
```

На дашборде — карточка `product_db`.  
Списки товаров/остатков заработают, когда таблицы/SP совпадут с `GoodsRepository` / `StoreRepository` (имена можно поправить под вашу схему).

### FTP

```env
FTP_HOST=...
FTP_USERNAME=...
FTP_PASSWORD=...
FTP_ROOT=/files_product
```

Каталоги как в `Product.ini` / `config/product.php`.

## Структура кода

```
app/
  Domain/
    Auth/          # pravo, modules
    Goods/         # номенклатура (read)
    Store/         # склад (read skeleton)
    System/        # health
  Infrastructure/
    ProductDb/     # единственная точка к external MySQL
    Files/         # CatalogPath + FTP
  Http/Controllers/
  Models/          # internal only
config/product.php # modules menu, pravo ids, catalogs
resources/js/Pages # Inertia Vue pages
docker/            # php-fpm, nginx, entrypoint
```

## Модули (меню)

| Route | Статус |
|-------|--------|
| `/` dashboard + health | готов |
| `/goods` | read skeleton |
| `/store` | read skeleton |
| `/orders` `/purchase` `/routing` `/inventory` `/finance` `/tasks` `/reports` `/settings` | placeholder |

## Права

Зеркало Delphi `get_pravo(pravo_id, podr_id)`:

- таблица `user_permissions`
- middleware `pravo:87` или `pravo:87,2`
- `User::hasPravo()`

## Команды

```powershell
docker compose exec app php artisan migrate
docker compose exec app php artisan db:seed
docker compose exec app php artisan tinker
docker compose logs -f app nginx
docker compose exec node npm run build
```

## Дорожная карта

0. ✅ Каркас Docker, auth, pravo, dual DB, FTP stubs  
1. 🔄 Read: номенклатура / склад (уточнить SQL/SP)  
2. Write через SP (документы)  
3. Заказы, закупки, ТК  
4. Отчёты / печать  
5. Отключение Delphi  

## Источник legacy

`C:\Product` — Delphi 7 client. Не смешивать с этим репозиторием.
