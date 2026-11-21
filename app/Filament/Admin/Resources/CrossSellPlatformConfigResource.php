<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\CrossSellPlatformConfigResource\Pages;
use App\Modules\CrossSell\Warehouse\Enums\PlatformType;
use App\Modules\CrossSell\Warehouse\Models\PlatformConfig;
use App\Modules\Infrastructure\Filament\Concerns\HasResourcePermission;
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

/**
 * 平台配置 Resource
 */
class CrossSellPlatformConfigResource extends Resource
{
    use HasResourcePermission;

    protected static ?string $model = PlatformConfig::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-globe-alt';

    protected static string|UnitEnum|null $navigationGroup = 'CrossSell 管理';

    protected static ?string $navigationLabel = '平台配置';

    protected static ?string $modelLabel = '平台配置';

    protected static ?string $pluralModelLabel = '平台配置';

    protected static ?int $navigationSort = 6;

    protected static function getPermissionPrefix(): string
    {
        return 'cross-sell-platform-config';
    }

    /**
     * 表单定义
     */
    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Schemas\Components\Section::make('平台信息')
                    ->schema([
                        Forms\Components\Select::make('platform')
                            ->label('平台类型')
                            ->options(collect(PlatformType::cases())->mapWithKeys(fn ($case) => [$case->value => $case->label()])->toArray())
                            ->required()
                            ->unique(ignoreRecord: true),

                        Forms\Components\TextInput::make('shop_name')
                            ->label('店铺名称')
                            ->maxLength(255),
                    ])
                    ->columns(2),

                Schemas\Components\Section::make('API配置')
                    ->schema([
                        Forms\Components\TextInput::make('api_key')
                            ->label('API Key')
                            ->maxLength(255),

                        Forms\Components\TextInput::make('api_secret')
                            ->label('API Secret')
                            ->password()
                            ->maxLength(255),

                        Forms\Components\Textarea::make('access_token')
                            ->label('Access Token')
                            ->rows(3),
                    ])
                    ->columns(1),

                Schemas\Components\Section::make('同步配置')
                    ->schema([
                        Forms\Components\Toggle::make('sync_enabled')
                            ->label('启用同步')
                            ->default(true),

                        Forms\Components\TextInput::make('sync_interval')
                            ->label('同步间隔（分钟）')
                            ->numeric()
                            ->default(30),

                        Forms\Components\Select::make('status')
                            ->label('状态')
                            ->options(['active' => '正常', 'inactive' => '禁用', 'error' => '错误'])
                            ->default('active'),
                    ])
                    ->columns(3),
            ]);
    }

    /**
     * 表格定义
     */
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('platform')
                    ->label('平台')
                    ->badge()
                    ->formatStateUsing(fn (PlatformType $state): string => $state->label())
                    ->color(fn (PlatformType $state): string => $state->getColor()),

                Tables\Columns\TextColumn::make('shop_name')
                    ->label('店铺名称')
                    ->searchable(),

                Tables\Columns\IconColumn::make('sync_enabled')
                    ->label('同步状态')
                    ->boolean(),

                Tables\Columns\TextColumn::make('sync_interval')
                    ->label('同步间隔')
                    ->suffix('分钟'),

                Tables\Columns\TextColumn::make('last_sync_at')
                    ->label('最后同步')
                    ->dateTime('Y-m-d H:i')
                    ->sortable(),

                Tables\Columns\TextColumn::make('status')
                    ->label('状态')
                    ->badge()
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'active' => '正常',
                        'inactive' => '禁用',
                        'error' => '错误',
                        default => $state,
                    })
                    ->color(fn ($state) => match ($state) {
                        'active' => 'success',
                        'inactive' => 'gray',
                        'error' => 'danger',
                        default => 'gray',
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('platform')
                    ->options(collect(PlatformType::cases())->mapWithKeys(fn ($case) => [$case->value => $case->label()])->toArray()),
            ])
            ->actions([
                Actions\EditAction::make(),
                Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Actions\BulkActionGroup::make([
                    Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    /**
     * 信息列表定义
     */
    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Schemas\Components\Section::make('平台信息')
                    ->schema([
                        Infolists\Components\TextEntry::make('platform')
                            ->label('平台类型')
                            ->badge()
                            ->formatStateUsing(fn (PlatformType $state): string => $state->label())
                            ->color(fn (PlatformType $state): string => $state->getColor()),

                        Infolists\Components\TextEntry::make('shop_name')
                            ->label('店铺名称'),

                        Infolists\Components\TextEntry::make('status')
                            ->label('状态')
                            ->badge()
                            ->formatStateUsing(fn ($state) => match ($state) {
                                'active' => '正常',
                                'inactive' => '禁用',
                                'error' => '错误',
                                default => $state,
                            })
                            ->color(fn ($state) => match ($state) {
                                'active' => 'success',
                                'inactive' => 'gray',
                                'error' => 'danger',
                                default => 'gray',
                            }),
                    ])
                    ->columns(3),

                Schemas\Components\Section::make('同步信息')
                    ->schema([
                        Infolists\Components\TextEntry::make('sync_enabled')
                            ->label('同步状态')
                            ->formatStateUsing(fn ($state) => $state ? '已启用' : '已禁用')
                            ->badge()
                            ->color(fn ($state) => $state ? 'success' : 'danger'),

                        Infolists\Components\TextEntry::make('sync_interval')
                            ->label('同步间隔')
                            ->suffix('分钟'),

                        Infolists\Components\TextEntry::make('last_sync_at')
                            ->label('最后同步')
                            ->dateTime('Y-m-d H:i'),
                    ])
                    ->columns(3),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCrossSellPlatformConfigs::route('/'),
            'create' => Pages\CreateCrossSellPlatformConfig::route('/create'),
            'edit' => Pages\EditCrossSellPlatformConfig::route('/{record}/edit'),
            'view' => Pages\ViewCrossSellPlatformConfig::route('/{record}'),
        ];
    }
}
