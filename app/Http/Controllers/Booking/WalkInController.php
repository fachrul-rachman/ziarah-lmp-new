<?php

namespace App\Http\Controllers\Booking;

use App\Http\Controllers\Controller;
use App\Models\WalkIn;
use App\Services\EthicsConsentService;
use App\Support\Normalization;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class WalkInController extends Controller
{
    public function __construct(private readonly EthicsConsentService $ethicsConsent) {}

    public function index(): Response
    {
        return Inertia::render('walk-in/index', [
            'ethics_image_url' => $this->ethicsConsent->imageUrl(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'customer_name' => ['required', 'string', 'max:255'],
            'customer_phone' => ['required', 'string', 'max:32'],
            'lot_number' => ['nullable', 'string', 'max:10'],
            'ethics_confirmed' => ['required', 'accepted'],
        ], [
            'customer_name.required' => 'Nama wajib diisi.',
            'customer_phone.required' => 'Nomor telepon wajib diisi.',
            'lot_number.max' => 'Nomor lot maksimal 10 karakter.',
            'ethics_confirmed.accepted' => 'Persetujuan etika berziarah wajib dicentang.',
        ]);

        try {
            $phone = Normalization::normalizePhoneId((string) $validated['customer_phone']);
        } catch (\RuntimeException $e) {
            return redirect()->back()->withErrors([
                'customer_phone' => $e->getMessage(),
            ]);
        }

        $lotNumber = Normalization::normalizeText((string) ($validated['lot_number'] ?? ''));

        $walkIn = WalkIn::query()->create([
            'public_token' => (string) Str::ulid(),
            'customer_name' => Normalization::normalizeText((string) $validated['customer_name']),
            'customer_phone' => $phone,
            'lot_number' => $lotNumber !== '' ? $lotNumber : null,
            'ethics_consented_at' => now(),
        ]);

        return redirect()->route('walk-in.success', $walkIn->public_token);
    }

    public function success(string $publicToken): Response
    {
        $walkIn = WalkIn::query()->where('public_token', $publicToken)->firstOrFail();

        return Inertia::render('walk-in/success', [
            'walkIn' => [
                'customer_name' => $walkIn->customer_name,
                'lot_number' => $walkIn->lot_number,
                'visited_at' => $walkIn->created_at?->timezone('Asia/Jakarta')->format('d M Y, H:i'),
            ],
        ]);
    }
}
