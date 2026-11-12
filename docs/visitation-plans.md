# Ziarah terencana dan WhatsApp

## Persiapan

- Jalankan migration setelah backup database dan isi konfigurasi di `.env.example`.
- `BURIAL_API_KEY` berisi rahasia acak minimal 32 karakter yang hanya dibagikan antarlayanan.
- `burial.site` dicocokkan dengan nama lokasi yang disederhanakan menggunakan slug, misalnya `Karawang` menjadi `karawang`, dan `Tangerang Selatan` menjadi `tangerang-selatan`. Semua lokasi yang sudah terdaftar didukung. Nama lokasi dan nomor lot harus cocok secara unik. Lokasi ambigu, lot ambigu, atau lot yang dihapus akan ditolak.
- Koneksi WhatsApp menggunakan Meta Cloud API. Isi access token, phone number ID, dan versi Graph API yang didukung akun pada `WHATSAPP_API_VERSION`, misalnya `v25.0` setelah diverifikasi di akun Meta.
- Daftarkan tiga template berbahasa Indonesia dengan kode bahasa yang sesuai `WHATSAPP_TEMPLATE_LANGUAGE`. Urutan variabel harus mengikuti tabel di bawah. Tombol URL memakai `https://<domain-ziarah>/{{1}}`. Parameter tombol berisi `booking/<token-booking>` untuk booking biasa atau `plan/<token-plan>` untuk booking terencana.
- Pastikan `APP_URL` memakai domain HTTPS Ziarah, scheduler berjalan setiap menit, dan queue worker berjalan pada antrean default.

## API aplikasi pemakaman

`POST /api/visitation-plans` dengan `Authorization: Bearer <BURIAL_API_KEY>` dan `Accept: application/json`.

```json
{
  "request_reference": "LMP-CONTOH123",
  "applicant": {
    "name": "Budi Santoso",
    "whatsapp": "+6281234567890",
    "relationship_to_deceased": "Anak"
  },
  "deceased": {
    "name": "Hendra Santoso",
    "date_of_death": "2026-11-21"
  },
  "burial": {
    "site": "karawang",
    "zone": "A",
    "lot_number": "123",
    "scheduled_at": "2026-11-28T12:00:00+07:00"
  }
}
```

Respons `201` saat dibuat pertama kali, atau `200` untuk request ulang dengan data yang sama.

```json
{
  "url": "https://<domain-ziarah>/plan/<token-acak>",
  "expires_at": "2026-11-14T09:00:00+07:00"
}
```

Contoh waktu kedaluwarsa di atas mengasumsikan rencana dibuat pada 9 November pukul 09.00 WIB. Aplikasi pemakaman mengirim URL kepada customer. Request ulang tidak memperpanjang masa berlaku. Data berbeda untuk referensi yang sama menghasilkan `409`. Data tidak valid atau lot yang tidak cocok menghasilkan `422`. Rahasia salah menghasilkan `401`. Retry gangguan jaringan menggunakan referensi dan payload yang sama.

## Aturan customer

- Momen memakai tanggal meninggal ditambah 7, 40, atau 100 hari. Satu tahun menggunakan ulang tahun kalender dengan 29 Februari menjadi 28 Februari.
- Tanggal acuan momen tetap ditampilkan. Tanggal kunjungan bisa dipilih hingga satu tahun sejak meninggal untuk momen 7, 40, 100 hari dan tanggal bebas. Khusus momen 1 Tahun, batasnya satu bulan kalender setelah tanggal acuan. Jam kunjungan harus setelah pemakaman dan memenuhi batas minimum booking dari pengaturan admin.
- Pemesanan dan penambahan momen dibuka selama lima hari sejak rencana dibuat. Setelah itu booking yang sudah terbentuk tetap dapat dikelola melalui tautan masing-masing.
- Momen tetap yang sudah aktif atau selesai tidak dapat dipesan ulang. Tanggal bebas tidak boleh mengulang tanggal booking aktif pada rencana yang sama. Satu pengiriman formulir menerima maksimal 20 jadwal.
- Semua pilihan dalam satu pengiriman disimpan bersama. Bentrok satu jadwal membatalkan seluruh penyimpanan pengiriman itu.
- Setiap booking mendapat kode sendiri. Kode booking memakai format ringkas seperti `Z-20261107-sb878`. Token dan ID internal membedakan reservasi serta pengiriman notifikasi. Kode lama tetap dapat ditampilkan pada booking lama.
- Reschedule booking terencana tetap memakai lot pemakaman dan batas tanggal sesuai momen. Label momen tetap tersimpan setelah tanggal digeser.

## Template dan pengiriman

| Template | Urutan variabel body |
|---|---|
| `ziarah_booking_confirmation` | Nama pemohon, kode booking, kegiatan/momen, jadwal, lokasi, zona dan nomor lot |
| `ziarah_booking_rescheduled` | Nama pemohon, kode booking terbaru, kegiatan/momen, jadwal terbaru, lokasi, zona dan nomor lot |
| `ziarah_planned_reminder` | Nama pemohon, nama almarhum/almarhumah, momen, kode booking, jadwal, lokasi, zona dan nomor lot |

Konfirmasi dikirim per booking. Reminder hanya untuk booking terencana yang aktif pada H-1, mulai pukul 09.00 WIB secara default. Jam dapat diubah dengan `WHATSAPP_REMINDER_TIME`. Jika scheduler terlambat, reminder masih dapat diproses pada hari H-1 tersebut. Pesan yang sudah tidak relevan karena cancel atau reschedule dilewati.

`whatsapp:bookings` menyimpan reminder dan mengirim pekerjaan untuk pesan pending. Tanpa konfigurasi Meta, pesan tetap pending. Kegagalan WhatsApp tidak membatalkan booking. Status terlihat pada detail booking admin.

Pekerjaan pengiriman memakai unique job Laravel berdasarkan ID pesan. Menjalankan scheduler berulang tidak menambah pekerjaan untuk pesan yang masih menunggu atau diproses. Scheduler dan worker harus menggunakan cache persisten yang sama dengan dukungan atomic lock, misalnya database atau Redis. Pada satu VPS, file cache dapat digunakan jika keduanya memakai direktori yang sama. Kunci dilepas ketika pekerjaan selesai atau gagal. Kegagalan dispatch juga melepas kunci sehingga scheduler berikutnya dapat mencoba lagi. Jika pekerjaan di antrean dihapus secara manual, kunci unik terkait perlu direkonsiliasi sebelum pengiriman dijadwalkan ulang.

| Status | Arti |
|---|---|
| `pending` | Menunggu scheduler, worker, atau konfigurasi |
| `sending` | Sudah diklaim worker untuk dikirim |
| `accepted` | Meta menerima request dan memberi message ID. Pengantaran ke perangkat belum dikonfirmasi |
| `failed` | Meta menolak request, periksa error code |
| `unknown` | Koneksi gagal dan hasil pengiriman belum dapat dipastikan |
| `skipped` | Booking atau jadwal pesan sudah tidak relevan |

Setelah memperbaiki penyebab kegagalan, jalankan `php artisan whatsapp:retry <id-pesan>` untuk status failed. Untuk unknown atau sending yang tertahan, periksa status pada penyedia sebelum memakai `--verified-not-sent`. Pemeriksaan ini diperlukan karena request yang terputus mungkin sudah diterima Meta. Worker yang sedang memproses pesan harus sudah berhenti sebelum reset status sending. Pesan accepted tidak bisa dikirim ulang melalui perintah tersebut.

Pengiriman live membutuhkan konfigurasi akun dan template yang disetujui. Tes otomatis menggunakan pengganti respons HTTP dan database SQLite terpisah. Uji integrasi pada PostgreSQL serta pengiriman ke nomor uji tetap diperlukan sebelum produksi.

API mewajibkan `burial.zone` dan `burial.lot_number` secara terpisah. Lokasi, zona, dan nomor lot dicocokkan berurutan. Format gabungan tidak diurai menjadi zona secara otomatis. Formulir customer mengumpulkan email untuk administrasi dan tetap mengirim notifikasi melalui WhatsApp.

Persetujuan WhatsApp diberikan melalui tindakan konfirmasi setelah pemberitahuan singkat di dekat tombol. Form tetap mengirim `whatsapp_consent: true` saat customer mengonfirmasi, dan server mencatat waktu persetujuan.

Lampu hanya ditawarkan untuk jam mulai pukul 18.00 sampai 05.59 WIB, mengikuti form booking biasa. Server menonaktifkan lampu pada slot di luar rentang ini, termasuk saat reschedule.
