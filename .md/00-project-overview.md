# 00 - Project Overview

## Nama Project
Aplikasi Booking Ziarah.

## Tujuan Dokumen
Dokumen ini adalah kontrak implementasi untuk Codex agar aplikasi dibuat tanpa asumsi liar, tanpa fitur tambahan yang tidak diminta, dan dengan aturan bisnis yang eksplisit.

## Tujuan Aplikasi
Aplikasi digunakan untuk menerima booking customer, mengelola data lokasi/zona/lot, mengelola jadwal kunjungan, mengatur event khusus, serta menghasilkan laporan Excel/PDF dan notifikasi Discord untuk persiapan operasional team.

## Bagian Aplikasi
Aplikasi memiliki 2 bagian utama:

1. Public customer form di route `/`.
2. Admin dashboard yang wajib login.

## Public Customer Form
Customer dapat membuat booking tanpa login.

Form customer wajib berisi:

1. Jenis kegiatan: `Ziarah`, `Naik Batu`, `Start Work`, `Wang San`.
2. Lokasi.
3. Jenis makam: `Makam` atau `Kotak Abu`.
4. Zona, berdasarkan lokasi dan jenis makam.
5. Tanggal kunjungan, minimal H+2 dari tanggal hari ini.
6. Jam kunjungan, berdasarkan time slot global dan aturan event jika ada.
7. Nomor lot, berdasarkan lokasi, zona, jenis makam, tanggal, dan jam yang masih tersedia.
8. Fasilitas: kursi, tong bakar, tenda, meja sembahyang, lampu.
9. Data customer: nama dan email.

## Admin Dashboard
Admin wajib login menggunakan email dan password.

Admin dashboard memiliki sidebar:

1. Dashboard.
2. Lokasi dan Lot.
3. Time Slots.
4. Event.
5. Setting.

## Prinsip Utama
Aplikasi wajib mengutamakan stabilitas data, kemudahan penggunaan, mobile-first, dan mencegah double booking.

## Batasan Scope
Aplikasi tidak membutuhkan:

1. Login customer.
2. Role admin bertingkat.
3. Payment.
4. Approval booking manual.
5. Multi-tenant.
6. Integrasi WhatsApp.
7. Cloud storage.

## Status Booking
Status booking yang digunakan:

1. `confirmed` untuk booking aktif baru.
2. `rescheduled` untuk booking aktif yang pernah diubah customer.
3. `cancelled` untuk booking yang dibatalkan.
4. `completed` untuk booking yang tanggal kunjungannya sudah lewat.

## Catatan Implementasi
Semua aturan di dokumen ini wajib dianggap sebagai sumber kebenaran; jika ada celah yang belum dijelaskan, Codex tidak boleh membuat asumsi dan harus menulis komentar eksplisit di kode atau dokumentasi internal bahwa keputusan tersebut membutuhkan konfirmasi.
