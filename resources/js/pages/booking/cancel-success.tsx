import { Head, Link, usePage } from "@inertiajs/react"

type Booking = {
  public_token: string
  booking_code: string
  visit_date: string
  time_slot: { id: number; start_time: string; end_time: string }
  location: { id: number; name: string }
  zone: { id: number; name: string }
  lot: { id: number; lot_number: string; size: string }
  status: string
}

export default function BookingCancelSuccess() {
  const page = usePage<{ booking: Booking }>()
  const booking = page.props.booking

  return (
    <>
      <Head title="Cancel Berhasil" />
      <div className="booking-success">
        <div className="wrap">
          <div className="success-header">
            <div className="check-ring" aria-hidden>
              <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                <path
                  d="M5 12l5 5L20 7"
                  stroke="#C9A84C"
                  strokeWidth="2.5"
                  strokeLinecap="round"
                  strokeLinejoin="round"
                />
              </svg>
            </div>
            <h1>Cancel Berhasil</h1>
            <p>Booking Anda berhasil dibatalkan</p>
            <div className="status-pill">
              <div className="status-dot" />
              Cancelled
            </div>
          </div>

          <div className="card">
            <div style={{ marginBottom: 10 }}>
              <div style={{ fontWeight: 800, fontSize: 14 }}>{booking.booking_code}</div>
              <div style={{ color: "rgba(26,39,68,0.8)", fontSize: 12 }}>
                {booking.visit_date} • {booking.time_slot.start_time} – {booking.time_slot.end_time}
              </div>
              <div style={{ color: "rgba(26,39,68,0.8)", fontSize: 12 }}>
                {booking.location.name} • {booking.zone.name} • {booking.lot.lot_number}
              </div>
            </div>

            <div className="actions" style={{ gap: 10 }}>
              <Link className="btn-secondary" href="/">
                Buat Booking Baru
              </Link>
              <Link className="btn-primary" href={`/booking/${booking.public_token}`}>
                Lihat Detail
              </Link>
            </div>
          </div>
        </div>
      </div>
    </>
  )
}

