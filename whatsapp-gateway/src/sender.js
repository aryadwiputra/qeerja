export function createSender(client) {
    return {
        async send(phone, message) {
            const chatId = phone.includes('@c.us') ? phone : `${phone}@c.us`;
            await client.sendMessage(chatId, message);
        },
    };
}
