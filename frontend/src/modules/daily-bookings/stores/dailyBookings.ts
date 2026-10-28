import {defineStore} from 'pinia'
import {ref} from 'vue'
import {dailyBookingApi} from '../api'
import type {DailyBooking,DailyOptions,Paginated} from '../types'
export const useDailyBookingStore=defineStore('dailyBookings',()=>{
 const rows=ref<DailyBooking[]>([]),options=ref<DailyOptions|null>(null),page=ref(1),lastPage=ref(1),total=ref(0),loading=ref(false),error=ref('')
 async function load(params:Record<string,string|number|undefined>={}){loading.value=true;error.value='';try{const response=await dailyBookingApi.list(params) as Paginated<DailyBooking>;rows.value=response.data;page.value=response.current_page;lastPage.value=response.last_page;total.value=response.total}catch(e){error.value=e instanceof Error?e.message:'Unable to load bookings.';throw e}finally{loading.value=false}}
 async function loadOptions(){options.value=await dailyBookingApi.options()}
 return{rows,options,page,lastPage,total,loading,error,load,loadOptions}
})
