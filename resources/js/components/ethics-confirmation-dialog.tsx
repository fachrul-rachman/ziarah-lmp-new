import * as React from 'react';

import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';

type EthicsConfirmationDialogProps = {
    open: boolean;
    imageUrl?: string | null;
    processing?: boolean;
    onConfirm: () => void;
};

export function EthicsConfirmationDialog({
    open,
    imageUrl,
    processing = false,
    onConfirm,
}: EthicsConfirmationDialogProps) {
    const [confirmed, setConfirmed] = React.useState(false);

    function confirmAndReset() {
        setConfirmed(false);
        onConfirm();
    }

    return (
        <Dialog open={open} onOpenChange={() => undefined}>
            <DialogContent
                className="max-h-[calc(100vh-2rem)] overflow-y-auto border-2 border-[#1a2744]/25 border-t-[3px] border-t-[#c9a84c] sm:max-w-xl"
                showCloseButton={false}
                onEscapeKeyDown={(event) => event.preventDefault()}
                onPointerDownOutside={(event) => event.preventDefault()}
                onInteractOutside={(event) => event.preventDefault()}
            >
                <DialogHeader>
                    <DialogTitle className="text-xl leading-7 text-[#1a2744]">
                        Konfirmasi Etika Berziarah
                    </DialogTitle>
                    <DialogDescription className="text-base leading-6">
                        Periksa informasi berikut sebelum data dikirim.
                    </DialogDescription>
                </DialogHeader>

                {imageUrl ? (
                    <img
                        src={imageUrl}
                        alt="Etika Berziarah Lestari Memorial Park"
                        className="max-h-[48vh] w-full rounded-md border object-contain"
                    />
                ) : (
                    <div className="flex min-h-40 items-center justify-center rounded-md border bg-gray-50 p-6 text-center text-base text-gray-600">
                        Foto Etika Berziarah belum dipasang oleh admin.
                    </div>
                )}

                <label className="flex cursor-pointer items-start gap-3 rounded-md border-2 border-[#c9a84c]/50 bg-[#c9a84c]/10 p-4 text-base leading-6 text-[#1a2744]">
                    <input
                        type="checkbox"
                        className="mt-1 h-6 w-6 shrink-0 accent-[#06038D]"
                        checked={confirmed}
                        onChange={(event) => setConfirmed(event.target.checked)}
                    />
                    <strong>
                        Saya mengkonfirmasi bahwa data sudah benar dan akan
                        mematuhi Etika Berziarah Lestari Memorial Park
                    </strong>
                </label>

                <DialogFooter>
                    <Button
                        type="button"
                        className="min-h-12 w-full bg-[#1a2744] text-base hover:bg-[#243359] sm:w-auto"
                        disabled={!confirmed || processing}
                        onClick={confirmAndReset}
                    >
                        {processing ? 'Mengirim...' : 'Konfirmasi dan Kirim'}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
