<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class LandingPage extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'organization_id', 'created_by', 'name', 'html_content',
        'training_html', 'redirect_url', 'language',
        'capture_credentials', 'show_training_after_submit', 'is_active',
    ];

    protected $casts = [
        'capture_credentials' => 'boolean',
        'show_training_after_submit' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }
}
