<script setup>
import { ref } from 'vue'
import { useRouter, RouterLink } from 'vue-router'
import { useCartStore } from '../stores/cart'
import { createOrder } from '../api/orders'

const cart = useCartStore()
const router = useRouter()

const customerId = ref(1)
const submitting = ref(false)
const errorMessage = ref('')

function decrement(item) {
  cart.updateQuantity(item.productId, item.quantity - 1 < 1 ? 1 : item.quantity - 1)
}

function increment(item) {
  cart.updateQuantity(item.productId, item.quantity + 1)
}

async function submitOrder() {
  if (cart.items.length === 0) {
    return
  }

  submitting.value = true
  errorMessage.value = ''

  try {
    const order = await createOrder({
      customer_id: customerId.value,
      items: cart.items.map((item) => ({
        product_id: item.productId,
        quantity: item.quantity,
      })),
    })
    cart.clear()
    router.push(`/orders/${order.id}`)
  } catch (e) {
    errorMessage.value =
      e.response?.data?.messages?.error ?? '注文の作成に失敗しました。在庫をご確認ください。'
  } finally {
    submitting.value = false
  }
}
</script>

<template>
  <div class="page-header">
    <h1>カート</h1>
    <p>内容をご確認のうえ、注文を確定してください。</p>
  </div>

  <div v-if="cart.items.length === 0" class="state-block">
    <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
      <path d="M3 3h2l.4 2M7 13h10l3-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m-9-1a1 1 0 1 0 0 2 1 1 0 0 0 0-2Zm10 0a1 1 0 1 0 0 2 1 1 0 0 0 0-2Z" />
    </svg>
    <span class="state-block__title">カートに商品がありません</span>
    <RouterLink to="/products" class="btn btn-primary btn-sm">商品一覧を見る</RouterLink>
  </div>

  <div v-else class="cart-layout">
    <div class="surface-card">
      <div v-for="item in cart.items" :key="item.productId" class="cart-line">
        <div class="cart-line__info">
          <p class="cart-line__name">{{ item.name }}</p>
          <span class="cart-line__price">¥{{ item.price.toLocaleString() }}</span>
        </div>
        <div class="stepper">
          <button class="stepper__btn" type="button" :disabled="item.quantity <= 1" @click="decrement(item)">−</button>
          <span class="stepper__value">{{ item.quantity }}</span>
          <button class="stepper__btn" type="button" @click="increment(item)">+</button>
        </div>
        <div class="cart-line__subtotal">¥{{ (item.price * item.quantity).toLocaleString() }}</div>
        <button class="btn-danger-ghost" title="削除" @click="cart.removeItem(item.productId)">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M3 6h18M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2m3 0-1 14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2L4 6h16Z" />
          </svg>
        </button>
      </div>
    </div>

    <div class="surface-card summary-card">
      <div class="summary-card__row">
        <span class="summary-card__label">合計金額</span>
        <span class="summary-card__total">¥{{ cart.totalAmount.toLocaleString() }}</span>
      </div>

      <label class="field-label" for="customerId">顧客ID</label>
      <input id="customerId" v-model.number="customerId" type="number" min="1" class="input" />
      <p class="field-hint">研修用の簡易実装のため、顧客IDを直接指定しています。</p>

      <div v-if="errorMessage" class="alert alert-danger" style="margin: 16px 0 0">
        {{ errorMessage }}
      </div>

      <button class="btn btn-primary btn-block" style="margin-top: 20px" :disabled="submitting" @click="submitOrder">
        {{ submitting ? '注文処理中...' : '注文する' }}
      </button>
    </div>
  </div>
</template>
