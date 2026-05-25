@php
    /** @var \App\Models\Booking $booking */
    $activityLabel = [
        'ziarah' => 'Ziarah',
        'naik_batu' => 'Naik Batu',
        'start_work' => 'Start Work',
        'wang_san' => 'Wang San',
    ][$booking->activity_type] ?? $booking->activity_type;

    $graveLabel = $booking->grave_type === 'kotak_abu' ? 'Kotak Abu' : 'Makam';

    $start = \Carbon\CarbonImmutable::parse($booking->timeSlot->start_time)->format('H:i');
    $end = \Carbon\CarbonImmutable::parse($booking->timeSlot->start_time)->addMinutes(59)->format('H:i');
@endphp

<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Bukti Booking</title>
    <style>
        body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 12px; color: #1a2744; }
        .title { font-size: 18px; font-weight: 700; margin-bottom: 6px; }
        .subtitle { color: #5a6480; margin-bottom: 14px; }
        .box { border: 1px solid rgba(26,39,68,0.25); border-radius: 10px; padding: 12px; }
        .row { display: flex; justify-content: space-between; gap: 10px; padding: 6px 0; border-bottom: 1px solid rgba(26,39,68,0.12); }
        .row:last-child { border-bottom: 0; }
        .k { color: #5a6480; width: 38%; }
        .v { font-weight: 600; width: 62%; text-align: right; }
        .code { font-family: monospace; font-size: 14px; }
    </style>
</head>
<body>
    <div class="title">Bukti Booking</div>
    <div class="subtitle">Simpan PDF ini sebagai bukti booking Anda.</div>

    <div class="box">
        <div class="row"><div class="k">Kode Booking</div><div class="v code">{{ $booking->booking_code }}</div></div>
        <div class="row"><div class="k">Jenis kegiatan</div><div class="v">{{ $activityLabel }}</div></div>
        <div class="row"><div class="k">Tanggal & Jam</div><div class="v">{{ optional($booking->visit_date)->format('Y-m-d') }} • {{ $start }} - {{ $end }}</div></div>
        <div class="row"><div class="k">Lokasi</div><div class="v">{{ $booking->location->name ?? '-' }}</div></div>
        <div class="row"><div class="k">Zona</div><div class="v">{{ $booking->zone->name ?? '-' }}</div></div>
        <div class="row"><div class="k">Jenis makam</div><div class="v">{{ $graveLabel }}</div></div>
        <div class="row"><div class="k">Nomor lot</div><div class="v">{{ $booking->lot->lot_number ?? '-' }} ({{ $booking->lot->size ?? '-' }})</div></div>
        <div class="row"><div class="k">Nama</div><div class="v">{{ $booking->customer_name }}</div></div>
        <div class="row"><div class="k">Email</div><div class="v">{{ $booking->customer_email }}</div></div>
        <div class="row"><div class="k">Kursi</div><div class="v">{{ (int) ($booking->facilities->chairs_count ?? 0) }}</div></div>
        <div class="row"><div class="k">Tong bakar</div><div class="v">{{ (int) ($booking->facilities->burn_barrels_count ?? 0) }}</div></div>
        <div class="row"><div class="k">Tenda</div><div class="v">{{ ($booking->facilities->has_tent ?? false) ? 'Ya' : 'Tidak' }}</div></div>
        <div class="row"><div class="k">Meja sembahyang</div><div class="v">{{ ($booking->facilities->has_prayer_table ?? false) ? 'Ya' : 'Tidak' }}</div></div>
        <div class="row"><div class="k">Lampu</div><div class="v">{{ ($booking->facilities->has_lamp ?? false) ? 'Ya' : 'Tidak' }}</div></div>
    </div>
</body>
</html>

