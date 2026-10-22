import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import LoginView from '@/views/LoginView.vue'
import DashboardView from '@/views/DashboardView.vue'
import ModulePlaceholderView from '@/views/ModulePlaceholderView.vue'

const modules = [
  ['/bookings', 'Daily Bookings'], ['/monthly-contracts', 'Monthly Contracts'],
  ['/fleet', 'Cars / Vehicles'], ['/customers', 'Customers'], ['/drivers', 'Drivers'],
  ['/operators', 'Operators'], ['/insurance', 'Insurance'], ['/maintenance', 'Maintenance'],
  ['/payments', 'Payments'], ['/reports', 'Reports'],
] as const
const router = createRouter({ history: createWebHistory(import.meta.env.BASE_URL), routes: [
  { path: '/', redirect: '/dashboard' },
  { path: '/login', component: LoginView, meta: { public: true } },
  { path: '/dashboard', component: DashboardView, meta: { title: 'Dashboard', description: 'Monitor normal daily bookings, available cars, and driver readiness from one warm, simple control panel.' } },
  ...modules.map(([path,title]) => ({ path, component: ModulePlaceholderView, meta: { title, description: `Manage ${title.toLowerCase()} from the GSS control panel.` } })),
  { path: '/:pathMatch(.*)*', redirect: '/dashboard' },
] })
router.beforeEach(async (to) => {
  const auth = useAuthStore()
  if (auth.token && !auth.user) await auth.loadUser()
  if (!to.meta.public && !auth.isAuthenticated) return { path: '/login', query: { redirect: to.fullPath } }
  if (to.path === '/login' && auth.isAuthenticated) return '/dashboard'
})
export default router
