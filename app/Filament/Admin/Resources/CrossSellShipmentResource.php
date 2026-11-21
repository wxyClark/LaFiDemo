<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\CrossSellShipmentResource\Pages;
use App\Modules\CrossSell\Fulfillment\Enums\ShipmentStatus;
use App\Modules\CrossSell\Fulfillment\Models\Shipment;
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
 * 发货单管理 Resource
 */
class CrossSellShipmentResource extends Resource
{
    use HasResourcePermission;

    protected static ?string $model = Shipment::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-truck';

    protected static string|UnitEnum|null $navigationGroup = 'CrossSell 管理';

    protected static ?string $navigationLabel = '发货管理';

    protected static ?string $modelLabel = '发货单';

    protected static ?string $pluralModelLabel = '发货单';

    protected static ?int $navigationSort = 4;

    protected static function getPermissionPrefix(): string
    {
        return 'cross-sell-shipment';
    }

    /**
     * 表单定义
     */
    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Schemas\Components\Section::make('发货信息')
                    ->schema([
                        Forms\Components\TextInput::make('shipment_no')
                            ->label('发货单号')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(50),

                        Forms\Components\TextInput::make('tracking_no')
                            ->label('物流单号')
                            ->maxLength(100),

                        Forms\Components\Select::make('status')
                            ->label('状态')
                            ->options(collect(ShipmentStatus::cases())->mapWithKeys(fn ($case) => [$case->value => $case->label()])->toArray())
                            ->required()
                            ->default('pending'),

                        Forms\Components\TextInput::make('carrier')
                            ->label('承运商')
                            ->maxLength(100),
                    ])
                    ->columns(2),

                Schemas\Components\Section::make('物流信息')
                    ->schema([
                        Forms\Components\TextInput::make('weight')
                            ->label('重量（kg）')
                            ->numeric(),

                        Forms\Components\TextInput::make('shipping_cost')
                            ->label('运费')
                            ->numeric()
                            ->prefix('$'),

                        Forms\Components\DateTimePicker::make('shipped_at')
                            ->label('发货时间'),

                        Forms\Components\DateTimePicker::make('delivered_at')
                            ->label('签收时间'),
                    ])
                    ->columns(2),
            ]);
    }

    /**
     * 表格定义
     */
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('shipment_no')
                    ->label('发货单号')
                    ->searchable()
                    ->sortable()
                    ->copyable(),

                Tables\Columns\TextColumn::make('order.order_no')
                    ->label('订单号')
                    ->sortable(),

                Tables\Columns\TextColumn::make('tracking_no')
                    ->label('物流单号')
                    ->copyable(),

                Tables\Columns\TextColumn::make('carrier')
                    ->label('承运商'),

                Tables\Columns\TextColumn::make('status')
                    ->label('状态')
                    ->badge()
                    ->formatStateUsing(fn (ShipmentStatus $state): string => $state->label())
                    ->color(fn (ShipmentStatus $state): string => $state->getColor()),

                Tables\Columns\TextColumn::make('shipped_at')
                    ->label('发货时间')
                    ->dateTime('Y-m-d H:i')
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('创建时间')
                    ->dateTime('Y-m-d H:i')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options(collect(ShipmentStatus::cases())->mapWithKeys(fn ($case) => [$case->value => $case->label()])->toArray()),
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
                Schemas\Components\Section::make('发货信息')
                    ->schema([
                        Infolists\Components\TextEntry::make('shipment_no')
                            ->label('发货单号'),

                        Infolists\Components\TextEntry::make('tracking_no')
                            ->label('物流单号'),

                        Infolists\Components\TextEntry::make('status')
                            ->label('状态')
                            ->badge()
                            ->formatStateUsing(fn (ShipmentStatus $state): string => $state->label())
                            ->color(fn (ShipmentStatus $state): string => $state->getColor()),

                        Infolists\Components\TextEntry::make('carrier')
                            ->label('承运商'),
                    ])
                    ->columns(2),

                Schemas\Components\Section::make('物流信息')
                    ->schema([
                        Infolists\Components\TextEntry::make('weight')
                            ->label('重量')
                            ->suffix('kg'),

                        Infolists\Components\TextEntry::make('shipping_cost')
                            ->label('运费')
                            ->money('USD'),

                        Infolists\Components\TextEntry::make('shipped_at')
                            ->label('发货时间')
                            ->dateTime('Y-m-d H:i'),

                        Infolists\Components\TextEntry::make('delivered_at')
                            ->label('签收时间')
                            ->dateTime('Y-m-d H:i'),
                    ])
                    ->columns(2),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCrossSellShipments::route('/'),
            'create' => Pages\CreateCrossSellShipment::route('/create'),
            'edit' => Pages\EditCrossSellShipment::route('/{record}/edit'),
            'view' => Pages\ViewCrossSellShipment::route('/{record}'),
        ];
    }
}
