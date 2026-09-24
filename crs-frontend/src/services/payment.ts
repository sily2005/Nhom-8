import api from './api.js'

export interface PaymentSetting {
  id?: number
  bank_code: string
  bank_name?: string
  account_number: string
  account_name: string
  syntax_prefix?: string
  template?: string
  is_active?: boolean
}

export async function fetchPaymentSettings(): Promise<PaymentSetting> {
  const response = await api.get('/payments/settings')
  return response.data?.data ?? response.data
}

export async function updatePaymentSettings(payload: Partial<PaymentSetting>): Promise<PaymentSetting> {
  const response = await api.put('/payments/settings', payload)
  return response.data?.data ?? response.data
}

