<?php

namespace App\Models;

use Database\Factories\EmailTemplateFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EmailTemplate extends Model
{
    /** @use HasFactory<EmailTemplateFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'organization_id', 'created_by', 'name', 'subject', 'from_name',
        'html_content', 'text_content', 'category', 'language',
        'tags', 'has_attachment_simulation', 'version', 'is_active',
    ];

    protected $casts = [
        'tags' => 'array',
        'has_attachment_simulation' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function renderForRecipient(TargetUser $user, CampaignRecipient $recipient, Campaign $campaign): string
    {
        $baseUrl = config('app.url');
        $landingUrl = route('track.landing', ['token' => $recipient->tracking_token], false);
        $reportUrl = route('track.report', ['token' => $recipient->tracking_token], false);
        $openPixel = '<img src="' . route('track.open', ['token' => $recipient->tracking_token], false) . '" width="1" height="1" alt="" style="display:none" />';

        $vars = [
            '{{first_name}}'              => e($user->first_name),
            '{{last_name}}'               => e($user->last_name),
            '{{email}}'                   => e($user->email),
            '{{department}}'              => e($user->department ?? ''),
            '{{campaign_name}}'           => e($campaign->name),
            '{{landing_url}}'             => $baseUrl . $landingUrl,
            '{{report_url}}'              => $baseUrl . $reportUrl,
            '{{tracking_pixel}}'          => $openPixel,
        ];

        $html = str_replace(array_keys($vars), array_values($vars), $this->html_content);

        // Inject tracking pixel before </body> if {{tracking_pixel}} not in template
        if (!str_contains($this->html_content, '{{tracking_pixel}}')) {
            $html = str_ireplace('</body>', $openPixel . '</body>', $html);
        }

        return $html;
    }
}
