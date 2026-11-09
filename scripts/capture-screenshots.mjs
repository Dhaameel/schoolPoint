import fs from 'node:fs';
import path from 'node:path';
import puppeteer from 'puppeteer-core';

const baseUrl = process.env.APP_URL ?? 'http://127.0.0.1:8001';
const chromePath = process.env.CHROME_PATH ?? 'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe';
const outputDir = path.resolve('storage/screenshots');

fs.mkdirSync(outputDir, { recursive: true });

async function capture(name, width, height) {
    const browser = await puppeteer.launch({
        executablePath: chromePath,
        headless: true,
        args: ['--no-sandbox', '--disable-gpu'],
    });

    try {
        const page = await browser.newPage();
        await page.setViewport({ width, height, deviceScaleFactor: 1 });
        await page.emulateMediaFeatures([{ name: 'prefers-reduced-motion', value: 'reduce' }]);

        await page.goto(`${baseUrl}/login`, { waitUntil: 'networkidle0' });
        await page.screenshot({ path: path.join(outputDir, `login-${name}.png`), fullPage: true });

        await page.type('#username', 'owner');
        await page.type('#password', 'password');

        await Promise.all([
            page.waitForNavigation({ waitUntil: 'networkidle0' }),
            page.click('button[type="submit"]'),
        ]);

        await page.goto(`${baseUrl}/dashboard`, { waitUntil: 'networkidle0' });
        await page.screenshot({ path: path.join(outputDir, `shell-${name}.png`), fullPage: true });
    } finally {
        await browser.close();
    }
}

await capture('desktop', 1440, 900);
await capture('phone', 390, 844);

console.log(`Screenshots saved to ${outputDir}`);
