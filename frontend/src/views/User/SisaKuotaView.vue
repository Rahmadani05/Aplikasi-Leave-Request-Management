<template>
    <div class="dashboard-layout">
        <aside class="sidebar">
            <div class="sidebar-header">
                <h2 class="brand-title">Leave<span class="brand-highlight">Hub</span></h2>
                <p class="brand-subtitle">Leave Request Management</p>
            </div>

            <div class="sidebar-menu">
                <p class="menu-label">USER MENU</p>
                <router-link to="/login" class="menu-item">
                    <i class="fas fa-key menu-icon"></i>
                    Login
                </router-link>
                <router-link to="/sisa-kuota" class="menu-item active">
                    <i class="fas fa-chart-bar menu-icon"></i>
                    Sisa Kuota
                </router-link>
                <router-link to="/ajukan-cuti" class="menu-item">
                    <i class="fas fa-pencil-alt menu-icon"></i>
                    Ajukan Cuti
                </router-link>
                <router-link to="/riwayat-cuti" class="menu-item">
                    <i class="fas fa-clipboard-list menu-icon"></i>
                    Riwayat Cuti
                </router-link>
            </div>

            <div class="sidebar-footer">
                <div class="user-profile">
                    <div class="user-avatar green-avatar">{{ getUserInitial() }}</div>
                    <div class="user-info">
                        <p class="user-name">{{ authStore.user?.name || 'User' }}</p>
                        <p class="user-email">{{ authStore.user?.email || 'user@energeek.id' }}</p>
                    </div>
                </div>
                <button @click="handleLogout" class="btn-logout" title="Logout">
                    <i class="fas fa-power-off"></i>
                </button>
            </div>
        </aside>

        <main class="main-content">
            <header class="content-header">
                <div>
                    <h1 class="page-title">Sisa Kuota Cuti</h1>
                    <p class="page-subtitle">Periode: Tahun {{ currentYear }} · Kalkulasi: total_quota - used</p>
                </div>

                <div class="role-indicator">
                    <span class="role-label">Role:</span>
                    <button class="role-btn">Admin</button>
                    <button class="role-btn active">User</button>
                    <button class="role-btn show-all"><i class="fas fa-camera"></i> Show All</button>
                </div>
            </header>

            <div class="quota-cards-container">
                <div v-for="balance in balances" :key="balance.id" class="quota-card">
                    <div class="quota-header">
                        <h3 class="quota-title">
                            <span v-if="balance.leave_type?.name === 'Annual Leave'">🏖️</span>
                            <span v-else>🏥</span>
                            {{ balance.leave_type?.name }}
                        </h3>
                    </div>

                    <div class="progress-bar-container">
                        <div class="progress-fill"
                            :class="balance.leave_type?.name === 'Annual Leave' ? 'blue-fill' : 'green-fill'"
                            :style="{ width: calculatePercentage(balance.total_quota, balance.used) + '%' }"></div>
                    </div>

                    <div class="quota-footer">
                        <span class="text-gray-500 font-medium">Terpakai: <strong class="text-dark">{{ balance.used }}
                                hari</strong></span>
                        <span class="text-gray-500 font-medium">Sisa: <strong class="text-dark">{{ balance.total_quota -
                                balance.used }} / {{ balance.total_quota }} hari</strong></span>
                    </div>
                </div>

                <div v-if="balances.length === 0" class="w-full text-gray-500">Memuat data sisa kuota...</div>
            </div>

            <div class="summary-cards mt-32">
                <div class="card">
                    <div class="card-icon yellow-text">
                        <i class="fas fa-hourglass-half"></i>
                    </div>
                    <div class="card-data">
                        <p class="card-title">Pending</p>
                        <h3 class="card-value yellow-text">{{ pendingCount }}</h3>
                        <p class="card-desc">Menunggu approval</p>
                    </div>
                </div>

                <div class="card">
                    <div class="card-icon green-text">
                        <i class="fas fa-check"></i>
                    </div>
                    <div class="card-data">
                        <p class="card-title">Approved</p>
                        <h3 class="card-value green-text">{{ approvedCount }}</h3>
                        <p class="card-desc">Disetujui tahun ini</p>
                    </div>
                </div>

                <div class="card">
                    <div class="card-icon red-text">
                        <i class="fas fa-times"></i>
                    </div>
                    <div class="card-data">
                        <p class="card-title">Rejected</p>
                        <h3 class="card-value red-text">{{ rejectedCount }}</h3>
                        <p class="card-desc">Ditolak</p>
                    </div>
                </div>
            </div>
        </main>
    </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useAuthStore } from '../../stores/auth'
import api from '../../services/api'

const authStore = useAuthStore()
const balances = ref<any[]>([])
const requests = ref<any[]>([])
const currentYear = new Date().getFullYear()

const handleLogout = async () => {
    await authStore.logout()
}

const getUserInitial = () => {
    if (authStore.user && authStore.user.name) {
        return authStore.user.name.substring(0, 1).toUpperCase()
    }
    return 'U'
}

// Menghitung lebar progress bar berdasarkan sisa cuti (Sisa / Total * 100)
const calculatePercentage = (total: number, used: number) => {
    if (total === 0) return 0
    const remaining = total - used
    return (remaining / total) * 100
}

const fetchMyBalances = async () => {
    try {
        const response = await api.get('/my-balances')
        if (response.data && response.data.data) {
            balances.value = response.data.data
        }
    } catch (error) {
        console.error('Failed to fetch balances', error)
    }
}

const fetchMyRequests = async () => {
    try {
        const response = await api.get('/leave-requests')
        if (response.data && response.data.data) {
            requests.value = response.data.data
        }
    } catch (error) {
        console.error('Failed to fetch requests', error)
    }
}

// Compute statistik request
const pendingCount = computed(() => requests.value.filter(r => r.status === 'pending').length)
const approvedCount = computed(() => requests.value.filter(r => r.status === 'approved').length)
const rejectedCount = computed(() => requests.value.filter(r => r.status === 'rejected').length)

onMounted(() => {
    fetchMyBalances()
    fetchMyRequests()
})
</script>

<style scoped>
/* Base Layout & Sidebar */
.dashboard-layout {
    display: flex;
    min-height: 100vh;
    background-color: #f4f5f7;
    font-family: 'Khand', sans-serif;
}

.sidebar {
    width: 260px;
    background-color: #1a1d24;
    color: white;
    display: flex;
    flex-direction: column;
    flex-shrink: 0;
}

.sidebar-header {
    padding: 24px;
    border-bottom: 1px solid #2d3748;
}

.brand-title {
    font-size: 24px;
    margin: 0;
    font-weight: 700;
}

.brand-highlight {
    color: #6366f1;
}

.brand-subtitle {
    font-size: 13px;
    color: #9ca3af;
    margin: 4px 0 0 0;
}

.sidebar-menu {
    padding: 24px 16px;
    flex: 1;
}

.menu-label {
    font-size: 12px;
    color: #6b7280;
    font-weight: 600;
    margin-bottom: 16px;
    padding-left: 8px;
    letter-spacing: 0.05em;
}

.menu-item {
    display: flex;
    align-items: center;
    padding: 12px 16px;
    color: #d1d5db;
    text-decoration: none;
    border-radius: 8px;
    margin-bottom: 8px;
    font-size: 15px;
    font-weight: 500;
    transition: all 0.2s;
}

.menu-item:hover {
    background-color: #374151;
    color: white;
}

.menu-item.active {
    background-color: #6366f1;
    color: white;
}

.menu-icon {
    width: 24px;
    margin-right: 12px;
}

.sidebar-footer { 
  padding: 16px; 
  border-top: 1px solid #2d3748; 
  display: flex; 
  flex-direction: row; 
  align-items: center; 
  justify-content: space-between; 
  flex-wrap: nowrap; 
  background-color: #111318; 
}

.user-profile { 
  display: flex; 
  align-items: center; 
  min-width: 0; 
  margin-right: 12px;
}

.user-avatar {
    width: 36px;
    height: 36px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    margin-right: 12px;
    color: white;
}

.green-avatar {
    background-color: #10b981;
}

.user-info {
  min-width: 0;
}

.user-info .user-name { 
  margin: 0; 
  font-size: 14px; 
  font-weight: 600; 
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.user-info .user-email { 
  margin: 0; 
  font-size: 12px; 
  color: #9ca3af; 
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.btn-logout { 
  background: transparent; 
  border: none; 
  color: #9ca3af; 
  cursor: pointer; 
  padding: 8px; 
  transition: color 0.2s; 
  flex-shrink: 0;
}
.btn-logout:hover { color: #ef4444; }

/* Main Content */
.main-content {
    flex: 1;
    padding: 32px 40px;
    overflow-y: auto;
}

.content-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 32px;
}

.page-title {
    margin: 0 0 8px 0;
    font-size: 28px;
    color: #111827;
    font-weight: 700;
}

.page-subtitle {
    margin: 0;
    color: #6b7280;
    font-size: 15px;
}

.role-indicator {
    display: flex;
    align-items: center;
    background: #1f2937;
    padding: 4px;
    border-radius: 8px;
}

.role-label {
    color: #9ca3af;
    font-size: 14px;
    margin: 0 12px;
}

.role-btn {
    background: transparent;
    border: none;
    color: #d1d5db;
    padding: 6px 16px;
    border-radius: 6px;
    font-size: 14px;
    cursor: pointer;
    font-family: inherit;
}

.role-btn.active {
    background: #6366f1;
    color: white;
}

.role-btn.show-all {
    color: #fbbf24;
}

/* Quota Cards (Progress Bars) */
.quota-cards-container {
    display: flex;
    gap: 24px;
    margin-bottom: 32px;
    flex-wrap: wrap;
}

.quota-card {
    background: white;
    border-radius: 12px;
    padding: 24px;
    flex: 1;
    min-width: 300px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
    border: 1px solid #e5e7eb;
}

.quota-header {
    margin-bottom: 16px;
}

.quota-title {
    margin: 0;
    font-size: 16px;
    font-weight: 700;
    color: #111827;
    display: flex;
    align-items: center;
    gap: 8px;
}

.progress-bar-container {
    width: 100%;
    height: 8px;
    background-color: #e5e7eb;
    border-radius: 4px;
    margin-bottom: 16px;
    overflow: hidden;
}

.progress-fill {
    height: 100%;
    border-radius: 4px;
    transition: width 0.5s ease-out;
}

.blue-fill {
    background-color: #6366f1;
}

.green-fill {
    background-color: #10b981;
}

.quota-footer {
    display: flex;
    justify-content: space-between;
    font-size: 14px;
}

.text-gray-500 {
    color: #6b7280;
}

.text-dark {
    color: #111827;
}

.font-medium {
    font-weight: 500;
}

/* Summary Cards (Pending, Approved, Rejected) */
.mt-32 {
    margin-top: 32px;
}

.summary-cards {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 24px;
}

.card {
    background: white;
    border-radius: 12px;
    padding: 24px;
    display: flex;
    flex-direction: column;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
    border: 1px solid #e5e7eb;
}

.card-icon {
    font-size: 24px;
    margin-bottom: 12px;
}

.card-title {
    margin: 0;
    color: #6b7280;
    font-size: 14px;
    font-weight: 600;
}

.card-value {
    margin: 8px 0;
    font-size: 32px;
    font-weight: 700;
}

.card-desc {
    margin: 0;
    color: #9ca3af;
    font-size: 13px;
}

.yellow-text {
    color: #f59e0b;
}

.green-text {
    color: #10b981;
}

.red-text {
    color: #ef4444;
}
</style>