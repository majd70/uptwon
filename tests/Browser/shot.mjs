/** Quick design-iteration screenshots: node tests/Browser/shot.mjs "/path|name|full" ... */
import { chromium } from 'playwright';
import { mkdirSync } from 'node:fs';
const BASE = process.env.BASE_URL ?? 'http://127.0.0.1:8567';
const OUT = 'storage/app/design';
mkdirSync(OUT, { recursive: true });
const b = await chromium.launch();
const c = await b.newContext({ viewport: { width: 390, height: 844 }, deviceScaleFactor: 2, isMobile: true, hasTouch: true });
const p = await c.newPage();
for (const arg of process.argv.slice(2)) {
  const [url, name, full] = arg.split('|');
  await p.goto(BASE + url, { waitUntil: 'networkidle' });
  await p.waitForTimeout(800);
  await p.screenshot({ path: `${OUT}/${name}.png`, fullPage: full === 'full' });
  console.log(name);
}
await b.close();
