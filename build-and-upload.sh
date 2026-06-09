#!/bin/bash
# ─── Compila los assets localmente y los sube al servidor ─────────────────────
# Ejecutar en tu MÁQUINA LOCAL (no en el servidor)
# Requiere: Node 18+, rsync o scp

SERVER="root@phishing.botanalisis.com"
REMOTE_PATH="/var/www/vhosts/phishing.botanalisis.com/httpdocs/public/build"

echo "🎨 Compilando assets..."
npm ci && npm run build

echo "📤 Subiendo public/build/ al servidor..."
rsync -avz --delete public/build/ "$SERVER:$REMOTE_PATH/"

echo "✅ Assets subidos a $SERVER:$REMOTE_PATH"
