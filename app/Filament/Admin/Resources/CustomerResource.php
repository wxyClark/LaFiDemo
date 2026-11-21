<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\CustomerResource\Pages;
use App\Modules\Infrastructure\Filament\Concerns\HasResourcePermission;
use App\Modules\Models\Customer;
use BackedEnum;
use Filament\Actions;
use Filament\Forms;
use Filament\Infolists;
use Filament\Resources\Resource;
use Filament\Schemas;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use UnitEnum;

class CustomerResource extends Resource
{
    use HasResourcePermission;

    protected static ?string $model = Customer::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-users';

    protected static string|UnitEnum|null $navigationGroup = '用户管理';

    protected static ?int $navigationSort = 1;

    protected static function getPermissionPrefix(): string
    {
        return 'customer';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Schemas\Components\Section::make('用户信息')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('用户名')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('email')
                            ->label('邮箱')
                            ->email()
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true),

                        Forms\Components\TextInput::make('phone')
                            ->label('手机号')
                            ->tel()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true),

                        Forms\Components\DateTimePicker::make('email_verified_at')
                            ->label('邮箱验证时间')
                            ->disabled(),
                    ])->columns(2),
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
                    ->label('用户名')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('email')
                    ->label('邮箱')
                    ->searchable(),

                Tables\Columns\TextColumn::make('phone')
                    ->label('手机号')
                    ->searchable(),

                Tables\Columns\TextColumn::make('email_verified_at')
                    ->label('邮箱验证')
                    ->dateTime('Y-m-d H:i')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('注册时间')
                    ->dateTime('Y-m-d H:i')
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Actions\ViewAction::make(),
                Actions\EditAction::make(),
            ])
            ->bulkActions([
                Actions\BulkActionGroup::make([
                    Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Schemas\Components\Section::make('用户详情')
                    ->schema([
                        Infolists\Components\TextEntry::make('id')
                            ->label('ID'),

                        Infolists\Components\TextEntry::make('name')
                            ->label('用户名'),

                        Infolists\Components\TextEntry::make('email')
                            ->label('邮箱'),

                        Infolists\Components\TextEntry::make('phone')
                            ->label('手机号')
                            ->placeholder('未填写'),

                        Infolists\Components\TextEntry::make('email_verified_at')
                            ->label('邮箱验证时间')
                            ->dateTime('Y-m-d H:i')
                            ->placeholder('未验证'),

                        Infolists\Components\TextEntry::make('created_at')
                            ->label('注册时间')
                            ->dateTime('Y-m-d H:i'),
                    ])->columns(2),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCustomers::route('/'),
            'create' => Pages\CreateCustomer::route('/create'),
            'edit' => Pages\EditCustomer::route('/{record}/edit'),
            'view' => Pages\ViewCustomer::route('/{record}'),
        ];
    }
}
