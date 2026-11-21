<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\AuditLogResource\Pages;
use App\Modules\Domains\Base\Logging\Models\AdminAuditLog;
use BackedEnum;
use Filament\Actions;
use Filament\Forms;
use Filament\Infolists;
use Filament\Resources\Resource;
use Filament\Schemas;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use UnitEnum;

class AuditLogResource extends Resource
{
    protected static ?string $model = AdminAuditLog::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-clock';

    protected static string|UnitEnum|null $navigationGroup = '系统管理';

    protected static ?string $navigationLabel = '操作审计';

    protected static ?string $modelLabel = '审计日志';

    protected static ?string $pluralModelLabel = '审计日志';

    protected static ?int $navigationSort = 95;

    protected static ?string $recordTitleAttribute = 'description';

    public static function canViewAny(): bool
    {
        $user = auth('admin')->user();

        if ($user?->hasRole('super_admin', 'admin')) {
            return true;
        }

        return $user->can('audit-log.view');
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

                Tables\Columns\TextColumn::make('admin_name')
                    ->label('操作人')
                    ->searchable(),

                Tables\Columns\TextColumn::make('admin_email')
                    ->label('邮箱')
                    ->searchable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('action')
                    ->label('操作')
                    ->badge()
                    ->color(fn (AdminAuditLog $record): string => $record->action_color),

                Tables\Columns\TextColumn::make('subject_type')
                    ->label('对象类型')
                    ->formatStateUsing(fn (?string $state): string => $state ? class_basename($state) : '-')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('subject_id')
                    ->label('对象 ID')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('description')
                    ->label('描述')
                    ->limit(50)
                    ->tooltip(fn (AdminAuditLog $record): string => $record->description ?? ''),

                Tables\Columns\TextColumn::make('ip_address')
                    ->label('IP')
                    ->limit(15),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('时间')
                    ->dateTime('Y-m-d H:i:s')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('action')
                    ->label('操作类型')
                    ->options([
                        'created' => '创建',
                        'updated' => '更新',
                        'deleted' => '删除',
                        'login' => '登录',
                        'logout' => '登出',
                        'login_failed' => '登录失败',
                    ]),

                Tables\Filters\Filter::make('admin_name')
                    ->label('操作人')
                    ->form([
                        Forms\Components\TextInput::make('admin_name')
                            ->label('操作人'),
                    ])
                    ->query(fn ($query, array $data) => $query
                        ->when($data['admin_name'] ?? null, fn ($q, $name) => $q->where('admin_name', 'like', "%{$name}%"))
                    ),

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
                            ->when($data['created_from'] ?? null, fn ($q, $date) => $q->where('created_at', '>=', $date))
                            ->when($data['created_until'] ?? null, fn ($q, $date) => $q->where('created_at', '<=', $date));
                    }),
            ])
            ->actions([
                Actions\ViewAction::make(),
            ])
            ->bulkActions([]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Schemas\Components\Section::make('操作信息')
                    ->schema([
                        Infolists\Components\TextEntry::make('id')
                            ->label('日志 ID'),

                        Infolists\Components\TextEntry::make('admin_name')
                            ->label('操作人'),

                        Infolists\Components\TextEntry::make('admin_email')
                            ->label('邮箱'),

                        Infolists\Components\TextEntry::make('action')
                            ->label('操作')
                            ->badge(fn (AdminAuditLog $record): string => $record->action_label)
                            ->color(fn (AdminAuditLog $record): string => $record->action_color),

                        Infolists\Components\TextEntry::make('description')
                            ->label('描述')
                            ->columnSpanFull(),

                        Infolists\Components\TextEntry::make('created_at')
                            ->label('时间')
                            ->dateTime('Y-m-d H:i:s'),
                    ])
                    ->columns(2),

                Schemas\Components\Section::make('操作对象')
                    ->schema([
                        Infolists\Components\TextEntry::make('subject_type')
                            ->label('对象类型')
                            ->formatStateUsing(fn (?string $state): string => $state ? class_basename($state) : '-'),

                        Infolists\Components\TextEntry::make('subject_id')
                            ->label('对象 ID'),
                    ])
                    ->columns(2),

                Schemas\Components\Section::make('变更详情')
                    ->schema([
                        Infolists\Components\TextEntry::make('old_values')
                            ->label('旧值')
                            ->formatStateUsing(fn ($state) => $state ? json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : '无')
                            ->fontFamily('mono')
                            ->columnSpanFull(),

                        Infolists\Components\TextEntry::make('new_values')
                            ->label('新值')
                            ->formatStateUsing(fn ($state) => $state ? json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : '无')
                            ->fontFamily('mono')
                            ->columnSpanFull(),
                    ])
                    ->collapsible(),

                Schemas\Components\Section::make('请求信息')
                    ->schema([
                        Infolists\Components\TextEntry::make('ip_address')
                            ->label('IP 地址'),

                        Infolists\Components\TextEntry::make('user_agent')
                            ->label('User Agent')
                            ->limit(50)
                            ->tooltip(fn (AdminAuditLog $record): string => $record->user_agent ?? '')
                            ->columnSpanFull(),
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
            'index' => Pages\ListAuditLogs::route('/'),
            'view' => Pages\ViewAuditLog::route('/{record}'),
        ];
    }
}
