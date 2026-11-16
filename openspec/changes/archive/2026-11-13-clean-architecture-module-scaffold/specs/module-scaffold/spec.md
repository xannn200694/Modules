# Spec Delta

## Purpose

Фиксирует дерево каталогов и стартовый срез списка, которые `make:module` создаёт для нового модуля, и места, куда последующие команды генерации кладут классы, миграции и сидеры.

## ADDED Requirements

### Requirement: Три слоя

`make:module` SHALL создать в каталоге модуля три прямых потомка: `Domain`, `Application` и `Infrastructure`. Команда MUST NOT создавать в корне модуля каталоги `Http`, `Models`, `Database`, `Providers`, `Routes` и `Resources`. Режим `--quick` и мастер SHALL давать одно и то же дерево.

#### Scenario: Быстрое создание

- **WHEN** пользователь выполняет `make:module` для slug `user` с `--quick`
- **THEN** в каталоге модуля есть `Domain`, `Application` и `Infrastructure`, а в корне модуля нет `Http`, `Models`, `Database`, `Providers`, `Routes` и `Resources`

#### Scenario: Мастер

- **WHEN** пользователь проходит мастер `make:module` и подтверждает создание модуля со slug `user`
- **THEN** дерево совпадает с деревом режима `--quick` для того же slug

### Requirement: Имена среза списка

Команда SHALL вывести basename как studly-форму slug и SHALL назвать срез списка множественным числом этого basename. Для basename `User` классы SHALL называться `ListUsersUseCase`, `ListUsersRequest` и `ListUsersResult` и SHALL лежать в `Application/UseCase/ListUsers/`. Пространство имён SHALL быть настроенным пространством имён модулей, затем basename, затем `Application\UseCase\ListUsers`.

#### Scenario: Модуль user

- **WHEN** `make:module` создаёт модуль со slug `user`
- **THEN** существуют `Application/UseCase/ListUsers/ListUsersUseCase.php`, `ListUsersRequest.php` и `ListUsersResult.php` в пространстве имён `{namespace}User\Application\UseCase\ListUsers`

#### Scenario: Модуль order

- **WHEN** `make:module` создаёт модуль со slug `order`
- **THEN** классы называются `ListOrdersUseCase`, `ListOrdersRequest` и `ListOrdersResult` и лежат в `Application/UseCase/ListOrders/`

### Requirement: Контракт запроса и результата

`List{Plural}Request` SHALL нести целые `page` и `perPage`. Без аргументов `page` SHALL быть `1`, а `perPage` SHALL быть `15`. `List{Plural}Result` SHALL нести список элементов с полями `name` и `email` и целое `total`. Сценарий SHALL принимать запрос и возвращать результат.

#### Scenario: Значения по умолчанию

- **WHEN** код создаёт `ListUsersRequest` нового модуля `user` без аргументов
- **THEN** `page` равен `1` и `perPage` равен `15`

#### Scenario: Страница списка

- **WHEN** сценарий списка модуля `user` получает запрос с `page` и `perPage`
- **THEN** результат содержит `total` по всему набору и элементы текущей страницы, у каждого элемента есть `name` и `email`

### Requirement: Вызов сценария из HTTP

Стартовый контроллер списка SHALL получить список, вызвав сценарий, и SHALL передать результат в представление. Этот контроллер MUST NOT читать модель хранения напрямую. Представление SHALL вывести `name` и `email` каждого элемента.

#### Scenario: Открытие списка

- **WHEN** HTTP-запрос открывает стартовую страницу списка нового модуля
- **THEN** контроллер вызывает `List{Plural}UseCase`, а представление показывает `name` и `email` элементов результата

### Requirement: Модель и порт

В `Domain/Model` SHALL быть модель домена с `name` и `email`, и она MUST NOT быть моделью хранения. В `Domain/Contracts` SHALL быть порт репозитория, через который сценарий получает модели домена. В `Infrastructure/Persistence` SHALL быть отдельная модель хранения и реализация порта.

#### Scenario: Модуль user

- **WHEN** `make:module` создаёт модуль со slug `user`
- **THEN** существуют `Domain/Model/User.php`, `Domain/Contracts/UserRepository.php`, `Infrastructure/Persistence/Eloquent/UserModel.php` и `Infrastructure/Persistence/EloquentUserRepository.php`, и модель домена не является моделью хранения

### Requirement: Провайдер нового модуля

`make:module` SHALL поместить `ModuleServiceProvider` и `RouteServiceProvider` в `Infrastructure/Providers`. Класс провайдера модуля SHALL быть `{namespace}{basename}\Infrastructure\Providers\ModuleServiceProvider`.

#### Scenario: Класс провайдера

- **WHEN** `make:module` создаёт модуль со slug `user`
- **THEN** файл `Infrastructure/Providers/ModuleServiceProvider.php` объявляет класс `{namespace}User\Infrastructure\Providers\ModuleServiceProvider`

### Requirement: Остальные стартовые файлы

Команда SHALL оставить `module.json` в корне модуля. Маршруты SHALL лежать в `Infrastructure/Http/Routes`, представления и переводы — в `Infrastructure/View`, настройки — в `Infrastructure/Config`. Каталоги миграций, фабрик и сидеров SHALL лежать в `Infrastructure/Persistence`. Стартовая миграция SHALL создавать таблицу по множественному snake-имени basename с колонками `name` и `email`.

#### Scenario: Модуль user

- **WHEN** `make:module` создаёт модуль со slug `user`
- **THEN** в корне есть `module.json`, маршруты лежат в `Infrastructure/Http/Routes`, и есть миграция, создающая таблицу `users` с колонками `name` и `email`

### Requirement: Команды генерации

Последующая команда `make:module:*` SHALL записывать файл в слой нового дерева: контроллер, middleware, request и policy — в `Infrastructure/Http`, модель — в `Infrastructure/Persistence/Eloquent`, провайдер — в `Infrastructure/Providers`, миграцию — в `Infrastructure/Persistence/Migrations`, сидер — в `Infrastructure/Persistence/Seeders`.

#### Scenario: Новый контроллер

- **WHEN** пользователь выполняет `make:module:controller` для уже созданного модуля новой раскладки
- **THEN** класс появляется в `Infrastructure/Http/Controllers`

#### Scenario: Новая модель хранения

- **WHEN** пользователь выполняет `make:module:model` для уже созданного модуля новой раскладки
- **THEN** класс появляется в `Infrastructure/Persistence/Eloquent`

#### Scenario: Новая миграция

- **WHEN** пользователь выполняет `make:module:migration` для уже созданного модуля новой раскладки
- **THEN** файл появляется в `Infrastructure/Persistence/Migrations`

### Requirement: Поиск миграций и сидеров

Команды модуля, которые читают миграции, SHALL брать `Infrastructure/Persistence/Migrations`, когда этот каталог есть, и `Database/Migrations`, когда его нет. Команды, которые читают сидеры, SHALL так же выбирать `Infrastructure/Persistence/Seeders`, а при его отсутствии — `Database/Seeds`.

#### Scenario: Новый модуль

- **WHEN** у модуля есть `Infrastructure/Persistence/Migrations` и команда миграций модуля запускается
- **THEN** команда читает миграции из `Infrastructure/Persistence/Migrations`

#### Scenario: Прежний модуль

- **WHEN** у модуля нет `Infrastructure/Persistence/Migrations`, а каталог `Database/Migrations` есть, и команда миграций модуля запускается
- **THEN** команда читает миграции из `Database/Migrations`
