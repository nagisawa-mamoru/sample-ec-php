import vue from '@vitejs/plugin-vue'
import { defineConfig } from 'vite'

// https://vite.dev/config/
export default defineConfig({
  plugins: [vue()],
  server: {
    watch: {
      // Docker(特にWindowsのbind mount)ではファイル変更イベントが
      // 届かないことがあるため、ポーリングで確実に検知する。
      usePolling: true,
    },
  },
  test: {
    environment: 'jsdom',
    globals: true,
  },
})
