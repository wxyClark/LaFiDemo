<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\ApiInterfaceResource\Pages;
use App\Modules\Domains\ApiTesting\Enums\HttpMethod;
use App\Modules\Domains\ApiTesting\Models\ApiFunction;
use App\Modules\Domains\ApiTesting\Models\ApiInterface;
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

class ApiInterfaceResource extends Resource
{
    use HasResourcePermission;

    protected static ?string $model = ApiInterface::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-link';

    protected static string|UnitEnum|null $navigationGroup = '接口测试';

    protected static ?string $navigationLabel = '接口管理';

    protected static ?string $modelLabel = 'API 接口';

    protected static ?string $pluralModelLabel = 'API 接口';

    protected static ?int $navigationSort = 3;

    protected static function getPermissionPrefix(): string
    {
        return 'api-testing';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Schemas\Components\Section::make('基本信息')
                    ->schema([
                        Forms\Components\Select::make('function_id')
                            ->label('所属功能')
                            ->options(fn () => ApiFunction::with('module')->get()
                                ->mapWithKeys(fn ($f) => [$f->id => $f->module->name.' > '.$f->name]))
                            ->required()
                            ->searchable()
                            ->preload(),

                        Forms\Components\TextInput::make('name')
                            ->label('接口名称')
                            ->required()
                            ->maxLength(200),

                        Forms\Components\Textarea::make('description')
                            ->label('描述')
                            ->rows(2),
                    ]),

                Schemas\Components\Section::make('请求配置')
                    ->schema([
                        Forms\Components\Select::make('method')
                            ->label('请求方法')
                            ->options(collect(HttpMethod::cases())->pluck('value', 'value')->toArray())
                            ->required()
                            ->default('GET')
                            ->reactive(),

                        Forms\Components\TextInput::make('path')
                            ->label('接口路径')
                            ->required()
                            ->maxLength(500)
                            ->placeholder('/api/users'),

                        Forms\Components\Toggle::make('auth_required')
                            ->label('需要认证')
                            ->default(true),

                        Forms\Components\Select::make('body_type')
                            ->label('Body 类型')
                            ->options([
                                'json' => 'JSON',
                                'form' => 'Form Data',
                                'raw' => 'Raw',
                                'none' => 'None',
                            ])
                            ->default('json'),
                    ])
                    ->columns(2),

                Schemas\Components\Section::make('默认 Headers')
                    ->schema([
                        Forms\Components\KeyValue::make('headers')
                            ->label('Headers'),
                    ])
                    ->collapsible(),

                Schemas\Components\Section::make('标签')
                    ->schema([
                        Forms\Components\TagsInput::make('tags')
                            ->label('标签')
                            ->helperText('按回车添加标签'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with(['function.module']))
            ->columns([
                Tables\Columns\TextColumn::make('function.module.name')
                    ->label('模块')
                    ->sortable(),

                Tables\Columns\TextColumn::make('function.name')
                    ->label('功能')
                    ->sortable(),

                Tables\Columns\TextColumn::make('name')
                    ->label('接口名称')
                    ->searchable(),

                Tables\Columns\TextColumn::make('method')
                    ->label('方法')
                    ->badge()
                    ->color(fn (HttpMethod $state): string => $state->color()),

                Tables\Columns\TextColumn::make('path')
                    ->label('路径')
                    ->limit(30)
                    ->fontFamily('mono'),

                Tables\Columns\IconColumn::make('auth_required')
                    ->label('认证')
                    ->boolean(),

                Tables\Columns\TextColumn::make('test_cases_count')
                    ->label('用例数')
                    ->counts('testCases'),

                Tables\Columns\TextColumn::make('sort_order')
                    ->label('排序')
                    ->sortable(),
            ])
            ->defaultSort('sort_order')
            ->filters([
                Tables\Filters\SelectFilter::make('function_id')
                    ->label('功能')
                    ->options(fn () => ApiFunction::pluck('name', 'id')),

                Tables\Filters\SelectFilter::make('method')
                    ->label('请求方法')
                    ->options(collect(HttpMethod::cases())->pluck('value', 'value')->toArray()),
            ])
            ->actions([
                Actions\EditAction::make(),
                Actions\DeleteAction::make(),
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
            'index' => Pages\ListApiInterfaces::route('/'),
            'create' => Pages\CreateApiInterface::route('/create'),
            'edit' => Pages\EditApiInterface::route('/{record}/edit'),
        ];
    }
}
