<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\AdminNotificationResource\Pages;
use App\Modules\Domains\Notification\Models\AdminNotification;
use BackedEnum;
use Filament\Actions;
use Filament\Infolists;
use Filament\Resources\Resource;
use Filament\Schemas;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use UnitEnum;

class AdminNotificationResource extends Resource
{
    protected static ?string $model = AdminNotification::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-bell';

    protected static string|UnitEnum|null $navigationGroup = '系统管理';

    protected static ?string $navigationLabel = '通知中心';

    protected static ?string $modelLabel = '通知';

    protected static ?string $pluralModelLabel = '通知';

    protected static ?int $navigationSort = 85;

    protected static ?string $recordTitleAttribute = 'title';

    public static function canViewAny(): bool
    {
        return auth('admin')->check();
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\IconColumn::make('is_read')
                    ->label('')
                    ->boolean()
                    ->trueIcon('heroicon-o-bell')
                    ->falseIcon('heroicon-o-bell-alert')
                    ->falseColor('warning'),

                Tables\Columns\TextColumn::make('type')
                    ->label('类型')
                    ->badge()
                    ->color(fn (AdminNotification $record): string => $record->type_color),

                Tables\Columns\TextColumn::make('title')
                    ->label('标题')
                    ->searchable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('message')
                    ->label('内容')
                    ->limit(50)
                    ->tooltip(fn (AdminNotification $record): string => $record->message),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('时间')
                    ->dateTime('Y-m-d H:i:s')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label('类型')
                    ->options([
                        'order_status_changed' => '订单状态变更',
                        'low_stock' => '库存预警',
                        'system' => '系统通知',
                    ]),

                Tables\Filters\SelectFilter::make('is_read')
                    ->label('状态')
                    ->options([
                        0 => '未读',
                        1 => '已读',
                    ]),
            ])
            ->actions([
                Actions\ViewAction::make(),
                Actions\Action::make('markAsRead')
                    ->label('标记已读')
                    ->icon('heroicon-o-check')
                    ->requiresConfirmation()
                    ->action(fn (AdminNotification $record) => $record->markAsRead())
                    ->visible(fn (AdminNotification $record) => ! $record->is_read),
            ])
            ->bulkActions([
                Actions\BulkActionGroup::make([
                    Actions\BulkAction::make('markAllAsRead')
                        ->label('全部标记已读')
                        ->icon('heroicon-o-check-circle')
                        ->action(function ($records) {
                            $records->each->markAsRead();
                        }),
                    Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Schemas\Components\Section::make('通知信息')
                    ->schema([
                        Infolists\Components\TextEntry::make('id')
                            ->label('ID'),

                        Infolists\Components\TextEntry::make('type')
                            ->label('类型')
                            ->badge(fn (AdminNotification $record): string => $record->type_label)
                            ->color(fn (AdminNotification $record): string => $record->type_color),

                        Infolists\Components\TextEntry::make('title')
                            ->label('标题')
                            ->columnSpanFull(),

                        Infolists\Components\TextEntry::make('message')
                            ->label('内容')
                            ->columnSpanFull(),

                        Infolists\Components\TextEntry::make('is_read')
                            ->label('状态')
                            ->formatStateUsing(fn (bool $state): string => $state ? '已读' : '未读')
                            ->badge()
                            ->color(fn (bool $state): string => $state ? 'success' : 'warning'),

                        Infolists\Components\TextEntry::make('read_at')
                            ->label('阅读时间')
                            ->dateTime('Y-m-d H:i:s')
                            ->placeholder('未读'),

                        Infolists\Components\TextEntry::make('created_at')
                            ->label('创建时间')
                            ->dateTime('Y-m-d H:i:s'),
                    ])
                    ->columns(2),

                Schemas\Components\Section::make('关联数据')
                    ->schema([
                        Infolists\Components\TextEntry::make('data')
                            ->label('数据')
                            ->formatStateUsing(fn ($state) => $state ? json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : '无')
                            ->fontFamily('mono')
                            ->columnSpanFull(),
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
            'index' => Pages\ListAdminNotifications::route('/'),
            'view' => Pages\ViewAdminNotification::route('/{record}'),
        ];
    }
}
