import { Head, usePage } from '@inertiajs/react';
import { CheckCircle2 } from 'lucide-react';

export default function WalkInSuccess() {
    const page = usePage<{
        walkIn: {
            customer_name: string;
            lot_number?: string | null;
            visited_at?: string | null;
        };
    }>();
    const walkIn = page.props.walkIn;

    return (
        <>
            <Head title="Walk-in Berhasil" />
            <main className="flex min-h-screen items-center justify-center bg-gray-100 px-4 py-10">
                <section className="w-full max-w-lg rounded-lg border bg-white p-7 text-center shadow-sm sm:p-10">
                    <CheckCircle2
                        className="mx-auto h-20 w-20 text-emerald-600"
                        strokeWidth={2.5}
                        aria-hidden="true"
                    />
                    <h1 className="mt-5 text-3xl font-bold text-gray-950">
                        Data Berhasil Dikirim
                    </h1>
                    <p className="mt-3 text-lg text-gray-700">
                        Terima kasih, {walkIn.customer_name}.
                    </p>
                    {walkIn.lot_number ? (
                        <p className="mt-2 text-base text-gray-600">
                            Nomor lot: {walkIn.lot_number}
                        </p>
                    ) : null}
                    <p className="mt-6 text-base font-medium text-emerald-800">
                        Selamat berziarah.
                    </p>
                </section>
            </main>
        </>
    );
}
