<?php

declare(strict_types=1);

namespace App\Modules\Domains\Xinfutong\Models;

use App\Modules\Domains\Xinfutong\Database\Factories\SyncLogFactory;
use App\Modules\Domains\Xinfutong\Enums\SyncStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SyncLog extends Model
{
    use HasFactory;

    protected $table = 'xinfutong_sync_logs';

    protected $fillable = [
        'employee_id',
        'sync_type',
        'status',
        'records_fetched',
        'records_created',
        'records_updated',
        'error_message',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'status' => SyncStatus::class,
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public static function newFactory(): SyncLogFactory
    {
        return SyncLogFactory::new();
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
