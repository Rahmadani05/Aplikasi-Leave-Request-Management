import { createRouter, createWebHistory } from 'vue-router'

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    {
      path: '/login',
      name: 'login',
      component: () => import('../views/LoginView.vue')
    },
    {
      path: '/',
      name: 'kelola-user',
      component: () => import('../views/Admin/KelolaUser.vue'),
      meta: { requiresAuth: true }
    },
    {
      path: '/leave-requests',
      name: 'leave-requests',
      component: () => import('../views/Admin/LeaveRequestsView.vue'), 
      meta: { requiresAuth: true }
    },
    {
      path: '/sisa-kuota',
      name: 'sisa-kuota',
      component: () => import('../views/User/SisaKuotaView.vue'),
      meta: { requiresAuth: true }
    },
    {
      path: '/ajukan-cuti',
      name: 'ajukan-cuti',
      component: () => import('../views/User/AjukanCutiView.vue'),
      meta: { requiresAuth: true }
    },
    {
      path: '/riwayat-cuti',
      name: 'riwayat-cuti',
      component: () => import('../views/User/RiwayatCutiView.vue'),
      meta: { requiresAuth: true }
    }
  ]
})

// Navigation Guard untuk proteksi halaman dan role
router.beforeEach((to, from) => {
  const token = localStorage.getItem('token')
  const userString = localStorage.getItem('user')
  const user = userString ? JSON.parse(userString) : null
  
  const isAuthenticated = !!token
  if (to.meta.requiresAuth && !isAuthenticated) {
    return { name: 'login' }
  } 
  
  if (to.name === 'login' && isAuthenticated) {
    return user?.role === 'admin' ? { path: '/' } : { path: '/sisa-kuota' }
  }

  if (isAuthenticated && user?.role === 'user') {
    if (to.path === '/' || to.path === '/leave-requests') {
      return { path: '/sisa-kuota' } // Tendang kembali ke dashboard user
    }
  }

  if (isAuthenticated && user?.role === 'admin') {
    if (to.path === '/sisa-kuota' || to.path === '/ajukan-cuti' || to.path === '/riwayat-cuti') {
      return { path: '/' }
    }
  }
})

export default router