<?php

declare(strict_types=1);

namespace App\Modules\Domains\Base\Logging\Models;

use App\Modules\Models\Admin;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class AdminAuditLog extends Model
{
    protected $table = 'admin_audit_logs';

    protected $fillable = [
        'admin_id',
        'admin_name',
        'admin_email',
        'action',
        'subject_type',
        'subject_id',
        'old_values',
        'new_values',
        'ip_address',
        'user_agent',
        'description',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
    ];

    protected $hidden = [
        'user_agent',
    ];

    public function admin(): BelongsTo
    {
        return $this->belongsTo(Admin::class);
    }

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    public function getActionLabelAttribute(): string
    {
        return match ($this->action) {
            'created' => '创建',
            'updated' => '更新',
            'deleted' => '删除',
            'login' => '登录',
            'logout' => '登出',
            'login_failed' => '登录失败',
            default => $this->action,
        };
    }

    public function getActionColorAttribute(): string
    {
        return match ($this->action) {
            'created' => 'success',
            'updated' => 'info',
            'deleted' => 'danger',
            'login' => 'success',
            'logout' => 'warning',
            'login_failed' => 'danger',
            default => 'gray',
        };
    }

    public static function record(
        string $action,
        ?Model $subject = null,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?string $description = null,
    ): static {
        $admin = auth('admin')->user();

        return static::create([
            'admin_id' => $admin?->id,
            'admin_name' => $admin?->name ?? 'System',
            'admin_email' => $admin?->email ?? 'system',
            'action' => $action,
            'subject_type' => $subject?->getMorphClass(),
            'subject_id' => $subject?->getKey(),
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'description' => $description,
        ]);
    }
}
