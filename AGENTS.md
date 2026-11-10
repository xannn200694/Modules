# AGENTS.md

Пакет `xannn94/modules`: модули Laravel 13 со слоями `Domain`, `Application`, `Infrastructure`. Правь исходники в этом каталоге. Копия в `Cms/vendor/xannn94/modules` — результат установки, её не редактируют.

## Раскладка

Новый модуль создаёт только `make:module`. Для slug `user` срез называется `ListUsersUseCase`, `ListUsersRequest`, `ListUsersResult` и лежит в `Application/UseCase/ListUsers/`. Запрос несёт `page` и `perPage`, результат — элементы с `name` и `email` и поле `total`. Контроллер списка вызывает сценарий. Модель домена не наследует Eloquent. Модель хранения — `Infrastructure/Persistence/Eloquent`, порт — `Domain/Contracts`, реализация — `EloquentUserRepository`.

`make:module:model` пишет модель хранения, не модель домена. `pathMap` не задаёт эту раскладку: карта одна на приложение и не различает старый и новый модуль.

Уже созданные модули приложения, включая `Cms/app/Modules/User`, не перекладывай, пока об этом не попросили. Старт берёт `Infrastructure\Providers\ModuleServiceProvider`, а если класса нет — прежний `Providers\ModuleServiceProvider`. Миграции и сидеры так же предпочитают новый каталог и читают `Database/Migrations` и `Database/Seeds`, только когда нового каталога нет.

## Проверки

Перед завершением правки `src` или тестов прогони из этого каталога:

```bash
docker compose run --rm php vendor/bin/phpcs
docker compose run --rm php vendor/bin/phpstan
docker compose run --rm php vendor/bin/phpunit
```

phpcs — PSR-12 для `src`. phpstan — уровень 5 для `src`. Тесты живут в `tests/` и поднимают пакет через Testbench, без каркаса приложения в репозитории.

## OpenSpec

Планирование лежит в `openspec/`. Артефакты пиши по-русски. Заголовки OpenSpec и слова SHALL/MUST оставляй на английском. Поведение генератора и старта меняется через change, а не правкой кода в обход спецификации.
