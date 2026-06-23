import 'dotenv/config';
import { startServer } from './src/server.js';
import { createClient } from './src/client.js';

const PORT = parseInt(process.env.GATEWAY_PORT || '3001', 10);
const client = createClient();

client.on('ready', () => {
    console.log('[gateway] client ready');
});

client.on('disconnected', (reason) => {
    console.warn('[gateway] disconnected:', reason);
});

client.on('auth_failure', (msg) => {
    console.error('[gateway] auth failure:', msg);
});

startServer(client, PORT);
