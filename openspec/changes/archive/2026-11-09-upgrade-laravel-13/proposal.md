# Proposal

## Why

Пакет собран под PHP 5.6 и Laravel 5.3, поэтому его нельзя проверить на текущем стеке и нельзя подключить к хосту на актуальном Laravel. Нужны жёсткие проверки в WSL, которые подтверждают уже зафиксированный контракт загрузки и не требуют отдельного приложения.

## What Changes

- **BREAKING:** `php` поднимается до `^8.5` (образ — 8.5.12), зависимости, без которых загружается `src/`, поднимаются до `13.40.*`: `illuminate/support`, `console`, `filesystem`, `config`, `database` и `laravel/framework` (в нём живёт `Illuminate\Foundation\Application`).
- Команды переводятся на API Laravel 13. Поведение capability `module-boot` не меняется.
- Шаблон модуля сохраняет контракт хоста `xannn94/larakhan`: состав генерируемых файлов не переделывается.
- В `require-dev` добавляются phpcs (PSR-12), phpstan (уровень 5), phpunit и Orchestra Testbench 11.
- Compose в WSL запускает phpcs, phpstan и phpunit и завершается. Первые тесты покрывают `module-boot`.
- Приложение Laravel, HTTP-сервис и репозиторий `xannn94/larakhan` в этот репозиторий не добавляются.

## Capabilities

### New Capabilities

- `package-checks`: запуск phpcs, phpstan и phpunit через Compose в WSL на PHP 8.5. Capability описывает, чем заканчивается успешный прогон и что phpunit проверяет контракт `module-boot` через Testbench, без отдельного приложения.

### Modified Capabilities

Нет. Требования `module-boot` не меняются. Тесты подтверждают уже опубликованную спецификацию.

## Impact

- `composer.json`: платформа PHP, линейка `illuminate/*`, dev-зависимости. Для потребителей Laravel 5.3 это несовместимое обновление.
- Команды в `src/Console`: `fire()`, `str_slug()`, `studly_case()` заменяются вызовами Laravel 13.
- Новые файлы: конфиги phpcs, phpstan и phpunit, каталог `tests/`, `compose.yaml` и образ PHP 8.5 для WSL.
- `illuminate/routing` остаётся зависимостью хоста, который исполняет сгенерированный шаблон. Этот пакет в своём `require` его не держит.
- `openspec/specs/module-boot/spec.md` не переписывается.
