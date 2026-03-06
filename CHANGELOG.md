# Changelog

All notable changes to `christyoga123/vuelament` will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.2.3] - 2026-03-06

### Changed

- **Install Command**: Reverted the `vuelament:install` behavior to no longer run automatic `npm install` and `shadcn-vue` installations. Experience showed that automated UI component installation is too prone to failing silently depending on users' TypeScript and Node environments. The command now purely scaffolds the Vuelament PHP core, Vue stubs, jsconfig, panel configs, and Vite/App entry points. Users will be directed to manually run Shadcn-Vue installation.
- **Documentation**: Updated `README.md` to reflect the new workflow where scaffolding is automated, but NPM dependencies and Shadcn initialization are done manually.

---

## [1.2.2] - 2026-03-06

### Changed

- **Documentation**: Updated `README.md` to recommend and document manual Shadcn-Vue installation as the best practice, bypassing the error-prone automated frontend installation.

---

## [1.2.1] - 2026-03-06

### Added

- **Fully automated `vuelament:install`**: The install command now handles the entire frontend setup automatically:
  - Installs all required NPM dependencies (Inertia, Vue, Shadcn-Vue, etc.).
  - Initializes Shadcn-Vue (`npx shadcn-vue@latest init`).
  - Installs all required UI components (including Sidebar) with automatic TypeScript toggle workaround.
  - Scaffolds `jsconfig.json` to prevent the `✖ Validating import alias` error.
  - Users only need to run `php artisan vuelament:install` — no manual NPM or Shadcn-Vue commands needed.

### Fixed

- **Shadcn-Vue Sidebar install error**: Automatically toggles `typescript: true` in `components.json` before installing components (workaround for `[@vue/compiler-sfc] Failed to resolve import source "."` bug), then reverts to `false` after installation.
- **Shadcn-Vue import alias validation error**: Automatically scaffolds `jsconfig.json` with `@/*` path alias when neither `jsconfig.json` nor `tsconfig.json` exists.

---

## [1.2.0] - 2026-03-06

### Added

- **Central Panel Registry (`Vuelament`)**: A singleton class acting as the central registry for all panels. Enables a sustainable multi-panel architecture (e.g., Admin, Sales, User, etc.).
- **Dynamic Panel Resolution**: `app('vuelament.panel')` is now resolved dynamically based on the current context:
  - **HTTP**: Auto-detects from the URL path prefix (using longest prefix match).
  - **CLI**: Prompts interactively using `choice()` or reads from the `--panel=` option.
  - **Fallback**: Uses the default panel from `config('vuelament.default_panel')`.
- **CLI Panel Option for `vuelament:user`**: The Artisan command now supports explicit panel selection (`php artisan vuelament:user --panel=admin`). If there are multiple panels and the flag is omitted, an interactive chooser will prompt the user.
- **Facade Helpers**: Added `V::registry()`, `V::currentPanel()`, and `V::getPanel($id)` for ergonomic multi-panel access.

### Fixed

- **`BindingResolutionException: Target class [vuelament.panel] does not exist`**: Previously, panels were only bound using a specific key (`vuelament.panel.{id}`), causing all generic internal calls to `app('vuelament.panel')` to fail. Now, `'vuelament.panel'` is mapped dynamically through the registry.

### Changed

- **Panel Registration**: `PanelServiceProvider::register()` now registers the panel to the central `Vuelament` registry, replacing the reliance on only individual singleton bindings.
- **Global Setup**: `VuelamentServiceProvider::register()` now initializes the central registry singleton (`'vuelament'`) and dynamic panel binding (`'vuelament.panel'`).

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
