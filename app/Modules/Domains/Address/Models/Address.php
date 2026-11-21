<?php

declare(strict_types=1);

namespace App\Modules\Domains\Address\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Modules\Domains\Address\Models\Address where($attribute, $operator = null, $value = null, $boolean = 'and')
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Modules\Domains\Address\Models\Address whereParentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Modules\Domains\Address\Models\Address whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Modules\Domains\Address\Models\Address whereCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Modules\Domains\Address\Models\Address whereLevel($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Modules\Domains\Address\Models\Address whereLevelNum($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Modules\Domains\Address\Models\Address wherePinyin($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Modules\Domains\Address\Models\Address whereMergePath($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Modules\Domains\Address\Models\Address whereSort($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Modules\Domains\Address\Models\Address newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Modules\Domains\Address\Models\Address newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Modules\Domains\Address\Models\Address query()
 *
 * @mixin \Eloquent
 */
class Address extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'addresses';

    protected $fillable = [
        'parent_id',
        'name',
        'code',
        'area_code',
        'level',
        'level_num',
        'pinyin',
        'merge_path',
        'sort',
        'source',
        'synced_at',
    ];

    protected $casts = [
        'merge_path' => 'array',
        'sort' => 'integer',
        'level_num' => 'integer',
        'synced_at' => 'datetime',
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
