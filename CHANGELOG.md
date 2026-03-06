# Changelog

All notable changes to `christyoga123/vuelament` will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.0] - 2026-03-06

### Added

- **Core Architecture**: Panel-first, module-based MVC structure (Resources, Pages, Services, Widgets).
- **Service Providers**: Added `VuelamentServiceProvider` for framework auto-discovery and `PanelServiceProvider` for user panel configuration.
- **Generic CRUD Router**: `ResourceRouteController` to eliminate the need for per-model controllers.
- **Action & Service System**: Extensible actions with modal forms and pure PHP business logic execution (`app()->call()`).
- **Form Builder**:
  - Generic components: `TextInput`, `Textarea`, `RichEditor`, `DatePicker`, `DateRangePicker`, `TimePicker`, `Select`, `Radio`, `Checkbox`, `Toggle`, `FileInput`, `Repeater`.
  - Layout components: `Card`, `Section`, `Grid`.
  - Client-side reactivity: `visibleWhen`, `hiddenWhen`, `disabledWhen`, `enabledWhen`, `requiredWhen`.
- **Table Builder**:
  - Columns: `TextColumn`, `ImageColumn`, `IconColumn`, `ToggleColumn`, `CheckboxColumn`, `BadgeCell`.
  - Actions: Inline row actions (`EditAction`, `DeleteAction`, `RestoreAction`, `ForceDeleteAction`).
  - Bulk Actions: `DeleteBulkAction`, `RestoreBulkAction`, `ForceDeleteBulkAction`.
  - Features: Searchable, selectable, sortable, paginated with dynamic filters.
- **Frontend App**: VILT stack integration using Vue 3, Inertia.js, Tailwind CSS (v4), and Shadcn-Vue.
- **Artisan Commands**:
  - `vuelament:install` to publish config, assets, and scaffold `vite.config.js` / `app.js`.
  - `vuelament:panel` to generate new panels (multi-panel support).
  - `vuelament:resource` to scaffold full CRUD modules.
  - `vuelament:page` to scaffold custom Vue/PHP pages.
  - `vuelament:service` to scaffold business logic services.
  - `vuelament:user` to seed admin users.
- **Stubs System**: Customizable `.stub` files for code generation.

### Changed

- Converted application-level codebase into an installable Composer package.

### Security

- Integrated Laravel native Authentication and Authorization endpoints into panel scopes.
