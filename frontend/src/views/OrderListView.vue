<script setup>
import { ref, onMounted } from 'vue'
import { fetchOrders } from '../api/orders'

const orders = ref([])
const loading = ref(true)
const errorMessage = ref('')

onMounted(async () => {
  try {
    orders.value = await fetchOrders()
  } catch (e) {
    errorMessage.value = '注文一覧の取得に失敗しました。'
  } finally {
    loading.value = false
  }
})
</script>

<template>
  <div class="page-header">
    <h1>注文一覧</h1>
    <p>これまでに作成された注文を確認できます。</p>
  </div>

  <div v-if="loading" class="state-block">
    <div class="spinner"></div>
    <span>読み込み中...</span>
  </div>

  <div v-else-if="errorMessage" class="alert alert-danger">{{ errorMessage }}</div>

  <div v-else-if="orders.length === 0" class="state-block">
    <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
      <path d="M9 12h6m-6 4h6m-9 5h12a2 2 0 0 0 2-2V7l-5-5H6a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2Z" />
    </svg>
    <span class="state-block__title">注文はまだありません</span>
  </div>

  <div v-else class="surface-card" style="overflow: hidden">
    <table class="data-table">
      <thead>
        <tr>
          <th>注文ID</th>
          <th>顧客名</th>
          <th>ステータス</th>
          <th>合計金額</th>
          <th>注文日時</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="order in orders" :key="order.id">
          <td>#{{ order.id }}</td>
          <td>{{ order.customer_name }}</td>
          <td><span class="status-badge">{{ order.status }}</span></td>
          <td>¥{{ Number(order.total_amount).toLocaleString() }}</td>
          <td>{{ order.created_at }}</td>
          <td>
            <router-link :to="`/orders/${order.id}`" class="btn btn-ghost btn-sm">
              詳細
            </router-link>
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</template>
