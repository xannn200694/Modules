# Proposal

## Why

У пакета нет спецификации загрузки модулей, а README описывает другой пакет. При разборе кода решено, что контракт старта — кэш `storage/app/modules.json` в том виде, в котором его читает boot. Это нужно зафиксировать до любых следующих изменений, чтобы отличать текущее поведение от бага.

## What Changes

- Вводится capability `module-boot`: какие записи кэша boot поднимает и какие поля записи для этого использует.
- Код пакета, команды Artisan и формат `module.json` этим изменением не меняются.
- Поведение рантайма не меняется. Ломающих изменений нет.

## Capabilities

### New Capabilities

- `module-boot`: загрузка включённых модулей из кэша при boot приложения. Capability описывает отбор записей, порядок и регистрацию провайдера и файлов `autoload`. Сборка кэша командой `module:optimize` и правки `module:enable` / `module:disable` остаются способом получить этот кэш, а не отдельным контрактом boot.

### Modified Capabilities

Нет. В `openspec/specs/` ещё нет capabilities.

## Impact

- Спецификация описывает текущий путь `ModulesServiceProvider::boot()` → `Modules::register()` и чтение `storage/app/modules.json`.
- Затронутое чтение: `src/Modules.php`, `src/Repositories/LocalRepository.php`, `src/Helpers/module.php`. Исходники в этом изменении не правятся.
- `module.json` в контракт boot не входит: его читает `optimize`, когда пересобирает кэш.
