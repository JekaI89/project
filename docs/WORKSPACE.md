# Рабочие каталоги

| Путь | Роль |
|------|------|
| **`C:\product_grok`** | **Единственный рабочий проект** — код, Docker, `.env`, дампы, скрипты |
| **`C:\Product`** | **Только чтение / сравнение** — исходники Delphi 7 + EhLib, старые ini/sql |

## Правило

- Пишем и правим только в `C:\product_grok`.
- В `C:\Product` заглядываем, чтобы понять экран/SP/SQL, **не** складываем туда web-артефакты.
- Дамп БД: `C:\product_grok\storage\dumps\product.sql`
- Конфиг: `C:\product_grok\.env`

## Импорт своей БД (прод-дамп)

1. Скопировать `.sql` → `C:\product_grok\storage\dumps\product_YYYYMMDD.sql`  
   (сейчас: `product_20260722.sql`)
2. `cd C:\product_grok`
3. `powershell -ExecutionPolicy Bypass -File .\scripts\db-setup.ps1 -Force`

Скрипт берёт **самый новый** `product_*.sql` из `storage\dumps`.
