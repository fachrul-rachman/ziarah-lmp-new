# 06 - Lot Import Excel

## Tujuan Import
Import Excel digunakan hanya untuk memasukkan atau memperbarui data lokasi, zona, dan lot.

Import Excel bukan untuk input booking.

Format Excel pada gambar hanya contoh output laporan team, bukan input import.

## Package
Gunakan Laravel Excel.

## Proses
Import wajib diproses melalui queue/job.

Chunk size default: 500 baris per chunk.

## Kolom Input Excel
Excel import lot wajib memiliki kolom:

1. `jenis_makam`.
2. `lokasi`.
3. `zona`.
4. `no_lot`.
5. `ukuran`.

Nama kolom boleh dipetakan dengan variasi label jika diperlukan, tetapi hasil akhir internal wajib sama.

## Validasi Per Baris
Setiap baris wajib validasi:

1. `jenis_makam` wajib `Makam` atau `Kotak Abu`.
2. `lokasi` wajib isi.
3. `zona` wajib isi.
4. `no_lot` wajib isi.
5. `ukuran` wajib isi.

`ukuran` adalah string bebas dan case-insensitive untuk pencarian/pembandingan.

## Upsert
Import wajib upsert, bukan selalu insert baru.

Unique key:

`jenis_makam + lokasi + zona + no_lot_normalized`

Jika data sudah ada, update field yang relevan seperti ukuran.

Jika belum ada, buat data baru.

## Auto Create Lokasi dan Zona
Jika lokasi atau zona dari Excel belum ada di database, sistem boleh membuat record baru otomatis.

Lokasi dan zona yang dibuat otomatis langsung aktif dan muncul di form customer.

## Error Handling
Jika ada baris invalid, proses tidak boleh berhenti total.

Baris valid tetap diproses.

Baris invalid dicatat ke `import_job_errors` dengan:

1. Nomor baris.
2. Data mentah baris tersebut.
3. Pesan error.

## Import Job Summary
Setelah import selesai, sistem wajib menyimpan ringkasan:

1. Total rows.
2. Processed rows.
3. Success rows.
4. Failed rows.
5. Status akhir.
6. Waktu mulai.
7. Waktu selesai.

## Normalisasi
Normalisasi wajib dilakukan untuk:

1. `jenis_makam`.
2. `lokasi`.
3. `zona`.
4. `no_lot`.
5. `ukuran`.

Normalisasi minimal:

1. Trim whitespace.
2. Collapse multiple spaces menjadi 1 spasi.
3. Perbandingan case-insensitive.
4. Untuk `normalized_lot_number`, gunakan format aman untuk unique key dan booking code.

## Data Besar
Satu zona bisa berisi sampai 50 ribu lot.

Karena itu implementasi wajib:

1. Chunk import 500 baris.
2. Tidak memuat seluruh file ke memory jika tidak perlu.
3. Menggunakan upsert batch jika memungkinkan.
4. Mencatat error tanpa menghentikan batch lain.
5. Menampilkan progress import di admin jika memungkinkan.

## Larangan
Import tidak boleh:

1. Menghapus booking lama.
2. Menghapus lot yang tidak ada di Excel.
3. Menganggap Excel sebagai replace full database.
4. Menghentikan semua proses karena 1 baris error.
5. Membuat nomor lot dari input customer manual.
