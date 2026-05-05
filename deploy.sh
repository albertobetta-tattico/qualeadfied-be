#!/bin/bash
set -e

# ============================================================
# Qualeadfied - Deploy Script
# Builds frontend, packages everything, uploads via FTP
# ============================================================

# Colors
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m'

# Config
BACKEND_DIR="$(cd "$(dirname "$0")" && pwd)"
FRONTEND_DIR="$BACKEND_DIR/../qualeadfied"
STAGING_DIR="/tmp/qualeadfied-deploy-staging"
ZIP_FILE="/tmp/qualeadfied-deploy.zip"

FTP_HOST="ftp.justskills.it"
FTP_USER="alberto@justskills.it"
FTP_PASS='mwnMjb{E=y8to;^]'

SETUP_TOKEN="qualeadfied-setup-2026"
SITE_URL="https://qualeadfied.justskills.it"

echo -e "${GREEN}=== Qualeadfied Deploy Script ===${NC}\n"

# -----------------------------------------------
# Step 1: Build Nuxt frontend
# -----------------------------------------------
echo -e "${YELLOW}[1/6] Building Nuxt frontend...${NC}"
cd "$FRONTEND_DIR"

# A running `nuxt dev` on this project poisons `nuxt generate`: the prerender
# fetches the dev server, saves the dev shell (@vite/client + filesystem paths
# like `/_nuxt/Users/.../node_modules/...`), and the deployed app is just a
# white page in production. Stop any local dev servers tied to this project
# before generating.
DEV_PIDS=$(pgrep -f "$FRONTEND_DIR/node_modules/.bin/nuxt dev" 2>/dev/null || true)
if [ -n "$DEV_PIDS" ]; then
  echo "  Stopping local nuxt dev servers ($DEV_PIDS)..."
  kill $DEV_PIDS 2>/dev/null || true
  sleep 2
  STILL=$(pgrep -f "$FRONTEND_DIR/node_modules/.bin/nuxt dev" 2>/dev/null || true)
  [ -n "$STILL" ] && kill -9 $STILL 2>/dev/null || true
fi

# Wipe stale build outputs (.nuxt cache can also leak dev metadata)
rm -rf "$FRONTEND_DIR/dist" "$FRONTEND_DIR/.output" "$FRONTEND_DIR/.nuxt"

NUXT_PUBLIC_API_BASE=/api npx nuxt generate

# Sanity-check: the prerendered shell must NOT contain the Vite dev client
# nor filesystem paths. If it does, the build was poisoned by a dev server.
if grep -q "@vite/client\|/_nuxt/Users/" "$FRONTEND_DIR/dist/index.html"; then
  echo -e "${RED}  ✗ Build is poisoned (dev shell in dist/index.html). Aborting.${NC}"
  exit 1
fi
echo -e "${GREEN}  ✓ Frontend built${NC}\n"

# -----------------------------------------------
# Step 2: Install composer dependencies (production)
# -----------------------------------------------
echo -e "${YELLOW}[2/6] Installing composer dependencies...${NC}"
cd "$BACKEND_DIR"
composer install --optimize-autoloader --no-dev --quiet
echo -e "${GREEN}  ✓ Composer dependencies installed${NC}\n"

# -----------------------------------------------
# Step 3: Assemble staging directory
# -----------------------------------------------
echo -e "${YELLOW}[3/6] Assembling deployment package...${NC}"
rm -rf "$STAGING_DIR"
mkdir -p "$STAGING_DIR"

# Copy Laravel project (excluding dev files)
rsync -a \
  --exclude='.git' \
  --exclude='node_modules' \
  --exclude='tests' \
  --exclude='docker-compose.yml' \
  --exclude='.env' \
  --exclude='storage/logs/*.log' \
  --exclude='storage/framework/cache/data/*' \
  --exclude='storage/framework/sessions/*' \
  --exclude='storage/framework/views/*.php' \
  --exclude='bootstrap/cache/*.php' \
  --exclude='*.iml' \
  --exclude='.idea' \
  --exclude='.claude' \
  --exclude='docs' \
  --exclude='deploy.sh' \
  --exclude='README.md' \
  --exclude='package.json' \
  --exclude='package-lock.json' \
  --exclude='api.json' \
  --exclude='.editorconfig' \
  "$BACKEND_DIR/" "$STAGING_DIR/"

# Copy Nuxt build output to root (SPA files)
rsync -a "$FRONTEND_DIR/dist/" "$STAGING_DIR/"

# Create root index.php (Laravel entry point)
cat > "$STAGING_DIR/index.php" << 'PHPEOF'
<?php

/**
 * Laravel Entry Point (root-level deployment for shared hosting)
 *
 * This file sits at the document root and delegates to public/index.php.
 * Since public/index.php uses __DIR__ for path resolution, all relative
 * paths (../vendor, ../bootstrap, ../storage) resolve correctly.
 */

require __DIR__ . '/public/index.php';
PHPEOF

# Create root .htaccess
cat > "$STAGING_DIR/.htaccess" << 'HTEOF'
# Force index.html over index.php for directory requests (Nuxt SPA over Laravel)
DirectoryIndex index.html

# ===================================================
# MIME types (evita MIME "text/html" per .js/.mjs/.wasm)
# ===================================================
<IfModule mod_mime.c>
    AddType application/javascript .js .mjs
    AddType application/wasm       .wasm
    AddType text/css               .css
    AddType image/svg+xml          .svg
    AddType font/woff              .woff
    AddType font/woff2             .woff2
</IfModule>

# ===================================================
# Cache policy: HTML sempre fresco, asset hashati immutable
# ===================================================
<IfModule mod_headers.c>
    <FilesMatch "\.(html)$">
        Header set Cache-Control "no-cache, no-store, must-revalidate"
        Header set Pragma "no-cache"
        Header set Expires "0"
    </FilesMatch>

    <FilesMatch "\.(js|mjs|css|woff|woff2|ttf|otf|eot|svg|png|jpg|jpeg|gif|webp|avif|ico|map|wasm)$">
        Header set Cache-Control "public, max-age=31536000, immutable"
    </FilesMatch>
</IfModule>

<IfModule mod_rewrite.c>
    <IfModule mod_negotiation.c>
        Options -MultiViews -Indexes
    </IfModule>

    RewriteEngine On

    # Handle Authorization Header
    RewriteCond %{HTTP:Authorization} .
    RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]

    # Handle X-XSRF-Token Header
    RewriteCond %{HTTP:x-xsrf-token} .
    RewriteRule .* - [E=HTTP_X_XSRF_TOKEN:%{HTTP:X-XSRF-Token}]

    # -----------------------------------------------
    # Laravel API routes → index.php (Laravel)
    # -----------------------------------------------
    RewriteRule ^api(/.*)?$ index.php [L]
    RewriteRule ^sanctum(/.*)?$ index.php [L]
    RewriteRule ^up$ index.php [L]

    # -----------------------------------------------
    # Static files → serve directly (_nuxt/, images, etc.)
    # -----------------------------------------------
    RewriteCond %{REQUEST_FILENAME} -f
    RewriteRule .* - [L]

    RewriteCond %{REQUEST_FILENAME} -d
    RewriteRule .* - [L]

    # -----------------------------------------------
    # Asset mancanti NON vanno nel fallback SPA:
    # meglio un 404 pulito (evita MIME "text/html" sui .js)
    # -----------------------------------------------
    RewriteCond %{REQUEST_URI} ^/_nuxt/ [OR]
    RewriteCond %{REQUEST_URI} \.(js|mjs|css|map|wasm|woff2?|ttf|otf|eot|svg|png|jpg|jpeg|gif|webp|avif|ico|json)$ [NC]
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteRule . - [R=404,L]

    # -----------------------------------------------
    # Everything else → Nuxt SPA fallback
    # -----------------------------------------------
    RewriteRule .* 200.html [L]
</IfModule>

# ===================================================
# Security: Protect sensitive Laravel files
# ===================================================
<FilesMatch "^\.env">
    <IfModule mod_authz_core.c>
        Require all denied
    </IfModule>
    <IfModule !mod_authz_core.c>
        Order deny,allow
        Deny from all
    </IfModule>
</FilesMatch>

<FilesMatch "^(artisan|composer\.(json|lock)|phpunit\.xml|\.editorconfig|\.gitignore|\.gitattributes)$">
    <IfModule mod_authz_core.c>
        Require all denied
    </IfModule>
    <IfModule !mod_authz_core.c>
        Order deny,allow
        Deny from all
    </IfModule>
</FilesMatch>
HTEOF

# Copy .env.production
cp "$BACKEND_DIR/.env.production" "$STAGING_DIR/.env.production" 2>/dev/null || true

echo -e "${GREEN}  ✓ Staging directory assembled${NC}\n"

# -----------------------------------------------
# Step 4: Create zip
# -----------------------------------------------
echo -e "${YELLOW}[4/6] Creating zip archive...${NC}"
rm -f "$ZIP_FILE"
cd "$STAGING_DIR"
zip -r -9 -q "$ZIP_FILE" . -x "*.DS_Store" "*__MACOSX*"
ZIP_SIZE=$(du -h "$ZIP_FILE" | cut -f1)
echo -e "${GREEN}  ✓ Archive created: $ZIP_SIZE${NC}\n"

# -----------------------------------------------
# Step 5: Upload via FTP
# -----------------------------------------------
echo -e "${YELLOW}[5/6] Uploading to server...${NC}"

# Upload zip
curl -T "$ZIP_FILE" "ftp://$FTP_HOST/qualeadfied-deploy.zip" \
  --user "$FTP_USER:$FTP_PASS" --progress-bar

# Create and upload setup script
SETUP_SCRIPT="/tmp/qualeadfied-setup.php"
cat > "$SETUP_SCRIPT" << 'SETUPEOF'
<?php
$setupToken = 'qualeadfied-setup-2026';
if (($_GET['token'] ?? '') !== $setupToken) {
    http_response_code(403);
    die('Access denied.');
}

set_time_limit(300);
echo "<pre>\n=== Qualeadfied Deploy ===\n\n";

$root = __DIR__;

// Wipe stale Nuxt SPA assets (hashed filenames change between builds; old
// chunks left behind would just be dead bytes, but the real risk is having
// the old index.html survive if the upload skips it for any reason).
echo "[1/4] Wiping stale frontend assets...\n";
$rmrf = function (string $path) use (&$rmrf): void {
    if (! is_dir($path)) return;
    $items = scandir($path) ?: [];
    foreach ($items as $item) {
        if ($item === '.' || $item === '..') continue;
        $full = $path . '/' . $item;
        is_dir($full) ? $rmrf($full) : @unlink($full);
    }
    @rmdir($path);
};
$rmrf($root . '/_nuxt');
// Wipe stale prerendered route directories from previous deploys.
// Two layers:
//  1) Explicit whitelist of every top-level Nuxt route currently in pages/.
//  2) Dynamic sweep: any leftover top-level dir that contains an index.html
//     (Laravel dirs — public, app, bootstrap, ... — never do, so they're safe).
foreach (['admin', 'auth', 'carrello', 'catalogo', 'checkout', 'dashboard',
         'form', 'i-miei-lead', 'leads', 'login', 'ordini', 'pacchetti',
         'password-dimenticata', 'profilo', 'prova-gratuita', 'registrati',
         'reset-password', 'verifica-email'] as $stale) {
    $rmrf($root . '/' . $stale);
}
foreach (scandir($root) ?: [] as $item) {
    if ($item === '.' || $item === '..') continue;
    $full = $root . '/' . $item;
    if (is_dir($full) && is_file($full . '/index.html')) {
        $rmrf($full);
    }
}
foreach (['index.html', '200.html', '404.html', 'favicon.ico', 'favicon.png',
         'logo.png', 'logo-mini.png', 'robots.txt'] as $f) {
    @unlink($root . '/' . $f);
}
echo "  OK\n";

// Extract zip
echo "[1b/4] Extracting...\n";
$zip = new ZipArchive();
if ($zip->open($root . '/qualeadfied-deploy.zip') === true) {
    $zip->extractTo($root);
    $zip->close();
    unlink($root . '/qualeadfied-deploy.zip');
    echo "  OK\n";
} else {
    die("  FAILED\n");
}

// Storage dirs
echo "[2/4] Storage directories...\n";
foreach (['storage/app/public', 'storage/framework/cache/data', 'storage/framework/sessions',
          'storage/framework/testing', 'storage/framework/views', 'storage/logs', 'bootstrap/cache'] as $d) {
    if (!is_dir("$root/$d")) mkdir("$root/$d", 0755, true);
}
echo "  OK\n";

// Setup .env
echo "[3/4] Environment...\n";
if (file_exists("$root/.env.production") && !file_exists("$root/.env")) {
    copy("$root/.env.production", "$root/.env");
    echo "  Created .env from .env.production\n";
} elseif (file_exists("$root/.env")) {
    echo "  .env already exists (kept)\n";
}

// Protect dirs
$deny = "<IfModule mod_authz_core.c>\n    Require all denied\n</IfModule>\n<IfModule !mod_authz_core.c>\n    Order deny,allow\n    Deny from all\n</IfModule>";
foreach (['app', 'bootstrap', 'config', 'database', 'resources', 'routes', 'vendor', 'storage'] as $d) {
    if (is_dir("$root/$d")) @file_put_contents("$root/$d/.htaccess", $deny);
}
echo "  Directories protected\n";

// Laravel setup
echo "[4/4] Laravel setup...\n";
require "$root/vendor/autoload.php";
$app = require_once "$root/bootstrap/app.php";
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

\Illuminate\Support\Facades\DB::connection()->getPdo();
echo "  DB connected\n";

\Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
echo \Illuminate\Support\Facades\Artisan::output();

if (isset($_GET['seed'])) {
    \Illuminate\Support\Facades\Artisan::call('db:seed', ['--force' => true]);
    echo \Illuminate\Support\Facades\Artisan::output();
}

\Illuminate\Support\Facades\Artisan::call('config:cache');
\Illuminate\Support\Facades\Artisan::call('route:cache');
echo "  Config & routes cached\n";

echo "\n=== Done! ===\n";
echo "DELETE this file: setup.php\n</pre>";
SETUPEOF

curl -T "$SETUP_SCRIPT" "ftp://$FTP_HOST/setup.php" \
  --user "$FTP_USER:$FTP_PASS" -s

echo -e "\n${GREEN}  ✓ Files uploaded${NC}\n"

# -----------------------------------------------
# Step 6: Run setup on server
# -----------------------------------------------
echo -e "${YELLOW}[6/6] Running server setup...${NC}"

SEED_PARAM=""
if [[ "$1" == "--seed" ]]; then
  SEED_PARAM="&seed=1"
  echo "  (with database seeding)"
fi

RESULT=$(curl -s "$SITE_URL/setup.php?token=$SETUP_TOKEN$SEED_PARAM")
echo "$RESULT" | sed 's/<[^>]*>//g'

# Delete setup.php
curl -s -Q "DELE setup.php" "ftp://$FTP_HOST/" --user "$FTP_USER:$FTP_PASS" > /dev/null 2>&1

# Cleanup
rm -rf "$STAGING_DIR" "$ZIP_FILE" "$SETUP_SCRIPT"

echo -e "\n${GREEN}=== Deploy Complete ===${NC}"
echo -e "Site: $SITE_URL"
echo -e "Admin: $SITE_URL/admin/login"
echo -e "API:   $SITE_URL/api/public/categories"
