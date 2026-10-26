## Лаб. работа №4
### Тема: Расчет выхода продукта в многостадийном синтезе
Сарыглар Начын, ИУ5-55Б

# Веб-сервис учета этапов химического синтеза

Бэкенд (REST API) системы расчета выхода продукта в многостадийном синтезе. Химики публикуют стадии синтеза (например, синтеза парацетамола) с фото, коротким видео, температурой реакции и выходом стадии. Все названия таблиц, полей и методов соответствуют предметной области.

В 4-й лабораторной добавлены:
* аутентификация и авторизация через **JWT** с **blacklist** отозванных токенов в **Redis**;
* автозаполнение создателя (`chemist_id`) из JWT при создании этапа;
* описание всех методов в **Swagger** (OpenAPI 3.0).

## Стек

| Компонент | Технология |
|---|---|
| Веб-сервис | PHP 8.5, Laravel 13, Eloquent ORM |
| База данных | PostgreSQL 18 |
| Хранилище медиа | MinIO (S3) |
| Blacklist JWT | Redis 8 |
| Авторизация | php-open-source-saver/jwt-auth |
| Документация API | Swagger, darkaonline/l5-swagger |
| Развертывание | Docker, Laravel Sail |

## Запуск

```bash
./vendor/bin/sail up -d
./vendor/bin/sail artisan migrate --seed
./vendor/bin/sail artisan l5-swagger:generate
```

* Swagger UI: http://localhost/api/documentation
* API: http://localhost/api
* MinIO: http://localhost:8900

---

## Авторизация (JWT + blacklist)

1. Химик регистрируется (`POST /api/chemists/register`) и входит (`POST /api/chemists/login`). В ответ приходит `access_token`.
2. Токен передается в заголовке каждого метода создателя:
   ```
   Authorization: Bearer <access_token>
   ```
3. Бэкенд определяет текущего химика из токена (guard `api`, провайдер `chemists`). Создатель этапа, черновик, лайки и признаки `is_owner` / `is_liked` берутся из JWT, а не передаются с клиента.
4. При выходе (`POST /api/chemists/logout`) токен попадает в blacklist в Redis и больше не принимается: на любой защищенный метод с ним приходит `401`.
5. Без токена или с недействительным токеном методы создателя возвращают `401 {"message": "Unauthenticated."}`.

Проверить blacklist в Redis (кеш Laravel хранится в базе 1):
```bash
./vendor/bin/sail exec redis redis-cli -n 1 --scan
```

### Права доступа

| Действие | Гость | Создатель (с JWT) |
|---|---|---|
| Список этапов, лента | ✓ | ✓ (+ признаки `is_owner`, `is_liked`) |
| Регистрация, вход | ✓ | ✓ |
| Черновик, создание, публикация, удаление, лайк, выход | 401 | ✓ |

## Swagger

* Документация генерируется из аннотаций `@OA\...` в контроллерах `app/Http/Controllers/Api`.
* Без авторизации в Swagger доступно только чтение: список этапов и лента.
* Для методов создателя: выполнить `POST /chemists/login` → нажать **Authorize** → вставить `access_token`.
* После изменения аннотаций: `./vendor/bin/sail artisan l5-swagger:generate`.

---

## Таблицы базы данных

Три таблицы, каскадное удаление между ними запрещено (`RESTRICT`).

### 1. chemists (Химики-пользователи)
* `id` (bigint, PK) — уникальный идентификатор химика.
* `full_name` (varchar) — полное имя химика.
* `email` (varchar, unique) — электронная почта, используется как логин.
* `password` (varchar) — хеш пароля.
* `created_at`, `updated_at` (timestamp) — системные поля.

### 2. synthesis_stages (Этапы синтеза — услуги)
* `id` (bigint, PK) — уникальный идентификатор этапа.
* `name` (varchar) — наименование этапа синтеза.
* `brief_description` (text) — краткое описание реакции.
* `lifecycle_status` (enum) — статус: `draft` (черновик), `published` (опубликован), `deleted` (удален).
* `media_image_url` (varchar) — сгенерированное на латинице имя файла изображения в MinIO.
* `media_video_url` (varchar) — сгенерированное на латинице имя файла видео в MinIO.
* `yield_percentage` (numeric 5,2) — поле по предметной области: выход стадии, %.
* `reaction_temperature_celsius` (integer) — поле по предметной области: температура реакции, °C.
* `chemist_id` (bigint, FK) — создатель этапа, заполняется из JWT.
* `created_at`, `updated_at` (timestamp) — системные поля, формируются на бэкенде.

### 3. synthesis_stage_likes (Лайки)
Связь многие-ко-многим между химиками и этапами.
* `id` (bigint, PK) — идентификатор записи.
* `synthesis_stage_id` (bigint, FK) — этап синтеза.
* `chemist_id` (bigint, FK) — химик, поставивший лайк.

---

## HTTP методы (REST API)

Все адреса начинаются с `/api`. Работа с БД через ORM, логическое удаление — через SQL `UPDATE`. Этапы в статусе `deleted` на клиент не передаются. Системные поля (id, статус, создатель, даты) с клиента не принимаются.

### Домен synthesis-stages (Этапы синтеза)

| Метод | URL | Доступ | Описание |
|---|---|---|---|
| GET | `/api/synthesis-stages` | все | Список опубликованных этапов. Фильтр `?filter_temperature=` (минимальная температура). Признак `is_owner` 0/1 — создатель этапа совпадает с текущим химиком |
| GET | `/api/synthesis-stages/feed/{id?}` | все | Лента: без `id` — первый опубликованный этап, с `id` — этот этап, с `id` и `?next=true` — следующий. Признак `is_liked` 0/1 — текущий химик лайкнул этап |
| GET | `/api/synthesis-stages/draft` | создатель | Черновик текущего химика (id не указывается) |
| POST | `/api/synthesis-stages` | создатель | Создание черновика: `name`, файлы `media_image`, `media_video` (multipart). Файлы сохраняются в MinIO |
| PUT | `/api/synthesis-stages/{id}/publish` | создатель | Публикация своего черновика: `brief_description`, `yield_percentage`, `reaction_temperature_celsius`. Вернуть в черновик нельзя |
| DELETE | `/api/synthesis-stages/{id}` | создатель | Логическое удаление своего этапа (статус `deleted`) |
| POST | `/api/synthesis-stages/{id}/like` | создатель | Лайк: `action` = 1 ставит, 0 отменяет |

### Домен chemists (Химики)

| Метод | URL | Доступ | Описание |
|---|---|---|---|
| POST | `/api/chemists/register` | все | Регистрация: `full_name`, `email`, `password` |
| POST | `/api/chemists/login` | все | Аутентификация: `email`, `password` → `access_token`, `token_type` |
| POST | `/api/chemists/logout` | создатель | Деавторизация: токен добавляется в blacklist Redis |
