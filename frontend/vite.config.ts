import tailwindcss from '@tailwindcss/vite';
import react from '@vitejs/plugin-react';
import path from 'path';
import { fileURLToPath } from 'url';
import { defineConfig, loadEnv } from 'vite';

const currentDir = path.dirname(fileURLToPath(import.meta.url));

export default defineConfig(({ mode }) => {
  // Load environment variables from both the frontend directory and the project root
  const env = {
    ...loadEnv(mode, path.resolve(currentDir, '..'), ''),
    ...loadEnv(mode, currentDir, ''),
  };

  const telegramBotToken = env.VITE_TELEGRAM_BOT_TOKEN || env.TELEGRAM_BOT_TOKEN || '';
  const telegramChatId = env.VITE_TELEGRAM_CHAT_ID || env.TELEGRAM_CHAT_ID || '';

  return {
    plugins: [react(), tailwindcss()],
    envPrefix: ['VITE_', 'TELEGRAM_'],
    define: {
      'import.meta.env.VITE_TELEGRAM_BOT_TOKEN': JSON.stringify(telegramBotToken),
      'import.meta.env.VITE_TELEGRAM_CHAT_ID': JSON.stringify(telegramChatId),
    },
    resolve: {
      alias: {
        '@': path.resolve(currentDir, '.'),
      },
    },
    server: {
      // HMR is disabled in AI Studio via DISABLE_HMR env var.
      // Do not modify—file watching is disabled to prevent flickering during agent edits.
      hmr: process.env.DISABLE_HMR !== 'true',
      // Disable file watching when DISABLE_HMR is true to save CPU during agent edits.
      watch: process.env.DISABLE_HMR === 'true' ? null : {},
    },
  };
});
