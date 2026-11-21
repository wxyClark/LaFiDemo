<?php

declare(strict_types=1);

namespace App\Modules\Domains\ApiTesting\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ApiFunction extends Model
{
    protected $table = 'api_functions';

    protected $fillable = [
        'module_id',
        'name',
        'description',
        'sort_order',
    ];

    public function module(): BelongsTo
    {
        return $this->belongsTo(ApiModule::class);
    }

    public function interfaces(): HasMany
    {
        return $this->hasMany(ApiInterface::class, 'function_id');
    }
}
