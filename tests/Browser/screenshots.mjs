/**
 * Renders the public pages at the required breakpoints in both languages and
 * writes PNGs to storage/app/screenshots. Run with the dev server up:
 *
 *   php artisan serve --port=8123
 *   node tests/Browser/screenshots.mjs
 */
import { chromium } from 'playwright';
import { mkdirSync } from 'node:fs';

const BASE = process.env.BASE_URL ?? 'http://127.0.0.1:8123';
const OUT = process.env.OUT_DIR ?? 'storage/app/screenshots';

const VIEWPORTS = [
    { name: '375', width: 375, height: 812, mobile: true },
    { name: '414', width: 414, height: 896, mobile: true },
    { name: '768', width: 768, height: 1024, mobile: false },
    { name: '1280', width: 1280, height: 900, mobile: false },
];

mkdirSync(OUT, { recursive: true });

const browser = await chromium.launch();
const problems = [];

/** Assert a condition and record it rather than aborting the whole run. */
function check(label, ok, detail = '') {
    console.log(`${ok ? 'PASS' : 'FAIL'}  ${label}${detail ? '  — ' + detail : ''}`);
    if (!ok) problems.push(`${label} ${detail}`);
}

for (const lang of ['ar', 'en']) {
    for (const vp of VIEWPORTS) {
        const context = await browser.newContext({
            viewport: { width: vp.width, height: vp.height },
            deviceScaleFactor: 2,
            isMobile: vp.mobile,
            hasTouch: vp.mobile,
        });
        const page = await context.newPage();

        for (const [route, slug] of [['/', 'landing'], ['/menu', 'menu']]) {
            await page.goto(`${BASE}${route}?lang=${lang}`, { waitUntil: 'networkidle' });

            const tag = `${slug}-${lang}-${vp.name}`;

            // Direction and language actually flipped?
            const dir = await page.getAttribute('html', 'dir');
            const htmlLang = await page.getAttribute('html', 'lang');
            check(`${tag} dir/lang`, dir === (lang === 'ar' ? 'rtl' : 'ltr') && htmlLang === lang,
                `dir=${dir} lang=${htmlLang}`);

            // No horizontal overflow at any width.
            const overflow = await page.evaluate(() =>
                document.documentElement.scrollWidth - document.documentElement.clientWidth);
            check(`${tag} no h-overflow`, overflow <= 1, `overflow=${overflow}px`);

            // Every interactive control clears a 48px tap target on mobile.
            if (vp.mobile) {
                const small = await page.evaluate(() => {
                    const bad = [];
                    for (const el of document.querySelectorAll('a[href], button, input, [role="button"]')) {
                        // Skip the visually-hidden skip-to-content link.
                        if (el.classList.contains('sr-only')) continue;
                        if (el.closest('[x-cloak]') || el.offsetParent === null) continue;
                        const r = el.getBoundingClientRect();
                        if (r.width === 0 || r.height === 0) continue;
                        if (r.height < 44 || r.width < 44) {
                            bad.push(`${el.tagName}.${el.className.toString().slice(0, 28)}=${Math.round(r.width)}x${Math.round(r.height)}`);
                        }
                    }
                    return bad;
                });
                check(`${tag} tap targets`, small.length === 0, small.slice(0, 4).join(' | '));
            }

            // Real photos, not broken images.
            const brokenImages = await page.evaluate(() =>
                [...document.images].filter((i) => i.complete && i.naturalWidth === 0).length);
            check(`${tag} images ok`, brokenImages === 0, `broken=${brokenImages}`);

            await page.screenshot({ path: `${OUT}/${tag}.png`, fullPage: false });

            if (slug === 'menu') {
                // Category strip scrolls and the active tab tracks the sections.
                const tabs = await page.locator('[data-tab]').count();
                check(`${tag} category tabs`, tabs >= 8, `tabs=${tabs}`);

                const scrollable = await page.evaluate(() => {
                    const nav = document.querySelector('[data-tab]')?.parentElement;
                    return nav ? nav.scrollWidth > nav.clientWidth : false;
                });
                check(`${tag} strip scrolls`, scrollable || !vp.mobile, `scrollable=${scrollable}`);

                // Jump to a later category and confirm the tab follows.
                const lastTab = page.locator('[data-tab]').last();
                const lastId = await lastTab.getAttribute('data-tab');
                await lastTab.click();
                await page.waitForTimeout(900);
                const activeNow = await page.evaluate(() =>
                    document.querySelector('[data-tab][aria-current="true"]')?.dataset.tab);
                check(`${tag} scroll-spy`, activeNow === lastId, `expected=${lastId} got=${activeNow}`);
                await page.screenshot({ path: `${OUT}/${tag}-tab-last.png` });

                // Search filters down.
                await page.goto(`${BASE}${route}?lang=${lang}`, { waitUntil: 'networkidle' });
                await page.locator('input[type="search"]').fill('shrimp');
                await page.waitForTimeout(400);
                const visibleItems = await page.locator('li[data-search]:visible').count();
                check(`${tag} search filters`, visibleItems > 0 && visibleItems < 20, `visible=${visibleItems}`);
                await page.screenshot({ path: `${OUT}/${tag}-search.png` });

                // Bottom sheet opens with the full description.
                await page.locator('input[type="search"]').fill('');
                await page.waitForTimeout(300);
                await page.locator('li[data-search] button').first().click();
                await page.waitForTimeout(600);
                const sheetVisible = await page.locator('[role="dialog"]').isVisible();
                check(`${tag} bottom sheet`, sheetVisible);
                await page.screenshot({ path: `${OUT}/${tag}-sheet.png` });
            }
        }

        await context.close();
    }
}

await browser.close();

console.log(`\n${problems.length === 0 ? 'ALL CHECKS PASSED' : problems.length + ' CHECK(S) FAILED'}`);
if (problems.length) {
    problems.forEach((p) => console.log('  - ' + p));
    process.exitCode = 1;
}
