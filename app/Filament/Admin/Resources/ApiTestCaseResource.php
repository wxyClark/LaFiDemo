<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\ApiTestCaseResource\Pages;
use App\Modules\Domains\ApiTesting\Models\ApiEnvironment;
use App\Modules\Domains\ApiTesting\Models\ApiInterface;
use App\Modules\Domains\ApiTesting\Models\ApiTestCase;
use App\Modules\Domains\ApiTesting\Services\TestExecutorService;
use App\Modules\Infrastructure\Filament\Concerns\HasResourcePermission;
use BackedEnum;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use UnitEnum;

class ApiTestCaseResource extends Resource
{
    use HasResourcePermission;

    protected static ?string $model = ApiTestCase::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-document-check';

    protected static string|UnitEnum|null $navigationGroup = '接口测试';

    protected static ?string $navigationLabel = '测试用例';

    protected static ?string $modelLabel = '测试用例';

    protected static ?string $pluralModelLabel = '测试用例';

    protected static ?int $navigationSort = 4;

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
                        Forms\Components\Select::make('interface_id')
                            ->label('所属接口')
                            ->options(fn () => ApiInterface::with('function.module')->get()
                                ->mapWithKeys(fn ($i) => [$i->id => $i->function->module->name.' > '.$i->function->name.' > '.$i->name]))
                            ->required()
                            ->searchable()
                            ->preload(),

                        Forms\Components\Select::make('environment_id')
                            ->label('测试环境')
                            ->options(fn () => ApiEnvironment::pluck('name', 'id'))
                            ->required()
                            ->default(fn () => ApiEnvironment::where('is_default', true)->first()?->id),

                        Forms\Components\TextInput::make('name')
                            ->label('用例名称')
                            ->required()
                            ->maxLength(200),

                        Forms\Components\Textarea::make('description')
                            ->label('描述')
                            ->rows(2),
                    ]),

                Schemas\Components\Section::make('请求参数')
                    ->schema([
                        Forms\Components\KeyValue::make('headers')
                            ->label('Headers (覆盖默认)')
                            ->helperText('留空则使用接口默认 Headers'),

                        Forms\Components\KeyValue::make('query_params')
                            ->label('Query 参数'),

                        Forms\Components\Textarea::make('body')
                            ->label('请求 Body')
                            ->rows(8)
                            ->placeholder('{\n    "key": "value"\n}'),
                    ])
                    ->collapsible(),

                Schemas\Components\Section::make('断言配置')
                    ->schema([
                        Forms\Components\TextInput::make('expected_status')
                            ->label('预期状态码')
                            ->numeric()
                            ->default(200)
                            ->required(),

                        Forms\Components\Repeater::make('expected_data')
                            ->label('数据断言')
                            ->schema([
                                Forms\Components\TextInput::make('path')
                                    ->label('JSON Path')
                                    ->placeholder('data.id'),

                                Forms\Components\Select::make('operator')
                                    ->label('操作符')
                                    ->options([
                                        'equals' => '等于',
                                        'not_equals' => '不等于',
                                        'gt' => '大于',
                                        'gte' => '大于等于',
                                        'lt' => '小于',
                                        'lte' => '小于等于',
                                        'contains' => '包含',
                                        'exists' => '存在',
                                        'not_exists' => '不存在',
                                        'in' => '在列表中',
                                        'type' => '类型等于',
                                    ])
                                    ->default('equals'),

                                Forms\Components\TextInput::make('expected')
                                    ->label('期望值'),
                            ])
                            ->columns(3),

                        Forms\Components\TextInput::make('expected_response_time')
                            ->label('最大响应时间 (ms)')
                            ->numeric()
                            ->placeholder('1000'),
                    ])
                    ->collapsible(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with(['interface.function.module', 'environment', 'latestResult']))
            ->columns([
                Tables\Columns\CheckboxColumn::make('id')
                    ->label(''),

                Tables\Columns\TextColumn::make('interface.function.module.name')
                    ->label('模块')
                    ->sortable(),

                Tables\Columns\TextColumn::make('interface.name')
                    ->label('接口'),

                Tables\Columns\TextColumn::make('name')
                    ->label('用例名称')
                    ->searchable(),

                Tables\Columns\TextColumn::make('environment.name')
                    ->label('环境'),

                Tables\Columns\TextColumn::make('expected_status')
                    ->label('预期状态码'),

                Tables\Columns\TextColumn::make('latestResult.status')
                    ->label('最近结果')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state?->label() ?? '未测试')
                    ->color(fn ($state) => $state?->color() ?? 'gray'),

                Tables\Columns\TextColumn::make('sort_order')
                    ->label('排序')
                    ->sortable(),
            ])
            ->defaultSort('sort_order')
            ->actions([
                Actions\Action::make('test')
                    ->label('测试')
                    ->icon('heroicon-o-play')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('执行测试')
                    ->modalDescription('确定要执行这个测试用例吗？')
                    ->action(function (ApiTestCase $record) {
                        $executor = app(TestExecutorService::class);
                        $result = $executor->execute($record);

                        Notification::make()
                            ->title('测试完成')
                            ->body($result->status->label().' - '.$result->response_time.'ms')
                            ->color($result->status->color())
                            ->send();
                    }),

                Actions\EditAction::make(),
                Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Actions\BulkActionGroup::make([
                    Actions\DeleteBulkAction::make(),

                    Actions\BulkAction::make('testSelected')
                        ->label('批量测试')
                        ->icon('heroicon-o-play')
                        ->color('success')
                        ->requiresConfirmation()
                        ->modalHeading('批量执行测试')
                        ->modalDescription('确定要执行选中的测试用例吗？')
                        ->action(function ($records) {
                            $executor = app(TestExecutorService::class);
                            $results = [];

                            foreach ($records as $record) {
                                $results[] = $executor->execute($record);
                            }

                            $passed = collect($results)->filter(fn ($r) => $r->status->value === 'pass')->count();
                            $failed = collect($results)->filter(fn ($r) => $r->status->value === 'fail')->count();
                            $error = collect($results)->filter(fn ($r) => $r->status->value === 'error')->count();

                            Notification::make()
                                ->title('批量测试完成')
                                ->body("通过: {$passed} | 失败: {$failed} | 错误: {$error}")
                                ->color($failed > 0 || $error > 0 ? 'danger' : 'success')
                                ->send();
                        }),

                    Actions\BulkAction::make('testAndRedirect')
                        ->label('测试并查看结果')
                        ->icon('heroicon-o-arrow-right')
                        ->color('primary')
                        ->requiresConfirmation()
                        ->modalHeading('执行测试并跳转')
                        ->modalDescription('确定要执行测试并跳转到结果页面吗？')
                        ->action(function ($records) {
                            $executor = app(TestExecutorService::class);
                            $batch = $executor->executeBatchAndRecord($records);

                            return redirect()->route('filament.admin.resources.api-test-results.index', [
                                'batch_id' => $batch->id,
                            ]);
                        }),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListApiTestCases::route('/'),
            'create' => Pages\CreateApiTestCase::route('/create'),
            'edit' => Pages\EditApiTestCase::route('/{record}/edit'),
        ];
    }
}
