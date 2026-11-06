<?php

namespace App\Filament\Tenant\Resources;

use App\Filament\Tenant\Resources\VariantResource\Pages;
use App\Modules\Catalog\Application\UpdateVariantStatus;
use App\Modules\Catalog\Domain\VariantStatus;
use App\Modules\Catalog\Infrastructure\ProductVariant;
use App\Modules\Tenancy\Application\CurrentTenant;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * 变体资源（Tenant Panel；业务范围限定在解析出的租户内）。
 *
 * 租户范围：getEloquentQuery 按 CurrentTenant 过滤 + VariantPolicy
 * 记录级校验；状态变更经 UpdateVariantStatus 用例。
 */
class VariantResource extends Resource
{
    protected static ?string $model = ProductVariant::class;

    protected static ?string $navigationLabel = '变体';

    protected static ?string $modelLabel = '变体';

    protected static ?string $pluralModelLabel = '变体';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('tenant_id', app(CurrentTenant::class)->id());
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->schema([
            TextEntry::make('sku')->label('SKU'),
            TextEntry::make('status')->label('状态')
                ->formatStateUsing(fn (VariantStatus $state) => $state->value),
            TextEntry::make('created_at')->label('创建时间')->dateTime('Y-m-d H:i'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->label('ID')->sortable(),
                TextColumn::make('sku')->label('SKU')->searchable()->sortable(),
                TextColumn::make('product_id')->label('商品 ID')->sortable(),
                TextColumn::make('status')->label('状态')->badge()
                    ->formatStateUsing(fn (VariantStatus $state) => $state->value)
                    ->color(fn (VariantStatus $state) => $state === VariantStatus::Active ? 'success' : 'danger'),
                TextColumn::make('created_at')->label('创建时间')->dateTime('Y-m-d H:i')->sortable(),
            ])
            ->actions([
                Action::make('toggleStatus')
                    ->label(fn (ProductVariant $record) => $record->status === VariantStatus::Active ? '停用' : '启用')
                    ->icon(fn (ProductVariant $record) => $record->status === VariantStatus::Active ? 'heroicon-o-pause' : 'heroicon-o-play')
                    ->color(fn (ProductVariant $record) => $record->status === VariantStatus::Active ? 'danger' : 'success')
                    ->requiresConfirmation()
                    ->form([
                        TextInput::make('reason')->label('操作原因')->required(),
                    ])
                    ->authorize(fn (ProductVariant $record) => auth()->user()?->can('update', $record) ?? false)
                    ->action(function (ProductVariant $record, array $data): void {
                        $newStatus = $record->status === VariantStatus::Active
                            ? VariantStatus::Disabled
                            : VariantStatus::Active;

                        app(UpdateVariantStatus::class)(
                            (int) $record->getKey(),
                            $newStatus,
                            $data['reason'],
                        );
                    }),
            ])
            ->defaultSort('id');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListVariants::route('/'),
            'view' => Pages\ViewVariant::route('/{record}'),
        ];
    }
}
