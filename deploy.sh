#!/bin/bash
# ─── ClearPhish – Script de despliegue para Plesk ─────────────────────────────
# Ejecutar desde la raíz del proyecto: bash deploy.sh
# Requiere: PHP 8.3+, Composer, Node.js 18+ en el PATH del servidor

set -e

echo "🚀 ClearPhish Deploy Script"
echo "================================"

# 1. Verificar PHP
PHP_VERSION=$(php -r "echo PHP_MAJOR_VERSION.'.'.PHP_MINOR_VERSION;")
echo "✓ PHP $PHP_VERSION"

# 2. Instalar dependencias PHP (sin dev)
echo "📦 Instalando dependencias Composer..."
composer install --no-dev --optimize-autoloader --no-interaction

# 3. Instalar y compilar frontend
echo "🎨 Compilando assets Vite..."
npm ci --silent
npm run build

# 4. Configurar permisos
echo "🔒 Configurando permisos..."
chmod -R 755 storage bootstrap/cache
chmod -R 644 storage/logs/*.log 2>/dev/null || true

# 5. Generar clave si no existe
if grep -q "^APP_KEY=$" .env || grep -q "^APP_KEY=\"\"$" .env; then
    echo "🔑 Generando APP_KEY..."
    php artisan key:generate --no-interaction
else
    echo "✓ APP_KEY ya configurada"
fi

# 6. Caché de configuración para producción
echo "⚡ Optimizando para producción..."
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# 7. Ejecutar migraciones
echo "🗄️  Ejecutando migraciones..."
php artisan migrate --force --no-interaction

# 8. Enlace de almacenamiento público
echo "🔗 Creando storage symlink..."
php artisan storage:link --force

# 9. Seed solo si la tabla organizations está vacía
ORGS=$(php artisan tinker --execute="echo App\Models\Organization::count();" 2>/dev/null | tail -1)
if [ "$ORGS" = "0" ]; then
    echo "🌱 Ejecutando seeders..."
    php artisan db:seed --no-interaction
else
    echo "✓ Base de datos ya tiene datos (skip seed)"
fi

echo ""
echo "✅ Deploy completado."
echo "   URL: $(grep APP_URL .env | cut -d= -f2)"
echo ""
echo "⚠️  Recuerda configurar los cron jobs en Plesk (ver PLESK_DEPLOY.md)"
