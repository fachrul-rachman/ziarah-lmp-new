const ALLOWED_IMAGE_TYPES = ['image/jpeg', 'image/png', 'image/webp'];
const MAX_SOURCE_BYTES = 12 * 1024 * 1024;
const TARGET_UPLOAD_BYTES = 850 * 1024;

export async function prepareImageUpload(file: File): Promise<File> {
    if (!ALLOWED_IMAGE_TYPES.includes(file.type)) {
        throw new Error('Gunakan foto berformat JPG, PNG, atau WebP.');
    }

    if (file.size > MAX_SOURCE_BYTES) {
        throw new Error('Ukuran foto awal maksimal 12 MB.');
    }

    if (file.size <= TARGET_UPLOAD_BYTES) {
        return file;
    }

    if (!('createImageBitmap' in window)) {
        throw new Error('Browser ini belum mendukung pengecilan foto. Gunakan foto di bawah 850 KB.');
    }

    const bitmap = await createImageBitmap(file);
    let maxDimension = 1600;
    let quality = 0.82;
    let latestBlob: Blob | null = null;

    try {
        for (let attempt = 0; attempt < 8; attempt += 1) {
            const scale = Math.min(1, maxDimension / Math.max(bitmap.width, bitmap.height));
            const canvas = document.createElement('canvas');

            canvas.width = Math.max(1, Math.round(bitmap.width * scale));
            canvas.height = Math.max(1, Math.round(bitmap.height * scale));

            const context = canvas.getContext('2d');

            if (!context) {
                throw new Error('Foto gagal disiapkan. Silakan pilih foto lain.');
            }

            context.drawImage(bitmap, 0, 0, canvas.width, canvas.height);
            latestBlob = await canvasToBlob(canvas, quality);

            if (latestBlob.size <= TARGET_UPLOAD_BYTES) {
                return asWebpFile(file, latestBlob);
            }

            if (quality > 0.55) {
                quality -= 0.1;
            } else {
                maxDimension = Math.round(maxDimension * 0.8);
                quality = 0.75;
            }
        }
    } finally {
        bitmap.close();
    }

    if (latestBlob && latestBlob.size <= 2 * 1024 * 1024) {
        return asWebpFile(file, latestBlob);
    }

    throw new Error('Foto masih terlalu besar setelah diperkecil. Silakan pilih foto lain.');
}

function canvasToBlob(canvas: HTMLCanvasElement, quality: number): Promise<Blob> {
    return new Promise((resolve, reject) => {
        canvas.toBlob(
            (blob) => {
                if (blob) {
                    resolve(blob);
                } else {
                    reject(new Error('Foto gagal disiapkan. Silakan pilih foto lain.'));
                }
            },
            'image/webp',
            quality,
        );
    });
}

function asWebpFile(source: File, blob: Blob): File {
    const name = source.name.replace(/\.[^.]+$/, '') || 'foto-etika';

    return new File([blob], `${name}.webp`, {
        type: 'image/webp',
        lastModified: Date.now(),
    });
}
