import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const router = createRouter({
  history: createWebHistory(),
  routes: [
    {
      path: '/login',
      name: 'login',
      component: () => import('@/pages/LoginPage.vue'),
      meta: { guest: true },
    },
    {
      path: '/',
      component: () => import('@/layouts/AppLayout.vue'),
      meta: { requiresAuth: true },
      children: [
        {
          path: '',
          name: 'dashboard',
          component: () => import('@/pages/DashboardPage.vue'),
        },
        {
          path: 'reception',
          name: 'reception',
          component: () => import('@/pages/reception/ReceptionPage.vue'),
        },
        {
          path: 'reservations',
          name: 'reservations',
          component: () => import('@/pages/reservations/ReservationListPage.vue'),
        },
        {
          path: 'reservations/new',
          name: 'reservation-new',
          component: () => import('@/pages/reservations/ReservationFormPage.vue'),
        },
        {
          path: 'reservations/:id/edit',
          name: 'reservation-edit',
          component: () => import('@/pages/reservations/ReservationFormPage.vue'),
        },
        {
          path: 'dispatch',
          name: 'dispatch',
          component: () => import('@/pages/dispatch/DispatchPage.vue'),
        },
        {
          path: 'casts',
          name: 'casts',
          component: () => import('@/pages/casts/CastListPage.vue'),
        },
        {
          path: 'drivers',
          name: 'drivers',
          component: () => import('@/pages/drivers/DriverListPage.vue'),
        },
        {
          path: 'customers',
          name: 'customers',
          component: () => import('@/pages/customers/CustomerListPage.vue'),
        },
        {
          path: 'options',
          name: 'options',
          component: () => import('@/pages/options/OptionListPage.vue'),
        },
        {
          path: 'codewords',
          name: 'codewords',
          component: () => import('@/pages/codewords/CodewordListPage.vue'),
        },
        {
          path: 'ranks',
          name: 'ranks',
          component: () => import('@/pages/ranks/RankListPage.vue'),
        },
        {
          path: 'shifts',
          name: 'shifts',
          component: () => import('@/pages/shifts/ShiftPage.vue'),
        },
      ],
    },
    { path: '/:pathMatch(.*)*', redirect: '/' },
  ],
})

router.beforeEach((to) => {
  const auth = useAuthStore()
  if (to.meta.requiresAuth && !auth.isLoggedIn) return '/login'
  if (to.meta.guest && auth.isLoggedIn) return '/'
})

export default router
