<?php

use App\Http\Controllers\CampaignController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EmailTemplateController;
use App\Http\Controllers\GroupController;
use App\Http\Controllers\LandingPageController;
use App\Http\Controllers\OrganizationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SendingProfileController;
use App\Http\Controllers\TargetUserController;
use App\Http\Controllers\TrackingController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// ─── Public tracking routes (no auth) ─────────────────────────────────────
Route::prefix('t')->name('track.')->group(function () {
    Route::get('/o/{token}', [TrackingController::class, 'open'])->name('open');
    Route::get('/l/{token}', [TrackingController::class, 'landing'])->name('landing');
    Route::post('/s/{token}', [TrackingController::class, 'submit'])->name('submit');
    Route::get('/r/{token}', [TrackingController::class, 'report'])->name('report');
});

// ─── Welcome / login redirect ──────────────────────────────────────────────
Route::get('/', fn() => redirect()->route('login'));

// ─── Authenticated routes ──────────────────────────────────────────────────
Route::middleware(['auth', 'verified'])->group(function () {

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Profile
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Organization
    Route::get('/organization', [OrganizationController::class, 'show'])->name('organization.show');
    Route::patch('/organization', [OrganizationController::class, 'update'])->name('organization.update');
    Route::post('/organization/domains', [OrganizationController::class, 'addDomain'])->name('organization.domains.add');
    Route::post('/organization/domains/{domain}/verify', [OrganizationController::class, 'verifyDomain'])->name('organization.domains.verify');
    Route::delete('/organization/domains/{domain}', [OrganizationController::class, 'removeDomain'])->name('organization.domains.remove');

    // Sending Profiles
    Route::resource('sending-profiles', SendingProfileController::class)->only(['index', 'store', 'destroy']);
    Route::post('/sending-profiles/{sendingProfile}/check-dns', [SendingProfileController::class, 'checkDns'])->name('sending-profiles.check-dns');
    Route::post('/sending-profiles/{sendingProfile}/verify', [SendingProfileController::class, 'verify'])->name('sending-profiles.verify');

    // Target Users
    Route::get('/users', [TargetUserController::class, 'index'])->name('target-users.index');
    Route::post('/users', [TargetUserController::class, 'store'])->name('target-users.store');
    Route::delete('/users/{targetUser}', [TargetUserController::class, 'destroy'])->name('target-users.destroy');
    Route::post('/users/{targetUser}/exclude', [TargetUserController::class, 'toggleExclude'])->name('target-users.exclude');
    Route::post('/users/import', [TargetUserController::class, 'importCsv'])->name('target-users.import');

    // Groups
    Route::resource('groups', GroupController::class)->only(['index', 'store', 'update', 'destroy']);

    // Email Templates
    Route::resource('email-templates', EmailTemplateController::class);
    Route::post('/email-templates/{emailTemplate}/send-test', [EmailTemplateController::class, 'sendTest'])->name('email-templates.send-test');

    // Landing Pages
    Route::resource('landing-pages', LandingPageController::class);

    // Campaigns
    Route::resource('campaigns', CampaignController::class);
    Route::post('/campaigns/{campaign}/launch', [CampaignController::class, 'launch'])->name('campaigns.launch');
    Route::post('/campaigns/{campaign}/pause', [CampaignController::class, 'pause'])->name('campaigns.pause');
    Route::post('/campaigns/{campaign}/cancel', [CampaignController::class, 'cancel'])->name('campaigns.cancel');
    Route::post('/campaigns/{campaign}/dry-run', [CampaignController::class, 'dryRun'])->name('campaigns.dry-run');
    Route::get('/campaigns/{campaign}/export', [CampaignController::class, 'exportCsv'])->name('campaigns.export');
});

require __DIR__ . '/auth.php';
