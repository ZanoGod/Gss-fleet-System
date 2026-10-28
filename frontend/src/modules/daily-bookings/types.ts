export interface DailyVehicle { id:number; plate_no:string; model_name:string; availability_status:string }
export interface DailyDriver { id:number; full_name:string; driver_status:string }
export interface DailyOperator { id:number; full_name:string; phone_number?:string|null }
export interface DailyBooking { id:number; guest_company_name:string; car_id:number|null; car?:DailyVehicle|null; custom_car_name:string|null; secondary_car_id:number|null; secondary_car?:DailyVehicle|null; driver_id:number|null; driver?:DailyDriver|null; custom_driver_name:string|null; operator_id:number|null; operator_name:string; operator?:DailyOperator|null; even_odd:'Even'|'Odd'|null; start_date:string; end_date:string; status:'Pending'|'Confirm'|'Completed'|'Cancelled'; remark:string|null }
export interface DailyBookingInput { guest_company_name:string; car_id:number|null; custom_car_name:string; secondary_car_id:number|null; driver_id:number|null; custom_driver_name:string; operator_id:number; even_odd:'Even'|'Odd'; start_date:string; end_date:string; status:DailyBooking['status']; remark:string }
export interface DailyOptions { cars:DailyVehicle[]; drivers:DailyDriver[]; operators:DailyOperator[]; statuses:DailyBooking['status'][] }
export interface Paginated<T> { data:T[]; current_page:number; last_page:number; total:number; links?:Array<{url:string|null;label:string;active:boolean}> }
