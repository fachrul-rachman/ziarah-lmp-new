# 08 - Email, PDF, Discord, and Export Reporting

## Email Provider
Gunakan Laravel Mail dengan SMTP Mailgun dari awal.

Jangan membuat implementasi email yang bergantung pada provider development saja.

Konfigurasi tetap melalui `.env` Laravel.

## Email Customer
Email customer dikirim untuk:

1. Booking berhasil.
2. Reschedule berhasil.

Email tidak perlu dikirim untuk:

1. Customer cancel.
2. Admin cancel.

## Bahasa Email
Email customer wajib bahasa Indonesia.

## Isi Email Booking Berhasil
Email booking berhasil wajib berisi:

1. Salam singkat.
2. Detail booking lengkap.
3. Note customer hardcoded.
4. Tombol/link `Cancel Booking`.
5. Tombol/link `Reschedule Booking`.

## Template Note Customer
Note hardcoded awal:

1. Harap datang sesuai tanggal dan jam yang sudah dipilih.
2. Harap membawa bukti booking ini saat datang.
3. Tidak diperkenankan memberikan tip kepada petugas.
4. Jika ingin membatalkan atau menjadwalkan ulang, gunakan tombol pada email ini.

Template boleh dibuat rapi di blade/mail view, tetapi tidak perlu setting admin.

## PDF Customer
PDF customer berbeda dari PDF team.

PDF customer hanya berisi rincian booking customer.

PDF customer bisa di-download dari halaman booking berhasil dan halaman detail public token.

PDF customer wajib berisi:

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
11. Note customer.

## Discord Notification
Discord notification dikirim otomatis untuk data H-1.

Jika hari ini tanggal 20, notifikasi berisi booking tanggal 21.

Jam kirim diambil dari Setting.

Timezone wajib `Asia/Jakarta`.

Jika tidak ada booking untuk H-1, jangan kirim pesan apa pun.

## Status yang Masuk Discord
Masuk Discord:

1. `confirmed`.
2. `rescheduled`.

Tidak masuk Discord:

1. `cancelled`.
2. `completed`.

## File Discord
Discord mengirim 2 kategori file:

1. Ziarah.
2. Kegiatan, yaitu gabungan `Naik Batu`, `Start Work`, dan `Wang San`.

Untuk masing-masing kategori, sistem membuat Excel dan PDF.

Jika hanya ada data ziarah, kirim file ziarah saja.

Jika hanya ada data kegiatan, kirim file kegiatan saja.

## Summary Discord
Summary Discord wajib dipisah antara Ziarah dan Kegiatan.

Format summary minimal:

1. Judul kategori.
2. Tanggal target.
3. Total booking.
4. Total kursi.
5. Total tenda.
6. Total meja sembahyang.
7. Total tong bakar.
8. Total lampu.

Contoh struktur:

```text
Ziarah - 2026-01-21
Total booking: 12
Kursi: 80
Tenda: 3
Meja sembahyang: 5
Tong bakar: 7
Lampu: 2
```

## Excel Team
Excel team adalah output laporan untuk team, bukan input import.

Kolom Excel team:

1. Nomor.
2. Jenis kegiatan, kosong/tidak diperlukan untuk Ziarah.
3. Jam.
4. Lokasi.
5. Nama.
6. Jenis makam.
7. Zona.
8. No Lot.
9. Fasilitas.

Fasilitas harus cukup jelas untuk dipakai team operasional.

## PDF Team
PDF team dibuat dari data yang sama dengan Excel team.

PDF team bertujuan untuk diprint.

PDF team wajib landscape.

PDF team berbeda dari PDF customer.

## Urutan Laporan Team
Data laporan team wajib diurutkan per zona.

Jika ada zona A, B, C, maka semua zona A tampil dulu, lalu B, lalu C.

Tidak perlu sorting khusus nomor lot.

## Export Manual Admin
Admin bisa export manual dari dashboard berdasarkan filter aktif.

Admin memilih format:

1. Excel.
2. PDF.

Format export manual sama dengan format team print untuk Discord H-1.

## File Storage
File generated disimpan di `storage/app`.

File boleh dihapus otomatis oleh cleanup job jika diperlukan, tetapi jangan hapus sebelum proses attach Discord/email selesai.
