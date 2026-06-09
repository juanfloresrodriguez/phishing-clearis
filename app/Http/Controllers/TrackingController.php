<?php

namespace App\Http\Controllers;

use App\Models\CampaignRecipient;
use App\Models\LandingPage;
use App\Services\TrackingService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class TrackingController extends Controller
{
    public function __construct(private TrackingService $tracker) {}

    /** Tracking pixel – 1x1 transparent GIF */
    public function open(Request $request, string $token): Response
    {
        $recipient = CampaignRecipient::where('tracking_token', $token)->first();

        if ($recipient) {
            $alreadyOpened = $recipient->events()
                ->where('event_type', 'email_opened')->exists();

            if (!$alreadyOpened) {
                $this->tracker->recordEvent($recipient, 'email_opened', $request);
            }
        }

        $pixel = base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7');

        return response($pixel, 200, [
            'Content-Type'  => 'image/gif',
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            'Pragma'        => 'no-cache',
        ]);
    }

    /** Signed landing link */
    public function landing(Request $request, string $token)
    {
        $recipient = CampaignRecipient::where('tracking_token', $token)
            ->with(['campaign.landingPage'])
            ->first();

        if (!$recipient) {
            abort(404);
        }

        $alreadyClicked = $recipient->events()->where('event_type', 'link_clicked')->exists();
        if (!$alreadyClicked) {
            $this->tracker->recordEvent($recipient, 'link_clicked', $request);
        }

        $this->tracker->recordEvent($recipient, 'landing_loaded', $request);

        $landingPage = $recipient->campaign?->landingPage;

        if (!$landingPage) {
            return view('tracking.generic-landing', ['token' => $token]);
        }

        $html = $this->injectTrackingForm($landingPage->html_content, $token);

        return response($html)->header('Content-Type', 'text/html');
    }

    /** Form submit from landing page */
    public function submit(Request $request, string $token)
    {
        $recipient = CampaignRecipient::where('tracking_token', $token)
            ->with(['campaign.landingPage'])
            ->first();

        if (!$recipient) {
            abort(404);
        }

        $alreadyStarted = $recipient->events()->where('event_type', 'form_started')->exists();
        if (!$alreadyStarted) {
            $this->tracker->recordEvent($recipient, 'form_started', $request);
        }

        // Record form submit – values are discarded, only field metadata stored
        $this->tracker->recordFormSubmit($recipient, $request, $request->except(['_token', '_method', '__tracking_token']));

        $landingPage = $recipient->campaign?->landingPage;

        // Show educational page
        if ($landingPage?->show_training_after_submit) {
            $this->tracker->recordEvent($recipient, 'training_seen', $request);
            $trainingHtml = $landingPage->training_html ?? $this->defaultTrainingHtml();
            return response($trainingHtml)->header('Content-Type', 'text/html');
        }

        if ($landingPage?->redirect_url) {
            return redirect($landingPage->redirect_url);
        }

        return view('tracking.training');
    }

    /** User reports the phishing email */
    public function report(Request $request, string $token)
    {
        $recipient = CampaignRecipient::where('tracking_token', $token)->first();

        if ($recipient) {
            $alreadyReported = $recipient->events()->where('event_type', 'email_reported')->exists();
            if (!$alreadyReported) {
                $this->tracker->recordEvent($recipient, 'email_reported', $request);
            }
        }

        return view('tracking.reported');
    }

    private function injectTrackingForm(string $html, string $token): string
    {
        $injection = '<input type="hidden" name="__tracking_token" value="' . e($token) . '">';

        // Replace form action to point to our tracking endpoint
        $html = preg_replace_callback(
            '/<form([^>]*)>/i',
            fn($m) => '<form' . $m[1] . ' action="' . route('track.submit', $token) . '" method="POST">' . "\n"
                . '<input type="hidden" name="_token" value="' . csrf_token() . '">' . "\n"
                . $injection,
            $html
        );

        return $html;
    }

    private function defaultTrainingHtml(): string
    {
        return <<<HTML
<!DOCTYPE html>
<html lang="es">
<head><meta charset="UTF-8"><title>Simulación de Phishing</title>
<style>body{font-family:sans-serif;max-width:600px;margin:60px auto;text-align:center;color:#333}
.alert{background:#fff3cd;border:1px solid #ffc107;padding:20px;border-radius:8px;margin:20px 0}
h1{color:#dc3545}</style></head>
<body>
<h1>⚠️ Esto era una simulación de phishing</h1>
<div class="alert">
<p>Has interactuado con un email de simulación de concienciación sobre ciberseguridad.</p>
<p><strong>No se han enviado tus datos reales a ningún lugar.</strong></p>
</div>
<p>Aprende a identificar emails maliciosos: comprueba siempre el remitente real, los enlaces antes de hacer clic y solicitudes inusuales de credenciales.</p>
<p><a href="mailto:security@tuempresa.com">Contacta al equipo de seguridad si tienes dudas.</a></p>
</body></html>
HTML;
    }
}
