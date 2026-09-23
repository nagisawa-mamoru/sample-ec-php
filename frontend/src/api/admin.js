import apiClient from './client'

export function receiveStock(productId, quantity) {
  return apiClient
    .post(`/admin/products/${productId}/receive-stock`, { quantity })
    .then((res) => res.data)
}

export function updateDiscount(productId, discountPercentage) {
  return apiClient
    .patch(`/admin/products/${productId}/discount`, { discount_percentage: discountPercentage })
    .then((res) => res.data)
}
