# Domain layer

Операции ERP, не таблицы.

- Один use-case = один (или несколько) классов в `Domain/{Module}/`.
- Доступ к external MySQL — только через `Infrastructure\ProductDb\ProductQuery`.
- HTTP-контроллеры тонкие: validate → domain → Inertia.

Когда переносите экран Delphi:

1. Найдите SQL/SP в `.pas` / `dmGet`.
2. Добавьте метод в repository/service.
3. Inertia page + route.
4. Права `pravo:*` при необходимости.
