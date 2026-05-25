# 04 - Customer Booking, Cancel, and Reschedule Flow

## Route Public
Route utama customer adalah `/`.

Customer tidak perlu login.

## Urutan Form Booking
Form booking wajib mengikuti urutan dependency berikut:

1. Pilih jenis kegiatan.
2. Pilih lokasi.
3. Pilih jenis makam.
4. Pilih zona berdasarkan lokasi dan jenis makam.
5. Pilih tanggal kunjungan minimal H+2.
6. Pilih jam kunjungan dari time slot global.
7. Pilih nomor lot berdasarkan lokasi, zona, jenis makam, tanggal, dan jam.
8. Pilih fasilitas.
9. Isi nama dan email.
10. Submit booking.

## Dependency Field
Jika field parent berubah, field child wajib reset.

Contoh:

1. Jika lokasi berubah, zona dan lot wajib reset.
2. Jika jenis makam berubah, zona dan lot wajib reset.
3. Jika tanggal atau jam berubah, lot wajib refresh karena availability bisa berubah.

## Lot Picker
Nomor lot ditampilkan sebagai chip/list yang bisa dipilih.

Search boleh disediakan untuk memfilter chip/list.

Customer tidak boleh mengetik lot manual sebagai nilai final.

## Submit Booking
Saat submit:

1. Validasi semua input.
2. Jalankan database transaction.
3. Pastikan lot masih available.
4. Simpan booking dengan status `confirmed`.
5. Simpan fasilitas.
6. Generate public token.
7. Generate booking code.
8. Kirim email booking berhasil.
9. Tampilkan halaman booking berhasil.

## Halaman Booking Berhasil
Halaman booking berhasil wajib menampilkan detail booking lengkap:

1. Jenis kegiatan.
2. Lokasi.
3. Jenis makam.
4. Zona.
5. Nomor lot.
6. Tanggal.
7. Jam.
8. Fasilitas.
9. Nama customer.
10. Email customer.

Halaman ini wajib menyediakan tombol download PDF customer.

## Email Booking Berhasil
Email booking berhasil wajib dikirim setelah booking berhasil.

Email wajib berbahasa Indonesia.

Email wajib berisi:

1. Detail booking.
2. Note customer hardcoded.
3. Tombol/link `Cancel Booking`.
4. Tombol/link `Reschedule Booking`.

Link cancel dan reschedule memakai public token yang aman.

## Halaman Detail Public Token
Link dari email wajib membuka halaman detail booking via public token.

Halaman ini wajib menampilkan semua detail booking dan menyediakan aksi sesuai link:

1. Cancel booking.
2. Reschedule booking.

Jika tanggal kunjungan sudah lewat, halaman tetap boleh menampilkan detail tetapi aksi cancel/reschedule harus disabled atau ditolak.

## Cancel Customer
Customer cancel melalui link email.

Flow cancel:

1. Customer membuka link cancel.
2. Sistem tampilkan detail booking.
3. Customer wajib mengisi alasan cancel.
4. Customer confirm cancel.
5. Sistem ubah status menjadi `cancelled`.
6. Sistem tampilkan halaman berhasil cancel.

Tidak perlu email konfirmasi cancel.

Setelah cancel, lot langsung tersedia lagi untuk slot yang sama.

## Reschedule Customer
Customer reschedule melalui link email.

Customer boleh mengubah:

1. Tanggal.
2. Jam.
3. Lokasi.
4. Jenis makam.
5. Zona.
6. Lot.
7. Fasilitas.
8. Nama.
9. Email.

Tanggal baru wajib minimal H+2 dari hari reschedule.

Nomor lot baru wajib melewati validasi availability yang sama seperti booking baru.

## Penyimpanan Reschedule
Reschedule wajib meng-update record booking yang sama.

Status booking berubah menjadi `rescheduled`.

Sistem wajib mencatat histori perubahan lama dan baru.

## Email Setelah Reschedule
Setelah reschedule berhasil, email booking berhasil dikirim ulang dengan detail terbaru.

Email ulang tetap berisi tombol cancel dan reschedule dengan token yang sama.

## Public Token Expiry
Public token tidak perlu dihapus.

Aksi cancel/reschedule dianggap expired setelah tanggal kunjungan lewat.

## Error Handling Customer
Jika validasi gagal, tampilkan pesan jelas dan spesifik.

Contoh:

1. `Lot sudah tidak tersedia untuk tanggal dan jam tersebut.`
2. `Tanggal kunjungan minimal H+2.`
3. `Kursi minimal 5 dan maksimal 10.`
4. `Lampu hanya tersedia untuk jam 19:00 sampai 03:00.`
