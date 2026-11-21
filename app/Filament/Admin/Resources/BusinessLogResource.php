<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\BusinessLogResource\Pages;
use App\Modules\Domains\Logging\Enums\LogLevel;
use App\Modules\Domains\Logging\Models\BusinessLog;
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
use Illuminate\Support\Str;
use UnitEnum;

class BusinessLogResource extends Resource
{
    use HasResourcePermission;

    protected static ?string $model = BusinessLog::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static string|UnitEnum|null $navigationGroup = '系统管理';

    protected static ?string $navigationLabel = '业务日志';

    protected static ?string $modelLabel = '业务日志';

    protected static ?string $pluralModelLabel = '业务日志';

    protected static ?int $navigationSort = 101;

    protected static function getPermissionPrefix(): string
    {
        return 'business-log';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),

                Tables\Columns\TextColumn::make('request_id')
                    ->label('请求 ID')
                    ->limit(12)
                    ->copyable()
                    ->tooltip(fn (BusinessLog $record): string => $record->request_id ?? '-')
                    ->placeholder('-'),

                Tables\Columns\TextColumn::make('level')
                    ->label('级别')
                    ->badge()
                    ->color(fn (LogLevel $state): string => $state->color()),

                Tables\Columns\TextColumn::make('channel')
                    ->label('通道')
                    ->badge(),

                Tables\Columns\TextColumn::make('message')
                    ->label('消息')
                    ->limit(50)
                    ->tooltip(fn (BusinessLog $record): string => $record->message),

                Tables\Columns\TextColumn::make('file')
                    ->label('文件')
                    ->limit(30)
                    ->placeholder('-')
                    ->tooltip(fn (BusinessLog $record): string => $record->file ? $record->file.':'.$record->line : ''),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('时间')
                    ->dateTime('Y-m-d H:i:s')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('level')
                    ->label('日志级别')
                    ->options([
                        'debug' => '调试',
                        'info' => '信息',
                        'warning' => '警告',
                        'error' => '错误',
                        'critical' => '严重',
                    ]),

                Tables\Filters\SelectFilter::make('channel')
                    ->label('日志通道')
                    ->options([
                        'default' => '默认',
                        'auth' => '认证',
                        'order' => '订单',
                        'payment' => '支付',
                    ]),

                Tables\Filters\Filter::make('has_request_id')
                    ->label('有关联请求')
                    ->query(fn ($query) => $query->whereNotNull('request_id')),

                Tables\Filters\Filter::make('created_at')
                    ->label('时间范围')
                    ->form([
                        Forms\Components\DatePicker::make('created_from')
                            ->label('开始时间'),
                        Forms\Components\DatePicker::make('created_until')
                            ->label('结束时间'),
                    ])
                    ->query(function ($query, array $data): void {
                        $query
                            ->when($data['created_from'], fn ($q, $date) => $q->where('created_at', '>=', $date))
                            ->when($data['created_until'], fn ($q, $date) => $q->where('created_at', '<=', $date));
                    }),
            ])
            ->actions([
                Actions\ViewAction::make(),
                Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Schemas\Components\Section::make('基本信息')
                    ->schema([
                        Infolists\Components\TextEntry::make('id')
                            ->label('ID'),

                        Infolists\Components\TextEntry::make('request_id')
                            ->label('请求 ID')
                            ->copyable()
                            ->tooltip(fn (BusinessLog $record): string => $record->request_id ?? '-')
                            ->placeholder('无关联请求')
                            ->fontFamily('mono'),

                        Infolists\Components\TextEntry::make('level')
                            ->label('级别')
                            ->badge(),

                        Infolists\Components\TextEntry::make('channel')
                            ->label('通道')
                            ->badge(),

                        Infolists\Components\TextEntry::make('created_at')
                            ->label('时间')
                            ->dateTime('Y-m-d H:i:s'),
                    ])
                    ->columns(2),

                Schemas\Components\Section::make('日志内容')
                    ->schema([
                        Infolists\Components\TextEntry::make('message')
                            ->label('消息')
                            ->columnSpanFull(),

                        Infolists\Components\TextEntry::make('context')
                            ->label('上下文')
                            ->formatStateUsing(fn ($state) => format_json($state))
                            ->fontFamily('mono')
                            ->columnSpanFull(),

                        Infolists\Components\TextEntry::make('extra')
                            ->label('额外数据')
                            ->formatStateUsing(fn ($state) => format_json($state))
                            ->fontFamily('mono')
                            ->columnSpanFull(),
                    ]),

                Schemas\Components\Section::make('触发位置')
                    ->schema([
                        Infolists\Components\TextEntry::make('file')
                            ->label('文件')
                            ->fontFamily('mono')
                            ->columnSpan(3),

                        Infolists\Components\TextEntry::make('line')
                            ->label('行号'),
                    ])
                    ->columns(4)
                    ->collapsible(),

                Schemas\Components\Section::make('调用堆栈')
                    ->schema([
                        Infolists\Components\TextEntry::make('trace')
                            ->label('堆栈')
                            ->formatStateUsing(fn ($state) => $state ? Str::limit($state, 2000) : '-')
                            ->fontFamily('mono')
                            ->columnSpanFull(),
                    ])
                    ->collapsible()
                    ->collapsed(),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBusinessLogs::route('/'),
            'view' => Pages\ViewBusinessLog::route('/{record}'),
        ];
    }
}
