import assert from 'node:assert/strict';
import fs from 'node:fs/promises';
import path from 'node:path';
import { pathToFileURL } from 'node:url';

const root = process.cwd();
const playwright = await import(process.env.PLAYWRIGHT_MODULE ? pathToFileURL(process.env.PLAYWRIGHT_MODULE).href : 'playwright');
const browser = await playwright.chromium.launch({ headless: true,
  ...(process.env.CHROMIUM_EXECUTABLE ? { executablePath: process.env.CHROMIUM_EXECUTABLE, args: ['--no-sandbox', '--disable-dev-shm-usage', '--disable-gpu'] } : {}),
});
const artifact = process.env.ENTERPRISE_UI_ARTIFACT || root;
async function findFile(directory, name) {
  for (const entry of await fs.readdir(directory, { withFileTypes: true })) {
    if (entry.isFile() && entry.name === name) return path.join(directory, name);
    if (entry.isDirectory() && !['node_modules', 'vendor', '.git', 'public'].includes(entry.name)) {
      const found = await findFile(path.join(directory, entry.name), name);
      if (found) return found;
    }
  }
  return null;
}
const variants = ['primary', 'secondary', 'success', 'danger', 'warning', 'info', 'light', 'default', 'dark'];
let scenarios = 0, checks = 0;
try {
  for (const brand of ['008751', 'ffffff', 'ffc107', '6f42c1', '000000']) {
    const fixture = await findFile(artifact, `enterprise-addons-${brand}.html`);
    assert(fixture, `Missing rendered Blade fixture for ${brand}`);
    // Isolate presentation checks from unrelated application JS; CSS and Blade markup are unchanged.
    const html = (await fs.readFile(fixture, 'utf8')).replace(/<script\b[^>]*>[\s\S]*?<\/script>/gi, '');
    for (const width of [1280, 390]) for (const dark of [false, true]) {
      const page = await browser.newPage({ viewport: { width, height: 900 } });
      await page.route('**/*', async route => {
        const url = new URL(route.request().url());
        if (url.pathname === '/__enterprise_fixture') return route.fulfill({ contentType: 'text/html', body: html });
        const local = path.resolve(root, 'public', '.' + decodeURIComponent(url.pathname));
        if (local.startsWith(path.join(root, 'public') + path.sep)) {
          try { if ((await fs.stat(local)).isFile()) return route.fulfill({ path: local }); } catch {}
        }
        return route.fulfill({ status: 204, body: '' });
      });
      await page.goto('http://localhost/__enterprise_fixture');
      await page.evaluate(({ dark, variants }) => {
        document.body.classList.toggle('dark-mode', dark);
        document.querySelector('#app-sidebar')?.classList.toggle('sidebar-themed', dark);
        const container = document.createElement('div'); container.id = 'enterprise-button-fixture';
        container.innerHTML = variants.map(variant => `<button class="btn btn-${variant}" data-variant="${variant}"><span class="text-${variant}">${variant} action</span></button>`).join(' ');
        document.querySelector('.content-wrapper').append(container);
      }, { dark, variants });
      for (const state of ['normal', 'hover', 'focus', 'disabled']) {
        for (const variant of variants) {
          const button = page.locator(`[data-variant="${variant}"]`);
          await button.evaluate((element, disabled) => { element.disabled = disabled; }, state === 'disabled');
          if (state === 'hover') await button.hover();
          if (state === 'focus') await button.focus();
          const ratio = await button.evaluate(element => {
            const rgb = value => value.match(/[\d.]+/g).slice(0, 3).map(Number);
            const luminance = value => { const [r,g,b] = rgb(value).map(x => x / 255).map(x => x <= .04045 ? x / 12.92 : ((x + .055) / 1.055) ** 2.4); return .2126*r + .7152*g + .0722*b; };
            const a = luminance(getComputedStyle(element.querySelector('span')).color);
            const b = luminance(getComputedStyle(element).backgroundColor);
            return (Math.max(a,b) + .05) / (Math.min(a,b) + .05);
          });
          assert(ratio >= 4.5, `${brand}/${width}/${dark}/${variant}/${state}: ${ratio}`); checks++;
        }
      }
      assert.equal(await page.locator('h1').filter({ hasText: 'Add-ons' }).count(), 1);
      assert.equal(await page.locator('article').count(), 3);
      assert(await page.locator('body').innerText().then(text => text.includes('Locked')));
      const overflow = await page.evaluate(() => document.documentElement.scrollWidth > innerWidth + 1);
      assert.equal(overflow, false, `Horizontal overflow at ${brand}/${width}/${dark}`);
      if (brand === 'ffffff' || brand === '008751') {
        await page.screenshot({ path: path.join(root, `storage/framework/testing/enterprise-${brand}-${width}-${dark ? 'dark' : 'light'}.png`), fullPage: true });
      }
      scenarios++; await page.close();
    }
  }
  console.log(JSON.stringify({ renderedScenarios: scenarios, buttonContrastChecks: checks, minimumContrast: 4.5 }));
} finally { await browser.close(); }
