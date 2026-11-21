<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\XinfutongAccountResource\Pages;
use App\Modules\Domains\Xinfutong\Models\Account;
use App\Modules\Infrastructure\Filament\Concerns\HasResourcePermission;
use BackedEnum;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use UnitEnum;

class XinfutongAccountResource extends Resource
{
    use HasResourcePermission;

    protected static ?string $model = Account::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-building-office';

    protected static string|UnitEnum|null $navigationGroup = '薪福通';

    protected static ?string $navigationLabel = '账号管理';

    protected static ?string $modelLabel = '账号';

    protected static ?string $pluralModelLabel = '账号';

    protected static ?int $navigationSort = 0;

    protected static function getPermissionPrefix(): string
    {
        return 'xinfutong-account';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Schemas\Components\Section::make('基本信息')
                    ->schema([
                        Forms\Components\TextInput::make('company_name')
                            ->label('企业名称')
                            ->required()
                            ->maxLength(100)
                            ->unique(ignoreRecord: true),

                        Forms\Components\TextInput::make('base_url')
                            ->label('API 基础地址')
                            ->required()
                            ->maxLength(500)
                            ->default('https://xft.cmbchina.com/open'),

                        Forms\Components\TextInput::make('app_id')
                            ->label('AppId')
                            ->required()
                            ->maxLength(100),

                        Forms\Components\TextInput::make('authority_secret')
                            ->label('AuthoritySecret')
                            ->required()
                            ->maxLength(200)
                            ->password()
                            ->revealable(),
                    ])
                    ->columns(2),

                Schemas\Components\Section::make('密钥配置')
                    ->schema([
                        Forms\Components\Textarea::make('sign_private_key')
                            ->label('SM2 签名私钥')
                            ->required()
                            ->rows(6)
                            ->helperText('PEM 格式'),

                        Forms\Components\Textarea::make('verify_public_key')
                            ->label('SM2 验签公钥')
                            ->rows(6)
                            ->helperText('PEM 格式（可选）'),
                    ]),

                Schemas\Components\Section::make('其他配置')
                    ->schema([
                        Forms\Components\TextInput::make('timeout')
                            ->label('超时时间（秒）')
                            ->numeric()
                            ->default(30)
                            ->minValue(5)
                            ->maxValue(300),

                        Forms\Components\Toggle::make('is_default')
                            ->label('设为默认账号')
                            ->default(false),

                        Forms\Components\Toggle::make('is_active')
                            ->label('启用')
                            ->default(true),

                        Forms\Components\Textarea::make('remark')
                            ->label('备注')
                            ->rows(3),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('company_name')
                    ->label('企业名称')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('app_id')
                    ->label('AppId')
                    ->limit(20)
                    ->copyable(),

                Tables\Columns\IconColumn::make('is_default')
                    ->label('默认')
                    ->boolean(),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('启用')
                    ->boolean(),

                Tables\Columns\TextColumn::make('timeout')
                    ->label('超时')
                    ->suffix('s'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('创建时间')
                    ->dateTime('Y-m-d H:i')
                    ->sortable(),
            ])
            ->defaultSort('is_default', 'desc')
            ->actions([
                Actions\EditAction::make(),
                Actions\DeleteAction::make(),
                Actions\Action::make('setDefault')
                    ->label('设为默认')
                    ->icon('heroicon-o-star')
                    ->color('warning')
                    ->visible(fn (Account $record): bool => ! $record->is_default)
                    ->action(function (Account $record): void {
                        $record->setAsDefault();
                    }),
            ])
            ->bulkActions([
                Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListXinfutongAccounts::route('/'),
            'create' => Pages\CreateXinfutongAccount::route('/create'),
            'edit' => Pages\EditXinfutongAccount::route('/{record}/edit'),
        ];
    }
}
