import apiClient from './client'

export function fetchOrders() {
  return apiClient.get('/orders').then((res) => res.data)
}

export function fetchOrder(id) {
  return apiClient.get(`/orders/${id}`).then((res) => res.data)
}

export function createOrder(payload) {
  return apiClient.post('/orders', payload).then((res) => res.data)
}
