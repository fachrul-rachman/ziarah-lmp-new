import { Head } from '@inertiajs/react';

import { Card, CardContent, CardHeader, CardTitle } from './../../components/ui/card';
import { AdminLayout } from './../../layouts/admin-layout';

export default function AdminLocations() {
    return (
        <>
            <Head title="Lokasi dan Lot" />
            <AdminLayout title="Lokasi dan Lot">
                <Card>
                    <CardHeader>
                        <CardTitle>Lokasi dan Lot</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <p className="text-sm text-gray-600">
                            Placeholder. Modul 3 akan berisi CRUD lokasi, zona, lot, dan import Excel.
                        </p>
                    </CardContent>
                </Card>
            </AdminLayout>
        </>
    );
}

