# Tasks

## 1. Отбор из кэша

- [x] 1.1 Сверить чтение существующего кэша и холодный старт с `src/Repositories/LocalRepository.php`: при живом `storage/app/modules.json` путь `enabled()` не открывает `module.json`; если файла нет, кэш строится по каталогам, поле манифеста побеждает, отсутствующий `enabled` берётся из `modules.enabled` (иначе `true`). Проверка: проследить `getCache`, `createCache`, `optimize`, `getManifest` и не менять исходники.
- [x] 1.2 Сверить отбор `enabled` и порядок `order` с `LocalRepository::enabled()`, `LocalRepository::all()` и `where`/`sortBy` в `illuminate/support` 5.3.31. `true`, число `1`, строка `"1"` и строка `"true"` входят, `false`, пустое поле, `0` и строка `"0"` не входят; меньший `order` раньше, запись без `order` раньше числового. Проверка: сравнение оператора `==` и `sortBy('order')` без правок исходников.

## 2. Регистрация отобранных

- [x] 2.1 Сверить регистрацию провайдера с `src/Modules.php` и `src/Helpers/module.php`: в обход попадают только отобранные записи; класс `{namespace}{basename}\Providers\ModuleServiceProvider` берётся из записи, найденной по `slug`; отсутствующий класс пропускается. Проверка: `register`, `registerServiceProvider`, `module_class` без правок исходников.
- [x] 2.2 Сверить `autoload`, карту путей и поля вне загрузки: существующий файл подключается, отсутствующий пропускается, карта путей меняет фрагмент строки, без `autoload` дополнительных файлов нет; `name`, `version`, `description`, `id` на класс и путь не влияют; правка кэша после старта текущий процесс не догружает. Проверка: `autoloadFiles`, `module_path` и отсутствие повторного чтения кэша после `register()` без правок исходников.

## Workflow follow-up

- Заархивировать изменение после ревью. Рантайм не выкладывается.
- Проверить, что архив положил capability в `openspec/specs/module-boot/`.
