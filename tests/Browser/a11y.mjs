import lighthouse from 'lighthouse';
import { launch } from 'chrome-launcher';
const chrome = await launch({ chromeFlags: ['--headless=new', '--no-sandbox'] });
for (const path of ['/?lang=ar', '/menu?lang=ar']) {
  const { lhr } = await lighthouse(`http://127.0.0.1:8567${path}`,
    { port: chrome.port, output: 'json', logLevel: 'error', onlyCategories: ['accessibility'] });
  console.log(`\n### ${path}  score=${Math.round(lhr.categories.accessibility.score * 100)}`);
  for (const ref of lhr.categories.accessibility.auditRefs) {
    const a = lhr.audits[ref.id];
    if (a.score !== null && a.score < 1) {
      console.log(` FAIL ${a.id}: ${a.title}`);
      for (const it of (a.details?.items ?? []).slice(0, 4)) {
        console.log('   -', (it.node?.snippet ?? JSON.stringify(it)).slice(0, 150));
      }
    }
  }
}
await chrome.kill();
