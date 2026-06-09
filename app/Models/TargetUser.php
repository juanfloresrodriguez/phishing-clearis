<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TargetUser extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'organization_id', 'first_name', 'last_name', 'email',
        'department', 'job_title', 'office', 'language', 'tags',
        'is_active', 'excluded', 'import_source',
    ];

    protected $casts = [
        'tags' => 'array',
        'is_active' => 'boolean',
        'excluded' => 'boolean',
    ];

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function groups()
    {
        return $this->belongsToMany(Group::class);
    }

    public function fullName(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }
}
