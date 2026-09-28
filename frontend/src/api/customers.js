import apiClient from './client'

export function fetchCustomers() {
  return apiClient.get('/customers').then((res) => res.data)
}
