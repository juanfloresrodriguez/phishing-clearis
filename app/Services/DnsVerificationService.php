<?php

namespace App\Services;

use App\Models\OrganizationDomain;
use App\Models\SendingProfile;

class DnsVerificationService
{
    public function generateVerificationToken(OrganizationDomain $domain): string
    {
        $token = 'clearphish-verify=' . bin2hex(random_bytes(16));
        $domain->update(['verification_token' => $token]);
        return $token;
    }

    public function verifyDomain(OrganizationDomain $domain): bool
    {
        $records = @dns_get_record($domain->domain, DNS_TXT);
        if (!$records) return false;

        foreach ($records as $record) {
            if (isset($record['txt']) && str_contains($record['txt'], $domain->verification_token)) {
                $domain->update([
                    'is_verified' => true,
                    'verified_at' => now(),
                ]);
                return true;
            }
        }

        return false;
    }

    public function checkSendingProfileDns(SendingProfile $profile): array
    {
        $domain = substr(strrchr($profile->from_email, '@'), 1);
        $results = ['spf' => false, 'dkim' => false, 'dmarc' => false];

        // SPF check
        $txtRecords = @dns_get_record($domain, DNS_TXT) ?: [];
        foreach ($txtRecords as $r) {
            if (isset($r['txt']) && str_starts_with($r['txt'], 'v=spf1')) {
                $results['spf'] = true;
            }
        }

        // DMARC check
        $dmarcRecords = @dns_get_record('_dmarc.' . $domain, DNS_TXT) ?: [];
        foreach ($dmarcRecords as $r) {
            if (isset($r['txt']) && str_starts_with($r['txt'], 'v=DMARC1')) {
                $results['dmarc'] = true;
            }
        }

        // DKIM: we check if a default selector exists (selector1 or google)
        foreach (['google', 'selector1', 'default', 'k1'] as $selector) {
            $dkimRecords = @dns_get_record("{$selector}._domainkey.{$domain}", DNS_TXT) ?: [];
            foreach ($dkimRecords as $r) {
                if (isset($r['txt']) && str_contains($r['txt'], 'p=')) {
                    $results['dkim'] = true;
                    break 2;
                }
            }
        }

        $profile->update([
            'spf_ok'          => $results['spf'],
            'dkim_ok'         => $results['dkim'],
            'dmarc_ok'        => $results['dmarc'],
            'dns_checked_at'  => now(),
        ]);

        return $results;
    }
}
