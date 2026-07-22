<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Data Walk-in</title>
    <style>
        body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 10px; color: #1f2937; }
        h1 { margin: 0 0 4px; font-size: 16px; }
        .period { margin-bottom: 12px; font-weight: 700; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #cbd5e1; padding: 6px; text-align: left; }
        th { background: #f1f5f9; }
        .center { text-align: center; }
    </style>
</head>
<body>
    <h1>DATA WALK-IN</h1>
    <div class="period">PERIODE: {{ $minDate ?: '-' }}{{ $maxDate && $maxDate !== $minDate ? ' - '.$maxDate : '' }}</div>
    <table>
        <thead>
            <tr>
                <th class="center">Nomor</th>
                <th>Nama</th>
                <th>Nomor Telepon</th>
                <th>Nomor Lot</th>
                <th>Waktu Kedatangan</th>
                <th>Waktu Persetujuan</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $index => $row)
                <tr>
                    <td class="center">{{ $index + 1 }}</td>
                    <td>{{ $row['customer_name'] ?? '' }}</td>
                    <td>{{ $row['customer_phone'] ?? '' }}</td>
                    <td>{{ $row['lot_number'] ?? '-' }}</td>
                    <td>{{ $row['visited_at'] ?? '' }}</td>
                    <td>{{ $row['ethics_consented_at'] ?? '' }}</td>
                </tr>
            @empty
                <tr><td colspan="6" class="center">Tidak ada data.</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
