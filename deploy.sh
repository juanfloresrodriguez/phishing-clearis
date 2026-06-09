#!/bin/bash
# ─── ClearPhish – Script de despliegue para Plesk ─────────────────────────────
# Ejecutar desde la raíz del proyecto: bash deploy.sh

set -e

echo "🚀 ClearPhish Deploy Script"
echo "================================"

# ─── Detectar PHP ─────────────────────────────────────────────────────────────
find_php() {
    for p in \
        /opt/plesk/php/8.4/bin/php \
        /opt/plesk/php/8.3/bin/php \
        /opt/plesk/php/8.2/bin/php \
        /usr/bin/php8.4 \
        /usr/bin/php8.3 \
        /usr/local/bin/php \
        /usr/bin/php; do
        [ -x "$p" ] && echo "$p" && return 0
    done
    command -v php &>/dev/null && echo "php" && return 0
    return 1
}

PHP=$(find_php) || {
    echo "❌ PHP no encontrado. Pasa la ruta: PHP=/opt/plesk/php/8.4/bin/php bash deploy.sh"
    exit 1
}
PHP_VER=$("$PHP" -r "echo PHP_MAJOR_VERSION.'.'.PHP_MINOR_VERSION;")
echo "✓ PHP $PHP_VER  →  $PHP"

# Añadir el bin de PHP al PATH para que Composer lo encuentre como "php"
PHP_BIN_DIR=$(dirname "$PHP")
export PATH="$PHP_BIN_DIR:$PATH"

# ─── Detectar Composer ────────────────────────────────────────────────────────
if [ -f composer.phar ]; then
    COMPOSER="$PHP composer.phar"
elif command -v composer &>/dev/null; then
    COMPOSER="composer"
else
    echo "⚠  Composer no encontrado. Descargando composer.phar..."
    "$PHP" -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
    "$PHP" composer-setup.php --quiet
    rm composer-setup.php
    COMPOSER="$PHP composer.phar"
fi
echo "✓ Composer  →  $COMPOSER"

# ─── Detectar Node / npm ──────────────────────────────────────────────────────
# Node 12 es demasiado antiguo; Vite requiere Node 18+
NODE_OK=false
for n in \
    /opt/plesk/node/18/bin/node \
    /opt/plesk/node/20/bin/node \
    /opt/plesk/node/22/bin/node \
    /usr/local/bin/node \
    node; do
    if ([ -x "$n" ] || command -v "$n" &>/dev/null) 2>/dev/null; then
        NODE_VER=$("$n" -e "process.exit(parseInt(process.versions.node)<18?1:0)" 2>/dev/null && "$n" -v)
        if [ $? -eq 0 ]; then
            NODE_BIN_DIR=$(dirname "$n")
            export PATH="$NODE_BIN_DIR:$PATH"
            echo "✓ Node $("$n" -v)  →  $n"
            NODE_OK=true
            break
        fi
    fi
done

if [ "$NODE_OK" = false ]; then
    if [ -d "public/build" ]; then
        echo "⚠  Node 18+ no disponible. Usando public/build/ existente."
    else
        echo ""
        echo "❌ Node.js 18+ no encontrado y public/build/ no existe."
        echo "   Opción 1 – Compilar en tu máquina local y subir la carpeta:"
        echo "     npm run build"
        echo "     scp -r public/build/ root@servidor:/var/www/vhosts/phishing.botanalisis.com/httpdocs/public/"
        echo ""
        echo "   Opción 2 – Instalar Node 18 en el servidor:"
        echo "     curl -fsSL https://deb.nodesource.com/setup_18.x | bash -"
        echo "     apt-get install -y nodejs"
        echo ""
        echo "   Después vuelve a ejecutar: bash deploy.sh"
        exit 1
    fi
fi

echo ""

# ─── 1. Dependencias PHP ──────────────────────────────────────────────────────
echo "📦 Instalando dependencias Composer..."
$COMPOSER install --no-dev --optimize-autoloader --no-interaction

# ─── 2. Frontend ──────────────────────────────────────────────────────────────
if [ "$NODE_OK" = true ]; then
    echo "🎨 Compilando assets Vite..."
    npm ci --silent
    npm run build
else
    echo "✓ Usando public/build/ existente (Node 18+ no disponible)"
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
echo "─── Cron jobs para Plesk → Tareas programadas ───────────────────────────"
echo "   * * * * * $PHP $(pwd)/artisan schedule:run >> /dev/null 2>&1"
echo "   */5 * * * * $PHP $(pwd)/artisan queue:work database --queue=campaigns,tracking,default --tries=3 --max-time=240 --stop-when-empty >> /dev/null 2>&1"
echo ""
echo "─── Verifica ────────────────────────────────────────────────────────────"
echo "   Document Root en Plesk → $(pwd)/public"
