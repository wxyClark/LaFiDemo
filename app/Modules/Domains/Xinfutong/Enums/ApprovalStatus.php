<?php

declare(strict_types=1);

namespace App\Modules\Domains\Xinfutong\Enums;

enum ApprovalStatus: string
{
    case DRAFT = 'draft';
    case PENDING = 'pending';
    case APPROVED = 'approved';
    case REJECTED = 'rejected';
    case WITHDRAWN = 'withdrawn';

    public function label(): string
    {
        return match ($this) {
            self::DRAFT => '草稿',
            self::PENDING => '审批中',
            self::APPROVED => '已通过',
            self::REJECTED => '已驳回',
            self::WITHDRAWN => '已撤回',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::DRAFT => 'gray',
            self::PENDING => 'warning',
            self::APPROVED => 'success',
            self::REJECTED => 'danger',
            self::WITHDRAWN => 'info',
        };
    }
}
