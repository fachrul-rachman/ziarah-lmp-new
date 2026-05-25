import { Head, useForm, usePage } from "@inertiajs/react"
import * as React from "react"

import { ConfirmDialog } from "@/components/confirm-dialog"
import { Button } from "@/components/ui/button"
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card"
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from "@/components/ui/dialog"
import { Input } from "@/components/ui/input"
import { AdminLayout } from "@/layouts/admin-layout"

type Lot = {
  id: number
  zone_id: number
  grave_type: "makam" | "kotak_abu"
  lot_number: string
  size: string
}

type Zone = {
  id: number
  location_id: number
  name: string
  lots_count: number
}

type Location = {
  id: number
  name: string
  zones_count: number
  lots_count: number
  zones: Zone[]
}

export default function AdminLocationsIndex() {
  const page = usePage<{
    locations: Location[]
    errors: Record<string, string>
    flash?: { success?: string; import_job_id?: number }
  }>()

  const locations = page.props.locations ?? []
  const errors = page.props.errors ?? {}
  const flashSuccess = page.props.flash?.success

  const locationCreateForm = useForm<{ name: string }>({ name: "" })
  const locationUpdateForm = useForm<{ name: string }>({ name: "" })
  const locationDeleteForm = useForm({})
  const [editLocation, setEditLocation] = React.useState<Location | null>(null)
  const [deleteLocationId, setDeleteLocationId] = React.useState<number | null>(null)

  const zoneCreateForm = useForm<{ name: string }>({ name: "" })
  const zoneUpdateForm = useForm<{ name: string }>({ name: "" })
  const zoneDeleteForm = useForm({})
  const [editZone, setEditZone] = React.useState<{ zone: Zone; location: Location } | null>(null)
  const [deleteZone, setDeleteZone] = React.useState<{ zoneId: number; locationId: number } | null>(null)

  const lotCreateForm = useForm({
    location_id: "",
    zone_id: "",
    grave_type: "",
    lot_number: "",
    size: "",
  })
  const lotUpdateForm = useForm({
    location_id: "",
    zone_id: "",
    grave_type: "",
    lot_number: "",
    size: "",
  })
  const lotDeleteForm = useForm({})
  const [editLot, setEditLot] = React.useState<Lot | null>(null)
  const [deleteLotId, setDeleteLotId] = React.useState<number | null>(null)

  const importForm = useForm<{ file: File | null }>({ file: null })
  const [importJob, setImportJob] = React.useState<any | null>(null)

  const [openLocationIds, setOpenLocationIds] = React.useState<number[]>([])
  const [openZoneIds, setOpenZoneIds] = React.useState<number[]>([])

  const [zoneLots, setZoneLots] = React.useState<Record<number, Lot[]>>({})
  const [zoneLotsLoading, setZoneLotsLoading] = React.useState<Record<number, boolean>>({})

  const toggleLocation = (locationId: number) => {
    setOpenLocationIds((prev) =>
      prev.includes(locationId) ? prev.filter((id) => id !== locationId) : [...prev, locationId],
    )
  }

  const toggleZone = async (zoneId: number) => {
    const isOpen = openZoneIds.includes(zoneId)
    setOpenZoneIds((prev) =>
      isOpen ? prev.filter((id) => id !== zoneId) : [...prev, zoneId],
    )

    if (!isOpen && !zoneLots[zoneId]) {
      setZoneLotsLoading((prev) => ({ ...prev, [zoneId]: true }))

      try {
        const res = await fetch(`/admin/zones/${zoneId}/lots`, {
          headers: { Accept: "application/json" },
        })

        if (res.ok) {
          const data = (await res.json()) as { lots: Lot[] }
          setZoneLots((prev) => ({ ...prev, [zoneId]: data.lots }))
        }
      } finally {
        setZoneLotsLoading((prev) => ({ ...prev, [zoneId]: false }))
      }
    }
  }

  const refreshZoneLots = async (zoneId: number) => {
    setZoneLotsLoading((prev) => ({ ...prev, [zoneId]: true }))

    try {
      const res = await fetch(`/admin/zones/${zoneId}/lots`, {
        headers: { Accept: "application/json" },
      })

      if (res.ok) {
        const data = (await res.json()) as { lots: Lot[] }
        setZoneLots((prev) => ({ ...prev, [zoneId]: data.lots }))
      }
    } finally {
      setZoneLotsLoading((prev) => ({ ...prev, [zoneId]: false }))
    }
  }

  const pollImportJob = React.useCallback(async (jobId: number) => {
    const res = await fetch(`/admin/import-jobs/${jobId}`, {
      headers: { Accept: "application/json" },
    })

    if (!res.ok) {
      return
    }

    setImportJob(await res.json())
  }, [])

  React.useEffect(() => {
    const jobId = page.props.flash?.import_job_id
    if (!jobId) {
      return
    }

    // Avoid synchronous setState inside effect (lint rule).
    const t = window.setTimeout(() => pollImportJob(jobId), 0)

    return () => window.clearTimeout(t)
  }, [page.props.flash?.import_job_id, pollImportJob])

  React.useEffect(() => {
    if (!importJob) {
      return
    }

    if (importJob.status === "completed" || importJob.status === "failed") {
      return
    }

    const t = window.setInterval(() => pollImportJob(importJob.id), 1500)

    return () => window.clearInterval(t)
  }, [importJob, pollImportJob])

  return (
    <>
      <Head title="Lokasi dan Lot" />
      <AdminLayout title="Lokasi dan Lot">
        <div className="space-y-4">
          <Card>
            <CardHeader>
              <CardTitle>Upload Excel Lot</CardTitle>
            </CardHeader>
            <CardContent>
              <form
                className="flex flex-col gap-3 sm:flex-row sm:items-end"
                onSubmit={(e) => {
                  e.preventDefault()
                  importForm.post("/admin/lots/import", { forceFormData: true })
                }}
              >
                <div className="w-full sm:max-w-md">
                  <label className="mb-1 block text-sm font-medium">
                    File Excel (xlsx/xls/csv)
                  </label>
                  <Input
                    type="file"
                    accept=".xlsx,.xls,.csv"
                    onChange={(e) =>
                      importForm.setData("file", e.target.files?.[0] ?? null)
                    }
                  />
                  {errors.file ? (
                    <p className="mt-1 text-sm text-red-600">{errors.file}</p>
                  ) : null}
                </div>
                <Button type="submit" disabled={importForm.processing}>
                  {importForm.processing ? "Memproses…" : "Upload & Import"}
                </Button>
              </form>

              {importJob ? (
                <div className="mt-3 rounded-lg border border-gray-200 bg-white p-3 text-sm">
                  <div className="font-medium">
                    Job #{importJob.id} - {importJob.status}
                  </div>
                  <div className="text-gray-700">{importJob.filename}</div>
                  <div className="text-gray-700">
                    Processed: {importJob.processed_rows}/{importJob.total_rows} | Success:{" "}
                    {importJob.success_rows} | Failed: {importJob.failed_rows}
                  </div>
                  {importJob.errors?.length ? (
                    <div className="mt-2 text-red-700">
                      <div className="font-medium">Error terbaru:</div>
                      <ul className="list-disc pl-5">
                        {importJob.errors.map((e: any, idx: number) => (
                          <li key={idx}>
                            Row {e.row_number}: {e.error_message}
                          </li>
                        ))}
                      </ul>
                    </div>
                  ) : null}
                </div>
              ) : null}
            </CardContent>
          </Card>

          <Card>
            <CardHeader>
              <CardTitle>Tambah Lokasi</CardTitle>
            </CardHeader>
            <CardContent>
              <form
                className="flex flex-col gap-3 sm:flex-row sm:items-end"
                onSubmit={(e) => {
                  e.preventDefault()
                  locationCreateForm.post("/admin/locations", {
                    onSuccess: () => locationCreateForm.reset("name"),
                  })
                }}
              >
                <div className="w-full sm:max-w-md">
                  <label className="mb-1 block text-sm font-medium">
                    Nama lokasi
                  </label>
                  <Input
                    value={locationCreateForm.data.name}
                    onChange={(e) =>
                      locationCreateForm.setData("name", e.target.value)
                    }
                  />
                  {errors.name ? (
                    <p className="mt-1 text-sm text-red-600">{errors.name}</p>
                  ) : null}
                </div>
                <Button type="submit" disabled={locationCreateForm.processing}>
                  {locationCreateForm.processing ? "Memproses…" : "Tambah"}
                </Button>
              </form>

              {flashSuccess ? (
                <p className="mt-3 text-sm text-green-700">{flashSuccess}</p>
              ) : null}
              {errors.location ? (
                <p className="mt-3 text-sm text-red-600">{errors.location}</p>
              ) : null}
            </CardContent>
          </Card>

          {locations.length === 0 ? (
            <p className="text-sm text-gray-600">Belum ada lokasi.</p>
          ) : (
            <div className="space-y-3">
              {locations.map((loc) => {
                const isOpen = openLocationIds.includes(loc.id)

                return (
                  <Card key={loc.id}>
                    <CardHeader>
                      <div className="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
                        <button
                          type="button"
                          className="text-left"
                          onClick={() => toggleLocation(loc.id)}
                        >
                          <div className="text-base font-semibold">{loc.name}</div>
                          <div className="mt-1 text-sm text-gray-600">
                            Total zona: {loc.zones_count} • Total lot: {loc.lots_count}
                          </div>
                        </button>
                        <div className="flex gap-2">
                          <Button
                            variant="outline"
                            onClick={() => {
                              setEditLocation(loc)
                              locationUpdateForm.setData("name", loc.name)
                            }}
                          >
                            Edit
                          </Button>
                          <Button
                            variant="destructive"
                            onClick={() => setDeleteLocationId(loc.id)}
                            disabled={locationDeleteForm.processing}
                          >
                            Hapus
                          </Button>
                        </div>
                      </div>
                    </CardHeader>

                    {isOpen ? (
                      <CardContent>
                        <div className="space-y-2">
                          <div className="rounded-lg border border-gray-200 bg-white p-3">
                            <div className="text-sm font-medium">Tambah Zona</div>
                            <form
                              className="mt-2 flex flex-col gap-3 sm:flex-row sm:items-end"
                              onSubmit={(e) => {
                                e.preventDefault()
                                zoneCreateForm.post(`/admin/locations/${loc.id}/zones`, {
                                  onSuccess: () => zoneCreateForm.reset("name"),
                                })
                              }}
                            >
                              <div className="w-full sm:max-w-md">
                                <label className="mb-1 block text-sm font-medium">
                                  Nama zona
                                </label>
                                <Input
                                  value={zoneCreateForm.data.name}
                                  onChange={(e) =>
                                    zoneCreateForm.setData("name", e.target.value)
                                  }
                                />
                              </div>
                              <Button type="submit" disabled={zoneCreateForm.processing}>
                                {zoneCreateForm.processing ? "Memproses…" : "Tambah"}
                              </Button>
                            </form>
                            {errors.zone ? (
                              <p className="mt-2 text-sm text-red-600">{errors.zone}</p>
                            ) : null}
                          </div>

                          {loc.zones.length === 0 ? (
                            <p className="text-sm text-gray-600">Belum ada zona.</p>
                          ) : (
                            <div className="space-y-2">
                              {loc.zones.map((z) => {
                                const zOpen = openZoneIds.includes(z.id)
                                const lotsForZone = zoneLots[z.id] ?? []
                                const loading = zoneLotsLoading[z.id] ?? false

                                return (
                                  <div
                                    key={z.id}
                                    className="rounded-lg border border-gray-200 bg-white p-3"
                                  >
                                    <div className="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
                                      <button
                                        type="button"
                                        className="text-left"
                                        onClick={() => toggleZone(z.id)}
                                      >
                                        <div className="font-medium">{z.name}</div>
                                        <div className="mt-1 text-sm text-gray-600">
                                          Total lot: {z.lots_count}
                                        </div>
                                      </button>
                                      <div className="flex gap-2">
                                        <Button
                                          variant="outline"
                                          onClick={() => {
                                            setEditZone({ zone: z, location: loc })
                                            zoneUpdateForm.setData("name", z.name)
                                          }}
                                        >
                                          Edit
                                        </Button>
                                        <Button
                                          variant="destructive"
                                          onClick={() =>
                                            setDeleteZone({ zoneId: z.id, locationId: loc.id })
                                          }
                                          disabled={zoneDeleteForm.processing}
                                        >
                                          Hapus
                                        </Button>
                                        <Button
                                          variant="outline"
                                          onClick={() => {
                                            lotCreateForm.setData({
                                              location_id: String(loc.id),
                                              zone_id: String(z.id),
                                              grave_type: "",
                                              lot_number: "",
                                              size: "",
                                            })
                                            setEditLot({ id: 0, zone_id: z.id, grave_type: "makam", lot_number: "", size: "" })
                                          }}
                                        >
                                          + Lot
                                        </Button>
                                        <Button
                                          variant="outline"
                                          onClick={() => {
                                            setOpenZoneIds((prev) =>
                                              prev.includes(z.id) ? prev : [...prev, z.id],
                                            )
                                          }}
                                          title="Buka untuk melihat daftar lot"
                                        >
                                          Lihat Lot
                                        </Button>
                                      </div>
                                    </div>

                                    {zOpen ? (
                                      <div className="mt-3 space-y-3">
                                        <div className="flex flex-wrap gap-2">
                                          {loading ? (
                                            <span className="text-sm text-gray-600">Loading lot…</span>
                                          ) : lotsForZone.length === 0 ? (
                                            <span className="text-sm text-gray-600">Belum ada lot.</span>
                                          ) : (
                                            lotsForZone.map((lot) => (
                                              <button
                                                key={lot.id}
                                                type="button"
                                                className="rounded-full border border-gray-300 bg-white px-3 py-1 text-sm hover:bg-gray-50"
                                                title={`${lot.grave_type} • ${lot.size}`}
                                                onClick={() => {
                                                  setEditLot(lot)
                                                  lotUpdateForm.setData({
                                                    location_id: String(loc.id),
                                                    zone_id: String(z.id),
                                                    grave_type: lot.grave_type,
                                                    lot_number: lot.lot_number,
                                                    size: lot.size,
                                                  })
                                                }}
                                              >
                                                {lot.lot_number}
                                              </button>
                                            ))
                                          )}
                                        </div>
                                        <div className="pt-1">
                                          <Button
                                            variant="outline"
                                            onClick={() => refreshZoneLots(z.id)}
                                            disabled={loading}
                                          >
                                            Refresh
                                          </Button>
                                        </div>
                                      </div>
                                    ) : null}
                                  </div>
                                )
                              })}
                            </div>
                          )}
                        </div>
                      </CardContent>
                    ) : null}
                  </Card>
                )
              })}
            </div>
          )}
        </div>

        <Dialog open={editLocation !== null} onOpenChange={(open) => setEditLocation(open ? editLocation : null)}>
          <DialogContent>
            <DialogHeader>
              <DialogTitle>Edit lokasi</DialogTitle>
              <DialogDescription>Ubah nama lokasi.</DialogDescription>
            </DialogHeader>
            <div>
              <label className="mb-1 block text-sm font-medium">Nama lokasi</label>
              <Input
                value={locationUpdateForm.data.name}
                onChange={(e) => locationUpdateForm.setData("name", e.target.value)}
              />
            </div>
            <DialogFooter>
              <Button variant="outline" onClick={() => setEditLocation(null)}>
                Batal
              </Button>
              <Button
                onClick={() => {
                  if (!editLocation) {
return
}

                  locationUpdateForm.put(`/admin/locations/${editLocation.id}`, {
                    onSuccess: () => setEditLocation(null),
                  })
                }}
                disabled={locationUpdateForm.processing}
              >
                {locationUpdateForm.processing ? "Memproses…" : "Simpan"}
              </Button>
            </DialogFooter>
          </DialogContent>
        </Dialog>

        <ConfirmDialog
          open={deleteLocationId !== null}
          onOpenChange={(open) => setDeleteLocationId(open ? deleteLocationId : null)}
          title="Hapus lokasi?"
          description="Lokasi tidak bisa dihapus jika masih punya zona/lot aktif."
          confirmText="Hapus"
          confirmVariant="destructive"
          onConfirm={() => {
            if (deleteLocationId === null) {
return
}

            locationDeleteForm.delete(`/admin/locations/${deleteLocationId}`)
          }}
        />

        <Dialog open={editZone !== null} onOpenChange={(open) => setEditZone(open ? editZone : null)}>
          <DialogContent>
            <DialogHeader>
              <DialogTitle>Edit zona</DialogTitle>
              <DialogDescription>Ubah nama zona.</DialogDescription>
            </DialogHeader>
            <div>
              <label className="mb-1 block text-sm font-medium">Nama zona</label>
              <Input
                value={zoneUpdateForm.data.name}
                onChange={(e) => zoneUpdateForm.setData("name", e.target.value)}
              />
            </div>
            <DialogFooter>
              <Button variant="outline" onClick={() => setEditZone(null)}>
                Batal
              </Button>
              <Button
                onClick={() => {
                  if (!editZone) {
return
}

                  zoneUpdateForm.put(
                    `/admin/locations/${editZone.location.id}/zones/${editZone.zone.id}`,
                    { onSuccess: () => setEditZone(null) },
                  )
                }}
                disabled={zoneUpdateForm.processing}
              >
                {zoneUpdateForm.processing ? "Memproses…" : "Simpan"}
              </Button>
            </DialogFooter>
          </DialogContent>
        </Dialog>

        <ConfirmDialog
          open={deleteZone !== null}
          onOpenChange={(open) => setDeleteZone(open ? deleteZone : null)}
          title="Hapus zona?"
          description="Zona tidak bisa dihapus jika masih punya lot aktif."
          confirmText="Hapus"
          confirmVariant="destructive"
          onConfirm={() => {
            if (!deleteZone) {
return
}

            zoneDeleteForm.delete(`/admin/locations/${deleteZone.locationId}/zones/${deleteZone.zoneId}`)
          }}
        />

        <Dialog open={editLot !== null} onOpenChange={(open) => setEditLot(open ? editLot : null)}>
          <DialogContent>
            <DialogHeader>
              <DialogTitle>{editLot?.id ? "Edit lot" : "Tambah lot"}</DialogTitle>
              <DialogDescription>
                {editLot?.id ? "Ubah data lot." : "Tambah lot manual untuk zona ini."}
              </DialogDescription>
            </DialogHeader>
            <div className="space-y-3">
              <div>
                <label className="mb-1 block text-sm font-medium">Jenis makam</label>
                <Input
                  value={editLot?.id ? lotUpdateForm.data.grave_type : lotCreateForm.data.grave_type}
                  onChange={(e) =>
                    (editLot?.id ? lotUpdateForm : lotCreateForm).setData(
                      "grave_type",
                      e.target.value,
                    )
                  }
                  placeholder="makam / kotak_abu"
                />
              </div>
              <div>
                <label className="mb-1 block text-sm font-medium">Nomor lot</label>
                <Input
                  value={editLot?.id ? lotUpdateForm.data.lot_number : lotCreateForm.data.lot_number}
                  onChange={(e) =>
                    (editLot?.id ? lotUpdateForm : lotCreateForm).setData(
                      "lot_number",
                      e.target.value,
                    )
                  }
                />
              </div>
              <div>
                <label className="mb-1 block text-sm font-medium">Ukuran</label>
                <Input
                  value={editLot?.id ? lotUpdateForm.data.size : lotCreateForm.data.size}
                  onChange={(e) =>
                    (editLot?.id ? lotUpdateForm : lotCreateForm).setData(
                      "size",
                      e.target.value,
                    )
                  }
                />
              </div>
            </div>
            <DialogFooter>
              <Button variant="outline" onClick={() => setEditLot(null)}>
                Batal
              </Button>
              <Button
                onClick={() => {
                  if (!editLot) {
return
}

                  if (editLot.id) {
                    lotUpdateForm.put(`/admin/lots/${editLot.id}`, {
                      onSuccess: () => setEditLot(null),
                    })

                    return
                  }

                  lotCreateForm.post("/admin/lots", {
                    onSuccess: () => setEditLot(null),
                  })
                }}
                disabled={lotUpdateForm.processing || lotCreateForm.processing}
              >
                {lotUpdateForm.processing || lotCreateForm.processing ? "Memproses…" : "Simpan"}
              </Button>
              {editLot?.id ? (
                <Button
                  variant="destructive"
                  onClick={() => {
                    setDeleteLotId(editLot.id)
                    setEditLot(null)
                  }}
                >
                  Hapus
                </Button>
              ) : null}
            </DialogFooter>
          </DialogContent>
        </Dialog>

        <ConfirmDialog
          open={deleteLotId !== null}
          onOpenChange={(open) => setDeleteLotId(open ? deleteLotId : null)}
          title="Hapus lot?"
          description="Lot akan soft delete jika pernah dipakai booking."
          confirmText="Hapus"
          confirmVariant="destructive"
          onConfirm={() => {
            if (deleteLotId === null) {
return
}

            lotDeleteForm.delete(`/admin/lots/${deleteLotId}`)
          }}
        />
      </AdminLayout>
    </>
  )
}
