<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\XinfutongEmployeeResource\Pages;
use App\Modules\Domains\Xinfutong\Enums\EmployeeStatus;
use App\Modules\Domains\Xinfutong\Models\Employee;
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

class XinfutongEmployeeResource extends Resource
{
    use HasResourcePermission;

    protected static ?string $model = Employee::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-users';

    protected static string|UnitEnum|null $navigationGroup = '薪福通';

    protected static ?string $navigationLabel = '员工管理';

    protected static ?string $modelLabel = '员工';

    protected static ?string $pluralModelLabel = '员工';

    protected static ?int $navigationSort = 1;

    protected static function getPermissionPrefix(): string
    {
        return 'xinfutong-employee';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Schemas\Components\Section::make('基本信息')
                    ->schema([
                        Forms\Components\TextInput::make('employee_no')
                            ->label('工号')
                            ->required()
                            ->maxLength(64),

                        Forms\Components\TextInput::make('name')
                            ->label('姓名')
                            ->required()
                            ->maxLength(100),

                        Forms\Components\Select::make('gender')
                            ->label('性别')
                            ->options([
                                '男' => '男',
                                '女' => '女',
                            ]),

                        Forms\Components\TextInput::make('phone')
                            ->label('手机号')
                            ->maxLength(20),

                        Forms\Components\TextInput::make('email')
                            ->label('邮箱')
                            ->email()
                            ->maxLength(100),
                    ])
                    ->columns(2),

                Schemas\Components\Section::make('职位信息')
                    ->schema([
                        Forms\Components\TextInput::make('department')
                            ->label('部门')
                            ->maxLength(100),

                        Forms\Components\TextInput::make('position')
                            ->label('职位')
                            ->maxLength(100),

                        Forms\Components\DatePicker::make('hire_date')
                            ->label('入职日期'),

                        Forms\Components\Select::make('status')
                            ->label('状态')
                            ->options(EmployeeStatus::class)
                            ->default(EmployeeStatus::ACTIVE),
                    ])
                    ->columns(2),

                Schemas\Components\Section::make('同步信息')
                    ->schema([
                        Forms\Components\TextInput::make('xinfutong_id')
                            ->label('薪福通 ID')
                            ->readOnly()
                            ->dehydrated(false),

                        Forms\Components\DateTimePicker::make('last_synced_at')
                            ->label('最后同步时间')
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
                Tables\Columns\TextColumn::make('employee_no')
                    ->label('工号')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('name')
                    ->label('姓名')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('department')
                    ->label('部门')
                    ->searchable(),

                Tables\Columns\TextColumn::make('position')
                    ->label('职位'),

                Tables\Columns\TextColumn::make('hire_date')
                    ->label('入职日期')
                    ->date('Y-m-d')
                    ->sortable(),

                Tables\Columns\TextColumn::make('status')
                    ->label('状态')
                    ->badge()
                    ->formatStateUsing(fn (EmployeeStatus $state): string => $state->label())
                    ->color(fn (EmployeeStatus $state): string => $state->color()),

                Tables\Columns\TextColumn::make('last_synced_at')
                    ->label('最后同步')
                    ->dateTime('Y-m-d H:i')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->actions([
                Actions\ViewAction::make(),
                Actions\EditAction::make(),
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
            'index' => Pages\ListXinfutongEmployees::route('/'),
            'create' => Pages\CreateXinfutongEmployee::route('/create'),
            'edit' => Pages\EditXinfutongEmployee::route('/{record}/edit'),
        ];
    }

    /**
     * 触发薪福通员工同步.
     */
    public static function syncFromXinfutong(): void
    {
        try {
            $service = app(XinfutongService::class);
            $log = $service->syncEmployees();

            Notification::make()
                ->title('同步完成')
                ->body("拉取 {$log->records_fetched} 条，新增 {$log->records_created} 条，更新 {$log->records_updated} 条")
                ->success()
                ->send();
        } catch (\Throwable $e) {
            Notification::make()
                ->title('同步失败')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }
}
