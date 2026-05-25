# TASKS - Aplikasi Booking Ziarah

## Cara Pakai
Kerjakan modul secara berurutan. Setiap modul harus bisa dijalankan dan dicoba di browser sebelum lanjut. Checklist item setelah selesai dikerjakan. Jangan menambah fitur di luar yang tertulis.

---

## Modul 1 — Foundation

### Checklist

#### 1.1 Cek dan Setup Stack
- [x] Cek apakah Inertia.js sudah terpasang. Jika belum, install `inertiajs/inertia-laravel` dan setup server-side adapter.
- [x] Cek apakah React + TypeScript sudah terpasang. Jika belum, install dan konfigurasi.
- [x] Cek apakah Tailwind CSS sudah terpasang. Jika belum, install dan konfigurasi.
- [x] Install shadcn/ui dan setup komponen dasar: Button, Input, Select, Dialog, Table, Card.
- [x] Install `maatwebsite/excel`.
- [x] Install `barryvdh/laravel-dompdf`.
- [x] Install `guzzlehttp/guzzle`.
- [x] Set timezone ke `Asia/Jakarta` di `config/app.php`.
- [x] Set database ke PostgreSQL di `.env` dan `config/database.php`.
- [x] Konfigurasi Laravel Mail driver Mailgun via `.env`. Key wajib: `MAIL_MAILER`, `MAILGUN_DOMAIN`, `MAILGUN_SECRET`, `MAIL_FROM_ADDRESS`, `MAIL_FROM_NAME`.
- [x] Setup queue driver `database`. Jalankan `php artisan queue:table` jika belum ada.
- [x] Setup font Inter di Tailwind config. Body minimal 16px.

#### 1.2 Migration Semua Tabel
- [x] `users`
- [x] `locations` — unique index case-insensitive pada `lower(name)`
- [x] `zones` — unique index case-insensitive pada `location_id + lower(name)`
- [x] `lots` — unique index pada `grave_type + location_id + zone_id + normalized_lot_number`, soft delete
- [x] `time_slots`
- [x] `events`
- [x] `event_locations`
- [x] `event_hidden_facilities`
- [x] `bookings` — partial unique index pada `visit_date + time_slot_id + lot_id` WHERE `status IN ('confirmed', 'rescheduled')`
- [x] `booking_facilities`
- [x] `booking_reschedule_histories`
- [x] `settings`
- [x] `import_jobs`
- [x] `import_job_errors`
- [x] `notification_logs`
- [x] Jalankan semua migration, pastikan tidak ada error.

#### 1.3 Seeder
- [x] `AdminSeeder`: 1 user admin, email `admin@ziarah.test`, password `password`, name `Admin`.
- [x] `DummyDataSeeder`:
  - 3 lokasi: `Pemakaman Barat`, `Pemakaman Timur`, `Pemakaman Utara`.
  - Tiap lokasi: 2 zona (`Zona A`, `Zona B`).
  - Tiap zona: 10 lot, campuran `makam` dan `kotak_abu`, ukuran bervariasi `Single`, `Double`, `Family`.
  - 5 time slot global: `08:00`, `10:00`, `13:00`, `15:00`, `19:00`.
  - 2 setting: `discord_webhook_url` (string kosong), `discord_notification_time` (`08:00`).
- [x] `DatabaseSeeder` urutan: `AdminSeeder` → `DummyDataSeeder`.
- [x] Jalankan seeder, pastikan data masuk ke database.

#### 1.4 Layout Admin dan Auth
- [x] Buat layout admin dengan sidebar collapse:
  - Desktop: sidebar collapse ke icon-only.
  - Mobile: sidebar hidden, buka via hamburger.
  - Sidebar items: Dashboard, Lokasi dan Lot, Time Slots, Event, Setting.
  - Warna sidebar: background `#202938`, text putih, active item accent `#D4AF37`.
  - Background halaman: `#F8F9FB`.
  - Tombol selalu punya label teks, tidak icon-only.
- [x] Auth guard: semua route `/admin/*` wajib login, redirect ke `/admin/login` jika belum.
- [x] Halaman login `/admin/login`: form email + password. Tidak ada register, forgot password.
- [x] Logout: tombol logout di sidebar/header.
- [x] Placeholder halaman untuk setiap item sidebar (boleh hanya judul halaman, akan diisi modul berikutnya).

### Cara Coba Modul 1
1. Buka `/admin/login` → login dengan `admin@ziarah.test` / `password`.
2. Pastikan sidebar muncul dengan semua menu.
3. Pastikan logout berfungsi.
4. Cek database — semua tabel ada, seeder data masuk.

---

## Modul 2 — Time Slot

### Checklist

#### 2.1 CRUD Time Slot
- [x] Halaman list `/admin/time-slots`: tampilkan jam mulai dan jam selesai (mulai + 59 menit).
- [x] Tambah time slot: input jam mulai saja (format `HH:MM`).
- [x] Bulk generate time slot: input range jam mulai–jam akhir, step 60 menit (contoh 00:00–23:00).
- [x] Preset: tombol generate `24 jam` (00:00–23:00).
- [x] Hapus time slot: tolak jika dipakai booking aktif (`confirmed` atau `rescheduled`), tampilkan pesan error jelas.
- [x] Tidak perlu fitur edit.
- [x] Empty state: teks `Belum ada time slot. Tambah time slot baru.`
- [x] Validasi: format jam valid, tidak boleh duplikat.

### Cara Coba Modul 2
1. Login admin → buka Time Slots.
2. Tambah time slot `07:00` → muncul di list sebagai `07:00 - 07:59`.
3. Coba tambah `07:00` lagi → gagal dengan pesan duplikat.
4. Hapus `07:00` → berhasil.
5. Data seeder `08:00`, `10:00`, `13:00`, `15:00`, `19:00` sudah ada dari awal.

---

## Modul 3 — Lokasi, Zona, dan Lot

### Checklist

#### 3.1 CRUD Lokasi
- [x] Halaman list `/admin/locations`.
- [x] Tambah lokasi (nama saja).
- [x] Edit nama lokasi.
- [x] Hapus lokasi: tolak jika masih punya zona/lot aktif.
- [x] Empty state: teks `Belum ada lokasi.`

#### 3.2 CRUD Zona
- [x] Halaman list zona per lokasi `/admin/locations/{location}/zones`.
- [x] Tambah zona.
- [x] Edit nama zona.
- [x] Hapus zona: tolak jika masih punya lot aktif.

#### 3.3 CRUD Lot
- [x] Halaman list `/admin/lots` dengan filter: lokasi, zona, jenis makam.
- [x] Tambah lot manual: pilih lokasi, zona, jenis makam, nomor lot, ukuran.
- [x] Edit lot.
- [x] Hapus lot: soft delete jika lot pernah dipakai booking.
- [x] Bulk delete lot yang dipilih.
- [x] Empty state: teks `Belum ada lot.`
- [x] Validasi: `grave_type` wajib `makam`/`kotak_abu`, `lot_number` dan `size` wajib isi, unique key tidak boleh duplikat.

#### 3.4 Import Excel Lot
- [x] Tombol upload Excel di halaman lot.
- [x] Kolom Excel input: `jenis_makam`, `lokasi`, `zona`, `no_lot`, `ukuran`.
- [x] Proses via queue/job dengan Laravel Excel (chunk reading + read filter) tanpa OOM untuk 1000+ baris.
- [x] Upsert berdasarkan `grave_type + location_id + zone_id + normalized_lot_number`.
- [x] Auto-create lokasi dan zona jika belum ada.
- [x] Baris invalid dicatat ke `import_job_errors`, tidak menghentikan batch lain.
- [x] Ringkasan disimpan ke `import_jobs`.
- [x] Halaman admin menampilkan progress/status job (polling).
- [x] Normalisasi: trim, collapse spaces, `normalized_lot_number` lowercase.
- [x] Larangan: jangan hapus booking lama, jangan hapus lot tidak ada di Excel.

### Cara Coba Modul 3
1. Buka Lokasi → data seeder sudah ada. Tambah lokasi baru → muncul.
2. Edit nama lokasi → berubah.
3. Buka zona salah satu lokasi → data seeder ada. Tambah zona baru.
4. Buka Lot → data seeder ada. Tambah lot manual, edit, hapus.
5. Upload Excel dengan beberapa baris valid dan 1 baris invalid → baris valid masuk, baris invalid tercatat di error log.
6. Upload Excel 1000+ baris → queue worker tidak OOM, progress selesai sampai status `completed`.

---

## Modul 4 — Event

### Checklist

#### 4.1 CRUD Event
- [x] Halaman list `/admin/events`: tampilkan nama, tanggal mulai, tanggal selesai, lokasi terdampak.
- [x] Tambah event: nama, tanggal mulai, tanggal selesai, pilih minimal 1 lokasi (multi-select), pilih fasilitas yang disembunyikan (checkbox: Kursi, Tong Bakar, Tenda, Meja Sembahyang, Lampu).
- [x] Edit event.
- [x] Hapus event.
- [x] Empty state: teks `Belum ada event.`
- [x] Validasi:
  - Nama wajib isi.
  - Tanggal mulai dan selesai wajib isi.
  - Tanggal selesai >= tanggal mulai.
  - Minimal 1 lokasi wajib dipilih — jika tidak ada, simpan gagal dengan pesan error jelas.
- [x] Event boleh overlap.

### Cara Coba Modul 4
1. Tambah event tanpa lokasi → gagal dengan pesan error.
2. Tambah event valid dengan 1 lokasi dan beberapa fasilitas disembunyikan → berhasil.
3. Edit event → data berubah.
4. Hapus event → hilang dari list.

---

## Modul 5 — Customer Form dan Booking Submit

### Checklist

#### 5.1 Stepper Form Customer
- [x] Route `/` menampilkan form stepper 4 step.
- [x] **Step 1 — Lokasi & Waktu**:
  - Pilih jenis kegiatan: `Ziarah`, `Naik Batu`, `Start Work`, `Wang San`.
  - Pilih lokasi.
  - Pilih jenis makam: `Makam`, `Kotak Abu`.
  - Pilih zona (filter by lokasi + jenis makam).
  - Pilih tanggal: minimal H+2 timezone `Asia/Jakarta`, tanggal sebelum H+2 disabled.
  - Pilih jam dari time slot global.
  - Dependency reset: lokasi berubah → reset zona, lot. Jenis makam berubah → reset zona, lot. Zona berubah → reset lot. Tanggal/jam berubah → refresh lot availability.
- [x] **Step 2 — Pilih Lot**:
  - Lot tampil sebagai chip/list.
  - Filter: lokasi + zona + jenis makam + tanggal + jam, tidak sedang dipakai booking aktif.
  - Search/filter chip tersedia.
  - Customer tidak boleh submit teks lot manual.
  - Empty state: `Tidak ada lot tersedia untuk pilihan ini.`
- [x] **Step 3 — Fasilitas**:
  - Kursi: number input, min 5, max 10.
  - Tong bakar: number input, min 0, max 2.
  - Tenda: checkbox boolean.
  - Meja sembahyang: checkbox boolean.
  - Lampu: hanya tampil jika jam `>= 19:00` atau `<= 03:00`. Di luar rentang: hidden, nilai false.
  - Jika event aktif pada tanggal + lokasi: sembunyikan fasilitas sesuai event. Overlap event: gabungkan semua hidden facilities.
  - Jika tanggal/lokasi berubah dan event baru menyembunyikan fasilitas yang sudah dipilih: reset fasilitas tersebut ke false/0.
- [x] **Step 4 — Data Diri**:
  - Input nama (wajib).
  - Input email (wajib, format valid).
  - Tombol Submit Booking.

#### 5.2 Booking Submit
- [x] Validasi semua input server-side.
- [x] Database transaction wajib.
- [x] Cek availability lot saat submit (tangani race condition via unique index).
- [x] Simpan booking status `confirmed`.
- [x] Simpan `booking_facilities`.
- [x] Generate `public_token` (UUID/ULID random, bukan ID berurutan).
- [x] Generate `booking_code`: format `{PREFIX}-{YYYYMMDD}-{NORMALIZED_LOT}`.
  - Prefix: `Z` Ziarah, `NB` Naik Batu, `WS` Wang San, `SW` Start Work.
  - `YYYYMMDD` dari `visit_date`.
  - `NORMALIZED_LOT` dari `normalized_lot_number`, spasi/simbol diganti `-`.
- [ ] Kirim email booking berhasil via queue.
- [x] Redirect ke `/booking/success/{public_token}`.
- [x] Jika unique index conflict: tampilkan pesan `Lot sudah tidak tersedia untuk tanggal dan jam tersebut.`

#### 5.3 Halaman Booking Berhasil
- [x] Route `/booking/success/{public_token}`.
- [x] Tampilkan: jenis kegiatan, lokasi, jenis makam, zona, nomor lot, tanggal, jam, fasilitas, nama, email.
- [ ] Tombol download PDF customer.

#### 5.4 Email Booking Berhasil
- [ ] Bahasa Indonesia.
- [ ] Berisi: detail booking lengkap, note hardcoded (4 poin), tombol `Cancel Booking`, tombol `Reschedule Booking`.
- [ ] Link cancel dan reschedule pakai `public_token`.
- [ ] Kirim via queue.

#### 5.5 PDF Customer
- [ ] Berisi: jenis kegiatan, lokasi, jenis makam, zona, nomor lot, tanggal, jam, fasilitas, nama, email, note customer.
- [ ] Download tersedia dari halaman booking berhasil.

#### 5.6 Service Layer
- [x] `BookingService` — orchestrate booking submit.
- [x] `BookingAvailabilityService` — cek ketersediaan lot.
- [x] `BookingCodeService` — generate booking code.
- [x] `EventRuleService` — ambil hidden facilities berdasarkan tanggal + lokasi.

### Cara Coba Modul 5
1. Buka `/` → form stepper muncul.
2. Isi step 1, pilih lokasi, tanggal H+2, jam. Coba pilih tanggal H+1 → disabled.
3. Step 2: lot muncul sebagai chip. Pilih satu.
4. Step 3: isi fasilitas. Coba pilih jam `08:00` → lampu tidak muncul. Coba jam `19:00` → lampu muncul.
5. Step 4: isi nama dan email, submit.
6. Halaman booking berhasil muncul dengan detail dan tombol download PDF.
7. Cek email masuk (atau cek queue job di database jika Mailgun belum dikonfigurasi).
8. Coba submit lot yang sama di waktu yang sama dari tab berbeda → hanya 1 berhasil.

---

## Modul 6 — Cancel dan Reschedule Customer

### Checklist

#### 6.1 Halaman Detail Public Token
- [x] Route `/booking/{public_token}`.
- [x] Tampilkan semua detail booking.
- [x] Jika tanggal kunjungan sudah lewat: detail tetap tampil, tombol cancel/reschedule disabled dengan teks `Masa berlaku aksi sudah habis.`
- [x] Tombol download PDF customer tersedia.

#### 6.2 Cancel Customer
- [x] Route `/booking/{public_token}/cancel`.
- [x] Tampilkan detail booking.
- [x] Input alasan cancel wajib isi.
- [x] Dialog konfirmasi sebelum cancel.
- [x] Ubah status menjadi `cancelled`, simpan `cancel_reason`.
- [x] Lot langsung tersedia kembali.
- [x] Redirect ke halaman berhasil cancel.
- [x] Tidak perlu email konfirmasi cancel.
- [x] Tolak jika tanggal kunjungan sudah lewat.

#### 6.3 Reschedule Customer
- [x] Route `/booking/{public_token}/reschedule`.
- [x] Form reschedule pakai stepper 4 step yang sama, data lama sebagai default.
- [x] Customer boleh ubah: tanggal, jam, lokasi, jenis makam, zona, lot, fasilitas, nama, email.
- [x] Tanggal baru wajib minimal H+2 dari hari reschedule.
- [x] Submit reschedule:
  - [x] Validasi server-side.
  - [x] Database transaction.
  - [x] Cek availability lot baru.
  - [x] Update record booking yang sama.
  - [x] Ubah status menjadi `rescheduled`.
  - [x] Simpan histori ke `booking_reschedule_histories` (semua field old dan new).
  - [x] Update `booking_code` ikut data terbaru. Prefix tetap dari `activity_type` awal.
  - [x] Kirim email ulang via queue.
- [x] Redirect ke `/booking/{public_token}`.
- [x] Tolak jika tanggal kunjungan lama sudah lewat.

#### 6.4 Service Layer
- [x] `RescheduleBookingService`.
- [x] `CancelBookingService`.

### Cara Coba Modul 6
1. Dari email atau halaman booking berhasil, buka link detail booking.
2. Klik Cancel → wajib isi alasan → konfirmasi → status berubah `cancelled`.
3. Buat booking baru → klik Reschedule → ubah tanggal ke H+3 → submit → status `rescheduled`, histori tercatat.
4. Cek email reschedule masuk.
5. Coba reschedule booking yang tanggalnya sudah lewat → ditolak.

---

## Modul 7 — Admin Dashboard

### Checklist

#### 7.1 Tabel Booking
- [x] Route `/admin/dashboard`.
- [x] Kolom: Nama, Jenis Kegiatan, Lokasi / Zona / Lot, Tanggal dan Jam, Fasilitas, Status, Aksi.
- [x] Aksi per baris: Lihat Detail, Cancel Booking.
- [x] Filter: Tanggal, Jenis Kegiatan, Lokasi, Zona, Status.
- [x] Pagination: 25 data per halaman.
- [x] Di mobile: tabel berubah menjadi card list.
- [x] Empty state: `Tidak ada booking ditemukan.`

#### 7.2 Detail Booking Admin
- [x] Route `/admin/bookings/{id}`.
- [x] Tampilkan: booking code, nama, email, jenis kegiatan, jenis makam, lokasi, zona, nomor lot, tanggal, jam, fasilitas, status, alasan cancel (jika ada), histori reschedule (jika ada).

#### 7.3 Cancel Booking Admin
- [x] Cancel tersedia dari dashboard dan halaman detail.
- [x] Dialog konfirmasi wajib tampil.
- [x] Admin cancel tidak perlu alasan.
- [x] Admin cancel tidak kirim email.
- [x] Ubah status `cancelled`, lot tersedia kembali.

#### 7.4 Export Manual
- [x] Tombol export di halaman dashboard, berdasarkan filter aktif.
- [x] Admin pilih format: Excel atau PDF.
- [x] Format mengikuti contoh: `.md/contoh_file_export_from_web.xlsx`.
- [x] Format kolom: Nomor, Jenis Kegiatan, Jam, Lokasi, Nama, Jenis Makam, Zona, No. Lot, Tenda, Kursi, Tong Bakar, Meja Sembahyang, Lampu.
- [x] Diurutkan per zona (per section).
- [x] PDF landscape.
- [x] Export besar via queue/job.
- [x] `ExportReportService` dibuat di sini.

### Cara Coba Modul 7
1. Login admin → Dashboard → booking dari modul 5 & 6 muncul.
2. Filter by status `confirmed` → hanya confirmed tampil.
3. Buka detail booking → semua info tampil termasuk booking code.
4. Cancel booking dari admin → status berubah, tidak ada email terkirim.
5. Export Excel dan PDF → file ter-download dengan format benar.

---

## Modul 8 — Discord dan Scheduler

### Checklist

#### 8.1 Completed Scheduler
- [x] Command scheduler berjalan **tiap menit**.
- [x] Ambil booking `confirmed` atau `rescheduled` dengan `visit_date` < hari ini (timezone `Asia/Jakarta`).
- [x] Ubah status menjadi `completed`.
- [x] Booking `cancelled` tidak diubah.

#### 8.2 Discord Notification H-1
- [x] Scheduler berjalan harian, jam dari setting `discord_notification_time` (timezone `Asia/Jakarta`) — cek jam pakai `HH:MM` (abaikan detik).
- [x] Target tanggal: hari ini + 1.
- [x] Ambil booking status `confirmed` dan `rescheduled` untuk target tanggal.
- [x] Jika tidak ada booking: jangan kirim apa pun.
- [x] Buat file per kategori:
  - Ziarah: `activity_type = ziarah`.
  - Kegiatan: `activity_type` = `naik_batu`, `start_work`, atau `wang_san`.
- [x] Tiap kategori yang ada datanya: buat Excel dan PDF (landscape, urut per zona).
- [x] Kirim ke Discord webhook. Attach hanya file yang ada datanya.
- [x] Summary di pesan Discord per kategori: judul, tanggal, total booking, total kursi, tenda, meja sembahyang, tong bakar, lampu.
- [x] Catat hasil ke `notification_logs`.
- [x] Proses generate file via queue/job.
- [x] `DiscordNotificationService` dibuat di sini.

### Cara Coba Modul 8
1. Buat booking dengan tanggal kemarin (langsung via seeder/tinker) → jalankan scheduler → status berubah `completed`.
2. Buat booking dengan tanggal besok → set `discord_notification_time` ke 1 menit dari sekarang → pastikan Discord menerima pesan dan file.
3. Jika tidak ada booking besok → pastikan Discord tidak menerima apa pun.

---

## Modul 9 — Setting

### Checklist

#### 9.1 Halaman Setting Admin
- [x] Route `/admin/settings`.
- [x] Field: Discord Webhook URL (text, boleh kosong), Jam kirim notifikasi Discord (time, format `HH:MM`).
- [x] Simpan ke `settings` via upsert key-value.
- [x] Validasi: jam wajib format `HH:MM` valid. URL jika diisi wajib format URL valid.
- [x] Tampilkan pesan sukses setelah simpan.

#### 9.2 Aturan Fasilitas per Ukuran Lot (Size)
- [x] Normalisasi ukuran lot: case-insensitive untuk lookup (`normalized_size`), tampilkan Title Case (`size`).
- [x] Admin bisa atur rule per ukuran (min/max fasilitas, allow/disable per fasilitas).
- [x] Fallback rule ke global default jika ukuran belum ada rule.
- [x] Enforcement dilakukan di service saat submit & reschedule booking.
- [x] UI booking & reschedule: fasilitas yang tidak diizinkan hilang, dan batas min/max mengikuti rule.

### Cara Coba Modul 9
1. Buka Setting → isi Discord webhook URL dan jam kirim → simpan → pesan sukses muncul.
2. Refresh halaman → nilai tersimpan masih ada.
3. Isi jam dengan format salah → validasi error muncul.
4. Atur aturan ukuran lot di Setting → simpan → buka form booking dan pastikan batas & fasilitas mengikuti ukuran lot yang dipilih.

---

## Catatan Implementasi Wajib

1. Semua service layer wajib dibuat sesuai modul masing-masing: `BookingService`, `BookingAvailabilityService`, `RescheduleBookingService`, `CancelBookingService`, `LotImportService`, `ExportReportService`, `DiscordNotificationService`, `EventRuleService`, `BookingCodeService`.
2. Controller tidak boleh menjadi tempat utama business logic.
3. Semua validasi tanggal dan scheduler wajib timezone `Asia/Jakarta`.
4. Partial unique index PostgreSQL wajib untuk mencegah double booking di level database.
5. Booking dan reschedule wajib database transaction.
6. Public token tidak boleh ID berurutan.
7. Import lot tidak boleh menghentikan semua proses karena 1 baris error.
8. Jika ada celah yang tidak tercakup dokumen ini maupun dokumen referensi, jangan berasumsi — tulis komentar eksplisit di kode bahwa keputusan ini membutuhkan konfirmasi.
