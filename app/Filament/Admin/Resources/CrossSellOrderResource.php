<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\CrossSellOrderResource\OrderExporter;
use App\Filament\Admin\Resources\CrossSellOrderResource\Pages;
use App\Modules\CrossSell\Order\Enums\OrderStatus;
use App\Modules\CrossSell\Order\Models\Order;
use App\Modules\CrossSell\Warehouse\Enums\PlatformType;
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
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use UnitEnum;

/**
 * 订单管理 Resource
 */
class CrossSellOrderResource extends Resource
{
    use HasResourcePermission;

    protected static ?string $model = Order::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-shopping-cart';

    protected static string|UnitEnum|null $navigationGroup = 'CrossSell 管理';

    protected static ?string $navigationLabel = '订单管理';

    protected static ?string $modelLabel = '订单';

    protected static ?string $pluralModelLabel = '订单';

    protected static ?int $navigationSort = 3;

    protected static function getPermissionPrefix(): string
    {
        return 'cross-sell-order';
    }

    /**
     * 表单定义
     */
    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Schemas\Components\Section::make('订单信息')
                    ->schema([
                        Forms\Components\TextInput::make('order_no')
                            ->label('订单号')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(50),

                        Forms\Components\Select::make('platform')
                            ->label('订单来源')
                            ->options(collect(PlatformType::cases())->mapWithKeys(fn ($case) => [$case->value => $case->label()])->toArray())
                            ->required()
                            ->default('manual'),

                        Forms\Components\Select::make('status')
                            ->label('订单状态')
                            ->options(collect(OrderStatus::cases())->mapWithKeys(fn ($case) => [$case->value => $case->label()])->toArray())
                            ->required()
                            ->default('pending'),
                    ])
                    ->columns(3),

                Schemas\Components\Section::make('买家信息')
                    ->schema([
                        Forms\Components\TextInput::make('buyer_name')
                            ->label('买家姓名')
                            ->maxLength(255),

                        Forms\Components\TextInput::make('buyer_email')
                            ->label('买家邮箱')
                            ->email()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('buyer_phone')
                            ->label('买家电话')
                            ->tel()
                            ->maxLength(50),
                    ])
                    ->columns(3),

                Schemas\Components\Section::make('收货信息')
                    ->schema([
                        Forms\Components\TextInput::make('shipping_name')
                            ->label('收件人')
                            ->maxLength(255),

                        Forms\Components\TextInput::make('shipping_phone')
                            ->label('收件电话')
                            ->tel()
                            ->maxLength(50),

                        Forms\Components\TextInput::make('shipping_country')
                            ->label('目的国')
                            ->maxLength(10),

                        Forms\Components\KeyValue::make('shipping_address')
                            ->label('收货地址'),
                    ])
                    ->columns(2),

                Schemas\Components\Section::make('金额信息')
                    ->schema([
                        Forms\Components\TextInput::make('subtotal')
                            ->label('商品小计')
                            ->numeric()
                            ->default(0)
                            ->prefix('$'),

                        Forms\Components\TextInput::make('shipping_fee')
                            ->label('运费')
                            ->numeric()
                            ->default(0)
                            ->prefix('$'),

                        Forms\Components\TextInput::make('tax')
                            ->label('税费')
                            ->numeric()
                            ->default(0)
                            ->prefix('$'),

                        Forms\Components\TextInput::make('discount')
                            ->label('优惠金额')
                            ->numeric()
                            ->default(0)
                            ->prefix('$'),

                        Forms\Components\TextInput::make('total')
                            ->label('订单总额')
                            ->numeric()
                            ->required()
                            ->prefix('$'),

                        Forms\Components\Select::make('currency')
                            ->label('币种')
                            ->options(['USD' => 'USD', 'CNY' => 'CNY', 'EUR' => 'EUR'])
                            ->default('USD'),
                    ])
                    ->columns(3),

                Schemas\Components\Section::make('备注')
                    ->schema([
                        Forms\Components\Textarea::make('note')
                            ->label('订单备注')
                            ->rows(3),

                        Forms\Components\Textarea::make('customer_note')
                            ->label('客户备注')
                            ->rows(3),
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
                Tables\Columns\TextColumn::make('order_no')
                    ->label('订单号')
                    ->searchable()
                    ->sortable()
                    ->copyable(),

                Tables\Columns\TextColumn::make('platform')
                    ->label('来源')
                    ->badge()
                    ->formatStateUsing(fn (PlatformType $state): string => $state->label())
                    ->color(fn (PlatformType $state): string => $state->getColor()),

                Tables\Columns\TextColumn::make('buyer_name')
                    ->label('买家')
                    ->searchable(),

                Tables\Columns\TextColumn::make('buyer_email')
                    ->label('买家邮箱')
                    ->searchable()
                    ->limit(30),

                Tables\Columns\TextColumn::make('total')
                    ->label('金额')
                    ->money('USD')
                    ->sortable(),

                Tables\Columns\TextColumn::make('status')
                    ->label('状态')
                    ->badge()
                    ->formatStateUsing(fn (OrderStatus $state): string => $state->label())
                    ->color(fn (OrderStatus $state): string => $state->getColor()),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('创建时间')
                    ->dateTime('Y-m-d H:i')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options(collect(OrderStatus::cases())->mapWithKeys(fn ($case) => [$case->value => $case->label()])->toArray()),

                Tables\Filters\SelectFilter::make('platform')
                    ->options(collect(PlatformType::cases())->mapWithKeys(fn ($case) => [$case->value => $case->label()])->toArray()),

                Tables\Filters\Filter::make('created_at')
                    ->form([
                        Forms\Components\DatePicker::make('created_from')
                            ->label('开始日期')
                            ->placeholder('开始日期'),
                        Forms\Components\DatePicker::make('created_until')
                            ->label('结束日期')
                            ->placeholder('结束日期'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['created_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['created_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    }),
            ])
            ->actions([
                Actions\EditAction::make(),
                Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Actions\BulkActionGroup::make([
                    Actions\DeleteBulkAction::make(),
                    Actions\BulkAction::make('confirm_orders')
                        ->label('批量确认')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->action(fn (Collection $records) => $records->each->confirm())
                        ->requiresConfirmation()
                        ->modalHeading('批量确认订单')
                        ->modalDescription('确认将选中的订单状态改为已确认？'),
                    Actions\BulkAction::make('cancel_orders')
                        ->label('批量取消')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->action(fn (Collection $records) => $records->each->cancel())
                        ->requiresConfirmation()
                        ->modalHeading('批量取消订单')
                        ->modalDescription('确认将选中的订单状态改为已取消？'),
                ]),
                Actions\ExportBulkAction::make()
                    ->exporter(OrderExporter::class),
            ]);
    }

    /**
     * 信息列表定义
     */
    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Schemas\Components\Section::make('订单信息')
                    ->schema([
                        Infolists\Components\TextEntry::make('order_no')
                            ->label('订单号'),

                        Infolists\Components\TextEntry::make('platform')
                            ->label('来源')
                            ->badge()
                            ->formatStateUsing(fn (PlatformType $state): string => $state->label())
                            ->color(fn (PlatformType $state): string => $state->getColor()),

                        Infolists\Components\TextEntry::make('status')
                            ->label('状态')
                            ->badge()
                            ->formatStateUsing(fn (OrderStatus $state): string => $state->label())
                            ->color(fn (OrderStatus $state): string => $state->getColor()),
                    ])
                    ->columns(3),

                Schemas\Components\Section::make('买家信息')
                    ->schema([
                        Infolists\Components\TextEntry::make('buyer_name')
                            ->label('买家姓名'),

                        Infolists\Components\TextEntry::make('buyer_email')
                            ->label('买家邮箱'),

                        Infolists\Components\TextEntry::make('buyer_phone')
                            ->label('买家电话'),
                    ])
                    ->columns(3),

                Schemas\Components\Section::make('金额信息')
                    ->schema([
                        Infolists\Components\TextEntry::make('subtotal')
                            ->label('商品小计')
                            ->money('USD'),

                        Infolists\Components\TextEntry::make('shipping_fee')
                            ->label('运费')
                            ->money('USD'),

                        Infolists\Components\TextEntry::make('tax')
                            ->label('税费')
                            ->money('USD'),

                        Infolists\Components\TextEntry::make('discount')
                            ->label('优惠金额')
                            ->money('USD'),

                        Infolists\Components\TextEntry::make('total')
                            ->label('订单总额')
                            ->money('USD')
                            ->weight('bold'),
                    ])
                    ->columns(5),

                Schemas\Components\Section::make('订单明细')
                    ->schema([
                        Infolists\Components\RepeatableEntry::make('items')
                            ->label('商品列表')
                            ->schema([
                                Infolists\Components\TextEntry::make('product_title')
                                    ->label('商品'),

                                Infolists\Components\TextEntry::make('quantity')
                                    ->label('数量'),

                                Infolists\Components\TextEntry::make('unit_price')
                                    ->label('单价')
                                    ->money('USD'),

                                Infolists\Components\TextEntry::make('total_price')
                                    ->label('小计')
                                    ->money('USD'),
                            ])
                            ->columns(4),
                    ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCrossSellOrders::route('/'),
            'create' => Pages\CreateCrossSellOrder::route('/create'),
            'edit' => Pages\EditCrossSellOrder::route('/{record}/edit'),
            'view' => Pages\ViewCrossSellOrder::route('/{record}'),
        ];
    }
}
