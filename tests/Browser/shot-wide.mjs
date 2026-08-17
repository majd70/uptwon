import { chromium } from 'playwright';
import { mkdirSync } from 'node:fs';
const BASE = process.env.BASE_URL ?? 'http://127.0.0.1:8567';
mkdirSync('storage/app/design', { recursive: true });
const b = await chromium.launch();
for (const [w, h, tag] of [[1280, 900, 'w1280'], [768, 1024, 'w768']]) {
  const c = await b.newContext({ viewport: { width: w, height: h }, deviceScaleFactor: 1 });
  const p = await c.newPage();
  for (const lang of ['ar', 'en']) {
    await p.goto(`${BASE}/?lang=${lang}`, { waitUntil: 'networkidle' });
    await p.waitForTimeout(700);
    await p.screenshot({ path: `storage/app/design/land-${lang}-${tag}.png` });
  }
  await c.close();
}
await b.close();
console.log('ok');
