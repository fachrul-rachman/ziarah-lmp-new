<?php

use App\Services\Exports\AdminBookingExport;

function firstDataRow(array $exportArray): ?array
{
    foreach ($exportArray as $row) {
        if (($row[0] ?? null) === '1') {
            return $row;
        }
    }

    return null;
}

test('admin booking export falls back to visit_date + time_range when visit_schedule missing', function () {
    $rows = [[
        'activity_type' => 'ziarah',
        'activity_label' => 'Ziarah',
        'visit_date' => '2026-06-05',
        'time_range' => '08:00 - 08:59',
        'location' => 'Lokasi A',
        'customer_name' => 'Budi',
        'grave_label' => 'Makam',
        'zone' => 'Zona 1',
        'lot' => '10',
    ]];

    $export = new AdminBookingExport($rows, '2026-06-05', '2026-06-05');
    $dataRow = firstDataRow($export->array());

    expect($dataRow)->not->toBeNull();
    expect($dataRow[2] ?? null)->toBe('05 Jun 2026, 08:00');
});

test('admin booking export uses explicit visit_schedule when provided', function () {
    $rows = [[
        'activity_type' => 'ziarah',
        'activity_label' => 'Ziarah',
        'visit_schedule' => 'Custom Schedule',
        'visit_date' => '2026-06-05',
        'time_range' => '08:00 - 08:59',
    ]];

    $export = new AdminBookingExport($rows, '2026-06-05', '2026-06-05');
    $dataRow = firstDataRow($export->array());

    expect($dataRow)->not->toBeNull();
    expect($dataRow[2] ?? null)->toBe('Custom Schedule');
});

test('admin booking export fallback works when activity column is hidden', function () {
    $rows = [[
        'activity_type' => 'ziarah',
        'visit_date' => '2026-06-05',
        'time_sort' => '09:00',
    ]];

    $export = new AdminBookingExport($rows, '2026-06-05', '2026-06-05', hideActivityColumn: true);
    $dataRow = firstDataRow($export->array());

    expect($dataRow)->not->toBeNull();
    expect($dataRow[1] ?? null)->toBe('05 Jun 2026, 09:00');
});

