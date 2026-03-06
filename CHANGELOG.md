# Changelog

All notable changes to `christyoga123/vuelament` will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.1.0] - 2026-03-06

### Added

- **Central Panel Registry (`Vuelament`)**: Singleton class yang menjadi registry pusat untuk semua panel. Mendukung arsitektur multi-panel (Admin, Sales, User, dsb.) secara sustainable.
- **Dynamic Panel Resolution**: `app('vuelament.panel')` sekarang di-resolve secara dinamis berdasarkan konteks:
  - **HTTP**: Auto-detect dari URL path prefix (longest prefix match).
  - **CLI**: Dari opsi `--panel=` atau interactive `choice()` prompt.
  - **Fallback**: Default panel dari `config('vuelament.default_panel')`.
- **`--panel=` option di `vuelament:user`**: Artisan command sekarang mendukung panel selection eksplisit (`php artisan vuelament:user --panel=admin`). Jika multi-panel dan flag tidak diberikan, muncul interactive chooser.
- **Facade helpers**: `V::registry()`, `V::currentPanel()`, `V::getPanel($id)` untuk akses multi-panel yang ergonomis.

### Fixed

- **`BindingResolutionException: Target class [vuelament.panel] does not exist`**: Panel sebelumnya hanya di-bind sebagai `vuelament.panel.{id}` (spesifik), tapi semua internal call ke `app('vuelament.panel')` (generik). Sekarang `'vuelament.panel'` di-bind dinamis melalui registry.

### Changed

- `PanelServiceProvider::register()` sekarang mendaftarkan panel ke central `Vuelament` registry, bukan hanya individual singleton binding.
- `VuelamentServiceProvider::register()` sekarang menginisialisasi registry singleton (`'vuelament'`) dan dynamic binding (`'vuelament.panel'`).

---

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
