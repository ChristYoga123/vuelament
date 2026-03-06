<div align="center">
    <h1>🚀 Vuelament</h1>
    <p>A lightning-fast, lightweight, and modern Admin Dashboard & CRUD Generator for Laravel.<br> Built on the VILT stack (Vue 3, Inertia.js, Laravel, Tailwind CSS) and powered by Shadcn Vue for a gorgeous UI.</p>

[![Latest Version on Packagist](https://img.shields.io/packagist/v/christyoga123/vuelament.svg?style=flat-square)](https://packagist.org/packages/christyoga123/vuelament)
[![License](https://img.shields.io/packagist/l/christyoga123/vuelament.svg?style=flat-square)](LICENSE)
[![PHP Version](https://img.shields.io/packagist/php-v/christyoga123/vuelament.svg?style=flat-square)](https://packagist.org/packages/christyoga123/vuelament)

</div>

---

## ✨ Features

- **Ultra Lightweight**: Minimal dependencies, split code-chunking, and no bloat.
- **Beautiful UI**: Uses Tailwind CSS v4 and Shadcn-Vue for a premium, accessible, and easily customizable design.
- **VILT Stack**: Seamless SPA experience powered by Laravel, Inertia, Vue 3, and Tailwind.
- **Filament-Inspired Architecture**: Panel-first, module-based structure with page classes, header actions, and service-based action handling.
- **No Per-Model Controllers**: Generic `ResourceRouteController` handles all CRUD routing automatically — zero boilerplate.
- **Complete Form Builder**: Supports Rich Editor (Vue Quill), Date/Time Pickers (VueDatePicker), File Uploads, Selects, Toggles, Checkboxes, Radios, and responsive Grid Layouts.
- **Client-Side Form Reactivity**: Show/hide, enable/disable, and change required state of fields **instantly without server requests**.
- **Robust Table Builder**: Supports filtering, search, pagination, bulk actions, column toggling, and rich column types (Text, Badge, Toggle, Checkbox, Image, Icon).
- **Service-Based Actions**: Business logic lives in dedicated Service classes — testable, reusable, and separated from framework concerns.
- **Dynamic Modals**: Conditional Action dialogs, flexible modal widths, click-away handling, and dangerous action confirmations.
- **Multi-Panel Support**: Run multiple isolated admin panels (Admin, Sales, etc.) with zero conflict.

## 📦 Requirements

- **PHP** 8.2+
- **Laravel** 11.x / 12.x
- **Node.js** 18+ & NPM

---

## 🛠️ Installation

### 1. Install via Composer

```bash
composer require christyoga123/vuelament
```

### 2. Run the Install Command

```bash
php artisan vuelament:install
```

This will automatically:

- Publish the config file (`config/vuelament.php`)
- Publish Vue/JS components and layouts
- Generate the `AdminPanelProvider` and register it in `bootstrap/providers.php`
- Scaffold `app.js` and `vite.config.js` for Inertia + Vue
- Scaffold `jsconfig.json` (required by Shadcn-Vue)
- Install all NPM dependencies
- Initialize Shadcn-Vue and install all required UI components

### 3. Run Migrations & Create Admin User

```bash
php artisan migrate
php artisan vuelament:user
```

### 4. Start Dev Server

```bash
npm run dev
php artisan serve
```

Visit **`http://localhost:8000/admin/login`** to access the admin panel.

---

## 📁 Architecture — Panel-First, Module-Based

```
app/Vuelament/{Panel}/{Model}/
    ├── Resources/              ← CRUD page classes (like Filament)
    │    ├── List{Model}s.php        → getHeaderActions(): [CreateAction::make()]
    │    ├── Create{Model}.php       → getHeaderActions(), getFormActions()
    │    └── Edit{Model}.php         → getHeaderActions(), getFormActions()
    │
    ├── Pages/                  ← Custom pages (non-CRUD)
    │
    ├── Services/               ← Business logic (service-based actions)
    │    └── {Model}Service.php
    │
    ├── Widgets/                ← Dashboard widgets
    │
    └── {Model}Resource.php     ← Form schema, table schema, getPages()
```

> **No per-model controller needed!** The framework's generic `ResourceRouteController` handles all CRUD routing automatically. You only need Resource + Page classes + Services.

---

## 🧩 Artisan Commands

### Generate a Resource (full module)

```bash
# Multi-page mode (default) — generates List, Create, Edit pages
php artisan vuelament:resource Product --panel=Admin

# Single mode — modal-based create/edit (ManageProducts)
php artisan vuelament:resource Product --panel=Admin --simple

# Auto-generate form/table from database columns
php artisan vuelament:resource Product --panel=Admin --generate
```

This generates the full module structure:

```
app/Vuelament/Admin/Product/
    ├── Resources/
    │    ├── ListProducts.php       ← with CreateAction in getHeaderActions()
    │    ├── CreateProduct.php
    │    └── EditProduct.php
    ├── Pages/
    ├── Services/
    │    └── ProductService.php
    ├── Widgets/
    └── ProductResource.php         ← with getPages() referencing page classes
```

### Generate a Service

```bash
# Basic — creates ProductService in the Product module
php artisan vuelament:service Product --panel=Admin

# With explicit resource module
php artisan vuelament:service Payment --panel=Admin --resource=Order
```

### Generate a Custom Page

```bash
# Standalone page
php artisan vuelament:page Analytics --panel=Admin

# Page attached to a resource
php artisan vuelament:page Report --panel=Admin --resource=User
```

### Generate a Panel

```bash
php artisan vuelament:panel Sales --id=sales
```

### Create Admin User

```bash
php artisan vuelament:user
```

---

## 🚀 Usage Guide

### Page Classes — Like Filament

Page classes define **page-level actions** via `getHeaderActions()`. This is identical to Filament's pattern.

```php
// app/Vuelament/Admin/User/Resources/ListUsers.php
use ChristYoga123\Vuelament\Core\Pages\ListRecords;
use ChristYoga123\Vuelament\Components\Actions\CreateAction;

class ListUsers extends ListRecords
{
    protected static ?string $resource = UserResource::class;

    public static function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
            // ExportAction::make(),
            // ImportAction::make(),
        ];
    }
}
```

### Resource Registration — getPages()

Resources register their CRUD page classes and custom pages in `getPages()`:

```php
public static function getPages(): array
{
    return [
        // CRUD page classes — framework reads getHeaderActions() from these
        'index'  => Resources\ListUsers::class,
        'create' => Resources\CreateUser::class,
        'edit'   => Resources\EditUser::class,

        // Custom pages
        'report' => Pages\ReportPage::route('/{record}/report'),
    ];
}
```

### Table Schema — Row-Level Actions Only

Table schemas contain only **row-level inline actions**. Page-level actions (like "Create") belong in `getHeaderActions()` on the page class.

```php
public static function tableSchema(): PageSchema
{
    return PageSchema::make()
        ->components([
            Table::make()
                ->query(fn() => User::query()->latest())
                ->columns([
                    TextColumn::make('name')->searchable()->sortable(),
                    TextColumn::make('email')->sortable()->searchable(),
                    ToggleColumn::make('is_active')->label('Active'),
                ])
                ->actions([
                    // ✅ Row-level actions (inline in table)
                    Action::make('deactivate')
                        ->icon('user-x')
                        ->color('warning')
                        ->requiresConfirmation('Deactivate User', 'Are you sure?')
                        ->action([UserService::class, 'deactivate']),
                    EditAction::make(),
                    DeleteAction::make(),
                ])
                ->bulkActions([
                    ActionGroup::make('Bulk Actions')
                        ->icon('list')
                        ->actions([
                            DeleteBulkAction::make(),
                            RestoreBulkAction::make(),
                        ]),
                ])
                ->filters([
                    TrashFilter::make(),
                ])
                ->searchable()
                ->paginated()
                ->selectable(),
        ]);
}
```

### Defining Forms

```php
use ChristYoga123\Vuelament\Components\Layout\Grid;
use ChristYoga123\Vuelament\Components\Layout\Section;
use ChristYoga123\Vuelament\Components\Form\TextInput;
use ChristYoga123\Vuelament\Components\Form\Toggle;

public static function formSchema(): PageSchema
{
    return PageSchema::make()
        ->components([
            Section::make('General Information')
                ->components([
                    Grid::make(2)->components([
                        TextInput::make('name')->required()->uniqueIgnoreRecord(),
                        TextInput::make('email')->email()->required()->uniqueIgnoreRecord(),
                    ]),
                    Toggle::make('is_active')
                        ->label('Status Active')
                        ->hint('Turn off to prevent user login.'),
                    TextInput::make('password')
                        ->password()
                        ->revealable()
                        ->dehydrateStateUsing(fn (string $state): string => Hash::make($state))
                        ->saved(fn (?string $state): bool => filled($state))
                        ->required(fn (string $operation): bool => $operation === 'create'),
                ])
        ]);
}
```

#### Available Form Controls

- `TextInput` — text, email, password, number, with prefix/suffix and revealable support
- `Textarea` — multi-line text input
- `RichEditor` — Rich text editor (powered by VueQuill)
- `DatePicker`, `TimePicker`, `DateRangePicker` — Date/time selection (powered by VuePic)
- `Select` — Dropdown selection
- `Checkbox` — Single or multiple checkbox group
- `Radio` — Radio button group with horizontal/vertical layout
- `Toggle` — Switch toggle (powered by Shadcn Switch)
- `FileInput` — File upload with drag & drop, preview, and reorder support
- `Repeater` — Dynamic repeatable field groups

---

## ⚡ Service-Based Actions

Unlike Filament (where actions use Livewire Closures), Vuelament uses **dedicated Service classes** for business logic. This makes actions testable, reusable, and framework-agnostic.

### Flow

```
Resource   →  defines actions with form + service callable
   ↓
Action     →  captures form data, resolves service from container
   ↓
Service    →  executes business logic (pure PHP, testable)
   ↓
Database   →  Eloquent operations
```

### Action in Resource

```php
Action::make('deactivate')
    ->icon('user-x')
    ->color('warning')
    ->requiresConfirmation('Deactivate User', 'Are you sure?')
    ->action([UserService::class, 'deactivate'])
```

### Service Class

```php
// app/Vuelament/Admin/User/Services/UserService.php
class UserService
{
    public function deactivate(User $user, array $data = []): void
    {
        $user->update(['is_active' => false]);
    }
}
```

### How It Works

The framework resolves the service instance from the container and injects dependencies:

```php
$instance = app(UserService::class);

app()->call([$instance, 'deactivate'], [
    'user'   => $record,    // injected by lowercase model basename
    'record' => $record,    // also available as generic 'record'
    'data'   => $formData,  // form data from modal
]);
```

### Actions with Forms

```php
Action::make('assign_role')
    ->icon('shield')
    ->color('success')
    ->form([
        Select::make('role')->options([
            'admin' => 'Admin',
            'editor' => 'Editor',
        ])->required(),
    ])
    ->action([UserService::class, 'assignRole'])
```

---

## 🎯 Form Reactivity (Client-Side)

Vuelament evaluates form visibility, disabled, and required states **entirely on the client** — no server requests needed.

### ⚔️ Vuelament vs Filament

| Interaction                                  | Filament (Livewire)                      | Vuelament (Vue 3 + Inertia)        |
| -------------------------------------------- | ---------------------------------------- | ---------------------------------- |
| **Toggle field to show another**             | 🛑 Network request, re-renders component | ⚡ **Instant**. JavaScript only    |
| **Change required state based on selection** | 🛑 Requires network request              | ⚡ **Instant**. No server overhead |
| **Simple validation visibility**             | 🛑 Network round-trip, can feel sluggish | ⚡ **Instant**. Zero latency       |

```php
Toggle::make('is_active')->label('Active Status'),

// Shows INSTANTLY when is_active is toggled on (no server request!)
TextInput::make('activation_code')
    ->visibleWhen('is_active', true)
    ->requiredWhen('is_active', true),

Select::make('type')->options([
    'standard' => 'Standard',
    'premium'  => 'Premium',
]),

// Shows only when type = 'premium'
TextInput::make('premium_code')
    ->visibleWhen('type', 'premium'),
```

#### Available Reactivity Methods

| Method                           | Description                                |
| -------------------------------- | ------------------------------------------ |
| `->visibleWhen('field', value)`  | Show when field equals value               |
| `->hiddenWhen('field', value)`   | Hide when field equals value               |
| `->disabledWhen('field', value)` | Disable when field equals value            |
| `->enabledWhen('field', value)`  | Enable when field equals value             |
| `->requiredWhen('field', value)` | Required when field equals value           |
| `->visibleWhenAll([...])`        | Show when ALL conditions match (AND logic) |
| `->visibleWhenAny([...])`        | Show when ANY condition matches (OR logic) |

#### Supported Operators

`===`, `!==`, `in`, `notIn`, `filled`, `blank`, `>`, `<`, `>=`, `<=`

---

## ⚡ Custom Actions & Modals

```php
use ChristYoga123\Vuelament\Components\Table\Actions\Action;

Action::make('form')
    ->icon('form')
    ->color('success')
    ->label('Fill Form')
    ->modalHeading('Detailed User Form')
    ->modalWidth('4xl')
    ->modalCloseByClickingAway(false)
    ->modalCancelActionLabel('Close')
    ->form([
        TextInput::make('reference_id')->required(),
        Radio::make('type')->options([
            'a' => 'Type A',
            'b' => 'Type B',
        ])
    ])
    ->action([SomeService::class, 'processForm'])
```

### Action Configuration Methods

- `->requiresConfirmation()`: Auto-converts the form into a danger Action/Alert Dialog.
- `->modalWidth('2xl')`: Defines the modal width dynamically.
- `->modalCancelAction(false)` / `->modalSubmitAction(false)`: Hide specific modal buttons.
- `->modalCloseByClickingAway(false)`: Prevents accidental closure from outside clicks.

---

## 🏢 Multi-Panel Support

Vuelament supports multiple isolated admin panels by default.

**Creating a new panel:**

```bash
php artisan vuelament:panel Sales --id=sales
```

This creates `App\Vuelament\Providers\SalesPanelProvider`. Register in `bootstrap/providers.php`.

**Creating resources for a specific panel:**

```bash
php artisan vuelament:resource Invoice --panel=Sales
```

Vuelament isolates backend and frontend files into separate silos:

- **Backend**: `app/Vuelament/Sales/Invoice/InvoiceResource.php`
- **Frontend**: `resources/js/Pages/Vuelament/Sales/Resource/Invoice/...`

`AdminPanelProvider` and `SalesPanelProvider` have zero conflict.

---

## 🎨 Custom Pages & Widgets

Generate completely custom pages for charts, widgets, or dashboards:

```bash
php artisan vuelament:page Analytics --panel=Admin
```

This generates two files:

- `app/Vuelament/Admin/Pages/AnalyticsPage.php`
- `resources/js/Pages/Vuelament/Admin/Pages/AnalyticsPage.vue`

```php
use ChristYoga123\Vuelament\Core\BasePage;

class AnalyticsPage extends BasePage
{
    protected static ?string $navigationIcon = 'activity';
    protected static ?string $navigationLabel = 'Live Analytics';
    protected static string $slug = 'analytics';

    public static function getData(?\Illuminate\Database\Eloquent\Model $record = null): array
    {
        return [
            'totalUsers' => User::count(),
            'revenue' => 5000000,
        ];
    }
}
```

---

## 🛠️ Performance & Optimizations

- **Automatic Code Splitting**: Large libraries (`vue-quill`, `vue-datepicker`) are isolated into chunks via Vite.
- **Inertia.js Driven**: Lightning-fast SPA page transitions, no full browser reloads.
- **Client-Side Reactivity**: Form states evaluated in Vue without server requests.
- **Interactive Column Loading States**: Toggle/Checkbox columns disable during server requests, preventing spam.
- **No Per-Model Controllers**: Zero boilerplate controllers — framework handles routing.

---

## 📁 Frontend Architecture

```
resources/js/components/vuelament/
├── Table.vue                           # Main table orchestrator
├── table/
│   ├── utils.js                        # Pure helpers (resolveIcon, formatCell)
│   ├── composables/
│   │   └── useTableState.js            # All reactive table state & logic
│   ├── columns/
│   │   ├── ColumnCell.vue              # Dispatcher (selects by col.type)
│   │   ├── TextCell.vue                # Text + prefix/suffix/color
│   │   ├── BadgeCell.vue               # Badge with colors
│   │   ├── ToggleCell.vue              # Shadcn Switch + loading
│   │   ├── CheckboxCell.vue            # Checkbox + loading
│   │   ├── ImageCell.vue               # Image with circle/thumbnail
│   │   └── IconCell.vue                # Dynamic Lucide icon
│   ├── TableToolbar.vue                # Search + filters + column toggle
│   ├── TableFiltersAbove.vue           # Filters above table
│   ├── TableRowActions.vue             # Row actions (edit, delete, custom)
│   ├── TablePagination.vue             # Pagination + per page
│   ├── TableConfirmDialog.vue          # Confirmation dialog
│   └── TableActionFormDialog.vue       # Custom action form dialog
├── form/
│   ├── RichEditor.vue                  # Rich text editor wrapper
│   ├── DatePicker.vue                  # Date/time picker wrapper
│   └── composables/
│       └── useFormReactivity.js         # Client-side form reactivity
```

### Key Design Patterns

- **Composables** (`useTableState`, `useFormReactivity`) — extract all reactive logic from components
- **Provide/Inject** — table sub-components access shared state without prop drilling
- **Column Cell Dispatcher** — `ColumnCell.vue` routes to correct sub-component based on `col.type`
- **HasReactivity Trait** (PHP) — provides `visibleWhen()`, `disabledWhen()`, `requiredWhen()` methods serialized to JSON rules

---

## 🔧 Configuration

Publish the config file:

```bash
php artisan vendor:publish --tag=vuelament-config
```

```php
// config/vuelament.php
return [
    'default_panel' => 'admin',
    'user_model' => \App\Models\User::class,
    'app_path' => 'Vuelament',
    'assets' => [
        'auto_publish' => true,
    ],
];
```

### Customizing Stubs

You can publish and customize the code generation stubs:

```bash
php artisan vendor:publish --tag=vuelament-stubs
```

Stubs will be published to `stubs/vuelament/`. The framework will prioritize custom stubs over package defaults.

---

## 📋 All Available Commands

| Command                     | Description                                            |
| --------------------------- | ------------------------------------------------------ |
| `vuelament:install`         | Install Vuelament — publish assets, scaffold, setup    |
| `vuelament:resource {Name}` | Generate full CRUD module (Resource + Pages + Service) |
| `vuelament:service {Name}`  | Generate a Service class for action business logic     |
| `vuelament:page {Name}`     | Generate a custom page (PHP class + Vue component)     |
| `vuelament:panel {Name}`    | Generate a new panel provider                          |
| `vuelament:user`            | Create an admin user                                   |

### Common Options

| Option            | Description                     | Available On            |
| ----------------- | ------------------------------- | ----------------------- |
| `--panel=Admin`   | Target panel (default: Admin)   | resource, service, page |
| `--resource=User` | Attach to resource module       | service, page           |
| `--simple`        | Single-mode (modal create/edit) | resource                |
| `--generate`      | Auto-generate from database     | resource                |
| `--force`         | Overwrite existing files        | all                     |

---

## 📄 License

Vuelament is open-sourced software licensed under the [MIT License](LICENSE).

---

Happy Coding! 🎉
