import fs from 'node:fs';
import path from 'node:path';
import assert from 'node:assert/strict';
const {chromium}=await import(process.env.PLAYWRIGHT_MODULE || 'playwright');
const artifact=process.env.ROLE_UI_ARTIFACT;
const html=fs.readFileSync(path.join(artifact,'storage/framework/testing/v121-role-editor.html'),'utf8');
const browser=await chromium.launch({headless:true});
const page=await browser.newPage({viewport:{width:1440,height:1000}});
await page.route('**/*', async route=>{
 const url=new URL(route.request().url());
 if(url.hostname!=='localhost'){await route.abort();return;}
 const base=url.pathname.startsWith('/build/')?path.resolve(artifact,'public'):path.resolve('public');
 const target=path.resolve(base,'.'+decodeURIComponent(url.pathname));
 if(!target.startsWith(base+path.sep)||!fs.existsSync(target)||!fs.statSync(target).isFile()){await route.abort();return;}
 await route.fulfill({path:target});
});
await page.setContent(html,{waitUntil:'domcontentloaded'});
const save=page.getByRole('button',{name:'Save Role Permissions',exact:true});
assert.equal(await save.evaluate(el=>getComputedStyle(el).backgroundColor),'rgb(111, 66, 193)');
assert.equal(await page.locator('.card-primary .card-header').first().evaluate(el=>getComputedStyle(el).backgroundColor),'rgb(111, 66, 193)');
const view=page.locator('input[name="permissions[]"][value="members.view"]');
const create=page.locator('input[name="permissions[]"][value="members.create"]');
await view.uncheck();await create.check();
assert.equal(await view.evaluate(el=>el.checked),false);
assert.equal(await create.evaluate(el=>el.checked),true);
await page.locator('[data-permission-all]').check();
assert.equal(await page.locator('input[name="permissions[]"]').evaluateAll(els=>els.every(el=>el.checked)),true);
fs.mkdirSync('storage/framework/testing',{recursive:true});
await page.screenshot({path:'storage/framework/testing/permissions-desktop.png',fullPage:true});
await page.locator('body').evaluate(el=>el.classList.add('dark-mode'));
assert.equal(await save.evaluate(el=>getComputedStyle(el).backgroundColor),'rgb(111, 66, 193)');
await page.screenshot({path:'storage/framework/testing/permissions-dark.png',fullPage:true});
await page.setViewportSize({width:390,height:844});
assert.equal(await save.isVisible(),true);
assert.equal(await page.locator('section.content').evaluate(el=>el.scrollWidth<=el.clientWidth+1),true);
await page.screenshot({path:'storage/framework/testing/permissions-mobile.png',fullPage:true});
console.log('PASS: actual rendered role editor, branded headers/buttons, dark mode, mobile layout, independent actions and select-all.');
await browser.close();
