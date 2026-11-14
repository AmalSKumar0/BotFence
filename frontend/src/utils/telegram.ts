/**
 * Telegram notification dispatcher for Vighnaghna plugin downloads.
 * Uses credentials configured in .env:
 * - VITE_TELEGRAM_BOT_TOKEN or TELEGRAM_BOT_TOKEN
 * - VITE_TELEGRAM_CHAT_ID or TELEGRAM_CHAT_ID
 */

export interface DownloadSubmissionData {
  name: string;
  email: string;
  wouldReview: boolean;
  projectDescription?: string;
  timestamp?: string;
}

export interface TelegramDispatchResult {
  success: boolean;
  error?: string;
}

function escapeHtml(text: string): string {
  return (text || '')
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;');
}

export async function sendTelegramNotification(
  data: DownloadSubmissionData
): Promise<TelegramDispatchResult> {
  const metaEnv =
    typeof import.meta !== 'undefined' && (import.meta as unknown as { env?: Record<string, string | undefined> })?.env
      ? (import.meta as unknown as { env: Record<string, string | undefined> }).env
      : {};
  const procEnv = typeof process !== 'undefined' && process.env ? process.env : {};

  const botToken =
    metaEnv.VITE_TELEGRAM_BOT_TOKEN ||
    metaEnv.TELEGRAM_BOT_TOKEN ||
    procEnv.VITE_TELEGRAM_BOT_TOKEN ||
    procEnv.TELEGRAM_BOT_TOKEN;

  const chatId =
    metaEnv.VITE_TELEGRAM_CHAT_ID ||
    metaEnv.TELEGRAM_CHAT_ID ||
    procEnv.VITE_TELEGRAM_CHAT_ID ||
    procEnv.TELEGRAM_CHAT_ID;

  if (!botToken || !chatId) {
    const warning =
      '[Vighnaghna] Telegram credentials missing in .env (VITE_TELEGRAM_BOT_TOKEN and VITE_TELEGRAM_CHAT_ID).';
    console.warn(warning);
    return { success: false, error: warning };
  }

  const formattedTime = data.timestamp || new Date().toLocaleString();

  const projectDescLine = data.projectDescription?.trim()
    ? `\n📝 <b>Project / Notes:</b> ${escapeHtml(data.projectDescription.trim())}`
    : '';

  const text = `
🛡️ <b>New Vighnaghna Plugin Download</b>
━━━━━━━━━━━━━━━━━━━━━━━━━━
👤 <b>Name / Agency:</b> ${escapeHtml(data.name)}
📧 <b>Email:</b> ${escapeHtml(data.email)}
📬 <b>Updates & Release Notes:</b> ${data.wouldReview ? '✅ Subscribed' : '❌ Opted out'}${projectDescLine}
🕒 <b>Timestamp:</b> ${escapeHtml(formattedTime)}
📦 <b>Package:</b> vighnaghna.zip (WordPress Plugin v1.0.0)
`.trim();

  try {
    const response = await fetch(`https://api.telegram.org/bot${botToken}/sendMessage`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({
        chat_id: chatId,
        text,
        parse_mode: 'HTML',
      }),
    });

    const result = await response.json();
    if (!response.ok || !result.ok) {
      console.error(
        '[Vighnaghna] Telegram API error:',
        result?.description || response.statusText
      );
      return {
        success: false,
        error: result?.description || `HTTP ${response.status}`,
      };
    }

    return { success: true };
  } catch (err: unknown) {
    const errorMessage = err instanceof Error ? err.message : 'Unknown network error';
    console.error('[Vighnaghna] Failed to send Telegram notification:', errorMessage);
    return { success: false, error: errorMessage };
  }
}
