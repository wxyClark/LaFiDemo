<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\RoleResource\Pages;
use BackedEnum;
use Filament\Actions;
use Filament\Forms;
use Filament\Infolists;
use Filament\Resources\Resource;
use Filament\Schemas;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use UnitEnum;

class RoleResource extends Resource
{
    protected static ?string $model = Role::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-shield-check';

    protected static string|UnitEnum|null $navigationGroup = '系统管理';

    protected static ?string $navigationLabel = '角色管理';

    protected static ?string $modelLabel = '角色';

    protected static ?string $pluralModelLabel = '角色';

    protected static ?int $navigationSort = 90;

    public static function canViewAny(): bool
    {
        $user = auth('admin')->user();

        if ($user?->hasRole('super_admin', 'admin')) {
            return true;
        }

        return $user->can('role.view');
    }

    public static function canCreate(): bool
    {
        $user = auth('admin')->user();

        if ($user?->hasRole('super_admin', 'admin')) {
            return true;
        }

        return $user->can('role.create');
    }

    public static function canEdit($record): bool
    {
        $user = auth('admin')->user();

        if ($user?->hasRole('super_admin', 'admin')) {
            return true;
        }

        return $user->can('role.edit');
    }

    public static function canDelete($record): bool
    {
        $user = auth('admin')->user();

        if ($user?->hasRole('super_admin', 'admin')) {
            return true;
        }

        return $user->can('role.delete');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Schemas\Components\Section::make('角色信息')
                ->schema([
                    Forms\Components\TextInput::make('name')
                        ->label('角色标识')
                        ->required()
                        ->unique(ignoreRecord: true)
                        ->dehydrated()
                        ->readOnly(fn (?Role $record) => in_array($record?->name, ['super_admin'])),
                    Forms\Components\TextInput::make('label')
                        ->label('显示名称')
                        ->required(),
                ])
                ->columns(2),

            Schemas\Components\Section::make('权限分配')
                ->schema([
                    Forms\Components\CheckboxList::make('permissions')
                        ->relationship('permissions', 'name')
                        ->label('')
                        ->columns(3)
                        ->searchable()
                        ->getOptionLabelFromRecordUsing(fn (Permission $record): string => $record->name),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),

                Tables\Columns\TextColumn::make('name')
                    ->label('角色标识')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'super_admin' => 'danger',
                        'admin' => 'warning',
                        'operator' => 'info',
                        'viewer' => 'gray',
                        default => 'primary',
                    }),

                Tables\Columns\TextColumn::make('permissions_count')
                    ->label('权限数')
                    ->counts('permissions')
                    ->badge(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('创建时间')
                    ->dateTime('Y-m-d H:i:s')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->actions([
                Actions\EditAction::make(),
                Actions\DeleteAction::make()
                    ->requiresConfirmation()
                    ->modalHeading('删除角色')
                    ->modalDescription('确定要删除此角色吗？已分配此角色的管理员将失去对应权限。')
                    ->visible(fn (Role $record): bool => ! in_array($record->name, ['super_admin'])),
            ])
            ->bulkActions([
                Actions\DeleteBulkAction::make()
                    ->visible(fn () => false),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Schemas\Components\Section::make('角色信息')
                    ->schema([
                        Infolists\Components\TextEntry::make('name')
                            ->label('角色标识')
                            ->badge(),

                        Infolists\Components\TextEntry::make('created_at')
                            ->label('创建时间')
                            ->dateTime('Y-m-d H:i:s'),
                    ])
                    ->columns(2),

                Schemas\Components\Section::make('权限列表')
                    ->schema([
                        Infolists\Components\RepeatableEntry::make('permissions')
                            ->label('')
                            ->schema([
                                Infolists\Components\TextEntry::make('name')
                                    ->label('权限')
                                    ->badge(),
                            ])
                            ->columns(3),
                    ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRoles::route('/'),
            'create' => Pages\CreateRole::route('/create'),
            'edit' => Pages\EditRole::route('/{record}/edit'),
            'view' => Pages\ViewRole::route('/{record}'),
        ];
    }
}
