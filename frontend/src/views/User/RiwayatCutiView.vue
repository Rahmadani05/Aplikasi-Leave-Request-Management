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
                <router-link to="/ajukan-cuti" class="menu-item">
                    <i class="fas fa-pencil-alt menu-icon"></i>
                    Ajukan Cuti
                </router-link>
                <router-link to="/riwayat-cuti" class="menu-item active">
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
                    <h1 class="page-title">Riwayat Cuti Saya</h1>
                    <p class="page-subtitle">Semua pengajuan cuti yang pernah disubmit.</p>
                </div>

                <div class="role-indicator">
                    <span class="role-label">Role:</span>
                    <button class="role-btn">Admin</button>
                    <button class="role-btn active">User</button>
                    <button class="role-btn show-all"><i class="fas fa-camera"></i> Show All</button>
                </div>
            </header>

            <section class="table-section mb-32">
                <div class="table-header">
                    <h3 class="table-title">Riwayat Pengajuan</h3>
                    <span class="text-sm text-gray-500">{{ requests.length }} request</span>
                </div>

                <div class="table-responsive">
                    <table class="user-table">
                        <thead>
                            <tr>
                                <th>TIPE</th>
                                <th>TANGGAL</th>
                                <th>HARI</th>
                                <th>ALASAN</th>
                                <th>STATUS</th>
                                <th>CATATAN ADMIN</th>
                                <th>AKSI</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-if="requests.length === 0">
                                <td colspan="7" class="text-center py-8 text-gray-500">Belum ada riwayat pengajuan cuti.
                                </td>
                            </tr>
                            <tr v-for="req in requests" :key="req.id">
                                <td class="font-medium">{{ req.leave_type?.name }}</td>
                                <td>{{ formatDateRange(req.start_date, req.end_date) }}</td>
                                <td>{{ req.total_days }}</td>
                                <td>{{ req.reason }}</td>
                                <td>
                                    <span v-if="req.status === 'pending'" class="badge badge-yellow">• pending</span>
                                    <span v-else-if="req.status === 'approved'" class="badge badge-green">•
                                        approved</span>
                                    <span v-else-if="req.status === 'rejected'" class="badge badge-red-light">•
                                        rejected</span>
                                    <span v-else class="badge badge-gray">• cancelled</span>
                                </td>
                                <td class="text-gray-500">{{ req.admin_notes || '—' }}</td>
                                <td>
                                    <button v-if="req.status === 'pending'" @click="openCancelConfirmation(req)"
                                        class="btn-sm btn-red">
                                        Cancel
                                    </button>
                                    <button v-else-if="req.status === 'rejected' || req.status === 'cancelled'"
                                        @click="deleteRequest(req.id)" class="btn-sm-outline text-red">
                                        Hapus
                                    </button>
                                    <span v-else class="text-gray-400">—</span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>

            <div v-if="cancelTarget" class="action-card border-orange">
                <h4 class="action-title text-orange">Cancel Request?</h4>
                <p class="action-detail">
                    <strong>{{ cancelTarget.leave_type?.name }}</strong> · {{ formatDateRange(cancelTarget.start_date,
                    cancelTarget.end_date) }} ({{ cancelTarget.total_days }} hari)
                </p>
                <p class="action-detail mb-4">
                    Status saat ini: <span class="badge badge-yellow text-xs">• pending</span>
                </p>

                <p class="text-red font-medium text-sm mb-4">Request yang sudah di-cancel tidak bisa dikembalikan.</p>

                <div class="action-buttons-bottom">
                    <button @click="confirmCancel" :disabled="isProcessing" class="btn-md btn-red">
                        {{ isProcessing ? 'Memproses...' : 'Ya, Cancel' }}
                    </button>
                    <button @click="cancelTarget = null" class="btn-md btn-outline"
                        :disabled="isProcessing">Batal</button>
                </div>
            </div>

        </main>
    </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useAuthStore } from '../../stores/auth'
import api from '../../services/api'

const authStore = useAuthStore()
const requests = ref<any[]>([])
const cancelTarget = ref<any>(null)
const isProcessing = ref(false)

const handleLogout = async () => {
    await authStore.logout()
}

const getUserInitial = () => {
    if (authStore.user && authStore.user.name) {
        return authStore.user.name.substring(0, 1).toUpperCase()
    }
    return 'U'
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

// Buka form konfirmasi
const openCancelConfirmation = (req: any) => {
    cancelTarget.value = req
}

const confirmCancel = async () => {
  if (!cancelTarget.value) return
  isProcessing.value = true
  
  try {
    await api.put(`/leave-requests/${cancelTarget.value.id}/cancel`)
    
    cancelTarget.value = null
    fetchMyRequests() 
  } catch (error: any) {
    alert(error.response?.data?.message || 'Gagal membatalkan request.')
  } finally {
    isProcessing.value = false
  }
}

// Hapus permanen riwayat yang sudah rejected
const deleteRequest = async (id: number) => {
    if (confirm('Yakin ingin menghapus riwayat ini?')) {
        try {
            await api.delete(`/leave-requests/${id}`)
            fetchMyRequests()
        } catch (error) {
            alert('Gagal menghapus data.')
        }
    }
}

const formatDate = (dateString: string) => {
    if (!dateString) return ''
    const date = new Date(dateString)
    return date.toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' })
}

const formatDateRange = (start: string, end: string) => {
    if (!start || !end) return '-'
    try {
        const startDate = new Date(start)
        const endDate = new Date(end)

        const startDay = startDate.getDate()
        const endDay = endDate.getDate()
        const month = startDate.toLocaleDateString('id-ID', { month: 'short' })
        const year = startDate.getFullYear()

        if (start === end) return `${startDay} ${month} ${year}`
        if (startDate.getMonth() === endDate.getMonth() && startDate.getFullYear() === endDate.getFullYear()) {
            return `${startDay} - ${endDay} ${month} ${year}`
        }
        return `${formatDate(start)} - ${formatDate(end)}`
    } catch (e) {
        return `${start} - ${end}`
    }
}

onMounted(() => {
    fetchMyRequests()
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

/* Main Konten */
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

/* Tabel Styles */
.mb-32 {
    margin-bottom: 32px;
}

.table-section {
    background: white;
    border-radius: 12px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
    border: 1px solid #e5e7eb;
    overflow: hidden;
}

.table-header {
    padding: 20px 24px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-bottom: 1px solid #e5e7eb;
}

.table-title {
    margin: 0;
    font-size: 18px;
    color: #111827;
    font-weight: 700;
}

.text-sm {
    font-size: 14px;
}

.text-gray-500 {
    color: #6b7280;
}

.text-gray-400 {
    color: #9ca3af;
}

.py-8 {
    padding-top: 32px;
    padding-bottom: 32px;
}

.user-table {
    width: 100%;
    border-collapse: collapse;
    text-align: left;
}

.user-table th {
    background-color: white;
    color: #6b7280;
    font-size: 12px;
    font-weight: 600;
    padding: 12px 24px;
    border-bottom: 1px solid #e5e7eb;
}

.user-table td {
    padding: 16px 24px;
    border-bottom: 1px solid #e5e7eb;
    color: #374151;
    font-size: 14px;
}

.font-medium {
    font-weight: 600;
    color: #111827;
}

/* Badges */
.badge {
    padding: 4px 10px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 600;
    display: inline-block;
}

.badge-yellow {
    background-color: #fef3c7;
    color: #d97706;
}

.badge-green {
    background-color: #dcfce7;
    color: #15803d;
}

.badge-red-light {
    background-color: #fee2e2;
    color: #b91c1c;
}

.badge-gray {
    background-color: #f3f4f6;
    color: #4b5563;
}

.text-xs {
    font-size: 12px;
}

/* Buttons Tabel */
.btn-sm {
    padding: 6px 16px;
    border-radius: 6px;
    font-size: 13px;
    font-weight: 600;
    border: none;
    cursor: pointer;
    font-family: inherit;
    color: white;
    transition: background-color 0.2s;
}

.btn-red {
    background-color: #ef4444;
}

.btn-red:hover:not(:disabled) {
    background-color: #dc2626;
}

.btn-sm-outline {
    padding: 4px 12px;
    border: 1px solid #fca5a5;
    background: white;
    border-radius: 6px;
    font-size: 13px;
    cursor: pointer;
    color: #ef4444;
    transition: all 0.2s;
}

.btn-sm-outline:hover {
    background-color: #fef2f2;
}

.text-red {
    color: #ef4444;
}

/* Cancel Konfirmasi Card */
.action-card {
    background: white;
    padding: 24px;
    border-radius: 12px;
    border-left: 4px solid;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
    max-width: 500px;
}

.border-orange {
    border-left-color: #d97706;
}

.text-orange {
    color: #b45309;
}

.action-title {
    margin: 0 0 12px 0;
    font-size: 18px;
    font-weight: 700;
}

.action-detail {
    margin: 0 0 8px 0;
    color: #374151;
    font-size: 14px;
}

.mb-4 {
    margin-bottom: 16px;
}

.action-buttons-bottom {
    display: flex;
    gap: 12px;
    margin-top: 16px;
}

.btn-md {
    padding: 10px 20px;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 600;
    border: none;
    cursor: pointer;
    font-family: inherit;
    color: white;
    transition: all 0.2s;
}

.btn-outline {
    background: white;
    border: 1px solid #d1d5db;
    color: #374151;
}

.btn-outline:hover:not(:disabled) {
    background: #f9fafb;
}

.btn-md:disabled {
    opacity: 0.7;
    cursor: not-allowed;
}
</style>