<?php

namespace App\Filament\Tenant\Resources;

use App\Filament\Tenant\Resources\ProductResource\Pages;
use App\Modules\Catalog\Application\UpdateProductStatus;
use App\Modules\Catalog\Domain\ProductStatus;
use App\Modules\Catalog\Infrastructure\Product;
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
 * 商品资源（Tenant Panel；业务范围限定在解析出的租户内）。
 *
 * 租户范围：getEloquentQuery 按 CurrentTenant 过滤 + ProductPolicy
 * 记录级校验；状态变更经 UpdateProductStatus 用例（写入口在模块 Application）。
 * 创建/删除基线禁用（ProductPolicy::create/delete 返回 false）。
 */
class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static ?string $navigationLabel = '商品';

    protected static ?string $modelLabel = '商品';

    protected static ?string $pluralModelLabel = '商品';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('tenant_id', app(CurrentTenant::class)->id());
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->schema([
            TextEntry::make('title')->label('名称'),
            TextEntry::make('slug')->label('Slug'),
            TextEntry::make('status')->label('状态')
                ->formatStateUsing(fn (ProductStatus $state) => $state->value),
            TextEntry::make('created_at')->label('创建时间')->dateTime('Y-m-d H:i'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->label('ID')->sortable(),
                TextColumn::make('title')->label('名称')->searchable()->sortable(),
                TextColumn::make('slug')->label('Slug')->searchable(),
                TextColumn::make('status')->label('状态')->badge()
                    ->formatStateUsing(fn (ProductStatus $state) => $state->value)
                    ->color(fn (ProductStatus $state) => $state === ProductStatus::Active ? 'success' : 'danger'),
                TextColumn::make('created_at')->label('创建时间')->dateTime('Y-m-d H:i')->sortable(),
            ])
            ->actions([
                Action::make('toggleStatus')
                    ->label(fn (Product $record) => $record->status === ProductStatus::Active ? '下架' : '上架')
                    ->icon(fn (Product $record) => $record->status === ProductStatus::Active ? 'heroicon-o-arrow-down' : 'heroicon-o-arrow-up')
                    ->color(fn (Product $record) => $record->status === ProductStatus::Active ? 'danger' : 'success')
                    ->requiresConfirmation()
                    ->form([
                        TextInput::make('reason')->label('操作原因')->required(),
                    ])
                    ->authorize(fn (Product $record) => auth()->user()?->can('update', $record) ?? false)
                    ->action(function (Product $record, array $data): void {
                        $newStatus = $record->status === ProductStatus::Active
                            ? ProductStatus::Disabled
                            : ProductStatus::Active;

                        app(UpdateProductStatus::class)(
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
            'index' => Pages\ListProducts::route('/'),
            'view' => Pages\ViewProduct::route('/{record}'),
        ];
    }
}
