<?php

declare(strict_types=1);

namespace App\Modules\Domains\Xinfutong\Models;

use App\Modules\Domains\Xinfutong\Database\Factories\EmployeeFactory;
use App\Modules\Domains\Xinfutong\Enums\EmployeeStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Employee extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'xinfutong_employees';

    protected $fillable = [
        'xinfutong_id',
        'employee_no',
        'name',
        'gender',
        'phone',
        'email',
        'department',
        'position',
        'hire_date',
        'status',
        'raw_data',
        'last_synced_at',
    ];

    protected $casts = [
        'status' => EmployeeStatus::class,
        'hire_date' => 'date',
        'last_synced_at' => 'datetime',
        'raw_data' => 'array',
    ];

    public static function newFactory(): EmployeeFactory
    {
        return EmployeeFactory::new();
    }

    public function syncLogs(): HasMany
    {
        return $this->hasMany(SyncLog::class);
    }
}
