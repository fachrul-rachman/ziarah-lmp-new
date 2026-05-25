import { Head, useForm, usePage } from "@inertiajs/react"
import * as React from "react"

import { ConfirmDialog } from "@/components/confirm-dialog"
import { Button } from "@/components/ui/button"
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card"
import {
  Dialog,
  DialogContent,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from "@/components/ui/dialog"
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

type Location = {
  id: number
  name: string
}

type EventRow = {
  id: number
  name: string
  start_date: string
  end_date: string
  locations: Location[]
  hidden_facilities: string[]
}

function formatLocations(locations: Location[]) {
  if (!locations || locations.length === 0) {
return "-"
}

  return locations.map((l) => l.name).join(", ")
}

export default function AdminEvents() {
  const page = usePage<{
    events: EventRow[]
    locations: Location[]
    facilityLabels: Record<string, string>
    errors: Record<string, string>
    flash?: { success?: string }
  }>()

  const events = page.props.events ?? []
  const locations = page.props.locations ?? []
  const facilityLabels = page.props.facilityLabels ?? {}
  const errors = page.props.errors ?? {}
  const flashSuccess = page.props.flash?.success

  const [open, setOpen] = React.useState(false)
  const [mode, setMode] = React.useState<"create" | "edit">("create")
  const [editing, setEditing] = React.useState<EventRow | null>(null)

  const form = useForm<{
    name: string
    start_date: string
    end_date: string
    location_ids: number[]
    hidden_facilities: string[]
  }>({
    name: "",
    start_date: "",
    end_date: "",
    location_ids: [],
    hidden_facilities: [],
  })

  const deleteForm = useForm({})
  const [deleteId, setDeleteId] = React.useState<number | null>(null)

  const facilityKeys = ["chairs", "burn_barrels", "tent", "prayer_table", "lamp"]

  function openCreate() {
    setMode("create")
    setEditing(null)
    form.reset()
    setOpen(true)
  }

  function openEdit(row: EventRow) {
    setMode("edit")
    setEditing(row)
    form.setData({
      name: row.name ?? "",
      start_date: row.start_date ?? "",
      end_date: row.end_date ?? "",
      location_ids: row.locations?.map((l) => l.id) ?? [],
      hidden_facilities: row.hidden_facilities ?? [],
    })
    setOpen(true)
  }

  function toggleLocation(id: number) {
    const current = form.data.location_ids

    if (current.includes(id)) {
      form.setData(
        "location_ids",
        current.filter((x) => x !== id),
      )
    } else {
      form.setData("location_ids", [...current, id])
    }
  }

  function toggleFacility(key: string) {
    const current = form.data.hidden_facilities

    if (current.includes(key)) {
      form.setData(
        "hidden_facilities",
        current.filter((x) => x !== key),
      )
    } else {
      form.setData("hidden_facilities", [...current, key])
    }
  }

  function submit() {
    if (mode === "create") {
      form.post("/admin/events", {
        onSuccess: () => {
          setOpen(false)
          form.reset()
        },
      })

      return
    }

    if (!editing) {
return
}

    form.put(`/admin/events/${editing.id}`, {
      onSuccess: () => {
        setOpen(false)
        setEditing(null)
      },
    })
  }

  return (
    <>
      <Head title="Event" />
      <AdminLayout title="Event">
        <div className="space-y-4">
          <Card>
            <CardHeader className="flex flex-row items-center justify-between gap-2">
              <CardTitle>Event</CardTitle>
              <Button onClick={openCreate}>Tambah Event</Button>
            </CardHeader>
            <CardContent>
              {flashSuccess ? (
                <p className="mb-3 text-sm text-green-700">{flashSuccess}</p>
              ) : null}

              {events.length === 0 ? (
                <p className="text-sm text-gray-600">Belum ada event.</p>
              ) : (
                <Table>
                  <TableHeader>
                    <TableRow>
                      <TableHead>Nama</TableHead>
                      <TableHead>Mulai</TableHead>
                      <TableHead>Selesai</TableHead>
                      <TableHead>Lokasi</TableHead>
                      <TableHead className="text-right">Aksi</TableHead>
                    </TableRow>
                  </TableHeader>
                  <TableBody>
                    {events.map((row) => (
                      <TableRow key={row.id}>
                        <TableCell className="font-medium">
                          {row.name}
                        </TableCell>
                        <TableCell>{row.start_date}</TableCell>
                        <TableCell>{row.end_date}</TableCell>
                        <TableCell className="max-w-[28rem] truncate">
                          {formatLocations(row.locations)}
                        </TableCell>
                        <TableCell className="text-right">
                          <div className="flex justify-end gap-2">
                            <Button
                              variant="outline"
                              onClick={() => openEdit(row)}
                            >
                              Edit
                            </Button>
                            <Button
                              variant="destructive"
                              onClick={() => setDeleteId(row.id)}
                              disabled={deleteForm.processing}
                            >
                              Hapus
                            </Button>
                          </div>
                        </TableCell>
                      </TableRow>
                    ))}
                  </TableBody>
                </Table>
              )}
            </CardContent>
          </Card>
        </div>

        <Dialog
          open={open}
          onOpenChange={(next) => {
            if (!next) {
              setOpen(false)
              setEditing(null)
            } else {
              setOpen(true)
            }
          }}
        >
          <DialogContent className="sm:max-w-2xl">
            <DialogHeader>
              <DialogTitle>
                {mode === "create" ? "Tambah Event" : "Edit Event"}
              </DialogTitle>
            </DialogHeader>

            <form
              className="space-y-4"
              onSubmit={(e) => {
                e.preventDefault()
                submit()
              }}
            >
              <div>
                <label className="mb-1 block text-sm font-medium">Nama</label>
                <Input
                  value={form.data.name}
                  onChange={(e) => form.setData("name", e.target.value)}
                />
                {errors.name ? (
                  <p className="mt-1 text-sm text-red-600">{errors.name}</p>
                ) : null}
              </div>

              <div className="grid gap-3 sm:grid-cols-2">
                <div>
                  <label className="mb-1 block text-sm font-medium">
                    Tanggal mulai
                  </label>
                  <Input
                    type="date"
                    value={form.data.start_date}
                    onChange={(e) => form.setData("start_date", e.target.value)}
                  />
                  {errors.start_date ? (
                    <p className="mt-1 text-sm text-red-600">
                      {errors.start_date}
                    </p>
                  ) : null}
                </div>
                <div>
                  <label className="mb-1 block text-sm font-medium">
                    Tanggal selesai
                  </label>
                  <Input
                    type="date"
                    value={form.data.end_date}
                    onChange={(e) => form.setData("end_date", e.target.value)}
                  />
                  {errors.end_date ? (
                    <p className="mt-1 text-sm text-red-600">
                      {errors.end_date}
                    </p>
                  ) : null}
                </div>
              </div>

              <div>
                <p className="mb-2 text-sm font-medium">
                  Lokasi terdampak (pilih minimal 1)
                </p>
                <div className="grid gap-2 sm:grid-cols-2">
                  {locations.map((l) => (
                    <label
                      key={l.id}
                      className="flex items-center gap-2 rounded-md border bg-white px-3 py-2 text-sm"
                    >
                      <input
                        type="checkbox"
                        className="h-4 w-4"
                        checked={form.data.location_ids.includes(l.id)}
                        onChange={() => toggleLocation(l.id)}
                      />
                      <span className="truncate">{l.name}</span>
                    </label>
                  ))}
                </div>
                {errors.location_ids ? (
                  <p className="mt-1 text-sm text-red-600">
                    {errors.location_ids}
                  </p>
                ) : null}
              </div>

              <div>
                <p className="mb-2 text-sm font-medium">
                  Fasilitas yang disembunyikan (opsional)
                </p>
                <div className="grid gap-2 sm:grid-cols-2">
                  {facilityKeys.map((key) => (
                    <label
                      key={key}
                      className="flex items-center gap-2 rounded-md border bg-white px-3 py-2 text-sm"
                    >
                      <input
                        type="checkbox"
                        className="h-4 w-4"
                        checked={form.data.hidden_facilities.includes(key)}
                        onChange={() => toggleFacility(key)}
                      />
                      <span>{facilityLabels[key] ?? key}</span>
                    </label>
                  ))}
                </div>
              </div>

              <DialogFooter className="gap-2 sm:gap-2">
                <Button
                  type="button"
                  variant="outline"
                  onClick={() => setOpen(false)}
                >
                  Batal
                </Button>
                <Button type="submit" disabled={form.processing}>
                  {form.processing ? "Menyimpan" : "Simpan"}
                </Button>
              </DialogFooter>
            </form>
          </DialogContent>
        </Dialog>

        <ConfirmDialog
          open={deleteId !== null}
          onOpenChange={(next) => setDeleteId(next ? deleteId : null)}
          title="Hapus event?"
          description="Event yang dihapus tidak bisa dikembalikan."
          confirmText="Hapus"
          confirmVariant="destructive"
          onConfirm={() => {
            if (deleteId === null) {
return
}

            deleteForm.delete(`/admin/events/${deleteId}`)
          }}
        />
      </AdminLayout>
    </>
  )
}
