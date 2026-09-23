<script setup>
import { RouterLink, RouterView } from 'vue-router'
import { useCartStore } from './stores/cart'

const cart = useCartStore()
</script>

<template>
  <div class="app-shell">
    <nav class="app-nav">
      <RouterLink class="app-nav__brand" to="/products">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M3 3h2l.4 2M7 13h10l3-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m-9-1a1 1 0 1 0 0 2 1 1 0 0 0 0-2Zm10 0a1 1 0 1 0 0 2 1 1 0 0 0 0-2Z" />
        </svg>
        EC管理システム
      </RouterLink>
      <div class="app-nav__links">
        <RouterLink class="app-nav__link" to="/products">商品一覧</RouterLink>
        <RouterLink class="app-nav__link" to="/orders">注文一覧</RouterLink>
        <RouterLink class="app-nav__link" to="/cart">
          カート
          <span v-if="cart.totalCount > 0" class="app-nav__badge">{{ cart.totalCount }}</span>
        </RouterLink>
      </div>
    </nav>
    <main class="app-main">
      <RouterView />
    </main>
    <footer class="app-footer">
      <span>© 2026 EC管理システム</span>
      <RouterLink class="app-footer__link" to="/admin">v1.0</RouterLink>
    </footer>
  </div>
</template>
