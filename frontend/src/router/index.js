import { createRouter, createWebHistory } from 'vue-router'
import ProductListView from '../views/ProductListView.vue'
import ProductDetailView from '../views/ProductDetailView.vue'
import CartView from '../views/CartView.vue'
import OrderListView from '../views/OrderListView.vue'
import OrderDetailView from '../views/OrderDetailView.vue'

const routes = [
  { path: '/', redirect: '/products' },
  { path: '/products', name: 'products', component: ProductListView },
  { path: '/products/:id', name: 'product-detail', component: ProductDetailView, props: true },
  { path: '/cart', name: 'cart', component: CartView },
  { path: '/orders', name: 'orders', component: OrderListView },
  { path: '/orders/:id', name: 'order-detail', component: OrderDetailView, props: true },
]

const router = createRouter({
  history: createWebHistory(),
  routes,
})

export default router
