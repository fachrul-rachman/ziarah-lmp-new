@php
    /** @var \App\Models\Booking $booking */
    $booking = $booking ?? null;

    $activityLabel = [
        'ziarah'    => 'Ziarah',
        'naik_batu' => 'Naik Batu',
        'start_work'=> 'Start Work',
        'wang_san'  => 'Wang San',
    ][$booking->activity_type] ?? $booking->activity_type;

    $graveLabel = $booking->grave_type === 'kotak_abu' ? 'Kotak Abu' : 'Makam';

    $start = \Carbon\CarbonImmutable::parse($booking->timeSlot->start_time)->format('H:i');
    $end   = \Carbon\CarbonImmutable::parse($booking->timeSlot->start_time)->addMinutes(59)->format('H:i');

    $facilities = [
        'Kursi'           => (int)($booking->facilities->chairs_count ?? 0),
        'Tong bakar'      => (int)($booking->facilities->burn_barrels_count ?? 0),
        'Tenda'           => ($booking->facilities->has_tent ?? false) ? 'Ya' : 'Tidak',
        'Meja sembahyang' => ($booking->facilities->has_prayer_table ?? false) ? 'Ya' : 'Tidak',
        'Lampu'           => ($booking->facilities->has_lamp ?? false) ? 'Ya' : 'Tidak',
    ];
@endphp

<table width="100%" cellpadding="0" cellspacing="0" border="0" style="font-family: ui-sans-serif, system-ui, Arial, sans-serif; color: #1a1a1a;">
<tr><td align="center" style="padding: 24px 16px;">
<table width="600" cellpadding="0" cellspacing="0" border="0" style="max-width: 600px; width: 100%;">

    {{-- Label atas --}}
    <tr>
        <td style="font-size: 11px; letter-spacing: 0.12em; text-transform: uppercase; color: #999; padding-bottom: 6px;">
            Konfirmasi Pemesanan
        </td>
    </tr>

    {{-- Judul --}}
    <tr>
        <td style="font-size: 26px; font-weight: 400; color: #1a1a1a; font-family: Georgia, serif; padding-bottom: 8px;">
            Booking Berhasil
        </td>
    </tr>

    {{-- Salam --}}
    <tr>
        <td style="font-size: 15px; color: #555; padding-bottom: 24px;">
            Salam Sejahtera Bapak/Ibu <strong style="color: #1a1a1a;">{{ $booking->customer_name }}</strong>,<br>
            Berikut konfirmasi detail Ziarah:
        </td>
    </tr>

    {{-- Card --}}
    <tr>
        <td style="border: 1px solid #e5e5e5; border-radius: 12px; overflow: hidden;">
        <table width="100%" cellpadding="0" cellspacing="0" border="0">

            {{-- Header navy --}}
            <tr>
                <td style="background-color: #1a2744; padding: 14px 20px; border-radius: 12px 12px 0 0;">
                <table width="100%" cellpadding="0" cellspacing="0" border="0">
                    <tr>
                        <td>
                            <div style="font-size: 11px; letter-spacing: 0.1em; text-transform: uppercase; color: rgba(255,255,255,0.55); margin-bottom: 4px;">Kode Booking</div>
                            <div style="font-family: 'Courier New', Courier, monospace; font-size: 17px; font-weight: 700; color: #ffffff; letter-spacing: 0.04em;">{{ $booking->booking_code }}</div>
                        </td>
                        <td align="right" valign="middle">
                            <span style="background: rgba(255,255,255,0.15); border-radius: 20px; padding: 5px 12px; font-size: 11px; color: rgba(255,255,255,0.85); letter-spacing: 0.06em; text-transform: uppercase;">{{ $activityLabel }}</span>
                        </td>
                    </tr>
                </table>
                </td>
            </tr>

            {{-- Grid detail pakai table 2 kolom --}}
            <tr>
                <td style="padding: 0;">
                <table width="100%" cellpadding="0" cellspacing="0" border="0">

                    {{-- Row 1: Lokasi | Zona --}}
                    <tr>
                        <td width="50%" style="padding: 12px 12px 12px 20px; border-bottom: 1px solid #f0f0f0; border-right: 1px solid #f0f0f0;">
                            <div style="font-size: 11px; color: #999; text-transform: uppercase; letter-spacing: 0.08em; margin-bottom: 3px;">Lokasi</div>
                            <div style="font-size: 14px; font-weight: 600; color: #1a1a1a;">{{ $booking->location->name ?? '-' }}</div>
                        </td>
                        <td width="50%" style="padding: 12px 20px 12px 12px; border-bottom: 1px solid #f0f0f0;">
                            <div style="font-size: 11px; color: #999; text-transform: uppercase; letter-spacing: 0.08em; margin-bottom: 3px;">Zona</div>
                            <div style="font-size: 14px; font-weight: 600; color: #1a1a1a;">{{ $booking->zone->name ?? '-' }}</div>
                        </td>
                    </tr>

                    {{-- Row 2: Jenis Makam | Lot --}}
                    <tr>
                        <td width="50%" style="padding: 12px 12px 12px 20px; border-bottom: 1px solid #f0f0f0; border-right: 1px solid #f0f0f0;">
                            <div style="font-size: 11px; color: #999; text-transform: uppercase; letter-spacing: 0.08em; margin-bottom: 3px;">Jenis Makam</div>
                            <div style="font-size: 14px; font-weight: 600; color: #1a1a1a;">{{ $graveLabel }}</div>
                        </td>
                        <td width="50%" style="padding: 12px 20px 12px 12px; border-bottom: 1px solid #f0f0f0;">
                            <div style="font-size: 11px; color: #999; text-transform: uppercase; letter-spacing: 0.08em; margin-bottom: 3px;">Lot</div>
                            <div style="font-size: 14px; font-weight: 600; color: #1a1a1a;">{{ $booking->lot->lot_number ?? '-' }} <span style="font-weight: 400; color: #999;">({{ $booking->lot->size ?? '-' }})</span></div>
                        </td>
                    </tr>

                    {{-- Row 3: Tanggal | Jam --}}
                    <tr>
                        <td width="50%" style="padding: 12px 12px 12px 20px; border-right: 1px solid #f0f0f0;">
                            <div style="font-size: 11px; color: #999; text-transform: uppercase; letter-spacing: 0.08em; margin-bottom: 3px;">Tanggal</div>
                            <div style="font-size: 14px; font-weight: 600; color: #1a1a1a;">{{ optional($booking->visit_date)->format('Y-m-d') }}</div>
                        </td>
                        <td width="50%" style="padding: 12px 20px 12px 12px;">
                            <div style="font-size: 11px; color: #999; text-transform: uppercase; letter-spacing: 0.08em; margin-bottom: 3px;">Jam</div>
                            <div style="font-size: 14px; font-weight: 600; color: #1a1a1a;">{{ $start }} – {{ $end }}</div>
                        </td>
                    </tr>

                </table>
                </td>
            </tr>

            {{-- Fasilitas --}}
            <tr>
                <td style="background-color: #fafafa; border-top: 1px solid #f0f0f0; padding: 14px 20px; border-radius: 0 0 12px 12px;">
                    <div style="font-size: 11px; color: #999; text-transform: uppercase; letter-spacing: 0.08em; margin-bottom: 10px;">Fasilitas</div>
                    @foreach ($facilities as $label => $value)
                        <span style="display: inline-block; background: #ffffff; border: 1px solid #e5e5e5; border-radius: 20px; padding: 4px 12px; font-size: 12px; color: #555; margin: 0 4px 6px 0;">
                            {{ $label }}: <strong style="color: #1a1a1a;">{{ $value }}</strong>
                        </span>
                    @endforeach
                    @if (filled($booking->additional_note))
                        <div style="margin-top: 12px;">
                            <div style="font-size: 11px; color: #999; text-transform: uppercase; letter-spacing: 0.08em; margin-bottom: 6px;">Catatan tambahan</div>
                            <div style="font-size: 13px; line-height: 1.6; color: #1a1a1a; white-space: pre-line;">{{ $booking->additional_note }}</div>
                        </div>
                    @endif
                </td>
            </tr>

        </table>
        </td>
    </tr>

    {{-- Teks tombol --}}
    <tr>
        <td style="font-size: 15px; color: #555; padding: 20px 0 12px;">
            <strong>Notes:</strong><br><br>
            - Diharap hadir sesuai reservasi untuk kenyamanan selama proses kunjungan.<br>
            - No Tipping/ Pungli.<br>
            - Silahkan hubungi kami di 0877-8808-8820 (chat only) untuk info dan pengaduan.<br><br>
            Terima kasih perhatian dan kepercayaan Bapak/Ibu.<br><br>
            Hormat Kami,<br>
            <strong>Lestari Memorial Park</strong><br><br>
            Reschedule atau pembatalan:
        </td>
    </tr>

    {{-- Tombol aksi --}}
    <tr>
        <td>
        <table cellpadding="0" cellspacing="0" border="0">
            <tr>
                <td style="padding-right: 10px;">
                    <a href="{{ url('/booking/'.$booking->public_token.'/reschedule') }}"
                       style="display: inline-block; padding: 10px 18px; border-radius: 8px; border: 1px solid #d0d0d0; background-color: #ffffff; color: #1a1a1a; text-decoration: none; font-size: 13px; font-weight: 500;">
                        Reschedule Booking
                    </a>
                </td>
                <td>
                    <a href="{{ url('/booking/'.$booking->public_token.'/cancel') }}"
                       style="display: inline-block; padding: 10px 18px; border-radius: 8px; border: 1px solid #fca5a5; background-color: #fef2f2; color: #b91c1c; text-decoration: none; font-size: 13px; font-weight: 500;">
                        Cancel Booking
                    </a>
                </td>
            </tr>
        </table>
        </td>
    </tr>

</table>
</td></tr>
</table>
