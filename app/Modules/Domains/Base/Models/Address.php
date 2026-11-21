<?php

declare(strict_types=1);

namespace App\Modules\Domains\Base\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * 地址模型（5级行政区划）
 *
 * 支持国家-省-市-区-乡镇 5级地址体系
 */
class Address extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'addresses';

    protected $fillable = [
        'parent_id',
        'name',
        'code',
        'level',
        'level_num',
        'pinyin',
        'merge_path',
        'sort',
    ];

    protected $casts = [
        'merge_path' => 'array',
        'sort' => 'integer',
        'level_num' => 'integer',
    ];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Address::class, 'parent_id');
    }

    /**
     * @return HasMany<Address, $this>
     */
    public function children(): HasMany
    {
        return $this->hasMany(Address::class, 'parent_id')->orderBy('sort')->orderBy('name');
    }

    public function getFullPathAttribute(): string
    {
        if ($this->merge_path) {
            return implode('/', $this->merge_path);
        }

        return $this->name;
    }

    public function getParentNameAttribute(): ?string
    {
        return $this->parent?->name;
    }

    public function scopeByLevel($query, string $level)
    {
        return $query->where('level', $level);
    }

    public function scopeByParent($query, ?int $parentId)
    {
        return $query->where('parent_id', $parentId);
    }

    public function scopeByCode($query, string $code)
    {
        return $query->where('code', $code);
    }
}
