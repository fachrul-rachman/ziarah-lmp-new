import { Head, Link, usePage } from "@inertiajs/react"
import * as React from "react"

type Booking = {
  id: number
  public_token: string
  booking_code: string
  activity_type: string
  customer_name: string
  customer_email: string
  customer_phone?: string | null
  additional_note?: string | null
  grave_type: string
  visit_date: string
  location: { id: number; name: string }
  zone: { id: number; name: string }
  lot: { id: number; lot_number: string; size: string }
  time_slot: { id: number; start_time: string; end_time: string }
  facilities: {
    chairs_count: number
    burn_barrels_count: number
    has_tent: boolean
    has_prayer_table: boolean
    has_lamp: boolean
  }
  status: string
  cancel_reason?: string | null
}

function labelActivity(v: string) {
  return (
    {
      ziarah: "Ziarah",
      naik_batu: "Naik Batu",
      start_work: "Start Work",
      wang_san: "Wang San",
    }[v] ?? v
  )
}

function labelGrave(v: string) {
  return v === "kotak_abu" ? "Kotak Abu" : "Makam"
}

function labelStatus(v: string) {
  return (
    {
      confirmed: "Confirmed",
      rescheduled: "Rescheduled",
      cancelled: "Cancelled",
      completed: "Completed",
    }[v] ?? v
  )
}

export default function BookingShow() {
  const page = usePage<{ booking: Booking; expired: boolean }>()
  const booking = page.props.booking
  const expired = page.props.expired
  const [copied, setCopied] = React.useState(false)

  async function copyCode() {
    try {
      await navigator.clipboard.writeText(booking.booking_code)
      setCopied(true)
      window.setTimeout(() => setCopied(false), 1500)
    } catch {
      // ignore
    }
  }

  const facilities = [
    { label: "Kursi", value: String(booking.facilities.chairs_count), on: booking.facilities.chairs_count > 0 },
    {
      label: "Tong bakar",
      value: String(booking.facilities.burn_barrels_count),
      on: booking.facilities.burn_barrels_count > 0,
    },
    { label: "Tenda", value: booking.facilities.has_tent ? "Ya" : "Tidak", on: booking.facilities.has_tent },
    {
      label: "Meja sembahyang",
      value: booking.facilities.has_prayer_table ? "Ya" : "Tidak",
      on: booking.facilities.has_prayer_table,
    },
    { label: "Lampu", value: booking.facilities.has_lamp ? "Ya" : "Tidak", on: booking.facilities.has_lamp },
  ]

  return (
    <>
      <Head title="Detail Booking" />
      <div className="booking-success">
        <div className="wrap">
          <div className="success-header">
            <h1>Detail Booking</h1>
            <p>Cek detail, cancel, atau reschedule booking</p>
            <div className="status-pill">
              <div className="status-dot" />
              {labelStatus(booking.status)}
            </div>
          </div>

          {expired ? (
            <div
              style={{
                background: "#fff3cd",
                border: "1px solid rgba(26,39,68,0.2)",
                borderRadius: 10,
                padding: 10,
                marginBottom: 12,
                color: "#1a2744",
              }}
            >
              Masa berlaku aksi sudah habis.
            </div>
          ) : null}

          <div className="card">
            <div className="code-block">
              <div className="code-label">Kode Booking</div>
              <div className="code-value">{booking.booking_code}</div>
              <button className="code-copy" type="button" onClick={copyCode}>
                {copied ? "✓ Tersalin!" : "Salin Kode"}
              </button>
            </div>

            <div className="info-grid">
              <div className="info-cell">
                <div className="info-key">Jenis kegiatan</div>
                <div className="info-val">{labelActivity(booking.activity_type)}</div>
              </div>
              <div className="info-cell">
                <div className="info-key">Tanggal &amp; Jam</div>
                <div className="info-val">
                  {booking.visit_date}
                  <br />
                  {booking.time_slot.start_time} – {booking.time_slot.end_time}
                </div>
              </div>

              <div className="divider-row" />

              <div className="info-cell">
                <div className="info-key">Lokasi</div>
                <div className="info-val">{booking.location.name}</div>
              </div>
              <div className="info-cell">
                <div className="info-key">Zona</div>
                <div className="info-val">{booking.zone.name}</div>
              </div>

              <div className="divider-row" />

              <div className="info-cell">
                <div className="info-key">Jenis makam</div>
                <div className="info-val">{labelGrave(booking.grave_type)}</div>
              </div>
              <div className="info-cell">
                <div className="info-key">Nomor lot</div>
                <div className="info-val">
                  {booking.lot.lot_number} ({booking.lot.size})
                </div>
              </div>

              <div className="divider-row" />

              <div className="info-cell">
                <div className="info-key">Nama</div>
                <div className="info-val">{booking.customer_name}</div>
              </div>
              <div className="info-cell">
                <div className="info-key">Email</div>
                <div className="info-val" style={{ wordBreak: "break-all", fontSize: 12 }}>
                  {booking.customer_email}
                </div>
              </div>
              <div className="info-cell">
                <div className="info-key">Nomor telepon</div>
                <div className="info-val" style={{ wordBreak: "break-all", fontSize: 12 }}>
                  {booking.customer_phone
                    ? booking.customer_phone.startsWith("62")
                      ? `+${booking.customer_phone}`
                      : booking.customer_phone
                    : "-"}
                </div>
              </div>
            </div>

            <div className="fac-section">
              <div className="fac-label">Fasilitas</div>
              <div className="fac-box">
                <div className="fac-grid">
                  {facilities.map((f) => (
                    <div className="fac-item" key={f.label}>
                      <div className={`fac-dot ${f.on ? "on" : "off"}`} />
                      {f.label}: <b>{f.value}</b>
                    </div>
                  ))}
                </div>
              </div>
            </div>

            {booking.additional_note ? (
              <div className="fac-section">
                <div className="fac-label">Catatan tambahan</div>
                <div className="fac-box">
                  <div style={{ whiteSpace: "pre-line", color: "#1a2744", lineHeight: 1.6 }}>
                    {booking.additional_note}
                  </div>
                </div>
              </div>
            ) : null}

            <div className="actions" style={{ gap: 10 }}>
              <a className="btn-primary" href={`/booking/pdf/${booking.public_token}`}>
                Unduh Bukti Booking
              </a>
              {expired ? (
                <>
                  <button className="btn-secondary" type="button" disabled>
                    Reschedule
                  </button>
                  <button className="btn-secondary" type="button" disabled>
                    Cancel
                  </button>
                </>
              ) : (
                <>
                  <Link className="btn-secondary" href={`/booking/${booking.public_token}/reschedule`}>
                    Reschedule
                  </Link>
                  <Link className="btn-secondary" href={`/booking/${booking.public_token}/cancel`}>
                    Cancel
                  </Link>
                </>
              )}
            </div>
          </div>
        </div>
      </div>
    </>
  )
}
