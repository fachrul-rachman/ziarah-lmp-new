<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class EthicsConsentService
{
    public function imageUrl(): ?string
    {
        return $this->urlFor('ethics_image_path');
    }

    public function pdfUrl(): ?string
    {
        return $this->urlFor('ethics_pdf_path');
    }

    private function urlFor(string $key): ?string
    {
        $path = trim((string) DB::table('settings')->where('key', $key)->value('value'));

        if ($path === '' || ! Storage::disk('public')->exists($path)) {
            return null;
        }

        return Storage::disk('public')->url($path);
    }
}
