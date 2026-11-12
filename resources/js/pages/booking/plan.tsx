import { Head, Link, useForm } from '@inertiajs/react';
import {
    IconArmchair,
    IconArrowRight,
    IconCalendar,
    IconCalendarCheck,
    IconCheck,
    IconChevronDown,
    IconChevronLeft,
    IconChevronRight,
    IconClock,
    IconFlame,
    IconInfoCircle,
    IconLamp,
    IconLeaf,
    IconMail,
    IconMinus,
    IconPlus,
    IconTable,
    IconTent,
    IconTrash,
    IconUser,
    IconBrandWhatsapp,
} from '@tabler/icons-react';
import * as React from 'react';

import { EthicsConfirmationDialog } from '@/components/ethics-confirmation-dialog';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';

import '../../../css/visitation-plan.css';

type Schedule = {
    key: string;
    moment: string;
    date: string;
    time_slot_id: number | '';
    chairs_count: number;
    burn_barrels_count: number;
    has_tent: boolean;
    has_prayer_table: boolean;
    has_lamp: boolean;
};
type Slot = { id: number; start_time: string; available: boolean };
type Options = { date: string; slots: Slot[]; hidden_facilities: string[] };
type SizeRule = {
    chairs_min: number;
    chairs_max: number;
    burn_barrels_min: number;
    burn_barrels_max: number;
    tent_allowed: boolean;
    prayer_table_allowed: boolean;
    lamp_allowed: boolean;
};
type Plan = {
    token: string;
    applicant_name: string;
    applicant_phone: string;
    customer_email: string;
    deceased_name: string;
    date_of_death: string;
    burial_at: string;
    location: string;
    zone: string;
    lot: string;
    moments: Record<string, string>;
    minimum_date: string;
    maximum_date: string;
    year_maximum_date: string;
    expires_at: string;
    expired: boolean;
};
type Props = {
    plan: Plan;
    bookings: Array<{
        token: string;
        code: string;
        moment: string;
        label: string;
        date: string;
        time: string;
        status: string;
    }>;
    size_rule: SizeRule;
    booking_rules: { message: string };
    ethics_image_url?: string | null;
    ethics_pdf_url?: string | null;
};

const momentLabels: Record<string, string> = {
    '7': 'Hari ke-7',
    '40': 'Hari ke-40',
    '100': 'Hari ke-100',
    year: '1 Tahun',
    custom: 'Tanggal Bebas',
};
const statusLabels: Record<string, string> = {
    confirmed: 'Dikonfirmasi',
    rescheduled: 'Dijadwal ulang',
    cancelled: 'Dibatalkan',
    completed: 'Selesai',
};

function dateLabel(
    date: string,
    options: Intl.DateTimeFormatOptions = {
        day: 'numeric',
        month: 'long',
        year: 'numeric',
    },
) {
    if (!date) {
        return 'Pilih tanggal';
    }

    return new Intl.DateTimeFormat('id-ID', {
        ...options,
        timeZone: 'Asia/Jakarta',
    }).format(new Date(`${date}T12:00:00+07:00`));
}

const today = new Intl.DateTimeFormat('en-CA', {
    timeZone: 'Asia/Jakarta',
}).format(new Date());

function Calendar({
    value,
    min,
    max,
    onChange,
}: {
    value: string;
    min: string;
    max: string;
    onChange: (date: string) => void;
}) {
    const initial = (value || min).split('-').map(Number);
    const [month, setMonth] = React.useState({
        year: initial[0],
        index: initial[1] - 1,
    });
    const first = new Date(month.year, month.index, 1);
    const last = new Date(month.year, month.index + 1, 0);
    const prefix = `${month.year}-${String(month.index + 1).padStart(2, '0')}`;
    const previous = new Date(month.year, month.index, 0);
    const next = new Date(month.year, month.index + 1, 1);
    function move(offset: number) {
        const date = new Date(month.year, month.index + offset, 1);
        setMonth({ year: date.getFullYear(), index: date.getMonth() });
    }

    return (
        <div
            className="plan-calendar"
            role="group"
            aria-label="Kalender kunjungan"
        >
            <div className="plan-calendar-nav">
                <Button
                    type="button"
                    variant="ghost"
                    size="icon"
                    aria-label="Bulan sebelumnya"
                    disabled={
                        `${previous.getFullYear()}-${String(previous.getMonth() + 1).padStart(2, '0')}-31` <
                        min
                    }
                    onClick={() => move(-1)}
                >
                    <IconChevronLeft aria-hidden="true" />
                </Button>
                <strong aria-live="polite">
                    {dateLabel(`${prefix}-01`, {
                        month: 'long',
                        year: 'numeric',
                    })}
                </strong>
                <Button
                    type="button"
                    variant="ghost"
                    size="icon"
                    aria-label="Bulan berikutnya"
                    disabled={
                        `${next.getFullYear()}-${String(next.getMonth() + 1).padStart(2, '0')}-01` >
                        max
                    }
                    onClick={() => move(1)}
                >
                    <IconChevronRight aria-hidden="true" />
                </Button>
            </div>
            <div className="plan-calendar-grid">
                {['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min'].map(
                    (day) => (
                        <span key={day} className="plan-weekday">
                            {day}
                        </span>
                    ),
                )}
                {Array.from(
                    { length: (first.getDay() + 6) % 7 },
                    (_, index) => (
                        <span key={`blank-${index}`} />
                    ),
                )}
                {Array.from({ length: last.getDate() }, (_, index) => {
                    const date = `${prefix}-${String(index + 1).padStart(2, '0')}`;

                    return (
                        <Button
                            key={date}
                            type="button"
                            variant="ghost"
                            className="plan-day"
                            aria-label={dateLabel(date)}
                            aria-pressed={value === date}
                            data-today={date === today}
                            disabled={date < min || date > max}
                            onClick={() => onChange(date)}
                        >
                            {index + 1}
                        </Button>
                    );
                })}
            </div>
            <p className="plan-calendar-note">
                Tanggal yang dicoret berada di luar batas pemesanan.
            </p>
        </div>
    );
}

function ScheduleEditor({
    schedule,
    plan,
    rule,
    onChange,
    onOptions,
    expanded,
    onExpand,
}: {
    schedule: Schedule;
    plan: Plan;
    rule: SizeRule;
    onChange: (values: Partial<Schedule>) => void;
    onOptions: (key: string, options: Options) => void;
    expanded: boolean;
    onExpand: () => void;
}) {
    const [options, setOptions] = React.useState<Options | null>(null);
    const [error, setError] = React.useState('');
    const [attempt, setAttempt] = React.useState(0);
    const [editingDate, setEditingDate] = React.useState(false);
    const reportOptions = React.useEffectEvent(onOptions);
    React.useEffect(() => {
        if (!schedule.date) {
            return;
        }

        const controller = new AbortController();
        fetch(
            `/plan/${plan.token}/options?date=${encodeURIComponent(schedule.date)}&moment=${encodeURIComponent(schedule.moment)}`,
            {
                signal: controller.signal,
                headers: { Accept: 'application/json' },
            },
        )
            .then(async (response) => {
                if (!response.ok) {
                    throw new Error(
                        'Pilihan jam belum dapat dimuat. Silakan coba lagi.',
                    );
                }

                return response.json();
            })
            .then((data: Options) => {
                const result = { ...data, date: schedule.date };
                setOptions(result);
                reportOptions(schedule.key, result);
                setError('');
            })
            .catch((failure: Error) => {
                if (failure.name !== 'AbortError') {
                    setError(failure.message);
                }
            });

        return () => controller.abort();
    }, [plan.token, schedule.date, schedule.key, schedule.moment, attempt]);
    const ready = options?.date === schedule.date && !error;
    const hidden = ready ? options.hidden_facilities : [];
    const selectedSlot = ready
        ? options.slots.find((slot) => slot.id === schedule.time_slot_id)
        : null;
    const complete = !!selectedSlot?.available;
    const selectedHour = selectedSlot
        ? Number(selectedSlot.start_time.slice(0, 2))
        : null;
    const showLamp =
        selectedHour !== null && (selectedHour >= 18 || selectedHour < 6);
    const prefix = `schedule-${schedule.key}`;
    const referenceDate = plan.moments[schedule.moment];
    const showCalendar = !schedule.date || editingDate;
    const dateChanged =
        referenceDate && schedule.date && referenceDate !== schedule.date;
    const counters = [
        {
            field: 'chairs_count',
            facility: 'chairs',
            label: 'Kursi',
            min: rule.chairs_min,
            max: rule.chairs_max,
            Icon: IconArmchair,
        },
        {
            field: 'burn_barrels_count',
            facility: 'burn_barrels',
            label: 'Tong bakar',
            min: rule.burn_barrels_min,
            max: rule.burn_barrels_max,
            Icon: IconFlame,
        },
    ] as const;

    function selectDate(date: string) {
        if (date !== schedule.date) {
            onChange({ date, time_slot_id: '', has_lamp: false });
        }

        setEditingDate(false);
    }

    return (
        <article className="plan-editor" data-expanded={expanded}>
            <div className="plan-editor-header">
                <h3>{momentLabels[schedule.moment]}</h3>
                <button
                    type="button"
                    className="plan-editor-toggle"
                    aria-label={`${expanded ? 'Tutup' : 'Buka'} Jadwal ${momentLabels[schedule.moment]}`}
                    aria-expanded={expanded}
                    aria-controls={`${prefix}-body`}
                    onClick={onExpand}
                >
                    <span className="plan-badge" data-complete={complete}>
                        {complete && <IconCheck aria-hidden="true" />}
                        {selectedSlot?.available
                            ? `Jam ${selectedSlot.start_time.replace(':', '.')}`
                            : 'Belum ada jam'}
                    </span>
                    <IconChevronDown
                        className="plan-disclosure"
                        aria-hidden="true"
                    />
                </button>
                <div className="plan-editor-date">
                    <IconCalendar aria-hidden="true" />
                    <span>
                        {schedule.date
                            ? dateLabel(schedule.date)
                            : 'Tanggal belum dipilih'}
                    </span>
                    {(schedule.date || !expanded) && (
                        <Button
                            type="button"
                            size="sm"
                            className="plan-date-edit"
                            aria-expanded={expanded && showCalendar}
                            aria-controls={`${prefix}-calendar`}
                            onClick={() => {
                                if (!expanded) {
                                    onExpand();
                                }

                                setEditingDate(!expanded || !showCalendar);
                            }}
                        >
                            {expanded && showCalendar
                                ? 'Tutup Kalender'
                                : schedule.date
                                  ? 'Ubah Tanggal'
                                  : 'Pilih Tanggal'}
                        </Button>
                    )}
                </div>
                {dateChanged && (
                    <div className="plan-editor-reference">
                        <span>Tanggal acuan {dateLabel(referenceDate)}</span>
                        {referenceDate >= plan.minimum_date && (
                            <Button
                                type="button"
                                variant="link"
                                className="plan-date-reset"
                                onClick={() => selectDate(referenceDate)}
                            >
                                Kembalikan
                            </Button>
                        )}
                    </div>
                )}
            </div>
            <div
                className="plan-editor-body"
                id={`${prefix}-body`}
                hidden={!expanded}
            >
                <div id={`${prefix}-calendar`} hidden={!showCalendar}>
                    {showCalendar && (
                        <div className="plan-calendar-section">
                            <Calendar
                                value={schedule.date}
                                min={plan.minimum_date}
                                max={
                                    schedule.moment === 'year'
                                        ? plan.year_maximum_date
                                        : plan.maximum_date
                                }
                                onChange={selectDate}
                            />
                        </div>
                    )}
                </div>
                <fieldset className="plan-time-section">
                    <legend className="plan-label">
                        <IconClock aria-hidden="true" /> Jam Kunjungan
                    </legend>
                    {!schedule.date && (
                        <p className="plan-help">
                            Pilih tanggal terlebih dahulu.
                        </p>
                    )}
                    {schedule.date && !ready && !error && (
                        <p role="status" className="plan-help">
                            Memuat jam kunjungan...
                        </p>
                    )}
                    {error && (
                        <div role="alert" className="plan-error">
                            {error}{' '}
                            <Button
                                type="button"
                                variant="link"
                                onClick={() => {
                                    setError('');
                                    setAttempt((value) => value + 1);
                                }}
                            >
                                Coba lagi
                            </Button>
                        </div>
                    )}
                    {ready && (
                        <div className="plan-time-grid">
                            {options.slots.map((slot) => (
                                <Button
                                    key={slot.id}
                                    type="button"
                                    variant="outline"
                                    className="plan-time"
                                    aria-pressed={
                                        schedule.time_slot_id === slot.id
                                    }
                                    disabled={!slot.available}
                                    title={
                                        slot.available
                                            ? `${slot.start_time} WIB`
                                            : 'Jam tidak tersedia'
                                    }
                                    onClick={() => {
                                        const hour = Number(
                                            slot.start_time.slice(0, 2),
                                        );
                                        onChange({
                                            time_slot_id: slot.id,
                                            has_lamp:
                                                (hour >= 18 || hour < 6) &&
                                                schedule.has_lamp,
                                        });
                                    }}
                                >
                                    {schedule.time_slot_id === slot.id && (
                                        <IconCheck aria-hidden="true" />
                                    )}
                                    {slot.start_time.replace(':', '.')}
                                </Button>
                            ))}
                        </div>
                    )}
                    {ready && !options.slots.some((slot) => slot.available) && (
                        <p role="status" className="plan-help">
                            Belum ada jam tersedia pada tanggal ini.
                        </p>
                    )}
                    <p className="plan-time-note">Waktu Indonesia Barat</p>
                </fieldset>
                <div
                    className="plan-facilities"
                    role="group"
                    aria-labelledby={`${prefix}-facilities`}
                    aria-describedby={
                        hidden.length > 0 ? `${prefix}-hint` : undefined
                    }
                >
                    <h3 className="plan-label" id={`${prefix}-facilities`}>
                        Fasilitas Kunjungan
                    </h3>
                    {hidden.length > 0 && (
                        <p className="plan-help" id={`${prefix}-hint`}>
                            Beberapa fasilitas tidak tersedia karena ada event
                            pada tanggal ini.
                        </p>
                    )}
                    <div className="plan-counters">
                        {counters.map(
                            ({ field, facility, label, min, max, Icon }) => {
                                const restricted = hidden.includes(facility);
                                const count = restricted ? 0 : schedule[field];

                                return (
                                    <div className="plan-counter" key={field}>
                                        <div className="plan-counter-label">
                                            <Icon aria-hidden="true" />
                                            <span>
                                                <strong>{label}</strong>
                                                <small>
                                                    {restricted
                                                        ? 'Tidak tersedia'
                                                        : `${min} sampai ${max}`}
                                                </small>
                                            </span>
                                        </div>
                                        <div className="plan-counter-control">
                                            <Button
                                                type="button"
                                                variant="ghost"
                                                size="icon"
                                                aria-label={`Kurangi ${label.toLowerCase()}`}
                                                disabled={
                                                    restricted || count <= min
                                                }
                                                onClick={() =>
                                                    onChange({
                                                        [field]: count - 1,
                                                    })
                                                }
                                            >
                                                <IconMinus aria-hidden="true" />
                                            </Button>
                                            <output
                                                aria-label={`Jumlah ${label.toLowerCase()}`}
                                                aria-live="polite"
                                            >
                                                {count}
                                            </output>
                                            <Button
                                                type="button"
                                                variant="ghost"
                                                size="icon"
                                                aria-label={`Tambah ${label.toLowerCase()}`}
                                                disabled={
                                                    restricted || count >= max
                                                }
                                                onClick={() =>
                                                    onChange({
                                                        [field]: count + 1,
                                                    })
                                                }
                                            >
                                                <IconPlus aria-hidden="true" />
                                            </Button>
                                        </div>
                                    </div>
                                );
                            },
                        )}
                    </div>
                    <div className="plan-facility-options">
                        {(
                            [
                                [
                                    'has_tent',
                                    'tent',
                                    'Tenda',
                                    rule.tent_allowed,
                                    IconTent,
                                ],
                                [
                                    'has_prayer_table',
                                    'prayer_table',
                                    'Meja sembahyang',
                                    rule.prayer_table_allowed,
                                    IconTable,
                                ],
                                [
                                    'has_lamp',
                                    'lamp',
                                    'Lampu',
                                    rule.lamp_allowed,
                                    IconLamp,
                                ],
                            ] as const
                        ).map(([field, facility, label, allowed, Icon]) => {
                            if (
                                field === 'has_lamp' &&
                                (!showLamp ||
                                    !allowed ||
                                    hidden.includes(facility))
                            ) {
                                return null;
                            }

                            const disabled =
                                !allowed || hidden.includes(facility);
                            const selected = !disabled && schedule[field];

                            return (
                                <Button
                                    key={field}
                                    type="button"
                                    variant="outline"
                                    className="plan-facility-choice"
                                    aria-pressed={selected}
                                    disabled={disabled}
                                    onClick={() =>
                                        onChange({ [field]: !schedule[field] })
                                    }
                                >
                                    <Icon aria-hidden="true" />
                                    {label}
                                    {selected && (
                                        <IconCheck
                                            className="plan-choice-check"
                                            aria-hidden="true"
                                        />
                                    )}
                                </Button>
                            );
                        })}
                    </div>
                </div>
            </div>
        </article>
    );
}

export default function VisitationPlanPage({
    plan,
    bookings,
    size_rule: rule,
    booking_rules,
    ethics_image_url,
    ethics_pdf_url,
}: Props) {
    const form = useForm({
        schedules: [] as Schedule[],
        customer_email: plan.customer_email ?? '',
        ethics_confirmed: false,
    });
    const [confirmationOpen, setConfirmationOpen] = React.useState(false);
    const [activeEditor, setActiveEditor] = React.useState<string | null>(null);
    const [optionsBySchedule, setOptionsBySchedule] = React.useState<
        Record<string, Options>
    >({});
    const errors = form.errors as Record<string, string>;
    const hasDates = plan.minimum_date <= plan.year_maximum_date;
    const canSubmit =
        form.data.schedules.length > 0 &&
        form.data.schedules.every((schedule) => {
            const options = optionsBySchedule[schedule.key];

            return (
                options?.date === schedule.date &&
                options.slots.some(
                    (slot) =>
                        slot.id === schedule.time_slot_id && slot.available,
                )
            );
        });
    const showBar = !plan.expired && hasDates && form.data.schedules.length > 0;
    function addSchedule(moment: string) {
        form.clearErrors();
        const key = crypto.randomUUID();
        const referenceDate = plan.moments[moment];
        setActiveEditor(key);
        form.setData('schedules', [
            ...form.data.schedules,
            {
                key,
                moment,
                date:
                    referenceDate && referenceDate >= plan.minimum_date
                        ? referenceDate
                        : '',
                time_slot_id: '',
                chairs_count: rule.chairs_min,
                burn_barrels_count: rule.burn_barrels_min,
                has_tent: false,
                has_prayer_table: false,
                has_lamp: false,
            },
        ]);
    }
    const incomplete = form.data.schedules.filter((schedule) => {
        const options = optionsBySchedule[schedule.key];

        return !(
            options?.date === schedule.date &&
            options.slots.some(
                (slot) => slot.id === schedule.time_slot_id && slot.available,
            )
        );
    });
    const submitReason =
        form.data.schedules.length === 0
            ? ''
            : incomplete.length > 0
              ? `Lengkapi tanggal dan jam untuk ${incomplete
                    .map((schedule) => momentLabels[schedule.moment])
                    .join(', ')}.`
              : '';
    function removeSchedule(key: string) {
        const schedule = form.data.schedules.find((item) => item.key === key);

        if (
            schedule &&
            (schedule.time_slot_id !== '' ||
                schedule.has_tent ||
                schedule.has_prayer_table ||
                schedule.has_lamp ||
                schedule.chairs_count !== rule.chairs_min ||
                schedule.burn_barrels_count !== rule.burn_barrels_min) &&
            !window.confirm(
                `Hapus jadwal ${momentLabels[schedule.moment]}? Jam dan fasilitas yang sudah dipilih akan hilang.`,
            )
        ) {
            return;
        }

        form.clearErrors();
        form.setData(
            'schedules',
            form.data.schedules.filter((schedule) => schedule.key !== key),
        );
    }
    function submit() {
        form.transform((data) => ({
            ...data,
            ethics_confirmed: true,
            whatsapp_consent: true,
        }));
        form.post(`/plan/${plan.token}`, {
            preserveScroll: true,
            onSuccess: () => {
                form.reset('schedules');
                setConfirmationOpen(false);
            },
            onError: () => setConfirmationOpen(false),
        });
    }

    return (
        <div
            className={
                showBar
                    ? 'booking-form planned-visit plan-has-bar'
                    : 'booking-form planned-visit'
            }
        >
            <Head title="Ziarah Terencana">
                <meta name="referrer" content="no-referrer" />
            </Head>
            <header className="plan-brand">
                <img
                    src="/LESTARI.png"
                    alt="Lestari Memorial Park"
                    width={132}
                    height={44}
                />
            </header>
            <main className="plan-container">
                <h1>Rencanakan Ziarah</h1>
                <section
                    className="plan-memorial"
                    aria-label="Informasi Pemakaman"
                >
                    <div className="plan-memorial-name">
                        <span className="plan-memorial-icon">
                            <IconLeaf aria-hidden="true" />
                        </span>
                        <div>
                            <span>Untuk Mengenang</span>
                            <h2>{plan.deceased_name}</h2>
                        </div>
                    </div>
                    <dl className="plan-memorial-details">
                        <div>
                            <dt>Tanggal meninggal</dt>
                            <dd>{dateLabel(plan.date_of_death)}</dd>
                        </div>
                        <div>
                            <dt>Pemakaman</dt>
                            <dd>{plan.burial_at}</dd>
                        </div>
                        <div>
                            <dt>Lokasi</dt>
                            <dd>{plan.location}</dd>
                        </div>
                        <div>
                            <dt>Zona dan nomor lot</dt>
                            <dd>
                                Zona {plan.zone}, lot {plan.lot}
                            </dd>
                        </div>
                    </dl>
                </section>
                {plan.expired && (
                    <div role="status" className="plan-notice">
                        <IconInfoCircle aria-hidden="true" />
                        <p>
                            Masa pemesanan sudah berakhir. Reservasi yang sudah
                            dibuat tetap dapat dikelola. Hubungi tim Lestari
                            Memorial Park untuk bantuan.
                        </p>
                    </div>
                )}
                {!hasDates && !plan.expired && (
                    <p role="status" className="plan-notice">
                        Tidak ada tanggal yang memenuhi batas pemesanan. Hubungi
                        tim Lestari Memorial Park.
                    </p>
                )}
                <form
                    onSubmit={(event) => {
                        event.preventDefault();

                        if (canSubmit) {
                            setConfirmationOpen(true);
                        }
                    }}
                >
                    <div
                        className="plan-layout"
                        data-read-only={plan.expired || !hasDates}
                    >
                        <fieldset
                            className="plan-main"
                            disabled={form.processing}
                        >
                            {!plan.expired && hasDates && (
                                <>
                                    <section className="plan-panel">
                                        <div className="plan-section-title">
                                            <span className="plan-step">1</span>
                                            <div>
                                                <h2>Pilih Momen Ziarah</h2>
                                                <p>
                                                    Anda dapat memilih lebih
                                                    dari satu momen.
                                                </p>
                                            </div>
                                        </div>
                                        <div className="plan-moments">
                                            {Object.entries(plan.moments).map(
                                                ([moment, date]) => {
                                                    const booked =
                                                        bookings.some(
                                                            (booking) =>
                                                                booking.moment ===
                                                                    moment &&
                                                                booking.status !==
                                                                    'cancelled',
                                                        );
                                                    const selected =
                                                        form.data.schedules.find(
                                                            (schedule) =>
                                                                schedule.moment ===
                                                                moment,
                                                        );
                                                    const unavailable =
                                                        plan.minimum_date >
                                                        (moment === 'year'
                                                            ? plan.year_maximum_date
                                                            : plan.maximum_date);

                                                    return (
                                                        <Button
                                                            key={moment}
                                                            type="button"
                                                            variant="outline"
                                                            className="plan-moment"
                                                            aria-pressed={
                                                                !!selected
                                                            }
                                                            data-state={
                                                                booked
                                                                    ? 'booked'
                                                                    : unavailable
                                                                      ? 'unavailable'
                                                                      : undefined
                                                            }
                                                            disabled={
                                                                booked ||
                                                                unavailable ||
                                                                form.processing ||
                                                                (!selected &&
                                                                    form.data
                                                                        .schedules
                                                                        .length >=
                                                                        20)
                                                            }
                                                            onClick={() =>
                                                                selected
                                                                    ? removeSchedule(
                                                                          selected.key,
                                                                      )
                                                                    : addSchedule(
                                                                          moment,
                                                                      )
                                                            }
                                                        >
                                                            {selected && (
                                                                <IconCheck
                                                                    className="plan-moment-check"
                                                                    aria-hidden="true"
                                                                />
                                                            )}
                                                            <strong>
                                                                {
                                                                    momentLabels[
                                                                        moment
                                                                    ]
                                                                }
                                                            </strong>
                                                            <small>
                                                                {dateLabel(
                                                                    date,
                                                                    {
                                                                        day: 'numeric',
                                                                        month: 'short',
                                                                        year: 'numeric',
                                                                    },
                                                                )}
                                                            </small>
                                                            <span className="plan-moment-state">
                                                                {booked && (
                                                                    <IconCheck aria-hidden="true" />
                                                                )}
                                                                {booked
                                                                    ? 'Sudah dipesan'
                                                                    : unavailable
                                                                      ? 'Tidak tersedia'
                                                                      : selected
                                                                        ? 'Dipilih'
                                                                        : 'Pilih momen'}
                                                            </span>
                                                        </Button>
                                                    );
                                                },
                                            )}
                                        </div>
                                        <Button
                                            type="button"
                                            variant="outline"
                                            className="plan-add-custom"
                                            disabled={
                                                form.processing ||
                                                plan.minimum_date >
                                                    plan.maximum_date ||
                                                form.data.schedules.length >= 20
                                            }
                                            onClick={() =>
                                                addSchedule('custom')
                                            }
                                        >
                                            <IconPlus aria-hidden="true" />
                                            Tambah tanggal bebas
                                        </Button>
                                        <p className="plan-help">
                                            {booking_rules.message} Tanggal
                                            bebas sampai{' '}
                                            {dateLabel(plan.maximum_date)}.
                                            {form.data.schedules.length >= 20 &&
                                                ' Batas 20 jadwal sudah tercapai.'}
                                        </p>
                                    </section>
                                    {form.data.schedules.length > 0 && (
                                        <section aria-label="Jadwal dan Fasilitas">
                                            <div className="plan-section-title plan-section-outside">
                                                <span className="plan-step">
                                                    2
                                                </span>
                                                <div>
                                                    <h2>Atur Kunjungan Anda</h2>
                                                    <p>
                                                        Tentukan jam dan
                                                        fasilitas setiap jadwal.
                                                    </p>
                                                </div>
                                            </div>
                                            <div className="plan-editors">
                                                {form.data.schedules.map(
                                                    (schedule, index) => (
                                                        <div key={schedule.key}>
                                                            <ScheduleEditor
                                                                schedule={
                                                                    schedule
                                                                }
                                                                plan={plan}
                                                                rule={rule}
                                                                expanded={
                                                                    activeEditor ===
                                                                    schedule.key
                                                                }
                                                                onExpand={() =>
                                                                    setActiveEditor(
                                                                        (
                                                                            current,
                                                                        ) =>
                                                                            current ===
                                                                            schedule.key
                                                                                ? null
                                                                                : schedule.key,
                                                                    )
                                                                }
                                                                onChange={(
                                                                    values,
                                                                ) =>
                                                                    form.setData(
                                                                        'schedules',
                                                                        form.data.schedules.map(
                                                                            (
                                                                                item,
                                                                            ) =>
                                                                                item.key ===
                                                                                schedule.key
                                                                                    ? {
                                                                                          ...item,
                                                                                          ...values,
                                                                                      }
                                                                                    : item,
                                                                        ),
                                                                    )
                                                                }
                                                                onOptions={(
                                                                    key,
                                                                    options,
                                                                ) =>
                                                                    setOptionsBySchedule(
                                                                        (
                                                                            current,
                                                                        ) => ({
                                                                            ...current,
                                                                            [key]: options,
                                                                        }),
                                                                    )
                                                                }
                                                            />
                                                            {Object.entries(
                                                                errors,
                                                            )
                                                                .filter(
                                                                    ([key]) =>
                                                                        key.startsWith(
                                                                            `schedules.${index}.`,
                                                                        ),
                                                                )
                                                                .map(
                                                                    ([
                                                                        key,
                                                                        error,
                                                                    ]) => (
                                                                        <p
                                                                            key={
                                                                                key
                                                                            }
                                                                            role="alert"
                                                                            className="plan-error"
                                                                        >
                                                                            {
                                                                                error
                                                                            }
                                                                        </p>
                                                                    ),
                                                                )}
                                                        </div>
                                                    ),
                                                )}
                                            </div>
                                        </section>
                                    )}
                                    <section className="plan-panel">
                                        <div className="plan-section-title">
                                            <span className="plan-step">3</span>
                                            <div>
                                                <h2>Data Pemesan</h2>
                                                <p>
                                                    Konfirmasi dan pengingat
                                                    dikirim melalui WhatsApp.
                                                </p>
                                            </div>
                                        </div>
                                        <div className="plan-contact">
                                            <div>
                                                <IconUser aria-hidden="true" />
                                                <span>
                                                    <small>Nama pemohon</small>
                                                    <strong>
                                                        {plan.applicant_name}
                                                    </strong>
                                                </span>
                                            </div>
                                            <div>
                                                <IconBrandWhatsapp aria-hidden="true" />
                                                <span>
                                                    <small>
                                                        Nomor WhatsApp
                                                    </small>
                                                    <strong>
                                                        +{plan.applicant_phone}
                                                    </strong>
                                                </span>
                                            </div>
                                        </div>
                                        <label
                                            htmlFor="plan-email"
                                            className="plan-label"
                                        >
                                            Email
                                        </label>
                                        <div className="plan-email-field">
                                            <IconMail aria-hidden="true" />
                                            <Input
                                                id="plan-email"
                                                type="email"
                                                autoComplete="email"
                                                placeholder="nama@email.com"
                                                required
                                                maxLength={255}
                                                value={form.data.customer_email}
                                                onChange={(event) =>
                                                    form.setData(
                                                        'customer_email',
                                                        event.target.value,
                                                    )
                                                }
                                                aria-invalid={
                                                    !!errors.customer_email
                                                }
                                                aria-describedby="plan-email-help"
                                            />
                                        </div>
                                        <p
                                            className="plan-help"
                                            id="plan-email-help"
                                        >
                                            Bukti reservasi dikirim ke email
                                            ini.
                                        </p>
                                    </section>
                                </>
                            )}
                        </fieldset>
                        <aside
                            className="plan-sidebar"
                            aria-label="Ringkasan Reservasi"
                        >
                            {!plan.expired && hasDates && (
                                <section
                                    className="plan-summary"
                                    id="plan-summary"
                                >
                                    <div className="plan-summary-heading">
                                        <IconCalendarCheck aria-hidden="true" />
                                        <div>
                                            <h2>Ringkasan Reservasi</h2>
                                            <p>
                                                Periksa jadwal sebelum
                                                konfirmasi.
                                            </p>
                                        </div>
                                    </div>
                                    {form.data.schedules.length === 0 ? (
                                        <div className="plan-summary-empty">
                                            <IconCalendar aria-hidden="true" />
                                            <p>Belum ada jadwal dipilih</p>
                                            <span>
                                                Pilih momen ziarah untuk
                                                memulai.
                                            </span>
                                        </div>
                                    ) : (
                                        <div className="plan-reservations">
                                            {form.data.schedules.map(
                                                (schedule) => {
                                                    const options =
                                                        optionsBySchedule[
                                                            schedule.key
                                                        ];
                                                    const time =
                                                        options?.date ===
                                                        schedule.date
                                                            ? options.slots.find(
                                                                  (slot) =>
                                                                      slot.id ===
                                                                      schedule.time_slot_id,
                                                              )?.start_time
                                                            : '';

                                                    return (
                                                        <article
                                                            className="plan-reservation"
                                                            key={schedule.key}
                                                        >
                                                            <div className="plan-reservation-details">
                                                                <strong className="plan-reservation-title">
                                                                    <IconCalendar aria-hidden="true" />
                                                                    {
                                                                        momentLabels[
                                                                            schedule
                                                                                .moment
                                                                        ]
                                                                    }
                                                                </strong>
                                                                <p>
                                                                    <IconCalendar aria-hidden="true" />
                                                                    <span>
                                                                        {dateLabel(
                                                                            schedule.date,
                                                                        )}
                                                                    </span>
                                                                </p>
                                                                <p
                                                                    data-missing={
                                                                        !time
                                                                    }
                                                                >
                                                                    <IconClock aria-hidden="true" />
                                                                    <span>
                                                                        {time
                                                                            ? `${time.replace(':', '.')} WIB`
                                                                            : 'Pilih jam kunjungan'}
                                                                    </span>
                                                                </p>
                                                            </div>
                                                            <Button
                                                                type="button"
                                                                variant="ghost"
                                                                size="icon"
                                                                className="plan-remove"
                                                                aria-label={`Hapus ${momentLabels[schedule.moment]}`}
                                                                disabled={
                                                                    form.processing
                                                                }
                                                                onClick={() =>
                                                                    removeSchedule(
                                                                        schedule.key,
                                                                    )
                                                                }
                                                            >
                                                                <IconTrash aria-hidden="true" />
                                                            </Button>
                                                        </article>
                                                    );
                                                },
                                            )}
                                        </div>
                                    )}
                                    <div className="plan-summary-footer">
                                        {submitReason && !form.processing && (
                                            <p
                                                role="status"
                                                className="plan-submit-reason"
                                            >
                                                {submitReason}
                                            </p>
                                        )}
                                        <Button
                                            type="submit"
                                            className="plan-submit"
                                            disabled={
                                                !canSubmit || form.processing
                                            }
                                        >
                                            {form.processing
                                                ? 'Menyimpan...'
                                                : 'Konfirmasi reservasi'}
                                            <IconArrowRight aria-hidden="true" />
                                        </Button>
                                        <p>
                                            Dengan konfirmasi, Anda setuju
                                            menerima notifikasi WhatsApp.
                                        </p>
                                    </div>
                                    {Object.entries(errors)
                                        .filter(
                                            ([key]) =>
                                                !key.startsWith('schedules.'),
                                        )
                                        .map(([key, error]) => (
                                            <p
                                                key={key}
                                                role="alert"
                                                className="plan-error"
                                            >
                                                {error}
                                            </p>
                                        ))}
                                </section>
                            )}
                            {bookings.length > 0 && (
                                <section className="plan-saved">
                                    <h2>
                                        Reservasi Tersimpan{' '}
                                        <span>{bookings.length}</span>
                                    </h2>
                                    <div className="plan-reservations">
                                        {bookings.map((booking) => (
                                            <article
                                                key={booking.token}
                                                className="plan-reservation plan-reservation-saved"
                                            >
                                                <div className="plan-reservation-details">
                                                    <strong className="plan-reservation-title">
                                                        <IconCalendarCheck aria-hidden="true" />
                                                        {
                                                            momentLabels[
                                                                booking.moment
                                                            ]
                                                        }
                                                    </strong>
                                                    <p>
                                                        <IconCalendar aria-hidden="true" />
                                                        <span>
                                                            {dateLabel(
                                                                booking.date,
                                                            )}
                                                        </span>
                                                    </p>
                                                    <p>
                                                        <IconClock aria-hidden="true" />
                                                        <span>
                                                            {booking.time.replace(
                                                                ':',
                                                                '.',
                                                            )}{' '}
                                                            WIB
                                                        </span>
                                                    </p>
                                                    <span
                                                        className={`plan-status plan-status-${booking.status}`}
                                                    >
                                                        {statusLabels[
                                                            booking.status
                                                        ] ?? booking.status}
                                                    </span>
                                                    <span className="plan-booking-code-label">
                                                        Kode booking
                                                    </span>
                                                    <strong className="plan-booking-code">
                                                        {booking.code}
                                                    </strong>
                                                    <Link
                                                        className="plan-manage"
                                                        aria-label={`Kelola booking ${booking.code}`}
                                                        href={`/booking/${booking.token}`}
                                                    >
                                                        Kelola booking
                                                        <IconArrowRight aria-hidden="true" />
                                                    </Link>
                                                </div>
                                            </article>
                                        ))}
                                    </div>
                                </section>
                            )}
                        </aside>
                    </div>
                </form>
                <footer className="plan-footer">
                    <IconClock aria-hidden="true" />
                    <span>
                        Pemesanan melalui tautan ini tersedia sampai{' '}
                        {plan.expires_at}.
                    </span>
                </footer>
            </main>
            {showBar && (
                <div className="plan-mobile-bar">
                    <span>{form.data.schedules.length} jadwal dipilih</span>
                    <a href="#plan-summary">
                        {canSubmit ? 'Lanjut konfirmasi' : 'Lihat ringkasan'}
                    </a>
                </div>
            )}
            <EthicsConfirmationDialog
                open={confirmationOpen}
                imageUrl={ethics_image_url}
                pdfUrl={ethics_pdf_url}
                processing={form.processing}
                onConfirm={submit}
            />
        </div>
    );
}
