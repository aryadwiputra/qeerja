import express from 'express';
import QR from 'qrcode';
import { createSender } from './sender.js';

let currentQr = null;

export function startServer(client, port) {
    const app = express();

    app.use(express.json());

    client.on('qr', async (qr) => {
        currentQr = qr;
        const dataUri = await QR.toDataURL(qr);
        console.log('[gateway] QR refreshed');
        // expose via status endpoint
        app.set('qrDataUri', dataUri);
        app.set('qrTerminal', qr);
    });

    client.on('ready', () => {
        currentQr = null;
        app.set('ready', true);
    });

    client.on('disconnected', () => {
        app.set('ready', false);
        currentQr = null;
    });

    // GET /status
    app.get('/status', (_req, res) => {
        const ready = app.get('ready') === true;
        const qr = ready ? null : app.get('qrDataUri');

        res.json({
            ready,
            qr,
            qr_terminal: ready ? null : app.get('qrTerminal'),
        });
    });

    // POST /send
    app.post('/send', async (req, res) => {
        if (app.get('ready') !== true) {
            return res.status(503).json({ error: 'client not ready' });
        }

        const { phone, message } = req.body;

        if (!phone || !message) {
            return res.status(400).json({ error: 'phone and message required' });
        }

        try {
            const sender = createSender(client);
            await sender.send(phone, message);
            res.json({ ok: true });
        } catch (err) {
            console.error('[gateway] send error:', err);
            res.status(500).json({ error: err.message });
        }
    });

    // DELETE /session
    app.delete('/session', async (_req, res) => {
        try {
            await client.destroy();
            app.set('ready', false);
            currentQr = null;
            res.json({ ok: true });
        } catch (err) {
            res.status(500).json({ error: err.message });
        }
    });

    client.initialize().catch((err) => {
        console.error('[gateway] init error:', err);
    });

    app.listen(port, () => {
        console.log(`[gateway] listening on port ${port}`);
    });
}
