# 03 - Business Rules and Validation

## Jenis Kegiatan
Jenis kegiatan wajib salah satu:

1. `Ziarah`.
2. `Naik Batu`.
3. `Start Work`.
4. `Wang San`.

Semua booking wajib memilih jenis kegiatan, termasuk Ziarah.

## Jenis Makam
Jenis makam wajib salah satu:

1. `Makam`.
2. `Kotak Abu`.

Jenis makam wajib dipilih untuk semua jenis kegiatan.

## Tanggal Booking Baru
Tanggal kunjungan untuk booking baru wajib minimal H+2.

Contoh:

Jika hari ini tanggal 20, tanggal paling cepat yang boleh dipilih adalah tanggal 22.

## Tanggal Reschedule
Tanggal pengganti saat reschedule juga wajib minimal H+2 dari hari saat customer melakukan reschedule.

Customer boleh melakukan reschedule pada H-0 selama tanggal kunjungan lama belum lewat.

## Expired Link Customer
Link public token untuk detail/cancel/reschedule expired setelah tanggal kunjungan lewat.

Jika tanggal kunjungan sudah lewat, customer tidak boleh cancel atau reschedule.

## Status Awal Booking
Setelah customer submit booking valid, status langsung `confirmed`.

Tidak ada approval admin.

## Status Setelah Reschedule
Jika customer melakukan reschedule, record booking yang sama di-update dan status berubah menjadi `rescheduled`.

Histori perubahan wajib dicatat di `booking_reschedule_histories`.

## Status Setelah Cancel
Jika customer atau admin cancel booking, status menjadi `cancelled`.

Customer cancel wajib mengisi alasan.

Admin cancel tidak perlu alasan.

## Completed Otomatis
Scheduler tiap menit wajib mengubah booking menjadi `completed` setelah tanggal kunjungan lewat.

Booking yang sudah `completed` tetap tersimpan permanen untuk histori.

## Unik Lot per Slot
Satu lot tidak boleh dipakai oleh lebih dari 1 booking aktif pada tanggal dan jam yang sama.

Booking aktif adalah status:

1. `confirmed`.
2. `rescheduled`.

Booking `cancelled` dan `completed` tidak memblokir slot.

## Nomor Lot yang Ditampilkan ke Customer
Customer tidak boleh mengetik nomor lot manual.

Customer hanya boleh memilih lot dari daftar/chip yang disediakan sistem.

UI boleh menyediakan search/filter, tetapi hasil akhirnya tetap harus dipilih dari chip/list lot valid.

## Availability Lot
Lot yang tampil untuk dipilih wajib memenuhi semua syarat:

1. Sesuai lokasi.
2. Sesuai zona.
3. Sesuai jenis makam.
4. Tidak sedang dipakai booking aktif pada tanggal dan time slot yang sama.

## Fasilitas
Fasilitas yang tersedia:

1. Kursi.
2. Tong bakar.
3. Tenda.
4. Meja sembahyang.
5. Lampu.

## Aturan Kursi
Kursi wajib minimal 5 dan maksimal 10.

Customer boleh tidak memilih fasilitas lain, tetapi kursi tetap wajib mengikuti batas ini.

## Aturan Tong Bakar
Tong bakar disimpan sebagai integer.

Nilai minimal 0.

Maximal 2

## Aturan Tenda
Tenda disimpan sebagai boolean.

## Aturan Meja Sembahyang
Meja sembahyang disimpan sebagai boolean.

Meja sembahyang bisa disembunyikan oleh event.

## Aturan Lampu
Lampu hanya muncul dan boleh dipilih jika jam kunjungan berada pada rentang 19:00 sampai 03:00.

Rentang ini melewati tengah malam.

Jika jam di luar rentang tersebut, field lampu tidak boleh tampil dan nilai lampu wajib false.

## Event Override
Event hanya boleh menyembunyikan fasilitas.

Event tidak boleh mengubah:

1. Minimal H+2.
2. Aturan reschedule.
3. Time slot global.
4. Label fasilitas.
5. Validasi lot.

## Event Overlap
Event boleh overlap pada tanggal dan lokasi yang sama.

Jika beberapa event berlaku dan menyembunyikan fasilitas berbeda, semua fasilitas yang disembunyikan wajib digabung.

## Dashboard Filter
Dashboard admin cukup memiliki filter:

1. Tanggal.
2. Jenis kegiatan.
3. Lokasi.
4. Zona.
5. Status.

Tidak perlu keyword nama/no lot.

## Pagination
Pagination dashboard default 25 data per halaman.

## Discord H-1
Discord hanya mengambil booking untuk target tanggal besok.

Status yang masuk laporan Discord:

1. `confirmed`.
2. `rescheduled`.

Status yang tidak masuk:

1. `cancelled`.
2. `completed`.

Jika tidak ada booking H-1, jangan kirim pesan apa pun.
