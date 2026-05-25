import { Head, Link, router, usePage } from "@inertiajs/react"
import * as React from "react"

import { ConfirmDialog } from "@/components/confirm-dialog"
import { Button } from "@/components/ui/button"
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card"
import { AdminLayout } from "@/layouts/admin-layout"

type Booking = {
  id: number
  booking_code: string
  customer_name: string
  customer_email: string
  customer_phone?: string | null
  activity_type: string
  grave_type: string
  location: string
  zone: string
  lot: string
  visit_date: string
  time: string
  facilities: {
    chairs_count: number
    burn_barrels_count: number
    has_tent: boolean
    has_prayer_table: boolean
    has_lamp: boolean
  }
  status: string
  cancel_reason: string | null
  reschedules: Array<{
    from_date: string
    to_date: string
    from_time_slot_id: number
    to_time_slot_id: number
    created_at: string
  }>
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

export default function AdminBookingDetail() {
  const page = usePage<{ booking: Booking; flash?: { success?: string } }>()
  const booking = page.props.booking
  const flashSuccess = page.props.flash?.success

  const [openCancel, setOpenCancel] = React.useState(false)

  return (
    <>
      <Head title={`Booking ${booking.booking_code}`} />
      <AdminLayout title="Detail Booking">
        <div className="space-y-4">
          <Card>
            <CardHeader className="flex flex-row items-center justify-between gap-2">
              <CardTitle>Booking {booking.booking_code}</CardTitle>
              <Link href="/admin/dashboard">
                <Button variant="outline">Kembali</Button>
              </Link>
            </CardHeader>
            <CardContent className="space-y-2 text-sm">
              {flashSuccess ? <p className="text-sm text-green-700">{flashSuccess}</p> : null}
              <div className="grid gap-2 sm:grid-cols-2">
                <div>
                  <div className="text-gray-600">Nama</div>
                  <div className="font-medium">{booking.customer_name}</div>
                </div>
                <div>
                  <div className="text-gray-600">Email</div>
                  <div className="font-medium">{booking.customer_email}</div>
                </div>
                <div>
                  <div className="text-gray-600">Nomor Telepon</div>
                  <div className="font-medium">
                    {booking.customer_phone
                      ? booking.customer_phone.startsWith("62")
                        ? `+${booking.customer_phone}`
                        : booking.customer_phone
                      : "-"}
                  </div>
                </div>
                <div>
                  <div className="text-gray-600">Jenis kegiatan</div>
                  <div className="font-medium">{labelActivity(booking.activity_type)}</div>
                </div>
                <div>
                  <div className="text-gray-600">Jenis makam</div>
                  <div className="font-medium">{labelGrave(booking.grave_type)}</div>
                </div>
                <div>
                  <div className="text-gray-600">Lokasi</div>
                  <div className="font-medium">{booking.location}</div>
                </div>
                <div>
                  <div className="text-gray-600">Zona</div>
                  <div className="font-medium">{booking.zone}</div>
                </div>
                <div>
                  <div className="text-gray-600">Lot</div>
                  <div className="font-medium">{booking.lot}</div>
                </div>
                <div>
                  <div className="text-gray-600">Tanggal & Jam</div>
                  <div className="font-medium">
                    {booking.visit_date} • {booking.time}
                  </div>
                </div>
                <div>
                  <div className="text-gray-600">Status</div>
                  <div className="font-medium">{booking.status}</div>
                </div>
                {booking.cancel_reason ? (
                  <div>
                    <div className="text-gray-600">Alasan cancel</div>
                    <div className="font-medium">{booking.cancel_reason}</div>
                  </div>
                ) : null}
              </div>

              <div className="mt-3 rounded-md border bg-white p-3">
                <div className="mb-1 text-sm font-medium">Fasilitas</div>
                <div className="text-sm text-gray-700">Kursi: {booking.facilities.chairs_count}</div>
                <div className="text-sm text-gray-700">Tong bakar: {booking.facilities.burn_barrels_count}</div>
                <div className="text-sm text-gray-700">Tenda: {booking.facilities.has_tent ? "Ya" : "Tidak"}</div>
                <div className="text-sm text-gray-700">
                  Meja sembahyang: {booking.facilities.has_prayer_table ? "Ya" : "Tidak"}
                </div>
                <div className="text-sm text-gray-700">Lampu: {booking.facilities.has_lamp ? "Ya" : "Tidak"}</div>
              </div>

              <div className="mt-3 flex gap-2">
                <Button variant="destructive" onClick={() => setOpenCancel(true)} disabled={booking.status === "cancelled"}>
                  Cancel Booking
                </Button>
              </div>
            </CardContent>
          </Card>

          <Card>
            <CardHeader>
              <CardTitle>Histori Reschedule</CardTitle>
            </CardHeader>
            <CardContent>
              {booking.reschedules.length === 0 ? (
                <p className="text-sm text-gray-600">Belum ada histori reschedule.</p>
              ) : (
                <div className="space-y-2 text-sm">
                  {booking.reschedules.map((r, idx) => (
                    <div key={idx} className="rounded-md border bg-white p-3">
                      <div>
                        Dari {r.from_date} (slot {r.from_time_slot_id}) → {r.to_date} (slot {r.to_time_slot_id})
                      </div>
                      <div className="text-xs text-gray-600">{r.created_at}</div>
                    </div>
                  ))}
                </div>
              )}
            </CardContent>
          </Card>
        </div>

        <ConfirmDialog
          open={openCancel}
          onOpenChange={setOpenCancel}
          title="Cancel booking?"
          description="Admin cancel tidak perlu alasan dan tidak mengirim email."
          confirmText="Cancel"
          confirmVariant="destructive"
          onConfirm={() => {
            router.post(`/admin/bookings/${booking.id}/cancel`, {})
          }}
        />
      </AdminLayout>
    </>
  )
}
