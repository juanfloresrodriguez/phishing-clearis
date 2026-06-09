<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Simulación de Phishing – Formación</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
               background: #f8f9fa; margin: 0; padding: 20px; color: #333; }
        .container { max-width: 640px; margin: 60px auto; background: #fff;
                     border-radius: 12px; overflow: hidden;
                     box-shadow: 0 4px 24px rgba(0,0,0,.1); }
        .header { background: #dc3545; color: #fff; padding: 32px; text-align: center; }
        .header h1 { margin: 0; font-size: 1.8rem; }
        .body { padding: 32px; }
        .alert { background: #fff3cd; border-left: 4px solid #ffc107;
                 padding: 16px 20px; border-radius: 4px; margin-bottom: 24px; }
        .tips { list-style: none; padding: 0; }
        .tips li { padding: 10px 0; border-bottom: 1px solid #eee; }
        .tips li::before { content: "✓ "; color: #28a745; font-weight: bold; }
        .footer { text-align: center; padding: 24px; color: #888; font-size: .9rem; }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <h1>⚠️ Simulación de Phishing</h1>
        <p style="margin:.5rem 0 0">Este era un ejercicio de concienciación en ciberseguridad</p>
    </div>
    <div class="body">
        <div class="alert">
            <strong>Has interactuado con un email de simulación.</strong><br>
            No se han capturado ni almacenado tus credenciales reales.
        </div>

        <h3>¿Cómo detectar un phishing?</h3>
        <ul class="tips">
            <li>Verifica siempre la dirección real del remitente, no solo el nombre visible.</li>
            <li>Pasa el cursor sobre los enlaces antes de hacer clic para ver la URL real.</li>
            <li>Desconfía de urgencia inusual: "tu cuenta será bloqueada", "acción requerida inmediata".</li>
            <li>Las empresas legítimas nunca piden credenciales por email.</li>
            <li>Usa el botón de reporte de phishing de tu cliente de correo.</li>
        </ul>

        <p>Si tienes dudas sobre algún email, contacta al equipo de Seguridad.</p>
    </div>
    <div class="footer">Ejercicio autorizado de simulación de phishing – uso interno</div>
</div>
</body>
</html>
