# Changelog

All notable changes to `christyoga123/vuelament` will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.6.3] - 2026-03-14

### Fixed

- **FileInput Preview (NaN MB)**: Fixed an issue where `FileInput` would display "NaN MB" in edit mode because the initial value was a string path instead of a `File` object. Added robust handling for string paths, including proper filename extraction and image preview from storage URL.
- **FileInput Storage Cleanup**: When a file is removed (set to null) or replaced during edit, the old file is now automatically deleted from the public storage disk. Previously, old files were orphaned on disk.

## [1.6.2] - 2026-03-14

### Fixed

- **FileInput Preview (NaN MB)**: Fixed an issue where `FileInput` would display "NaN MB" in edit mode because the initial value was a string path instead of a `File` object. Added robust handling for string paths, including proper filename extraction and image preview from storage.

## [1.6.1] - 2026-03-14

### Fixed

- **Resource Generator Type Mismatch**: Fixed PHP type mismatch error in generated Resource classes where `$model` and `$slug` were generated as `?string` instead of `string` (matching `BaseResource`).
- **Missing "Create" Button**: Added `getHeaderActions()` with `CreateAction` to the multi-page `ListRecords` stub. Previously, users had to manually add the button in multi-page mode.
- **FileInput Persistence**: Fixed a bug where `FileInput` successfully validated files but failed to store them to disk or save the path to the database. `ResourceController` now correctly handles `UploadedFile` instances.

### Changed

- **Toast Stacking Behavior**: Modified `AppWrapper.vue` to use the premium "piling/stacked" toast look. Toasts now overlap and expand on hover (`expand: false`).

---

## [1.6.0] - 2026-03-11

### Security

- **[CRITICAL] Register Bypass Fixed**: `AuthController::register()` now calls `resolveUserAccess()` — the same access control check used by `login()`. Previously, any newly registered user was immediately logged into the panel without `hasPanelAccess()` / `canAccessPanel()` being checked, bypassing all panel authorization.
- **[HIGH] Rate Limiting on Auth Routes**: Added `throttle:6,1` middleware on `POST /login` and `throttle:5,1` on `POST /register` in `PanelServiceProvider` to prevent brute-force attacks.
- **[HIGH] Sort Column Allowlist in PageController**: `$sortField` in `PageController` is now validated against a whitelist of `sortable` columns from the table schema, consistent with `ResourceController`. Prevents column name probing / info disclosure. Direction also restricted to `asc`/`desc`.
- **[HIGH] `per_page` Cap in PageController**: `per_page` parameter capped at `min 1 / max 100` in `PageController`, closing a DoS vector via unbounded query size (previously only `ResourceController` had this cap).
- **[HIGH] Bulk Action ID Validation**: `bulkDestroy()`, `bulkRestore()`, and `bulkForceDelete()` now validate `ids` as `required|array|max:500` and `ids.*` as `integer|min:1`, preventing DoS via huge ID arrays and type confusion.
- **[HIGH] Resource-Level Authorization Hooks**: All CRUD methods in `ResourceController` now call `$resource::canViewAny()`, `canCreate()`, `canView()`, `canEdit()`, `canDelete()`, `canForceDelete()`, and `canRestore()` before executing. Unauthenticated or unauthorized calls abort with `403`. Override these static methods in your Resource class for fine-grained control.
- **[MEDIUM] Authenticate Middleware Consistency**: `Authenticate` middleware now checks `hasPanelAccess()` first, then `canAccessPanel()` — matching the priority order in `AuthController::resolveUserAccess()`. Previously only `canAccessPanel()` was checked, so a User model implementing only `hasPanelAccess()` would bypass the middleware.
- **[MEDIUM] Session Fixation Fixed in `register()`**: Added `$request->session()->regenerate()` after login in `register()`. Added `regenerateToken()` to the block path in `Authenticate` middleware.
- **[MEDIUM] HasPanelAccess Default Deny in Production**: When `spatie/laravel-permission` is not installed, `HasPanelAccess::canAccessPanel()` now returns `false` in all non-local environments (previously defaulted to `true` via `App::environment('local')` which was exploitable if `APP_ENV=local` was misconfigured on production).
- **[MEDIUM] User Model Data Exposure Fixed**: All controllers (`DashboardController`, `ResourceController`, `PageController`) no longer send the full Eloquent User model to Inertia. A new `safeAuthUser()` helper exposes only `id`, `name`, `email`, `avatar`, `profile_photo_url`. Implement `toInertiaArray()` on your User model for full control.
- **[MEDIUM] SVG Upload Removed from `FileInput::image()`**: `image/svg+xml` removed from default accepted MIME types — SVG files can contain embedded `<script>` tags causing Stored XSS if served inline. Use the new explicit `->allowSvg()` method (with warning) if SVG is genuinely required.
- **[MEDIUM] Server-Side MIME Type Validation for FileInput**: `BaseForm::getValidationRules()` now generates a `mimetypes:` Laravel rule from `acceptedFileTypes`, enforcing MIME validation server-side. Previously only the frontend `accept` attribute restricted file types.
- **[MEDIUM] Octane Race Condition Fixed in ResourceRouteController**: Replaced static property `$resource` with an instance property `$resourceInstance` in `ResourceRouteController`. Override `getResourceClass()` returns the instance property, making the controller safe for concurrent requests under Laravel Octane (Swoole / RoadRunner).
- **[LOW] `v-html` Removed from TablePagination**: Replaced `v-html="link.label"` with a safe `decodeLabel()` function that explicitly maps known HTML entities (`&laquo;`, `&raquo;`, `&amp;`, etc.) to their Unicode equivalents.
- **[LOW] `bcrypt()` Replaced with `Hash::make()`**: `AuthController::register()` now uses `Hash::make()` which respects the configured `config('hashing.driver')` (bcrypt, argon2i, argon2id).
- **[LOW] CLI Password Warning**: `vuelament:user --password` now emits a visible warning and a `Log::warning()` entry reminding that CLI arguments are stored in shell history.
- **[LOW] Security Meta Tags in Blade Template**: Added `csrf-token`, `referrer` policy, `X-Content-Type-Options`, and `Cache-Control` meta tags to `app.blade.php`. Added inline documentation for required HTTP-level security headers (`X-Frame-Options`, `Permissions-Policy`, etc.).

### Added

- **`BaseResource::can*()` Authorization Hooks**: Six new overridable static methods on `BaseResource`: `canViewAny()`, `canCreate()`, `canView($record)`, `canEdit($record)`, `canDelete($record)`, `canForceDelete($record)`, `canRestore($record)`. All default to `true` (backward-compatible). Override in your Resource class to implement role/policy-based access control.
- **`FileInput::allowSvg()`**: New explicit opt-in method to re-enable SVG uploads with a PHPDoc warning about XSS risk.
- **`safeAuthUser()` helper**: Shared protected method in `DashboardController`, `ResourceController` (trait), and `PageController` that returns only safe user fields to Inertia. Supports a `toInertiaArray()` hook on the User model for full developer control.
- **`resolveUserAccess()` helper**: Extracted into `AuthController` as a protected method, shared between `login()` and `register()` for consistent panel access resolution.
- **`getResourceClass()` method in ResourceController trait**: All internal method calls now use `$this->getResourceClass()` instead of `static::$resource` directly, enabling `ResourceRouteController` to safely override it with an instance property.

### Changed

- `AuthController::login()` and `register()` now share the same `resolveUserAccess()` logic.
- `Authenticate` middleware now checks both `hasPanelAccess()` and `canAccessPanel()`.
- `HasPanelAccess` trait now logs a `Log::warning()` in local env and `Log::error()` in production when `spatie/laravel-permission` is absent.

---

## [1.5.1] - 2026-03-08

### Changed

- **DB Transactions enabled by default**: `databaseTransactions` now defaults to `true` on Panel, ensuring data integrity across all CRUD operations.

### Improved

- **DRY Backend**: Extracted `resolveTable()` and `resolveFormSchema()` helper methods, eliminating 7 duplicated table/form resolution patterns in ResourceController.
- **Icon Memoization**: `resolveIcon()` in DashboardLayout now caches resolved icon components, avoiding repeated regex + lookup on every render.
- **Error Handling**: All destructive table operations (delete, restore, force-delete, bulk actions, custom actions) now have `onError` callbacks that show a toast notification instead of failing silently.
- **Manage Modal Errors**: Replaced direct computed `errors` mutation with local `formErrors` ref + proper `onError`/`onSuccess` callbacks. Added double-submit guard.
- **File Size Validation**: FormRenderer now validates file size client-side before processing. Configurable via `maxSize` prop (defaults to 10MB).
- **Interval Cleanup**: `setInterval` timers for non-image file progress simulation are now tracked and cleared on component unmount.

---

## [1.5.0] - 2026-03-08

### Security

- **SQL Injection Prevention**: Sort parameter now validated against allowed sortable columns; `direction` restricted to `asc`/`desc` only.
- **Arbitrary Column Update**: `updateColumn()` now validates column name against registered toggle columns only, preventing unauthorized column writes.
- **Mass Assignment Protection**: Replaced unsafe `$request->all()` fallback with `$request->only($fieldNames)`, extracting only form-defined fields.

### Fixed

- **Form State Preservation**: Added `preserveState: true` to Create.vue, Edit.vue, and custom action submissions so user input is preserved on validation errors.
- **FormRenderer Key Performance**: Replaced `Math.random()` in `:key` binding with deterministic index-based keys, preventing full DOM recreation on every render.
- **Memory Leaks**: `router.on('finish')` listener cleaned up on unmount in AppWrapper; search debounce timeout cleared in useTableState.
- **Action Error Handling**: `executeAction()` now wraps callback in try/catch, returning flash error instead of 500 page.
- **Table State Preservation**: Added `preserveState: true` to custom actions, bulk actions, and delete/restore operations.
- **DoS Prevention**: `per_page` parameter capped to maximum 100 records.

---

## [1.4.1] - 2026-03-08

### Changed

- **Cursor Pointer**: All interactive elements (buttons, action triggers) now display `cursor: pointer` via a global CSS rule injected during installation.
- **Localization**: Translated all remaining Indonesian text in PHP classes, Vue components, stubs, and docblocks to English for wider accessibility. Affected files include `ActionGroup`, `BaseAction`, `BaseTableAction`, `RestoreAction`, `RestoreBulkAction`, `ResourceController`, `MakeResourceCommand`, `MakePageCommand`, `PageController`, `BasePage`, `Panel`, `NavigationGroup`, `HasPanelAccess`, `BaseForm`, table composables, and all stubs.

---

## [1.4.0] - 2026-03-08

### Added

- **Notification Component**: New `Notification::make()` fluent PHP API for sending toast notifications from backend services and business logic. Supports `->success()`, `->info()`, `->danger()`, `->warning()` types with `->title()` and `->body()` methods. Call `->send()` to flash to session.
- **Toast Stacking**: Toasts now stack when multiple arrive quickly. Maximum 2 visible at a time; older toasts fade out automatically. Duration set to 4 seconds.
- **Structured Notifications via Inertia**: `VuelamentServiceProvider` now shares a `notifications` array prop alongside `flash`, enabling multiple notifications per request.

### Fixed

- **SoftDeletes Guard**: `executeAction()` no longer unconditionally calls `withTrashed()`. Now checks if the model uses `SoftDeletes` trait first, preventing `BadMethodCallException` on models without soft deletes.
- **SoftDelete Route Guards**: `restore()`, `forceDelete()`, `bulkRestore()`, and `bulkForceDelete()` now return a friendly error instead of crashing when the model does not use `SoftDeletes`.
- **Custom Notification Priority**: When an action uses `Notification::make()->send()`, the default success flash message is suppressed to avoid duplicate toasts.

---

## [1.3.7] - 2026-03-08

### Fixed

- **Manage Modal Validation Error**: Fixed a bug where the create/edit modal in `Manage.vue` would briefly flash a white border and close when encountering a 4xx validation error. Added `preserveState: true` to Inertia router calls so Vue component state (modal open/close) is preserved during redirect-back on validation errors.

---

## [1.3.6] - 2026-03-08

### Fixed

- **Toast Notifications**: Replaced unreliable `watch(flash)` with `router.on('finish')` in `AppWrapper.vue` to reliably trigger Sonner toast notifications after any Inertia request completes.
- **Manage Records Stub**: Added missing `CreateAction` header action to `manage-records.stub`.

### Added

- **Sonner CSS Auto-Injection**: The `vuelament:install` command now automatically injects required Sonner/Toast CSS styles into `app.css`, fixing Tailwind CSS v4 Preflight compatibility issues where toasts rendered as unstyled inline text.

---

## [1.3.5] - 2026-03-07

### Changed

- **Resource Generator**: Updated `vuelament:resource` command and stub to define the resource `$model` property using a class reference (e.g., `protected static ?string $model = User::class;`) instead of a fully-qualified string namespace.

---

## [1.3.4] - 2026-03-07

### Fixed

- **Toast Notifications**: Added `flash` sharing (`success`, `error`, `warning`, `info`) parameters to `VuelamentServiceProvider`, fixing an issue where action notifications were not displaying due to missing props injections on the client-side.

---

## [1.3.3] - 2026-03-07

### Fixed

- **Manage Mode Rendering**: Fixed an issue where `ManageRecords` simple mode did not pass the `formSchema` to the frontend, causing empty modal forms.
- **Inertia Redirects**: Fixed `MethodNotAllowedHttpException` by ensuring all mutation methods (store, update, destroy, bulk actions) safely return a `303 See Other` HTTP status code instead of a `302 Found`, properly instructing Inertia to issue a `GET` request on redirection.

---

## [1.3.2] - 2026-03-07

### Fixed

- **Fixed remaining `tableSchema()` calls** inside `ResourceController.php` related to `executeAction`, `executeBulkAction`, and `applySearch`.

---

## [1.3.1] - 2026-03-07

### Fixed

- **Fixed `index` method** in `ResourceController` incorrectly calling `tableSchema()` when using the new builder structure.

---

## [1.3.0] - 2026-03-07

### Changed

- Refactored `BaseResource` to use `Table` and `Form` builders instead of `PageSchema`.
- Updated `MakeResourceCommand` logic to generate components based on the new builder configuration.
- Changed default string to 'Bulk Actions'.

---

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
