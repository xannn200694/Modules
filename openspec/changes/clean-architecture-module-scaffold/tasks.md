# Tasks

## 1. Старт модуля

- [x] 1.1 В `Modules::registerServiceProvider()` сначала регистрировать `{namespace}{basename}\Infrastructure\Providers\ModuleServiceProvider`. Если класс есть, прежний `Providers\ModuleServiceProvider` не регистрировать. Если инфраструктурного класса нет, регистрировать прежний, когда он есть. Проверка: `docker compose run --rm php vendor/bin/phpunit --filter ModuleBootTest` в WSL завершается с кодом 0, и существующие сценарии прежнего провайдера остаются зелёными.
- [x] 1.2 Расширить хелпер `ModuleBootTest`, чтобы модуль мог получить провайдер в `Infrastructure/Providers`, в `Providers` или в обоих каталогах, и добавить сценарии: только инфраструктурный класс, только прежний класс, оба класса сразу. Проверка: тот же фильтр `ModuleBootTest` зелёный и покрывает три новых сценария.

## 2. Миграции и сидеры

- [x] 2.1 Вынести выбор каталога миграций: при наличии `Infrastructure/Persistence/Migrations` читать его буквальным путём, иначе читать `Database/Migrations` через `module_path()`. Подключить выбор в `module:migrate`, rollback, reset, refresh и `MigrationTrait`. Проверка: тест на оба каталога проходит в `docker compose run --rm php vendor/bin/phpunit`.
- [x] 2.2 В `module:seed` при наличии `Infrastructure/Persistence/Seeders` искать только `{namespace}{name}\Infrastructure\Persistence\Seeders\{name}DatabaseSeeder`, иначе — прежний класс в `Database\Seeds`. Проверка: тест отличает новый каталог, пустой новый каталог и прежний `Database/Seeds`, и `phpunit` для этого теста завершается с кодом 0.

## 3. Стартовое дерево

- [x] 3.1 Перенести стабы `resources/stubs/module` в `Domain`, `Application` и `Infrastructure`: срез `List{Plural}UseCase`, `List{Plural}Request`, `List{Plural}Result`, модель домена, порт, модель хранения, `Eloquent{Basename}Repository`, миграция таблицы с `name` и `email`, провайдеры, маршруты, представление, переводы, `Config` и `module.json`. Админский контроллер, `Route::localizedGroup`, меню и `settings.php` сохранить в новых каталогах. В корне модуля не оставлять `Http`, `Models`, `Database`, `Providers`, `Routes`, `Resources`. Проверка: дерево стабов совпадает со сценарием модуля `user` в `specs/module-scaffold/spec.md`.
- [x] 3.2 В `MakeModuleCommand` до копирования заполнять `basename`, `namespace` и множественное число basename и в `--quick`, и в мастере. Мастер заканчивать на `generate()`, не вызывая `make:module:model`, `make:module:migration` и `make:module:seeder`, и завершать команду успешно. Проверка: тест `--quick` для slug `user` и slug `order` создаёт имена `ListUsers*` и `ListOrders*`, а прогон мастера с ответами на вопросы даёт то же дерево и не создаёт `Models/`.
- [x] 3.3 Проверить поведение среза на сгенерированном модуле `user`: `ListUsersRequest` без аргументов даёт `page = 1` и `perPage = 15`, сценарий с подменённым портом возвращает `total` и элементы с `name` и `email`, контроллер списка вызывает `ListUsersUseCase` и не читает модель хранения. Проверка: эти утверждения входят в тест генерации, и `docker compose run --rm php vendor/bin/phpunit` для него завершается с кодом 0.

## 4. Команды генерации

- [x] 4.1 Перевести `make:module:controller`, `middleware`, `request` и `policy` на `Infrastructure\Http`, `make:module:model` на `Infrastructure\Persistence\Eloquent`, `make:module:provider` на `Infrastructure\Providers`, `make:module:migration` на `Infrastructure/Persistence/Migrations`, `make:module:seeder` на `Infrastructure/Persistence/Seeders`. Проверка: тест на модуле новой раскладки создаёт контроллер, модель и миграцию в этих каталогах, и `phpunit` для него завершается с кодом 0.

## 5. Сводка проверок

- [x] 5.1 Прогнать полный набор проверок пакета. Проверка: в WSL `docker compose run --rm php vendor/bin/phpcs`, `docker compose run --rm php vendor/bin/phpstan` и `docker compose run --rm php vendor/bin/phpunit` каждая завершается с кодом 0.

## Workflow follow-up

- После ревью заархивировать изменение и убедиться, что `module-scaffold` появилась в `openspec/specs/`, а требование провайдера в `openspec/specs/module-boot/spec.md` совпадает с дельтой.
