<?php

namespace Tests\Feature\Tracking;

use App\Models\Campaign;
use App\Models\CampaignEvent;
use App\Models\CampaignRecipient;
use App\Models\EmailTemplate;
use App\Models\LandingPage;
use App\Models\Organization;
use App\Models\SendingProfile;
use App\Models\TargetUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TrackingPixelTest extends TestCase
{
    use RefreshDatabase;

    private function createRecipient(): CampaignRecipient
    {
        $org = Organization::factory()->create();
        $template = EmailTemplate::factory()->create(['organization_id' => $org->id]);
        $profile = SendingProfile::factory()->create(['organization_id' => $org->id]);
        $campaign = Campaign::factory()->create([
            'organization_id'    => $org->id,
            'email_template_id'  => $template->id,
            'sending_profile_id' => $profile->id,
            'status'             => 'running',
        ]);
        $user = TargetUser::factory()->create(['organization_id' => $org->id]);

        return CampaignRecipient::create([
            'campaign_id'    => $campaign->id,
            'target_user_id' => $user->id,
            'tracking_token' => 'test-token-123',
        ]);
    }

    public function test_tracking_pixel_returns_gif(): void
    {
        $recipient = $this->createRecipient();

        $response = $this->get(route('track.open', $recipient->tracking_token));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'image/gif');
    }

    public function test_tracking_pixel_records_open_event(): void
    {
        $recipient = $this->createRecipient();

        $this->get(route('track.open', $recipient->tracking_token));

        $this->assertDatabaseHas('campaign_events', [
            'campaign_recipient_id' => $recipient->id,
            'event_type'            => 'email_opened',
        ]);
    }

    public function test_tracking_pixel_does_not_double_count_opens(): void
    {
        $recipient = $this->createRecipient();

        $this->get(route('track.open', $recipient->tracking_token));
        $this->get(route('track.open', $recipient->tracking_token));

        $this->assertEquals(1, CampaignEvent::where([
            'campaign_recipient_id' => $recipient->id,
            'event_type'            => 'email_opened',
        ])->count());
    }

    public function test_invalid_token_returns_gif_silently(): void
    {
        $response = $this->get(route('track.open', 'invalid-token-xyz'));
        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'image/gif');
    }

    public function test_form_submit_does_not_store_passwords(): void
    {
        $recipient = $this->createRecipient();

        $this->post(route('track.submit', $recipient->tracking_token), [
            'email'    => 'user@test.com',
            'password' => 'super-secret-password',
        ]);

        // Should not find the password value in any database record
        $events = CampaignEvent::where('event_type', 'form_submitted')->get();
        foreach ($events as $event) {
            $metadata = json_encode($event->metadata);
            $this->assertStringNotContainsString('super-secret-password', $metadata);
        }
    }
}
