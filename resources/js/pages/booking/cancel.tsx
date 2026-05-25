import { Head, Link, useForm, usePage } from "@inertiajs/react"
import * as React from "react"

type Booking = {
  public_token: string
  booking_code: string
  activity_type: string
  customer_name: string
  customer_email: string
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
}

export default function BookingCancel() {
  const page = usePage<{ booking: Booking; expired: boolean; errors: Record<string, string> }>()
  const booking = page.props.booking
  const expired = page.props.expired
  const errors = page.props.errors ?? {}

  const form = useForm<{ cancel_reason: string }>({ cancel_reason: "" })
  const [confirming, setConfirming] = React.useState(false)

  function submit() {
    form.post(`/booking/${booking.public_token}/cancel`, {
      preserveScroll: true,
      onSuccess: () => setConfirming(false),
    })
  }

  return (
    <>
      <Head title="Cancel Booking" />
      <div className="booking-success">
        <div className="wrap">
          <div className="success-header">
            <h1>Cancel Booking</h1>
            <p>Isi alasan cancel untuk melanjutkan</p>
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
            <div style={{ marginBottom: 10 }}>
              <div style={{ fontWeight: 800, fontSize: 14 }}>{booking.booking_code}</div>
              <div style={{ color: "rgba(26,39,68,0.8)", fontSize: 12 }}>
                {booking.visit_date} • {booking.time_slot.start_time} – {booking.time_slot.end_time}
              </div>
              <div style={{ color: "rgba(26,39,68,0.8)", fontSize: 12 }}>
                {booking.location.name} • {booking.zone.name} • {booking.lot.lot_number}
              </div>
            </div>

            <label style={{ display: "block", fontWeight: 700, marginBottom: 6 }}>Alasan cancel</label>
            <textarea
              value={form.data.cancel_reason}
              onChange={(e) => form.setData("cancel_reason", e.target.value)}
              rows={4}
              disabled={expired || form.processing}
              style={{
                width: "100%",
                borderRadius: 10,
                border: "1px solid rgba(26,39,68,0.2)",
                padding: 10,
                resize: "vertical",
              }}
              placeholder="Tulis alasan cancel..."
            />
            {errors.cancel_reason ? (
              <div style={{ color: "#b42318", marginTop: 6, fontSize: 12 }}>{errors.cancel_reason}</div>
            ) : null}
            {errors.cancel ? <div style={{ color: "#b42318", marginTop: 6, fontSize: 12 }}>{errors.cancel}</div> : null}

            <div className="actions" style={{ gap: 10, marginTop: 14 }}>
              <Link className="btn-secondary" href={`/booking/${booking.public_token}`}>
                Kembali
              </Link>
              {expired ? (
                <button className="btn-primary" type="button" disabled>
                  Cancel Booking
                </button>
              ) : (
                <button
                  className="btn-primary"
                  type="button"
                  disabled={!form.data.cancel_reason.trim() || form.processing}
                  onClick={() => setConfirming(true)}
                >
                  Cancel Booking
                </button>
              )}
            </div>
          </div>

          {confirming ? (
            <div
              role="dialog"
              aria-modal="true"
              style={{
                position: "fixed",
                inset: 0,
                background: "rgba(0,0,0,0.4)",
                display: "flex",
                alignItems: "center",
                justifyContent: "center",
                padding: 16,
                zIndex: 50,
              }}
              onClick={() => setConfirming(false)}
            >
              <div
                style={{
                  width: "min(520px, 100%)",
                  background: "#fff",
                  borderRadius: 12,
                  padding: 16,
                  border: "1px solid rgba(26,39,68,0.2)",
                }}
                onClick={(e) => e.stopPropagation()}
              >
                <div style={{ fontWeight: 800, fontSize: 16, marginBottom: 6 }}>Konfirmasi Cancel</div>
                <div style={{ color: "rgba(26,39,68,0.8)", fontSize: 13, marginBottom: 12 }}>
                  Anda yakin ingin membatalkan booking ini?
                </div>
                <div style={{ display: "flex", gap: 10, justifyContent: "flex-end" }}>
                  <button className="btn-secondary" type="button" onClick={() => setConfirming(false)}>
                    Batal
                  </button>
                  <button className="btn-primary" type="button" onClick={submit} disabled={form.processing}>
                    Ya, Cancel
                  </button>
                </div>
              </div>
            </div>
          ) : null}
        </div>
      </div>
    </>
  )
}

