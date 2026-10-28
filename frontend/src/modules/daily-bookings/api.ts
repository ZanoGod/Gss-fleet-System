import type { AxiosResponse } from 'axios'
import api from '@/services/api'
import type { DailyBooking, DailyBookingInput, DailyOptions, Paginated } from './types'
const unwrap=<T>(response:AxiosResponse<{data:T}>):T=>response.data.data
export const dailyBookingApi={
  async list(params:Record<string,string|number|undefined>={}){const response=await api.get<Paginated<DailyBooking>>('/api/daily-bookings',{params});return response.data},
  async get(id:number){return unwrap(await api.get<{data:DailyBooking}>(`/api/daily-bookings/${id}`))},
  async options(){return unwrap(await api.get<{data:DailyOptions}>('/api/daily-bookings/options'))},
  async save(input:DailyBookingInput,id?:number){return unwrap(await api.request<{data:DailyBooking}>({method:id?'put':'post',url:id?`/api/daily-bookings/${id}`:'/api/daily-bookings',data:input}))},
  async remove(id:number){await api.delete(`/api/daily-bookings/${id}`)},
}
