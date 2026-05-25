# 05 - Admin Dashboard and Management Flow

## Auth
Admin wajib login menggunakan email dan password.

Admin hanya perlu fitur:

1. Login.
2. Logout.

Tidak perlu:

1. Register publik.
2. Forgot password.
3. Role permission.

## Layout Admin
Admin dashboard menggunakan sidebar.

Sidebar wajib berisi:

1. Dashboard.
2. Lokasi dan Lot.
3. Time Slots.
4. Event.
5. Setting.

## Dashboard Booking
Dashboard menampilkan tabel booking.

Kolom wajib:

1. Nama.
2. Jenis kegiatan.
3. Lokasi / Zona / Lot.
4. Tanggal dan jam.
5. Fasilitas.
6. Status.
7. Aksi.

Aksi yang tersedia:

1. Lihat detail.
2. Cancel booking.

Admin tidak boleh edit booking manual.

## Filter Dashboard
Filter wajib:

1. Tanggal.
2. Jenis kegiatan.
3. Lokasi.
4. Zona.
5. Status.

Tidak perlu keyword nama/no lot.

## Pagination Dashboard
Default pagination 25 data per halaman.

## Detail Booking Admin
Detail booking admin wajib menampilkan:

1. Booking code.
2. Nama customer.
3. Email customer.
4. Jenis kegiatan.
5. Jenis makam.
6. Lokasi.
7. Zona.
8. Nomor lot.
9. Tanggal.
10. Jam.
11. Fasilitas.
12. Status.
13. Alasan cancel jika ada.
14. Histori reschedule jika ada.

## Cancel Booking Admin
Admin bisa cancel booking dari dashboard.

Admin cancel tidak perlu alasan.

Admin cancel tidak perlu mengirim email ke customer.

Setelah admin cancel, lot langsung tersedia lagi untuk slot yang sama.

## Lokasi dan Lot
Admin bisa mengelola lokasi, zona, dan lot.

Fitur wajib:

1. Upload Excel lot.
2. Create manual lot.
3. Edit manual lot.
4. Delete manual lot.
5. Bulk action tersedia untuk lot.

Tidak perlu fitur aktif/nonaktif.

## Bulk Lot
Bulk action disediakan, minimal untuk delete banyak lot.

Jika lot sudah pernah dipakai booking, delete harus aman:

1. Jangan merusak histori booking.
2. Jika hard delete berisiko, gunakan soft delete untuk lot.

## Time Slots
Admin mengatur time slot global.

Slot berdurasi 1 jam.

Admin hanya mengatur jam mulai, contoh `08:00`.

Sistem menganggap slot tersebut `08:00-08:59`.

## Event
Admin bisa membuat event.

Event wajib memiliki:

1. Nama event.
2. Tanggal mulai.
3. Tanggal selesai.
4. Minimal 1 lokasi.
5. Fasilitas yang disembunyikan.

Event boleh overlap.

Event hanya mengatur fasilitas yang disembunyikan.

## Setting
Setting wajib berisi:

1. Discord webhook URL.
2. Jam kirim notifikasi Discord.

Jam kirim memakai timezone `Asia/Jakarta`.

## Export Manual
Dashboard admin wajib menyediakan export manual berdasarkan filter yang sedang dipilih.

Admin dapat memilih format:

1. Excel.
2. PDF.

Format export manual mengikuti format team print yang sama seperti Discord H-1.

## Prinsip UI Admin
Admin digunakan oleh orang berumur, sehingga UI wajib:

1. Mobile-first.
2. Tombol besar.
3. Font mudah dibaca.
4. Kontras jelas.
5. Form tidak padat.
6. Pesan error mudah dipahami.
7. Tidak bergantung pada ikon saja.
