# 02 - Database Schema and Integrity

## Prinsip Database
Database wajib menjaga integritas utama, terutama double booking, relasi lokasi/zona/lot, histori reschedule, dan import lot besar.

Validasi aplikasi tidak cukup; constraint database wajib dipakai untuk aturan kritis.

## Tabel `users`
Dipakai untuk admin login.

Kolom minimal:

1. `id`.
2. `name`.
3. `email` unique.
4. `password`.
5. timestamps.

Tidak perlu role.

## Tabel `locations`
Menyimpan lokasi.

Kolom minimal:

1. `id`.
2. `name`.
3. timestamps.

`name` wajib unik case-insensitive.

Lokasi yang dibuat otomatis dari import langsung aktif dan muncul di form customer.

## Tabel `zones`
Menyimpan zona berdasarkan lokasi.

Kolom minimal:

1. `id`.
2. `location_id`.
3. `name`.
4. timestamps.

Unique case-insensitive untuk kombinasi `location_id + name`.

Zona yang dibuat otomatis dari import langsung aktif dan muncul di form customer.

## Tabel `lots`
Menyimpan nomor lot.

Kolom minimal:

1. `id`.
2. `location_id`.
3. `zone_id`.
4. `grave_type` enum/string terbatas: `makam`, `kotak_abu`.
5. `lot_number`.
6. `normalized_lot_number`.
7. `size` string bebas, wajib isi, case-insensitive untuk pencarian.
8. timestamps.

Unique key wajib berdasarkan:

`grave_type + location_id + zone_id + normalized_lot_number`

Import lot wajib melakukan upsert berdasarkan unique key ini.

## Tabel `time_slots`
Time slot bersifat global.

Kolom minimal:

1. `id`.
2. `start_time` format jam.
3. timestamps.

Slot adalah 1 jam.

Jika `start_time = 08:00`, maka slot dianggap `08:00-08:59`.

## Tabel `events`
Event khusus seperti Cheng Beng.

Kolom minimal:

1. `id`.
2. `name`.
3. `start_date`.
4. `end_date`.
5. timestamps.

Event bisa overlap.

## Tabel `event_locations`
Event wajib memiliki minimal 1 lokasi.

Kolom minimal:

1. `id`.
2. `event_id`.
3. `location_id`.

Jika event tidak punya lokasi, event tidak valid dan tidak boleh disimpan.

## Tabel `event_hidden_facilities`
Menyimpan fasilitas yang disembunyikan pada event.

Kolom minimal:

1. `id`.
2. `event_id`.
3. `facility_key`.

`facility_key` hanya boleh salah satu:

1. `chairs`.
2. `burn_barrels`.
3. `tent`.
4. `prayer_table`.
5. `lamp`.

Jika event overlap dan menyembunyikan fasilitas berbeda, hasil aturan digabung.

## Tabel `bookings`
Kolom minimal:

1. `id`.
2. `public_token` unique.
3. `activity_type`: `ziarah`, `naik_batu`, `start_work`, `wang_san`.
4. `booking_code`.
5. `customer_name`.
6. `customer_email`.
7. `location_id`.
8. `zone_id`.
9. `lot_id`.
10. `grave_type`: `makam`, `kotak_abu`.
11. `visit_date`.
12. `time_slot_id`.
13. `status`: `confirmed`, `rescheduled`, `cancelled`, `completed`.
14. `cancel_reason` nullable.
15. timestamps.

## Tabel `booking_facilities`
Kolom minimal:

1. `id`.
2. `booking_id` unique.
3. `chairs_count` integer.
4. `burn_barrels_count` integer.
5. `has_tent` boolean.
6. `has_prayer_table` boolean.
7. `has_lamp` boolean.
8. timestamps.

Kursi minimal 5 dan maksimal 10.

Tong bakar boleh 0 dan maximal 2.

Tenda, meja sembahyang, dan lampu boolean.

## Tabel `booking_reschedule_histories`
Wajib mencatat histori perubahan saat customer melakukan reschedule.

Kolom minimal:

1. `id`.
2. `booking_id`.
3. `old_visit_date`.
4. `old_time_slot_id`.
5. `old_location_id`.
6. `old_zone_id`.
7. `old_lot_id`.
8. `old_grave_type`.
9. `old_facilities_json`.
10. `new_visit_date`.
11. `new_time_slot_id`.
12. `new_location_id`.
13. `new_zone_id`.
14. `new_lot_id`.
15. `new_grave_type`.
16. `new_facilities_json`.
17. `changed_at`.
18. timestamps.

## Tabel `settings`
Setting minimal:

1. `key` unique.
2. `value` text/json.

Setting wajib mendukung:

1. `discord_webhook_url`.
2. `discord_notification_time`.

Email note customer tidak perlu setting; gunakan hardcode template.

## Tabel `import_jobs`
Mencatat import Excel lot.

Kolom minimal:

1. `id`.
2. `filename`.
3. `status`.
4. `total_rows`.
5. `processed_rows`.
6. `success_rows`.
7. `failed_rows`.
8. `started_at`.
9. `finished_at`.
10. timestamps.

## Tabel `import_job_errors`
Mencatat baris error saat import.

Kolom minimal:

1. `id`.
2. `import_job_id`.
3. `row_number`.
4. `raw_data_json`.
5. `error_message`.
6. timestamps.

## Tabel `notification_logs`
Mencatat pengiriman Discord.

Kolom minimal:

1. `id`.
2. `target_date`.
3. `status`.
4. `message`.
5. `attachments_json`.
6. `sent_at`.
7. timestamps.

## Unique Index Double Booking
Booking aktif tidak boleh double untuk kombinasi:

`visit_date + time_slot_id + lot_id`

Hanya status aktif yang memblokir slot:

1. `confirmed`.
2. `rescheduled`.

Status yang tidak memblokir slot:

1. `cancelled`.
2. `completed`.

Di PostgreSQL, gunakan partial unique index:

`unique where status in ('confirmed', 'rescheduled')`.

## Transaction Wajib
Pembuatan booking dan reschedule wajib memakai database transaction.

Jika 2 customer submit lot yang sama di slot yang sama secara bersamaan, hanya 1 transaksi yang boleh berhasil.
