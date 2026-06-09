#!/bin/bash
# ─── ClearPhish – Script de despliegue para Plesk ─────────────────────────────
# Ejecutar desde la raíz del proyecto: bash deploy.sh

set -e

echo "🚀 ClearPhish Deploy Script"
echo "================================"

# ─── Detectar PHP ─────────────────────────────────────────────────────────────
find_php() {
    # 1. Rutas típicas de Plesk (ordenadas por versión descendente)
    for p in \
        /opt/plesk/php/8.4/bin/php \
        /opt/plesk/php/8.3/bin/php \
        /opt/plesk/php/8.2/bin/php \
        /usr/bin/php8.4 \
        /usr/bin/php8.3 \
        /usr/bin/php8.2 \
        /usr/local/bin/php8.3 \
        /usr/local/bin/php \
        /usr/bin/php \
        php; do
        if command -v "$p" &>/dev/null || [ -x "$p" ]; then
            echo "$p"
            return 0
        fi
    done
    return 1
}

PHP=$(find_php) || {
    echo "❌ No se encontró PHP 8.3+. Rutas buscadas:"
    echo "   /opt/plesk/php/8.3/bin/php"
    echo "   /usr/bin/php8.3  /usr/local/bin/php  /usr/bin/php"
    echo ""
    echo "Soluciones:"
    echo "  1. Ejecuta: export PATH=\$PATH:/opt/plesk/php/8.3/bin  y vuelve a lanzar"
    echo "  2. O pasa la ruta: PHP=/opt/plesk/php/8.3/bin/php bash deploy.sh"
    exit 1
}

PHP_VER=$("$PHP" -r "echo PHP_MAJOR_VERSION.'.'.PHP_MINOR_VERSION;")
echo "✓ PHP $PHP_VER  →  $PHP"

# ─── Detectar Composer ────────────────────────────────────────────────────────
find_composer() {
    for c in \
        /opt/plesk/php/8.4/bin/composer \
        /opt/plesk/php/8.3/bin/composer \
        /usr/local/bin/composer \
        /usr/bin/composer \
        composer; do
        if command -v "$c" &>/dev/null || [ -x "$c" ]; then
            echo "$c"; return 0
        fi
    done
    # Si hay composer.phar en el proyecto
    [ -f composer.phar ] && echo "$PHP composer.phar" && return 0
    return 1
}

COMPOSER=$(find_composer) || {
    echo "❌ Composer no encontrado."
    echo "   Instálalo con: curl -sS https://getcomposer.org/installer | $PHP"
    echo "   Luego: mv composer.phar /usr/local/bin/composer && chmod +x /usr/local/bin/composer"
    exit 1
}
echo "✓ Composer  →  $COMPOSER"

# ─── Detectar Node / npm ──────────────────────────────────────────────────────
if command -v node &>/dev/null; then
    NODE_VER=$(node -v)
    echo "✓ Node $NODE_VER"
    BUILD_FRONTEND=true
else
    echo "⚠  Node.js no encontrado. Saltando compilación frontend."
    echo "   Asegúrate de que public/build/ existe (compila localmente y sube)."
    BUILD_FRONTEND=false
fi

echo ""

# ─── 1. Dependencias PHP ──────────────────────────────────────────────────────
echo "📦 Instalando dependencias Composer..."
$COMPOSER install --no-dev --optimize-autoloader --no-interaction

# ─── 2. Frontend ──────────────────────────────────────────────────────────────
if [ "$BUILD_FRONTEND" = true ]; then
    echo "🎨 Compilando assets Vite..."
    npm ci --silent
    npm run build
else
    if [ ! -d "public/build" ]; then
        echo "❌ public/build/ no existe y Node no está disponible."
        echo "   Compila localmente (npm run build) y sube la carpeta public/build/"
        exit 1
    fi
    echo "✓ Usando public/build/ existente"
fi

# ─── 3. Permisos ──────────────────────────────────────────────────────────────
echo "🔒 Configurando permisos..."
chmod -R 755 storage bootstrap/cache
chmod -R 644 storage/logs/*.log 2>/dev/null || true

# ─── 4. APP_KEY ───────────────────────────────────────────────────────────────
if ! grep -qE "^APP_KEY=base64:" .env 2>/dev/null; then
    echo "🔑 Generando APP_KEY..."
    "$PHP" artisan key:generate --no-interaction
else
    echo "✓ APP_KEY ya configurada"
fi

# ─── 5. Optimizar para producción ────────────────────────────────────────────
echo "⚡ Optimizando para producción..."
"$PHP" artisan config:clear
"$PHP" artisan config:cache
"$PHP" artisan route:cache
"$PHP" artisan view:cache
"$PHP" artisan event:cache

# ─── 6. Migraciones ───────────────────────────────────────────────────────────
echo "🗄️  Ejecutando migraciones..."
"$PHP" artisan migrate --force --no-interaction

# ─── 7. Storage symlink ───────────────────────────────────────────────────────
echo "🔗 Creando storage symlink..."
"$PHP" artisan storage:link --force 2>/dev/null || true

# ─── 8. Seed (solo primera vez) ───────────────────────────────────────────────
ORGS=$("$PHP" artisan tinker --execute="echo \App\Models\Organization::count();" 2>/dev/null | grep -E '^[0-9]+$' | tail -1)
if [ "${ORGS:-0}" = "0" ]; then
    echo "🌱 Ejecutando seeders (primera instalación)..."
    "$PHP" artisan db:seed --no-interaction
else
    echo "✓ Base de datos ya tiene datos (seed omitido)"
fi

# ─── Resumen ──────────────────────────────────────────────────────────────────
APP_URL=$(grep "^APP_URL=" .env | cut -d= -f2)
echo ""
echo "✅ Deploy completado."
echo "   URL: $APP_URL"
echo ""
echo "─── Próximos pasos ──────────────────────────────────────────────────────"
echo "1. Añade estos cron jobs en Plesk → Tareas programadas:"
echo ""
echo "   # Scheduler (cada minuto)"
echo "   * * * * * $PHP $(pwd)/artisan schedule:run >> /dev/null 2>&1"
echo ""
echo "   # Worker de colas (cada 5 min)"
echo "   */5 * * * * $PHP $(pwd)/artisan queue:work database --queue=campaigns,tracking,default --tries=3 --max-time=240 --stop-when-empty >> /dev/null 2>&1"
echo ""
echo "2. Activa HTTPS con Let's Encrypt en Plesk → SSL/TLS"
echo "3. Verifica que Document Root apunta a: $(pwd)/public"
