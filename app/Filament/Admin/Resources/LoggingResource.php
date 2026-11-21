<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\LoggingResource\Pages;
use App\Modules\Domains\Logging\Models\RequestLog;
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

class LoggingResource extends Resource
{
    use HasResourcePermission;

    protected static ?string $model = RequestLog::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-document-text';

    protected static string|UnitEnum|null $navigationGroup = '系统管理';

    protected static ?string $navigationLabel = '请求日志';

    protected static ?string $modelLabel = '请求日志';

    protected static ?string $pluralModelLabel = '请求日志';

    protected static ?int $navigationSort = 100;

    protected static function getPermissionPrefix(): string
    {
        return 'request-log';
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
                    ->tooltip(fn (RequestLog $record): string => $record->request_id),

                Tables\Columns\TextColumn::make('method')
                    ->label('方法')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'GET' => 'success',
                        'POST' => 'info',
                        'PUT' => 'warning',
                        'PATCH' => 'warning',
                        'DELETE' => 'danger',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('path')
                    ->label('路径')
                    ->limit(30)
                    ->tooltip(fn (RequestLog $record): string => $record->path),

                Tables\Columns\TextColumn::make('user_name')
                    ->label('用户')
                    ->placeholder('未登录'),

                Tables\Columns\TextColumn::make('ip_address')
                    ->label('IP')
                    ->limit(15),

                Tables\Columns\TextColumn::make('client_type')
                    ->label('客户端')
                    ->badge(),

                Tables\Columns\TextColumn::make('response_status')
                    ->label('状态码')
                    ->badge()
                    ->color(fn (int $state): string => match (true) {
                        $state >= 200 && $state < 300 => 'success',
                        $state >= 300 && $state < 400 => 'warning',
                        $state >= 400 && $state < 500 => 'danger',
                        $state >= 500 => 'danger',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('response_time')
                    ->label('耗时')
                    ->suffix('ms')
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('时间')
                    ->dateTime('Y-m-d H:i:s')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('method')
                    ->label('请求方法')
                    ->options([
                        'GET' => 'GET',
                        'POST' => 'POST',
                        'PUT' => 'PUT',
                        'PATCH' => 'PATCH',
                        'DELETE' => 'DELETE',
                    ]),

                Tables\Filters\SelectFilter::make('client_type')
                    ->label('客户端类型')
                    ->options([
                        'web' => 'Web',
                        'api' => 'API',
                        'ajax' => 'AJAX',
                        'postman' => 'Postman',
                        'curl' => 'cURL',
                        'android' => 'Android',
                        'ios' => 'iOS',
                    ]),

                Tables\Filters\SelectFilter::make('response_status')
                    ->label('状态码')
                    ->options([
                        '2xx' => '2xx 成功',
                        '3xx' => '3xx 重定向',
                        '4xx' => '4xx 客户端错误',
                        '5xx' => '5xx 服务器错误',
                    ])
                    ->query(fn ($query, array $data) => $data['value'] ?? null
                        ? $query->whereBetween('response_status', [
                            (int) substr($data['value'], 0, 1) * 100,
                            (int) substr($data['value'], 0, 1) * 100 + 99,
                        ])
                        : $query
                    ),

                Tables\Filters\Filter::make('is_error')
                    ->label('仅异常')
                    ->query(fn ($query) => $query->where('response_status', '>=', 400)
                        ->orWhereNotNull('exception_class')),

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
                Actions\BulkActionGroup::make([
                    Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Schemas\Components\Section::make('请求信息')
                    ->schema([
                        Infolists\Components\TextEntry::make('id')
                            ->label('日志 ID'),

                        Infolists\Components\TextEntry::make('request_id')
                            ->label('请求 ID')
                            ->copyable()
                            ->tooltip(fn (RequestLog $record): string => $record->request_id)
                            ->fontFamily('mono'),

                        Infolists\Components\TextEntry::make('method')
                            ->label('请求方法')
                            ->badge(),

                        Infolists\Components\TextEntry::make('path')
                            ->label('请求路径')
                            ->fontFamily('mono')
                            ->columnSpanFull(),

                        Infolists\Components\TextEntry::make('controller')
                            ->label('控制器')
                            ->placeholder('-'),

                        Infolists\Components\TextEntry::make('action')
                            ->label('方法')
                            ->placeholder('-'),
                    ])
                    ->columns(2),

                Schemas\Components\Section::make('用户信息')
                    ->schema([
                        Infolists\Components\TextEntry::make('user_name')
                            ->label('用户')
                            ->placeholder('未登录'),

                        Infolists\Components\TextEntry::make('user_type')
                            ->label('用户类型')
                            ->placeholder('-'),

                        Infolists\Components\TextEntry::make('ip_address')
                            ->label('IP 地址'),

                        Infolists\Components\TextEntry::make('client_type')
                            ->label('客户端类型')
                            ->badge(),

                        Infolists\Components\TextEntry::make('user_agent')
                            ->label('User Agent')
                            ->limit(50)
                            ->tooltip(fn (RequestLog $record): string => $record->user_agent ?? '')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Schemas\Components\Section::make('请求详情')
                    ->schema([
                        Infolists\Components\TextEntry::make('request_headers')
                            ->label('Headers')
                            ->formatStateUsing(fn ($state) => format_json($state === '' ? null : $state))
                            ->fontFamily('mono')
                            ->columnSpanFull(),

                        Infolists\Components\TextEntry::make('request_body')
                            ->label('Body')
                            ->formatStateUsing(fn ($state) => format_json($state === '' ? null : $state))
                            ->fontFamily('mono')
                            ->columnSpanFull(),

                        Infolists\Components\TextEntry::make('query_params')
                            ->label('Query Params')
                            ->formatStateUsing(fn ($state) => format_json($state === '' ? null : $state))
                            ->fontFamily('mono')
                            ->columnSpanFull(),
                    ]),

                Schemas\Components\Section::make('响应信息')
                    ->schema([
                        Infolists\Components\TextEntry::make('response_status')
                            ->label('状态码')
                            ->badge(),

                        Infolists\Components\TextEntry::make('response_time')
                            ->label('响应时间')
                            ->suffix(' ms'),

                        Infolists\Components\TextEntry::make('memory_usage')
                            ->label('内存使用')
                            ->formatStateUsing(fn ($state) => $state ? number_format($state / 1024 / 1024, 2).' MB' : '-'),

                        Infolists\Components\TextEntry::make('response_body')
                            ->label('响应 Body')
                            ->formatStateUsing(fn ($state) => format_json($state))
                            ->fontFamily('mono')
                            ->columnSpanFull(),
                    ]),

                Schemas\Components\Section::make('异常信息')
                    ->schema([
                        Infolists\Components\TextEntry::make('exception_class')
                            ->label('异常类')
                            ->placeholder('无异常')
                            ->color('danger'),

                        Infolists\Components\TextEntry::make('exception_message')
                            ->label('异常消息')
                            ->placeholder('无异常')
                            ->columnSpanFull(),

                        Infolists\Components\TextEntry::make('exception_trace')
                            ->label('异常堆栈')
                            ->formatStateUsing(fn ($state) => $state ? Str::limit($state, 500) : '-')
                            ->fontFamily('mono')
                            ->columnSpanFull(),
                    ])
                    ->collapsible(),

                Schemas\Components\Section::make('关联业务日志')
                    ->schema([
                        Infolists\Components\RepeatableEntry::make('businessLogs')
                            ->label('')
                            ->schema([
                                Infolists\Components\TextEntry::make('level')
                                    ->label('级别')
                                    ->badge(),

                                Infolists\Components\TextEntry::make('message')
                                    ->label('消息'),

                                Infolists\Components\TextEntry::make('created_at')
                                    ->label('时间')
                                    ->dateTime('Y-m-d H:i:s'),
                            ])
                            ->columns(3),
                    ])
                    ->collapsible(),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLogging::route('/'),
            'view' => Pages\ViewLogging::route('/{record}'),
        ];
    }
}
