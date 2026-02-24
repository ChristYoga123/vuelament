# Vuelament

**NOTE: Akan menjadi package Laravel**

**Admin panel builder untuk Laravel** — terinspirasi oleh [Filament](https://filamentphp.com), dibangun dengan **Vue.js 3**, **Inertia.js**, dan **shadcn-vue**.

Vuelament menyediakan cara deklaratif untuk membangun admin panel lengkap: resources (CRUD), form builder, table builder, actions, widgets, filter, dan navigasi — semua didefinisikan dari PHP tanpa menulis frontend secara manual.

---

## ✨ Fitur Utama

- 🏗️ **Resource Generator** — CRUD otomatis dari model Eloquent
- 📊 **Table Builder** — sortable, searchable, paginated, selectable columns
- 📝 **Form Builder** — TextInput, Select, DatePicker, Toggle, RichEditor, Repeater, dll
- ⚡ **Bulk Actions** — Hapus massal, restore, force delete dengan dropdown grouped
- 🎨 **Dark / Light Mode** — toggle tema di header
- 🔐 **Auth & Middleware** — login page bawaan dengan role-based guard
- 🧩 **Modular Panel** — multi panel support (admin, manager, dll)
- 📦 **Auto-discover** — resources, pages, widgets otomatis ter-register

---

## 📋 Requirements

- PHP >= 8.1
- Laravel >= 10.x
- Node.js >= 18.x
- Composer

---

## 🚀 Installation

### 1. Install Dependencies

```bash
composer install
npm install
```

### 2. Setup Environment

```bash
cp .env.example .env
php artisan key:generate
```

### 3. Migrasi Database

```bash
php artisan migrate
```

### 4. Buat User Admin

```bash
php artisan vuelament:user
```

Atau dengan opsi langsung:

```bash
php artisan vuelament:user --name="Admin" --email="admin@gmail.com" --password="password" --role="super_admin"
```

### 5. Build Frontend

```bash
# Development
npm run dev

# Production
npm run build
```

### 6. Jalankan Server

```bash
php artisan serve
```

Akses panel di: **http://127.0.0.1:8000/admin**

---

## 🛠️ Usage

### Panel Provider

Setiap panel didefinisikan di `app/Vuelament/Providers/`. Contoh `AdminPanelProvider.php`:

```php
namespace App\Vuelament\Providers;

use App\Vuelament\Core\Panel;
use App\Vuelament\Core\NavigationGroup;
use App\Vuelament\VuelamentServiceProvider;

class AdminPanelProvider extends VuelamentServiceProvider
{
    public function panel(): Panel
    {
        return Panel::make()
            ->id('admin')
            ->path('admin')
            ->brandName('Admin Panel')
            ->login()
            ->middleware(['web'])
            ->authMiddleware([\App\Vuelament\Http\Middleware\Authenticate::class])
            ->colors(['primary' => '#6366f1'])
            ->discoverResources(
                app_path('Vuelament/Admin/Resources'),
                'App\\Vuelament\\Admin\\Resources'
            )
            ->navigation([
                NavigationGroup::make('Master')
                    ->items([
                        ...UserResource::getNavigationItems(),
                    ])
            ]);
    }
}
```

Panel provider harus didaftarkan di `config/app.php` pada array `providers`.

---

### Panel Access (Authorization)

Secara default, Vuelament mencoba mengecek _role_ menggunakan package Spatie Permission. Jika Anda tidak menggunakannya, **akses panel hanya diizinkan otomatis ketika aplikasi berada di environment `local`**. Untuk environment `production`, Anda **wajib** mendefinisikan logika otorisasi secara eksplisit agar panel Anda aman.

Tambahkan trait `HasPanelAccess` pada model `User` dan _override_ method `canAccessPanel`:

```php
use App\Vuelament\Traits\HasPanelAccess;
use App\Vuelament\Core\Panel;

class User extends Authenticatable
{
    use HasPanelAccess; // Wajib

    public function canAccessPanel(Panel $panel): bool
    {
        // Contoh: Hanya user di bawah domain perusahaan yang boleh akses panel admin
        if ($panel->getId() === 'admin') {
            return str_ends_with($this->email, '@perusahaan.com') && $this->is_active;
        }

        return false;
    }
}
```

---

### Membuat Resource

#### Artisan Command

```bash
# Basic
php artisan vuelament:resource Post

# Dengan auto-generate field dari database
php artisan vuelament:resource Post --generate

# Dengan panel tertentu
php artisan vuelament:resource Post --panel=Admin

# Dengan model custom
php artisan vuelament:resource Post --model=BlogPost

# Overwrite jika sudah ada
php artisan vuelament:resource Post --force
```

Command ini akan menghasilkan:

- `app/Vuelament/Admin/Resources/PostResource.php`
- `app/Http/Controllers/Vuelament/Admin/PostController.php`

---

### Struktur Resource

Setiap resource memiliki 2 method utama: `tableSchema()` dan `formSchema()`.

```php
namespace App\Vuelament\Admin\Resources;

use App\Models\Post;
use App\Vuelament\Facades\V;
use App\Vuelament\Core\PageSchema;
use App\Vuelament\Core\BaseResource;
use App\Vuelament\Components\Table\Table;
use App\Vuelament\Components\Table\Column;
use App\Vuelament\Components\Actions\ActionGroup;
use App\Vuelament\Components\Actions\CreateAction;
use App\Vuelament\Components\Actions\DeleteBulkAction;
use App\Vuelament\Components\Table\Actions\EditAction;
use App\Vuelament\Components\Table\Actions\DeleteAction;

class PostResource extends BaseResource
{
    protected static string $model = Post::class;
    protected static string $slug = 'posts';
    protected static string $label = 'Post';
    protected static string $icon = 'file-text';

    protected static int $navigationSort = 1;

    public static function tableSchema(): PageSchema
    {
        return PageSchema::make()
            ->title(static::$label)
            ->components([
                Table::make()
                    ->columns([
                        Column::make('id')->label('ID')->sortable(),
                        Column::make('title')->label('Judul')->sortable()->searchable(),
                        Column::make('status')->label('Status')->badge(),
                        Column::make('created_at')->label('Dibuat')->dateFormat('d/m/Y')->sortable(),
                    ])
                    ->actions([
                        EditAction::make(),
                        DeleteAction::make(),
                    ])
                    ->bulkActions([
                        ActionGroup::make('Aksi Massal')
                            ->icon('list')
                            ->actions([
                                DeleteBulkAction::make(),
                            ]),
                    ])
                    ->filters([])
                    ->headerActions([
                        CreateAction::make(),
                    ])
                    ->searchable()
                    ->paginated()
                    ->selectable(),
            ]);
    }

    public static function formSchema(): PageSchema
    {
        return PageSchema::make()
            ->title('Buat ' . static::$label)
            ->components([
                V::grid(2)->schema([
                    V::textInput('title')->label('Judul')->required(),
                    V::select('category_id')
                        ->label('Kategori')
                        ->options(\App\Models\Category::pluck('name', 'id')->toArray()),
                ]),
                V::richEditor('content')->label('Konten')->required(),
                V::toggle('is_published')->label('Published'),
            ]);
    }
}
```

---

### Resource Data Hooks

Sebelum atau sesudah proses Create / Update, Anda bisa mengintersepsi (tweak) `$data` atau `$record` _Eloquent_ layaknya Laravel murni lewat method-method hook berikut di dalam file Resource Anda `(misal UserResource.php)`:

```php
    // Memanipulasi $data sebelum disimpan ke DB (saat Create)
    public static function mutateFormDataBeforeCreate(array $data): array
    {
        $data['user_id'] = auth()->id();
        return $data;
    }

    // Eksekusi logic setelah record baru terbentuk di DB
    public static function afterCreate(\Illuminate\Database\Eloquent\Model $record, array $data): void
    {
        // Contoh: Kirim email notifikasi
        // Mail::to($record->email)->send(new WelcomeMail($record));
    }

    // Memanipulasi $data sebelum disimpan ke DB (saat Edit/Update)
    public static function mutateFormDataBeforeSave(array $data): array
    {
        // ...
        return $data;
    }

    // Eksekusi logic setelah record di-update di DB
    public static function afterSave(\Illuminate\Database\Eloquent\Model $record, array $data): void
    {
        // ...
    }
```

---

### Custom Pages

Selain Resource (CRUD), Anda juga bisa membuat halaman custom. Sangat berguna untuk merender halaman _Report_, _Settings_, atau form _Single Action_.

#### Artisan Command

```bash
php artisan vuelament:page SettingsPage
```

#### Struktur Custom Page

Berkat arsitektur _Headless/Server-Driven UI_ (_Zero Vue Files_), secara default Custom Page akan meminjam template generik bawaan. Anda **tidak wajib** membuat `<template> Vue` secara manual jika Anda hanya butuh merender form/tabel sederhana.

```php
namespace App\Vuelament\Admin\Pages;

use App\Vuelament\Core\BasePage;
use App\Vuelament\Core\PageSchema;
use App\Vuelament\Components\Table\Table;
use App\Vuelament\Components\Table\Column;
use App\Models\User;

class SettingsPage extends BasePage
{
    protected static string $slug = 'settings';
    protected static string $title = 'Pengaturan Aplikasi';
    protected static ?string $description = 'Ubah parameter core dari aplikasi Anda.';
    protected static string $icon = 'settings';

    // (Opsional) Mengarahkan menu ini ke grup sidebar tertentu
    // protected static ?string $navigationGroup = 'Master';

    /**
     * Otomatis merender Vuelament Table pada halaman
     */
    public static function table(): ?PageSchema
    {
        return PageSchema::make()
            ->components([
                Table::make()
                    ->query(fn() => User::where('role', 'admin'))
                    ->columns([
                        Column::make('name')->label('Nama'),
                        Column::make('email')->label('Email'),
                    ])
                    ->paginated()
            ]);
    }
}
```

Jika Anda memerlukan halaman dashboard dengan chart/kanban kompleks yang tidak bisa dibungkus dengan komponen PHP, Anda dapat menimpa (override) view standar ke file Vue buatan Anda sendiri:

```php
    // Ubah pointer ini ke path file Vue komponen Anda (resources/js/Pages/...)
    protected static string $view = 'Vuelament/Pages/Admin/SettingsPage';
```

#### Resource Sub-Pages & Breadcrumbs

Jika halaman _Custom Page_ dimaksudkan sebagai halaman "anak" dari sebuah resource (bukan halaman global standalone), Anda dapat mendaftarkan property `$resource` pada Page tersebut. Ini akan membuat:

1. URL secara otomatis mengikuti format Resource (misal `/admin/users/{record}/report`).
2. Page dilompati dari auto-discovery global.
3. Otomatis menerima pelemparan data `$record` dari database jika dipanggil.

```php
class ReportPage extends BasePage
{
    // Meletakkan page ini di bawah UserResource
    protected static ?string $resource = UserResource::class;

    // (Opsional) Mengustomisasi breadcrumb
    public static function getBreadcrumbs(): array
    {
        return [
            url('/admin') => 'Dashboard',
            UserResource::getUrl('index') => 'Pengguna',
            null => 'Laporan Custom',
        ];
    }
}
```

_Note:_ Anda juga bisa meng- override metode `getBreadcrumbs(string $operation, mixed $record = null): array` pada `ResourceController` Anda.

---

### Table Builder

#### Columns

```php
Column::make('name')->label('Nama')->sortable()->searchable(),
Column::make('email')->label('Email')->sortable()->searchable(),
Column::make('is_active')->label('Aktif')->badge(),
Column::make('created_at')->label('Dibuat')->dateFormat('d/m/Y')->sortable(),
Column::make('notes')->label('Catatan')->toggleable(true, true), // toggleable, hidden by default
```

#### Row Actions

```php
->actions([
    Action::make('report')
        ->icon('file')
        ->color('success')  // 'danger', 'warning', 'success', 'primary'
        ->label('Laporan')
        ->url(fn(User $user) => ReportPage::getUrl(['record' => $user->id]))
        ->openUrlInNewTab(), // Buka tab baru jika diklik
    EditAction::make(),
    DeleteAction::make(),
    RestoreAction::make(),         // untuk SoftDeletes
    ForceDeleteAction::make(),     // untuk SoftDeletes
])
```

#### Bulk Actions (Grouped)

Bulk actions ditampilkan sebagai dropdown saat ada item yang dipilih:

```php
use App\Vuelament\Components\Actions\ActionGroup;
use App\Vuelament\Components\Actions\DeleteBulkAction;
use App\Vuelament\Components\Actions\RestoreBulkAction;
use App\Vuelament\Components\Actions\ForceDeleteBulkAction;

->bulkActions([
    ActionGroup::make('Aksi Massal')
        ->icon('list')
        ->actions([
            DeleteBulkAction::make(),
            RestoreBulkAction::make(),         // untuk SoftDeletes
            ForceDeleteBulkAction::make(),     // untuk SoftDeletes
        ]),
])
```

#### Header Actions

```php
->headerActions([
    CreateAction::make(),
])
```

#### Custom Table Query

Secara default, Table akan memanggil `YourModel::query()`. Jika ingin memfilter atau mensortir base query:

```php
Table::make()
    ->query(fn() => User::query()->where('is_active', true)->latest())
    ->columns([...])
```

#### Table Options

```php
Table::make()
    ->searchable()          // enable search bar
    ->paginated()           // enable pagination
    ->perPage(25)           // default per page
    ->perPageOptions([10, 25, 50, 100])
    ->selectable()          // checkbox select
    ->defaultSort('name', 'asc')
    ->emptyStateHeading('Belum ada data')
    ->emptyStateDescription('Klik tombol tambah untuk membuat data baru.')
```

---

### Filters

Filter mendukung 3 layout yang bisa dikonfigurasi:

```php
use App\Vuelament\Components\Table\FiltersLayout;
use App\Vuelament\Components\Filters\SelectFilter;
```

#### 1. Dropdown (Default)

Filter tersembunyi di balik icon filter button:

```php
->filters([
    SelectFilter::make('status')
        ->label('Status')
        ->options([
            'active'   => 'Aktif',
            'inactive' => 'Nonaktif',
        ]),
])
// atau secara eksplisit:
->filters([
    SelectFilter::make('status')->label('Status')->options([...]),
], layout: FiltersLayout::Dropdown)
```

#### 2. Above Content

Filter selalu visible di atas tabel:

```php
->filters([
    SelectFilter::make('category_id')
        ->label('Kategori')
        ->options(Category::pluck('name', 'id')->toArray()),
    SelectFilter::make('status')
        ->label('Status')
        ->options(['draft' => 'Draft', 'published' => 'Published']),
], layout: FiltersLayout::AboveContent)
```

#### 3. Above Content Collapsible

Filter di atas tabel dengan toggle show/hide:

```php
->filters([
    SelectFilter::make('trashed')
        ->label('Status')
        ->options([
            ''     => 'Tanpa Trashed',
            'with' => 'Dengan Trashed',
            'only' => 'Hanya Trashed',
        ]),
], layout: FiltersLayout::AboveContentCollapsible)
```

#### Menggunakan `filtersLayout()` terpisah

```php
->filters([...])
->filtersLayout(FiltersLayout::AboveContent)
```

#### Filter yang tersedia

| Filter           | Deskripsi                    |
| ---------------- | ---------------------------- |
| `SelectFilter`   | Dropdown select dengan opsi  |
| `ToggleFilter`   | Toggle on/off                |
| `RadioFilter`    | Radio button group           |
| `CheckboxFilter` | Checkbox group               |
| `CustomFilter`   | Filter custom dengan closure |

---

### Form Builder

Gunakan facade `V` untuk shorthand:

```php
// Text Input
V::textInput('name')->label('Nama')->required()->maxLength(255)
V::textInput('email')->label('Email')->type('email')->required()
V::textInput('price')->label('Harga')->type('number')
V::textInput('password')->label('Password')->password()->revealable()

// Textarea
V::textarea('description')->label('Deskripsi')->rows(5)

// Rich Editor
V::richEditor('content')->label('Konten')

// Select
V::select('category_id')
    ->label('Kategori')
    ->options(['draft' => 'Draft', 'published' => 'Published'])
    ->searchable()

// Date & Time
V::datePicker('birth_date')->label('Tanggal Lahir')
V::timePicker('start_time')->label('Waktu Mulai')
V::dateRangePicker('period')->label('Periode')

// Toggle & Checkbox
V::toggle('is_active')->label('Aktif')
V::checkbox('agree')->label('Setuju dengan syarat')

// Radio
V::radio('gender')
    ->label('Jenis Kelamin')
    ->options(['male' => 'Laki-laki', 'female' => 'Perempuan'])

// File Upload
V::fileInput('avatar')->label('Foto')->image()->maxSize(2048)

// Repeater
V::repeater('items')->label('Item')->schema([
    V::textInput('name')->label('Nama'),
    V::textInput('qty')->label('Qty')->type('number'),
])
```

#### Layout Components

```php
// Grid
V::grid(2)->schema([
    V::textInput('first_name'),
    V::textInput('last_name'),
])

// Section
V::section('Informasi Dasar')->schema([
    V::textInput('name'),
])

// Card
V::card('Detail')->schema([
    V::textarea('notes'),
])
```

#### Validation

Validasi otomatis dibangun dari properti komponen (`required()`, `maxLength()`, `type()`, dll). Bisa juga override manual:

```php
public static function rules(string $action, mixed $id = null): array
{
    return [
        'name'  => 'required|string|max:255',
        'email' => 'required|email|unique:users,email' . ($id ? ",{$id}" : ''),
    ];
}
```

---

### Data Lifecycle & Hooks (Form)

Form inputs memiliki siklus hidup yang memungkinkan manipulasi state sebelum disimpan, serta konfigurasi validasi yang dinamis:

```php
V::textInput('password')
    ->label('Password')
    ->password()
    ->revealable()
    // 1. Dinamis berdasarkan 'create' atau 'edit'
    ->required(fn (string $operation): bool => $operation === 'create')
    // 2. Manipulasi data sebelum validator/penyimpanan ke DB (Hash string)
    ->dehydrateStateUsing(fn (string $state): string => Hash::make($state))
    // 3. Batalkan state dehydrate (jangan simpan) jika value kosong (misal saat edit tapi password kosong)
    ->saved(fn (?string $state): bool => filled($state))
```

---

### Unique Validation

```php
V::textInput('email')
    ->label('Email')
    ->type('email')
    ->required()
    ->uniqueIgnoreRecord()  // auto skip current record on edit
```

---

### SoftDeletes Support

Jika model menggunakan `SoftDeletes`, tambahkan:

```php
// Di resource
protected static bool $softDeletes = true;

// Actions
->actions([
    EditAction::make(),
    DeleteAction::make(),
    RestoreAction::make(),
    ForceDeleteAction::make(),
])

// Bulk Actions
->bulkActions([
    ActionGroup::make('Aksi Massal')
        ->icon('list')
        ->actions([
            DeleteBulkAction::make(),
            RestoreBulkAction::make(),
            ForceDeleteBulkAction::make(),
        ]),
])

// Filter
->filters([
    SelectFilter::make()->withTrashed(),
])
```

---

### Widgets

Widget ditampilkan di halaman dashboard atau sebagai komponen tambahan:

#### Stats Overview

```php
use App\Vuelament\Components\Widgets\StatsOverviewWidget;
use App\Vuelament\Components\Widgets\Stat;

StatsOverviewWidget::make()
    ->stats([
        Stat::make('Total User', User::count())
            ->description('User terdaftar')
            ->icon('users')
            ->color('primary'),
        Stat::make('Pendapatan', 'Rp 15.000.000')
            ->description('+12% dari bulan lalu')
            ->icon('trending-up')
            ->color('success'),
        Stat::make('Order Pending', Order::where('status', 'pending')->count())
            ->description('Menunggu proses')
            ->icon('clock')
            ->color('warning'),
    ])
```

#### Chart Widget

```php
use App\Vuelament\Components\Widgets\ChartWidget;

ChartWidget::make()
    ->heading('Penjualan Bulanan')
    ->type('bar')  // bar, line, pie, doughnut
    ->data([
        'labels' => ['Jan', 'Feb', 'Mar', 'Apr'],
        'datasets' => [
            [
                'label' => 'Penjualan',
                'data' => [120, 190, 300, 250],
            ]
        ]
    ])
```

#### Table Widget

```php
use App\Vuelament\Components\Widgets\TableWidget;

TableWidget::make()
    ->heading('Order Terbaru')
    ->columns([...])
    ->query(fn() => Order::latest()->limit(5)->get())
```

---

### Dark Mode

Dark/Light mode toggle tersedia di header top bar. Preferensi disimpan di `localStorage` dan otomatis mendeteksi preferensi sistem.

---

### Icons

Vuelament menggunakan [Lucide Icons](https://lucide.dev/icons/). Semua icon didefinisikan dengan format kebab-case:

```php
protected static string $icon = 'users';         // → Users
protected static string $icon = 'file-text';      // → FileText
protected static string $icon = 'layout-dashboard'; // → LayoutDashboard
```

---

## 📁 Struktur Direktori

Kini semua komponen terkait sebuah resource (Resource config, Controller, Model Service, dan Pages spesifik) di-kelompokkan menjadi satu folder untuk memudahkan isolasi business logic (_Colocated Pattern_):

```
app/Vuelament/
├── Admin/
│   ├── Resources/
│   │   ├── User/
│   │   │   ├── UserResource.php
│   │   │   ├── UserController.php
│   │   │   ├── UserService.php
│   │   │   ├── Report.php             # Custom Page atau widget khusus resource
│   │   │   └── ...                    # File lain yang berhubungan khusus dengan User
│   ├── Pages/              # Custom pages global/standalone
│   └── Widgets/            # Dashboard widgets global/standalone
├── Commands/
│   ├── MakeResourceCommand.php
│   ├── MakeUserCommand.php
│   ├── MakePanelCommand.php
│   └── MakePageCommand.php
├── Components/
│   ├── Actions/            # ActionGroup, BulkAction, CreateAction, dll
│   ├── Filters/            # SelectFilter, ToggleFilter, dll
│   ├── Form/               # TextInput, Select, DatePicker, dll
│   ├── Infolists/          # TextEntry, ImageEntry
│   ├── Layout/             # Grid, Section, Card
│   ├── Table/              # Table, Column, row Actions
│   └── Widgets/            # StatsOverview, Chart, TableWidget
├── Core/
│   ├── BaseResource.php
│   ├── BaseComponent.php
│   ├── PageSchema.php
│   ├── Panel.php
│   ├── NavigationGroup.php
│   └── NavigationItem.php
├── Facades/
│   └── V.php               # Shorthand facade
├── Http/
│   ├── Traits/ResourceController.php
│   └── Middleware/
├── Providers/
│   └── AdminPanelProvider.php
├── Stubs/                   # Template untuk code generation
└── VuelamentServiceProvider.php

resources/js/
├── Layouts/
│   └── DashboardLayout.vue  # Sidebar + topbar + dark mode
├── Pages/Vuelament/
│   ├── Auth/Login.vue
│   ├── Dashboard.vue
│   └── Resource/
│       ├── Index.vue         # Table + bulk actions
│       ├── Create.vue        # Form create
│       └── Edit.vue          # Form edit
└── components/ui/            # shadcn-vue components
```

---

## 🧪 Artisan Commands

| Command                                 | Deskripsi                          |
| --------------------------------------- | ---------------------------------- |
| `vuelament:resource {name}`             | Generate resource + controller     |
| `vuelament:resource {name} --generate`  | Auto-generate dari database schema |
| `vuelament:user`                        | Buat user admin                    |
| `vuelament:panel {name}`                | Generate panel provider baru       |
| `vuelament:page {name} --resource=User` | Generate custom page               |

---

## 📄 License

MIT License
