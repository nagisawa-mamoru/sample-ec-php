<script setup>
import { ref, onMounted } from 'vue'
import { useRoute, RouterLink } from 'vue-router'
import { fetchOrder } from '../api/orders'

const route = useRoute()
const order = ref(null)
const loading = ref(true)
const errorMessage = ref('')

onMounted(async () => {
  try {
    order.value = await fetchOrder(route.params.id)
  } catch (e) {
    errorMessage.value = '注文が見つかりませんでした。'
  } finally {
    loading.value = false
  }
})
</script>

<template>
  <div v-if="loading" class="state-block">
    <div class="spinner"></div>
    <span>読み込み中...</span>
  </div>

  <div v-else-if="errorMessage" class="alert alert-danger">{{ errorMessage }}</div>

  <div v-else-if="order">
    <RouterLink to="/orders" class="back-link">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M19 12H5M12 19l-7-7 7-7" />
      </svg>
      注文一覧に戻る
    </RouterLink>

    <div class="page-header">
      <h1>注文詳細 #{{ order.id }}</h1>
      <p>
        <span class="status-badge">{{ order.status }}</span>
        <span style="margin-left: 10px; color: var(--color-text-muted)">{{ order.created_at }}</span>
      </p>
    </div>

    <div class="surface-card" style="overflow: hidden">
      <table class="data-table">
        <thead>
          <tr>
            <th>商品名</th>
            <th>単価</th>
            <th>数量</th>
            <th>小計</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="item in order.items" :key="item.id">
            <td>{{ item.product_name }}</td>
            <td>¥{{ Number(item.unit_price).toLocaleString() }}</td>
            <td>{{ item.quantity }}</td>
            <td>¥{{ (Number(item.unit_price) * item.quantity).toLocaleString() }}</td>
          </tr>
        </tbody>
      </table>
    </div>

    <div class="summary-card__row" style="max-width: 320px; margin-top: 20px; margin-left: auto">
      <span class="summary-card__label">合計金額</span>
      <span class="summary-card__total">¥{{ Number(order.total_amount).toLocaleString() }}</span>
    </div>
  </div>
</template>
