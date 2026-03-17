<template>
  <div>
    <h1>Dashboard Utama</h1>
    <p>Selamat datang, {{ authStore.user?.name }} ({{ authStore.user?.role }})</p>
    <button @click="authStore.logout">Logout</button>
  </div>
</template>

<script setup lang="ts">
import { useAuthStore } from '../stores/auth'
const authStore = useAuthStore()
</script>