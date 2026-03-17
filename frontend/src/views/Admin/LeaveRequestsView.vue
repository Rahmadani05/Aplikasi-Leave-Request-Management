<template>
  <div class="dashboard-layout">
    <aside class="sidebar">
      <div class="sidebar-header">
        <h2 class="brand-title">Leave<span class="brand-highlight">Hub</span></h2>
        <p class="brand-subtitle">Leave Request Management</p>
      </div>

      <div class="sidebar-menu">
        <p class="menu-label">ADMIN MENU</p>
        <router-link to="/login" class="menu-item">
          <i class="fas fa-key menu-icon"></i>
          Login
        </router-link>
        <router-link to="/" class="menu-item">
          <i class="fas fa-user menu-icon"></i>
          Kelola User
        </router-link>
        <router-link to="/leave-requests" class="menu-item active">
          <i class="fas fa-clipboard-list menu-icon"></i>
          Leave Requests
        </router-link>
      </div>

      <div class="sidebar-footer">
        <div class="user-profile">
          <div class="user-avatar">{{ getUserInitial() }}</div>
          <div class="user-info">
            <p class="user-name">{{ authStore.user?.name || 'Admin' }}</p>
            <p class="user-email">{{ authStore.user?.email || 'admin@energeek.id' }}</p>
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
          <h1 class="page-title">Semua Leave Request</h1>
          <p class="page-subtitle">Kelola dan respond permohonan cuti dari semua user.</p>
        </div>

        <div class="role-indicator">
          <span class="role-label">Role:</span>
          <button class="role-btn active">Admin</button>
          <button class="role-btn">User</button>
          <button class="role-btn show-all"><i class="fas fa-camera"></i> Show All</button>
        </div>
      </header>

      <div class="summary-cards">
        <div class="card">
          <div class="card-icon yellow-text">
            <i class="fas fa-hourglass-half"></i>
          </div>
          <div class="card-data">
            <p class="card-title">Pending</p>
            <h3 class="card-value yellow-text">{{ pendingCount }}</h3>
            <p class="card-desc">Menunggu keputusan</p>
          </div>
        </div>

        <div class="card">
          <div class="card-icon green-text">
            <i class="fas fa-check"></i>
          </div>
          <div class="card-data">
            <p class="card-title">Approved</p>
            <h3 class="card-value green-text">{{ approvedCount }}</h3>
            <p class="card-desc">Disetujui</p>
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

      <section class="table-section mb-32">
        <div class="table-header">
          <h3 class="table-title">Perlu Tindakan</h3>
          <span class="badge badge-yellow-light text-xs font-bold">{{ pendingRequests.length }} pending</span>
        </div>

        <div class="table-responsive">
          <table class="user-table">
            <thead>
              <tr>
                <th>USER</th>
                <th>TIPE</th>
                <th>TANGGAL</th>
                <th>HARI</th>
                <th>ALASAN</th>
                <th>STATUS</th>
                <th>AKSI</th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="pendingRequests.length === 0">
                <td colspan="7" class="text-center text-gray-500 py-8">
                  Tidak ada permohonan yang perlu tindakan.
                </td>
              </tr>
              <tr v-for="req in pendingRequests" :key="req.id">
                <td class="font-medium">{{ req.user?.name || 'User' }}</td>
                <td>{{ req.leave_type?.name || '-' }}</td>
                <td>{{ formatDateRange(req.start_date, req.end_date) }}</td>
                <td>{{ req.total_days }}</td>
                <td>{{ req.reason }}</td>
                <td><span class="badge badge-yellow">• pending</span></td>
                <td>
                  <div class="action-buttons">
                    <button @click="openActionForm('approved', req)" class="btn-sm btn-green">
                      Approve
                    </button>
                    <button @click="openActionForm('rejected', req)" class="btn-sm btn-red">
                      Reject
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </section>

      <div v-if="actionState.request" class="action-forms-container mb-32">
        <div v-if="actionState.type === 'approved'" class="action-card border-green">
          <h4 class="action-title">
            Approve Request — {{ actionState.request.user?.name || 'User' }}
          </h4>
          <p class="action-detail">
            <strong>{{ actionState.request.leave_type?.name || 'Cuti' }}</strong> :
            {{ formatDateRange(actionState.request.start_date, actionState.request.end_date) }} ({{
              actionState.request.total_days
            }}
            hari)
          </p>
          <p class="action-detail">Alasan: {{ actionState.request.reason }}</p>

          <div class="form-group mt-3">
            <label>Catatan Admin (opsional)</label>
            <textarea v-model="adminNotes" rows="2" placeholder="Approved, selamat berlibur."></textarea>
          </div>
          <div class="action-buttons-bottom">
            <button @click="submitAction" :disabled="isSubmitting" class="btn-md btn-green">
              Konfirmasi Approve
            </button>
            <button @click="closeActionForm" class="btn-md btn-outline">Batal</button>
          </div>
        </div>

        <div v-if="actionState.type === 'rejected'" class="action-card border-red">
          <h4 class="action-title red-text">
            Reject Request — {{ actionState.request.user?.name || 'User' }}
          </h4>
          <p class="action-detail">
            <strong>{{ actionState.request.leave_type?.name || 'Cuti' }}</strong> :
            {{ formatDateRange(actionState.request.start_date, actionState.request.end_date) }} ({{
              actionState.request.total_days
            }}
            hari)
          </p>
          <p class="action-detail mb-4">Alasan: {{ actionState.request.reason }}</p>

          <div class="form-group">
            <label>Catatan Admin (opsional)</label>
            <textarea v-model="adminNotes" rows="2" placeholder="Alasan penolakan..."></textarea>
          </div>
          <div class="action-buttons-bottom">
            <button @click="submitAction" :disabled="isSubmitting" class="btn-md btn-red">
              Konfirmasi Reject
            </button>
            <button @click="closeActionForm" class="btn-md btn-outline">Batal</button>
          </div>
        </div>
      </div>

      <section class="table-section">
        <div class="table-header">
          <h3 class="table-title">Riwayat Semua Request</h3>
        </div>

        <div class="table-responsive">
          <table class="user-table">
            <thead>
              <tr>
                <th>USER</th>
                <th>TIPE</th>
                <th>TANGGAL</th>
                <th>HARI</th>
                <th>STATUS</th>
                <th>DIRESPON</th>
                <th>CATATAN</th>
                <th>AKSI</th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="historyRequests.length === 0">
                <td colspan="8" class="text-center text-gray-500 py-8">Belum ada riwayat.</td>
              </tr>
              <tr v-for="req in historyRequests" :key="req.id">
                <td class="font-medium">{{ req.user?.name || 'User' }}</td>
                <td>{{ req.leave_type?.name || '-' }}</td>
                <td>{{ formatDateRange(req.start_date, req.end_date) }}</td>
                <td>{{ req.total_days }}</td>
                <td>
                  <span v-if="req.status === 'approved'" class="badge badge-green">• approved</span>
                  <span v-else-if="req.status === 'rejected'" class="badge badge-red-light">• rejected</span>
                  <span v-else class="badge badge-gray">• cancelled</span>
                </td>
                <td>{{ req.responded_at ? formatDate(req.responded_at) : '-' }}</td>
                <td>{{ req.admin_notes || '-' }}</td>
                <td>
                  <button @click="deleteRequest(req.id)" class="btn-sm-outline text-red">
                    Hapus
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </section>
    </main>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
// Pastikan path mundurnya benar (../../)
import { useAuthStore } from '../../stores/auth'
import api from '../../services/api'

const authStore = useAuthStore()
const requests = ref<any[]>([])

const actionState = ref<{ type: 'approved' | 'rejected' | null; request: any }>({
  type: null,
  request: null,
})
const adminNotes = ref('')
const isSubmitting = ref(false)

const handleLogout = async () => {
  await authStore.logout()
}

// Helper aman untuk inisial nama
const getUserInitial = () => {
  if (authStore.user && authStore.user.name) {
    return authStore.user.name.substring(0, 1).toUpperCase()
  }
  return 'A'
}

const fetchRequests = async () => {
  try {
    const response = await api.get('/leave-requests')
    if (response.data && response.data.data) {
      requests.value = response.data.data
    }
  } catch (error) {
    console.error('Failed to fetch requests', error)
  }
}

const pendingRequests = computed(() => requests.value.filter((r) => r.status === 'pending'))
const historyRequests = computed(() => requests.value.filter((r) => r.status !== 'pending'))

const pendingCount = computed(() => pendingRequests.value.length)
const approvedCount = computed(() => requests.value.filter((r) => r.status === 'approved').length)
const rejectedCount = computed(() => requests.value.filter((r) => r.status === 'rejected').length)

const openActionForm = (type: 'approved' | 'rejected', request: any) => {
  actionState.value = { type, request }
  adminNotes.value = ''
}

const closeActionForm = () => {
  actionState.value = { type: null, request: null }
  adminNotes.value = ''
}

const submitAction = async () => {
  if (!actionState.value.request) return
  isSubmitting.value = true

  try {
    await api.put(`/leave-requests/${actionState.value.request.id}/respond`, {
      status: actionState.value.type,
      admin_notes: adminNotes.value,
    })
    closeActionForm()
    fetchRequests()
  } catch (error) {
    alert('Terjadi kesalahan saat memproses request.')
  } finally {
    isSubmitting.value = false
  }
}

const deleteRequest = async (id: number) => {
  if (confirm('Yakin ingin menghapus history ini? Data akan di-soft delete.')) {
    try {
      await api.delete(`/leave-requests/${id}`)
      
      // Penting: Pastikan memanggil fungsi fetch data yang ada di halaman ini
      // Di halaman Admin biasanya namanya fetchRequests()
      await fetchRequests() 
      
      alert('Data berhasil dihapus (Soft Delete).')
    } catch (error: any) {
      // Menangkap pesan proteksi status dari backend
      const errorMsg = error.response?.data?.message || 'Gagal menghapus data.'
      alert(errorMsg)
    }
  }
}

// Helper tanggal yang tahan error
const formatDate = (dateString: string) => {
  if (!dateString) return '-'
  try {
    const date = new Date(dateString)
    return date.toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' })
  } catch (e) {
    return dateString
  }
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
    if (
      startDate.getMonth() === endDate.getMonth() &&
      startDate.getFullYear() === endDate.getFullYear()
    ) {
      return `${startDay} - ${endDay} ${month} ${year}`
    }
    return `${formatDate(start)} - ${formatDate(end)}`
  } catch (e) {
    return `${start} - ${end}`
  }
}

onMounted(() => {
  fetchRequests()
})
</script>

<style scoped>
/* CSS Sama persis dengan sebelumnya, meniru mockup yang rapi */
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
}

.user-avatar {
  width: 36px;
  height: 36px;
  background-color: #6366f1;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: bold;
  margin-right: 12px;
}

.user-info .user-name {
  margin: 0;
  font-size: 14px;
  font-weight: 600;
}

.user-info .user-email {
  margin: 0;
  font-size: 12px;
  color: #9ca3af;
}

.btn-logout {
  background: transparent;
  border: none;
  color: #9ca3af;
  cursor: pointer;
  padding: 8px;
}

.btn-logout:hover {
  color: #ef4444;
}

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

.summary-cards {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 24px;
  margin-bottom: 32px;
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

.text-blue {
  color: #3b82f6;
}

.mt-3 {
  margin-top: 12px;
}

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

.badge-yellow-light {
  background-color: #fef3c7;
  color: #b45309;
  border: 1px solid #fde68a;
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

.action-buttons {
  display: flex;
  gap: 8px;
}

.btn-sm {
  padding: 6px 12px;
  border-radius: 6px;
  font-size: 13px;
  font-weight: 600;
  border: none;
  cursor: pointer;
  font-family: inherit;
  color: white;
}

.btn-green {
  background-color: #10b981;
}

.btn-green:hover:not(:disabled) {
  background-color: #059669;
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
}

.action-forms-container {
  display: flex;
  flex-direction: column;
  gap: 16px;
  margin-top: 16px;
}

.action-card {
  background: white;
  padding: 24px;
  border-radius: 12px;
  border-left: 4px solid;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
  max-width: 600px;
}

.border-green {
  border-left-color: #10b981;
}

.border-red {
  border-left-color: #ef4444;
}

.action-title {
  margin: 0 0 12px 0;
  font-size: 18px;
  color: #111827;
  font-weight: 700;
}

.action-detail {
  margin: 0 0 8px 0;
  color: #374151;
  font-size: 14px;
}

.form-group label {
  display: block;
  margin-bottom: 8px;
  font-size: 14px;
  font-weight: 600;
  color: #374151;
}

.form-group textarea {
  width: 100%;
  padding: 12px;
  border: 1px solid #d1d5db;
  border-radius: 8px;
  font-family: inherit;
  font-size: 14px;
  margin-bottom: 16px;
  resize: vertical;
}

.form-group textarea:focus {
  outline: none;
  border-color: #6366f1;
}

.action-buttons-bottom {
  display: flex;
  gap: 12px;
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
}

.btn-outline {
  background: white;
  border: 1px solid #d1d5db;
  color: #374151;
}

.btn-outline:hover {
  background: #f9fafb;
}

.btn-md:disabled {
  opacity: 0.7;
  cursor: not-allowed;
}
</style>
