# Vuelament

Vuelament is a powerful, elegant, and developer-friendly administration panel builder for Laravel, powered by Vue 3, Inertia.js, and Tailwind CSS. It is heavily inspired by the philosophy of [Filament PHP](https://filamentphp.com), bringing the joy of rapid backend development to the modern Vue ecosystem.

Say goodbye to writing boilerplate frontend code for your CRUD operations. With Vuelament, you can build beautifully designed, highly interactive, and fully responsive admin panels by writing simple, declarative PHP classes.

---

## 🌟 Key Features

- **Filament-Inspired Syntax:** Construct tables, forms, and actions using a familiar fluent PHP API.
- **Modern Tech Stack:** Built on top of Laravel, Vue 3 (Composition API), Inertia.js, Tailwind CSS, and `shadcn-vue`.
- **Resource Management:** Scaffold entire CRUD operations (Create, Read, Update, Delete) with a single `Resource` class.
- **Advanced Table Builder:** Full-featured data tables including sorting, searching, pagination, custom column formatting, filters, and row/bulk actions.
- **Advanced Form Builder:** Create complex forms effortlessly. Features include Grids, Sections, dependent fields (show/hide), dynamic validation rules, inline dehydration, and various input types (Text, Password, Toggles, Selects, File Uploads, etc.).
- **Automatic Labels & State Formatting:** Out-of-the-box smart labeling and closure-based state evaluation (`getStateUsing`, `formatStateUsing`, `color`) for ultimate flexibility.
- **Custom Pages:** Easily create dedicated Vue pages within your panel using Artisan commands.
- **Seamless SPA Experience:** Enjoy lightning-fast page transitions and optimistic UI updates without full page reloads.

---

## 🚀 Quick Start

### 1. Installation

Require the package via Composer _(Make sure you have an existing Laravel + Inertia Vue project setup)_:

```bash
composer require vuelament/vuelament
```

Install the required NPM dependencies:

```bash
npm install lucide-vue-next @inertiajs/vue3 class-variance-authority clsx tailwind-merge
```

### 2. Creating a Panel

First, set up a Service Provider for your panel to define the path, branding, and discoverable directories for your resources and pages.

### 3. Creating a Resource

To manage an Eloquent model (e.g., `User`), create a Resource class:

```php
php artisan vuelament:resource User
```

This will generate a `UserResource.php` file. You can then define your Table and Form schemas using the fluent API:

#### Defining the Table

```php
use App\Vuelament\Components\Table\Table;
use App\Vuelament\Components\Table\Columns\TextColumn;
use App\Vuelament\Components\Table\Columns\ToggleColumn;

public static function tableSchema(): Table
{
    return Table::make()
        ->columns([
            TextColumn::make('name')
                ->label('Full Name')
                ->sortable()
                ->searchable(),

            TextColumn::make('email')
                ->label('Email Address')
                ->searchable(),

            ToggleColumn::make('is_active')
                ->label('Status'),

            TextColumn::make('roles')
                ->label('Role')
                ->badge()
                ->color(fn ($record) => $record->role === 'admin' ? 'success' : 'info')
                ->getStateUsing(fn ($record) => strtoupper($record->role)),
        ]);
}
```

#### Defining the Form

```php
use App\Vuelament\Core\PageSchema;
use App\Vuelament\Components\Layout\Grid;
use App\Vuelament\Components\Form\TextInput;
use App\Vuelament\Components\Form\Toggle;

public static function formSchema(): PageSchema
{
    return PageSchema::make()
        ->components([
            Grid::make(2)->schema([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),

                TextInput::make('email')
                    ->email()
                    ->required(),

                TextInput::make('password')
                    ->password()
                    ->required(fn (string $operation) => $operation === 'create')
                    ->dehydrateStateUsing(fn ($state) => Hash::make($state)),

                Toggle::make('is_active')
                    ->default(true),
            ])
        ]);
}
```

That's it! Vuelament will automatically render the List, Create, and Edit pages with all the configured features.

---

## 📖 Component Reference

### Table Columns

- `TextColumn::make('field')`
- `ToggleColumn::make('field')`
- _More to come..._

_Available Modifiers:_ `->label()`, `->sortable()`, `->searchable()`, `->badge()`, `->color()`, `->getStateUsing()`, `->formatStateUsing()`, `->prefix()`, `->suffix()`.

### Form Inputs

- `TextInput::make('field')`
- `Toggle::make('field')`
- `FileInput::make('field')`
- `Select::make('field')`
- `Repeater::make('field')`

_Available Modifiers:_ `->required()`, `->disabled()`, `->unique()`, `->dehydrateStateUsing()`, `->visible()`.

### Form Layouts

- `Grid::make(columns)`
- `Section::make('Heading')`
- `Card::make()`

---

## 🛠 Advanced Usage

### Dependency Injection in Closures

Vuelament evaluates closures using Laravel's robust service container. You can type-hint the current `$record` or the raw `$state` to execute conditional logic exactly like Filament:

```php
TextColumn::make('balance')
    ->color(function (User $user) {
        if ($user->balance < 0) return 'danger';
        if ($user->balance > 1000) return 'success';
        return 'warning';
    })
    ->formatStateUsing(fn ($state) => 'Rp ' . number_format($state, 0, ',', '.'));
```

### Flash Message Suppression

Toggles and inline column adjustments process silently via Vue's optimistic UI updates, skipping full round-trip page flashes and ensuring your application behaves like a true, seamless SPA.

---

## 🤝 Contributing

Contributions are welcome! Please feel free to submit a Pull Request if you'd like to add new input types, column formats, or improve the engine.

## 📄 License

Vuelament is open-sourced software licensed under the [MIT license](LICENSE).
