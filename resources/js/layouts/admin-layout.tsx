import { Link, useForm, usePage } from "@inertiajs/react"
import * as React from "react"

import { ConfirmDialog } from "@/components/confirm-dialog"
import { Button } from "@/components/ui/button"
import { cn } from "@/lib/utils"

type NavItem = {
    label: string;
    href: string;
    icon: React.ReactNode;
};

function IconGrid() {
    return (
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none">
            <path
                d="M4 4h7v7H4V4Zm9 0h7v7h-7V4ZM4 13h7v7H4v-7Zm9 0h7v7h-7v-7Z"
                fill="currentColor"
            />
        </svg>
    );
}

function IconMapPin() {
    return (
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none">
            <path
                d="M12 22s7-4.35 7-12a7 7 0 1 0-14 0c0 7.65 7 12 7 12Zm0-9.5a2.5 2.5 0 1 0 0-5 2.5 2.5 0 0 0 0 5Z"
                fill="currentColor"
            />
        </svg>
    );
}

function IconClock() {
    return (
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none">
            <path
                d="M12 22a10 10 0 1 0 0-20 10 10 0 0 0 0 20Zm1-10.4V6h-2v6.6l5.2 3.1 1-1.7-4.2-2.4Z"
                fill="currentColor"
            />
        </svg>
    );
}

function IconCalendar() {
    return (
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none">
            <path
                d="M7 2h2v2h6V2h2v2h3v18H4V4h3V2Zm13 8H6v10h14V10Z"
                fill="currentColor"
            />
        </svg>
    );
}

function IconSettings() {
    return (
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none">
            <path
                d="M19.4 13a7.7 7.7 0 0 0 0-2l2-1.6-2-3.4-2.4 1a7.9 7.9 0 0 0-1.7-1l-.4-2.6H9.1L8.7 6a7.9 7.9 0 0 0-1.7 1L4.6 6l-2 3.4L4.6 11a7.7 7.7 0 0 0 0 2l-2 1.6 2 3.4 2.4-1a7.9 7.9 0 0 0 1.7 1l.4 2.6h5.8l.4-2.6a7.9 7.9 0 0 0 1.7-1l2.4 1 2-3.4-2-1.6ZM12 15.5A3.5 3.5 0 1 1 12 8a3.5 3.5 0 0 1 0 7.5Z"
                fill="currentColor"
            />
        </svg>
    );
}

const navItems: NavItem[] = [
    { label: 'Dashboard', href: '/admin/dashboard', icon: <IconGrid /> },
    { label: 'Lokasi dan Lot', href: '/admin/locations', icon: <IconMapPin /> },
    { label: 'Time Slots', href: '/admin/time-slots', icon: <IconClock /> },
    { label: 'Event', href: '/admin/events', icon: <IconCalendar /> },
    { label: 'Setting', href: '/admin/settings', icon: <IconSettings /> },
];

export function AdminLayout({
    title,
    children,
}: {
    title: string;
    children: React.ReactNode;
}) {
    const { url } = usePage();
    const [collapsed, setCollapsed] = React.useState(false);
    const [mobileOpen, setMobileOpen] = React.useState(false);
    const [confirmLogout, setConfirmLogout] = React.useState(false);
    const logoutForm = useForm({});

    const doLogout = () => logoutForm.post('/admin/logout');

    const Sidebar = (
        <aside
            className={cn(
                'flex h-full shrink-0 flex-col bg-[#202938] text-white',
                collapsed ? 'w-20' : 'w-72',
            )}
        >
            <div className="flex items-center justify-between gap-2 border-b border-white/10 px-4 py-4">
                <div className={cn('min-w-0', collapsed && 'hidden')}>
                    <div className="text-sm font-semibold leading-tight">
                        Admin
                    </div>
                    <div className="text-xs text-white/70">
                        Booking Ziarah
                    </div>
                </div>
                <Button
                    variant="ghost"
                    className="h-10 w-10 text-white hover:bg-white/10 hover:text-white"
                    aria-label={collapsed ? 'Expand sidebar' : 'Collapse sidebar'}
                    title={collapsed ? 'Expand sidebar' : 'Collapse sidebar'}
                    onClick={() => setCollapsed((v) => !v)}
                >
                    <span className="text-lg leading-none">
                        {collapsed ? '>>' : '<<'}
                    </span>
                </Button>
            </div>

            <nav className="flex-1 px-2 py-3">
                <div className="space-y-1">
                    {navItems.map((item) => {
                        const active = url.startsWith(item.href);

                        return (
                            <Link
                                key={item.href}
                                href={item.href}
                                className={cn(
                                    'rounded-md px-3 py-3 text-sm font-medium transition-colors',
                                    collapsed
                                        ? 'flex flex-col items-center gap-1'
                                        : 'flex items-center gap-3',
                                    active
                                        ? 'bg-white/10 text-[#D4AF37]'
                                        : 'text-white/90 hover:bg-white/10 hover:text-white',
                                )}
                                title={item.label}
                            >
                                <span className="shrink-0">{item.icon}</span>
                                <span
                                    className={cn(
                                        collapsed
                                            ? 'text-center text-[10px] leading-tight'
                                            : 'truncate',
                                    )}
                                >
                                    {item.label}
                                </span>
                            </Link>
                        );
                    })}
                </div>
            </nav>

            <div className="border-t border-white/10 p-3">
                <Button
                    variant="outline"
                    className={cn(
                        'w-full border-white/20 bg-transparent text-white hover:bg-white/10 hover:text-white',
                        collapsed && 'px-0 text-xs',
                    )}
                    onClick={() => setConfirmLogout(true)}
                    title="Logout"
                >
                    Logout
                </Button>
            </div>
        </aside>
    );

    return (
        <div className="min-h-screen bg-[#F8F9FB] text-gray-900">
            <div className="hidden h-screen overflow-hidden md:flex">
                {Sidebar}
                <div className="flex min-w-0 flex-1 flex-col">
                    <header className="flex items-center justify-between border-b border-gray-200 bg-white px-4 py-4">
                        <h1 className="text-lg font-semibold">{title}</h1>
                    </header>
                    <main className="min-h-0 min-w-0 flex-1 overflow-y-auto p-4">
                        {children}
                    </main>
                </div>
            </div>

            <div className="md:hidden">
                <header className="flex items-center justify-between border-b border-gray-200 bg-white px-4 py-4">
                    <Button
                        variant="outline"
                        onClick={() => setMobileOpen(true)}
                    >
                        Menu
                    </Button>
                    <h1 className="text-base font-semibold">{title}</h1>
                </header>

                <main className="p-4">{children}</main>

                {mobileOpen ? (
                    <div className="fixed inset-0 z-40">
                        <div
                            className="absolute inset-0 bg-black/40"
                            onClick={() => setMobileOpen(false)}
                        />
                        <div className="absolute inset-y-0 left-0 w-72">
                            {Sidebar}
                        </div>
                    </div>
                ) : null}
            </div>

            <ConfirmDialog
                open={confirmLogout}
                onOpenChange={setConfirmLogout}
                title="Logout?"
                description="Anda yakin ingin logout dari dashboard admin?"
                confirmText="Logout"
                confirmVariant="destructive"
                onConfirm={doLogout}
            />
        </div>
    );
}
