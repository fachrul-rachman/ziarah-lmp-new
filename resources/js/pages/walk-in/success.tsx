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
            <main className="flex min-h-screen items-center justify-center bg-[#f7f8fa] px-4 py-10">
                <section className="w-full max-w-lg rounded-lg border-2 border-[#1a2744]/25 border-t-[3px] border-t-[#c9a84c] bg-white p-7 text-center shadow-[0_1px_4px_rgba(26,39,68,0.06)] sm:p-10">
                    <CheckCircle2
                        className="mx-auto h-20 w-20 text-[#1a2744]"
                        strokeWidth={2.5}
                        aria-hidden="true"
                    />
                    <h1 className="mt-5 text-3xl font-bold text-[#1a2744]">
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
                    <p className="mt-6 text-base font-medium text-[#9b7927]">
                        Selamat berziarah.
                    </p>
                </section>
            </main>
        </>
    );
}
