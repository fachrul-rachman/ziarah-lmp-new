import { Head, useForm, usePage } from '@inertiajs/react';
import * as React from 'react';

import { Button } from './../../components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from './../../components/ui/card';
import { Input } from './../../components/ui/input';
import { AdminLayout } from './../../layouts/admin-layout';
import { prepareImageUpload } from './../../lib/prepare-image-upload';

export default function AdminSettings() {
    const page = usePage<{
        values: {
            discord_webhook_url: string;
            discord_notification_time: string;
            ethics_image_url?: string | null;
            ethics_pdf_url?: string | null;
            booking_notice_enabled: boolean;
            booking_notice_title: string;
            booking_notice_body: string;
            booking_notice_start_date: string;
            booking_notice_end_date: string;
            booking_notice_image_url?: string | null;
        };
        errors: Record<string, string>;
        csrf_token?: string;
        flash?: { success?: string | null };
    }>();

    const values = page.props.values;
    const errors = page.props.errors ?? {};
    const flash = (page.props as any).flash ?? {};

    const form = useForm<{
        discord_webhook_url: string;
        discord_notification_time: string;
        ethics_image: File | null;
        ethics_pdf: File | null;
        booking_notice_enabled: boolean;
        booking_notice_title: string;
        booking_notice_body: string;
        booking_notice_start_date: string;
        booking_notice_end_date: string;
        booking_notice_image: File | null;
    }>({
        discord_webhook_url: values.discord_webhook_url ?? '',
        discord_notification_time: values.discord_notification_time ?? '08:00',
        ethics_image: null,
        ethics_pdf: null,
        booking_notice_enabled: values.booking_notice_enabled ?? false,
        booking_notice_title: values.booking_notice_title ?? '',
        booking_notice_body: values.booking_notice_body ?? '',
        booking_notice_start_date: values.booking_notice_start_date ?? '',
        booking_notice_end_date: values.booking_notice_end_date ?? '',
        booking_notice_image: null,
    });

    const [imagePreparing, setImagePreparing] = React.useState(false);
    const [imageError, setImageError] = React.useState<string | null>(null);
    const [noticeImagePreparing, setNoticeImagePreparing] = React.useState(false);
    const [noticeImageError, setNoticeImageError] = React.useState<string | null>(null);

    async function handleEthicsImageChange(event: React.ChangeEvent<HTMLInputElement>) {
        const file = event.target.files?.[0] ?? null;
        setImageError(null);

        if (!file) {
            form.setData('ethics_image', null);

            return;
        }

        setImagePreparing(true);

        try {
            form.setData('ethics_image', await prepareImageUpload(file));
        } catch (error) {
            form.setData('ethics_image', null);
            event.target.value = '';
            setImageError(error instanceof Error ? error.message : 'Foto gagal disiapkan.');
        } finally {
            setImagePreparing(false);
        }
    }

    async function handleNoticeImageChange(event: React.ChangeEvent<HTMLInputElement>) {
        const file = event.target.files?.[0] ?? null;
        setNoticeImageError(null);

        if (!file) {
            form.setData('booking_notice_image', null);

            return;
        }

        setNoticeImagePreparing(true);

        try {
            form.setData('booking_notice_image', await prepareImageUpload(file, 3 * 1024 * 1024));
        } catch (error) {
            form.setData('booking_notice_image', null);
            event.target.value = '';
            setNoticeImageError(error instanceof Error ? error.message : 'Foto gagal disiapkan.');
        } finally {
            setNoticeImagePreparing(false);
        }
    }

    const ethicsImagePreview = React.useMemo(
        () => (form.data.ethics_image ? URL.createObjectURL(form.data.ethics_image) : values.ethics_image_url ?? null),
        [form.data.ethics_image, values.ethics_image_url],
    );
    const noticeImagePreview = React.useMemo(
        () =>
            form.data.booking_notice_image
                ? URL.createObjectURL(form.data.booking_notice_image)
                : values.booking_notice_image_url ?? null,
        [form.data.booking_notice_image, values.booking_notice_image_url],
    );

    React.useEffect(() => {
        return () => {
            if (ethicsImagePreview?.startsWith('blob:')) URL.revokeObjectURL(ethicsImagePreview);
        };
    }, [ethicsImagePreview]);

    React.useEffect(() => {
        return () => {
            if (noticeImagePreview?.startsWith('blob:')) URL.revokeObjectURL(noticeImagePreview);
        };
    }, [noticeImagePreview]);

    type Rule = {
        normalized_size: string;
        display_size: string;
        chairs_min: number;
        chairs_max: number;
        burn_barrels_min: number;
        burn_barrels_max: number;
        tent_allowed: boolean;
        prayer_table_allowed: boolean;
        lamp_allowed: boolean;
    };

    const [loadingRules, setLoadingRules] = React.useState(false);
    const [savingRules, setSavingRules] = React.useState(false);
    const [ruleError, setRuleError] = React.useState<string | null>(null);
    const [defaultRule, setDefaultRule] = React.useState<Rule | null>(null);
    const [ruleItems, setRuleItems] = React.useState<Rule[]>([]);

    React.useEffect(() => {
        let cancelled = false;
        async function load() {
            setLoadingRules(true);
            setRuleError(null);
            try {
                const res = await fetch('/admin/lot-size-rules', { headers: { Accept: 'application/json' } });
                if (!res.ok) throw new Error('Gagal memuat aturan ukuran.');
                const data = await res.json();
                if (cancelled) return;

                const def = (data.default_rule ?? null) as Rule | null;
                setDefaultRule(def);

                const sizes = (data.sizes ?? []) as { normalized_size: string; display_size: string }[];
                const rules = (data.rules ?? {}) as Record<string, Rule>;

                const items: Rule[] = sizes.map((s) => {
                    const key = (s.normalized_size ?? '').toLowerCase();
                    const existing = rules[key];
                    return (
                        existing ?? {
                            normalized_size: key,
                            display_size: s.display_size,
                            chairs_min: def?.chairs_min ?? 5,
                            chairs_max: def?.chairs_max ?? 10,
                            burn_barrels_min: def?.burn_barrels_min ?? 0,
                            burn_barrels_max: def?.burn_barrels_max ?? 2,
                            tent_allowed: def?.tent_allowed ?? true,
                            prayer_table_allowed: def?.prayer_table_allowed ?? true,
                            lamp_allowed: def?.lamp_allowed ?? true,
                        }
                    );
                });

                setRuleItems(items);
            } catch (e: any) {
                if (!cancelled) setRuleError(e?.message ?? 'Gagal memuat aturan ukuran.');
            } finally {
                if (!cancelled) setLoadingRules(false);
            }
        }
        void load();
        return () => {
            cancelled = true;
        };
    }, []);

    async function saveRules() {
        setSavingRules(true);
        setRuleError(null);
        try {
            const tokenFromProps = page.props.csrf_token ?? '';
            const tokenFromMeta = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';
            const token = tokenFromProps || tokenFromMeta;

            // Use multipart/form-data + _token to match Laravel's default CSRF verification behavior.
            // This avoids relying on XSRF-TOKEN cookie being readable (it may be encrypted).
            const body = new FormData();
            body.append('_token', token);
            body.append('rules', JSON.stringify(ruleItems));

            const res = await fetch('/admin/lot-size-rules', {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    Accept: 'application/json',
                },
                body,
            });
            const data = await res.json().catch(() => null);
            if (!res.ok) {
                throw new Error(data?.message ?? 'Gagal menyimpan aturan ukuran.');
            }
        } catch (e: any) {
            setRuleError(e?.message ?? 'Gagal menyimpan aturan ukuran.');
        } finally {
            setSavingRules(false);
        }
    }

    return (
        <>
            <Head title="Setting" />
            <AdminLayout title="Setting">
                <Card>
                    <CardHeader>
                        <CardTitle>Setting</CardTitle>
                    </CardHeader>
                    <CardContent>
                        {flash?.success ? (
                            <div className="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900">
                                {flash.success}
                            </div>
                        ) : null}

                        <form
                            className="space-y-5"
                            onSubmit={(e) => {
                                e.preventDefault();
                                form.post('/admin/settings', { preserveScroll: true, forceFormData: true });
                            }}
                        >
                            <div className="space-y-2">
                                <div className="text-sm font-semibold">Discord Webhook URL</div>
                                <Input
                                    value={form.data.discord_webhook_url}
                                    onChange={(e) => form.setData('discord_webhook_url', e.target.value)}
                                    placeholder="https://discord.com/api/webhooks/..."
                                />
                                <div className="text-xs text-gray-600">
                                    Boleh kosong untuk menonaktifkan notifikasi Discord.
                                </div>
                                {errors.discord_webhook_url ? (
                                    <div className="text-xs text-red-600">{errors.discord_webhook_url}</div>
                                ) : null}
                            </div>

                            <div className="space-y-2">
                                <div className="text-sm font-semibold">Jam Notifikasi Discord (HH:MM)</div>
                                <Input
                                    type="time"
                                    step={60}
                                    value={form.data.discord_notification_time}
                                    onChange={(e) => form.setData('discord_notification_time', e.target.value)}
                                />
                                <div className="text-xs text-gray-600">
                                    Timezone: Asia/Jakarta. Scheduler hanya cocokkan menit (HH:MM), tidak menghitung detik.
                                </div>
                                {errors.discord_notification_time ? (
                                    <div className="text-xs text-red-600">{errors.discord_notification_time}</div>
                                ) : null}
                            </div>

                            <div className="space-y-4 border-t pt-5">
                                <label className="flex items-center gap-3 text-sm font-semibold">
                                    <input
                                        type="checkbox"
                                        className="h-5 w-5 accent-[#06038D]"
                                        checked={form.data.booking_notice_enabled}
                                        onChange={(e) => form.setData('booking_notice_enabled', e.target.checked)}
                                    />
                                    Tampilkan Informasi di Awal Form Booking
                                </label>

                                <div className="space-y-2">
                                    <div className="text-sm font-semibold">Judul Informasi</div>
                                    <Input
                                        maxLength={255}
                                        required={form.data.booking_notice_enabled}
                                        value={form.data.booking_notice_title}
                                        onChange={(e) => form.setData('booking_notice_title', e.target.value)}
                                    />
                                    {errors.booking_notice_title ? (
                                        <div className="text-xs text-red-600">{errors.booking_notice_title}</div>
                                    ) : null}
                                </div>

                                <div className="space-y-2">
                                    <div className="text-sm font-semibold">Isi Informasi</div>
                                    <textarea
                                        className="min-h-28 w-full rounded-md border px-3 py-2 text-sm"
                                        maxLength={2000}
                                        required={form.data.booking_notice_enabled}
                                        value={form.data.booking_notice_body}
                                        onChange={(e) => form.setData('booking_notice_body', e.target.value)}
                                    />
                                    {errors.booking_notice_body ? (
                                        <div className="text-xs text-red-600">{errors.booking_notice_body}</div>
                                    ) : null}
                                </div>

                                <div className="grid gap-4 sm:grid-cols-2">
                                    <div className="space-y-2">
                                        <div className="text-sm font-semibold">Tanggal Mulai</div>
                                        <Input
                                            type="date"
                                            required={form.data.booking_notice_enabled}
                                            value={form.data.booking_notice_start_date}
                                            onChange={(e) => form.setData('booking_notice_start_date', e.target.value)}
                                        />
                                        {errors.booking_notice_start_date ? (
                                            <div className="text-xs text-red-600">{errors.booking_notice_start_date}</div>
                                        ) : null}
                                    </div>
                                    <div className="space-y-2">
                                        <div className="text-sm font-semibold">Tanggal Berakhir</div>
                                        <Input
                                            type="date"
                                            min={form.data.booking_notice_start_date || undefined}
                                            required={form.data.booking_notice_enabled}
                                            value={form.data.booking_notice_end_date}
                                            onChange={(e) => form.setData('booking_notice_end_date', e.target.value)}
                                        />
                                        {errors.booking_notice_end_date ? (
                                            <div className="text-xs text-red-600">{errors.booking_notice_end_date}</div>
                                        ) : null}
                                    </div>
                                </div>

                                <div className="space-y-3">
                                    <div className="text-sm font-semibold">Foto Informasi (Opsional)</div>
                                    {noticeImagePreview ? (
                                        <img
                                            src={noticeImagePreview}
                                            alt="Foto informasi booking saat ini"
                                            className="max-h-72 w-full rounded-md border bg-gray-50 object-contain"
                                        />
                                    ) : null}
                                    <Input
                                        type="file"
                                        accept="image/jpeg,image/png,image/webp"
                                        disabled={noticeImagePreparing}
                                        onChange={handleNoticeImageChange}
                                    />
                                    <div className="text-xs text-gray-600">
                                        PNG, JPG, atau WebP sampai 3 MB tetap memakai format asli. Foto yang lebih besar akan diperkecil otomatis.
                                    </div>
                                    {noticeImagePreparing ? (
                                        <div className="text-xs font-medium text-[#1a2744]">Menyiapkan foto...</div>
                                    ) : null}
                                    {noticeImageError ? (
                                        <div className="text-xs text-red-600">{noticeImageError}</div>
                                    ) : null}
                                    {errors.booking_notice_image ? (
                                        <div className="text-xs text-red-600">{errors.booking_notice_image}</div>
                                    ) : null}
                                </div>
                            </div>

                            <div className="space-y-3">
                                <div className="text-sm font-semibold">Foto Konfirmasi Etika Berziarah</div>
                                {ethicsImagePreview ? (
                                    <img
                                        src={ethicsImagePreview}
                                        alt="Foto konfirmasi etika berziarah saat ini"
                                        className="max-h-72 w-full rounded-md border bg-gray-50 object-contain"
                                    />
                                ) : (
                                    <div className="flex min-h-32 items-center justify-center rounded-md border bg-gray-50 p-4 text-sm text-gray-600">
                                        Belum ada foto yang dipasang.
                                    </div>
                                )}
                                <Input
                                    type="file"
                                    accept="image/jpeg,image/png,image/webp"
                                    disabled={imagePreparing}
                                    onChange={handleEthicsImageChange}
                                />
                                <div className="text-xs text-gray-600">
                                    Format JPG, PNG, atau WebP. Foto awal maksimal 12 MB dan akan diperkecil otomatis.
                                </div>
                                {imagePreparing ? (
                                    <div className="text-xs font-medium text-[#1a2744]">Menyiapkan foto...</div>
                                ) : null}
                                {imageError ? <div className="text-xs text-red-600">{imageError}</div> : null}
                                {errors.ethics_image ? (
                                    <div className="text-xs text-red-600">{errors.ethics_image}</div>
                                ) : null}
                            </div>

                            <div className="space-y-3">
                                <div className="text-sm font-semibold">PDF Etika Berziarah</div>
                                {values.ethics_pdf_url ? (
                                    <a
                                        href={values.ethics_pdf_url}
                                        target="_blank"
                                        rel="noreferrer"
                                        className="block text-sm font-medium text-blue-700 underline"
                                    >
                                        Lihat PDF saat ini
                                    </a>
                                ) : (
                                    <div className="text-sm text-gray-600">Belum ada PDF yang dipasang.</div>
                                )}
                                <Input
                                    type="file"
                                    accept="application/pdf"
                                    onChange={(event) => form.setData('ethics_pdf', event.target.files?.[0] ?? null)}
                                />
                                <div className="text-xs text-gray-600">Format PDF, maksimal 4 MB.</div>
                                {errors.ethics_pdf ? (
                                    <div className="text-xs text-red-600">{errors.ethics_pdf}</div>
                                ) : null}
                            </div>

                            <div className="flex items-center gap-3">
                                <Button
                                    type="submit"
                                    disabled={form.processing || imagePreparing || noticeImagePreparing}
                                >
                                    {form.processing ? 'Menyimpan…' : 'Simpan'}
                                </Button>
                            </div>
                        </form>

                        <div className="my-8 h-px bg-gray-200" />

                        <div className="space-y-2">
                            <div className="text-sm font-semibold">Aturan Fasilitas per Ukuran Lot</div>
                            <div className="text-xs text-gray-600">
                                Jika ukuran belum punya aturan, akan fallback ke rule global default.
                            </div>
                        </div>

                        {ruleError ? (
                            <div className="mt-3 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                                {ruleError}
                            </div>
                        ) : null}

                        {loadingRules ? (
                            <div className="mt-4 text-sm text-gray-600">Memuat aturan…</div>
                        ) : ruleItems.length === 0 ? (
                            <div className="mt-4 text-sm text-gray-600">
                                Belum ada ukuran lot terdeteksi. Tambahkan lot terlebih dahulu.
                            </div>
                        ) : (
                            <div className="mt-4 space-y-4">
                                <div className="overflow-auto rounded-lg border border-gray-200">
                                    <table className="min-w-[980px] w-full text-sm">
                                        <thead className="bg-gray-50">
                                            <tr className="text-left">
                                                <th className="px-3 py-2 font-semibold">Ukuran</th>
                                                <th className="px-3 py-2 font-semibold">Kursi Min</th>
                                                <th className="px-3 py-2 font-semibold">Kursi Max</th>
                                                <th className="px-3 py-2 font-semibold">Tong Min</th>
                                                <th className="px-3 py-2 font-semibold">Tong Max</th>
                                                <th className="px-3 py-2 font-semibold">Tenda</th>
                                                <th className="px-3 py-2 font-semibold">Meja</th>
                                                <th className="px-3 py-2 font-semibold">Lampu</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            {ruleItems.map((r, idx) => (
                                                <tr key={r.normalized_size} className="border-t">
                                                    <td className="px-3 py-2 font-semibold">{r.display_size}</td>
                                                    <td className="px-3 py-2">
                                                        <Input
                                                            type="number"
                                                            min={0}
                                                            max={200}
                                                            value={String(r.chairs_min)}
                                                            onChange={(e) => {
                                                                const v = Number(e.target.value || 0);
                                                                setRuleItems((prev) =>
                                                                    prev.map((x, i) =>
                                                                        i === idx ? { ...x, chairs_min: v } : x,
                                                                    ),
                                                                );
                                                            }}
                                                        />
                                                    </td>
                                                    <td className="px-3 py-2">
                                                        <Input
                                                            type="number"
                                                            min={0}
                                                            max={200}
                                                            value={String(r.chairs_max)}
                                                            onChange={(e) => {
                                                                const v = Number(e.target.value || 0);
                                                                setRuleItems((prev) =>
                                                                    prev.map((x, i) =>
                                                                        i === idx ? { ...x, chairs_max: v } : x,
                                                                    ),
                                                                );
                                                            }}
                                                        />
                                                    </td>
                                                    <td className="px-3 py-2">
                                                        <Input
                                                            type="number"
                                                            min={0}
                                                            max={50}
                                                            value={String(r.burn_barrels_min)}
                                                            onChange={(e) => {
                                                                const v = Number(e.target.value || 0);
                                                                setRuleItems((prev) =>
                                                                    prev.map((x, i) =>
                                                                        i === idx ? { ...x, burn_barrels_min: v } : x,
                                                                    ),
                                                                );
                                                            }}
                                                        />
                                                    </td>
                                                    <td className="px-3 py-2">
                                                        <Input
                                                            type="number"
                                                            min={0}
                                                            max={50}
                                                            value={String(r.burn_barrels_max)}
                                                            onChange={(e) => {
                                                                const v = Number(e.target.value || 0);
                                                                setRuleItems((prev) =>
                                                                    prev.map((x, i) =>
                                                                        i === idx ? { ...x, burn_barrels_max: v } : x,
                                                                    ),
                                                                );
                                                            }}
                                                        />
                                                    </td>
                                                    <td className="px-3 py-2">
                                                        <label className="inline-flex items-center gap-2">
                                                            <input
                                                                type="checkbox"
                                                                checked={r.tent_allowed}
                                                                onChange={(e) =>
                                                                    setRuleItems((prev) =>
                                                                        prev.map((x, i) =>
                                                                            i === idx
                                                                                ? { ...x, tent_allowed: e.target.checked }
                                                                                : x,
                                                                        ),
                                                                    )
                                                                }
                                                            />
                                                            <span>{r.tent_allowed ? 'Ya' : 'Tidak'}</span>
                                                        </label>
                                                    </td>
                                                    <td className="px-3 py-2">
                                                        <label className="inline-flex items-center gap-2">
                                                            <input
                                                                type="checkbox"
                                                                checked={r.prayer_table_allowed}
                                                                onChange={(e) =>
                                                                    setRuleItems((prev) =>
                                                                        prev.map((x, i) =>
                                                                            i === idx
                                                                                ? {
                                                                                      ...x,
                                                                                      prayer_table_allowed: e.target.checked,
                                                                                  }
                                                                                : x,
                                                                        ),
                                                                    )
                                                                }
                                                            />
                                                            <span>{r.prayer_table_allowed ? 'Ya' : 'Tidak'}</span>
                                                        </label>
                                                    </td>
                                                    <td className="px-3 py-2">
                                                        <label className="inline-flex items-center gap-2">
                                                            <input
                                                                type="checkbox"
                                                                checked={r.lamp_allowed}
                                                                onChange={(e) =>
                                                                    setRuleItems((prev) =>
                                                                        prev.map((x, i) =>
                                                                            i === idx
                                                                                ? { ...x, lamp_allowed: e.target.checked }
                                                                                : x,
                                                                        ),
                                                                    )
                                                                }
                                                            />
                                                            <span>{r.lamp_allowed ? 'Ya' : 'Tidak'}</span>
                                                        </label>
                                                    </td>
                                                </tr>
                                            ))}
                                        </tbody>
                                    </table>
                                </div>

                                <div className="flex items-center gap-3">
                                    <Button type="button" onClick={saveRules} disabled={savingRules}>
                                        {savingRules ? 'Menyimpan…' : 'Simpan Aturan Ukuran'}
                                    </Button>
                                </div>
                            </div>
                        )}
                    </CardContent>
                </Card>
            </AdminLayout>
        </>
    );
}
