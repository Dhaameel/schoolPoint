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
    await page.screenshot({ path: path.join(outputDir, `stage5-${name}.png`), fullPage });
}

async function capture(size, label) {
    const browser = await puppeteer.launch({ executablePath: chromePath, headless: true, args: ['--no-sandbox', '--disable-gpu'] });

    try {
        const page = await browser.newPage();
        await page.setViewport({ width: size.width, height: size.height, deviceScaleFactor: 1 });
        await page.emulateMediaFeatures([{ name: 'prefers-reduced-motion', value: 'reduce' }]);
        await login(page, 'owner', 'password');

        await page.goto(`${baseUrl}/reports`, { waitUntil: 'networkidle0' });
        await shot(page, `reports-${label}`);

        await page.goto(`${baseUrl}/reports/students`, { waitUntil: 'networkidle0' });
        await shot(page, `student-report-${label}`);

        await page.goto(`${baseUrl}/reports/teachers`, { waitUntil: 'networkidle0' });
        await shot(page, `teacher-report-${label}`);

        await page.goto(`${baseUrl}/reports/classes`, { waitUntil: 'networkidle0' });
        await shot(page, `class-report-${label}`);

        if (label === 'desktop') {
            await page.goto(`${baseUrl}/reports/students/print`, { waitUntil: 'networkidle0' });
            await shot(page, 'student-report-print-desktop');

            await page.goto(`${baseUrl}/reports/classes/print`, { waitUntil: 'networkidle0' });
            await shot(page, 'class-report-print-desktop');
        }
    } finally {
        await browser.close();
    }
}

await capture({ width: 1440, height: 900 }, 'desktop');
await capture({ width: 390, height: 844 }, 'phone');

console.log(`Stage 5 screenshots saved to ${outputDir}`);
