<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\XinfutongApprovalResource\Pages;
use App\Modules\Domains\Xinfutong\Enums\ApprovalStatus;
use App\Modules\Domains\Xinfutong\Models\ApprovalRequest;
use App\Modules\Domains\Xinfutong\Services\XinfutongService;
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

class XinfutongApprovalResource extends Resource
{
    use HasResourcePermission;

    protected static ?string $model = ApprovalRequest::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-document-check';

    protected static string|UnitEnum|null $navigationGroup = '薪福通';

    protected static ?string $navigationLabel = '审批提单';

    protected static ?string $modelLabel = '审批单';

    protected static ?string $pluralModelLabel = '审批单';

    protected static ?int $navigationSort = 3;

    protected static function getPermissionPrefix(): string
    {
        return 'xinfutong-approval';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Schemas\Components\Section::make('基本信息')
                    ->schema([
                        Forms\Components\TextInput::make('approval_no')
                            ->label('审批单号')
                            ->readOnly()
                            ->dehydrated(false)
                            ->default(fn () => 'APR'.date('Ymd').rand(10000, 99999)),

                        Forms\Components\Select::make('approval_type')
                            ->label('审批类型')
                            ->options([
                                'leave' => '请假',
                                'expense' => '报销',
                                'hire' => '入职',
                                'resign' => '离职',
                                'other' => '其他',
                            ])
                            ->required(),

                        Forms\Components\TextInput::make('title')
                            ->label('审批标题')
                            ->required()
                            ->maxLength(200),

                        Forms\Components\Textarea::make('description')
                            ->label('审批说明')
                            ->rows(3),
                    ])
                    ->columns(2),

                Schemas\Components\Section::make('申请人信息')
                    ->schema([
                        Forms\Components\TextInput::make('applicant_name')
                            ->label('申请人姓名')
                            ->required()
                            ->maxLength(100),

                        Forms\Components\TextInput::make('applicant_id')
                            ->label('申请人员工 ID')
                            ->maxLength(64),
                    ])
                    ->columns(2),

                Schemas\Components\Section::make('审批数据')
                    ->schema([
                        Forms\Components\KeyValue::make('form_data')
                            ->label('表单数据')
                            ->helperText('以 JSON 格式填写审批相关的表单数据'),
                    ]),

                Schemas\Components\Section::make('状态信息')
                    ->schema([
                        Forms\Components\Select::make('status')
                            ->label('状态')
                            ->options(ApprovalStatus::class)
                            ->default(ApprovalStatus::DRAFT)
                            ->disabled(),

                        Forms\Components\TextInput::make('xinfutong_approval_id')
                            ->label('薪福通审批 ID')
                            ->readOnly()
                            ->dehydrated(false),

                        Forms\Components\DateTimePicker::make('submitted_at')
                            ->label('提交时间')
                            ->readOnly()
                            ->dehydrated(false),

                        Forms\Components\DateTimePicker::make('approved_at')
                            ->label('审批完成时间')
                            ->readOnly()
                            ->dehydrated(false),
                    ])
                    ->columns(2)
                    ->collapsible(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('approval_no')
                    ->label('审批单号')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('approval_type')
                    ->label('类型')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'leave' => '请假',
                        'expense' => '报销',
                        'hire' => '入职',
                        'resign' => '离职',
                        default => '其他',
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'leave' => 'info',
                        'expense' => 'warning',
                        'hire' => 'success',
                        'resign' => 'danger',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('title')
                    ->label('标题')
                    ->limit(30)
                    ->searchable(),

                Tables\Columns\TextColumn::make('applicant_name')
                    ->label('申请人')
                    ->searchable(),

                Tables\Columns\TextColumn::make('status')
                    ->label('状态')
                    ->badge()
                    ->formatStateUsing(fn (ApprovalStatus $state): string => $state->label())
                    ->color(fn (ApprovalStatus $state): string => $state->color()),

                Tables\Columns\TextColumn::make('submitted_at')
                    ->label('提交时间')
                    ->dateTime('Y-m-d H:i')
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('创建时间')
                    ->dateTime('Y-m-d H:i')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->actions([
                Actions\ViewAction::make(),
                Actions\EditAction::make(),
                Actions\Action::make('submit')
                    ->label('提交审批')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('primary')
                    ->requiresConfirmation()
                    ->modalHeading('提交审批到薪福通')
                    ->modalDescription('确认将此审批单提交到薪福通进行审批？')
                    ->visible(fn (ApprovalRequest $record): bool => $record->status === ApprovalStatus::DRAFT)
                    ->action(function (ApprovalRequest $record): void {
                        try {
                            $service = app(XinfutongService::class);
                            $service->submitApproval($record);

                            Notification::make()
                                ->title('审批提交成功')
                                ->body("审批单 {$record->approval_no} 已提交到薪福通")
                                ->success()
                                ->send();
                        } catch (\Throwable $e) {
                            Notification::make()
                                ->title('审批提交失败')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
                Actions\Action::make('checkStatus')
                    ->label('查询状态')
                    ->icon('heroicon-o-arrow-path')
                    ->color('gray')
                    ->visible(fn (ApprovalRequest $record): bool => $record->status === ApprovalStatus::PENDING)
                    ->action(function (ApprovalRequest $record): void {
                        try {
                            $service = app(XinfutongService::class);
                            $service->queryApprovalStatus($record);

                            Notification::make()
                                ->title('状态已更新')
                                ->body("当前状态: {$record->status->label()}")
                                ->success()
                                ->send();
                        } catch (\Throwable $e) {
                            Notification::make()
                                ->title('查询失败')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
                Actions\Action::make('withdraw')
                    ->label('撤回')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('撤回审批')
                    ->modalDescription('确认撤回此审批单？撤回后将无法恢复。')
                    ->visible(fn (ApprovalRequest $record): bool => $record->status === ApprovalStatus::PENDING)
                    ->action(function (ApprovalRequest $record): void {
                        try {
                            $service = app(XinfutongService::class);
                            $service->withdrawApproval($record);

                            Notification::make()
                                ->title('审批已撤回')
                                ->body("审批单 {$record->approval_no} 已撤回")
                                ->success()
                                ->send();
                        } catch (\Throwable $e) {
                            Notification::make()
                                ->title('撤回失败')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
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
            'index' => Pages\ListXinfutongApprovals::route('/'),
            'create' => Pages\CreateXinfutongApproval::route('/create'),
            'edit' => Pages\EditXinfutongApproval::route('/{record}/edit'),
        ];
    }
}
