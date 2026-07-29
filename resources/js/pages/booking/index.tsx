import { Head, useForm, usePage } from "@inertiajs/react"
import * as React from "react"

import { EthicsConfirmationDialog } from "@/components/ethics-confirmation-dialog"
import { IconDownload } from "@tabler/icons-react"
import { IconMapPin } from "@tabler/icons-react"
import { IconSearch } from "@tabler/icons-react"


type Location = { id: number; name: string }
type TimeSlot = { id: number; start_time: string; end_time: string }
type Zone = { id: number; name: string }
type Lot = { id: number; lot_number: string; size: string }
type LotSizeRule = {
  normalized_size: string
  display_size: string
  chairs_min: number
  chairs_max: number
  burn_barrels_min: number
  burn_barrels_max: number
  tent_allowed: boolean
  prayer_table_allowed: boolean
  lamp_allowed: boolean
}
type HiddenFacilityReason = { facility_key: string; event_names: string[] }
type BookingNotice = {
  title: string
  body: string
  image_url?: string | null
  download_url?: string | null
}

const STEPS = ["Lokasi", "Zona & Lot", "Fasilitas", "Data Diri"] as const

const ACTIVITY_OPTIONS = [
  { value: "ziarah", label: "Ziarah" },
  { value: "naik_batu", label: "Naik Batu" },
  { value: "start_work", label: "Start Work" },
  { value: "wang_san", label: "Wang San" },
] as const

const GRAVE_OPTIONS = [
  { value: "makam", label: "Makam" },
  { value: "kotak_abu", label: "Kotak Abu" },
] as const

const MONTHS = [
  "Januari",
  "Februari",
  "Maret",
  "April",
  "Mei",
  "Juni",
  "Juli",
  "Agustus",
  "September",
  "Oktober",
  "November",
  "Desember",
]

function jakartaTodayStart(): Date {
  // Create a date representing today's date in Asia/Jakarta (UTC+7) at local midnight.
  const now = new Date()
  const utcMs = now.getTime() + now.getTimezoneOffset() * 60000
  const jkt = new Date(utcMs + 7 * 60 * 60000)
  jkt.setHours(0, 0, 0, 0)
  return jkt
}

function ymdFromDate(d: Date): string {
  const y = d.getFullYear()
  const m = String(d.getMonth() + 1).padStart(2, "0")
  const day = String(d.getDate()).padStart(2, "0")
  return `${y}-${m}-${day}`
}

function ymdToDate(ymd: string): Date | null {
  const m = /^(\d{4})-(\d{2})-(\d{2})$/.exec(ymd)
  if (!m) return null
  const y = Number(m[1])
  const mo = Number(m[2]) - 1
  const d = Number(m[3])
  const dt = new Date(y, mo, d)
  if (Number.isNaN(dt.getTime())) return null
  dt.setHours(0, 0, 0, 0)
  return dt
}

function minBookingDateYmd(): string {
  const d = jakartaTodayStart()
  d.setDate(d.getDate() + 2)
  return ymdFromDate(d)
}

function maxBookingDateYmd(): string {
  const d = jakartaTodayStart()
  d.setDate(d.getDate() + 100)
  return ymdFromDate(d)
}

type BookingState = {
  step: 1 | 2 | 3 | 4
  activity_type: string
  location_id: number | null
  grave_type: string
  zone_id: number | null
  lot_id: number | null
  booking_date: string | null
  time_slot_id: number | null
  zone_search: string
  lot_search: string
  chairs_count: number
  burn_barrels_count: number
  has_tent: boolean
  has_prayer_table: boolean
  has_lamp: boolean
  name: string
  email: string
  phone: string
  additional_note: string
  cal_year: number
  cal_month: number
}

function initialCalendar(): { cal_year: number; cal_month: number } {
  const min = ymdToDate(minBookingDateYmd()) ?? new Date()
  return { cal_year: min.getFullYear(), cal_month: min.getMonth() }
}

export default function BookingIndex() {
  const page = usePage<{
    locations: Location[]
    timeSlots: TimeSlot[]
    errors: Record<string, string>
    ethics_image_url?: string | null
    booking_notice?: BookingNotice | null
  }>()

  const locations = page.props.locations ?? []
  const timeSlots = page.props.timeSlots ?? []
  const errors = page.props.errors ?? {}

  const calInit = React.useMemo(() => initialCalendar(), [])
  const [state, setState] = React.useState<BookingState>({
    step: 1,
    activity_type: "ziarah",
    location_id: null,
    grave_type: "makam",
    zone_id: null,
    lot_id: null,
    booking_date: null,
    time_slot_id: null,
    zone_search: "",
    lot_search: "",
    chairs_count: 5,
    burn_barrels_count: 0,
    has_tent: false,
    has_prayer_table: false,
    has_lamp: false,
    name: "",
    email: "",
    phone: "",
    additional_note: "",
    cal_year: calInit.cal_year,
    cal_month: calInit.cal_month,
  })

  const [zones, setZones] = React.useState<Zone[]>([])
  const [lots, setLots] = React.useState<(Lot & { normalized_size?: string })[]>([])
  const [hiddenFacilities, setHiddenFacilities] = React.useState<string[]>([])
  const [hiddenFacilityReasons, setHiddenFacilityReasons] = React.useState<HiddenFacilityReason[]>([])
  const [defaultSizeRule, setDefaultSizeRule] = React.useState<LotSizeRule | null>(null)
  const [sizeRules, setSizeRules] = React.useState<Record<string, LotSizeRule>>({})
  const [confirmationOpen, setConfirmationOpen] = React.useState(false)

  const postForm = useForm({
    activity_type: "",
    location_id: "",
    grave_type: "",
    zone_id: "",
    visit_date: "",
    time_slot_id: "",
    lot_id: "",
    chairs_count: 5,
    burn_barrels_count: 0,
    has_tent: false,
    has_prayer_table: false,
    has_lamp: false,
    customer_name: "",
    customer_email: "",
    customer_phone: "",
    additional_note: "",
    ethics_confirmed: false,
  })

  const minDateYmd = React.useMemo(() => minBookingDateYmd(), [])
  const maxDateYmd = React.useMemo(() => maxBookingDateYmd(), [])

  const selectedTimeSlot = React.useMemo(() => {
    if (!state.time_slot_id) return null
    return timeSlots.find((t) => t.id === state.time_slot_id) ?? null
  }, [state.time_slot_id, timeSlots])

  const showLamp = React.useMemo(() => {
    const start = selectedTimeSlot?.start_time
    if (!start) return false
    const [h] = start.split(":").map((x) => Number(x))
    return h >= 19 || h <= 3
  }, [selectedTimeSlot])

  const filteredZones = React.useMemo(() => {
    const q = state.zone_search.trim().toLowerCase()
    if (!q) return zones
    return zones.filter((z) => z.name.toLowerCase().includes(q))
  }, [zones, state.zone_search])

  const filteredLots = React.useMemo(() => {
    const q = state.lot_search.trim().toLowerCase()
    if (!q) return lots
    return lots.filter((l) => l.lot_number.toLowerCase().includes(q))
  }, [lots, state.lot_search])

  const selectedLot = React.useMemo(() => {
    if (!state.lot_id) return null
    return lots.find((l) => l.id === state.lot_id) ?? null
  }, [lots, state.lot_id])

  const currentSizeRule = React.useMemo(() => {
    const key = (selectedLot?.normalized_size ?? "").toLowerCase()
    if (key && sizeRules[key]) return sizeRules[key]
    return defaultSizeRule
  }, [defaultSizeRule, selectedLot?.normalized_size, sizeRules])

  const hidden = React.useMemo(() => new Set(hiddenFacilities), [hiddenFacilities])
  const facilityLabels = React.useMemo<Record<string, string>>(
    () => ({
      chairs: "Kursi",
      burn_barrels: "Tong Bakar",
      tent: "Tenda",
      prayer_table: "Meja sembahyang",
      lamp: "Lampu",
    }),
    [],
  )

  const hiddenNotes = React.useMemo(() => {
    if (hiddenFacilityReasons.length === 0) return []
    return hiddenFacilityReasons.map((r) => {
      const facility = facilityLabels[r.facility_key] ?? r.facility_key
      const names = (r.event_names ?? []).filter(Boolean).join(", ")
      const eventPart = names ? `selama event ${names}` : "selama event"
      return `${eventPart}, ${facility} tidak tersedia.`
    })
  }, [hiddenFacilityReasons, facilityLabels])

  async function fetchZones(locationId: number, graveType: string) {
    const res = await fetch(
      `/booking/zones?location_id=${encodeURIComponent(String(locationId))}&grave_type=${encodeURIComponent(graveType)}`,
      { headers: { Accept: "application/json" } },
    )
    if (!res.ok) return
    const data = await res.json()
    setZones(data.zones ?? [])
  }

  async function fetchLotSizeRules() {
    const res = await fetch(`/booking/lot-size-rules`, { headers: { Accept: "application/json" } })
    if (!res.ok) return
    const data = await res.json()
    setDefaultSizeRule(data.default_rule ?? null)
    setSizeRules(data.rules ?? {})
  }

  async function fetchLots(next: {
    location_id: number
    zone_id: number
    grave_type: string
    booking_date: string
    time_slot_id: number
  }) {
    const params = new URLSearchParams({
      location_id: String(next.location_id),
      zone_id: String(next.zone_id),
      grave_type: next.grave_type,
      visit_date: next.booking_date,
      time_slot_id: String(next.time_slot_id),
    })
    const res = await fetch(`/booking/lots?${params.toString()}`, {
      headers: { Accept: "application/json" },
    })
    if (!res.ok) return
    const data = await res.json()
    setLots(data.lots ?? [])
  }

  async function fetchHiddenFacilities(next: {
    location_id: number
    booking_date: string
  }) {
    const params = new URLSearchParams({
      location_id: String(next.location_id),
      visit_date: next.booking_date,
    })
    const res = await fetch(`/booking/hidden-facilities?${params.toString()}`, {
      headers: { Accept: "application/json" },
    })
    if (!res.ok) return
    const data = await res.json()
    setHiddenFacilities(data.hidden_facilities ?? [])
    setHiddenFacilityReasons(data.hidden_facility_reasons ?? [])
  }

  // When location or grave type changes, reset downstream state and reload zones.
  React.useEffect(() => {
    if (!state.location_id) {
      setZones([])
      setLots([])
      setHiddenFacilities([])
      setHiddenFacilityReasons([])
      return
    }

    setState((prev) => ({
      ...prev,
      zone_id: null,
      lot_id: null,
      zone_search: "",
      lot_search: "",
    }))
    setLots([])

    void fetchZones(state.location_id, state.grave_type)
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [state.location_id, state.grave_type])

  React.useEffect(() => {
    void fetchLotSizeRules()
  }, [])

  // When zone, date, or time changes, refresh lots.
  React.useEffect(() => {
    if (
      !state.location_id ||
      !state.zone_id ||
      !state.booking_date ||
      !state.time_slot_id
    ) {
      setLots([])
      return
    }

    setState((prev) => ({ ...prev, lot_id: null, lot_search: "" }))

    void fetchLots({
      location_id: state.location_id,
      zone_id: state.zone_id,
      grave_type: state.grave_type,
      booking_date: state.booking_date,
      time_slot_id: state.time_slot_id,
    })
    void fetchHiddenFacilities({
      location_id: state.location_id,
      booking_date: state.booking_date,
    })
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [state.zone_id, state.booking_date, state.time_slot_id])

  // Enforce lamp rules when time slot changes.
  React.useEffect(() => {
    setState((prev) => ({
      ...prev,
      has_lamp: showLamp ? prev.has_lamp : false,
    }))
  }, [showLamp])

  // Apply hidden facility resets.
  React.useEffect(() => {
    setState((prev) => ({
      ...prev,
      chairs_count: hidden.has("chairs") ? 5 : prev.chairs_count,
      burn_barrels_count: hidden.has("burn_barrels") ? 0 : prev.burn_barrels_count,
      has_tent: hidden.has("tent") ? false : prev.has_tent,
      has_prayer_table: hidden.has("prayer_table") ? false : prev.has_prayer_table,
      has_lamp: hidden.has("lamp") ? false : prev.has_lamp,
    }))
  }, [hidden])

  // Apply lot-size facility rules (hide = force reset); also enforce min/max on counters.
  React.useEffect(() => {
    if (!currentSizeRule) return
    setState((prev) => {
      const next = { ...prev }
      // counters
      next.chairs_count = Math.max(currentSizeRule.chairs_min, Math.min(currentSizeRule.chairs_max, next.chairs_count))
      next.burn_barrels_count = Math.max(
        currentSizeRule.burn_barrels_min,
        Math.min(currentSizeRule.burn_barrels_max, next.burn_barrels_count),
      )
      if (!currentSizeRule.tent_allowed) next.has_tent = false
      if (!currentSizeRule.prayer_table_allowed) next.has_prayer_table = false
      if (!currentSizeRule.lamp_allowed) next.has_lamp = false
      return next
    })
  }, [currentSizeRule])

  function stepClass(n: number) {
    if (n < state.step) return "done"
    if (n === state.step) return "active"
    return "idle"
  }

  function canNext(): boolean {
    if (state.step === 1) {
      return !!state.location_id && !!state.grave_type
    }
    if (state.step === 2) {
      return !!state.zone_id && !!state.booking_date && !!state.time_slot_id && !!state.lot_id
    }
    if (state.step === 3) return true
    return false
  }

  function nextStep() {
    setState((prev) => {
      if (prev.step >= 4) return prev
      return { ...prev, step: (prev.step + 1) as BookingState["step"] }
    })
  }

  function prevStep() {
    setState((prev) => {
      if (prev.step <= 1) return prev
      return { ...prev, step: (prev.step - 1) as BookingState["step"] }
    })
  }

  function selectLocation(id: number) {
    setState((prev) => ({
      ...prev,
      location_id: id,
      zone_id: null,
      lot_id: null,
      booking_date: null,
      time_slot_id: null,
    }))
    setHiddenFacilities([])
    setHiddenFacilityReasons([])
  }

  function selectDate(ymd: string) {
    setState((prev) => ({
      ...prev,
      booking_date: ymd,
      lot_id: null,
    }))
  }

  function selectSlot(id: number) {
    setState((prev) => ({
      ...prev,
      time_slot_id: id,
      lot_id: null,
    }))
  }

  function calPrev() {
    setState((prev) => {
      const d = new Date(prev.cal_year, prev.cal_month - 1, 1)
      return { ...prev, cal_year: d.getFullYear(), cal_month: d.getMonth() }
    })
  }

  function calNext() {
    setState((prev) => {
      const d = new Date(prev.cal_year, prev.cal_month + 1, 1)
      return { ...prev, cal_year: d.getFullYear(), cal_month: d.getMonth() }
    })
  }

  function renderCalendar() {
    const yr = state.cal_year
    const mo = state.cal_month
    const firstDay = new Date(yr, mo, 1).getDay()
    const lead = (firstDay + 6) % 7
    const total = new Date(yr, mo + 1, 0).getDate()

    const minDate = ymdToDate(minDateYmd) ?? jakartaTodayStart()
    const maxDate = ymdToDate(maxDateYmd) ?? jakartaTodayStart()
    const blanks = Array.from({ length: lead }, (_, i) => (
      <div key={`b-${i}`} />
    ))

    const days = Array.from({ length: total }, (_, i) => {
      const d = i + 1
      const dt = new Date(yr, mo, d)
      dt.setHours(0, 0, 0, 0)
      const ymd = ymdFromDate(dt)
      const disabled = dt < minDate || dt > maxDate
      const selected = state.booking_date === ymd
      const cls = ["cal-day", disabled ? "disabled" : "", selected ? "selected" : ""]
        .filter(Boolean)
        .join(" ")
      return (
        <div
          key={ymd}
          className={cls}
          onClick={disabled ? undefined : () => selectDate(ymd)}
          role={disabled ? undefined : "button"}
          tabIndex={disabled ? -1 : 0}
        >
          {d}
        </div>
      )
    })

    return (
      <div className="cal-wrap">
        <div className="cal-header">
          <button className="cal-nav" type="button" onClick={calPrev} aria-label="Sebelumnya">
            ‹
          </button>
          <span className="cal-title">
            {MONTHS[mo]} {yr}
          </span>
          <button className="cal-nav" type="button" onClick={calNext} aria-label="Berikutnya">
            ›
          </button>
        </div>
        <div className="cal-days-header">
          {["Sen", "Sel", "Rab", "Kam", "Jum", "Sab", "Min"].map((d) => (
            <span key={d}>{d}</span>
          ))}
        </div>
        <div className="cal-grid">
          {blanks}
          {days}
        </div>
        <div className="cal-info">
          <span>Minimal pemesanan H+2 dan maksimal adalah H+100 dari hari ini</span>
        </div>
      </div>
    )
  }

  function submitBooking() {
    if (
      !state.location_id ||
      !state.zone_id ||
      !state.lot_id ||
      !state.booking_date ||
      !state.time_slot_id
    ) {
      return
    }

    const payload = {
      activity_type: state.activity_type,
      location_id: String(state.location_id),
      grave_type: state.grave_type,
      zone_id: String(state.zone_id),
      visit_date: state.booking_date,
      time_slot_id: String(state.time_slot_id),
      lot_id: String(state.lot_id),
      chairs_count: state.chairs_count,
      burn_barrels_count: state.burn_barrels_count,
      has_tent: state.has_tent,
      has_prayer_table: state.has_prayer_table,
      has_lamp: state.has_lamp,
      customer_name: state.name,
      customer_email: state.email,
      customer_phone: state.phone,
      additional_note: state.additional_note,
      ethics_confirmed: true,
    }

    postForm.transform(() => payload)
    postForm.post("/booking", {
      preserveScroll: true,
      onError: () => setConfirmationOpen(false),
    })
  }

  return (
    <>
      <Head title="Booking" />
      <div className="booking-form">
        <div className="wrap">
          <div className="stepper">
            {STEPS.map((label, idx) => {
              const n = idx + 1
              const cls = stepClass(n)
              return (
                <React.Fragment key={label}>
                  <div className="step-item">
                    <div className={`step-circle ${cls}`}>{n < state.step ? "✓" : n}</div>
                    <div className={`step-label ${cls}`}>{label}</div>
                  </div>
                  {n < STEPS.length ? (
                    <div className={`step-line ${n < state.step ? "done" : "idle"}`} />
                  ) : null}
                </React.Fragment>
              )
            })}
          </div>

          <div className="card">
            <div className="card-header">
              {state.step === 1 ? (
                <>
                  <h2>Pilih Lokasi</h2>
                  <p>Pilih area pemakaman yang ingin dikunjungi</p>
                </>
              ) : null}
              {state.step === 2 ? (
                <>
                  <h2>Tanggal, Jam, Zona &amp; Lot</h2>
                  <p>Tentukan tanggal, jam, zona, dan lot yang tersedia</p>
                  <div className="hint-box" style={{ marginTop: 10 }}>
                    <p style={{ color: "var(--ink)" }}>Pastikan zona dan lot di isi dengan benar.</p>
                  </div>
                </>
              ) : null}
              {state.step === 3 ? (
                <>
                  <h2>Fasilitas</h2>
                  <p>Pilih fasilitas yang Anda butuhkan</p>
                </>
              ) : null}
              {state.step === 4 ? (
                <>
                  <h2>Data Diri</h2>
                  <p>Isi informasi kontak untuk konfirmasi booking</p>
                </>
              ) : null}
            </div>
            <div className="divider" />

            <div className="card-body">
              {errors.availability ? (
                <div className="hint-box" style={{ marginBottom: 12 }}>
                  <p>{errors.availability}</p>
                </div>
              ) : null}

              {state.step === 1 ? (
                <div className="space">
                  {page.props.booking_notice ? (
                    <section
                      className="rounded-lg border-2 border-[#c9a84c]/50 bg-[#c9a84c]/10 p-3"
                      aria-label="Informasi penting"
                    >
                      <h3 className="text-sm font-semibold text-[#1a2744]">
                        {page.props.booking_notice.title}
                      </h3>
                      <p className="mt-1 whitespace-pre-line text-sm leading-5 text-[#3d4864]">
                        {page.props.booking_notice.body}
                      </p>
                      {page.props.booking_notice.image_url ? (
                        <img
                          src={page.props.booking_notice.image_url}
                          alt={page.props.booking_notice.title}
                          className="mt-3 max-h-72 w-full rounded-md border bg-white object-contain"
                        />
                      ) : null}
                      {page.props.booking_notice.download_url ? (
                        <a
                          href={page.props.booking_notice.download_url}
                          download
                          className="mt-3 inline-flex min-h-10 items-center gap-2 rounded-md bg-[#06038D] px-4 py-2 text-sm font-semibold text-white hover:bg-[#050276]"
                        >
                          <IconDownload size={18} aria-hidden="true" />
                          Unduh Foto
                        </a>
                      ) : null}
                    </section>
                  ) : null}

                  <div>
                    <label className="field-label">Jenis kegiatan</label>
                    <div className="sel-wrap">
                      <select
                        className="inp"
                        value={state.activity_type}
                        onChange={(e) => {
                          const next = e.target.value
                          setState((p) => ({
                            ...p,
                            activity_type: next,
                            ...(next !== "ziarah"
                              ? { grave_type: "makam", zone_id: null, lot_id: null }
                              : {}),
                          }))
                        }}
                      >
                        {ACTIVITY_OPTIONS.map((o) => (
                          <option key={o.value} value={o.value}>
                            {o.label}
                          </option>
                        ))}
                      </select>
                    </div>
                  </div>

                  <div>
                    <label className="field-label">Lokasi pemakaman</label>
                    <div className="loc-grid">
                      {locations.map((l) => {
                        const selected = state.location_id === l.id
                        return (
                          <button
                            key={l.id}
                            type="button"
                            className={`loc-btn ${selected ? "sel" : ""}`}
                            onClick={() => selectLocation(l.id)}
                          >
                            <div className={`loc-icon ${selected ? "sel" : ""}`}>
                              <IconMapPin size={20} />
                            </div>
                            <span className="loc-name">{l.name}</span>
                          </button>
                        )
                      })}
                    </div>
                    {errors.location_id ? (
                      <p style={{ marginTop: 6, fontSize: 12, color: "var(--color-text-danger)" }}>
                        {errors.location_id}
                      </p>
                    ) : null}
                  </div>

                  <div>
                    <label className="field-label">Jenis makam</label>
                    <div className="sel-wrap">
                      <select
                        className="inp"
                        value={state.grave_type}
                        onChange={(e) =>
                          setState((p) => ({ ...p, grave_type: e.target.value }))
                        }
                      >
                        {(state.activity_type !== "ziarah"
                          ? GRAVE_OPTIONS.filter((o) => o.value === "makam")
                          : GRAVE_OPTIONS
                        ).map((o) => (
                          <option key={o.value} value={o.value}>
                            {o.label}
                          </option>
                        ))}
                      </select>
                    </div>
                  </div>
                </div>
              ) : null}

              {state.step === 2 ? (
                <div className="space">
                  <div>
                    <label className="field-label">Tanggal kunjungan</label>
                    {renderCalendar()}
                    {errors.visit_date ? (
                      <p style={{ marginTop: 6, fontSize: 12, color: "var(--color-text-danger)" }}>
                        {errors.visit_date}
                      </p>
                    ) : null}
                  </div>

                  <div>
                    <label className="field-label">Jam kunjungan</label>
                    <div className="ts-grid">
                      {timeSlots.map((ts) => {
                        const selected = state.time_slot_id === ts.id
                        return (
                          <button
                            key={ts.id}
                            type="button"
                            className={`ts-btn ${selected ? "sel" : ""}`}
                            onClick={() => selectSlot(ts.id)}
                            title={`${ts.start_time} - ${ts.end_time}`}
                          >
                            {ts.start_time}
                          </button>
                        )
                      })}
                    </div>
                    {errors.time_slot_id ? (
                      <p style={{ marginTop: 6, fontSize: 12, color: "var(--color-text-danger)" }}>
                        {errors.time_slot_id}
                      </p>
                    ) : null}
                  </div>

                  <div>
                    <div className="row-between">
                      <label className="field-label" style={{ margin: 0 }}>
                        Zona
                      </label>
                      <span className="lot-badge">{filteredZones.length} zona</span>
                    </div>
                    <div className="search-row">
                      <div className="search-input" style={{ flex: 1 }}>
                        <IconSearch size={14} color="var(--muted)" />
                        <input
                          value={state.zone_search}
                          onChange={(e) =>
                            setState((p) => ({ ...p, zone_search: e.target.value }))
                          }
                          placeholder="Cari zona..."
                        />
                      </div>
                    </div>
                    <div className="scroll-box" style={{ marginTop: 8 }}>
                      {filteredZones.length === 0 ? (
                        <p style={{ fontSize: 13, color: "var(--hint)" }}>Tidak ada zona.</p>
                      ) : (
                        <div className="chip-grid" style={{ gridTemplateColumns: "repeat(2, 1fr)" }}>
                          {filteredZones.map((z) => {
                            const selected = state.zone_id === z.id
                            return (
                              <button
                                key={z.id}
                                type="button"
                                className={`chip-btn ${selected ? "sel" : ""}`}
                                onClick={() =>
                                  setState((p) => ({ ...p, zone_id: z.id, lot_id: null }))
                                }
                              >
                                {z.name}
                              </button>
                            )
                          })}
                        </div>
                      )}
                    </div>
                    {errors.zone_id ? (
                      <p style={{ marginTop: 6, fontSize: 12, color: "var(--color-text-danger)" }}>
                        {errors.zone_id}
                      </p>
                    ) : null}
                  </div>

                  <div>
                    <div className="row-between">
                      <label className="field-label" style={{ margin: 0 }}>
                        Nomor lot
                        {state.zone_id ? ` - ${zones.find((z) => z.id === state.zone_id)?.name ?? ""}` : ""}
                      </label>
                      <span className="lot-badge">{filteredLots.length} tersedia</span>
                    </div>

                    {!state.zone_id || !state.booking_date || !state.time_slot_id ? (
                      <div className="hint-box" style={{ marginTop: 8 }}>
                        <p>Pilih tanggal, jam, dan zona terlebih dahulu.</p>
                      </div>
                    ) : (
                      <>
                        <div className="search-row" style={{ marginTop: 8 }}>
                          <div className="search-input" style={{ flex: 1 }}>
                            <IconSearch size={14} color="var(--muted)" />
                            <input
                              value={state.lot_search}
                              onChange={(e) =>
                                setState((p) => ({ ...p, lot_search: e.target.value }))
                              }
                              placeholder="Cari nomor lot..."
                            />
                          </div>
                        </div>
                        <div className="scroll-box" style={{ marginTop: 8, maxHeight: 260 }}>
                          {filteredLots.length === 0 ? (
                            <p style={{ fontSize: 13, color: "var(--hint)" }}>Tidak ditemukan.</p>
                          ) : (
                            <div className="chip-grid">
                              {filteredLots.map((l) => {
                                const selected = state.lot_id === l.id
                                return (
                                  <button
                                    key={l.id}
                                    type="button"
                                    className={`chip-btn ${selected ? "sel" : ""}`}
                                    onClick={() => setState((p) => ({ ...p, lot_id: l.id }))}
                                    title={`Ukuran: ${l.size}`}
                                  >
                                    {l.lot_number}
                                  </button>
                                )
                              })}
                            </div>
                          )}
                        </div>
                      </>
                    )}
                    {errors.lot_id ? (
                      <p style={{ marginTop: 6, fontSize: 12, color: "var(--color-text-danger)" }}>
                        {errors.lot_id}
                      </p>
                    ) : null}
                  </div>
                </div>
              ) : null}

              {state.step === 3 ? (
                <div className="space">
                  {hiddenNotes.length > 0 ? (
                    <div className="hint-box">
                      <p>{hiddenNotes.join(" ")}</p>
                    </div>
                  ) : null}
                  <div>
                    <label className="field-label">Jumlah item</label>
                    <div className="counter-list">
                      {!hidden.has("chairs") ? (
                        <div className="counter-row">
                          <div>
                            <div className="counter-label">Kursi</div>
                            <div className="counter-hint">
                              Std {currentSizeRule?.chairs_min ?? 5} - Maks {currentSizeRule?.chairs_max ?? 10}
                            </div>
                          </div>
                          <div className="counter-ctrl">
                            <button
                              className="counter-btn"
                              type="button"
                              disabled={state.chairs_count <= (currentSizeRule?.chairs_min ?? 5)}
                              onClick={() =>
                                setState((p) => ({
                                  ...p,
                                  chairs_count: Math.max(currentSizeRule?.chairs_min ?? 5, p.chairs_count - 1),
                                }))
                              }
                            >
                              −
                            </button>
                            <div className="counter-val">{state.chairs_count}</div>
                            <button
                              className="counter-btn"
                              type="button"
                              disabled={state.chairs_count >= (currentSizeRule?.chairs_max ?? 10)}
                              onClick={() =>
                                setState((p) => ({
                                  ...p,
                                  chairs_count: Math.min(currentSizeRule?.chairs_max ?? 10, p.chairs_count + 1),
                                }))
                              }
                            >
                              +
                            </button>
                          </div>
                        </div>
                      ) : null}

                      {!hidden.has("burn_barrels") ? (
                        <div className="counter-row">
                          <div>
                            <div className="counter-label">Tong Bakar</div>
                            <div className="counter-hint">
                              Std {currentSizeRule?.burn_barrels_min ?? 0} - Maks {currentSizeRule?.burn_barrels_max ?? 2}
                            </div>
                          </div>
                          <div className="counter-ctrl">
                            <button
                              className="counter-btn"
                              type="button"
                              disabled={state.burn_barrels_count <= (currentSizeRule?.burn_barrels_min ?? 0)}
                              onClick={() =>
                                setState((p) => ({
                                  ...p,
                                  burn_barrels_count: Math.max(
                                    currentSizeRule?.burn_barrels_min ?? 0,
                                    p.burn_barrels_count - 1,
                                  ),
                                }))
                              }
                            >
                              −
                            </button>
                            <div className="counter-val">{state.burn_barrels_count}</div>
                            <button
                              className="counter-btn"
                              type="button"
                              disabled={state.burn_barrels_count >= (currentSizeRule?.burn_barrels_max ?? 2)}
                              onClick={() =>
                                setState((p) => ({
                                  ...p,
                                  burn_barrels_count: Math.min(
                                    currentSizeRule?.burn_barrels_max ?? 2,
                                    p.burn_barrels_count + 1,
                                  ),
                                }))
                              }
                            >
                              +
                            </button>
                          </div>
                        </div>
                      ) : null}
                    </div>
                  </div>

                  <div>
                    <label className="field-label">Perlengkapan tambahan</label>
                    <div className="toggle-list">
                      {!hidden.has("tent") && (currentSizeRule?.tent_allowed ?? true) ? (
                        <div
                          className="toggle-row"
                          role="button"
                          tabIndex={0}
                          onClick={() =>
                            setState((p) => ({ ...p, has_tent: !p.has_tent }))
                          }
                        >
                          <span className="toggle-label">Tenda</span>
                          <div className={`toggle-switch ${state.has_tent ? "on" : ""}`}>
                            <div className={`toggle-knob ${state.has_tent ? "on" : ""}`} />
                          </div>
                        </div>
                      ) : null}

                      {!hidden.has("prayer_table") && (currentSizeRule?.prayer_table_allowed ?? true) ? (
                        <div
                          className="toggle-row"
                          role="button"
                          tabIndex={0}
                          onClick={() =>
                            setState((p) => ({
                              ...p,
                              has_prayer_table: !p.has_prayer_table,
                            }))
                          }
                        >
                          <span className="toggle-label">Meja Sembahyang</span>
                          <div
                            className={`toggle-switch ${state.has_prayer_table ? "on" : ""}`}
                          >
                            <div
                              className={`toggle-knob ${state.has_prayer_table ? "on" : ""}`}
                            />
                          </div>
                        </div>
                      ) : null}

                      {showLamp ? (
                        !hidden.has("lamp") && (currentSizeRule?.lamp_allowed ?? true) ? (
                          <div
                            className="toggle-row"
                            role="button"
                            tabIndex={0}
                            onClick={() =>
                              setState((p) => ({ ...p, has_lamp: !p.has_lamp }))
                            }
                          >
                            <span className="toggle-label">Lampu</span>
                            <div className={`toggle-switch ${state.has_lamp ? "on" : ""}`}>
                              <div className={`toggle-knob ${state.has_lamp ? "on" : ""}`} />
                            </div>
                          </div>
                        ) : null
                      ) : null}
                    </div>
                  </div>
                </div>
              ) : null}

              {state.step === 4 ? (
                <div className="space">
                  <div>
                    <label className="field-label">Nama lengkap</label>
                    <input
                      className="inp"
                      type="text"
                      placeholder="cth. Budi Santoso"
                      value={state.name}
                      onChange={(e) => setState((p) => ({ ...p, name: e.target.value }))}
                    />
                    {errors.customer_name ? (
                      <p style={{ marginTop: 6, fontSize: 12, color: "var(--color-text-danger)" }}>
                        {errors.customer_name}
                      </p>
                    ) : null}
                  </div>
                  <div>
                    <label className="field-label">Alamat email</label>
                    <input
                      className="inp"
                      type="email"
                      placeholder="cth. budi@email.com"
                      value={state.email}
                      onChange={(e) => setState((p) => ({ ...p, email: e.target.value }))}
                    />
                    {errors.customer_email ? (
                      <p style={{ marginTop: 6, fontSize: 12, color: "var(--color-text-danger)" }}>
                        {errors.customer_email}
                      </p>
                    ) : null}
                  </div>
                  <div>
                    <label className="field-label">Nomor telepon</label>
                    <input
                      className="inp"
                      type="tel"
                      inputMode="numeric"
                      placeholder="cth. 081234567890 atau 6281234567890"
                      minLength={10}
                      maxLength={13}
                      pattern="(?:08|62)[0-9]{8,11}"
                      title="Gunakan 10 sampai 13 angka dan awali dengan 08 atau 62"
                      value={state.phone}
                      onChange={(e) => setState((p) => ({ ...p, phone: e.target.value.replace(/\D/g, "") }))}
                    />
                    <p style={{ marginTop: 6, fontSize: 12, color: "var(--muted)" }}>
                      Gunakan 10-13 angka, diawali 08 atau 62.
                    </p>
                    {errors.customer_phone ? (
                      <p style={{ marginTop: 6, fontSize: 12, color: "var(--color-text-danger)" }}>
                        {errors.customer_phone}
                      </p>
                    ) : null}
                  </div>

                  <div className="summary">
                    <div className="summary-head">
                      <p>
                        Ringkasan <span>Booking</span>
                      </p>
                    </div>
                    <div className="summary-row">
                      <span className="summary-key">Lokasi</span>
                      <span className="summary-val">
                        {locations.find((l) => l.id === state.location_id)?.name ?? "-"}
                      </span>
                    </div>
                    <div className="summary-row">
                      <span className="summary-key">Zona</span>
                      <span className="summary-val">
                        {zones.find((z) => z.id === state.zone_id)?.name ?? "-"}
                      </span>
                    </div>
                    <div className="summary-row">
                      <span className="summary-key">Lot</span>
                      <span className="summary-val">
                        {lots.find((l) => l.id === state.lot_id)?.lot_number ?? "-"}
                      </span>
                    </div>
                    <div className="summary-row">
                      <span className="summary-key">Tanggal</span>
                      <span className="summary-val">{state.booking_date ?? "-"}</span>
                    </div>
                    <div className="summary-row">
                      <span className="summary-key">Jam</span>
                      <span className="summary-val">
                        {selectedTimeSlot
                          ? `${selectedTimeSlot.start_time} - ${selectedTimeSlot.end_time}`
                          : "-"}
                      </span>
                    </div>
                    <div className="summary-row">
                      <span className="summary-key">Fasilitas</span>
                      <span className="summary-val">
                        Kursi {state.chairs_count} · Tong {state.burn_barrels_count} · Tenda{" "}
                        {state.has_tent ? "Ya" : "Tidak"} · Meja{" "}
                        {state.has_prayer_table ? "Ya" : "Tidak"} · Lampu{" "}
                        {state.has_lamp ? "Ya" : "Tidak"}
                      </span>
                    </div>
                  </div>

                  <div>
                    <label className="field-label">Catatan tambahan</label>
                    <textarea
                      className="inp"
                      rows={4}
                      placeholder="Tulis catatan tambahan bila perlu"
                      value={state.additional_note}
                      onChange={(e) => setState((p) => ({ ...p, additional_note: e.target.value }))}
                    />
                    {errors.additional_note ? (
                      <p style={{ marginTop: 6, fontSize: 12, color: "var(--color-text-danger)" }}>
                        {errors.additional_note}
                      </p>
                    ) : null}
                  </div>

                  <button
                    type="button"
                    className="btn-confirm"
                    onClick={() => setConfirmationOpen(true)}
                    disabled={postForm.processing}
                  >
                    {postForm.processing ? "Memproses…" : "Konfirmasi & Kirim Booking"}
                  </button>
                </div>
              ) : null}
            </div>
          </div>

          <div className="nav">
            <button
              type="button"
              className="btn-back"
              onClick={prevStep}
              disabled={state.step === 1 || postForm.processing}
            >
              ← Kembali
            </button>
            {state.step < 4 ? (
              <button
                type="button"
                className="btn-next"
                onClick={nextStep}
                disabled={!canNext()}
              >
                Lanjut →
              </button>
            ) : null}
          </div>
        </div>
      </div>
      <EthicsConfirmationDialog
        open={confirmationOpen}
        imageUrl={page.props.ethics_image_url}
        processing={postForm.processing}
        onConfirm={submitBooking}
      />
    </>
  )
}
