# Pada Backend rename file .env.example sehingga menjadi .env dan ubah mengikuti berikut :
- DB_CONNECTION=pgsql
- DB_HOST=127.0.0.1 (atau ubah menjadi "db" agar bisa running dari Docker)
- DB_PORT=5432
- DB_DATABASE= (isi dengan nama database kalian)
- DB_USERNAME=postgres
- DB_PASSWORD= (isi dengan password postgreeSQL kalian)

## Install Depedensi Backend Laravel
- **composer update**
- **php artisan migrate:fresh --seed**

## Install Depedensi Frontend Vue.js
- **npm install**

## Cara run yaitu dengan cara jalankan semua server baik Backend maupun Frontend
- **Backend** : php artisan serve
- **Frontend** : npm run dev
- Jalankan localhost milih Frontend

## Dokumentasi API
- **Tersedia di**: /backend/Tes_API.json
- **Cara pakai**: Import file tersebut ke aplikasi Postman, atur base_url pada environment

## Fitur Utama
- **Role-based Access Control**: Pemisahan fitur antara Admin dan User.
- **Dashboard User**: Monitoring sisa kuota cuti secara real-time.
- **Form Pengajuan Cuti**: Validasi otomatis tanggal dan simulasi sisa saldo sebelum submit.
- **Manajemen Admin**: Pengelolaan data user dan pemrosesan permohonan cuti (Approve/Reject).
- **Soft Delete System**: Data yang dihapus tetap tersimpan di database dengan audit trail (`deleted_at`, `deleted_by`).
- **State Machine Flow**: Alur permohonan (Pending -> Approved/Rejected/Cancelled).

## Leave Request Flow
**Sistem ini mengikuti alur status sebagai berikut**:
- **Pending**: Status awal saat diajukan.
- **Approved**: Disetujui Admin (Saldo berkurang).
- **Rejected**: Ditolak Admin (Saldo kembali).
- **Cancelled**: Dibatalkan User (Hanya bisa untuk status pending).
- **Soft Deleted**: Data dihapus dari list tapi tetap ada di DB untuk kebutuhan audit.

## Tech Stack
- **Backend** : Laravel 12
- **Frontend** : Vue.js 3 (Vite), Pinia, Tailwind CSS, TypeScript
- **Database** : PostgreSQL
- **Testing** : PHPUnit (Backend) & Vitest (Frontend)

## Running with Docker
**Aplikasi ini telah mendukung kontainerisasi menggunakan Docker. Pastikan Docker Desktop telah terpasang di komputer Anda**

**Pada file "docker-compose.yml" ubah nama database dan password database dengan milik kalian**

**Akses Aplikasi:**
- **Frontend: http://localhost:5173**
- **Backend API: http://localhost:8000**

**Langkah-langkah:**
- **Pastikan Anda berada di root folder project**
- **Jalankan Migrasi (wajib)**: docker-compose exec backend php artisan migrate:fresh --seed
- **Build dan Run**: docker-compose up --build

## Clone Repositori
- https://github.com/Rahmadani05/Aplikasi-Leave-Request-Management.git

## Akun Login
- **Email** : admin@energeek.id
- **Password** : tes123
