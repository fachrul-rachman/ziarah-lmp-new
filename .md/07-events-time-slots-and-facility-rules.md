# 07 - Events, Time Slots, and Facility Rules

## Time Slot Global
Time slot berlaku global untuk semua lokasi dan semua jenis kegiatan.

Time slot tidak berbeda per lokasi.

## Durasi Slot
Durasi slot adalah 1 jam.

Admin hanya mengatur jam mulai.

Contoh:

Jika admin membuat slot `08:00`, maka sistem menganggap slot tersebut `08:00-08:59`.

## Event Tidak Override Time Slot
Event tidak boleh mengubah time slot.

Event hanya boleh menyembunyikan fasilitas.

## Event
Event digunakan untuk kondisi khusus seperti Cheng Beng.

Event memiliki:

1. Nama.
2. Tanggal mulai.
3. Tanggal selesai.
4. Lokasi yang terdampak.
5. Fasilitas yang disembunyikan.

## Lokasi Event
Event wajib memilih minimal 1 lokasi.

Jika lokasi kosong, penyimpanan event harus gagal.

Tidak ada konsep event global tanpa lokasi.

## Event Overlap
Event boleh overlap.

Jika event A menyembunyikan meja sembahyang dan event B menyembunyikan lampu pada tanggal/lokasi yang sama, maka form customer wajib menyembunyikan meja sembahyang dan lampu.

## Aturan Event Berlaku
Event berlaku jika:

1. Tanggal booking berada di antara `start_date` dan `end_date` event.
2. Lokasi booking termasuk lokasi event.

## Fasilitas yang Bisa Disembunyikan Event
Event hanya bisa menyembunyikan fasilitas berikut:

1. Kursi.
2. Tong bakar.
3. Tenda.
4. Meja sembahyang.
5. Lampu.

Namun jika kursi disembunyikan, Codex wajib berhati-hati karena kursi punya aturan minimal 5; jika fitur ini belum diinginkan, UI admin sebaiknya hanya menyediakan fasilitas boolean untuk disembunyikan dan tidak menyembunyikan kursi kecuali memang dipilih admin.

## Aturan Kursi
Kursi wajib minimal 5 dan maksimal 10.

Aturan ini berlaku untuk booking baru dan reschedule.

## Aturan Tong Bakar
Tong bakar integer minimal 0.

Maximal 2

## Aturan Tenda
Tenda boolean.

Jika disembunyikan oleh event, nilai wajib false.

## Aturan Meja Sembahyang
Meja sembahyang boolean.

Jika disembunyikan oleh event, nilai wajib false.

## Aturan Lampu
Lampu hanya muncul di jam 19:00 sampai 03:00.

Karena melewati tengah malam, validasi wajib menggunakan logika OR:

1. Jam mulai >= 19:00, atau
2. Jam mulai <= 03:00.

Jika jam 04:00 sampai 18:59, lampu tidak boleh tampil dan nilai wajib false.

Jika event menyembunyikan lampu, lampu tidak boleh tampil walaupun jam berada di rentang 19:00 sampai 03:00.

## Prioritas Aturan Fasilitas
Prioritas validasi:

1. Event hidden facilities.
2. Aturan jam lampu.
3. Validasi nilai fasilitas.

## Form Customer Saat Event
Jika customer memilih tanggal dan lokasi yang terkena event, form wajib refresh daftar fasilitas yang tersedia.

Jika customer sudah memilih fasilitas lalu tanggal/lokasi berubah ke event yang menyembunyikan fasilitas itu, nilai fasilitas tersebut wajib reset ke false atau 0.
