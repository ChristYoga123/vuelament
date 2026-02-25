<?php

namespace App\Vuelament\Admin\Resources\User;

use App\Models\User;
use App\Vuelament\Core\PageSchema;
use App\Vuelament\Core\BaseResource;
use Illuminate\Support\Facades\Hash;
use App\Vuelament\Components\Form\Radio;
use App\Vuelament\Components\Form\Toggle;
use App\Vuelament\Components\Layout\Grid;
use App\Vuelament\Components\Table\Table;
use App\Vuelament\Components\Form\Checkbox;
use App\Vuelament\Components\Form\Textarea;
use App\Vuelament\Components\Form\FileInput;
use App\Vuelament\Components\Form\TextInput;
use App\Vuelament\Components\Form\DatePicker;
use App\Vuelament\Components\Form\RichEditor;
use App\Vuelament\Components\Form\TimePicker;
use App\Vuelament\Components\Actions\ActionGroup;
use App\Vuelament\Components\Filters\TrashFilter;
use App\Vuelament\Components\Table\FiltersLayout;
use App\Vuelament\Components\Actions\CreateAction;
use App\Vuelament\Components\Filters\SelectFilter;
use App\Vuelament\Components\Form\DateRangePicker;
use App\Vuelament\Components\Table\Actions\Action;
use App\Vuelament\Components\Actions\DeleteBulkAction;
use App\Vuelament\Components\Table\Actions\EditAction;
use App\Vuelament\Components\Table\Columns\TextColumn;
use App\Vuelament\Components\Actions\RestoreBulkAction;
use App\Vuelament\Components\Table\Actions\DeleteAction;
use App\Vuelament\Components\Table\Columns\ToggleColumn;
use App\Vuelament\Components\Table\Actions\RestoreAction;
use App\Vuelament\Components\Actions\ForceDeleteBulkAction;
use App\Vuelament\Components\Table\Actions\ForceDeleteAction;

class UserResource extends BaseResource
{
    protected static string $model = User::class;
    protected static string $slug = 'users';
    protected static string $label = 'User';
    protected static string $icon = 'users';

    // ── Navigation ───────────────────────────────────────
    protected static int $navigationSort = 0;
    // protected static ?string $navigationGroup = 'Master Data';

    public static function tableSchema(): PageSchema
    {
        return PageSchema::make()
            ->title(static::$label)
            ->components([
                Table::make()
                ->query(fn() => User::query()->withoutRole(['super_admin'])->latest())
                    ->columns([
                        TextColumn::make('id')
                            ->label('ID')
                            ->sortable(),
                        TextColumn::make('name')
                            ->label('Name')
                            ->sortable()
                            ->searchable(),
                        TextColumn::make('email')
                            ->label('Email')
                            ->sortable()
                            ->searchable(),
                        TextColumn::make('created_at')
                            ->label('Dibuat')
                            ->dateFormat('d/m/Y')
                            ->sortable()
                            ->toggleable(true, true),
                        TextColumn::make('updated_at')
                            ->label('Diupdate')
                            ->dateFormat('d/m/Y')
                            ->sortable()
                            ->toggleable(true, true),
                        ToggleColumn::make('is_active')
                            ->label('Aktif')
                            ->sortable(),
                        TextColumn::make('keaktifan')
                            ->label('Status')
                            ->getStateUsing(fn(User $user) => $user->is_active ? 'Aktif' : 'Tidak Aktif')
                            ->badge()
                            ->color(fn(User $user) => $user->is_active ? 'success' : 'danger')
                    ])
                    ->actions([
                        Action::make('form')
                            ->icon('form')
                            ->color('success')
                            ->label('Form')
                            ->modalWidth('4xl')
                            ->modalCloseByClickingAway(false)
                            ->modalCancelActionLabel('Tutup')
                            ->modalSubmitAction(false)
                            ->form([
                                TextInput::make('text')
                                    ->required()
                                    ->maxLength(255),
                                TextInput::make('number')
                                    ->required()
                                    ->number(),
                                TextInput::make('email')
                                    ->required()
                                    ->email(),
                                TextInput::make('password')
                                    ->required()
                                    ->password()
                                    ->revealable()
                                    ->minLength(4),
                                Toggle::make('is_active')
                                    ->required(),
                                Radio::make('radio')
                                    ->required()
                                    ->options([
                                        'option1' => 'Option 1',
                                        'option2' => 'Option 2',
                                        'option3' => 'Option 3',
                                    ]),
                                Checkbox::make('checkbox')
                                    ->required()
                                    ->options([
                                        'option1' => 'Option 1',
                                        'option2' => 'Option 2',
                                        'option3' => 'Option 3',
                                    ]),
                                RichEditor::make('content')
                                    ->required(),
                                Textarea::make('textarea')
                                    ->required(),
                                DatePicker::make('date')
                                    ->required(),
                                TimePicker::make('time')
                                    ->required(),
                                DateRangePicker::make('date_range')
                                    ->required(),
                                FileInput::make('file')
                                    ->label('File (Single)')
                                    ->required(),
                                FileInput::make('files')
                                    ->label('File (Multiple)')
                                    ->multiple()
                                    ->required()
                                    ->reorderable(),
                            ]),
                        EditAction::make()
                            ->label('Edit'),
                        DeleteAction::make()
                            ->label('Delete'),
                        RestoreAction::make(),
                        ForceDeleteAction::make(),
                    ])
                    ->bulkActions([
                        ActionGroup::make('Aksi Massal')
                            ->icon('list')
                            ->actions([
                                DeleteBulkAction::make(),
                                RestoreBulkAction::make(),
                                ForceDeleteBulkAction::make(),
                            ]),
                    ])
                    ->filters([
                        TrashFilter::make()
                    ])
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
                Grid::make(1)
                    ->schema([
                        TextInput::make('name')
                            ->label('Name')
                            ->required()
                            ->uniqueIgnoreRecord(),
                        TextInput::make('email')
                            ->label('Email')
                            ->email()
                            ->required()
                            ->uniqueIgnoreRecord(),
                        Toggle::make('is_active')
                            ->label('Status Aktif')
                            ->hint('Matikan ini untuk mencegah user login.'),
                        TextInput::make('password')
                            ->label('Password')
                            ->password()
                            ->revealable()
                            ->dehydrateStateUsing(fn (string $state): string => Hash::make($state))
                            ->saved(fn (?string $state): bool => filled($state))
                            ->required(fn (string $operation): bool => $operation === 'create'),
                    ])
            ]);
    }

    public static function getPages(): array
    {
        return [
            'report' => ReportPage::route('/{record}/report'),
        ];
    }
}