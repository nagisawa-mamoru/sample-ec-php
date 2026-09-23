<script setup>
import { ref, onMounted, computed } from 'vue'
import { useRoute, useRouter, RouterLink } from 'vue-router'
import { fetchProduct } from '../api/products'
import { useCartStore } from '../stores/cart'

const route = useRoute()
const router = useRouter()
const cart = useCartStore()

const product = ref(null)
const quantity = ref(1)
const loading = ref(true)
const errorMessage = ref('')

const stockClass = computed(() => {
  if (!product.value) return ''
  if (product.value.stock <= 0) return 'stock-badge stock-badge--out'
  if (product.value.stock <= 5) return 'stock-badge stock-badge--low'
  return 'stock-badge stock-badge--ok'
})

onMounted(async () => {
  try {
    product.value = await fetchProduct(route.params.id)
  } catch (e) {
    errorMessage.value = '商品が見つかりませんでした。'
  } finally {
    loading.value = false
  }
})

function decrement() {
  if (quantity.value > 1) quantity.value -= 1
}

function increment() {
  if (quantity.value < product.value.stock) quantity.value += 1
}

function addToCart() {
  cart.addItem(product.value, quantity.value)
  router.push('/cart')
}
</script>

<template>
  <div v-if="loading" class="state-block">
    <div class="spinner"></div>
    <span>読み込み中...</span>
  </div>

  <div v-else-if="errorMessage" class="alert alert-danger">{{ errorMessage }}</div>

  <div v-else-if="product">
    <RouterLink to="/products" class="back-link">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M19 12H5M12 19l-7-7 7-7" />
      </svg>
      商品一覧に戻る
    </RouterLink>

    <div class="surface-card" style="padding: 32px; max-width: 480px">
      <span class="product-card__category">{{ product.category }}</span>
      <h1 style="margin: 10px 0 4px; font-size: 1.5rem">{{ product.name }}</h1>
      <p v-if="product.discounted_price" class="product-card__price" style="font-size: 1.6rem">
        <span style="text-decoration: line-through; color: var(--color-text-muted); font-size: 0.7em; margin-right: 8px">
          ¥{{ Number(product.price).toLocaleString() }}
        </span>
        ¥{{ Number(product.discounted_price).toLocaleString() }}
      </p>
      <p v-else class="product-card__price" style="font-size: 1.6rem">
        ¥{{ Number(product.price).toLocaleString() }}
      </p>
      <span :class="stockClass">在庫 {{ product.stock }}</span>

      <div style="margin: 24px 0">
        <label class="field-label">数量</label>
        <div class="stepper">
          <button class="stepper__btn" type="button" :disabled="quantity <= 1" @click="decrement">−</button>
          <span class="stepper__value">{{ quantity }}</span>
          <button class="stepper__btn" type="button" :disabled="quantity >= product.stock" @click="increment">+</button>
        </div>
      </div>

      <button class="btn btn-primary btn-block" :disabled="product.stock <= 0" @click="addToCart">
        カートに追加
      </button>
    </div>
  </div>
</template>
