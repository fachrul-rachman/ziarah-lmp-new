<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class EthicsConsentService
{
    public function imageUrl(): ?string
    {
        $path = trim((string) DB::table('settings')->where('key', 'ethics_image_path')->value('value'));

        if ($path === '' || ! Storage::disk('public')->exists($path)) {
            return null;
        }

        return Storage::disk('public')->url($path);
    }
}
