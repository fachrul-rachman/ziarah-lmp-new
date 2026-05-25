# 09 - UI/UX and Testing Checklist

## Target User
Pengguna customer dan admin banyak yang berumur, sehingga UI harus sangat mudah dipakai.

UI wajib mobile-first untuk customer form. Admin dashboard dioptimalkan untuk desktop.

## Style Visual
Tampilan wajib mewah tetapi tidak berlebihan.

Warna utama:

1. Dasar: `#F8F9FB`.
2. Core/button: `#202938`.
3. Accent tambahan: `#D4AF37`.

Font family: **Inter**.

Ukuran font body minimal 16px.

## Prinsip UI
UI wajib:

1. Mobile-first untuk customer, desktop-first untuk admin.
2. Font Inter, minimal 16px, mudah dibaca.
3. Tombol cukup besar untuk disentuh.
4. Kontras jelas.
5. Label eksplisit.
6. Tidak bergantung pada ikon saja.
7. Error message jelas.
8. Step form tidak membingungkan.
9. Loading state menggunakan spinner.
10. Empty state menggunakan teks saja, tanpa ilustrasi.

## Customer Form UX
Customer form wajib menggunakan stepper 4 step:

1. **Step 1 - Lokasi & Waktu**: jenis kegiatan, lokasi, jenis makam, zona, tanggal, jam.
2. **Step 2 - Pilih Lot**: lot picker chip/list.
3. **Step 3 - Fasilitas**: kursi, tong bakar, tenda, meja sembahyang, lampu.
4. **Step 4 - Data Diri**: nama, email, submit.

Saat field parent berubah, field child reset dan diberi penjelasan singkat.

Jangan tampilkan semua pilihan sekaligus.

## Lot Picker UX
Lot picker wajib berupa chip/list pilihan.

Search hanya untuk memfilter chip/list.

Customer tidak boleh submit teks lot manual.

## Admin UX
Admin dashboard dioptimalkan untuk desktop.

Sidebar collapse ke icon-only saat dikecilkan, hidden di mobile dengan hamburger.

Tabel di mobile berubah menjadi card list.

Aksi penting seperti cancel wajib punya konfirmasi dialog.

Tombol aksi selalu punya label teks, tidak icon-only.

## Testing Booking
Test wajib:

1. Booking baru berhasil dengan data valid.
2. Booking gagal jika tanggal kurang dari H+2.
3. Booking gagal jika kursi kurang dari 5.
4. Booking gagal jika kursi lebih dari 10.
5. Booking gagal jika tong bakar lebih dari 2.
6. Booking gagal jika lot sudah dipakai slot yang sama.
7. Booking berhasil jika lot sama tapi tanggal berbeda.
8. Booking berhasil jika lot sama tapi jam berbeda.
9. Lampu muncul untuk jam 19:00.
10. Lampu muncul untuk jam 03:00.
11. Lampu tidak muncul untuk jam 04:00.
12. Lampu tidak muncul untuk jam 18:00.

## Testing Reschedule
Test wajib:

1. Reschedule berhasil dengan tanggal baru H+2.
2. Reschedule gagal jika tanggal baru kurang dari H+2.
3. Reschedule gagal jika lot baru tidak tersedia.
4. Reschedule gagal jika tong bakar lebih dari 2.
5. Reschedule mencatat histori.
6. Reschedule mengubah status menjadi `rescheduled`.
7. Reschedule mengirim email ulang.
8. Token tetap sama setelah reschedule.
9. Booking code prefix tetap meski lokasi berubah saat reschedule.

## Testing Cancel
Test wajib:

1. Customer cancel wajib isi alasan.
2. Customer cancel mengubah status menjadi `cancelled`.
3. Customer cancel membuat lot tersedia lagi.
4. Admin cancel tidak perlu alasan.
5. Admin cancel tidak mengirim email.

## Testing Import Lot
Test wajib:

1. Import valid membuat lokasi, zona, dan lot baru.
2. Import data yang sama melakukan upsert.
3. Import baris invalid mencatat error.
4. Import baris valid tetap masuk walaupun ada baris invalid.
5. Import ukuran kosong gagal untuk baris tersebut.
6. Import chunk size 500 berjalan.

## Testing Event
Test wajib:

1. Event wajib minimal 1 lokasi.
2. Event menyembunyikan fasilitas sesuai tanggal dan lokasi.
3. Event tidak berlaku untuk lokasi lain.
4. Event overlap menggabungkan hidden facilities.
5. Event tidak mengubah time slot.

## Testing Discord dan Export
Test wajib:

1. Scheduler mengambil data H-1.
2. Discord tidak mengirim apa pun jika tidak ada booking.
3. Discord memasukkan status `confirmed`.
4. Discord memasukkan status `rescheduled`.
5. Discord mengecualikan status `cancelled`.
6. Discord mengecualikan status `completed`.
7. Excel/PDF ziarah terpisah dari kegiatan.
8. Kegiatan menggabungkan `Naik Batu`, `Start Work`, dan `Wang San`.
9. Summary fasilitas benar.
10. Laporan diurutkan per zona.
11. PDF team landscape.

## Testing Completed Scheduler
Test wajib:

1. Scheduler completed berjalan tiap menit.
2. Booking yang tanggalnya lewat berubah menjadi `completed`.
3. Booking `cancelled` tidak diubah menjadi `completed`.
4. Histori booking tetap tersimpan.

## Testing Security
Test wajib:

1. Admin route tidak bisa diakses tanpa login.
2. Public token tidak memakai ID berurutan.
3. Token invalid ditolak.
4. Cancel/reschedule ditolak jika tanggal kunjungan sudah lewat.
5. Double submit lot yang sama hanya membuat 1 booking berhasil.

## Definition of Done
Implementasi dianggap selesai jika:

1. Semua flow customer berjalan.
2. Semua flow admin berjalan.
3. Import lot chunk 500 berjalan.
4. Double booking dicegah oleh database.
5. Email booking berhasil dan reschedule berhasil terkirim.
6. PDF customer bisa di-download.
7. Export Excel/PDF team berjalan.
8. Discord H-1 berjalan sesuai setting.
9. Completed scheduler berjalan tiap menit.
10. Test untuk aturan kritis tersedia.