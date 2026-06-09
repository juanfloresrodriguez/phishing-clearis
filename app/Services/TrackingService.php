<?php

namespace App\Services;

use App\Models\CampaignEvent;
use App\Models\CampaignRecipient;
use Illuminate\Http\Request;
use Jenssegers\Agent\Agent;

class TrackingService
{
    public function recordEvent(
        CampaignRecipient $recipient,
        string $eventType,
        Request $request,
        array $metadata = []
    ): CampaignEvent {
        $ip = $this->truncateIp($request->ip());
        $parsed = $this->parseUserAgent($request->userAgent() ?? '');

        return CampaignEvent::create([
            'campaign_id'           => $recipient->campaign_id,
            'campaign_recipient_id' => $recipient->id,
            'target_user_id'        => $recipient->target_user_id,
            'event_type'            => $eventType,
            'ip_address'            => $ip,
            'user_agent'            => substr($request->userAgent() ?? '', 0, 255),
            'device_type'           => $parsed['device'],
            'browser'               => $parsed['browser'],
            'os'                    => $parsed['os'],
            'metadata'              => $metadata,
            'occurred_at'           => now(),
        ]);
    }

    public function recordFormSubmit(
        CampaignRecipient $recipient,
        Request $request,
        array $formFields
    ): CampaignEvent {
        // NEVER store actual values — only field names and types
        $safeMetadata = [];
        foreach ($formFields as $fieldName => $value) {
            $safeMetadata[] = [
                'field_name' => $fieldName,
                'field_type' => $this->detectFieldType($fieldName),
                'was_filled' => !empty($value),
                // Value is intentionally discarded/hashed
            ];
        }

        return $this->recordEvent($recipient, 'form_submitted', $request, [
            'fields' => $safeMetadata,
        ]);
    }

    private function truncateIp(string $ip): string
    {
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            // Truncate last octet: 192.168.1.x → 192.168.1.0
            return preg_replace('/\.\d+$/', '.0', $ip);
        }
        // For IPv6 truncate last 2 groups
        $parts = explode(':', $ip);
        if (count($parts) >= 4) {
            $parts[count($parts) - 1] = 'xxxx';
            $parts[count($parts) - 2] = 'xxxx';
            return implode(':', $parts);
        }
        return $ip;
    }

    private function parseUserAgent(string $ua): array
    {
        // Simple UA parsing without external package dependency
        $device = 'desktop';
        if (preg_match('/mobile|android|iphone/i', $ua)) $device = 'mobile';
        elseif (preg_match('/tablet|ipad/i', $ua)) $device = 'tablet';

        $browser = 'Unknown';
        if (str_contains($ua, 'Chrome')) $browser = 'Chrome';
        elseif (str_contains($ua, 'Firefox')) $browser = 'Firefox';
        elseif (str_contains($ua, 'Safari')) $browser = 'Safari';
        elseif (str_contains($ua, 'Edge')) $browser = 'Edge';

        $os = 'Unknown';
        if (str_contains($ua, 'Windows')) $os = 'Windows';
        elseif (str_contains($ua, 'Mac')) $os = 'macOS';
        elseif (str_contains($ua, 'Linux')) $os = 'Linux';
        elseif (str_contains($ua, 'Android')) $os = 'Android';
        elseif (str_contains($ua, 'iPhone') || str_contains($ua, 'iPad')) $os = 'iOS';

        return compact('device', 'browser', 'os');
    }

    private function detectFieldType(string $fieldName): string
    {
        $name = strtolower($fieldName);
        if (str_contains($name, 'pass') || str_contains($name, 'pwd')) return 'password';
        if (str_contains($name, 'email') || str_contains($name, 'mail')) return 'email';
        if (str_contains($name, 'user') || str_contains($name, 'login')) return 'username';
        return 'text';
    }
}
