import apiClient from './client'

export function fetchProducts() {
  return apiClient.get('/products').then((res) => res.data)
}

export function fetchProduct(id) {
  return apiClient.get(`/products/${id}`).then((res) => res.data)
}
