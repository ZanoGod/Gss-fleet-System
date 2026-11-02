export interface Assignment {
  id: number
  name: string
  start_date: string
  end_date: string
  status: string
  resource_type?: string
  vehicle?: string | null
  secondary_vehicle?: string | null
  driver?: string | null
  driver_id?: number | null
}
export interface ResourceRow {
  id: number
  [key: string]: unknown
  assignments?: Assignment[]
  maintenances?: { id: number; title: string; scheduled_date: string; status: string }[]
  insurance_policies?: { id: number }[]
}
export interface Field {
  key: string
  label: string
  type?: string
  required?: boolean
  max?: number
  min?: number
  maxNumber?: number
  step?: string
  options?: string[]
  default?: string | number
  placeholder?: string
}
export interface ResourceConfig {
  key: string
  title: string
  singular: string
  path: string
  endpoint: string
  icon: string
  description: string
  formHelp: string
  statusKey: string
  statuses: string[]
  fields: Field[]
  filters: Field[]
  columns: { key: string; label: string }[]
}
export const fleetConfig: ResourceConfig = {
  key: 'fleet',
  title: 'Fleet Management',
  singular: 'Vehicle',
  path: '/fleet',
  endpoint: '/api/cars',
  icon: 'bi-car-front',
  description: 'Manage company vehicles and booking assignments.',
  formHelp:
    'Stored status is manually managed. Maintenance and current assignments also restrict availability. Update assignment dates in bookings or monthly contracts.',
  statusKey: 'availability_status',
  statuses: ['Available', 'Assigned', 'Maintenance'],
  fields: [
    { key: 'car_type', label: 'Car Type', required: true, max: 100 },
    { key: 'plate_no', label: 'Plate Number', required: true, max: 50 },
    { key: 'model_name', label: 'Model / Year', required: true, max: 100 },
    {
      key: 'seat_capacity',
      label: 'Seat Capacity',
      type: 'number',
      min: 1,
      maxNumber: 255,
      required: true,
      default: 4,
    },
    { key: 'availability_status', label: 'Stored Status', required: true, default: 'Available' },
    { key: 'note', label: 'Notes', type: 'textarea' },
  ],
  filters: [
    { key: 'search', label: 'Search', placeholder: 'Plate, type, model, guest/company' },
    { key: 'status', label: 'Stored Status', options: [] },
    {
      key: 'month',
      label: 'Assignment Month',
      type: 'month',
      default: new Intl.DateTimeFormat('sv-SE', {
        timeZone: 'Asia/Rangoon',
        year: 'numeric',
        month: '2-digit',
      }).format(new Date()),
    },
  ],
  columns: [
    { key: 'plate_no', label: 'Plate Number' },
    { key: 'car_type', label: 'Car Type' },
    { key: 'model_name', label: 'Model / Year' },
    { key: 'seat_capacity', label: 'Seats' },
    { key: 'availability_status', label: 'Stored Status' },
    { key: 'effective_status', label: 'Availability Today' },
  ],
}
