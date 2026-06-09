<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class AuditLog extends Model
{
    protected $fillable = [
        'user_id', 'organization_id', 'action',
        'subject_type', 'subject_id', 'old_values', 'new_values',
        'ip_address', 'user_agent',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
    ];

    public static function record(
        string $action,
        mixed $subject = null,
        array $oldValues = [],
        array $newValues = []
    ): self {
        return self::create([
            'user_id'        => Auth::id(),
            'organization_id'=> Auth::user()?->organization_id,
            'action'         => $action,
            'subject_type'   => $subject ? get_class($subject) : null,
            'subject_id'     => $subject?->id,
            'old_values'     => $oldValues ?: null,
            'new_values'     => $newValues ?: null,
            'ip_address'     => Request::ip(),
            'user_agent'     => Request::userAgent(),
        ]);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }
}
