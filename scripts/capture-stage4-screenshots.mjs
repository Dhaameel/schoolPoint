import fs from 'node:fs';
import path from 'node:path';
import puppeteer from 'puppeteer-core';

const baseUrl = process.env.APP_URL ?? 'http://127.0.0.1:8001';
const chromePath = process.env.CHROME_PATH ?? 'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe';
const outputDir = path.resolve('storage/screenshots');
fs.mkdirSync(outputDir, { recursive: true });

async function login(page, username, password) {
    await page.goto(`${baseUrl}/login`, { waitUntil: 'networkidle0' });
    await page.type('#username', username);
    await page.type('#password', password);
    await Promise.all([
        page.waitForNavigation({ waitUntil: 'networkidle0' }),
        page.click('button[type="submit"]'),
    ]);
}

async function shot(page, name, fullPage = true) {
    await page.screenshot({ path: path.join(outputDir, `stage4-${name}.png`), fullPage });
}

async function capture(size, label) {
    const browser = await puppeteer.launch({ executablePath: chromePath, headless: true, args: ['--no-sandbox', '--disable-gpu'] });

    try {
        const page = await browser.newPage();
        await page.setViewport({ width: size.width, height: size.height, deviceScaleFactor: 1 });
        await page.emulateMediaFeatures([{ name: 'prefers-reduced-motion', value: 'reduce' }]);
        await login(page, 'owner', 'password');

        await page.goto(`${baseUrl}/fees`, { waitUntil: 'networkidle0' });
        await shot(page, `fees-list-${label}`, label === 'desktop');

        await page.goto(`${baseUrl}/fees/structures`, { waitUntil: 'networkidle0' });
        await shot(page, `fee-structures-${label}`);

        await page.goto(`${baseUrl}/fees/bill`, { waitUntil: 'networkidle0' });
        await page.evaluate(() => {
            const select = document.getElementById('fee_structure_id');

            if (select) {
                select.selectedIndex = select.options.length - 1;
                select.dispatchEvent(new Event('change'));
            }
        });
        await shot(page, `bill-class-${label}`);

        await page.goto(`${baseUrl}/fees?status=partial`, { waitUntil: 'networkidle0' });
        const showHref = await page.evaluate(() => {
            const link = [...document.querySelectorAll('a[href]')].find((anchor) => /\/fees\/\d+$/.test(anchor.getAttribute('href') ?? ''));
            return link?.getAttribute('href') ?? null;
        });

        if (showHref) {
            await page.goto(new URL(showHref, baseUrl).href, { waitUntil: 'networkidle0' });
            await shot(page, `fee-detail-${label}`);

            const receiptHref = await page.evaluate(() => {
                const link = [...document.querySelectorAll('a[href]')].find((anchor) => /\/fees\/payments\/\d+\/receipt$/.test(anchor.getAttribute('href') ?? ''));
                return link?.getAttribute('href') ?? null;
            });

            if (receiptHref) {
                await page.goto(new URL(receiptHref, baseUrl).href, { waitUntil: 'networkidle0' });
                await shot(page, `receipt-${label}`);
            }
        }

        await page.goto(`${baseUrl}/fees?search=REV-`, { waitUntil: 'networkidle0' });
        const reversedHref = await page.evaluate(() => {
            const link = [...document.querySelectorAll('a[href]')].find((anchor) => /\/fees\/\d+$/.test(anchor.getAttribute('href') ?? ''));
            return link?.getAttribute('href') ?? null;
        });

        if (reversedHref) {
            await page.goto(new URL(reversedHref, baseUrl).href, { waitUntil: 'networkidle0' });
            await shot(page, `fee-reversal-${label}`);
        }

        await page.goto(`${baseUrl}/fees?status=void`, { waitUntil: 'networkidle0' });
        const voidHref = await page.evaluate(() => {
            const link = [...document.querySelectorAll('a[href]')].find((anchor) => /\/fees\/\d+$/.test(anchor.getAttribute('href') ?? ''));
            return link?.getAttribute('href') ?? null;
        });

        if (voidHref) {
            await page.goto(new URL(voidHref, baseUrl).href, { waitUntil: 'networkidle0' });
            await shot(page, `fee-void-${label}`);
        }
    } finally {
        await browser.close();
    }
}

await capture({ width: 1440, height: 900 }, 'desktop');
await capture({ width: 390, height: 844 }, 'phone');

console.log(`Stage 4 screenshots saved to ${outputDir}`);
