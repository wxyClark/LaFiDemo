<?php

namespace App\Filament\Tenant\Resources;

use App\Filament\Tenant\Resources\StockResource\Pages;
use App\Modules\Inventory\Application\AdjustStock;
use App\Modules\Inventory\Application\StockAdjustment;
use App\Modules\Inventory\Domain\StockMovementType;
use App\Modules\Inventory\Infrastructure\Stock;
use App\Modules\Tenancy\Application\CurrentTenant;
use Filament\Actions\Action;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

/**
 * 库存余额资源（Tenant Panel；只读投影 + 库存调整 Action）。
 *
 * 正确性：库存是 AdjustStock 用例维护的只读投影，本资源不提供
 * create/update/delete 直接写入口（StockPolicy::create/update/delete 返回 false）；
 * 调整动作调 AdjustStock（事务条件更新 + 幂等键 + 审计 + 跨租户写拒绝，
 * AGENTS.md §4 / data-and-cache.md §2）。
 */
class StockResource extends Resource
{
    protected static ?string $model = Stock::class;

    protected static ?string $navigationLabel = '库存';

    protected static ?string $modelLabel = '库存';

    protected static ?string $pluralModelLabel = '库存';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('tenant_id', app(CurrentTenant::class)->id());
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->label('ID')->sortable(),
                TextColumn::make('variant_id')->label('变体 ID')->sortable(),
                TextColumn::make('on_hand')->label('现有库存')->sortable(),
                TextColumn::make('version')->label('版本')->sortable(),
                TextColumn::make('updated_at')->label('更新时间')->dateTime('Y-m-d H:i')->sortable(),
            ])
            ->actions([
                Action::make('adjust')
                    ->label('调整库存')
                    ->icon('heroicon-o-adjustments-horizontal')
                    ->color('primary')
                    ->form([
                        Select::make('movement')
                            ->label('方向')->required()
                            ->options([
                                StockMovementType::In->value => '入库',
                                StockMovementType::Out->value => '出库',
                            ]),
                        TextInput::make('quantity')
                            ->label('数量')->numeric()->minValue(1)->required(),
                        TextInput::make('reason')->label('原因')->required()
                            ->helperText('审计字段，必填'),
                        Hidden::make('idempotency_key')
                            ->default(fn () => Str::uuid()->toString()),
                    ])
                    ->authorize(fn (Stock $record) => auth()->user()?->can('adjust', $record) ?? false)
                    ->action(function (Stock $record, array $data): void {
                        $tenant = app(CurrentTenant::class)->required();
                        $movement = StockMovementType::from($data['movement']);

                        $result = app(AdjustStock::class)->adjust(
                            $tenant,
                            new StockAdjustment(
                                variantId: (int) $record->variant_id,
                                quantity: (int) $data['quantity'],
                                movement: $movement,
                                idempotencyKey: $data['idempotency_key'],
                                reason: $data['reason'],
                                operatorType: 'tenant_user',
                                operatorId: (int) auth()->id(),
                                referenceType: 'filament_stock_adjustment',
                                referenceId: (int) $record->getKey(),
                            ),
                        );

                        $actionLabel = $result->duplicated ? '（幂等重放，未重复执行）' : '';

                        Notification::make()
                            ->title("调整成功：{$result->quantityBefore} → {$result->quantityAfter}{$actionLabel}")
                            ->success()
                            ->send();
                    }),
            ])
            ->defaultSort('id');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListStock::route('/'),
        ];
    }
}
