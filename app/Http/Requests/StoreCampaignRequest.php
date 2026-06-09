<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCampaignRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'name'                  => 'required|string|max:255',
            'subject'               => 'required|string|max:255',
            'description'           => 'nullable|string|max:1000',
            'email_template_id'     => 'required|exists:email_templates,id',
            'landing_page_id'       => 'nullable|exists:landing_pages,id',
            'sending_profile_id'    => 'required|exists:sending_profiles,id',
            'group_ids'             => 'required|array|min:1',
            'group_ids.*'           => 'exists:groups,id',
            'scheduled_start_at'    => 'nullable|date',
            'scheduled_end_at'      => 'nullable|date|after:scheduled_start_at',
            'send_window_start'     => 'nullable|date_format:H:i',
            'send_window_end'       => 'nullable|date_format:H:i|after:send_window_start',
            'respect_work_hours'    => 'boolean',
            'respect_work_days'     => 'boolean',
            'rate_limit_per_minute' => 'integer|min:1|max:500',
            'language'              => 'nullable|string|max:5',
            'excluded_user_ids'     => 'nullable|array',
        ];
    }
}
