# Модуль платежей - Документация

## Структура проекта

### Laravel контроллеры
- **BillingController** (`app/Http/Controllers/BillingController.php`)
  - `index()` - страница выбора тарифов
  - `initiate()` - инициация платежа (JSON API)
  - `processing()` - страница ожидания обработки
  - `success()` - страница успеха платежа
  - `failed()` - страница ошибки платежа
  - `checkStatus()` - API endpoint для проверки статуса заказа

### Vue 3 компоненты

#### Pages (в `resources/js/pages/Billing/`)
1. **Index.vue** - Экран выбора тариф
   - Сетка с 3 тарифами (Базовый, Про, Бизнес)
   - Sandbox Alert баннер сверху
   - Компонент TestCardInfo для демонстрации тестовых карт
   - Обработка клика на кнопку "Выбрать" с отправкой POST запроса

2. **Processing.vue** - Экран ожидания обработки платежа
   - Анимированный спиннер
   - Автоматический опрос статуса платежа каждые 2 секунды
   - Авторедирект на Success или Failed в зависимости от результата

3. **Success.vue** - Экран успешного платежа
   - Зелёная галочка с анимацией
   - Информация об активированном тарифе
   - Кнопка для перехода в личный кабинет

4. **Failed.vue** - Экран ошибки платежа
   - Красный крестик с анимацией
   - Динамический блок ошибки из пропсов
   - Советы для решения проблемы
   - Кнопки для повтора и возврата в кабинет

#### Components
- **TestCardInfo.vue** - Компонент с тестовыми картами для sandbox режима
- **PaymentLogPanel.vue** - Логгер операций для рекрутера (terminal-style)

### API Endpoints

#### POST `/billing/initiate`
Инициация платежа. Требует аутентификации.

**Request:**
```json
{
  "plan_id": "basic|pro|business"
}
```

**Response:**
```json
{
  "redirect_url": "https://yookassa.ru/checkout/..."
}
```

#### GET `/api/v1/orders/{orderId}/status`
Проверка статуса платежа. Требует аутентификации.

**Response:**
```json
{
  "status": "completed|failed|processing",
  "order_id": "...",
  "error": "..."
}
```

### Маршруты

Все маршруты требуют аутентификации (`middleware(['auth', 'verified'])`):

```
GET  /billing/                    → billing.index        (выбор тарифа)
POST /billing/initiate             → payment.initiate     (инициация платежа)
GET  /billing/processing           → billing.processing   (обработка)
GET  /billing/success              → billing.success      (успех)
GET  /billing/failed               → billing.failed       (ошибка)
GET  /api/v1/orders/{id}/status    → billing.checkStatus  (API проверки)
```

## Интеграция с YooKassa

1. **PaymentService** создает платеж через YooKassa API
2. **OrderService** управляет заказами и связывает их с платежами
3. Платежная система использует webhook для обновления статуса платежа

## Особенности UI

### Дизайн
- Современный минималистичный дизайн с Tailwind CSS
- Полная поддержка Dark Mode
- Адаптивный дизайн для всех устройств
- Плавные анимации и переходы

### Accessibility
- Семантическая HTML разметка
- Доступные иконки с SVG
- Читаемые контрасты цветов
- Поддержка клавиатурной навигации

### Интерактивность
- Загрузочные состояния на кнопках
- Анимированные иконки (спиннеры, галочки, крестики)
- Плавные переходы между состояниями
- Подсказки для пользователя

## Как использовать

### Для тестирования платежей

1. Используйте тестовые карты из компонента **TestCardInfo.vue**:
   - **Успех**: 4111 1111 1111 1111
   - **Недостаточно средств**: 4002 0200 0200 0200
   - **Ошибка**: 5555 5555 5555 4444

2. Дата и CVC - любые будущие значения

### Логирование операций

Компонент **PaymentLogPanel.vue** отслеживает события платежей в real-time. 
В реальном приложении логи должны приходить через:
- WebSocket
- Server-Sent Events (SSE)
- Long Polling

## Структура базы данных

### Таблица `orders`
```sql
- id (ULID)
- user_id (foreign)
- amount (decimal)
- currency (string)
- status (string)
- description (string)
- timestamps
```

### Таблица `payments`
```sql
- id (ULID)
- order_id (foreign ULID)
- gateway_payment_id (string, nullable)
- status (string: created|processing|succeeded|failed)
- payment_method (string, nullable)
- error_message (text, nullable)
- timestamps
```

## Модели

### Order
```php
class Order extends Model {
    public function payment() -> HasOne
}
```

### Payment
```php
class Payment extends Model {
    public function order() -> BelongsTo
}
```

## Enums

### OrderStatusEnum
- Пока пуст (может быть заполнен позже)

### PaymentStatusEnum
- CREATED = 'created'
- PROCESSING = 'processing'
- SUCCEEDED = 'succeeded'
- FAILED = 'failed'

## Инструкции для рекрутера

1. Откройте `/billing/` (требует логина)
2. Выберите тариф
3. Используйте тестовые карты для симуляции успеха/ошибок
4. Посмотрите логи операций в **PaymentLogPanel** (внизу справа)
5. Проверьте все три сценария:
   - ✓ Успешный платеж
   - ✗ Ошибка платежа
   - ⚠ Timeout при обработке

## Технические детали

- **Frontend**: Vue 3 (Composition API + TypeScript)
- **Backend**: Laravel 13 + Inertia.js
- **Styling**: Tailwind CSS
- **API**: YooKassa SDK
- **Database**: Eloquent ORM с ULID

## Возможные улучшения

1. WebSocket для real-time логирования
2. Более подробные логи операций
3. Retry механизм для неудачных платежей
4. Email уведомления о статусе платежа
5. Поддержка множественных способов оплаты (СБП, Яндекс.Касса и т.д.)
