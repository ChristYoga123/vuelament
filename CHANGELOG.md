# Changelog

All notable changes to `christyoga123/vuelament` will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.2.9] - 2026-03-07

### Added

- **Manage Mode Vue Page**: Added `Manage.vue` component that handles simple mode resources for managing standard CRUD inside a modal.

### Fixed

- **Resource Generator (`--simple` flag)**: Fixed a bug where creating a resource using the `--simple` flag failed due to missing string replacements in the `resource.stub` file.
- **Resource Generator (`--simple` flag)**: Fixed a bug where the generated `Manage[Model]` page class did not properly append the `CreateAction` logic.
- **Manage Mode Rendering**: Fixed an issue where the `ResourceController` forced rendering `Index.vue` even when in simple/manage mode, preventing `Manage.vue` from loading.
- **Table Components**: Fixed `Table` and `TableRowActions` components to correctly emit `@createAction` and `@editAction` events instead of forcing router navigation when `isManageMode` is true.

---

## [1.2.8] - 2026-03-06

### Changed

- **Panel Access Simulator**: `hasPanelAccess()` authorization default check now falls back to `true` on the `local` environment allowing rapid development prototyping. On production, it securely falls back to `false` ensuring non-configured setups deny access.
- **Localization**: Changed "Email atau password salah." to "Invalid email or password." in `AuthController`.

---

## [1.2.7] - 2026-03-06

### Changed

- **Localization**: Translated remaining hardcoded Indonesian texts in Vue components (e.g., table pagination, toast notifications, login auth, boolean flags in utils) into English for wider accessibility.

---

## [1.2.5] - 2026-03-06

### Added

- **Inertia Error Sharing**: Added automatic global sharing of session errors to Inertia via `VuelamentServiceProvider`. This fixes an issue where validation/login errors were not displaying on the frontend since Vuelament does not mandate a `HandleInertiaRequests` middleware.
- **Documentation**: Added documentation specifying how users can restrict panel access using the `hasPanelAccess()` method in their `User` model.

### Changed

- **Panel Authorization**: `AuthController` now returns `You cannot access this panel.` as the generic error when `hasPanelAccess` returns false, and prioritizes checking `hasPanelAccess` over `canAccessPanel`.

---

## [1.2.4] - 2026-03-06

### Added

- **Documentation**: Added instructions to install `typescript` and `vue-tsc` as dev-dependencies to resolve `[@vue/compiler-sfc] Failed to load TypeScript` issues for users opting into `typescript: true` during Shadcn-Vue installation.
- **Install Command**: Updated post-install terminal instructions to output the typescript / vue-tsc dependencies.

---

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

## [1.3.0] - 2026-03-07

### Changed

- Refactored `BaseResource` to use `Table` and `Form` builders instead of `PageSchema`.
- Updated `MakeResourceCommand` logic to generate components based on the new builder configuration.
- Changed default string to 'Bulk Actions'.

## [1.3.1] - 2026-03-07

### Fixed

- Fixed `index` method in `ResourceController` incorrectly calling `tableSchema()` when using the new builder structure.

## [1.3.2] - 2026-03-07

### Fixed

- Fixed remaining `tableSchema()` calls inside `ResourceController.php` related to `executeAction`, `executeBulkAction`, and `applySearch`.

## [1.3.3] - 2026-03-07

### Fixed

- **Manage Mode Rendering**: Fixed an issue where `ManageRecords` simple mode did not pass the `formSchema` to the frontend, causing empty modal forms.
- **Inertia Redirects**: Fixed `MethodNotAllowedHttpException` by ensuring all mutation methods (store, update, destroy, bulk actions) safely return a `303 See Other` HTTP status code instead of a `302 Found`, properly instructing Inertia to issue a `GET` request on redirection.

## [1.3.4] - 2026-03-07

### Fixed

- **Toast Notifications**: Added `flash` sharing (`success`, `error`, `warning`, `info`) parameters to `VuelamentServiceProvider`, fixing an issue where action notifications were not displaying due to missing props injections on the client-side.

## [1.4.1] - 2026-03-08

### Changed

- **Cursor Pointer**: All interactive elements (buttons, action triggers) now display `cursor: pointer` via a global CSS rule injected during installation.
- **Localization**: Translated all remaining Indonesian text in PHP classes, Vue components, stubs, and docblocks to English for wider accessibility. Affected files include `ActionGroup`, `BaseAction`, `BaseTableAction`, `RestoreAction`, `RestoreBulkAction`, `ResourceController`, `MakeResourceCommand`, `MakePageCommand`, `PageController`, `BasePage`, `Panel`, `NavigationGroup`, `HasPanelAccess`, `BaseForm`, table composables, and all stubs.

## [1.4.0] - 2026-03-08

### Added

- **Notification Component**: New `Notification::make()` fluent PHP API for sending toast notifications from backend services and business logic. Supports `->success()`, `->info()`, `->danger()`, `->warning()` types with `->title()` and `->body()` methods. Call `->send()` to flash to session.
- **Toast Stacking**: Toasts now stack when multiple arrive quickly. Maximum 2 visible at a time; older toasts fade out automatically. Duration set to 4 seconds.
- **Structured Notifications via Inertia**: `VuelamentServiceProvider` now shares a `notifications` array prop alongside `flash`, enabling multiple notifications per request.

### Fixed

- **SoftDeletes Guard**: `executeAction()` no longer unconditionally calls `withTrashed()`. Now checks if the model uses `SoftDeletes` trait first, preventing `BadMethodCallException` on models without soft deletes.
- **SoftDelete Route Guards**: `restore()`, `forceDelete()`, `bulkRestore()`, and `bulkForceDelete()` now return a friendly error instead of crashing when the model does not use `SoftDeletes`.
- **Custom Notification Priority**: When an action uses `Notification::make()->send()`, the default success flash message is suppressed to avoid duplicate toasts.

## [1.3.7] - 2026-03-08

### Fixed

- **Manage Modal Validation Error**: Fixed a bug where the create/edit modal in `Manage.vue` would briefly flash a white border and close when encountering a 4xx validation error. Added `preserveState: true` to Inertia router calls so Vue component state (modal open/close) is preserved during redirect-back on validation errors.

## [1.3.6] - 2026-03-08

### Fixed

- **Toast Notifications**: Replaced unreliable `watch(flash)` with `router.on('finish')` in `AppWrapper.vue` to reliably trigger Sonner toast notifications after any Inertia request completes.
- **Manage Records Stub**: Added missing `CreateAction` header action to `manage-records.stub`.

### Added

- **Sonner CSS Auto-Injection**: The `vuelament:install` command now automatically injects required Sonner/Toast CSS styles into `app.css`, fixing Tailwind CSS v4 Preflight compatibility issues where toasts rendered as unstyled inline text.

## [1.3.5] - 2026-03-07

### Changed

- **Resource Generator**: Updated `vuelament:resource` command and stub to define the resource `$model` property using a class reference (e.g., `protected static ?string $model = User::class;`) instead of a fully-qualified string namespace.
