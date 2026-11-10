import type { ResourceConfig } from '@/components/common/resourceTypes'
export const fleetConfig: ResourceConfig = {
  title: 'Fleet List',
  singular: 'Vehicle',
  path: '/fleet',
  endpoint: '/api/cars',
  summaryEndpoint: '/api/cars/summary',
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
