import { Head, useForm, usePage } from "@inertiajs/react"
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

type TimeSlot = {
  id: number
  start_time: string
  end_time: string
}

export default function AdminTimeSlots() {
  const page = usePage<{
    timeSlots: TimeSlot[]
    errors: Record<string, string>
    flash?: { success?: string }
  }>()

  const timeSlots = page.props.timeSlots ?? []
  const errors = page.props.errors ?? {}
  const flashSuccess = page.props.flash?.success

  const createForm = useForm<{ start_time: string }>({ start_time: "" })
  const bulkForm = useForm<{ start_time: string; end_time: string }>({
    start_time: "",
    end_time: "",
  })
  const deleteForm = useForm({})
  const [deleteId, setDeleteId] = React.useState<number | null>(null)

  return (
    <>
      <Head title="Time Slots" />
      <AdminLayout title="Time Slots">
        <div className="space-y-4">
          <Card>
            <CardHeader>
              <CardTitle>Tambah Time Slot</CardTitle>
            </CardHeader>
            <CardContent>
              <form
                className="flex flex-col gap-3 sm:flex-row sm:items-end"
                onSubmit={(e) => {
                  e.preventDefault()
                  createForm.post("/admin/time-slots", {
                    onSuccess: () => createForm.reset("start_time"),
                  })
                }}
              >
                <div className="w-full sm:max-w-xs">
                  <label className="mb-1 block text-sm font-medium">
                    Jam mulai (HH:MM)
                  </label>
                  <Input
                    type="time"
                    value={createForm.data.start_time}
                    onChange={(e) =>
                      createForm.setData("start_time", e.target.value)
                    }
                  />
                  {errors.start_time ? (
                    <p className="mt-1 text-sm text-red-600">
                      {errors.start_time}
                    </p>
                  ) : null}
                </div>

                <Button type="submit" disabled={createForm.processing}>
                  {createForm.processing ? "Memproses…" : "Tambah"}
                </Button>
              </form>

              {flashSuccess ? (
                <p className="mt-3 text-sm text-green-700">{flashSuccess}</p>
              ) : null}
              {errors.time_slot ? (
                <p className="mt-3 text-sm text-red-600">{errors.time_slot}</p>
              ) : null}
            </CardContent>
          </Card>

          <Card>
            <CardHeader>
              <CardTitle>Bulk Generate</CardTitle>
            </CardHeader>
            <CardContent>
              <form
                className="grid gap-3 sm:grid-cols-3 sm:items-end"
                onSubmit={(e) => {
                  e.preventDefault()
                  bulkForm.post("/admin/time-slots/bulk", {
                    onSuccess: () => bulkForm.reset(),
                  })
                }}
              >
                <div>
                  <label className="mb-1 block text-sm font-medium">
                    Jam mulai
                  </label>
                  <Input
                    type="time"
                    value={bulkForm.data.start_time}
                    onChange={(e) =>
                      bulkForm.setData("start_time", e.target.value)
                    }
                  />
                  {errors.start_time ? (
                    <p className="mt-1 text-sm text-red-600">
                      {errors.start_time}
                    </p>
                  ) : null}
                </div>
                <div>
                  <label className="mb-1 block text-sm font-medium">
                    Jam akhir
                  </label>
                  <Input
                    type="time"
                    value={bulkForm.data.end_time}
                    onChange={(e) =>
                      bulkForm.setData("end_time", e.target.value)
                    }
                  />
                  {errors.end_time ? (
                    <p className="mt-1 text-sm text-red-600">
                      {errors.end_time}
                    </p>
                  ) : null}
                </div>
                <div className="flex gap-2">
                  <Button
                    type="submit"
                    disabled={bulkForm.processing}
                    className="w-full"
                  >
                    {bulkForm.processing ? "Memproses…" : "Generate"}
                  </Button>
                  <Button
                    type="button"
                    variant="outline"
                    onClick={() => {
                      bulkForm.setData({
                        start_time: "00:00",
                        end_time: "23:00",
                      })
                      bulkForm.post("/admin/time-slots/bulk", {
                        preserveScroll: true,
                        onSuccess: () => bulkForm.reset(),
                      })
                    }}
                    disabled={bulkForm.processing}
                    className="whitespace-nowrap"
                    title="Buat time slot 00:00 sampai 23:00 (step 60 menit)"
                  >
                    24 Jam
                  </Button>
                </div>
              </form>
              <p className="mt-2 text-sm text-gray-600">
                Step otomatis 60 menit. Slot yang sudah ada akan dilewati.
              </p>
            </CardContent>
          </Card>

          <Card>
            <CardHeader>
              <CardTitle>Daftar Time Slots</CardTitle>
            </CardHeader>
            <CardContent>
              {timeSlots.length === 0 ? (
                <p className="text-sm text-gray-600">
                  Belum ada time slot. Tambah time slot baru.
                </p>
              ) : (
                <Table>
                  <TableHeader>
                    <TableRow>
                      <TableHead>Mulai</TableHead>
                      <TableHead>Selesai</TableHead>
                      <TableHead className="text-right">Aksi</TableHead>
                    </TableRow>
                  </TableHeader>
                  <TableBody>
                    {timeSlots.map((slot) => (
                      <TableRow key={slot.id}>
                        <TableCell className="font-medium">
                          {slot.start_time}
                        </TableCell>
                        <TableCell>{slot.end_time}</TableCell>
                        <TableCell className="text-right">
                          <Button
                            variant="destructive"
                            onClick={() => setDeleteId(slot.id)}
                            disabled={deleteForm.processing}
                          >
                            Hapus
                          </Button>
                        </TableCell>
                      </TableRow>
                    ))}
                  </TableBody>
                </Table>
              )}
            </CardContent>
          </Card>
        </div>

        <ConfirmDialog
          open={deleteId !== null}
          onOpenChange={(open) => setDeleteId(open ? deleteId : null)}
          title="Hapus time slot?"
          description="Time slot yang dipakai booking aktif tidak bisa dihapus."
          confirmText="Hapus"
          confirmVariant="destructive"
          onConfirm={() => {
            if (deleteId === null) {
return
}

            deleteForm.delete(`/admin/time-slots/${deleteId}`)
          }}
        />
      </AdminLayout>
    </>
  )
}
