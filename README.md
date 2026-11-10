# xannn94/modules

Пакет модулей для Laravel 13. Новый модуль появляется сразу в трёх слоях: `Domain`, `Application`, `Infrastructure`. Стартовый список проходит через сценарий, а не через прямой запрос к модели хранения.

Нужны PHP 8.5 и Laravel 13.40.

## Установка

Подключите пакет через Composer и зарегистрируйте провайдер в `bootstrap/providers.php`:

```php
use Xannn94\Modules\ModulesServiceProvider;

return [
    ModulesServiceProvider::class,
];
```

Конфиг публикуется так:

```bash
php artisan vendor:publish --provider="Xannn94\Modules\ModulesServiceProvider" --tag=config
```

По умолчанию модули лежат в `app/Modules`, пространство имён — `App\Modules\`. Фасад, если он нужен: `Xannn94\Modules\Facades\Module`.

## Новый модуль

```bash
php artisan make:module user --quick
```

Без `--quick` команда спрашивает имя, slug, версию и описание, а дерево получается тем же. Для slug `user` каталог называется `User`, срез списка — `ListUsers*`. Для `order` это `ListOrders*`.

```
User/
  module.json
  Domain/
    Model/User.php
    Contracts/UserRepository.php
  Application/UseCase/ListUsers/
    ListUsersUseCase.php
    ListUsersRequest.php
    ListUsersResult.php
  Infrastructure/
    Persistence/
      Eloquent/UserModel.php
      EloquentUserRepository.php
      Migrations/ Factories/ Seeders/
    Http/          контроллеры, запросы, middleware, маршруты
    Providers/     ModuleServiceProvider, RouteServiceProvider
    View/          представления и переводы
    Config/        menu.php, settings.php
```

`ListUsersRequest` несёт `page` (по умолчанию 1) и `perPage` (по умолчанию 15). `ListUsersResult` несёт элементы с `name` и `email` и поле `total`. Контроллер списка вызывает `ListUsersUseCase`. Модель домена не является моделью Eloquent: хранение лежит в `UserModel` и `EloquentUserRepository`.

Дальше те же команды пишут в новые слои:

```bash
php artisan make:module:controller user ShowController
php artisan make:module:model user Profile
php artisan make:module:migration user add_bio_to_users
php artisan make:module:middleware user LogRequests
php artisan make:module:request user StoreUserRequest
php artisan make:module:policy user UserPolicy
php artisan make:module:provider user ExtraServiceProvider
php artisan make:module:seeder user UserSeeder
```

Модель, созданная `make:module:model`, — модель хранения в `Infrastructure/Persistence/Eloquent`.

## Старт и уже созданные модули

Пока есть `storage/app/modules.json`, старт читает его и не открывает манифесты. Пересобрать кэш: `php artisan module:optimize`.

Для нового модуля регистрируется `App\Modules\User\Infrastructure\Providers\ModuleServiceProvider`. Если этого класса нет, регистрируется прежний `App\Modules\User\Providers\ModuleServiceProvider`. Уже лежащий модуль команда `make:module` не перекладывает.

Миграции читаются из `Infrastructure/Persistence/Migrations`, когда этот каталог есть, иначе из `Database/Migrations`. Сидер ищется в `Infrastructure/Persistence/Seeders`, когда этот каталог есть, иначе в `Database/Seeds`.

## Команды модуля

```bash
php artisan module:list
php artisan module:enable user
php artisan module:disable user
php artisan module:migrate user
php artisan module:migrate:rollback user
php artisan module:migrate:reset user
php artisan module:migrate:refresh user
php artisan module:seed user
php artisan module:optimize
```

## Проверки

Из каталога пакета, через Compose, без публикации порта:

```bash
docker compose run --rm php vendor/bin/phpcs
docker compose run --rm php vendor/bin/phpstan
docker compose run --rm php vendor/bin/phpunit
```

phpcs проверяет `src` на PSR-12. phpstan смотрит `src` на уровне 5.

## Лицензия

MIT. Текст лицензии — в файле `LICENSE`.
