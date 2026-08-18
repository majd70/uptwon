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
it.

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

Default login **admin@uptown.test** / **password** — change it immediately.

Only if you edit anything under `resources/`:

```bash
npm install && npm run build   # then commit public/build
```

---

## Deploying to shared hosting (cPanel / Hostinger)

Laravel's document root must be `public/`, and the rest of the app must sit **outside**
the web root.

1. **Upload** the project to `~/uptown` (a sibling of `public_html`), and the contents
   of `uptown/public/` into `public_html/`.

2. **Repoint the bootstrap** in `public_html/index.php`:

   ```php
   require __DIR__.'/../uptown/vendor/autoload.php';
   $app = require_once __DIR__.'/../uptown/bootstrap/app.php';
   ```

3. **Environment** — copy `.env.example` to `~/uptown/.env` and set:

   ```
   APP_ENV=production
   APP_DEBUG=false
   APP_URL=https://yourdomain.com
   ```

   Generate a key with `php artisan key:generate --show` and paste it into `APP_KEY`.
   Create the MySQL database in cPanel and fill in `DB_*`.

4. **Install and migrate:**

   ```bash
   composer install --no-dev --optimize-autoloader
   php artisan migrate --force
   php artisan db:seed --force
   php artisan storage:link
   php artisan menu:import
   ```

   If SSH is unavailable, upload `vendor/` from your machine and run the commands from
   cPanel's Terminal.

5. **Storage link** — `storage:link` needs symlink support. If the host blocks it,
   copy `storage/app/public/` into `public_html/storage/` instead. The menu photos
   live there, so this step is what makes the images appear.

6. **Cache for production:**

   ```bash
   php artisan config:cache && php artisan route:cache && php artisan view:cache
   ```

   Re-run these after any `.env` change.

7. **Permissions** — `storage/` and `bootstrap/cache/` must be writable (755, or 775
   if PHP runs as a different user).

8. **Print the QR code** from **Admin → QR code** *after* `APP_URL` points at the
   live domain — the code encodes that URL. The page warns you while it still points
   at a non-HTTPS address.

### Post-deploy checklist

- [ ] `/` and `/menu` load, **with dish photos showing**
- [ ] `APP_URL` matches the live domain
- [ ] `APP_DEBUG=false`, `APP_ENV=production`
- [ ] Admin password changed from the seeded default
- [ ] Real Instagram / Facebook / TikTok URLs set (the repo ships placeholders)
- [ ] Phone, WhatsApp and Google Maps link filled in under Settings
- [ ] Compression is on — `curl -sH 'Accept-Encoding: gzip' -o /dev/null -w '%{size_download}\n' https://yourdomain.com/menu` should return ~12 KB, not ~200 KB

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

36 feature tests: landing and menu load, menu lists imported items, null prices render
`—`, hidden and empty categories stay hidden, locale switching flips `lang`/`dir`, the
locale cookie persists, name fallback between languages, monogram falls back from logo
to letters to initials, QR scan logging, admin routes require auth and load once
authenticated, QR PNG is a decodable image and SVG is valid XML, and `menu:import` is
idempotent across repeated runs.

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
