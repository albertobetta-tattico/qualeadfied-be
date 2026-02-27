# Qualeadfied - Guida al Deploy

## Architettura

Il deploy è su hosting condiviso (LiteSpeed/Apache) con un unico dominio:

```
qualeadfied.justskills.it/           → Nuxt SPA (index.html / 200.html)
qualeadfied.justskills.it/_nuxt/     → Asset compilati Nuxt (JS/CSS)
qualeadfied.justskills.it/api/...    → Laravel API (index.php → public/index.php)
```

Frontend (Nuxt 3 SPA) e Backend (Laravel 12) convivono nella stessa document root.
Il `.htaccess` instrada `/api/*` a Laravel e tutto il resto alla SPA Nuxt.

## Server

| Parametro | Valore |
|-----------|--------|
| Dominio | https://qualeadfied.justskills.it |
| Server FTP | ftp.justskills.it |
| FTP User | alberto@justskills.it |
| Document Root | /home/pprxwltz/qualeadfied.justskills.it |
| PHP | 8.3 (LiteSpeed) |
| Database | MySQL - pprxwltz_qualeadfied |
| DB User | pprxwltz_qualeadmin |

## Deploy Automatico

Lo script `deploy.sh` automatizza l'intero processo:

```bash
# Deploy standard (solo codice + migrazioni)
./deploy.sh

# Deploy con seeding del database (prima installazione o reset)
./deploy.sh --seed
```

### Cosa fa lo script

1. **Build frontend Nuxt** — Compila la SPA con `NUXT_PUBLIC_API_BASE=/api`
2. **Composer install** — Installa dipendenze PHP (no-dev)
3. **Assembla package** — Unisce Laravel + Nuxt in una directory staging
4. **Crea zip** — Comprime tutto (~12MB)
5. **Upload FTP** — Carica zip + script setup sul server
6. **Esegue setup remoto** — Estrae, migra, protegge directory, cache config/route
7. **Pulizia** — Rimuove zip e script setup dal server

## Struttura su Server

```
/ (document root)
├── .htaccess          ← Routing: /api → Laravel, rest → SPA
├── index.php          ← Entry Laravel (require public/index.php)
├── index.html         ← Entry Nuxt SPA
├── 200.html           ← SPA fallback (client-side routing)
├── _nuxt/             ← Asset compilati Nuxt
├── admin/             ← Pagine pre-renderizzate Nuxt
├── catalogo/, login/, ... ← Altre pagine Nuxt
├── .env               ← Config produzione (protetto da .htaccess)
│
├── app/               ← Laravel (protetto: .htaccess deny)
├── bootstrap/         ← Laravel (protetto)
├── config/            ← Laravel (protetto)
├── database/          ← Laravel (protetto)
├── public/            ← Laravel public/ originale
│   └── index.php      ← Entry point PHP di Laravel
├── resources/         ← Laravel (protetto)
├── routes/            ← Laravel (protetto)
├── storage/           ← Laravel (protetto)
└── vendor/            ← Laravel (protetto)
```

## Sicurezza

Tutte le directory sensibili di Laravel hanno un `.htaccess` con `Require all denied`.
I file `.env`, `artisan`, `composer.json`, `composer.lock` sono bloccati dal `.htaccess` root.

Test sicurezza:
```bash
curl -s -o /dev/null -w "%{http_code}" https://qualeadfied.justskills.it/.env          # → 403
curl -s -o /dev/null -w "%{http_code}" https://qualeadfied.justskills.it/vendor/        # → 403
curl -s -o /dev/null -w "%{http_code}" https://qualeadfied.justskills.it/config/app.php # → 403
```

## Deploy Manuale (senza script)

Se necessario, il processo manuale:

```bash
# 1. Build frontend
cd ../qualeadfied
NUXT_PUBLIC_API_BASE=/api npx nuxt generate

# 2. Composer install
cd ../qualeadfied-be
composer install --optimize-autoloader --no-dev

# 3. Assemblare e zippare (vedi deploy.sh per dettagli rsync)

# 4. Upload zip via FTP client (FileZilla, cyberduck, ecc.)

# 5. Sul server: estrarre, copiare .env, eseguire:
#    php artisan migrate --force
#    php artisan config:cache
#    php artisan route:cache
```

## Credenziali Admin

| Parametro | Valore |
|-----------|--------|
| URL | https://qualeadfied.justskills.it/admin/login |
| Email | admin@qualeadfied.com |
| Password | password |

**Cambiare la password in produzione!**

## Troubleshooting

### La homepage mostra la pagina Laravel
Verificare che `.htaccess` contenga `DirectoryIndex index.html` come prima riga.

### 500 su /api/*
Verificare che `.env` esista e sia corretto. Controllare `storage/logs/laravel.log`.

### Le rotte SPA danno 404 al refresh
Verificare che `.htaccess` abbia la regola `RewriteRule .* 200.html [L]` come fallback.

### Errori di permessi
Le directory `storage/` e `bootstrap/cache/` devono essere scrivibili dal web server:
```bash
chmod -R 755 storage bootstrap/cache
```
