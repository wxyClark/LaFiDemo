<?php

namespace App\Filament\Platform\Resources\TenantResource\Pages;

use App\Filament\Platform\Resources\TenantResource;
use Filament\Resources\Pages\EditRecord;

class EditTenant extends EditRecord
{
    protected static string $resource = TenantResource::class;

    /**
     * 租户不提供硬删除（业务上用"停用"替代，见 UpdateTenantStatus 与
     * TenantPolicy::disable）；编辑页仅保留保存。
     */
    protected function getHeaderActions(): array
    {
        return [];
    }
}
