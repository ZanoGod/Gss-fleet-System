import api from '@/services/api'
import type {
  FinanceEntry,
  FinanceInvoice,
  FinanceOptions,
  FinancePageData,
  FinancePayment,
} from './types'

export const financeApi = {
  async options(): Promise<FinanceOptions> {
    return (await api.get<{ data: FinanceOptions }>('/api/finance/options')).data.data
  },
  async dashboard(params: Record<string, string> = {}) {
    return (await api.get('/api/finance/dashboard', { params })).data.data
  },
  async entries(params: Record<string, string | number>) {
    return (await api.get<FinancePageData<FinanceEntry>>('/api/finance/entries', { params })).data
  },
  async invoices(params: Record<string, string | number>) {
    return (await api.get<FinancePageData<FinanceInvoice>>('/api/finance/invoices', { params }))
      .data
  },
  async report(params: Record<string, string>) {
    return (await api.get('/api/finance/report', { params })).data.data
  },
  async payments(invoiceId: number): Promise<FinancePayment[]> {
    return (
      await api.get<{ data: FinancePayment[] }>(`/api/finance/invoices/${invoiceId}/payments`)
    ).data.data
  },
  async saveEntry(input: Record<string, any>, id?: number) {
    return api.request({
      method: id ? 'put' : 'post',
      url: id ? `/api/finance/entries/${id}` : '/api/finance/entries',
      data: input,
    })
  },
  async deleteEntry(id: number) {
    return api.delete(`/api/finance/entries/${id}`)
  },
  async saveInvoice(input: Record<string, any>, id?: number) {
    return api.request({
      method: id ? 'put' : 'post',
      url: id ? `/api/finance/invoices/${id}` : '/api/finance/invoices',
      data: input,
    })
  },
  async deleteInvoice(id: number) {
    return api.delete(`/api/finance/invoices/${id}`)
  },
  async savePayment(invoiceId: number, input: Record<string, any>, paymentId?: number) {
    const url = `/api/finance/invoices/${invoiceId}/payments${paymentId ? `/${paymentId}` : ''}`
    return api.request({ method: paymentId ? 'put' : 'post', url, data: input })
  },
  async deletePayment(invoiceId: number, paymentId: number) {
    return api.delete(`/api/finance/invoices/${invoiceId}/payments/${paymentId}`)
  },
  exportUrl(params: Record<string, string>) {
    const query = new URLSearchParams(params).toString()
    return `/api/finance/export.csv${query ? `?${query}` : ''}`
  },
}
