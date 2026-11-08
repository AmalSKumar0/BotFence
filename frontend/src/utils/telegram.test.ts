import test from 'node:test';
import assert from 'node:assert/strict';
import { sendTelegramNotification } from './telegram';

test('sendTelegramNotification gracefully warns and returns false when credentials are missing', async () => {
  const result = await sendTelegramNotification({
    name: 'John Doe',
    email: 'john@example.com',
    wouldReview: true,
  });

  assert.equal(result.success, false);
  assert.match(result.error || '', /credentials missing/i);
});

test('sendTelegramNotification posts formatted payload to Telegram API when credentials exist', async () => {
  process.env.VITE_TELEGRAM_BOT_TOKEN = '123456:TEST_TOKEN';
  process.env.VITE_TELEGRAM_CHAT_ID = '987654321';

  let capturedUrl = '';
  let capturedBody: any = null;

  const originalFetch = globalThis.fetch;
  globalThis.fetch = (async (input: RequestInfo | URL, init?: RequestInit) => {
    capturedUrl = String(input);
    capturedBody = JSON.parse(init?.body as string);
    return {
      ok: true,
      status: 200,
      json: async () => ({ ok: true, result: {} }),
    } as Response;
  }) as typeof fetch;

  try {
    const result = await sendTelegramNotification({
      name: 'Amal S Kumar',
      email: 'amal@example.com',
      wouldReview: true,
      timestamp: '2026-11-08 10:00:00',
    });

    assert.equal(result.success, true);
    assert.equal(capturedUrl, 'https://api.telegram.org/bot123456:TEST_TOKEN/sendMessage');
    assert.equal(capturedBody.chat_id, '987654321');
    assert.equal(capturedBody.parse_mode, 'HTML');
    assert.ok(capturedBody.text.includes('Amal S Kumar'));
    assert.ok(capturedBody.text.includes('amal@example.com'));
    assert.ok(capturedBody.text.includes('BotFence.zip'));
  } finally {
    globalThis.fetch = originalFetch;
    delete process.env.VITE_TELEGRAM_BOT_TOKEN;
    delete process.env.VITE_TELEGRAM_CHAT_ID;
  }
});
