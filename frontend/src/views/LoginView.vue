<template>
    <div class="login-wrapper">
        <div class="login-card">
            <div class="header">
                <h1 class="brand-title">Leave<span class="brand-highlight">Hub</span></h1>
                <p class="brand-subtitle">Leave Request Management System</p>
            </div>

            <form @submit.prevent="handleLogin" class="login-form">
                <div v-if="errorMessage" class="error-alert">
                    {{ errorMessage }}
                </div>

                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" v-model="email" placeholder="admin@energeek.id" required />
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" v-model="password" placeholder="••••••••••" required />
                </div>

                <button type="submit" :disabled="isLoading" class="btn-login">
                    {{ isLoading ? 'Memproses...' : 'Login' }}
                </button>
            </form>

            <p class="footer-text">Sanctum PAT · No register endpoint</p>
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
.login-wrapper {
    display: flex;
    justify-content: center;
    align-items: center;
    min-height: 100vh;
    background-color: #1a1d24;
    font-family: 'Khand', sans-serif;
}

.login-card {
    background: #ffffff;
    padding: 40px 40px 30px 40px;
    border-radius: 16px;
    width: 100%;
    max-width: 420px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

.header {
    margin-bottom: 30px;
}

.brand-title {
    font-size: 32px;
    font-weight: 700;
    color: #1f2937;
    margin: 0 0 5px 0;
}

.brand-highlight {
    color: #4285f4;
}

.brand-subtitle {
    font-size: 16px;
    color: #6b7280;
    margin: 0;
}

.form-group {
    margin-bottom: 20px;
}

label {
    display: block;
    margin-bottom: 8px;
    font-size: 15px;
    font-weight: 600;
    color: #374151;
}

input {
    width: 100%;
    padding: 12px 16px;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    font-family: inherit;
    font-size: 16px;
    background-color: #f9fafb;
    color: #1f2937;
    transition: border-color 0.2s;
    box-sizing: border-box;
}

input:focus {
    outline: none;
    border-color: #4285f4;
    background-color: #ffffff;
}

.btn-login {
    width: 100%;
    padding: 12px;
    background-color: #4a72f5;
    color: white;
    border: none;
    border-radius: 8px;
    font-size: 18px;
    font-weight: 600;
    cursor: pointer;
    font-family: inherit;
    margin-top: 10px;
    transition: background-color 0.2s;
}

.btn-login:hover:not(:disabled) {
    background-color: #3b5bdb;
}

.btn-login:disabled {
    background-color: #9ca3af;
    cursor: not-allowed;
}

.error-alert {
    background-color: #fee2e2;
    color: #b91c1c;
    padding: 10px;
    border-radius: 8px;
    margin-bottom: 20px;
    font-size: 14px;
}

.footer-text {
    text-align: center;
    color: #9ca3af;
    font-size: 14px;
    margin-top: 25px;
    margin-bottom: 0;
}
</style>
