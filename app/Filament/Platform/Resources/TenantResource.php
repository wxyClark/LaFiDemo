<?php

namespace App\Filament\Platform\Resources;

use App\Filament\Platform\Resources\TenantResource\Pages;
use App\Modules\Tenancy\Application\UpdateTenantStatus;
use App\Modules\Tenancy\Domain\Tenant;
use App\Modules\Tenancy\Domain\TenantStatus;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * 租户管理资源（Platform Panel）。
 *
 * 授权：TenantPolicy（平台分支，见 AppServiceProvider 显式注册）；
 * 写操作经 UpdateTenantStatus 用例（平台高危操作须附原因 + 审计日志）。
 */
class TenantResource extends Resource
{
    protected static ?string $model = Tenant::class;

    protected static ?string $navigationLabel = '租户管理';

    protected static ?string $modelLabel = '租户';

    protected static ?string $pluralModelLabel = '租户';

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            TextInput::make('name')->label('名称')->required(),
            Select::make('status')->label('状态')->required()
                ->options(collect(TenantStatus::cases())->mapWithKeys(
                    fn (TenantStatus $status) => [$status->value => $status->value]
                )),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->label('ID')->sortable(),
                TextColumn::make('name')->label('名称')->searchable()->sortable(),
                TextColumn::make('status')->label('状态')->badge()
                    ->formatStateUsing(fn (TenantStatus $state) => $state->value)
                    ->color(fn (TenantStatus $state) => $state === TenantStatus::Active ? 'success' : 'danger'),
                TextColumn::make('created_at')->label('创建时间')->dateTime('Y-m-d H:i')->sortable(),
            ])
            ->actions([
                EditAction::make(),
                Action::make('toggleStatus')
                    ->label(fn (Tenant $record) => $record->isEnabled() ? '停用' : '启用')
                    ->icon(fn (Tenant $record) => $record->isEnabled() ? 'heroicon-o-pause' : 'heroicon-o-play')
                    ->color(fn (Tenant $record) => $record->isEnabled() ? 'danger' : 'success')
                    ->requiresConfirmation()
                    ->form([
                        TextInput::make('reason')->label('操作原因')->required()
                            ->helperText('平台高危操作：须填写原因并记入审计日志'),
                    ])
                    ->authorize(fn (Tenant $record) => auth()->user()?->can('disable', $record) ?? false)
                    ->action(function (Tenant $record, array $data): void {
                        $newStatus = $record->isEnabled() ? TenantStatus::Disabled : TenantStatus::Active;

                        app(UpdateTenantStatus::class)(
                            (int) $record->getKey(),
                            $newStatus,
                            $data['reason'],
                            (int) auth()->id(),
                        );
                    }),
            ])
            ->defaultSort('id');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTenants::route('/'),
            'edit' => Pages\EditTenant::route('/{record}/edit'),
        ];
    }
}
