import { Client, LocalAuth } from 'whatsapp-web.js';

export function createClient() {
    return new Client({
        authStrategy: new LocalAuth({
            clientId: 'qeerja',
            dataPath: './sessions',
        }),
        puppeteer: {
            headless: true,
            args: [
                '--no-sandbox',
                '--disable-setuid-sandbox',
                '--disable-dev-shm-usage',
                '--disable-accelerated-2d-canvas',
                '--no-first-run',
                '--no-zygote',
                '--single-process',
                '--disable-gpu',
            ],
        },
    });
}
