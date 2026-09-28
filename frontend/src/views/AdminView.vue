<script setup>
import { ref, onMounted, reactive } from 'vue'
import { fetchProducts } from '../api/products'
import { fetchCustomers } from '../api/customers'
import { receiveStock, updateDiscount, createCustomer } from '../api/admin'

const products = ref([])
const loading = ref(true)
const errorMessage = ref('')

const stockForm = reactive({})
const discountForm = reactive({})
const savingStockId = ref(null)
const savingDiscountId = ref(null)
const feedback = ref('')

const customers = ref([])
const customersLoading = ref(true)
const customersErrorMessage = ref('')
const customerForm = reactive({ name: '', email: '' })
const creatingCustomer = ref(false)
const customerFeedback = ref('')
const customerErrors = ref({})

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

async function loadCustomers() {
  customersLoading.value = true
  customersErrorMessage.value = ''

  try {
    customers.value = await fetchCustomers()
  } catch (e) {
    customersErrorMessage.value = '顧客一覧の取得に失敗しました。'
  } finally {
    customersLoading.value = false
  }
}

onMounted(loadProducts)
onMounted(loadCustomers)

async function submitCustomer() {
  creatingCustomer.value = true
  customerFeedback.value = ''
  customerErrors.value = {}

  try {
    const created = await createCustomer({
      name: customerForm.name,
      email: customerForm.email,
    })
    customers.value.push(created)
    customerForm.name = ''
    customerForm.email = ''
    customerFeedback.value = `${created.name} を顧客として登録しました。`
  } catch (e) {
    customerErrors.value = e.response?.data?.messages ?? {}
    if (Object.keys(customerErrors.value).length === 0) {
      customerFeedback.value = '顧客の登録に失敗しました。'
    }
  } finally {
    creatingCustomer.value = false
  }
}

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

  <div class="page-header" style="margin-top: 40px">
    <h1>顧客管理</h1>
    <p>顧客の新規登録と一覧確認を行います。</p>
  </div>

  <div v-if="customersErrorMessage" class="alert alert-danger">{{ customersErrorMessage }}</div>

  <div v-else class="cart-layout">
    <div class="surface-card" style="overflow: hidden">
      <table class="data-table">
        <thead>
          <tr>
            <th>顧客名</th>
            <th>メールアドレス</th>
          </tr>
        </thead>
        <tbody v-if="!customersLoading">
          <tr v-for="customer in customers" :key="customer.id">
            <td>{{ customer.name }}</td>
            <td>{{ customer.email }}</td>
          </tr>
        </tbody>
      </table>
      <p v-if="customersLoading" class="state-block">読み込み中...</p>
      <p v-else-if="customers.length === 0" class="state-block">顧客がまだ登録されていません。</p>
    </div>

    <div class="surface-card summary-card">
      <div v-if="customerFeedback" class="alert alert-success" style="margin-bottom: 16px">
        {{ customerFeedback }}
      </div>

      <label class="field-label" for="newCustomerName">顧客名</label>
      <input id="newCustomerName" v-model="customerForm.name" type="text" class="input" />
      <p v-if="customerErrors.name" class="field-hint" style="color: var(--color-danger)">
        {{ customerErrors.name }}
      </p>

      <label class="field-label" for="newCustomerEmail" style="margin-top: 12px">メールアドレス</label>
      <input id="newCustomerEmail" v-model="customerForm.email" type="email" class="input" />
      <p v-if="customerErrors.email" class="field-hint" style="color: var(--color-danger)">
        {{ customerErrors.email }}
      </p>

      <button
        class="btn btn-primary btn-block"
        style="margin-top: 20px"
        :disabled="creatingCustomer || !customerForm.name || !customerForm.email"
        @click="submitCustomer"
      >
        {{ creatingCustomer ? '登録中...' : '顧客を追加' }}
      </button>
    </div>
  </div>
</template>
