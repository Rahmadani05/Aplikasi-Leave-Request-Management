import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import LeaveRequestView from '../views/Admin/LeaveRequestsView.vue'
import { useAuthStore } from '../stores/auth'

vi.mock('vue-router', () => ({
  useRouter: vi.fn(() => ({ push: vi.fn() })),
  useRoute: vi.fn()
}))

// API call
vi.mock('../../services/api', () => ({
  default: {
    get: vi.fn(() => Promise.resolve({ 
      data: { data: [] } 
    }))
  }
}))

describe('Admin LeaveRequestView - Unit Test', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
  })

  it('menampilkan judul halaman Leave Requests dengan benar', async () => {
    const authStore = useAuthStore()
    authStore.user = { name: 'Admin', role: 'admin' }

    const wrapper = mount(LeaveRequestView, {
      global: {
        stubs: ['router-link', 'router-view', 'font-awesome-icon']
      }
    })

    expect(wrapper.find('.page-title').text()).toBe('Semua Leave Request')
  })
})