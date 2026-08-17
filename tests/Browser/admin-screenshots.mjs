/**
 * Logs into the Filament panel and captures each admin screen.
 *
 *   ADMIN_EMAIL=... ADMIN_PASSWORD=... node tests/Browser/admin-screenshots.mjs
 */
import { chromium } from 'playwright';
import { mkdirSync } from 'node:fs';

const BASE = process.env.BASE_URL ?? 'http://127.0.0.1:8567';
const OUT = process.env.OUT_DIR ?? 'storage/app/screenshots-admin';
const EMAIL = process.env.ADMIN_EMAIL ?? 'admin@uptown.test';
const PASSWORD = process.env.ADMIN_PASSWORD ?? 'password';

mkdirSync(OUT, { recursive: true });

const browser = await chromium.launch();
const context = await browser.newContext({ viewport: { width: 1440, height: 1000 } });
const page = await context.newPage();
const problems = [];

function check(label, ok, detail = '') {
    console.log(`${ok ? 'PASS' : 'FAIL'}  ${label}${detail ? '  — ' + detail : ''}`);
    if (!ok) problems.push(`${label} ${detail}`);
}

// Log in
await page.goto(`${BASE}/admin/login`, { waitUntil: 'networkidle' });
await page.fill('input[type="email"]', EMAIL);
await page.fill('input[type="password"]', PASSWORD);
await Promise.all([
    page.waitForURL((u) => !u.pathname.endsWith('/login'), { timeout: 20000 }),
    page.click('button[type="submit"]'),
]);
check('login', !page.url().includes('/login'), page.url());

const PAGES = [
    ['/admin', 'dashboard'],
    ['/admin/categories', 'categories'],
    ['/admin/menu-items', 'menu-items'],
    ['/admin/manage-settings', 'settings'],
    ['/admin/qr-codes', 'qr-codes'],
];

for (const [url, slug] of PAGES) {
    await page.goto(`${BASE}${url}`, { waitUntil: 'networkidle' });
    await page.waitForTimeout(1200);

    const errors = await page.locator('text=/Whoops|Server Error|Exception/i').count();
    check(`${slug} renders`, errors === 0, `errorMarkers=${errors}`);

    await page.screenshot({ path: `${OUT}/${slug}.png`, fullPage: true });
}

// The QR preview must be a real, non-empty image.
await page.goto(`${BASE}/admin/qr-codes`, { waitUntil: 'networkidle' });
await page.waitForTimeout(1200);
const qrOk = await page.evaluate(() => {
    const img = document.querySelector('img[alt="QR code preview"]');
    return img ? img.naturalWidth > 100 : false;
});
check('qr preview image', qrOk);

// Editing an item should open the bilingual form.
await page.goto(`${BASE}/admin/menu-items/1/edit`, { waitUntil: 'networkidle' });
await page.waitForTimeout(1500);
check('item edit renders', !page.url().includes('/login'), page.url());
const hasArabic = await page.locator('input[dir="rtl"]').count();
check('item form has RTL fields', hasArabic > 0, `rtlInputs=${hasArabic}`);
await page.screenshot({ path: `${OUT}/menu-item-edit.png`, fullPage: true });

await browser.close();

console.log(`\n${problems.length === 0 ? 'ALL CHECKS PASSED' : problems.length + ' CHECK(S) FAILED'}`);
problems.forEach((p) => console.log('  - ' + p));
if (problems.length) process.exitCode = 1;
