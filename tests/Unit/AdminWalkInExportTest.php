<?php

use App\Services\Exports\AdminWalkInExport;

test('admin walk-in export contains the visit data', function () {
    $export = new AdminWalkInExport([
        [
            'customer_name' => 'Budi Santoso',
            'customer_phone' => '6281234567890',
            'lot_number' => 'A-12',
            'visited_at' => '22 Juli 2026, 10:30',
            'ethics_consented_at' => '22 Juli 2026, 10:29',
        ],
    ], '2026-07-22', '2026-07-22');

    $rows = $export->array();

    expect($rows[2])->toBe([
        'Nomor',
        'Nama',
        'Nomor Telepon',
        'Nomor Lot',
        'Waktu Kedatangan',
        'Waktu Persetujuan',
    ])->and($rows[3][1])->toBe('Budi Santoso')
        ->and($rows[3][3])->toBe('A-12');
});
