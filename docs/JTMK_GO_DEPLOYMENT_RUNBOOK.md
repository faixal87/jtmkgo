# JTMK Go Deployment Runbook

Last updated: 2026-07-08

This runbook explains how to move JTMK Go development to another PC and how to deploy safely to AWS.

## 1. Fresh PC Setup

Install the required tools:

- PHP compatible with the current Laravel version
- Composer
- Node.js and npm
- MySQL or MariaDB
- Git
- Herd, Valet, Laragon, or another local web server
- PuTTY or OpenSSH client for AWS deployment

Clone the repository:

```bash
git clone <repo-url> jtmk
cd jtmk
git checkout feature/program-go
```

Install dependencies:

```bash
composer install
npm install
```

Prepare environment:

```bash
cp .env.example .env
php artisan key:generate
```

Update `.env` manually with local database, mail, app URL, and storage settings. Do not commit `.env`.

Run database setup only when appropriate:

```bash
php artisan migrate
php artisan db:seed
```

Create storage symlink:

```bash
php artisan storage:link
```

Build assets:

```bash
npm run build
```

Clear/cache Laravel files:

```bash
php artisan optimize:clear
php artisan view:cache
```

## 2. Moving Data To Another PC

To make the new PC match the old local development data:

1. Export MySQL database from old PC.
2. Import into the new PC database.
3. Copy uploaded storage files if needed.

Important storage paths:

```text
storage/app/public
public/storage
```

Do not copy or share sensitive `.env` values publicly.

## 3. Git Discipline

Before deployment:

```bash
git status --short
git diff
```

Stage only the files related to the current task:

```bash
git add <file1> <file2>
git commit -m "Short clear commit message"
git push origin feature/program-go
```

Do not commit these cancelled local logo/favicon files unless the user explicitly approves:

```text
public/favicon.ico
public/favicon.png
go_logo_v1.png
resources/views/auth/login.blade.php
resources/views/welcome.blade.php
```

If they become staged accidentally:

```bash
git restore --staged public/favicon.ico public/favicon.png go_logo_v1.png resources/views/auth/login.blade.php resources/views/welcome.blade.php
```

## 4. AWS Production Target

Known production target:

```text
Host/IP: 54.151.202.158
Public URL: https://go.jtmkpolimas.com
SSH user: ubuntu
Project path: /var/www/jtmkgo
Deploy branch: feature/program-go
```

The old domain `jtmkgo.ddns.net` (free No-IP dynamic DNS) is retired and no longer resolves. Do not reference it in new work; use `go.jtmkpolimas.com`.

Private key content must not be committed. Keep the key locally and configure it per PC.

Old local key path used on the original PC:

```text
C:\Users\Administrator\OneDrive - polimas.edu.my\Documents\JTMK GO\jtmkgo.ppk
```

On a new PC, either place the key in an equivalent private location or update the deploy command path.

## 4.1 SSL / HTTPS (Certbot)

Set up 2026-07-08. Do not redo this setup unless the certificate is genuinely broken or the domain changes.

```text
Domain: go.jtmkpolimas.com
DNS: A record -> 54.151.202.158, managed via mschosting.com nameservers
Certificate: Let's Encrypt, obtained via Certbot (snap install, --nginx plugin)
Auto-renewal: snap.certbot.renew.timer (runs twice daily, renews when close to expiry)
nginx site config: /etc/nginx/sites-available/jtmkgo (managed blocks for SSL + HTTP->HTTPS redirect added by Certbot)
```

`APP_URL` in production `.env` is `https://go.jtmkpolimas.com`.

To check certificate status or force a renewal test (does not consume real rate limits):

```bash
sudo certbot certificates
sudo certbot renew --dry-run
```

If the domain ever changes, re-run `sudo certbot --nginx -d <new-domain> --non-interactive --agree-tos -m <email> --redirect` after updating `server_name` in the nginx config and confirming DNS resolves to the server first — Let's Encrypt cannot issue a certificate for a domain that does not resolve to this server.

## 5. AWS Deploy With PuTTY Plink

From Windows PowerShell, after pushing to GitHub:

```powershell
& "C:\Program Files\PuTTY\plink.exe" -batch -i "C:\Path\To\jtmkgo.ppk" ubuntu@54.151.202.158 'cd /var/www/jtmkgo && git fetch origin feature/program-go && git pull --ff-only origin feature/program-go && php artisan optimize:clear && php artisan view:cache && git log -1 --oneline'
```

If migrations are required and approved:

```powershell
& "C:\Program Files\PuTTY\plink.exe" -batch -i "C:\Path\To\jtmkgo.ppk" ubuntu@54.151.202.158 'cd /var/www/jtmkgo && php artisan migrate --force'
```

Do not run migrations automatically unless the user requested or approved it.

## 6. AWS Deploy With OpenSSH

If using OpenSSH instead of PuTTY:

```bash
ssh -i /path/to/key ubuntu@54.151.202.158
cd /var/www/jtmkgo
git fetch origin feature/program-go
git pull --ff-only origin feature/program-go
php artisan optimize:clear
php artisan view:cache
git log -1 --oneline
```

If migrations are approved:

```bash
php artisan migrate --force
```

## 7. Production Dirty Files

The server may show unrelated runtime/cache/storage dirty files. Do not reset or delete them blindly.

Known examples:

```text
bootstrap/cache/*.tmp
storage/**/.gitignore
deploy.sh
```

Treat these as unrelated unless the task is specifically about deployment cleanup.

## 8. Post Deployment Checks

After deployment, verify:

```bash
git log -1 --oneline
php artisan route:list
php artisan optimize:clear
php artisan view:cache
```

In browser, at `https://go.jtmkpolimas.com`:

- Hard refresh with Ctrl + F5.
- Test the updated page.
- If CSS/JS changed, ensure assets were built and deployed.

If Vite assets changed and production uses built assets:

```bash
npm install
npm run build
php artisan optimize:clear
```

Use this on production only when Node/npm are available and the deployment policy allows it.

## 9. Common Manual Commands

Clear Laravel cache:

```bash
php artisan optimize:clear
```

Cache Blade views:

```bash
php artisan view:cache
```

Run migrations after approval:

```bash
php artisan migrate --force
```

Create storage link:

```bash
php artisan storage:link
```

Build frontend:

```bash
npm run build
```

## 10. New Codex Session Handoff Prompt

Use this when starting on another PC:

```text
Read docs/JTMK_GO_PROJECT_CONTEXT.md and docs/JTMK_GO_DEPLOYMENT_RUNBOOK.md first.
Continue development on branch feature/program-go.
Do not commit cancelled logo/favicon local files.
Before AWS deployment, commit and push to GitHub first, then ask for permission unless I explicitly say deploy now.
```

