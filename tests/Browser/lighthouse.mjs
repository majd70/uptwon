/**
 * Mobile Lighthouse run over the two public pages, in both languages.
 *
 *   node tests/Browser/lighthouse.mjs
 */
import lighthouse from 'lighthouse';
import { launch } from 'chrome-launcher';

const BASE = process.env.BASE_URL ?? 'http://127.0.0.1:8567';
const TARGET = Number(process.env.TARGET ?? 90);

const chrome = await launch({ chromeFlags: ['--headless=new', '--no-sandbox'] });
const results = [];

for (const path of ['/?lang=ar', '/menu?lang=ar', '/?lang=en', '/menu?lang=en']) {
    const { lhr } = await lighthouse(
        `${BASE}${path}`,
        { port: chrome.port, output: 'json', logLevel: 'error' },
        // Default Lighthouse mobile preset: Moto G Power, 4x CPU throttle, slow 4G.
        undefined,
    );

    const score = (key) => Math.round((lhr.categories[key]?.score ?? 0) * 100);
    const row = {
        page: path,
        performance: score('performance'),
        accessibility: score('accessibility'),
        bestPractices: score('best-practices'),
        seo: score('seo'),
        lcp: lhr.audits['largest-contentful-paint']?.displayValue,
        cls: lhr.audits['cumulative-layout-shift']?.displayValue,
        tbt: lhr.audits['total-blocking-time']?.displayValue,
    };
    results.push(row);
    console.log(
        `${path.padEnd(16)} perf=${String(row.performance).padStart(3)}  a11y=${String(row.accessibility).padStart(3)}` +
        `  bp=${String(row.bestPractices).padStart(3)}  seo=${String(row.seo).padStart(3)}` +
        `  LCP=${row.lcp}  CLS=${row.cls}  TBT=${row.tbt}`,
    );
}

await chrome.kill();

const worst = Math.min(...results.map((r) => r.performance));
console.log(`\nLowest mobile performance score: ${worst} (target ${TARGET})`);
if (worst < TARGET) process.exitCode = 1;
