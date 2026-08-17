import { chromium } from 'playwright';
import { mkdirSync } from 'node:fs';
const BASE = process.env.BASE_URL ?? 'http://127.0.0.1:8567';
mkdirSync('storage/app/design', { recursive: true });
const b = await chromium.launch();
for (const [lang, w, h, tag] of [['ar', 390, 844, 'm'], ['en', 390, 844, 'm'], ['en', 1280, 900, 'w']]) {
  const c = await b.newContext({ viewport: { width: w, height: h }, deviceScaleFactor: 2, isMobile: w < 500, hasTouch: w < 500 });
  const p = await c.newPage();
  await p.goto(`${BASE}/menu?lang=${lang}`, { waitUntil: 'networkidle' });
  await p.waitForTimeout(600);
  // click the thumbnail specifically
  await p.locator('li[data-search] .u-thumb').first().click();
  await p.waitForTimeout(900);
  await p.screenshot({ path: `storage/app/design/dialog-${lang}-${tag}.png` });
  console.log(`dialog-${lang}-${tag}`, await p.locator('[role="dialog"]').isVisible());
  await c.close();
}
await b.close();
