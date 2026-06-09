<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Group extends Model
{
    protected $fillable = [
        'organization_id', 'name', 'description', 'type', 'dynamic_filters',
    ];

    protected $casts = ['dynamic_filters' => 'array'];

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function targetUsers()
    {
        return $this->belongsToMany(TargetUser::class);
    }

    public function resolveMembers(): \Illuminate\Database\Eloquent\Collection
    {
        if ($this->type === 'static') {
            return $this->targetUsers()->where('is_active', true)->where('excluded', false)->get();
        }

        $query = TargetUser::where('organization_id', $this->organization_id)
            ->where('is_active', true)
            ->where('excluded', false);

        if (!empty($this->dynamic_filters['department'])) {
            $query->where('department', $this->dynamic_filters['department']);
        }
        if (!empty($this->dynamic_filters['tags'])) {
            foreach ($this->dynamic_filters['tags'] as $tag) {
                $query->whereJsonContains('tags', $tag);
            }
        }

        return $query->get();
    }
}
