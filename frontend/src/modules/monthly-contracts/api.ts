import api from'@/services/api';import type{AxiosResponse}from'axios';import type{ContractInput,ContractOptions,ContractPage,MonthlyContract}from'./types'
const unwrap=<T>(r:AxiosResponse<{data:T}>):T=>r.data.data
export const monthlyContractApi={
 async list(params:Record<string,string|number|undefined>={}){return (await api.get<ContractPage>('/api/monthly-contracts',{params})).data},
 async get(id:number){return unwrap(await api.get<{data:MonthlyContract}>(`/api/monthly-contracts/${id}`))},
 async options(){return unwrap(await api.get<{data:ContractOptions}>('/api/monthly-contracts/options'))},
 async summary(params:Record<string,string|undefined>={}){return unwrap(await api.get<{data:{total:number;statuses:Record<string,number>}}>('/api/monthly-contracts/summary',{params}))},
 async save(input:ContractInput,id?:number){return unwrap(await api.request<{data:MonthlyContract}>({method:id?'put':'post',url:id?`/api/monthly-contracts/${id}`:'/api/monthly-contracts',data:input}))},
 async renew(id:number,input:ContractInput){return unwrap(await api.post<{data:MonthlyContract}>(`/api/monthly-contracts/${id}/renew`,input))},
 async remove(id:number){await api.delete(`/api/monthly-contracts/${id}`)},
}
