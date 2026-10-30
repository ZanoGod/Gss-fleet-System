import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { getApiErrorCategory } from '@/services/apiErrors'

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    { path: '/register', component: () => import('@/layouts/AuthLayout.vue'), meta: { public: true }, children: [{ path: '', component: () => import('@/views/RegisterView.vue'), meta: { title: 'Register', public: true } }] },
    { path: '/login', component: () => import('@/layouts/AuthLayout.vue'), meta: { public: true }, children: [{ path: '', name: 'login', component: () => import('@/views/LoginView.vue'), meta: { title: 'Login', public: true } }] },
    { path: '/', component: () => import('@/layouts/AppLayout.vue'), children: [
      { path: '', redirect: '/dashboard' },
      { path: 'dashboard', name: 'dashboard', component: () => import('@/modules/dashboard/pages/DashboardPage.vue'), meta: { title: 'Dashboard', description: 'Monitor fleet operations from one place.' } },
      { path: 'daily-bookings', name: 'dailybookingsList', component: () => import('@/modules/daily-bookings/pages/BookingListPage.vue'), meta: { title: 'Daily Bookings', description: 'Search, review, and manage date-based fleet bookings.' } },
      { path: 'daily-bookings/create', name: 'dailybookingsCreate', component: () => import('@/modules/daily-bookings/pages/BookingCreatePage.vue'), meta: { title: 'Create Daily Booking', description: 'Booking dates are inclusive calendar dates. The server checks vehicle and driver availability when confirming.' } },
      { path: 'daily-bookings/:id/edit', name: 'dailybookingsEdit', component: () => import('@/modules/daily-bookings/pages/BookingEditPage.vue'), meta: { title: 'Edit Daily Booking', description: 'Update the saved booking fields and status.' } },
      { path: 'daily-bookings/:id', name: 'dailybookingsDetail', component: () => import('@/modules/daily-bookings/pages/BookingDetailPage.vue'), meta: { title: 'Booking Details', description: 'Review vehicle, driver, operator, date, and status information.' } },
      { path: 'fleet', name: 'fleetList', component: () => import('@/modules/fleet/pages/VehicleListPage.vue'), meta: { title: 'Vehicles', description: 'View vehicle information from verified application data.' } },
      { path: 'fleet/create', name: 'fleetCreate', component: () => import('@/modules/fleet/pages/VehicleCreatePage.vue'), meta: { title: 'Create Vehicle', description: 'This page is a non-submitting shell until legacy fields and API rules are verified.' } },
      { path: 'fleet/:id/edit', name: 'fleetEdit', component: () => import('@/modules/fleet/pages/VehicleEditPage.vue'), meta: { title: 'Edit Vehicle', description: 'This page is a non-submitting shell until legacy fields and API rules are verified.' } },
      { path: 'fleet/:id', name: 'fleetDetail', component: () => import('@/modules/fleet/pages/VehicleDetailPage.vue'), meta: { title: 'Vehicle details', description: 'View vehicle information from verified application data.' } },
      { path: 'customers', name: 'customersList', component: () => import('@/modules/customers/pages/CustomerListPage.vue'), meta: { title: 'Customers', description: 'Search customer profiles and review monthly contract assignments.' } },
      { path: 'customers/create', name: 'customersCreate', component: () => import('@/modules/customers/pages/CustomerCreatePage.vue'), meta: { title: 'Create Customer', description: 'Create a customer profile for monthly contracts.' } },
      { path: 'customers/:id/edit', name: 'customersEdit', component: () => import('@/modules/customers/pages/CustomerEditPage.vue'), meta: { title: 'Edit Customer', description: 'Update customer contact and service information.' } },
      { path: 'customers/:id', name: 'customersDetail', component: () => import('@/modules/customers/pages/CustomerDetailPage.vue'), meta: { title: 'Customer details', description: 'Review the customer profile and contracts for a selected month.' } },
      { path: 'drivers', name: 'driversList', component: () => import('@/modules/drivers/pages/DriverListPage.vue'), meta: { title: 'Drivers', description: 'View driver information from verified application data.' } },
      { path: 'drivers/create', name: 'driversCreate', component: () => import('@/modules/drivers/pages/DriverCreatePage.vue'), meta: { title: 'Create Driver', description: 'This page is a non-submitting shell until legacy fields and API rules are verified.' } },
      { path: 'drivers/:id/edit', name: 'driversEdit', component: () => import('@/modules/drivers/pages/DriverEditPage.vue'), meta: { title: 'Edit Driver', description: 'This page is a non-submitting shell until legacy fields and API rules are verified.' } },
      { path: 'drivers/:id', name: 'driversDetail', component: () => import('@/modules/drivers/pages/DriverDetailPage.vue'), meta: { title: 'Driver details', description: 'View driver information from verified application data.' } },
      { path: 'operators', name: 'operatorsList', component: () => import('@/modules/operators/pages/OperatorListPage.vue'), meta: { title: 'Operators', description: 'View operator information from verified application data.' } },
      { path: 'operators/create', name: 'operatorsCreate', component: () => import('@/modules/operators/pages/OperatorCreatePage.vue'), meta: { title: 'Create Operator', description: 'This page is a non-submitting shell until legacy fields and API rules are verified.' } },
      { path: 'operators/:id/edit', name: 'operatorsEdit', component: () => import('@/modules/operators/pages/OperatorEditPage.vue'), meta: { title: 'Edit Operator', description: 'This page is a non-submitting shell until legacy fields and API rules are verified.' } },
      { path: 'operators/:id', name: 'operatorsDetail', component: () => import('@/modules/operators/pages/OperatorDetailPage.vue'), meta: { title: 'Operator details', description: 'View operator information from verified application data.' } },
      { path: 'insurance', name: 'insuranceList', component: () => import('@/modules/insurance/pages/InsuranceListPage.vue'), meta: { title: 'Insurance Management', description: 'Manage coverage, providers, and claims across both vehicle fleets.' } },
      { path: 'insurance/create', name: 'insuranceCreate', component: () => import('@/modules/insurance/pages/InsuranceCreatePage.vue'), meta: { title: 'Create Insurance Policy', description: 'Record a policy against its verified daily or monthly fleet vehicle.' } },
      { path: 'insurance/:id/edit', name: 'insuranceEdit', component: () => import('@/modules/insurance/pages/InsuranceEditPage.vue'), meta: { title: 'Edit Insurance Policy', description: 'Update a policy that has not been renewed, cancelled, or archived.' } },
      { path: 'insurance/:id', name: 'insuranceDetail', component: () => import('@/modules/insurance/pages/InsuranceDetailPage.vue'), meta: { title: 'Insurance Policy', description: 'Policy details, claims, renewal history, and private documents.' } },
      { path: 'maintenance', name: 'maintenanceList', component: () => import('@/modules/maintenance/pages/MaintenanceListPage.vue'), meta: { title: 'Maintenances', description: 'View maintenance information from verified application data.' } },
      { path: 'maintenance/create', name: 'maintenanceCreate', component: () => import('@/modules/maintenance/pages/MaintenanceCreatePage.vue'), meta: { title: 'Create Maintenance', description: 'This page is a non-submitting shell until legacy fields and API rules are verified.' } },
      { path: 'maintenance/:id/edit', name: 'maintenanceEdit', component: () => import('@/modules/maintenance/pages/MaintenanceEditPage.vue'), meta: { title: 'Edit Maintenance', description: 'This page is a non-submitting shell until legacy fields and API rules are verified.' } },
      { path: 'maintenance/:id', name: 'maintenanceDetail', component: () => import('@/modules/maintenance/pages/MaintenanceDetailPage.vue'), meta: { title: 'Maintenance details', description: 'View maintenance information from verified application data.' } },
      { path: 'monthly-contracts', name: 'monthlycontractsList', component: () => import('@/modules/monthly-contracts/pages/ContractListPage.vue'), meta: { title: 'Monthly Contracts', description: 'Manage customer agreements and monthly fleet assignments.' } },
      { path: 'monthly-contracts/create', name: 'monthlycontractsCreate', component: () => import('@/modules/monthly-contracts/pages/ContractCreatePage.vue'), meta: { title: 'Create Monthly Contract', description: 'Assign monthly fleet resources and capture customer and contract insurance snapshots.' } },
      { path: 'monthly-contracts/:id/edit', name: 'monthlycontractsEdit', component: () => import('@/modules/monthly-contracts/pages/ContractEditPage.vue'), meta: { title: 'Edit Monthly Contract', description: 'Update the contract while preserving its number and renewal history.' } },
      { path: 'monthly-contracts/:id/renew', name: 'monthlycontractsRenew', component: () => import('@/modules/monthly-contracts/pages/ContractRenewPage.vue'), meta: { title: 'Renew Monthly Contract', description: 'Renewal preserves the prior contract and creates a linked historical term.' } },
      { path: 'monthly-contracts/:id', name: 'monthlycontractsDetail', component: () => import('@/modules/monthly-contracts/pages/ContractDetailPage.vue'), meta: { title: 'Monthly Contract Details', description: 'Contract period, customer snapshots, monthly fleet assignments, and renewal lineage.' } },
      { path: 'payments', name: 'payments', component: () => import('@/modules/payments/pages/PaymentListPage.vue'), meta: { title: 'Payments', description: 'Review payment records when the legacy workflow and API are verified.' } },
      { path: 'reports', name: 'reports', component: () => import('@/modules/reports/pages/ReportsPage.vue'), meta: { title: 'Reports', description: 'Review operational reporting.' } }
    ] },
    { path: '/bookings/:pathMatch(.*)*', redirect: to => '/daily-bookings'+(to.params.pathMatch ? '/'+(Array.isArray(to.params.pathMatch)?to.params.pathMatch.join('/') : to.params.pathMatch) : '') },
    { path: '/bookings', redirect: '/daily-bookings' },
    { path: '/:pathMatch(.*)*', redirect: '/dashboard' },
  ],
})

router.beforeEach(async (to) => {
  const auth = useAuthStore()
  try {
    const wasAuthenticated = auth.isAuthenticated
    await auth.initializeAuth()
    if (wasAuthenticated && !to.meta.public) await auth.getUser()
  } catch (error) {
    if (to.meta.public) return true
    const category = getApiErrorCategory(error)
    const reason = category === 'database' ? 'database' : category === 'server' ? 'server' : 'unavailable'
    return { path: '/login', query: { reason } }
  }
  if (!to.meta.public && !auth.isAuthenticated) return { path: '/login', query: { redirect: to.fullPath } }
  if (to.path === '/login' && auth.isAuthenticated) return '/dashboard'
})

export default router
