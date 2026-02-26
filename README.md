<div align="center">
    <h1>🚀 Vuelament</h1>
    <p>A lightning-fast, lightweight, and modern Admin Dashboard & CRUD Generator for Laravel.<br> Built on the VILT stack (Vue 3, Inertia.js, Laravel, Tailwind CSS) and powered by Shadcn Vue for a gorgeous UI.</p>
</div>

---

## ✨ Features

- **Ultra Lightweight**: Minimal dependencies, split code-chunking, and no bloat.
- **Beautiful UI**: Uses Tailwind CSS v4 and Shadcn-Vue for a premium, accessible, and easily customizable design.
- **VILT Stack**: Seamless SPA experience powered by Laravel, Inertia, Vue 3, and Tailwind.
- **Complete Form Builder**: Supports Rich Editor (Vue Quill), Date/Time Pickers (VueDatePicker), File Uploads, Selects, Toggles, Checkboxes, Radios, and responsive Grid Layouts.
- **Client-Side Form Reactivity**: Show/hide, enable/disable, and change required state of fields **instantly without server requests**.
- **Robust Table Builder**: Supports filtering, search, pagination, bulk actions, column toggling, and rich column types (Text, Badge, Toggle, Checkbox, Image, Icon).
- **Dynamic Modals**: Conditional Action dialogs, flexible modal widths, click-away handling, and dangerous action confirmations.
- **Modular Architecture**: Clean, maintainable codebase with composables, sub-components, and separated concerns.

## 📦 Requirements

- **PHP** 8.2+
- **Laravel** 11.x
- **Node.js** 18+ & NPM

## 🛠️ Installation

1. Install via composer (Local path/symlink assuming you are developing the package):

```bash
composer require your-vendor/vuelament
```

2. Publish the assets and configuration:

```bash
php artisan vuelament:install
```

3. Run migrations for base Vuelament tables (if any):

```bash
php artisan migrate
```

4. Install NPM dependencies & Build assets:

```bash
npm install
npm run build
```

---

## 🚀 Usage Guide

### Creating a Resource

Vuelament centers around **Resources**—classes that describe how your Eloquent Models can be created, read, updated, and deleted.

Run the Artisan command to create a new Resource:

```bash
php artisan make:vuelament-resource User
```

This will create a `UserResource.php` inside `app/Vuelament/Admin/Resources/User`.

### Defining Forms

Define the form layout inside your Resource's `formSchema` method:

```php
use App\Vuelament\Components\Layout\Grid;
use App\Vuelament\Components\Layout\Section;
use App\Vuelament\Components\Form\TextInput;
use App\Vuelament\Components\Form\DatePicker;
use App\Vuelament\Components\Form\RichEditor;

public static function formSchema(): PageSchema
{
    return PageSchema::make()
        ->components([
            Section::make('General Information')
                ->components([
                    Grid::make(2)->components([
                        TextInput::make('name')->required(),
                        TextInput::make('email')->email()->required(),
                    ]),
                    RichEditor::make('content')->minHeight(300),
                    DatePicker::make('published_at'),
                ])
        ]);
}
```

#### Available Form Controls:

- `TextInput` — text, email, password, number, with prefix/suffix and revealable support
- `Textarea` — multi-line text input
- `RichEditor` — Rich text editor (powered by VueQuill)
- `DatePicker`, `TimePicker`, `DateRangePicker` — Date/time selection (powered by VuePic)
- `Select` — Dropdown selection
- `Checkbox` — Single or multiple checkbox group
- `Radio` — Radio button group with horizontal/vertical layout
- `Toggle` — Switch toggle (powered by Shadcn Switch)
- `FileInput` — File upload with drag & drop, preview, and reorder support

### Form Reactivity (Client-Side)

Vuelament evaluates form visibility, disabled, and required states **entirely on the client** — no server requests needed for simple field interactions. This is a major performance advantage over Filament/Livewire.

#### ⚔️ Vuelament vs Filament (Behavior Comparison)

| Interaction                                  | Filament (Livewire)                                                    | Vuelament (Vue 3 + Inertia)                                               |
| -------------------------------------------- | ---------------------------------------------------------------------- | ------------------------------------------------------------------------- |
| **Toggle a field to show another**           | 🛑 Triggers a network request to the server, re-renders the component. | ⚡ **Instant**. Evaluated entirely in JavaScript via `useFormReactivity`. |
| **Change required state based on selection** | 🛑 Requires a network request.                                         | ⚡ **Instant**. Handled locally without any server overhead.              |
| **Simple validation visibility**             | 🛑 Network round-trip, can feel sluggish on slow connections.          | ⚡ **Instant**. Zero latency.                                             |
| **Complex logic (coming soon)**              | Server-side execution via Closure.                                     | Hybrid: specific fields trigger lightweight AJAX for state evaluation.    |

```php
use App\Vuelament\Components\Form\TextInput;
use App\Vuelament\Components\Form\Toggle;
use App\Vuelament\Components\Form\Select;

public static function formSchema(): PageSchema
{
    return PageSchema::make()
        ->components([
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

            // Disabled when is_locked = true
            TextInput::make('email')
                ->disabledWhen('is_locked', true),

            // Shows when category field is filled (any value)
            Select::make('subcategory')
                ->visibleWhen('category', operator: 'filled'),
        ]);
}
```

#### Available Reactivity Methods:

| Method                           | Description                                |
| -------------------------------- | ------------------------------------------ |
| `->visibleWhen('field', value)`  | Show when field equals value               |
| `->hiddenWhen('field', value)`   | Hide when field equals value               |
| `->disabledWhen('field', value)` | Disable when field equals value            |
| `->enabledWhen('field', value)`  | Enable when field equals value             |
| `->requiredWhen('field', value)` | Required when field equals value           |
| `->visibleWhenAll([...])`        | Show when ALL conditions match (AND logic) |
| `->visibleWhenAny([...])`        | Show when ANY condition matches (OR logic) |

#### Supported Operators:

`===`, `!==`, `in`, `notIn`, `filled`, `blank`, `>`, `<`, `>=`, `<=`

```php
// Multiple conditions (AND)
TextInput::make('bank_account')
    ->visibleWhenAll([
        ['field' => 'payment_method', 'value' => 'transfer'],
        ['field' => 'is_active', 'value' => true],
    ]);

// Value in array
TextInput::make('notes')
    ->visibleWhen('status', ['pending', 'review'], operator: 'in');

// Filled check (not empty)
Select::make('subcategory')
    ->visibleWhen('category', operator: 'filled');
```

### Defining Tables

Define the list view columns, actions, and filters inside `tableSchema()`:

```php
use App\Vuelament\Components\Table\Table;
use App\Vuelament\Components\Table\Columns\TextColumn;
use App\Vuelament\Components\Table\Columns\ToggleColumn;
use App\Vuelament\Components\Table\Columns\CheckboxColumn;
use App\Vuelament\Components\Table\Columns\ImageColumn;
use App\Vuelament\Components\Table\Columns\IconColumn;
use App\Vuelament\Components\Table\Actions\EditAction;
use App\Vuelament\Components\Table\Actions\DeleteAction;
use App\Vuelament\Components\Filters\SelectFilter;

public static function tableSchema(): PageSchema
{
    return PageSchema::make()
        ->components([
            Table::make()
                ->query(fn() => User::query())
                ->columns([
                    TextColumn::make('name')->searchable()->sortable(),
                    TextColumn::make('email'),
                    ToggleColumn::make('is_active'),
                    CheckboxColumn::make('is_verified'),
                    ImageColumn::make('avatar')->circle()->size('32px'),
                    IconColumn::make('status_icon'),
                ])
                ->actions([
                    EditAction::make(),
                    DeleteAction::make(),
                ])
                ->filters([
                    SelectFilter::make('role')->options([...]),
                ])
        ]);
}
```

#### Available Column Types:

| Column           | Description                                                                     |
| ---------------- | ------------------------------------------------------------------------------- |
| `TextColumn`     | Text with optional prefix, suffix, color, badge, and date formatting            |
| `ToggleColumn`   | Interactive switch toggle with loading state (disabled while request processes) |
| `CheckboxColumn` | Interactive checkbox with loading state                                         |
| `ImageColumn`    | Image display with `->circle()`, `->thumbnail()`, `->size()`                    |
| `IconColumn`     | Dynamic Lucide icon with color support                                          |

All interactive columns (Toggle, Checkbox) automatically show a **disabled/loading state** while processing server requests — preventing spam clicks and giving users clear visual feedback.

---

## ⚡ Custom Actions & Modals

You can create standalone or table-row actions that pop up modals containing forms or confirmations. Vuelament handles these interactions beautifully.

```php
use App\Vuelament\Components\Table\Actions\Action;

Action::make('form')
    ->icon('form')
    ->color('success')
    ->label('Fill Form')
    ->modalHeading('Detailed User Form')
    ->modalWidth('4xl') // Using Tailwind JIT sizes: sm, md, lg, xl, 2xl, 3xl, etc.
    ->modalCloseByClickingAway(false) // Force user to click cancel/submit
    ->modalCancelActionLabel('Close')
    ->form([
        TextInput::make('reference_id')->required(),
        Radio::make('type')->options([
            'a' => 'Type A',
            'b' => 'Type B',
        ])
    ])
    ->action(function (array $data) {
        // Execute server logic here.
    })
```

### Action Configuration Methods:

- `->requiresConfirmation()`: Auto-converts the form into a danger Action/Alert Dialog.
- `->modalWidth('2xl')`: Defines the modal width dynamically.
- `->modalCancelAction(false)` / `->modalSubmitAction(false)`: Hide specific modal buttons.
- `->modalCloseByClickingAway(false)`: Prevents accidental closure from outside clicks.

---

## 🏢 Multi-Panel Support (Admin, Panel B, etc.)

Vuelament is built with multi-tenancy/multi-panel setups in mind by default. You can spin up as many isolated panels as you want.

**Creating a new panel:**

```bash
php artisan vuelament:panel Sales --id=sales
```

This will create `App\Vuelament\Providers\SalesPanelProvider`. You can register this in your `bootstrap/providers.php`.

**Creating Resources for a Specific Panel:**
When generating resources or pages, simply append `--panel=Sales`.

```bash
php artisan vuelament:resource Invoice --panel=Sales
```

This forces Vuelament to automatically isolate the back-end and front-end files into entirely separate silos:

1. **Controller & Resource**: `app/Vuelament/Sales/Resources/InvoiceResource.php`
2. **Vue Frontend**: `resources/js/Pages/Vuelament/Sales/Resource/Invoice/...`

This means your `AdminPanelProvider` and `SalesPanelProvider` will have zero conflict with each other!

---

## 🎨 Custom Pages & Widgets

Sometimes an auto-generated CRUD isn't enough. You can create completely custom pages that act as blank canvases for charts, widgets, or customized dashboards.

1. Generate a Custom Page:

```bash
php artisan vuelament:page Analytics
```

2. This will generate two files:
    - `app/Vuelament/Admin/Pages/AnalyticsPage.php` - The backend class dictating navigation, routes, and props.
    - `resources/js/Pages/Vuelament/Admin/Pages/AnalyticsPage.vue` - The frontend Vue Component.

3. Defining the Backend Page:

```php
use App\Vuelament\Core\BasePage;

class AnalyticsPage extends BasePage
{
    protected static ?string $navigationIcon = 'activity';
    protected static ?string $navigationLabel = 'Live Analytics';
    protected static string $slug = 'analytics';

    // Send custom properties to the Vue component
    protected function getViewData(): array
    {
        return [
            'totalUsers' => User::count(),
            'revenue' => 5000000,
        ];
    }
}
```

4. Crafting the Frontend (`AnalyticsPage.vue`):

```vue
<script setup>
import { usePage } from "@inertiajs/vue3";
import DashboardLayout from "@/Layouts/DashboardLayout.vue";

const props = defineProps({
    totalUsers: Number,
    revenue: Number,
});
</script>

<template>
    <DashboardLayout title="Live Analytics">
        <div class="grid grid-cols-2 gap-4">
            <div class="p-6 bg-card rounded-xl border shadow">
                <h3>Total Users</h3>
                <p class="text-3xl font-bold">{{ totalUsers }}</p>
            </div>
            <div class="p-6 bg-card rounded-xl border shadow">
                <h3>Revenue</h3>
                <p class="text-3xl font-bold text-success">{{ revenue }}</p>
            </div>
        </div>
    </DashboardLayout>
</template>
```

---

## 🛠️ Performance & Optimizations

Vuelament is optimized to have minimal impact on your users' network:

- **Automatic Code Splitting**: Through Vite, large libraries (`vue-quill`, `vue-datepicker`) are isolated into their own chunks. They only load when the specific form component is evaluated.
- **Inertia.js Driven**: Lightning-fast SPA page transitions, no full browser reloads.
- **Client-Side Reactivity**: Form visibility, disabled, and required states are evaluated in Vue without server requests — unlike Filament/Livewire which round-trips every interaction.
- **Interactive Column Loading States**: Toggle and Checkbox columns disable during server requests, preventing spam and providing clear UX feedback.

---

## 📁 Architecture & Folder Structure

Vuelament follows a modular Vue component architecture for maintainability:

```
resources/js/components/vuelament/
├── Table.vue                           # Main table orchestrator (~260 lines)
├── table/
│   ├── utils.js                        # Pure helpers (resolveIcon, formatCell, isTruthy)
│   ├── composables/
│   │   └── useTableState.js            # All reactive table state & logic
│   ├── columns/
│   │   ├── ColumnCell.vue              # Dispatcher (selects component by col.type)
│   │   ├── TextCell.vue                # Normal text + prefix/suffix/color
│   │   ├── BadgeCell.vue               # Badge rendering with colors
│   │   ├── ToggleCell.vue              # Wraps Shadcn Switch + disabled behavior
│   │   ├── CheckboxCell.vue            # Native checkbox + disabled behavior
│   │   ├── ImageCell.vue               # Image with circle/thumbnail/size
│   │   └── IconCell.vue                # Dynamic Lucide icon + color
│   ├── TableToolbar.vue                # Search + filter dropdown + column toggle
│   ├── TableFiltersAbove.vue           # Filter layout above table content
│   ├── TableRowActions.vue             # Row actions (edit, delete, custom)
│   ├── TablePagination.vue             # Pagination + per page selector
│   ├── TableConfirmDialog.vue          # Confirmation dialog
│   └── TableActionFormDialog.vue       # Custom action form/infolist dialog
├── form/
│   ├── RichEditor.vue                  # Rich text editor wrapper
│   ├── DatePicker.vue                  # Date/time picker wrapper
│   └── composables/
│       └── useFormReactivity.js         # Client-side form reactivity evaluator
```

### Key Design Patterns:

- **Composables** (`useTableState`, `useFormReactivity`) — extract all reactive logic from components
- **Provide/Inject** — table sub-components access shared state without prop drilling
- **Column Cell Dispatcher** — `ColumnCell.vue` routes to the correct sub-component based on `col.type`, making it easy to add new column types
- **HasReactivity Trait** (PHP) — provides `visibleWhen()`, `disabledWhen()`, `requiredWhen()` methods that serialize to JSON rules evaluated client-side

---

Happy Coding! 🎉
