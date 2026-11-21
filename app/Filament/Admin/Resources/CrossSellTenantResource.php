<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\CrossSellTenantResource\Pages;
use App\Modules\CrossSell\Tenant\Enums\TenantPlan;
use App\Modules\CrossSell\Tenant\Enums\TenantStatus;
use App\Modules\CrossSell\Tenant\Models\Tenant;
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
use UnitEnum;

/**
 * 租户管理 Resource
 */
class CrossSellTenantResource extends Resource
{
    use HasResourcePermission;

    protected static ?string $model = Tenant::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-building-office-2';

    protected static string|UnitEnum|null $navigationGroup = 'CrossSell 管理';

    protected static ?string $navigationLabel = '租户管理';

    protected static ?string $modelLabel = '租户';

    protected static ?string $pluralModelLabel = '租户';

    protected static ?int $navigationSort = 1;

    protected static function getPermissionPrefix(): string
    {
        return 'cross-sell-tenant';
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
                        Forms\Components\TextInput::make('name')
                            ->label('租户名称')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('slug')
                            ->label('唯一标识')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(100)
                            ->regex('/^[a-z0-9-]+$/')
                            ->helperText('只允许小写字母、数字和连字符'),

                        Forms\Components\TextInput::make('email')
                            ->label('联系邮箱')
                            ->email()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('phone')
                            ->label('联系电话')
                            ->tel()
                            ->maxLength(50),
                    ])
                    ->columns(2),

                Schemas\Components\Section::make('套餐信息')
                    ->schema([
                        Forms\Components\Select::make('plan')
                            ->label('套餐类型')
                            ->options(collect(TenantPlan::cases())->mapWithKeys(fn ($case) => [$case->value => $case->label()])->toArray())
                            ->required()
                            ->default('free'),

                        Forms\Components\DateTimePicker::make('trial_ends_at')
                            ->label('试用期结束时间')
                            ->helperText('免费套餐的试用期截止时间'),

                        Forms\Components\DateTimePicker::make('plan_starts_at')
                            ->label('付费开始时间'),

                        Forms\Components\DateTimePicker::make('plan_expires_at')
                            ->label('付费到期时间'),
                    ])
                    ->columns(2),

                Schemas\Components\Section::make('状态')
                    ->schema([
                        Forms\Components\Select::make('status')
                            ->label('状态')
                            ->options(collect(TenantStatus::cases())->mapWithKeys(fn ($case) => [$case->value => $case->label()])->toArray())
                            ->required()
                            ->default('active'),
                    ]),
            ]);
    }

    /**
     * 表格定义
     */
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),

                Tables\Columns\TextColumn::make('name')
                    ->label('租户名称')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('slug')
                    ->label('标识')
                    ->searchable(),

                Tables\Columns\TextColumn::make('plan')
                    ->label('套餐')
                    ->badge()
                    ->formatStateUsing(fn (TenantPlan $state): string => $state->label())
                    ->color(fn (TenantPlan $state): string => $state->getColor()),

                Tables\Columns\TextColumn::make('status')
                    ->label('状态')
                    ->badge()
                    ->formatStateUsing(fn (TenantStatus $state): string => $state->label())
                    ->color(fn (TenantStatus $state): string => $state->getColor()),

                Tables\Columns\TextColumn::make('users_count')
                    ->label('用户数')
                    ->counts('users')
                    ->sortable(),

                Tables\Columns\TextColumn::make('trial_ends_at')
                    ->label('试用期截止')
                    ->dateTime('Y-m-d H:i')
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('创建时间')
                    ->dateTime('Y-m-d H:i')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('plan')
                    ->options(collect(TenantPlan::cases())->mapWithKeys(fn ($case) => [$case->value => $case->label()])->toArray()),

                Tables\Filters\SelectFilter::make('status')
                    ->options(collect(TenantStatus::cases())->mapWithKeys(fn ($case) => [$case->value => $case->label()])->toArray()),
            ])
            ->actions([
                Actions\EditAction::make(),
                Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Actions\BulkActionGroup::make([
                    Actions\DeleteBulkAction::make(),
                ]),
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
                        Infolists\Components\TextEntry::make('name')
                            ->label('租户名称'),

                        Infolists\Components\TextEntry::make('slug')
                            ->label('唯一标识'),

                        Infolists\Components\TextEntry::make('email')
                            ->label('联系邮箱'),

                        Infolists\Components\TextEntry::make('phone')
                            ->label('联系电话'),
                    ])
                    ->columns(2),

                Schemas\Components\Section::make('套餐信息')
                    ->schema([
                        Infolists\Components\TextEntry::make('plan')
                            ->label('套餐类型')
                            ->badge()
                            ->formatStateUsing(fn (TenantPlan $state): string => $state->label())
                            ->color(fn (TenantPlan $state): string => $state->getColor()),

                        Infolists\Components\TextEntry::make('status')
                            ->label('状态')
                            ->badge()
                            ->formatStateUsing(fn (TenantStatus $state): string => $state->label())
                            ->color(fn (TenantStatus $state): string => $state->getColor()),

                        Infolists\Components\TextEntry::make('trial_ends_at')
                            ->label('试用期截止')
                            ->dateTime('Y-m-d H:i'),

                        Infolists\Components\TextEntry::make('created_at')
                            ->label('创建时间')
                            ->dateTime('Y-m-d H:i'),
                    ])
                    ->columns(2),

                Schemas\Components\Section::make('关联数据')
                    ->schema([
                        Infolists\Components\TextEntry::make('users_count')
                            ->label('用户数')
                            ->state(fn ($record) => $record->users()->count()),

                        Infolists\Components\TextEntry::make('departments_count')
                            ->label('部门数')
                            ->state(fn ($record) => $record->departments()->count()),

                        Infolists\Components\TextEntry::make('warehouses_count')
                            ->label('仓库数')
                            ->state(fn ($record) => $record->warehouses()->count()),
                    ])
                    ->columns(3),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCrossSellTenants::route('/'),
            'create' => Pages\CreateCrossSellTenant::route('/create'),
            'edit' => Pages\EditCrossSellTenant::route('/{record}/edit'),
            'view' => Pages\ViewCrossSellTenant::route('/{record}'),
        ];
    }
}
