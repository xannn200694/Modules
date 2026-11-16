# Design

## Context

См. proposal.md, раздел Why. Сейчас `make:module` копирует `resources/stubs/module` в каталог модуля, а `make:module:*` пишет классы в `Http`, `Models`, `Providers`, `Database`. Старт в `Modules::registerServiceProvider()` ищет только `{namespace}{basename}\Providers\ModuleServiceProvider`. Миграции читают `Database/Migrations` через `module_path()`. `module:seed` ищет класс `{namespace}{name}\Database\Seeds\{name}DatabaseSeeder`.

`module_path()` дополнительно прогоняет относительный путь через `modules.pathMap`. Карта одна на приложение, поэтому ею нельзя развести старый и новый модуль.

Режим `--quick` вызывает `generate()`, не заполнив `basename` и `namespace`. Мастер после `generate()` ещё вызывает `make:module:model`, `make:module:migration` и `make:module:seeder`, затем возвращает строку ошибки.

## Goals / Non-Goals

**Goals:**

- Оба режима `make:module` копируют одно дерево из стабов, уже с заполненными `basename`, множественным числом и пространством имён.
- Стартовый список модуля собирается как `Infrastructure → UseCase → Domain`, а модель хранения остаётся за портом репозитория.
- Команды миграций и сидеров сами выбирают новый каталог или прежний.
- Старт предпочитает инфраструктурный провайдер и оставляет прежний класс рабочим.

**Non-Goals:**

- Перекладывать уже созданные модули приложения, включая `Cms/app/Modules/User`.
- Вести админский CRUD через сценарий. Админский контроллер остаётся адаптером CMS и получает модель хранения.
- Подбирать поля среза по предметной области модуля. У каждого нового модуля образец один: `name`, `email`, `page`, `perPage`, `total`.
- Менять правила `enabled`, `order` и `autoload` у `module-boot`.

## Decisions

### Дерево копируется стабами, мастер не дописывает второй набор файлов

`generate()` по-прежнему копирует `resources/stubs/module`, подставляя плейсхолдеры. Срез списка, обе модели, порт, реализация, миграция, провайдеры, маршруты и представление лежат в стабах. Мастер после подтверждения заканчивается на `generate()` и больше не вызывает `make:module:model`, `make:module:migration`, `make:module:seeder`. Иначе рядом со срезом появилась бы вторая модель.

Оба режима до копирования заполняют `basename` как studly slug, `namespace` как `modules.namespace` + basename и множественное число basename через `Str::plural`. Для slug `user` это `Users` и таблица `users`. Человекочитаемое имя остаётся в `module.json`.

Альтернатива — собирать срез отдельными командами после копирования пустых каталогов. Она расходится у `--quick` и мастера, а спецификация требует одно дерево.

### Срез — три класса, элементы результата без четвёртого типа

Каталог `Application/UseCase/ListUsers/` содержит только `ListUsersUseCase`, `ListUsersRequest` и `ListUsersResult`. Запрос — readonly-объект с `page = 1` и `perPage = 15`. Результат хранит `total` и список элементов `array{name: string, email: string}`. Отдельный класс элемента не вводится: в срезе остаются три согласованных имени.

`ListUsersUseCase::handle(ListUsersRequest): ListUsersResult` вызывает порт домена и перекладывает модели домена в элементы результата. Контроллер списка собирает запрос из query: положительные целые `page` и `perPage` попадают в запрос, остальные значения заменяются умолчаниями. Представление печатает `name` и `email`.

Модель домена — обычный объект с `name` и `email`, без базового класса Eloquent. `UserRepository::list(int $page, int $perPage)` возвращает модели домена и полное количество. `EloquentUserRepository` выполняет запрос к `UserModel`. Провайдер модуля связывает порт с этой реализацией.

Альтернатива — отдать во view коллекцию Eloquent. Она короче, но снова склеивает контроллер с хранением.

### Генераторы меняют пространство имён напрямую

`pathMap` не заполняется и не становится механизмом раскладки. Команды `make:module:*` меняют свой относительный namespace:

| Команда | Куда пишет |
|---|---|
| controller, middleware, request | `Infrastructure\Http\...` |
| policy | `Infrastructure\Http\Policies` |
| model | `Infrastructure\Persistence\Eloquent` |
| provider | `Infrastructure\Providers` |
| migration | `Infrastructure/Persistence/Migrations` |
| seeder | `Infrastructure/Persistence/Seeders`, namespace `Infrastructure\Persistence\Seeders` |

`make:module:model` создаёт модель хранения. Модель домена входит только в стартовый срез.

Альтернатива — оставить старые namespace и переписать их картой путей. Карта не различает модули и не меняет хвост namespace вроде `Http\Controllers`.

### Миграции и сидеры выбирают каталог сами

Общий способ выбирает путь миграций: если у модуля есть каталог `Infrastructure/Persistence/Migrations`, команды читают его; иначе читают `Database/Migrations`. Через него проходят `module:migrate`, rollback, reset, refresh и `MigrationTrait`. Новый путь собирается без подмены `pathMap`. Прежний путь по-прежнему проходит через `module_path()`, чтобы уже настроенная карта не сломалась.

`module:seed` сначала ищет `{namespace}{name}\Infrastructure\Persistence\Seeders\{name}DatabaseSeeder`, если каталог `Infrastructure/Persistence/Seeders` есть. Иначе ищет прежний `{namespace}{name}\Database\Seeds\{name}DatabaseSeeder`. Пустой новый каталог не проваливается в старый класс.

### Старт пробует два класса провайдера

`registerServiceProvider()` сначала проверяет `{namespace}{basename}\Infrastructure\Providers\ModuleServiceProvider`. Класс есть — регистрируется он один. Класса нет — проверяется прежний `Providers\ModuleServiceProvider`. Нет обоих — модуль пропускается, как сейчас.

Существующие тесты `ModuleBootTest` пишут прежний класс и остаются годными. Для нового сценария хелпер теста умеет положить провайдер в `Infrastructure/Providers`, в `Providers` или в оба места.

## Risks / Trade-offs

- [Образец `name`/`email` не похож на предметную область модуля вроде `order`] → Это стартовый срез, его правят после генерации. Генератор не спрашивает поля.
- [В модуле оказались оба провайдера] → Стартует только инфраструктурный. Прежний класс молчит, пока новый файл лежит рядом.
- [Пустой `Infrastructure/Persistence/Seeders` закрывает старый сидер] → Так и задумано: наличие нового каталога означает новую раскладку. Старый модуль этот каталог не получает.
- [`pathMap` уже переписывает `Database/Migrations`] → Новый каталог проверяется буквальным путём. Прежний вызов `module_path()` для старых модулей сохраняется.
- [Админский контроллер по-прежнему создаёт модель хранения] → Он остаётся адаптером CMS. Публичный список через него не ходит.

## Migration Plan

Пакет меняет генератор и старт. Уже лежащие модули не перемещаются: у них нет инфраструктурного провайдера, поэтому старт берёт прежний класс, а миграции остаются в `Database/Migrations`.

Новый модуль появляется только через обновлённый `make:module`. Откат — вернуть предыдущую версию пакета до следующей генерации. Уже сгенерированный по новой раскладке модуль откат пакета не перекладывает обратно: его провайдер перестанет находиться, пока пакет снова не понимает инфраструктурный класс.

Выкладка не требует миграции данных и пересборки кэша модулей: класс провайдера в кэш не записывается.
