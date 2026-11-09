# Tasks

## 1. Платформа и Compose

- [x] 1.1 Поднять в `composer.json` PHP до `^8.5` и `illuminate/support`, `console`, `filesystem`, `config`, `database` плюс `laravel/framework` до `13.40.*`, убрать `illuminate/routing` из `require`. Проверка: `composer validate` проходит, а перечисленные ограничения видны в `composer.json`.
- [x] 1.2 Добавить в `require-dev` phpcs, phpstan, phpunit и `orchestra/testbench` `^11.3`, описать образ PHP 8.5.12 и команды Compose для трёх проверок без публикации порта. Проверка: `docker compose config` в WSL показывает сервис PHP 8.5 и не содержит проброса порта.

## 2. Загрузка на Laravel 13

- [x] 2.1 Заменить `fire()` на `handle()`, `str_slug()` на `Str::slug()`, `studly_case()` на `Str::studly()` в `src/`. Проверка: поиск по `src/` больше не находит эти три вызова.
- [x] 2.2 Сохранить текущий состав шаблона модуля для `xannn94/larakhan`. Проверка: заглушки по-прежнему содержат `App\Providers\ModuleProvider`, `App\Modules\Admin\Http\Controllers\Admin`, `Route::localizedGroup` и файлы меню и `settings.php`.
- [x] 2.3 Добавить тесты Testbench на сценарии `openspec/specs/module-boot/spec.md` без каркаса приложения в репозитории. Проверка: `docker compose run --rm php vendor/bin/phpunit` в WSL завершается с кодом 0.

## 3. Стиль и статический анализ

- [x] 3.1 Настроить phpcs на PSR-12 и довести исходники пакета до этого стандарта. Проверка: `docker compose run --rm php vendor/bin/phpcs` в WSL завершается с кодом 0.
- [x] 3.2 Настроить phpstan на уровень 5 для каталога исходников и устранить найденные ошибки. Проверка: `docker compose run --rm php vendor/bin/phpstan` в WSL завершается с кодом 0.

## Workflow follow-up

- После ревью заархивировать изменение и убедиться, что capability `package-checks` попала в `openspec/specs/package-checks/`.
