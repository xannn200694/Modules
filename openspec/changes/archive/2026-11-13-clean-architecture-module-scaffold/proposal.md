# Proposal

## Why

`make:module` собирает модуль как набор каталогов Laravel: контроллер сразу читает Eloquent-модель, сценария приложения между ними нет. Новые модули должны появляться сразу в чистой архитектуре, где список проходит через UseCase с явным запросом и результатом.

## What Changes

- **BREAKING** `make:module` в режиме `--quick` и в мастере создаёт модуль из трёх слоёв: `Domain`, `Application`, `Infrastructure`. Прежние каталоги `Http`, `Models`, `Database`, `Providers`, `Routes`, `Resources` в корне модуля больше не создаются.
- Стартовый вертикальный срез списка называет классы от basename модуля: `User` даёт `ListUsersUseCase`, `ListUsersRequest`, `ListUsersResult`.
- `List{Plural}Request` несёт `page` и `perPage`. `List{Plural}Result` несёт элементы с `name` и `email` и поле `total`. Эти поля — образец стартового среза для любого нового модуля.
- Команды `make:module:*` кладут новые классы в соответствующие слои.
- **BREAKING** у нового модуля провайдер — `Infrastructure\Providers\ModuleServiceProvider`. Boot регистрирует этот класс, если он есть. Если его нет, boot регистрирует прежний `Providers\ModuleServiceProvider`, и уже созданные модули продолжают стартовать.
- Уже созданные модули приложения, включая `Cms/app/Modules/User`, эта смена не перекладывает.

## Capabilities

### New Capabilities

- `module-scaffold`: состав каталогов и стартовых файлов, которые создаёт `make:module`, и каталоги, в которые пишут команды `make:module:*`.

### Modified Capabilities

- `module-boot`: требование «Провайдер модуля» выбирает `Infrastructure\Providers\ModuleServiceProvider`, а при его отсутствии — прежний класс `Providers\ModuleServiceProvider`.

## Impact

- Стабы `resources/stubs/module/**`, `MakeModuleCommand`, генераторы в `src/Console/Generators`, пути миграций и сидеров в командах модуля.
- `Modules::registerServiceProvider` и тесты `tests/ModuleBootTest.php`.
- Карта путей `modules.pathMap` эту раскладку не задаёт: она одна на приложение и не различает старый и новый модуль.
- Зависимости Composer не меняются. PSR-4 `App\` → `app/` уже покрывает новые пространства имён.
