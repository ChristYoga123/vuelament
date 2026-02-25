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
- **Robust Table Builder**: Supports filtering, search, pagination, bulk actions, and custom column rendering.
- **Dynamic Modals**: Conditional Action dialogs, flexible modal widths, click-away handling, and dangerous action confirmations.

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

- `TextInput`
- `Textarea`
- `RichEditor` (Powered by VueQuill)
- `DatePicker`, `TimePicker`, `DateRangePicker` (Powered by VuePic)
- `Select`
- `Checkbox`
- `Radio`
- `Toggle`
- `FileInput`

### Defining Tables

Define the list view columns, actions, and filters inside `tableSchema()`:

```php
use App\Vuelament\Components\Table\Table;
use App\Vuelament\Components\Table\Columns\TextColumn;
use App\Vuelament\Components\Table\Columns\ToggleColumn;
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
    ->modalCancelActionLabel('Tutup')
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

---

Happy Coding! 🎉
