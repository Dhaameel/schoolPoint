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
    await page.screenshot({ path: path.join(outputDir, `stage2-${name}.png`), fullPage: true });
}

async function captureViewport(size, label) {
    const browser = await puppeteer.launch({
        executablePath: chromePath,
        headless: true,
        args: ['--no-sandbox', '--disable-gpu'],
    });

    try {
        const page = await browser.newPage();
        await page.setViewport({ width: size.width, height: size.height, deviceScaleFactor: 1 });
        await page.emulateMediaFeatures([{ name: 'prefers-reduced-motion', value: 'reduce' }]);
        await login(page, 'owner', 'password');

        for (const [name, url] of Object.entries({
            'subjects-list': '/subjects',
            'teachers-list': '/teachers',
            'classes-list': '/classes',
            'students-list': '/students',
        })) {
            await page.goto(`${baseUrl}${url}`, { waitUntil: 'networkidle0' });
            await shot(page, `${name}-${label}`);
        }

        await page.goto(`${baseUrl}/students`, { waitUntil: 'networkidle0' });
        const profileHref = await page.evaluate(() => {
            const links = [...document.querySelectorAll('a[href]')];
            const found = links.find((link) => /\/students\/\d+$/.test(link.getAttribute('href') ?? ''));
            return found?.getAttribute('href') ?? null;
        });

        if (profileHref) {
            await page.goto(new URL(profileHref, baseUrl).href, { waitUntil: 'networkidle0' });
            await shot(page, `student-profile-${label}`);
        }
    } finally {
        await browser.close();
    }

    const teacherBrowser = await puppeteer.launch({
        executablePath: chromePath,
        headless: true,
        args: ['--no-sandbox', '--disable-gpu'],
    });

    try {
        const page = await teacherBrowser.newPage();
        await page.setViewport({ width: size.width, height: size.height, deviceScaleFactor: 1 });
        await page.emulateMediaFeatures([{ name: 'prefers-reduced-motion', value: 'reduce' }]);
        await login(page, 'teacher1', 'password');
        await page.goto(`${baseUrl}/my-classes`, { waitUntil: 'networkidle0' });
        await shot(page, `teacher-my-classes-${label}`);
    } finally {
        await teacherBrowser.close();
    }
}

await captureViewport({ width: 1440, height: 900 }, 'desktop');
await captureViewport({ width: 390, height: 844 }, 'phone');

console.log(`Stage 2 screenshots saved to ${outputDir}`);
