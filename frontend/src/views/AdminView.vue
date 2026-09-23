<script setup>
import { ref, onMounted, reactive } from 'vue'
import { fetchProducts } from '../api/products'
import { receiveStock, updateDiscount } from '../api/admin'

const products = ref([])
const loading = ref(true)
const errorMessage = ref('')

const stockForm = reactive({})
const discountForm = reactive({})
const savingStockId = ref(null)
const savingDiscountId = ref(null)
const feedback = ref('')

async function loadProducts() {
  loading.value = true
  errorMessage.value = ''

  try {
    products.value = await fetchProducts()
    products.value.forEach((product) => {
      stockForm[product.id] = 1
      discountForm[product.id] = product.discount_percentage ?? ''
    })
  } catch (e) {
    errorMessage.value = '商品一覧の取得に失敗しました。'
  } finally {
    loading.value = false
  }
}

onMounted(loadProducts)

async function submitReceiveStock(product) {
  const quantity = Number(stockForm[product.id])

  if (!quantity || quantity <= 0) {
    return
  }

  savingStockId.value = product.id
  feedback.value = ''

  try {
    const updated = await receiveStock(product.id, quantity)
    Object.assign(product, updated)
    stockForm[product.id] = 1
    feedback.value = `${product.name} の在庫を ${quantity} 個 入荷登録しました。`
  } catch (e) {
    feedback.value = '在庫の入荷登録に失敗しました。'
  } finally {
    savingStockId.value = null
  }
}

async function submitDiscount(product) {
  const raw = discountForm[product.id]
  const discountPercentage = raw === '' || raw === null ? null : Number(raw)

  savingDiscountId.value = product.id
  feedback.value = ''

  try {
    const updated = await updateDiscount(product.id, discountPercentage)
    Object.assign(product, updated)
    feedback.value = `${product.name} の割引設定を更新しました。`
  } catch (e) {
    feedback.value = '割引設定の更新に失敗しました。'
  } finally {
    savingDiscountId.value = null
  }
}
</script>

<template>
  <div class="page-header">
    <h1>管理画面</h1>
    <p>在庫の仕入れ登録と、商品ごとの割引設定を行います。</p>
  </div>

  <div v-if="loading" class="state-block">
    <div class="spinner"></div>
    <span>読み込み中...</span>
  </div>

  <div v-else-if="errorMessage" class="alert alert-danger">{{ errorMessage }}</div>

  <div v-else>
    <div v-if="feedback" class="alert alert-success" style="margin-bottom: 16px">{{ feedback }}</div>

    <div class="surface-card" style="overflow: hidden">
      <table class="data-table">
        <thead>
          <tr>
            <th>商品名</th>
            <th>現在の在庫</th>
            <th>在庫回転率</th>
            <th>仕入れ数量</th>
            <th></th>
            <th>通常価格</th>
            <th>割引率(%)</th>
            <th>割引後価格</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="product in products" :key="product.id">
            <td>{{ product.name }}</td>
            <td>{{ product.stock }}</td>
            <td>{{ product.stock_turnover_rate }}%</td>
            <td>
              <input
                v-model.number="stockForm[product.id]"
                type="number"
                min="1"
                class="input"
                style="width: 90px"
              />
            </td>
            <td>
              <button
                class="btn btn-primary btn-sm"
                :disabled="savingStockId === product.id"
                @click="submitReceiveStock(product)"
              >
                入荷登録
              </button>
            </td>
            <td>¥{{ Number(product.price).toLocaleString() }}</td>
            <td>
              <input
                v-model="discountForm[product.id]"
                type="number"
                min="0"
                max="100"
                step="0.01"
                class="input"
                style="width: 90px"
                placeholder="未設定"
              />
            </td>
            <td>
              <span v-if="product.discounted_price">¥{{ Number(product.discounted_price).toLocaleString() }}</span>
              <span v-else style="color: var(--color-text-muted)">-</span>
            </td>
            <td>
              <button
                class="btn btn-ghost btn-sm"
                :disabled="savingDiscountId === product.id"
                @click="submitDiscount(product)"
              >
                更新
              </button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>
