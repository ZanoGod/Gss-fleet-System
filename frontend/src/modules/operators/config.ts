import type { ResourceConfig } from '@/components/common/resourceTypes'
const statuses = ['Active', 'Inactive']

export const operatorConfig: ResourceConfig = {
  title: 'Operators',
  singular: 'Operator',
  path: '/operators',
  endpoint: '/api/operators',
  summaryEndpoint: '/api/operators/summary',
  icon: 'bi-headset',
  description: 'Manage the operator directory used by daily bookings and monthly contracts.',
  formHelp:
    'Renaming synchronizes linked daily booking operator names, as in the legacy system. Monthly contract snapshots are preserved.',
  statusKey: 'operator_status',
  statuses,
  fields: [
    { key: 'full_name', label: 'Operator Name', required: true, max: 150 },
    { key: 'phone_number', label: 'Phone Number', type: 'tel', max: 30 },
    {
      key: 'operator_status',
      options: statuses,
      label: 'Status',
      required: true,
      default: 'Active',
    },
    { key: 'note', label: 'Notes', type: 'textarea' },
  ],
  filters: [
    { key: 'search', label: 'Search', placeholder: 'Name, phone, notes' },
    { key: 'status', label: 'Status', options: statuses },
  ],
  columns: [
    { key: 'full_name', label: 'Operator Name' },
    { key: 'phone_number', label: 'Phone Number' },
    { key: 'operator_status', label: 'Status' },
    { key: 'note', label: 'Notes' },
  ],
}
