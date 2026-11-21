<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\CrossSellProductResource\Pages;
use App\Filament\Admin\Resources\CrossSellProductResource\ProductExporter;
use App\Modules\CrossSell\Product\Enums\ProductStatus;
use App\Modules\CrossSell\Product\Models\Product;
use App\Modules\CrossSell\Product\Models\ProductCategory;
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
 * 商品管理 Resource
 */
class CrossSellProductResource extends Resource
{
    use HasResourcePermission;

    protected static ?string $model = Product::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-cube';

    protected static string|UnitEnum|null $navigationGroup = 'CrossSell 管理';

    protected static ?string $navigationLabel = '商品管理';

    protected static ?string $modelLabel = '商品';

    protected static ?string $pluralModelLabel = '商品';

    protected static ?int $navigationSort = 2;

    protected static function getPermissionPrefix(): string
    {
        return 'cross-sell-product';
    }

    /**
     * 表单定义
     */
    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Schemas\Components\Section::make('基本信息')
                    ->schema([
                        Forms\Components\TextInput::make('title')
                            ->label('商品标题')
                            ->required()
                            ->maxLength(500),

                        Forms\Components\Select::make('category_id')
                            ->label('商品分类')
                            ->options(ProductCategory::pluck('name', 'id'))
                            ->searchable()
                            ->nullable(),

                        Forms\Components\TextInput::make('brand')
                            ->label('品牌')
                            ->maxLength(255),

                        Forms\Components\Select::make('status')
                            ->label('状态')
                            ->options(collect(ProductStatus::cases())->mapWithKeys(fn ($case) => [$case->value => $case->label()])->toArray())
                            ->required()
                            ->default('draft'),
                    ])
                    ->columns(2),

                Schemas\Components\Section::make('多语言信息')
                    ->schema([
                        Forms\Components\TextInput::make('title_en')
                            ->label('英文标题')
                            ->maxLength(500),

                        Forms\Components\Textarea::make('description')
                            ->label('中文描述')
                            ->rows(5),

                        Forms\Components\Textarea::make('description_en')
                            ->label('英文描述')
                            ->rows(5),
                    ])
                    ->columns(2),

                Schemas\Components\Section::make('图片')
                    ->schema([
                        Forms\Components\FileUpload::make('main_image')
                            ->label('主图')
                            ->image()
                            ->maxSize(1024 * 1024)
                            ->directory('products'),

                        Forms\Components\FileUpload::make('images')
                            ->label('详情图')
                            ->multiple()
                            ->image()
                            ->maxSize(1024 * 1024)
                            ->directory('products'),
                    ])
                    ->columns(2),

                Schemas\Components\Section::make('1688信息')
                    ->schema([
                        Forms\Components\TextInput::make('source_url')
                            ->label('1688商品链接')
                            ->url()
                            ->maxLength(500),

                        Forms\Components\TextInput::make('source_id')
                            ->label('1688商品ID')
                            ->maxLength(100),
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
                Tables\Columns\ImageColumn::make('main_image')
                    ->label('主图')
                    ->circular(),

                Tables\Columns\TextColumn::make('title')
                    ->label('商品标题')
                    ->searchable()
                    ->sortable()
                    ->limit(50),

                Tables\Columns\TextColumn::make('brand')
                    ->label('品牌')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('category.name')
                    ->label('分类')
                    ->sortable(),

                Tables\Columns\TextColumn::make('skus_count')
                    ->label('SKU数')
                    ->counts('skus')
                    ->sortable(),

                Tables\Columns\TextColumn::make('status')
                    ->label('状态')
                    ->badge()
                    ->formatStateUsing(fn (ProductStatus $state): string => $state->label())
                    ->color(fn (ProductStatus $state): string => $state->getColor()),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('创建时间')
                    ->dateTime('Y-m-d H:i')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options(collect(ProductStatus::cases())->mapWithKeys(fn ($case) => [$case->value => $case->label()])->toArray()),

                Tables\Filters\SelectFilter::make('category_id')
                    ->options(ProductCategory::pluck('name', 'id')),

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
                    Actions\BulkAction::make('activate')
                        ->label('批量上架')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->action(fn (Collection $records) => $records->each->update(['status' => 'active']))
                        ->requiresConfirmation()
                        ->modalHeading('批量上架商品')
                        ->modalDescription('确认将选中的商品状态改为上架？'),
                    Actions\BulkAction::make('deactivate')
                        ->label('批量下架')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->action(fn (Collection $records) => $records->each->update(['status' => 'inactive']))
                        ->requiresConfirmation()
                        ->modalHeading('批量下架商品')
                        ->modalDescription('确认将选中的商品状态改为下架？'),
                ]),
                Actions\ExportBulkAction::make()
                    ->exporter(ProductExporter::class),
            ]);
    }

    /**
     * 信息列表定义
     */
    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Schemas\Components\Section::make('基本信息')
                    ->schema([
                        Infolists\Components\TextEntry::make('title')
                            ->label('商品标题'),

                        Infolists\Components\TextEntry::make('category.name')
                            ->label('分类'),

                        Infolists\Components\TextEntry::make('brand')
                            ->label('品牌'),

                        Infolists\Components\TextEntry::make('status')
                            ->label('状态')
                            ->badge()
                            ->formatStateUsing(fn (ProductStatus $state): string => $state->label())
                            ->color(fn (ProductStatus $state): string => $state->getColor()),
                    ])
                    ->columns(2),

                Schemas\Components\Section::make('图片')
                    ->schema([
                        Infolists\Components\ImageEntry::make('main_image')
                            ->label('主图'),

                        Infolists\Components\ImageEntry::make('images')
                            ->label('详情图')
                            ->multiple(),
                    ])
                    ->columns(2),

                Schemas\Components\Section::make('SKU信息')
                    ->schema([
                        Infolists\Components\TextEntry::make('skus_count')
                            ->label('SKU数量')
                            ->state(fn ($record) => $record->skus()->count()),

                        Infolists\Components\TextEntry::make('skus_total_stock')
                            ->label('总库存')
                            ->state(fn ($record) => $record->skus()->sum('stock')),
                    ])
                    ->columns(2),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCrossSellProducts::route('/'),
            'create' => Pages\CreateCrossSellProduct::route('/create'),
            'edit' => Pages\EditCrossSellProduct::route('/{record}/edit'),
            'view' => Pages\ViewCrossSellProduct::route('/{record}'),
        ];
    }
}
