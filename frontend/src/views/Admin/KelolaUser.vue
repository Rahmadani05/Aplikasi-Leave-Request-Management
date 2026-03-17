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
        <router-link to="/" class="menu-item active">
          <i class="fas fa-user menu-icon"></i>
          Kelola User
        </router-link>
        <router-link to="/leave-requests" class="menu-item">
          <i class="fas fa-clipboard-list menu-icon"></i>
          Leave Requests
        </router-link>
      </div>

      <div class="sidebar-footer">
        <div class="user-profile">
          <div class="user-avatar">{{ authStore.user?.name?.charAt(0) || 'A' }}</div>
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
          <h1 class="page-title">Kelola User</h1>
          <p class="page-subtitle">Buat dan kelola akun user. Maksimal 2 user.</p>
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
          <div class="card-icon blue-bg">
            <i class="fas fa-user"></i>
          </div>
          <div class="card-data">
            <p class="card-title">Total User</p>
            <h3 class="card-value blue-text">{{ users.length }}</h3>
            <p class="card-desc">Maks. 2 user</p>
          </div>
        </div>

        <div class="card">
          <div class="card-icon green-bg">
            <i class="fas fa-check"></i>
          </div>
          <div class="card-data">
            <p class="card-title">Slot Tersedia</p>
            <h3 class="card-value green-text">{{ 2 - users.length }}</h3>
            <p class="card-desc">{{ users.length >= 2 ? 'Kuota penuh' : 'Slot tersedia' }}</p>
          </div>
        </div>
      </div>

      <section class="table-section">
        <div class="table-header">
          <h3 class="table-title">Daftar User</h3>
          <button class="btn-primary" :disabled="users.length >= 2" @click="openAddUserModal">
            + Tambah User
          </button>
        </div>

        <div class="table-responsive">
          <table class="user-table">
            <thead>
              <tr>
                <th>NAMA</th>
                <th>EMAIL</th>
                <th>ANNUAL LEAVE</th>
                <th>SICK LEAVE</th>
                <th>AKSI</th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="users.length === 0">
                <td colspan="5" class="text-center">Belum ada data user. Silakan tambah user baru.</td>
              </tr>
              <tr v-for="user in users" :key="user.id">
                <td class="font-medium">{{ user.name }}</td>
                <td>{{ user.email }}</td>
                <td>{{ getLeaveBalance(user, 'Annual Leave') }} hari</td>
                <td>{{ getLeaveBalance(user, 'Sick Leave') }} hari</td>
                <td>
                  <button class="btn-outline">Update Password</button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </section>
    </main>

    <div v-if="showAddModal" class="modal-overlay">
      <div class="modal-card">
        <div class="modal-header">
          <h3 class="modal-title">Tambah User Baru</h3>
          <button class="btn-close" @click="closeAddUserModal">&times;</button>
        </div>
        <p class="modal-subtitle">Leave balance otomatis ter-assign saat user dibuat</p>

        <form @submit.prevent="submitAddUser">
          <div v-if="modalError" class="error-alert">{{ modalError }}</div>

          <div class="form-group">
            <label>Nama Lengkap</label>
            <input type="text" v-model="formUser.name" placeholder="Budi Santoso" required />
          </div>

          <div class="form-row">
            <div class="form-group half-width">
              <label>Email</label>
              <input type="email" v-model="formUser.email" placeholder="budi@energeek.id" required />
            </div>
            <div class="form-group half-width">
              <label>Password</label>
              <input type="password" v-model="formUser.password" placeholder="Min 8 karakter" required minlength="8" />
            </div>
          </div>

          <div class="info-box">
            <i class="fas fa-info-circle"></i> Auto-assign: Annual Leave (12 hari), Sick Leave (6 hari)
          </div>

          <div class="modal-actions">
            <button type="submit" class="btn-primary" :disabled="isSubmitting">
              {{ isSubmitting ? 'Menyimpan...' : 'Simpan User' }}
            </button>
            <button type="button" class="btn-outline" @click="closeAddUserModal" :disabled="isSubmitting">
              Batal
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useAuthStore } from '../../stores/auth'
import api from '../../services/api'

const authStore = useAuthStore()
const users = ref<any[]>([])

// State untuk Modal Tambah User
const showAddModal = ref(false)
const isSubmitting = ref(false)
const modalError = ref('')
const formUser = ref({ name: '', email: '', password: '' })

const handleLogout = async () => {
  await authStore.logout()
}

const fetchUsers = async () => {
  try {
    const response = await api.get('/users')
    users.value = response.data.data
  } catch (error) {
    console.error('Gagal mengambil data user:', error)
  }
}

const getLeaveBalance = (user: any, typeName: string) => {
  if (!user.leave_balances) return '0 / 0'
  const balance = user.leave_balances.find((b: any) => b.leave_type.name === typeName)
  if (!balance) return '0 / 0'
  const remaining = balance.total_quota - balance.used
  return `${remaining} / ${balance.total_quota}`
}

// Logika Modal
const openAddUserModal = () => {
  showAddModal.value = true
  modalError.value = ''
  formUser.value = { name: '', email: '', password: '' }
}

const closeAddUserModal = () => {
  showAddModal.value = false
}

const submitAddUser = async () => {
  isSubmitting.value = true
  modalError.value = ''

  try {
    await api.post('/users', formUser.value)
    closeAddUserModal()
    fetchUsers() // Refresh tabel setelah berhasil ditambah
  } catch (error: any) {
    // Menangkap error validasi spesifik dari Laravel
    if (error.response?.status === 422 && error.response.data.errors) {
      const errs = error.response.data.errors
      modalError.value = Object.values(errs).flat().join(' ')
    } else {
      modalError.value = error.response?.data?.message || 'Gagal menambahkan user.'
    }
  } finally {
    isSubmitting.value = false
  }
}

onMounted(() => {
  fetchUsers()
})
</script>

<style scoped>
/* Semua style dari versi sebelumnya tetap dipertahankan */
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
  transition: color 0.2s;
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
  grid-template-columns: 1fr 1fr;
  gap: 24px;
  margin-bottom: 32px;
}

.card {
  background: white;
  border-radius: 12px;
  padding: 24px;
  display: flex;
  align-items: flex-start;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
  border: 1px solid #e5e7eb;
}

.card-icon {
  width: 48px;
  height: 48px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 20px;
  margin-right: 16px;
}

.blue-bg {
  background-color: #e0e7ff;
  color: #6366f1;
}

.green-bg {
  background-color: #dcfce7;
  color: #22c55e;
}

.card-title {
  margin: 0 0 4px 0;
  color: #6b7280;
  font-size: 14px;
}

.card-value {
  margin: 0 0 4px 0;
  font-size: 32px;
  font-weight: 700;
}

.blue-text {
  color: #6366f1;
}

.green-text {
  color: #22c55e;
}

.card-desc {
  margin: 0;
  color: #9ca3af;
  font-size: 13px;
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
}

.btn-primary {
  background-color: #6366f1;
  color: white;
  border: none;
  padding: 8px 16px;
  border-radius: 6px;
  font-weight: 600;
  cursor: pointer;
  font-family: inherit;
}

.btn-primary:disabled {
  background-color: #9ca3af;
  cursor: not-allowed;
}

.user-table {
  width: 100%;
  border-collapse: collapse;
  text-align: left;
}

.user-table th {
  background-color: #f9fafb;
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

.btn-outline {
  background: white;
  border: 1px solid #d1d5db;
  color: #374151;
  padding: 6px 12px;
  border-radius: 6px;
  font-size: 13px;
  cursor: pointer;
  font-family: inherit;
  transition: all 0.2s;
}

.btn-outline:hover {
  background: #f9fafb;
  border-color: #9ca3af;
}

.text-center {
  text-align: center;
}

/* --- MODAL STYLES --- */
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background-color: rgba(0, 0, 0, 0.5);
  display: flex;
  justify-content: center;
  align-items: center;
  z-index: 1000;
}

.modal-card {
  background: white;
  width: 100%;
  max-width: 500px;
  border-radius: 12px;
  padding: 30px;
  box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 8px;
}

.modal-title {
  margin: 0;
  font-size: 20px;
  color: #111827;
  font-weight: 700;
}

.btn-close {
  background: transparent;
  border: none;
  font-size: 24px;
  color: #9ca3af;
  cursor: pointer;
  line-height: 1;
}

.btn-close:hover {
  color: #ef4444;
}

.modal-subtitle {
  margin: 0 0 24px 0;
  color: #6b7280;
  font-size: 14px;
}

.form-group {
  margin-bottom: 16px;
}

.form-row {
  display: flex;
  gap: 16px;
}

.half-width {
  flex: 1;
}

.form-group label {
  display: block;
  margin-bottom: 8px;
  font-size: 14px;
  font-weight: 600;
  color: #374151;
}

.form-group input {
  width: 100%;
  padding: 10px 14px;
  border: 1px solid #d1d5db;
  border-radius: 6px;
  font-family: inherit;
  font-size: 15px;
  box-sizing: border-box;
}

.form-group input:focus {
  outline: none;
  border-color: #6366f1;
}

.info-box {
  background-color: #e0e7ff;
  color: #4338ca;
  padding: 12px;
  border-radius: 6px;
  font-size: 13px;
  margin-bottom: 24px;
  display: flex;
  align-items: center;
  gap: 8px;
}

.modal-actions {
  display: flex;
  gap: 12px;
}

.error-alert {
  background-color: #fee2e2;
  color: #b91c1c;
  padding: 10px;
  border-radius: 6px;
  margin-bottom: 16px;
  font-size: 14px;
}
</style>