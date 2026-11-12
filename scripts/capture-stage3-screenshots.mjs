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

async function shot(page, name) {
    await page.screenshot({ path: path.join(outputDir, `stage3-${name}.png`), fullPage: true });
}

async function capture(size, label) {
    const adminBrowser = await puppeteer.launch({ executablePath: chromePath, headless: true, args: ['--no-sandbox', '--disable-gpu'] });
    try {
        const page = await adminBrowser.newPage();
        await page.setViewport({ width: size.width, height: size.height, deviceScaleFactor: 1 });
        await page.emulateMediaFeatures([{ name: 'prefers-reduced-motion', value: 'reduce' }]);
        await login(page, 'owner', 'password');

        await page.goto(`${baseUrl}/attendance?month=2026-11`, { waitUntil: 'networkidle0' });
        await shot(page, `attendance-grid-${label}`);

        await page.goto(`${baseUrl}/attendance/take?date=2026-11-11`, { waitUntil: 'networkidle0' });
        await shot(page, `take-attendance-${label}`);

        await page.goto(`${baseUrl}/settings`, { waitUntil: 'networkidle0' });
        await shot(page, `school-days-${label}`);
    } finally {
        await adminBrowser.close();
    }

    const teacherBrowser = await puppeteer.launch({ executablePath: chromePath, headless: true, args: ['--no-sandbox', '--disable-gpu'] });
    try {
        const page = await teacherBrowser.newPage();
        await page.setViewport({ width: size.width, height: size.height, deviceScaleFactor: 1 });
        await page.emulateMediaFeatures([{ name: 'prefers-reduced-motion', value: 'reduce' }]);
        await login(page, 'teacher1', 'password');

        await page.goto(`${baseUrl}/teacher/attendance`, { waitUntil: 'networkidle0' });
        await shot(page, `teacher-attendance-${label}`);

        const takeHref = await page.evaluate(() => {
            const link = [...document.querySelectorAll('a[href]')].find((anchor) => /\/teacher\/attendance\/\d+\/take$/.test(anchor.getAttribute('href') ?? ''));
            return link?.getAttribute('href') ?? null;
        });

        if (takeHref) {
            await page.goto(new URL(takeHref, baseUrl).href, { waitUntil: 'networkidle0' });
            await shot(page, `teacher-take-attendance-${label}`);
        }
    } finally {
        await teacherBrowser.close();
    }
}

await capture({ width: 1440, height: 900 }, 'desktop');
await capture({ width: 390, height: 844 }, 'phone');

console.log(`Stage 3 screenshots saved to ${outputDir}`);
