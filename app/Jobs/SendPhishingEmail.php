<?php

namespace App\Jobs;

use App\Models\CampaignEvent;
use App\Models\CampaignRecipient;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Mail\Message;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendPhishingEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 60;

    public function __construct(public CampaignRecipient $recipient) {}

    public function handle(): void
    {
        $campaign = $this->recipient->campaign;

        if (!in_array($campaign->status, ['running', 'dry_run'])) {
            return;
        }

        $user = $this->recipient->targetUser;
        $template = $campaign->emailTemplate;
        $profile = $campaign->sendingProfile;

        // Validate that recipient domain is authorized
        $recipientDomain = substr(strrchr($user->email, '@'), 1);
        $authorizedDomains = $campaign->organization->verifiedDomains()
            ->where('allow_recipients', true)
            ->pluck('domain');

        if (!$authorizedDomains->contains($recipientDomain)) {
            $this->fail("Recipient domain {$recipientDomain} is not authorized for this organization.");
            return;
        }

        $htmlBody = $template->renderForRecipient($user, $this->recipient, $campaign);
        $subject = $this->replaceVars($campaign->subject, $user);
        $fromName = $template->from_name ?? $profile->from_name;

        if ($campaign->is_dry_run) {
            // Dry run: log only, no actual send
            $this->recipient->update(['sent_at' => now()]);
            $this->logEvent('email_sent');
            return;
        }

        try {
            $mailerConfig = $this->buildMailerConfig($profile);

            Mail::mailer('campaign_smtp')
                ->html($htmlBody)
                ->to($user->email, $user->fullName())
                ->from($profile->from_email, $fromName)
                ->subject($subject)
                ->send(new \App\Mail\PhishingEmail($htmlBody, $subject, $profile->from_email, $fromName));

            $this->recipient->update(['sent_at' => now()]);
            $this->logEvent('email_sent');
        } catch (\Throwable $e) {
            $this->recipient->update([
                'send_error' => true,
                'send_error_message' => substr($e->getMessage(), 0, 500),
            ]);
            $this->logEvent('send_failed', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    private function logEvent(string $type, array $metadata = []): void
    {
        CampaignEvent::create([
            'campaign_id'           => $this->recipient->campaign_id,
            'campaign_recipient_id' => $this->recipient->id,
            'target_user_id'        => $this->recipient->target_user_id,
            'event_type'            => $type,
            'metadata'              => $metadata ?: null,
            'occurred_at'           => now(),
        ]);
    }

    private function replaceVars(string $subject, $user): string
    {
        return str_replace(
            ['{{first_name}}', '{{last_name}}', '{{email}}'],
            [$user->first_name, $user->last_name, $user->email],
            $subject
        );
    }

    private function buildMailerConfig($profile): array
    {
        $config = [
            'transport' => 'smtp',
            'host'      => $profile->smtp_host,
            'port'      => $profile->smtp_port,
            'encryption'=> $profile->smtp_encryption !== 'none' ? $profile->smtp_encryption : null,
            'username'  => $profile->smtp_username,
            'password'  => $profile->smtp_password,
        ];

        config(['mail.mailers.campaign_smtp' => $config]);
        return $config;
    }
}
