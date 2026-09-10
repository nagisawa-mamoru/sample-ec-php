import { defineStore } from 'pinia'

export const useCartStore = defineStore('cart', {
  state: () => ({
    items: [], // { productId, name, price, quantity }
  }),

  getters: {
    totalCount: (state) => state.items.reduce((sum, item) => sum + item.quantity, 0),
    totalAmount: (state) => state.items.reduce((sum, item) => sum + item.price * item.quantity, 0),
  },

  actions: {
    addItem(product, quantity = 1) {
      const existing = this.items.find((item) => item.productId === product.id)

      if (existing) {
        existing.quantity += quantity
      } else {
        this.items.push({
          productId: product.id,
          name: product.name,
          price: Number(product.price),
          quantity,
        })
      }
    },

    updateQuantity(productId, quantity) {
      const item = this.items.find((item) => item.productId === productId)

      if (item && quantity > 0) {
        item.quantity = quantity
      }
    },

    removeItem(productId) {
      this.items = this.items.filter((item) => item.productId !== productId)
    },

    clear() {
      this.items = []
    },
  },
})
