<template>
  <div class="login-container">
    <div class="login-card">
      <h1 class="brand">LeaveHub</h1>
      <p class="subtitle">Leave Request Management System</p>
      
      <form @submit.prevent="handleLogin" class="login-form">
        <div v-if="errorMessage" class="error-alert">
          {{ errorMessage }}
        </div>

        <div class="form-group">
          <label for="email">Email</label>
          <input 
            type="email" 
            id="email" 
            v-model="email" 
            placeholder="admin@energeek.id"
            required
          />
        </div>

        <div class="form-group">
          <label for="password">Password</label>
          <input 
            type="password" 
            id="password" 
            v-model="password" 
            placeholder="••••••••"
            required
          />
        </div>

        <button type="submit" :disabled="isLoading" class="btn-login">
          {{ isLoading ? 'Memproses...' : 'Login' }}
        </button>
      </form>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { useAuthStore } from '../stores/auth'

const authStore = useAuthStore()
const email = ref('')
const password = ref('')
const errorMessage = ref('')
const isLoading = ref(false)

const handleLogin = async () => {
  isLoading.value = true
  errorMessage.value = ''
  
  const result = await authStore.login(email.value, password.value)
  
  if (!result.success) {
    errorMessage.value = result.message || 'Login gagal.'
  }
  
  isLoading.value = false
}
</script>

<style scoped>
.login-container {
  display: flex;
  justify-content: center;
  align-items: center;
  height: 100vh;
  background-color: #f3f4f6;
}

.login-card {
  background: white;
  padding: 40px;
  border-radius: 8px;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
  width: 100%;
  max-width: 400px;
}

.brand {
  font-size: 32px;
  font-weight: 700;
  color: #111827;
  margin-bottom: 5px;
  text-align: center;
}

.subtitle {
  font-size: 16px;
  color: #6b7280;
  margin-bottom: 30px;
  text-align: center;
}

.form-group {
  margin-bottom: 20px;
}

label {
  display: block;
  margin-bottom: 8px;
  font-weight: 500;
}

input {
  width: 100%;
  padding: 10px 12px;
  border: 1px solid #d1d5db;
  border-radius: 6px;
  font-family: inherit;
  font-size: 16px;
}

.btn-login {
  width: 100%;
  padding: 12px;
  background-color: #4f46e5;
  color: white;
  border: none;
  border-radius: 6px;
  font-size: 16px;
  font-weight: 600;
  cursor: pointer;
  font-family: inherit;
}

.btn-login:disabled {
  background-color: #9ca3af;
}

.error-alert {
  background-color: #fee2e2;
  color: #b91c1c;
  padding: 10px;
  border-radius: 6px;
  margin-bottom: 20px;
  font-size: 14px;
}
</style>