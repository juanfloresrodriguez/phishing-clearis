<?php

namespace Database\Seeders;

use App\Models\EmailTemplate;
use App\Models\Group;
use App\Models\LandingPage;
use App\Models\Organization;
use App\Models\OrganizationDomain;
use App\Models\SendingProfile;
use App\Models\TargetUser;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Create roles
        $roles = ['superadmin', 'org_admin', 'campaign_manager', 'analyst', 'viewer'];
        foreach ($roles as $role) {
            Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
        }

        // Detectar dominio desde APP_URL o usar demo.local como fallback
        $appUrl = config('app.url', 'http://localhost');
        $host = parse_url($appUrl, PHP_URL_HOST) ?? 'localhost';
        // Extraer dominio principal (ej: phishing.botanalisis.com → botanalisis.com)
        $parts = explode('.', $host);
        $seedDomain = count($parts) >= 2
            ? implode('.', array_slice($parts, -2))
            : 'demo.local';

        // Create demo organization
        $org = Organization::firstOrCreate(
            ['slug' => 'demo-org'],
            [
                'name'     => 'Demo Organization',
                'timezone' => 'Europe/Madrid',
                'work_hours' => ['start' => '09:00', 'end' => '18:00', 'days' => [1,2,3,4,5]],
                'event_retention_days' => 365,
                'is_active' => true,
            ]
        );

        // Add domain (pre-verified for seeding – mark as pending in production)
        $isProd = config('app.env') === 'production';
        OrganizationDomain::firstOrCreate(
            ['organization_id' => $org->id, 'domain' => $seedDomain],
            [
                'verification_token' => 'clearphish-verify=' . bin2hex(random_bytes(8)),
                'is_verified'        => !$isProd, // auto-verified only in dev/local
                'verified_at'        => !$isProd ? now() : null,
                'allow_recipients'   => !$isProd,
                'allow_sending'      => !$isProd,
            ]
        );

        // Create admin user
        $adminEmail = $isProd ? "admin@{$seedDomain}" : "admin@demo.local";
        $admin = User::firstOrCreate(
            ['email' => $adminEmail],
            [
                'name'            => 'Admin User',
                'password'        => Hash::make('P@ssw0rd-ClearPhish!'),
                'organization_id' => $org->id,
            ]
        );
        $admin->assignRole('org_admin');

        // Create sending profile (uses MAIL_* env vars)
        $profile = SendingProfile::firstOrCreate(
            ['organization_id' => $org->id, 'from_email' => config('mail.from.address')],
            [
                'name'            => $isProd ? 'SMTP Principal' : 'Demo SMTP (Mailpit)',
                'from_name'       => config('mail.from.name', 'IT Security Team'),
                'mailer'          => 'smtp',
                'smtp_host'       => config('mail.mailers.smtp.host', 'localhost'),
                'smtp_port'       => (int) config('mail.mailers.smtp.port', 1025),
                'smtp_encryption' => config('mail.mailers.smtp.encryption', 'none') ?? 'none',
                'smtp_username'   => config('mail.mailers.smtp.username'),
                'smtp_password'   => config('mail.mailers.smtp.password'),
                'is_verified'     => !$isProd,
                'spf_ok'          => !$isProd,
                'dkim_ok'         => !$isProd,
                'dmarc_ok'        => !$isProd,
            ]
        );

        if ($isProd) {
            $this->command->warn("⚠  Producción: verifica el dominio '{$seedDomain}' en Organization → Dominios.");
            $this->command->warn("⚠  Login admin: {$adminEmail} / P\@ssw0rd-ClearPhish!");
            $this->command->warn("⚠  Cambia la contraseña inmediatamente tras el primer acceso.");
            return; // No crear usuarios de demo en producción
        }

        // Create sample target users (solo en dev)
        $targetUsers = [];
        $sampleUsers = [
            ['Ana', 'García', 'ana.garcia@demo.local', 'Engineering'],
            ['Carlos', 'López', 'carlos.lopez@demo.local', 'Finance'],
            ['María', 'Martínez', 'maria.martinez@demo.local', 'HR'],
            ['Pedro', 'Sánchez', 'pedro.sanchez@demo.local', 'Engineering'],
            ['Laura', 'Fernández', 'laura.fernandez@demo.local', 'Marketing'],
        ];

        foreach ($sampleUsers as [$fn, $ln, $email, $dept]) {
            $targetUsers[] = TargetUser::firstOrCreate(
                ['organization_id' => $org->id, 'email' => $email],
                [
                    'first_name'  => $fn,
                    'last_name'   => $ln,
                    'department'  => $dept,
                    'language'    => 'es',
                    'is_active'   => true,
                ]
            );
        }

        // Create group
        $group = Group::firstOrCreate(
            ['organization_id' => $org->id, 'name' => 'All Employees'],
            ['type' => 'static']
        );
        $group->targetUsers()->syncWithoutDetaching(collect($targetUsers)->pluck('id'));

        // Create sample email template
        EmailTemplate::firstOrCreate(
            ['organization_id' => $org->id, 'name' => 'Google Workspace – Contraseña expira'],
            [
                'subject'      => 'Acción requerida: tu contraseña de Google expira en 24h',
                'from_name'    => 'Google Workspace',
                'category'     => 'google_workspace',
                'language'     => 'es',
                'version'      => 1,
                'is_active'    => true,
                'created_by'   => $admin->id,
                'html_content' => <<<HTML
<!DOCTYPE html>
<html><head><meta charset="UTF-8">
<style>body{font-family:Arial,sans-serif;background:#f4f4f4;margin:0;padding:20px}
.container{max-width:600px;margin:0 auto;background:#fff;border-radius:8px;overflow:hidden}
.header{background:#4285F4;padding:20px;text-align:center}
.header h1{color:#fff;margin:0;font-size:22px}
.body{padding:30px}
.btn{display:inline-block;background:#4285F4;color:#fff;padding:12px 28px;text-decoration:none;border-radius:4px;font-weight:bold}
.footer{background:#f9f9f9;padding:16px;text-align:center;font-size:12px;color:#888}
</style></head>
<body>
<div class="container">
<div class="header"><h1>Google Workspace</h1></div>
<div class="body">
<p>Hola <strong>{{first_name}}</strong>,</p>
<p>Tu contraseña de Google Workspace caducará en <strong>24 horas</strong>.</p>
<p>Para mantener acceso a tu cuenta corporativa, renueva tu contraseña haciendo clic en el botón:</p>
<p style="text-align:center;margin:30px 0"><a href="{{landing_url}}" class="btn">Renovar contraseña</a></p>
<p style="font-size:12px;color:#999">Si no realizas esta acción, perderás acceso a Gmail, Drive y Calendar.</p>
<p style="font-size:12px;color:#bbb">¿Has recibido este mensaje por error? <a href="{{report_url}}" style="color:#4285F4">Repórtalo aquí</a></p>
</div>
<div class="footer">Google Workspace – {{campaign_name}}</div>
</div>
{{tracking_pixel}}
</body></html>
HTML,
            ]
        );

        // Create sample landing page
        LandingPage::firstOrCreate(
            ['organization_id' => $org->id, 'name' => 'Google Login Simulado'],
            [
                'language'                  => 'es',
                'show_training_after_submit'=> true,
                'capture_credentials'       => false,
                'created_by'                => $admin->id,
                'html_content'              => <<<HTML
<!DOCTYPE html>
<html><head><meta charset="UTF-8">
<style>body{font-family:Arial,sans-serif;background:#f5f5f5;display:flex;align-items:center;justify-content:center;min-height:100vh;margin:0}
.card{background:#fff;border-radius:8px;padding:40px;width:360px;box-shadow:0 2px 12px rgba(0,0,0,.12);text-align:center}
.logo{font-size:28px;font-weight:700;color:#4285F4;margin-bottom:8px}
h2{font-size:18px;color:#1a1a1a;margin:0 0 24px}
input{width:100%;padding:12px;border:1px solid #ddd;border-radius:6px;font-size:14px;margin-bottom:12px;box-sizing:border-box}
button{width:100%;padding:12px;background:#4285F4;color:#fff;border:none;border-radius:6px;font-size:14px;cursor:pointer;font-weight:bold}
button:hover{background:#3367d6}</style>
</head><body>
<div class="card">
<div class="logo">Google</div>
<h2>Iniciar sesión</h2>
<form method="POST">
<input type="email" name="email" placeholder="Email corporativo" required>
<input type="password" name="password" placeholder="Contraseña" required>
<button type="submit">Siguiente</button>
</form>
<p style="font-size:11px;color:#bbb;margin-top:16px">Google Workspace</p>
</div>
</body></html>
HTML,
            ]
        );

        $this->command->info("✅ Demo data seeded. Login: {$adminEmail} / password");
    }
}
