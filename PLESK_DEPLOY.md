# Despliegue en Plesk – phishing.botanalisis.com

Guía paso a paso para desplegar ClearPhish en un servidor Plesk con PHP.

---

## Requisitos del servidor

| Componente | Mínimo | Verificación |
|---|---|---|
| PHP | 8.3+ | `php -v` |
| Extensiones PHP | pdo_mysql, mbstring, openssl, tokenizer, xml, ctype, json, bcmath, curl, zip | `php -m` |
| Composer | 2.x | `composer -V` |
| Node.js | 18+ | `node -v` |
| MySQL/MariaDB | 8.0+ / 10.4+ | Panel Plesk |

> **¿Sin Redis?** La app funciona perfectamente con el driver `database` para colas, sesiones y caché. Rendimiento suficiente para campañas internas.

---

## Paso 1 – Crear dominio en Plesk

1. Plesk Panel → **Dominios** → **Añadir dominio**
2. Nombre: `phishing.botanalisis.com`
3. **Document Root**: asegúrate de apuntar a `httpdocs/public` (ver Paso 3)

---

## Paso 2 – Crear base de datos MySQL

1. Plesk Panel → Tu dominio → **Bases de datos** → **Añadir base de datos**
2. Nombre: `clearphish_prod`
3. Crear usuario: `clearphish_user` con contraseña segura
4. Anota los datos, los necesitarás en el `.env`

---

## Paso 3 – Subir el código

### Opción A – Git (recomendado si el servidor tiene Git)

```bash
# Conectar por SSH al servidor (Plesk Panel → SSH Terminal o cliente SSH)
cd /var/www/vhosts/phishing.botanalisis.com/httpdocs

# Clonar el repositorio
git clone https://github.com/juanfloresrodriguez/phishing-clearis.git .
```

### Opción B – SFTP / Gestor de ficheros Plesk

1. Subir todos los ficheros del proyecto a `/var/www/vhosts/phishing.botanalisis.com/httpdocs/`
2. Incluir la carpeta `vendor/` (o ejecutar `composer install` vía SSH)

---

## Paso 4 – Configurar Document Root

**Crítico**: Laravel sirve todo desde la carpeta `public/`. Debes indicárselo a Plesk.

### Opción A – Panel Plesk (más fácil)

1. Plesk → Tu dominio → **Configuración de alojamiento**
2. **Raíz del documento**: cambiar de `httpdocs` a `httpdocs/public`
3. Guardar

### Opción B – Crear `.htaccess` en la raíz (si no puedes cambiar document root)

Si Plesk **no permite** cambiar el document root, crea este fichero en `httpdocs/.htaccess`:

```apache
RewriteEngine On
RewriteRule ^(.*)$ public/$1 [L]
```

O mejor, usa un `index.php` puente en `httpdocs/`:

```php
<?php
// httpdocs/index.php  (NO en httpdocs/public/)
require __DIR__.'/public/index.php';
```

> La primera opción (cambiar document root) es la correcta y segura.

---

## Paso 5 – Configurar PHP en Plesk

1. Plesk → Tu dominio → **PHP**
2. Versión: **PHP 8.3** o **PHP 8.4**
3. Handler: **FPM** (mejor rendimiento) o CGI
4. Extensiones activas (verificar todas):
   - `pdo_mysql` ✓
   - `mbstring` ✓
   - `openssl` ✓
   - `tokenizer` ✓
   - `xml` ✓
   - `ctype` ✓
   - `json` ✓
   - `bcmath` ✓
   - `curl` ✓
   - `zip` ✓
   - `fileinfo` ✓

---

## Paso 6 – Configurar entorno

### Conectar por SSH

```bash
ssh usuario@phishing.botanalisis.com
cd /var/www/vhosts/phishing.botanalisis.com/httpdocs
```

### Crear el fichero `.env`

```bash
# Copiar la plantilla de producción
cp .env.plesk .env

# Editar con tus datos reales
nano .env
```

**Variables mínimas a cambiar:**

```env
APP_URL=https://phishing.botanalisis.com
APP_KEY=                    # ← Se genera en el Paso 7

DB_HOST=localhost
DB_DATABASE=clearphish_prod
DB_USERNAME=clearphish_user
DB_PASSWORD=TU_PASSWORD_AQUI

MAIL_HOST=smtp.gmail.com
MAIL_USERNAME=tu@botanalisis.com
MAIL_PASSWORD=tu_app_password
MAIL_FROM_ADDRESS=security@botanalisis.com

TRACKING_BASE_URL=https://phishing.botanalisis.com
LANDING_BASE_URL=https://phishing.botanalisis.com
```

---

## Paso 7 – Ejecutar despliegue

```bash
# Desde la raíz del proyecto vía SSH
bash deploy.sh
```

El script hace automáticamente:
- ✅ `composer install --no-dev`
- ✅ `npm ci && npm run build`
- ✅ `php artisan key:generate`
- ✅ `php artisan migrate`
- ✅ `php artisan config:cache` + `route:cache` + `view:cache`
- ✅ `php artisan storage:link`
- ✅ `php artisan db:seed` (solo primera vez)

### Si no tienes Node.js en el servidor

Compila los assets **localmente** antes de subir:

```bash
# En tu máquina local
npm ci && npm run build
# Sube la carpeta public/build/ al servidor
```

---

## Paso 8 – Configurar cron jobs en Plesk

**Esto es obligatorio** para que las campañas se ejecuten automáticamente.

1. Plesk Panel → Tu dominio → **Tareas programadas (Cron)**
2. Añadir las siguientes tareas:

### Scheduler de Laravel (cada minuto)

```
* * * * * /usr/bin/php /var/www/vhosts/phishing.botanalisis.com/httpdocs/artisan schedule:run >> /dev/null 2>&1
```

### Worker de colas (si usas driver `database`)

```
*/5 * * * * /usr/bin/php /var/www/vhosts/phishing.botanalisis.com/httpdocs/artisan queue:work database --queue=campaigns,tracking,default --tries=3 --max-time=240 --stop-when-empty >> /dev/null 2>&1
```

> El flag `--stop-when-empty` hace que el worker se inicie vía cron cada 5 minutos y termine cuando no haya trabajos, evitando procesos zombi en hosting compartido.

> Ajusta `/usr/bin/php` a la ruta correcta de tu PHP: `which php` o usa `php8.3` si hay múltiples versiones.

---

## Paso 9 – Configurar HTTPS

Plesk suele incluir Let's Encrypt:

1. Plesk → Tu dominio → **SSL/TLS**
2. **Let's Encrypt** → Emitir certificado
3. Marcar "Asegurar webmail" si aplica

---

## Paso 10 – Verificar instalación

```bash
# Comprobar que la app responde
curl -I https://phishing.botanalisis.com

# Ver logs si hay errores
tail -f storage/logs/laravel.log

# Verificar colas
php artisan queue:monitor
```

Abre https://phishing.botanalisis.com en el navegador.  
Login: `admin@demo.local` / `password` (cambiar inmediatamente)

---

## Permisos de ficheros

```bash
# Desde la raíz del proyecto
find . -type f -name "*.php" -exec chmod 644 {} \;
find . -type d -exec chmod 755 {} \;
chmod -R 775 storage bootstrap/cache
```

---

## Solución de problemas frecuentes en Plesk

### Error 500 al abrir la web

```bash
tail -50 storage/logs/laravel.log
# También revisar: /var/www/vhosts/phishing.botanalisis.com/logs/error_log
```

### "No application encryption key has been specified"

```bash
php artisan key:generate
php artisan config:cache
```

### Páginas en blanco o sin CSS

```bash
php artisan config:clear
php artisan route:clear
php artisan view:clear
# Verificar que public/build/ existe
ls public/build/
```

### Las colas no procesan

```bash
# Ejecutar worker manualmente para ver errores
php artisan queue:work database --queue=campaigns,tracking,default --tries=1 -v

# Ver trabajos fallidos
php artisan queue:failed
```

### Error de permisos en storage

```bash
chmod -R 775 storage bootstrap/cache
# Si el grupo del servidor web es www-data o apache:
chown -R tu_usuario:www-data storage bootstrap/cache
```

### Migrations fallan por orden de foreign keys

```bash
# Ejecutar con drop-all si es primera instalación
php artisan migrate:fresh --seed
```

---

## Estructura en el servidor

```
/var/www/vhosts/phishing.botanalisis.com/
├── httpdocs/                   ← Raíz del dominio en Plesk
│   ├── public/                 ← Document Root (apunta aquí)
│   │   ├── index.php
│   │   ├── build/              ← Assets Vite compilados
│   │   └── .htaccess
│   ├── app/
│   ├── database/
│   ├── resources/
│   ├── routes/
│   ├── storage/
│   ├── vendor/
│   ├── .env                    ← NO en control de versiones
│   └── artisan
└── logs/                       ← Logs de Plesk/Apache
```

---

## Post-instalación: primeros pasos

1. **Cambiar credenciales del admin demo** → Perfil → Cambiar contraseña
2. **Crear tu organización real** → Organization → Actualizar nombre
3. **Añadir y verificar tu dominio** → Organization → Dominios → Añadir `botanalisis.com`
4. **Añadir DNS TXT** de verificación en tu proveedor DNS
5. **Crear perfil de envío** con SMTP de Google Workspace
6. **Verificar DNS** (SPF/DKIM/DMARC) desde el perfil de envío
7. **Importar usuarios** vía CSV

---

## Checklist de seguridad en producción

- [ ] `APP_DEBUG=false` en `.env`
- [ ] `APP_ENV=production` en `.env`
- [ ] `SESSION_SECURE_COOKIE=true` (requiere HTTPS)
- [ ] HTTPS activo con Let's Encrypt
- [ ] Contraseña del admin demo cambiada
- [ ] Document root apunta a `public/` (no a la raíz)
- [ ] Fichero `.env` no accesible desde el navegador
- [ ] Permisos `storage/` y `bootstrap/cache/` = 775
- [ ] Cron jobs configurados
- [ ] Backup de base de datos programado en Plesk
