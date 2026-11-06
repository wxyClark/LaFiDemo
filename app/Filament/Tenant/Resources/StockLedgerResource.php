<?php

namespace App\Filament\Tenant\Resources;

use App\Filament\Tenant\Resources\StockLedgerResource\Pages;
use App\Modules\Inventory\Domain\StockMovementType;
use App\Modules\Inventory\Infrastructure\StockLedger;
use App\Modules\Tenancy\Application\CurrentTenant;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * 库存流水资源（Tenant Panel；只读审计事实表）。
 *
 * 只追加：无 create/update/delete（StockLedgerPolicy 全部返回 false）；
 * 租户范围经 CurrentTenant 过滤，记录级校验由 Policy 承担。
 */
class StockLedgerResource extends Resource
{
    protected static ?string $model = StockLedger::class;

    protected static ?string $navigationLabel = '库存流水';

    protected static ?string $modelLabel = '库存流水';

    protected static ?string $pluralModelLabel = '库存流水';

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
                TextColumn::make('movement_type')->label('方向')->badge()
                    ->formatStateUsing(fn (StockMovementType $state) => $state === StockMovementType::In ? '入库' : '出库')
                    ->color(fn (StockMovementType $state) => $state === StockMovementType::In ? 'success' : 'danger'),
                TextColumn::make('quantity_delta')->label('变动量')->sortable(),
                TextColumn::make('quantity_before')->label('变动前')->sortable(),
                TextColumn::make('quantity_after')->label('变动后')->sortable(),
                TextColumn::make('reason')->label('原因'),
                TextColumn::make('operator_type')->label('操作者类型'),
                TextColumn::make('created_at')->label('时间')->dateTime('Y-m-d H:i')->sortable(),
            ])
            ->defaultSort('id', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListStockLedger::route('/'),
        ];
    }
}
