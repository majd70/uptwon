# Uptown Restaurant & Café — QR menu & link page

Bilingual (Arabic / English) digital menu for Uptown Restaurant & Café. Diners scan a
QR code at the table, land on a mobile-first link page, and tap through to the full
menu. Staff manage everything from a Filament admin panel at `/admin`.

Laravel 12 · PHP 8.2 · MySQL · Tailwind v4 · Alpine.js · Filament v3

---

## What ships in this repository

**The menu is already in here.** You do not need the restaurant's original
spreadsheet, Word document or PDF to deploy — the extracted dataset and the
processed photographs travel with the code:

| | |
|---|---|
| `database/data/menu_data.json` | 8 categories, 85 items, Arabic + English names and descriptions, prices |
| `storage/app/public/menu-items/` | 68 WebP files — 34 dishes, each with a full-size and a thumbnail (~2 MB) |
| `public/build/` | compiled CSS and JS, **committed on purpose** so the server never needs Node |
| `public/fonts/` | the two self-hosted display faces (26 KB) |

`php artisan menu:import` reads the JSON and, when the original photographs are not
present, links each dish to the WebP file already sitting in `storage/`. So a fresh
clone reproduces the exact same menu, images included.

Not in the repository (by design): `.env`, `vendor/`, `node_modules/`, and the
`public/storage` symlink — all created during setup.

---

## Requirements

| | |
|---|---|
| PHP | 8.2+ with `gd`, `zip`, `fileinfo`, `mbstring`, `pdo_mysql`, `exif` |
| MySQL / MariaDB | 5.7+ / 10.3+ |
| Composer | 2.x |
| Node | **not required on the server** — only to rebuild assets during development |

Imagick is *not* required; QR code PNGs are rasterised with GD.

---

## Local setup

```bash
git clone https://github.com/majd70/uptwon.git
cd uptwon

composer install
cp .env.example .env
php artisan key:generate
```

Create the database, then set `DB_*` and **`APP_URL`** in `.env`. `APP_URL` must match
the address you actually open — image URLs and the QR code target are both built from
it. Once the site is live you can override it from the dashboard instead: see
**Website address** below.

```sql
CREATE DATABASE uptown_menu CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

```bash
php artisan migrate
php artisan db:seed          # settings row + admin user
php artisan storage:link
php artisan menu:import      # 8 categories, 85 items, 34 photos
php artisan serve --port=8000
```

| URL | |
|---|---|
| `/` | landing page |
| `/menu` | full menu |
| `/admin` | admin panel |

Default login **admin@uptown.test** / **password** — change it immediately from
**the avatar menu → Profile**.

Only if you edit anything under `resources/`:

```bash
npm install && npm run build   # then commit public/build
```

---

## Deploying to Hostinger

The app is deployed once by hand, then every push to `main` updates it automatically
(`.github/workflows/deploy.yml`).

### 1. Point the domain at Hostinger

In hPanel → **Domains**, either add `uptownrest.com` and set the registrar's
nameservers to Hostinger's, or complete a domain transfer. Nameservers propagate in
hours; a transfer takes 5–7 days — you do not need to wait for the transfer to launch
the site.

Then enable **SSL** (hPanel → Security → SSL) so the site is served over HTTPS.

### 2. Set PHP to 8.2+

hPanel → **Advanced → PHP Configuration**. Enable `gd`, `zip`, `fileinfo`, `mbstring`,
`pdo_mysql`, `exif`.

### 3. Create the database

hPanel → **Databases → MySQL**. Note the database name, user and password — Hostinger
prefixes them (e.g. `u123456789_uptown`).

### 4. First deploy, over SSH

hPanel → **Advanced → SSH Access**. Note the host, port (usually **65002**) and
username, then:

```bash
ssh -p 65002 uXXXXXXXX@your-server-ip

cd ~/domains/uptownrest.com
git clone https://github.com/majd70/uptwon.git app
cd app

composer install --no-dev --optimize-autoloader
cp .env.example .env
php artisan key:generate
nano .env          # set DB_DATABASE / DB_USERNAME / DB_PASSWORD, APP_ENV=production, APP_DEBUG=false

php artisan migrate --force
php artisan db:seed --force      # settings row + admin user — first time only
php artisan menu:import          # the 85 items and their photos — first time only
php artisan storage:link
php artisan config:cache && php artisan route:cache && php artisan view:cache
```

### 5. Make `public/` the web root

Laravel must serve from `public/`, never from the project root:

```bash
cd ~/domains/uptownrest.com
rm -rf public_html
ln -s app/public public_html
```

The symlink means a deploy updates the served files too, with nothing to copy.

> If the host refuses a symlinked document root, keep `public_html` as a real folder,
> copy `app/public/*` into it, edit `public_html/index.php` so both `require` paths
> point at `../app/`, and add `cp -r public/. ../public_html/` to the deploy script —
> otherwise CSS and images will not update on deploy.

### 6. Set the live domain in the dashboard

Open `https://uptownrest.com/admin` → **Settings → Website** and enter
`https://uptownrest.com`. That drives the QR code and every image URL, and it survives
`config:cache`.

### 7. Turn on automatic deployment

Generate a key **on the server**, authorise it, and give the private half to GitHub:

```bash
ssh-keygen -t ed25519 -C "github-deploy" -f ~/.ssh/github_deploy -N ""
cat ~/.ssh/github_deploy.pub >> ~/.ssh/authorized_keys
chmod 600 ~/.ssh/authorized_keys
cat ~/.ssh/github_deploy          # copy the whole private key, BEGIN/END lines included
```

In GitHub → **Settings → Secrets and variables → Actions → New repository secret**:

| Secret | Value |
|---|---|
| `SSH_HOST` | your server IP from hPanel |
| `SSH_USER` | `uXXXXXXXX` |
| `SSH_PORT` | `65002` |
| `SSH_KEY` | the **private** key printed above |
| `DEPLOY_PATH` | `/home/uXXXXXXXX/domains/uptownrest.com/app` |
| `PHP_BIN` | optional — e.g. `/usr/bin/php8.2` if plain `php` is an older build |

Push to `main`, then watch **Actions** in GitHub. You can also trigger it by hand from
that tab (*Run workflow*).

### What the deploy does — and deliberately does not

Each run pulls `main`, installs production dependencies, runs migrations, and rebuilds
the config, route and view caches.

It **never runs `menu:import`**. That command rewrites names, descriptions and prices
from `database/data/menu_data.json`, so running it on every deploy would silently undo
every price change made in the admin. Run it by hand, over SSH, only when the source
data itself changes.

It also never runs `db:seed`, so the admin password and settings are never reset.

### Post-deploy checklist

- [ ] `https://uptownrest.com` and `/menu` load, **with dish photos showing**
- [ ] **Settings → Website** set to `https://uptownrest.com`
- [ ] `APP_ENV=production`, `APP_DEBUG=false` in `.env`
- [ ] Admin password changed from the seeded default (avatar menu → Profile)
- [ ] Real Instagram / Facebook / TikTok URLs set (the repo ships placeholders)
- [ ] Phone, WhatsApp and Google Maps link filled in
- [ ] QR code printed **after** the domain was set — check the URL under the preview
- [ ] Compression on: `curl -sH 'Accept-Encoding: gzip' -o /dev/null -w '%{size_download}\n' https://uptownrest.com/menu` returns ~12 KB, not ~200 KB

---


## `menu:import`

```bash
php artisan menu:import                    # import / re-import
php artisan menu:import --dry-run          # report changes, write nothing
php artisan menu:import --skip-images      # text only
php artisan menu:import --prune            # delete anything not in the data file
php artisan menu:import --file=path.json   # use a different data file
```

**Idempotent** — categories are keyed on `name_en`, items on (category, `name_en`), so
re-running updates rows instead of duplicating them. It deliberately does *not* reset
`is_available` or `is_featured`: those are editorial choices made in the admin, not
facts from the source documents.

Eight items have no price in the restaurant's own spreadsheet. They are stored as
`NULL`, never guessed, and render as `—`.

Twenty items have no Arabic name — the Arabic source menu has no dessert section at
all. The UI falls back to English for those.

---

## How the site behaves

**Language.** `SetLocale` resolves the locale from `?lang=ar|en`, then the
`uptown_locale` cookie, then the *Default language* setting; an explicit switch is
remembered for a year. Arabic sets `dir="rtl"` and the layout mirrors.
`$item->name` and `$item->description` return the current language and fall back to
the other when empty. The admin panel is always English.

**Cover image.** Shown as the backdrop behind the restaurant name, darkened rather
than faded out — the filter drops its brightness instead of its opacity, so the
artwork stays readable as shape and detail while cream type keeps its contrast on
top. A cover on a light ground would otherwise swallow the type entirely. Without a
cover, a dish photograph stands in.

**Branding.** Colours, logo, monogram, contact details, social links and working hours
all come from the single `restaurant_settings` row, cached and exposed through
`settings()`. The mark inside the gold ring is an uploaded **logo**, or the
**monogram** letters, or the initials of the English name — in that order.

**Caching.** The menu payload is cached for 12 hours and invalidated automatically
whenever a category, item or the settings row is saved, or `menu:import` runs.

**Scan tracking.** Hitting `/` with `?utm_source=qr` writes a `qr_scans` row; plain
visits are not counted. The dashboard shows today / 7-day / 30-day totals.

The admin offers a single restaurant-wide QR code. Per-table codes were removed from
the UI, but `QrCodeBuilder::url($table)`, the `?table=N` parameter and the
`qr_scans.table_number` column all still work — the feature can return without a
migration.

**Website address.** `Settings → Website` holds the address guests actually open.
When set it overrides `APP_URL` at runtime for the whole app — the QR code target,
every menu photo URL, and generated links — so moving to a real domain needs no file
editing and no `config:clear`. Left empty, everything falls back to `APP_URL`. A
trailing slash is trimmed automatically, and the tab shows exactly what the QR code
will encode.

Note that a **printed** QR code cannot be updated: the address is baked into the
image. Set the domain first, then print.

**Accounts.** There is one admin account, created by `db:seed`. The avatar menu opens
a **Profile** page for changing the name, email and password; the password change asks
for the current one first, so an unattended session cannot be used to lock the owner
out. Self-registration and password reset are deliberately off — this panel controls
the live menu, and a reset flow would need working outbound mail. Add a second account
with `php artisan tinker` if a colleague needs one.

**Rate limiting.** Public routes are capped at 90 requests/minute per IP.

**Uploads.** Validated (JPEG/PNG/WebP, ≤ 3 MB), resized to 1200px and converted to
WebP, with a matching thumbnail — the same treatment imported images get.

---

## The design

**Direction — "after dark".** The two brand colours are kept but their ratio is
inverted: the deep green is the ground rather than an accent, so the page reads as a
dining room in the evening and the food photography is the only bright thing on it. A
brass accent carries the rules, the icons and the one primary button. All three
colours are editable under Settings → Branding.

**Type — "inscription".** Marcellus (Roman inscriptional capitals) for Latin and Reem
Kufi (geometric Kufi) for Arabic — both descend from carved and constructed
letterforms rather than from a pen, which is what lets the two scripts look like one
restaurant instead of two. Body text stays on the system stack. Self-hosted, 26 KB
total, no third-party request; only the face for the language being read is preloaded.

**Gilding.** Flat gold reads as mustard, so the accent is a gradient with light and
dark stops. Behind it sits an eight-point-star lattice at 5% opacity — a Cairo motif,
at a strength where it registers as texture rather than pattern.

**Binding.** Both pages are one column with a gold hairline down each side running the
full length of the page, and the hero photograph is a page-level backdrop that bleeds
the full viewport width and fades to solid ground on every edge. Nothing is a panel
sitting on a background, so no seam is visible.

**Menu rows** are set like a printed bill of fare: dish name, dotted leader, price,
description underneath. Tapping one opens a centred dialog — a full photograph fading
into the card, then the rule, the gilded name, the price between two brass lines, and
the full description.

---

## Tests

```bash
php artisan test
```

44 feature tests: landing and menu load, menu lists imported items, null prices render
`—`, hidden and empty categories stay hidden, locale switching flips `lang`/`dir`, the
locale cookie persists, name fallback between languages, monogram falls back from logo
to letters to initials, QR scan logging, admin routes require auth and load once
authenticated, QR PNG is a decodable image and SVG is valid XML, and `menu:import` is
idempotent across repeated runs, and the dashboard's Website address overrides
`APP_URL` while falling back to it when empty. Six more cover the profile page:
the password only changes when the current one is given and correct.

Tests run against in-memory SQLite and never touch the real database.

### Browser and performance checks

With the dev server running:

```bash
node tests/Browser/screenshots.mjs         # public pages, 375/414/768/1280 in AR + EN
node tests/Browser/admin-screenshots.mjs   # admin panel
node tests/Browser/lighthouse.mjs          # mobile Lighthouse
```

The first asserts, per breakpoint: `dir`/`lang` flip, no horizontal overflow, tap
targets ≥ 44px on mobile, no broken images, the category strip scrolls, scroll-spy
tracks the active tab, search filters, and the dialog opens.

Measured on the Lighthouse mobile preset, served **uncompressed** by `artisan serve`:

| Page | Perf | A11y | Best practices | SEO |
|---|---|---|---|---|
| `/` (ar) | 98 | 100 | 100 | 100 |
| `/` (en) | 99 | 100 | 100 | 100 |
| `/menu` (ar) | 84 | 100 | 100 | 100 |
| `/menu` (en) | 87 | 100 | 100 | 100 |

The menu lists all 85 items in one document: 206 KB uncompressed, **12 KB gzipped**.
`public/.htaccess` enables `mod_deflate`/`mod_brotli`, which any real host uses — the
figures above are a worst case.
