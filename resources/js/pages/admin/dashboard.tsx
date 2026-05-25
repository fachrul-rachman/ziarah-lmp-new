import { Head, Link, router, usePage } from "@inertiajs/react"
import * as React from "react"

import { ConfirmDialog } from "@/components/confirm-dialog"
import { Button } from "@/components/ui/button"
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card"
import { Input } from "@/components/ui/input"
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from "@/components/ui/table"
import { AdminLayout } from "@/layouts/admin-layout"

type BookingRow = {
  id: number
  customer_name: string
  customer_phone?: string | null
  activity_type: string
  location: string
  zone: string
  lot: string
  visit_date: string
  time: string
  facilities: string
  status: string
}

type Option = { id: number; name: string }

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

function labelStatus(v: string) {
  return (
    {
      confirmed: "confirmed",
      rescheduled: "rescheduled",
      cancelled: "cancelled",
      completed: "completed",
    }[v] ?? v
  )
}

export default function AdminDashboard() {
  const page = usePage<{
    filters: {
      date?: string
      activity_type?: string
      location_id?: number
      zone_id?: number
      status?: string
    }
    bookings: BookingRow[]
    pagination: {
      current_page: number
      last_page: number
      per_page: number
      total: number
      links: Array<{ url: string | null; label: string; active: boolean }>
    }
    locations: Option[]
    zones: Option[]
    errors: Record<string, string>
    flash?: { success?: string; export_job_id?: number }
    latestExportJobId?: number
  }>()

  const bookings = page.props.bookings ?? []
  const locations = page.props.locations ?? []
  const zones = page.props.zones ?? []
  const errors = page.props.errors ?? {}
  const flashSuccess = page.props.flash?.success
  const initialFilters = page.props.filters ?? {}

  const [filters, setFilters] = React.useState({
    date: initialFilters.date ?? "",
    activity_type: initialFilters.activity_type ?? "",
    location_id: initialFilters.location_id ? String(initialFilters.location_id) : "",
    zone_id: initialFilters.zone_id ? String(initialFilters.zone_id) : "",
    status: initialFilters.status ?? "",
  })

  const [cancelId, setCancelId] = React.useState<number | null>(null)
  const [exportFormat, setExportFormat] = React.useState<"excel" | "pdf">("excel")
  const [exportJob, setExportJob] = React.useState<null | {
    id: number
    status: string
    download_url: string | null
    error_message: string | null
    format: string
  }>(null)

  const exportJobId = page.props.flash?.export_job_id ?? page.props.latestExportJobId ?? null

  const pollExportJob = React.useCallback(async (id: number) => {
    const res = await fetch(`/admin/exports/${id}`, { headers: { Accept: "application/json" } })
    if (!res.ok) return
    setExportJob(await res.json())
  }, [])

  React.useEffect(() => {
    if (!exportJobId) return
    void pollExportJob(exportJobId)
  }, [exportJobId, pollExportJob])

  React.useEffect(() => {
    if (!exportJob) return
    if (exportJob.status === "completed" || exportJob.status === "failed") return
    const t = window.setInterval(() => void pollExportJob(exportJob.id), 1500)
    return () => window.clearInterval(t)
  }, [exportJob, pollExportJob])

  function applyFilters() {
    const params: Record<string, string> = {}
    if (filters.date) params.date = filters.date
    if (filters.activity_type) params.activity_type = filters.activity_type
    if (filters.location_id) params.location_id = filters.location_id
    if (filters.zone_id) params.zone_id = filters.zone_id
    if (filters.status) params.status = filters.status
    router.get("/admin/dashboard", params, { preserveScroll: true })
  }

  function resetFilters() {
    setFilters({ date: "", activity_type: "", location_id: "", zone_id: "", status: "" })
    router.get("/admin/dashboard", {}, { preserveScroll: true })
  }

  function doExport() {
    const payload: Record<string, string> = { format: exportFormat }
    if (filters.date) payload.date = filters.date
    if (filters.activity_type) payload.activity_type = filters.activity_type
    if (filters.location_id) payload.location_id = filters.location_id
    if (filters.zone_id) payload.zone_id = filters.zone_id
    if (filters.status) payload.status = filters.status

    router.post("/admin/exports", payload, { preserveScroll: true })
  }

  return (
    <>
      <Head title="Dashboard" />
      <AdminLayout title="Dashboard">
        <div className="space-y-4">
          <Card>
            <CardHeader>
              <CardTitle>Filter</CardTitle>
            </CardHeader>
            <CardContent>
              <div className="grid gap-3 md:grid-cols-5">
                <div>
                  <label className="mb-1 block text-sm font-medium">Tanggal</label>
                  <Input
                    type="date"
                    value={filters.date}
                    onChange={(e) => setFilters((p) => ({ ...p, date: e.target.value }))}
                  />
                </div>
                <div>
                  <label className="mb-1 block text-sm font-medium">Jenis kegiatan</label>
                  <select
                    className="h-10 w-full rounded-md border px-3 text-sm"
                    value={filters.activity_type}
                    onChange={(e) => setFilters((p) => ({ ...p, activity_type: e.target.value }))}
                  >
                    <option value="">Semua</option>
                    <option value="ziarah">Ziarah</option>
                    <option value="naik_batu">Naik Batu</option>
                    <option value="start_work">Start Work</option>
                    <option value="wang_san">Wang San</option>
                  </select>
                </div>
                <div>
                  <label className="mb-1 block text-sm font-medium">Lokasi</label>
                  <select
                    className="h-10 w-full rounded-md border px-3 text-sm"
                    value={filters.location_id}
                    onChange={(e) => setFilters((p) => ({ ...p, location_id: e.target.value }))}
                  >
                    <option value="">Semua</option>
                    {locations.map((l) => (
                      <option key={l.id} value={l.id}>
                        {l.name}
                      </option>
                    ))}
                  </select>
                </div>
                <div>
                  <label className="mb-1 block text-sm font-medium">Zona</label>
                  <select
                    className="h-10 w-full rounded-md border px-3 text-sm"
                    value={filters.zone_id}
                    onChange={(e) => setFilters((p) => ({ ...p, zone_id: e.target.value }))}
                  >
                    <option value="">Semua</option>
                    {zones.map((z) => (
                      <option key={z.id} value={z.id}>
                        {z.name}
                      </option>
                    ))}
                  </select>
                </div>
                <div>
                  <label className="mb-1 block text-sm font-medium">Status</label>
                  <select
                    className="h-10 w-full rounded-md border px-3 text-sm"
                    value={filters.status}
                    onChange={(e) => setFilters((p) => ({ ...p, status: e.target.value }))}
                  >
                    <option value="">Semua</option>
                    <option value="confirmed">confirmed</option>
                    <option value="rescheduled">rescheduled</option>
                    <option value="cancelled">cancelled</option>
                    <option value="completed">completed</option>
                  </select>
                </div>
              </div>

              {errors.date || errors.activity_type || errors.location_id || errors.zone_id || errors.status ? (
                <p className="mt-2 text-sm text-red-600">Filter tidak valid.</p>
              ) : null}

              <div className="mt-3 flex flex-col gap-2 sm:flex-row">
                <Button onClick={applyFilters}>Terapkan</Button>
                <Button variant="outline" onClick={resetFilters}>
                  Reset
                </Button>
              </div>
            </CardContent>
          </Card>

          <Card>
            <CardHeader className="flex flex-row items-center justify-between gap-2">
              <CardTitle>Export</CardTitle>
              <div className="flex items-center gap-2">
                <select
                  className="h-10 rounded-md border px-3 text-sm"
                  value={exportFormat}
                  onChange={(e) => setExportFormat(e.target.value as "excel" | "pdf")}
                >
                  <option value="excel">Excel</option>
                  <option value="pdf">PDF</option>
                </select>
                <Button onClick={doExport}>Export</Button>
              </div>
            </CardHeader>
            <CardContent>
              {flashSuccess ? <p className="text-sm text-green-700">{flashSuccess}</p> : null}
              {exportJob ? (
                <div className="mt-2 rounded-md border bg-white p-3 text-sm">
                  <div className="font-medium">
                    Export #{exportJob.id} - {exportJob.status} ({exportJob.format})
                  </div>
                  {exportJob.status === "completed" && exportJob.download_url ? (
                    <a className="mt-2 inline-block text-sm text-blue-700 underline" href={exportJob.download_url}>
                      Download file export
                    </a>
                  ) : null}
                  {exportJob.status === "failed" ? (
                    <p className="mt-2 text-sm text-red-600">{exportJob.error_message ?? "Export gagal."}</p>
                  ) : null}
                </div>
              ) : null}
            </CardContent>
          </Card>

          <Card>
            <CardHeader>
              <CardTitle>Tabel Booking</CardTitle>
            </CardHeader>
            <CardContent>
              {bookings.length === 0 ? (
                <p className="text-sm text-gray-600">Tidak ada booking ditemukan.</p>
              ) : (
                <>
                  <div className="hidden md:block">
                    <Table>
                      <TableHeader>
                        <TableRow>
                          <TableHead>Nama</TableHead>
                          <TableHead>Jenis Kegiatan</TableHead>
                          <TableHead>Lokasi / Zona / Lot</TableHead>
                          <TableHead>Tanggal dan Jam</TableHead>
                          <TableHead>Fasilitas</TableHead>
                          <TableHead>Status</TableHead>
                          <TableHead className="text-right">Aksi</TableHead>
                        </TableRow>
                      </TableHeader>
                      <TableBody>
                        {bookings.map((b) => (
                          <TableRow key={b.id}>
                            <TableCell className="font-medium">
                              <div>{b.customer_name}</div>
                              {b.customer_phone ? (
                                <div className="text-xs text-muted-foreground">
                                  {b.customer_phone.startsWith("62")
                                    ? `+${b.customer_phone}`
                                    : b.customer_phone}
                                </div>
                              ) : null}
                            </TableCell>
                            <TableCell>{labelActivity(b.activity_type)}</TableCell>
                            <TableCell>
                              {b.location}
                              <div className="text-xs text-gray-600">
                                {b.zone} • {b.lot}
                              </div>
                            </TableCell>
                            <TableCell>
                              {b.visit_date}
                              <div className="text-xs text-gray-600">{b.time}</div>
                            </TableCell>
                            <TableCell className="max-w-[18rem] truncate" title={b.facilities}>
                              {b.facilities}
                            </TableCell>
                            <TableCell>{labelStatus(b.status)}</TableCell>
                            <TableCell className="text-right">
                              <div className="flex justify-end gap-2">
                                <Link href={`/admin/bookings/${b.id}`}>
                                  <Button variant="outline">Lihat Detail</Button>
                                </Link>
                                <Button variant="destructive" onClick={() => setCancelId(b.id)}>
                                  Cancel
                                </Button>
                              </div>
                            </TableCell>
                          </TableRow>
                        ))}
                      </TableBody>
                    </Table>
                  </div>

                  <div className="grid gap-3 md:hidden">
                    {bookings.map((b) => (
                      <div key={b.id} className="rounded-lg border bg-white p-3 text-sm">
                        <div className="font-semibold">{b.customer_name}</div>
                        {b.customer_phone ? (
                          <div className="text-xs text-muted-foreground">
                            {b.customer_phone.startsWith("62")
                              ? `+${b.customer_phone}`
                              : b.customer_phone}
                          </div>
                        ) : null}
                        <div className="text-gray-700">{labelActivity(b.activity_type)}</div>
                        <div className="mt-1 text-gray-700">
                          {b.location} • {b.zone} • {b.lot}
                        </div>
                        <div className="mt-1 text-gray-700">
                          {b.visit_date} • {b.time}
                        </div>
                        <div className="mt-2 text-xs text-gray-700">{b.facilities}</div>
                        <div className="mt-2 text-xs text-gray-600">Status: {labelStatus(b.status)}</div>
                        <div className="mt-3 flex gap-2">
                          <Link className="flex-1" href={`/admin/bookings/${b.id}`}>
                            <Button className="w-full" variant="outline">
                              Detail
                            </Button>
                          </Link>
                          <Button className="flex-1" variant="destructive" onClick={() => setCancelId(b.id)}>
                            Cancel
                          </Button>
                        </div>
                      </div>
                    ))}
                  </div>

                  <div className="mt-4 flex flex-wrap gap-2">
                    {page.props.pagination.links.map((l, idx) => {
                      const label = l.label.replace(/&laquo;|&raquo;/g, "").trim() || l.label
                      return (
                        <button
                          key={idx}
                          type="button"
                          disabled={!l.url}
                          onClick={() => l.url && router.visit(l.url, { preserveScroll: true })}
                          className={`rounded-md border px-3 py-1 text-sm ${
                            l.active ? "bg-gray-900 text-white" : "bg-white"
                          } ${!l.url ? "opacity-50" : ""}`}
                        >
                          {label}
                        </button>
                      )
                    })}
                  </div>
                </>
              )}
            </CardContent>
          </Card>
        </div>

        <ConfirmDialog
          open={cancelId !== null}
          onOpenChange={(open) => setCancelId(open ? cancelId : null)}
          title="Cancel booking?"
          description="Admin cancel tidak perlu alasan dan tidak mengirim email."
          confirmText="Cancel"
          confirmVariant="destructive"
          onConfirm={() => {
            if (!cancelId) return
            router.post(`/admin/bookings/${cancelId}/cancel`, {}, { preserveScroll: true })
          }}
        />
      </AdminLayout>
    </>
  )
}
