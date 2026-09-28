import { defineStore } from 'pinia'
import { ref, computed, watch } from 'vue'

const STORAGE_KEY = 'cart-items'

function loadItems() {
  try {
    const raw = localStorage.getItem(STORAGE_KEY)
    const parsed = raw ? JSON.parse(raw) : []
    return Array.isArray(parsed) ? parsed : []
  } catch (e) {
    return []
  }
}

export const useCartStore = defineStore('cart', () => {
  const items = ref(loadItems()) // { productId, name, price, quantity }

  watch(
    items,
    (value) => {
      try {
        localStorage.setItem(STORAGE_KEY, JSON.stringify(value))
      } catch (e) {
        // localStorageが使用できない環境では永続化を諦める
      }
    },
    { deep: true },
  )

  const totalCount = computed(() => items.value.reduce((sum, item) => sum + item.quantity, 0))
  const totalAmount = computed(() =>
    items.value.reduce((sum, item) => sum + item.price * item.quantity, 0),
  )

  function addItem(product, quantity = 1) {
    const existing = items.value.find((item) => item.productId === product.id)

    if (existing) {
      existing.quantity += quantity
    } else {
      items.value.push({
        productId: product.id,
        name: product.name,
        price: Number(product.price),
        quantity,
      })
    }
  }

  function updateQuantity(productId, quantity) {
    const item = items.value.find((item) => item.productId === productId)

    if (item && quantity > 0) {
      item.quantity = quantity
    }
  }

  function removeItem(productId) {
    items.value = items.value.filter((item) => item.productId !== productId)
  }

  function clear() {
    items.value = []
  }

  return { items, totalCount, totalAmount, addItem, updateQuantity, removeItem, clear }
})
