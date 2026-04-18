import { defineConfig } from 'vite';
import path from 'node:path';

export default defineConfig({
  base: '/',
  publicDir: false,
  server: {
    host: '127.0.0.1',
    port: 5173,
    strictPort: true,
    cors: true,
    origin: 'http://127.0.0.1:5173',
  },
  build: {
    outDir: 'public/build',
    emptyOutDir: false,
    manifest: true,
    rollupOptions: {
      input: {
        editorial: path.resolve(__dirname, 'assets/editorial/main.js'),
      },
      output: {
        entryFileNames: 'assets/[name]-[hash].js',
        chunkFileNames: 'assets/[name]-[hash].js',
        assetFileNames: 'assets/[name]-[hash][extname]',
      },
    },
  },
});
