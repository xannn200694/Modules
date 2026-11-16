# Spec Delta

## MODIFIED Requirements

### Requirement: Провайдер модуля

Для каждого отобранного модуля система SHALL зарегистрировать класс `{namespace}{basename}\Infrastructure\Providers\ModuleServiceProvider`, если он существует. Если этот класс есть, система MUST NOT регистрировать вместе с ним прежний класс. Если инфраструктурного класса нет, система SHALL зарегистрировать `{namespace}{basename}\Providers\ModuleServiceProvider`, когда тот существует. `basename` система SHALL взять из записи кэша, найденной по `slug`. `namespace` SHALL быть настроенным пространством имён модулей. Если ни одного из этих классов нет, система MUST NOT регистрировать провайдер этого модуля и SHALL перейти к остальным отобранным модулям.

#### Scenario: Класс провайдера есть

- **WHEN** у отобранного модуля существует класс `Infrastructure\Providers\ModuleServiceProvider`
- **THEN** система регистрирует этот класс и не регистрирует прежний `Providers\ModuleServiceProvider`

#### Scenario: Класса провайдера нет

- **WHEN** ни инфраструктурного класса, ни прежнего класса нет
- **THEN** система не регистрирует провайдер этого модуля и продолжает старт

#### Scenario: Есть только прежний провайдер

- **WHEN** инфраструктурного класса нет, а `Providers\ModuleServiceProvider` существует
- **THEN** система регистрирует прежний класс
