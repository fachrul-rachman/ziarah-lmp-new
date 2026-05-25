# 01 - Tech Stack and Implementation Rules

## Stack Wajib
Aplikasi wajib menggunakan:

1. Laravel.
2. PostgreSQL.
3. Inertia.js.
4. React.
5. TypeScript.
6. Tailwind CSS.
7. shadcn/ui atau komponen React setara yang rapi dan reusable.
8. Laravel Excel untuk import dan export Excel.
9. Laravel Scheduler untuk pekerjaan otomatis.
10. Laravel Queue untuk pekerjaan berat.
11. Laravel Mail dengan SMTP Mailgun dari awal.
12. PDF generator Laravel yang stabil untuk kebutuhan HTML-to-PDF.

## Timezone
Timezone aplikasi wajib `Asia/Jakarta`.

Semua validasi tanggal, scheduler, export H-1, completed otomatis, dan lock H+2 wajib memakai timezone ini.

## Storage
Semua file generated seperti Excel/PDF laporan disimpan di `storage/app`.

Tidak perlu cloud storage.

## Queue
Import lot dan generate laporan besar wajib diproses melalui queue/job.

Queue worker dianggap wajib aktif di production.

## Scheduler
Laravel Scheduler wajib dipakai untuk:

1. Mengirim notifikasi Discord H-1 sesuai jam di Setting.
2. Mengubah status booking menjadi `completed` setelah tanggal kunjungan lewat.

## Auth Admin
Admin login cukup email dan password.

Tidak perlu role, permission, register publik, atau forgot password.

## Public Customer
Customer tidak perlu login.

Customer mengakses detail booking, cancel, dan reschedule menggunakan token publik yang kuat dan tidak mudah ditebak.

## Token Publik
Public token wajib random panjang, tidak berurutan, tidak mudah ditebak, dan aman untuk link email.

Implementasi yang diterima:

1. UUID/ULID random.
2. Signed route Laravel.
3. Kombinasi token random dan signed URL.

Token tidak boleh berupa ID auto increment.

## Kode Booking
Kode booking hanya ditampilkan di dashboard admin.

Kode booking tidak dikirim ke Discord team.

Format kode booking:

`{PREFIX}-{YYYYMMDD}-{NORMALIZED_LOT}`

Prefix:

1. `Z` untuk `Ziarah`.
2. `NB` untuk `Naik Batu`.
3. `WS` untuk `Wang San`.
4. `SW` untuk `Start Work`.

Nomor lot pada kode booking wajib dinormalisasi agar aman sebagai kode, misalnya spasi dan simbol diganti dengan pemisah yang konsisten.

Jika booking di-reschedule, kode booking mengikuti data terbaru.

## Aturan Anti-Asumsi untuk Codex
Codex tidak boleh:

1. Menambah fitur payment.
2. Menambah login customer.
3. Menambah approval booking.
4. Mengubah status awal booking dari `confirmed`.
5. Mengirim notifikasi Discord jika tidak ada booking H-1.
6. Menghapus data booking lama.
7. Membuat import lot menghentikan semua proses hanya karena sebagian baris error.
8. Menyimpan file export di cloud.
9. Mengabaikan unique index untuk mencegah double booking.
10. Mengganti stack utama tanpa instruksi eksplisit.

## Struktur Implementasi yang Disarankan
Gunakan service layer untuk logika penting:

1. `BookingService`.
2. `BookingAvailabilityService`.
3. `RescheduleBookingService`.
4. `CancelBookingService`.
5. `LotImportService`.
6. `ExportReportService`.
7. `DiscordNotificationService`.
8. `EventRuleService`.
9. `BookingCodeService`.

Controller tidak boleh menjadi tempat utama business logic.
