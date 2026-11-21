<?php

declare(strict_types=1);

namespace App\Modules\Domains\Xinfutong\Models;

use App\Modules\Domains\Xinfutong\Database\Factories\ApprovalRequestFactory;
use App\Modules\Domains\Xinfutong\Enums\ApprovalStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ApprovalRequest extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'xinfutong_approval_requests';

    protected $fillable = [
        'approval_no',
        'approval_type',
        'title',
        'description',
        'form_data',
        'status',
        'applicant_name',
        'applicant_id',
        'xinfutong_approval_id',
        'error_message',
        'raw_response',
        'submitted_at',
        'approved_at',
    ];

    protected $casts = [
        'status' => ApprovalStatus::class,
        'form_data' => 'array',
        'raw_response' => 'array',
        'submitted_at' => 'datetime',
        'approved_at' => 'datetime',
    ];

    public static function newFactory(): ApprovalRequestFactory
    {
        return ApprovalRequestFactory::new();
    }
}
