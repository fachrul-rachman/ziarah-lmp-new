<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>DATA BOOKING</title>
    <style>
        body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 9px; color: #1a2744; }
        h1 { font-size: 14px; margin: 0 0 4px; }
        h2 { font-size: 11px; margin: 14px 0 6px; }
        .sub { font-size: 10px; margin: 0 0 10px; font-weight: 700; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid rgba(26,39,68,0.2); padding: 4px 4px; vertical-align: top; }
        th { background: #f7f8fa; text-align: left; font-weight: 700; }
        .right { text-align: right; }
        .center { text-align: center; }
        .nowrap { white-space: nowrap; }
        .muted { color: rgba(26,39,68,0.7); }
    </style>
</head>
<body>
@php
    use Carbon\CarbonImmutable;

    $months = [
        1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni',
        7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
    ];

    $formatIdDate = function (?string $ymd) use ($months) {
        if (! $ymd) return null;
        $dt = CarbonImmutable::parse($ymd, 'Asia/Jakarta');
        return $dt->format('d').' '.$months[(int) $dt->format('n')].' '.$dt->format('Y');
    };

    $rangeText = $minDate && $maxDate && $minDate !== $maxDate
        ? ($formatIdDate($minDate).' - '.$formatIdDate($maxDate))
        : ($formatIdDate($minDate) ?? CarbonImmutable::now('Asia/Jakarta')->format('d M Y'));

    $ziarahRows = array_values(array_filter($rows, fn ($r) => ($r['activity_type'] ?? '') === 'ziarah'));
    $kegiatanRows = array_values(array_filter($rows, fn ($r) => ($r['activity_type'] ?? '') !== 'ziarah'));

    $sections = [];
    if (count($ziarahRows) > 0) $sections[] = ['title' => 'Data Ziarah', 'rows' => $ziarahRows];
    if (count($kegiatanRows) > 0) $sections[] = ['title' => 'Data Kegiatan', 'rows' => $kegiatanRows];
    if (count($sections) === 0) $sections[] = ['title' => 'Data Ziarah', 'rows' => []];
@endphp

<h1>DATA BOOKING</h1>
<div class="sub">TANGGAL EXPORT: {{ $rangeText }}</div>

@foreach($sections as $section)
    @php
        $sectionRows = $section['rows'];
        $totTent = 0;
        $totChairs = 0;
        $totBurn = 0;
        $totPrayer = 0;
        $totLamp = 0;
    @endphp

    <h2>{{ $section['title'] }}</h2>
    <table>
        <thead>
        <tr>
            <th class="center" style="width: 24px;">Nomor</th>
            <th style="width: 88px;">Jenis Kegiatan</th>
            <th class="nowrap" style="width: 70px;">Jam</th>
            <th style="width: 90px;">Lokasi</th>
            <th style="width: 110px;">Nama</th>
            <th style="width: 70px;">Jenis Makam</th>
            <th style="width: 50px;">Zona</th>
            <th style="width: 55px;">No. Lot</th>
            <th class="center" style="width: 34px;">Tenda</th>
            <th class="center" style="width: 34px;">Kursi</th>
            <th class="center" style="width: 54px;">Tong Bakar</th>
            <th class="center" style="width: 70px;">Meja Sembahyang</th>
            <th class="center" style="width: 34px;">Lampu</th>
        </tr>
        </thead>
        <tbody>
        @foreach($sectionRows as $idx => $r)
            @php
                $tenda = (int) ($r['has_tent'] ?? 0);
                $kursi = (int) ($r['chairs_count'] ?? 0);
                $tong = (int) ($r['burn_barrels_count'] ?? 0);
                $meja = (bool) ($r['has_prayer_table'] ?? false);
                $lampu = (bool) ($r['has_lamp'] ?? false);

                $totTent += $tenda;
                $totChairs += $kursi;
                $totBurn += $tong;
                $totPrayer += $meja ? 1 : 0;
                $totLamp += $lampu ? 1 : 0;
            @endphp
            <tr>
                <td class="center">{{ $idx + 1 }}</td>
                <td>{{ $r['activity_label'] ?? '' }}</td>
                <td class="nowrap">{{ $r['time_range'] ?? '' }}</td>
                <td>{{ $r['location'] ?? '' }}</td>
                <td>{{ $r['customer_name'] ?? '' }}</td>
                <td>{{ $r['grave_label'] ?? '' }}</td>
                <td>{{ $r['zone'] ?? '' }}</td>
                <td>{{ $r['lot'] ?? '' }}</td>
                <td class="center">{{ $tenda }}</td>
                <td class="center">{{ $kursi }}</td>
                <td class="center">{{ $tong }}</td>
                <td class="center">{{ $meja ? 'Ya' : 'Tidak' }}</td>
                <td class="center">{{ $lampu ? 'Ya' : 'Tidak' }}</td>
            </tr>
        @endforeach
        <tr>
            <td colspan="8" class="right muted"><strong>Total Kebutuhan Fasilitas:</strong></td>
            <td class="center"><strong>{{ $totTent }}</strong></td>
            <td class="center"><strong>{{ $totChairs }}</strong></td>
            <td class="center"><strong>{{ $totBurn }}</strong></td>
            <td class="center"><strong>{{ $totPrayer }}</strong></td>
            <td class="center"><strong>{{ $totLamp }}</strong></td>
        </tr>
        </tbody>
    </table>
@endforeach

</body>
</html>

