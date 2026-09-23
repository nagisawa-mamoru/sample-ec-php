<script setup>
import { ref, onMounted } from 'vue'
import { fetchProducts } from '../api/products'
import { useCartStore } from '../stores/cart'

const products = ref([])
const loading = ref(true)
const errorMessage = ref('')
const cart = useCartStore()

onMounted(async () => {
  try {
    products.value = await fetchProducts()
  } catch (e) {
    errorMessage.value = '商品一覧の取得に失敗しました。'
  } finally {
    loading.value = false
  }
})

function stockClass(stock) {
  if (stock <= 0) return 'stock-badge stock-badge--out'
  if (stock <= 5) return 'stock-badge stock-badge--low'
  return 'stock-badge stock-badge--ok'
}

function addToCart(product) {
  cart.addItem(product, 1)
}
</script>

<template>
  <div class="page-header">
    <h1>商品一覧</h1>
    <p>在庫状況を確認しながら、カートに商品を追加できます。</p>
  </div>

  <div v-if="loading" class="state-block">
    <div class="spinner"></div>
    <span>読み込み中...</span>
  </div>

  <div v-else-if="errorMessage" class="alert alert-danger">{{ errorMessage }}</div>

  <div v-else class="product-grid">
    <div v-for="product in products" :key="product.id" class="product-card">
      <span class="product-card__category">{{ product.category }}</span>
      <h2 class="product-card__name">{{ product.name }}</h2>
      <p v-if="product.discounted_price" class="product-card__price">
        <span style="text-decoration: line-through; color: var(--color-text-muted); font-size: 0.85em; margin-right: 6px">
          ¥{{ Number(product.price).toLocaleString() }}
        </span>
        ¥{{ Number(product.discounted_price).toLocaleString() }}
      </p>
      <p v-else class="product-card__price">¥{{ Number(product.price).toLocaleString() }}</p>
      <span :class="stockClass(product.stock)">在庫 {{ product.stock }}</span>

      <div class="product-card__footer">
        <router-link :to="`/products/${product.id}`" class="btn btn-ghost btn-sm">
          詳細
        </router-link>
        <button
          class="btn btn-primary btn-sm"
          :disabled="product.stock <= 0"
          @click="addToCart(product)"
        >
          カートに追加
        </button>
      </div>
    </div>
  </div>
</template>
