<?php

namespace App\Vuelament\Admin\Resources\User;

use App\Models\User;
use App\Vuelament\Facades\V;
use App\Vuelament\Core\PageSchema;
use App\Vuelament\Core\BaseResource;
use Illuminate\Support\Facades\Hash;
use App\Vuelament\Components\Table\Table;
use App\Vuelament\Components\Table\Column;
use App\Vuelament\Components\Actions\ActionGroup;
use App\Vuelament\Components\Table\FiltersLayout;
use App\Vuelament\Components\Actions\CreateAction;
use App\Vuelament\Components\Filters\SelectFilter;
use App\Vuelament\Components\Table\Actions\Action;
use App\Vuelament\Components\Actions\DeleteBulkAction;
use App\Vuelament\Components\Table\Actions\EditAction;
use App\Vuelament\Components\Actions\RestoreBulkAction;
use App\Vuelament\Components\Table\Actions\DeleteAction;
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
                        Column::make('id')
                            ->label('ID')
                            ->sortable(),
                        Column::make('name')
                            ->label('Name')
                            ->sortable()
                            ->searchable(),
                        Column::make('email')
                            ->label('Email')
                            ->sortable()
                            ->searchable(),
                        Column::make('created_at')
                            ->label('Dibuat')
                            ->dateFormat('d/m/Y')
                            ->sortable()
                            ->toggleable(true, true),
                        Column::make('updated_at')
                            ->label('Diupdate')
                            ->dateFormat('d/m/Y')
                            ->sortable()
                            ->toggleable(true, true),
                    ])
                    ->actions([
                        Action::make('report')
                            ->icon('file')
                            ->color('success')
                            ->label('Laporan')
                            ->url(fn(User $user) => ReportPage::getUrl(['record' => $user->id])),
                        EditAction::make(),
                        DeleteAction::make(),
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
                        SelectFilter::make()
                            ->withTrashed()
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
                V::grid(1)
                    ->schema([
                        V::textInput('name')
                            ->label('Name')
                            ->required()
                            ->uniqueIgnoreRecord(),
                        V::textInput('email')
                            ->label('Email')
                            ->type('email')
                            ->email()
                            ->required()
                            ->uniqueIgnoreRecord(),
                        V::textInput('password')
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