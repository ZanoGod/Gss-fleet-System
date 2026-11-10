import type { ResourceConfig } from '@/components/common/resourceTypes'
export const driverConfig: ResourceConfig & { optionsEndpoint: string } = {
  title: 'Drivers',
  singular: 'Driver',
  path: '/drivers',
  endpoint: '/api/drivers',
  summaryEndpoint: '/api/drivers/summary',
  optionsEndpoint: '/api/drivers/options',
  icon: 'bi-person-vcard',
  description: 'Maintain driver availability and review trip assignments.',
  formHelp:
    'Booking dates and customer details stay in Daily Bookings or Monthly Contracts. Stored status and availability for particular dates are separate. Existing license data is preserved.',
  statusKey: 'driver_status',
  statuses: ['Available', 'Assigned', 'Leave', 'Inactive'],
  fields: [
    { key: 'full_name', label: 'Driver Name', required: true, max: 150 },
    { key: 'phone_number', label: 'Phone Number', type: 'tel', required: true, max: 30 },
    { key: 'driver_status', label: 'Stored Status', required: true, default: 'Available' },
    { key: 'note', label: 'Note', type: 'textarea' },
  ],
  filters: [
    { key: 'search', label: 'Search', placeholder: 'Name, phone, notes, guest/company' },
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
    { key: 'full_name', label: 'Driver Name' },
    { key: 'phone_number', label: 'Phone Number' },
    { key: 'driver_status', label: 'Stored Status' },
  ],
}
