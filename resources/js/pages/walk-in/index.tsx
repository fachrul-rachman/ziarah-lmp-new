import { Head, useForm, usePage } from '@inertiajs/react';
import * as React from 'react';

import { EthicsConfirmationDialog } from '@/components/ethics-confirmation-dialog';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';

export default function WalkInIndex() {
    const page = usePage<{
        ethics_image_url?: string | null;
        ethics_pdf_url?: string | null;
        errors: Record<string, string>;
    }>();
    const [confirmationOpen, setConfirmationOpen] = React.useState(false);
    const form = useForm({
        customer_name: '',
        customer_phone: '',
        lot_number: '',
        booking_h2_reason: '',
        ethics_confirmed: false,
    });

    function openConfirmation(event: React.FormEvent) {
        event.preventDefault();

        if (!form.data.customer_name.trim() || !form.data.customer_phone.trim()) {
            return;
        }

        setConfirmationOpen(true);
    }

    function submitWalkIn() {
        form.transform((data) => ({ ...data, ethics_confirmed: true }));
        form.post('/walk-in', {
            preserveScroll: true,
            onError: () => setConfirmationOpen(false),
        });
    }

    const errors = page.props.errors ?? {};

    return (
        <>
            <Head title="Walk-in Ziarah" />
            <main className="min-h-screen bg-[#f7f8fa] px-4 py-8 sm:py-12">
                <div className="mx-auto max-w-xl">
                    <header className="mb-7 text-center">
                        <p className="text-sm font-semibold text-[#9b7927]">
                            Lestari Memorial Park
                        </p>
                        <h1 className="mt-2 text-3xl font-bold text-[#1a2744]">
                            Walk-in Ziarah
                        </h1>
                    </header>

                    <form
                        className="relative space-y-6 overflow-hidden rounded-lg border-2 border-[#1a2744]/25 bg-white p-5 pt-7 shadow-[0_1px_4px_rgba(26,39,68,0.06)] before:absolute before:inset-x-0 before:top-0 before:h-[3px] before:bg-[#c9a84c] sm:p-8 sm:pt-9"
                        onSubmit={openConfirmation}
                    >
                        <div className="space-y-2">
                            <label
                                htmlFor="customer_name"
                                className="block text-lg font-semibold text-gray-900"
                            >
                                Nama
                            </label>
                            <Input
                                id="customer_name"
                                className="h-12 border-2 border-[#1a2744]/15 bg-[#f7f8fa] text-base focus-visible:border-[#1a2744] focus-visible:ring-[#1a2744]/10"
                                autoComplete="name"
                                maxLength={255}
                                required
                                value={form.data.customer_name}
                                onChange={(event) =>
                                    form.setData(
                                        'customer_name',
                                        event.target.value,
                                    )
                                }
                            />
                            {errors.customer_name ? (
                                <p className="text-sm text-red-700">
                                    {errors.customer_name}
                                </p>
                            ) : null}
                        </div>

                        <div className="space-y-2">
                            <label
                                htmlFor="customer_phone"
                                className="block text-lg font-semibold text-gray-900"
                            >
                                Nomor telepon
                            </label>
                            <Input
                                id="customer_phone"
                                className="h-12 border-2 border-[#1a2744]/15 bg-[#f7f8fa] text-base focus-visible:border-[#1a2744] focus-visible:ring-[#1a2744]/10"
                                type="tel"
                                inputMode="tel"
                                autoComplete="tel"
                                placeholder="Contoh: 081234567890"
                                minLength={10}
                                maxLength={13}
                                pattern="(?:08|62)[0-9]{8,11}"
                                title="Gunakan 10 sampai 13 angka dan awali dengan 08 atau 62"
                                required
                                value={form.data.customer_phone}
                                onChange={(event) =>
                                    form.setData(
                                        'customer_phone',
                                        event.target.value.replace(/\D/g, ''),
                                    )
                                }
                            />
                            <p className="text-sm text-gray-600">
                                Gunakan 10-13 angka, diawali 08 atau 62.
                            </p>
                            {errors.customer_phone ? (
                                <p className="text-sm text-red-700">
                                    {errors.customer_phone}
                                </p>
                            ) : null}
                        </div>

                        <div className="space-y-2">
                            <label
                                htmlFor="lot_number"
                                className="block text-lg font-semibold text-gray-900"
                            >
                                Nomor lot{' '}
                                <span className="font-normal text-gray-500">
                                    (opsional)
                                </span>
                            </label>
                            <Input
                                id="lot_number"
                                className="h-12 border-2 border-[#1a2744]/15 bg-[#f7f8fa] text-base focus-visible:border-[#1a2744] focus-visible:ring-[#1a2744]/10"
                                maxLength={10}
                                placeholder="Ketik nomor lot"
                                value={form.data.lot_number}
                                onChange={(event) =>
                                    form.setData(
                                        'lot_number',
                                        event.target.value,
                                    )
                                }
                            />
                            <p className="text-sm text-gray-600">
                                Maksimal 10 karakter.
                            </p>
                            {errors.lot_number ? (
                                <p className="text-sm text-red-700">
                                    {errors.lot_number}
                                </p>
                            ) : null}
                        </div>

                        <div className="space-y-2">
                            <label
                                htmlFor="booking_h2_reason"
                                className="block text-lg font-semibold text-gray-900"
                            >
                                Alasan tidak mengisi Link Booking Ziarah H-2
                            </label>
                            <select
                                id="booking_h2_reason"
                                className="h-12 w-full rounded-md border-2 border-[#1a2744]/15 bg-[#f7f8fa] px-3 text-base focus:border-[#1a2744] focus:outline-none focus:ring-2 focus:ring-[#1a2744]/10"
                                required
                                value={form.data.booking_h2_reason}
                                onChange={(event) => form.setData('booking_h2_reason', event.target.value)}
                            >
                                <option value="">Pilih alasan</option>
                                <option value="Tidak tahu">Tidak tahu</option>
                                <option value="Tahu tapi lupa">Tahu tapi lupa</option>
                                <option value="Tidak bisa">Tidak bisa</option>
                                <option value="Keperluan mendadak">Keperluan mendadak</option>
                            </select>
                            {errors.booking_h2_reason ? (
                                <p className="text-sm text-red-700">{errors.booking_h2_reason}</p>
                            ) : null}
                            <p className="text-sm font-medium text-red-700">
                                Untuk kenyamanan Anda berziarah, mohon mengisi Link Ziarah 2 hari sebelum kedatangan
                            </p>
                        </div>

                        <Button
                            type="submit"
                            className="min-h-12 w-full bg-[#1a2744] text-base hover:bg-[#243359]"
                        >
                            Lanjut ke Konfirmasi
                        </Button>
                    </form>
                </div>
            </main>

            <EthicsConfirmationDialog
                open={confirmationOpen}
                imageUrl={page.props.ethics_image_url}
                pdfUrl={page.props.ethics_pdf_url}
                processing={form.processing}
                onConfirm={submitWalkIn}
            />
        </>
    );
}
