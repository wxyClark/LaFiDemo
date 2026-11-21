<?php

declare(strict_types=1);

namespace App\Modules\Domains\Xinfutong\Models;

use App\Modules\Domains\Xinfutong\Database\Factories\AccountFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Account extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'xinfutong_accounts';

    protected $fillable = [
        'company_name',
        'base_url',
        'app_id',
        'authority_secret',
        'sign_private_key',
        'verify_public_key',
        'timeout',
        'is_default',
        'is_active',
        'remark',
    ];

    protected $casts = [
        'timeout' => 'integer',
        'is_default' => 'boolean',
        'is_active' => 'boolean',
    ];

    protected $hidden = [
        'sign_private_key',
        'authority_secret',
    ];

    public static function newFactory(): AccountFactory
    {
        return AccountFactory::new();
    }

    /**
     * 获取默认账号.
     */
    public static function getDefault(): ?self
    {
        return static::query()
            ->where('is_default', true)
            ->where('is_active', true)
            ->first();
    }

    /**
     * 获取所有活跃账号.
     */
    public static function getActive(): Collection
    {
        return static::query()
            ->where('is_active', true)
            ->orderBy('company_name')
            ->get();
    }

    /**
     * 设为默认账号.
     */
    public function setAsDefault(): void
    {
        static::query()->update(['is_default' => false]);
        $this->update(['is_default' => true]);
    }

    /**
     * 同步日志.
     */
    public function syncLogs(): HasMany
    {
        return $this->hasMany(SyncLog::class);
    }

    /**
     * 审批请求.
     */
    public function approvalRequests(): HasMany
    {
        return $this->hasMany(ApprovalRequest::class);
    }
}
