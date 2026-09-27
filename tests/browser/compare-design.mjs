// Tangkapan layar halaman yang disetujui (docs/design) berdampingan dengan situs, untuk dibandingkan.
// Pakai: node tests/browser/compare-design.mjs <folder-keluaran> [lebar=1280] [light|dark]
// Situs harus berjalan di http://127.0.0.1:8123.
import { chromium } from 'playwright';
const [out, w, scheme] = [process.argv[2], Number(process.argv[3] || 1280), process.argv[4] || 'light'];
const b = await chromium.launch();
const shots = [
  ['design-home', new URL('../../docs/design/', import.meta.url).href + 'kamalan-home.html'],
  ['ours-home', 'http://127.0.0.1:8123/'],
  ['design-detail', new URL('../../docs/design/', import.meta.url).href + 'kamalan-vendor-detail.html'],
  ['ours-detail', 'http://127.0.0.1:8123/wedding/venue/taman-cilandak'],
];
for (const [name, url] of shots) {
  const p = await b.newPage({ viewport: { width: w, height: 900 }, colorScheme: scheme });
  await p.route(/fonts\.(googleapis|gstatic)/, (r) => r.abort());
  await p.goto(url); await p.waitForTimeout(400);
  await p.screenshot({ path: `${out}/${name}-${w}-${scheme}.png`, fullPage: true });
  console.log(name, await p.evaluate(() => document.documentElement.scrollHeight));
  await p.close();
}
await b.close();
