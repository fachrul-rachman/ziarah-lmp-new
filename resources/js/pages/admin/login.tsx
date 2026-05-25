import { Head, useForm } from '@inertiajs/react';

import { Button } from './../../components/ui/button';
import { Input } from './../../components/ui/input';

export default function AdminLogin() {
    const form = useForm({
        email: '',
        password: '',
    });

    return (
        <>
            <Head title="Admin Login" />
            <div className="flex min-h-screen items-center justify-center bg-[#F8F9FB] p-6">
                <div className="w-full max-w-md rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
                    <div className="mb-6">
                        <h1 className="text-xl font-semibold text-gray-900">
                            Login Admin
                        </h1>
                        <p className="mt-1 text-sm text-gray-600">
                            Masuk untuk mengelola booking.
                        </p>
                    </div>

                    <form
                        onSubmit={(e) => {
                            e.preventDefault();
                            form.post('/admin/login');
                        }}
                        className="space-y-4"
                    >
                        <div>
                            <label className="mb-1 block text-sm font-medium text-gray-900">
                                Email
                            </label>
                            <Input
                                type="email"
                                value={form.data.email}
                                onChange={(e) =>
                                    form.setData('email', e.target.value)
                                }
                                autoComplete="email"
                            />
                            {form.errors.email ? (
                                <p className="mt-1 text-sm text-red-600">
                                    {form.errors.email}
                                </p>
                            ) : null}
                        </div>

                        <div>
                            <label className="mb-1 block text-sm font-medium text-gray-900">
                                Password
                            </label>
                            <Input
                                type="password"
                                value={form.data.password}
                                onChange={(e) =>
                                    form.setData('password', e.target.value)
                                }
                                autoComplete="current-password"
                            />
                        </div>

                        <Button
                            type="submit"
                            className="w-full"
                            disabled={form.processing}
                        >
                            {form.processing ? 'Memproses…' : 'Login'}
                        </Button>
                    </form>
                </div>
            </div>
        </>
    );
}

