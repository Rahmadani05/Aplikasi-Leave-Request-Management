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
                <router-link to="/sisa-kuota" class="menu-item">
                    <i class="fas fa-chart-bar menu-icon"></i>
                    Sisa Kuota
                </router-link>
                <router-link to="/ajukan-cuti" class="menu-item active">
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
                    <h1 class="page-title">Ajukan Cuti</h1>
                    <p class="page-subtitle">Isi form untuk mengajukan permohonan cuti baru.</p>
                </div>

                <div class="role-indicator">
                    <span class="role-label">Role:</span>
                    <button class="role-btn">Admin</button>
                    <button class="role-btn active">User</button>
                    <button class="role-btn show-all"><i class="fas fa-camera"></i> Show All</button>
                </div>
            </header>

            <div class="form-card">
                <h3 class="card-title-main">Form Pengajuan Cuti</h3>

                <form @submit.prevent="submitLeaveRequest" class="leave-form">
                    <div v-if="errorMessage" class="error-alert">
                        <i class="fas fa-exclamation-circle"></i> {{ errorMessage }}
                    </div>
                    <div v-if="successMessage" class="success-alert">
                        <i class="fas fa-check-circle"></i> {{ successMessage }}
                    </div>

                    <div class="form-group">
                        <label>Jenis Cuti</label>
                        <select v-model="form.leave_type_id" required class="form-control">
                            <option value="" disabled>Pilih Jenis Cuti</option>
                            <option v-for="balance in balances" :key="balance.leave_type_id"
                                :value="balance.leave_type_id">
                                {{ balance.leave_type?.name }} (sisa: {{ balance.total_quota - balance.used }} hari)
                            </option>
                        </select>
                    </div>

                    <div class="form-row">
                        <div class="form-group half-width">
                            <label>Tanggal Mulai</label>
                            <input type="date" v-model="form.start_date" required class="form-control" :min="today" />
                        </div>
                        <div class="form-group half-width">
                            <label>Tanggal Selesai</label>
                            <input type="date" v-model="form.end_date" required class="form-control"
                                :min="form.start_date || today" />
                        </div>
                    </div>

                    <div v-if="form.start_date && form.end_date && selectedBalance" class="info-box blue-bg-light">
                        <p class="info-text">
                            📅 <strong>Total: {{ calculatedDays }} hari</strong>
                            ({{ formatDateDisplay(form.start_date) }} - {{ formatDateDisplay(form.end_date) }})
                        </p>
                        <p class="info-text info-sub">
                            Sisa balance setelah approved:
                            <strong>{{ currentSisa }} → <span :class="{ 'text-red': projectedSisa < 0 }">{{ projectedSisa
                                    }} hari</span></strong>
                        </p>
                    </div>

                    <div class="form-group">
                        <label>Alasan</label>
                        <textarea v-model="form.reason" rows="3" placeholder="Liburan keluarga ke Malang" required
                            class="form-control"></textarea>
                    </div>

                    <button type="submit" class="btn-primary"
                        :disabled="isSubmitting || projectedSisa < 0 || calculatedDays <= 0">
                        {{ isSubmitting ? 'Memproses...' : 'Submit Pengajuan' }}
                    </button>
                </form>
            </div>

            <div class="validation-info-cards mt-32">
                <div class="validation-card green-border">
                    <h4 class="validation-title">Validasi yang Dicek</h4>
                    <ul class="validation-list">
                        <li><i class="fas fa-check-square text-green"></i> <strong>Tanggal valid</strong> (start ≤ end,
                            tidak boleh sama)</li>
                        <li><i class="fas fa-check-square text-green"></i> <strong>Kuota cukup</strong> (sisa ≥ hari
                            yang diajukan)</li>
                        <li><i class="fas fa-check-square text-green"></i> <strong>Tidak ada overlap</strong> dengan
                            request pending/approved</li>
                    </ul>
                </div>

                <div class="validation-card red-border mt-16">
                    <h4 class="validation-title text-red">Contoh: Validasi Gagal</h4>
                    <ul class="validation-list">
                        <li><i class="fas fa-check-square text-green"></i> <strong class="text-green">Tanggal
                                valid</strong></li>
                        <li><i class="fas fa-times text-red"></i> <strong class="text-red">Kuota tidak cukup</strong> —
                            sisa 2 hari, mengajukan 5 hari</li>
                        <li><i class="fas fa-times text-red"></i> <strong class="text-red">Overlap terdeteksi</strong> —
                            bentrok dengan request 18-20 Mar (pending)</li>
                    </ul>
                </div>
            </div>
        </main>
    </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../../stores/auth'
import api from '../../services/api'

const authStore = useAuthStore()
const router = useRouter()
const balances = ref<any[]>([])

// Form State
const form = ref({
    leave_type_id: '',
    start_date: '',
    end_date: '',
    reason: ''
})

const isSubmitting = ref(false)
const errorMessage = ref('')
const successMessage = ref('')

const today = new Date().toISOString().split('T')[0]

const handleLogout = async () => {
    await authStore.logout()
}

const getUserInitial = () => {
    if (authStore.user && authStore.user.name) {
        return authStore.user.name.substring(0, 1).toUpperCase()
    }
    return 'U'
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

const selectedBalance = computed(() => {
    return balances.value.find(b => b.leave_type_id === form.value.leave_type_id)
})

const currentSisa = computed(() => {
    if (!selectedBalance.value) return 0
    return selectedBalance.value.total_quota - selectedBalance.value.used
})

const calculatedDays = computed(() => {
    if (!form.value.start_date || !form.value.end_date) return 0
    const start = new Date(form.value.start_date)
    const end = new Date(form.value.end_date)

    const diffTime = end.getTime() - start.getTime()
    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24)) + 1

    return diffDays > 0 ? diffDays : 0
})

const projectedSisa = computed(() => {
    return currentSisa.value - calculatedDays.value
})

const formatDateDisplay = (dateString: string) => {
    if (!dateString) return ''
    const date = new Date(dateString)
    return date.toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' })
}

// Submit Data
const submitLeaveRequest = async () => {
    isSubmitting.value = true
    errorMessage.value = ''
    successMessage.value = ''

    try {
        await api.post('/leave-requests', form.value)
        successMessage.value = 'Pengajuan cuti berhasil dikirim!'

        setTimeout(() => {
            router.push('/riwayat-cuti')
        }, 1500)

    } catch (error: any) {
        if (error.response?.status === 422 && error.response.data.errors) {
            const errs = error.response.data.errors
            errorMessage.value = Object.values(errs).flat().join(' ')
        } else {
            errorMessage.value = error.response?.data?.message || 'Gagal mengajukan cuti.'
        }
    } finally {
        isSubmitting.value = false
    }
}

onMounted(() => {
    fetchMyBalances()
})
</script>

<style scoped>
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
    align-items: center;
    justify-content: space-between;
    flex-wrap: nowrap;
    background-color: #111318;
}

.user-profile {
    display: flex;
    align-items: center;
    overflow: hidden;
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
    flex-shrink: 0;
}

.green-avatar {
    background-color: #10b981;
}

.user-info {
    overflow: hidden;
    white-space: nowrap;
    margin-right: 8px;
}

.user-info .user-name {
    margin: 0;
    font-size: 14px;
    font-weight: 600;
    overflow: hidden;
    text-overflow: ellipsis;
}

.user-info .user-email {
    margin: 0;
    font-size: 12px;
    color: #9ca3af;
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

.btn-logout:hover {
    color: #ef4444;
}

/* Main konten */
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

/* Form Card */
.form-card {
    background: white;
    border-radius: 12px;
    padding: 32px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
    border: 1px solid #e5e7eb;
    max-width: 800px;
}

.card-title-main {
    margin: 0 0 24px 0;
    font-size: 18px;
    color: #111827;
    font-weight: 700;
}

.leave-form .form-group {
    margin-bottom: 20px;
}

.leave-form label {
    display: block;
    margin-bottom: 8px;
    font-size: 14px;
    font-weight: 600;
    color: #374151;
}

.form-control {
    width: 100%;
    padding: 10px 14px;
    border: 1px solid #d1d5db;
    border-radius: 8px;
    font-family: inherit;
    font-size: 15px;
    background-color: #fff;
    color: #111827;
    box-sizing: border-box;
}

.form-control:focus {
    outline: none;
    border-color: #6366f1;
    box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
}

.form-row {
    display: flex;
    gap: 20px;
}

.half-width {
    flex: 1;
}

.btn-primary {
    background-color: #6366f1;
    color: white;
    border: none;
    padding: 12px 24px;
    border-radius: 8px;
    font-size: 16px;
    font-weight: 600;
    cursor: pointer;
    font-family: inherit;
    transition: background-color 0.2s;
}

.btn-primary:hover:not(:disabled) {
    background-color: #4f46e5;
}

.btn-primary:disabled {
    background-color: #9ca3af;
    cursor: not-allowed;
    opacity: 0.7;
}

/* Dynamic Info Box */
.info-box {
    padding: 16px;
    border-radius: 8px;
    margin-bottom: 24px;
}

.blue-bg-light {
    background-color: #e0e7ff;
    color: #3730a3;
    border: 1px solid #c7d2fe;
}

.info-text {
    margin: 0 0 4px 0;
    font-size: 14px;
}

.info-sub {
    color: #4f46e5;
}

/* Alerts */
.error-alert {
    background-color: #fee2e2;
    color: #b91c1c;
    padding: 12px 16px;
    border-radius: 8px;
    margin-bottom: 20px;
    font-size: 14px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.success-alert {
    background-color: #dcfce7;
    color: #15803d;
    padding: 12px 16px;
    border-radius: 8px;
    margin-bottom: 20px;
    font-size: 14px;
    display: flex;
    align-items: center;
    gap: 8px;
}

/* validasi cards */
.mt-32 {
    margin-top: 32px;
}

.mt-16 {
    margin-top: 16px;
}

.validation-card {
    background: white;
    border-radius: 12px;
    padding: 24px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
    border: 1px solid #e5e7eb;
    max-width: 800px;
}

.validation-title {
    margin: 0 0 16px 0;
    font-size: 16px;
    font-weight: 700;
    color: #111827;
}

.validation-list {
    list-style: none;
    padding: 0;
    margin: 0;
}

.validation-list li {
    margin-bottom: 8px;
    font-size: 14px;
    color: #374151;
    display: flex;
    align-items: center;
    gap: 10px;
}

.text-green {
    color: #10b981;
}

.text-red {
    color: #ef4444;
}
</style>