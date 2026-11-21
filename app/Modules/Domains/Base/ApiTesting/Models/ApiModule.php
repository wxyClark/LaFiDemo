<?php

declare(strict_types=1);

namespace App\Modules\Domains\Base\ApiTesting\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class ApiModule extends Model
{
    use HasFactory;

    protected $table = 'api_modules';

    protected $fillable = [
        'name',
        'description',
        'icon',
        'sort_order',
    ];

    public function functions(): HasMany
    {
        return $this->hasMany(ApiFunction::class, 'module_id');
    }

    public function interfaces(): HasManyThrough
    {
        return $this->hasManyThrough(ApiInterface::class, ApiFunction::class, 'module_id', 'function_id');
    }
}
