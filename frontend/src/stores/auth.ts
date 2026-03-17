import { defineStore } from 'pinia'
import { ref } from 'vue'
import api from '../services/api'
import { useRouter } from 'vue-router'

export const useAuthStore = defineStore('auth', () => {
  const user = ref(JSON.parse(localStorage.getItem('user') || 'null'))
  const token = ref(localStorage.getItem('token') || null)
  const router = useRouter()

  const login = async (email: string, password: string) => {
    try {
      const response = await api.post('/login', { email, password })
      
      // Simpan token dan data user
      token.value = response.data.access_token
      user.value = response.data.user
      localStorage.setItem('token', token.value)
      localStorage.setItem('user', JSON.stringify(user.value))
      
      router.push('/')
      return { success: true }
    } catch (error: any) {
      return { 
        success: false, 
        message: error.response?.data?.message || 'Terjadi kesalahan pada server'
      }
    }
  }

  const logout = async () => {
    try {
      await api.post('/logout')
    } catch (error) {
      console.error('Logout error', error)
    } finally {
      // Bersihkan state dan storage
      token.value = null
      user.value = null
      localStorage.removeItem('token')
      localStorage.removeItem('user')
      router.push('/login')
    }
  }

  return { user, token, login, logout }
})