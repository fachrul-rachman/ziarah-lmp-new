import { Head, useForm, usePage } from '@inertiajs/react';
import * as React from 'react';

import { EthicsConfirmationDialog } from '@/components/ethics-confirmation-dialog';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';

export default function WalkInIndex() {
    const page = usePage<{
        ethics_image_url?: string | null;
        errors: Record<string, string>;
    }>();
    const [confirmationOpen, setConfirmationOpen] = React.useState(false);
    const form = useForm({
        customer_name: '',
        customer_phone: '',
        lot_number: '',
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
            <main className="min-h-screen bg-gray-100 px-4 py-8 sm:py-12">
                <div className="mx-auto max-w-xl">
                    <header className="mb-7 text-center">
                        <p className="text-sm font-semibold text-emerald-700">
                            Lestari Memorial Park
                        </p>
                        <h1 className="mt-2 text-3xl font-bold text-gray-950">
                            Walk-in Ziarah
                        </h1>
                    </header>

                    <form
                        className="space-y-6 rounded-lg border bg-white p-5 shadow-sm sm:p-8"
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
                                className="h-12 text-base"
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
                                className="h-12 text-base"
                                type="tel"
                                inputMode="tel"
                                autoComplete="tel"
                                placeholder="Contoh: 081234567890"
                                maxLength={32}
                                required
                                value={form.data.customer_phone}
                                onChange={(event) =>
                                    form.setData(
                                        'customer_phone',
                                        event.target.value,
                                    )
                                }
                            />
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
                                className="h-12 text-base"
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

                        <Button
                            type="submit"
                            className="min-h-12 w-full bg-emerald-700 text-base hover:bg-emerald-800"
                        >
                            Lanjut ke Konfirmasi
                        </Button>
                    </form>
                </div>
            </main>

            <EthicsConfirmationDialog
                open={confirmationOpen}
                imageUrl={page.props.ethics_image_url}
                processing={form.processing}
                onConfirm={submitWalkIn}
            />
        </>
    );
}
