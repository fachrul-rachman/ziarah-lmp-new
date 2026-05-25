<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Lot;
use App\Models\LotSizeRule;
use App\Services\LotSizeRuleService;
use App\Support\Normalization;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LotSizeRuleController extends Controller
{
    public function __construct(private readonly LotSizeRuleService $rules)
    {
    }

    public function index(): JsonResponse
    {
        $sizes = Lot::query()
            ->whereNull('deleted_at')
            ->select(['normalized_size', 'size'])
            ->groupBy(['normalized_size', 'size'])
            ->orderBy('size')
            ->get()
            ->map(fn (Lot $l) => [
                'normalized_size' => (string) $l->normalized_size,
                'display_size' => (string) $l->size,
            ])
            ->values();

        return response()->json([
            'default_rule' => $this->rules->defaultRule('global'),
            'sizes' => $sizes,
            'rules' => $this->rules->allRules(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $rawRules = $request->input('rules');
        if (is_string($rawRules)) {
            $decoded = json_decode($rawRules, true);
            if (! is_array($decoded)) {
                return response()->json([
                    'message' => 'Format rules tidak valid.',
                ], 422);
            }
            $request->merge(['rules' => $decoded]);
        }

        $validated = $request->validate([
            'rules' => ['required', 'array', 'min:1'],
            'rules.*.normalized_size' => ['required', 'string', 'max:255'],
            'rules.*.display_size' => ['required', 'string', 'max:255'],
            'rules.*.chairs_min' => ['required', 'integer', 'min:0', 'max:200'],
            'rules.*.chairs_max' => ['required', 'integer', 'min:0', 'max:200'],
            'rules.*.burn_barrels_min' => ['required', 'integer', 'min:0', 'max:50'],
            'rules.*.burn_barrels_max' => ['required', 'integer', 'min:0', 'max:50'],
            'rules.*.tent_allowed' => ['required', 'boolean'],
            'rules.*.prayer_table_allowed' => ['required', 'boolean'],
            'rules.*.lamp_allowed' => ['required', 'boolean'],
        ]);

        $rules = collect($validated['rules'])
            ->map(function (array $r) {
                $key = Normalization::normalizeSizeKey((string) $r['normalized_size']);
                $display = Normalization::normalizeSizeDisplay((string) $r['display_size']);

                $chairsMin = (int) $r['chairs_min'];
                $chairsMax = (int) $r['chairs_max'];
                $burnMin = (int) $r['burn_barrels_min'];
                $burnMax = (int) $r['burn_barrels_max'];

                if ($chairsMin > $chairsMax) {
                    throw new \RuntimeException("chairs_min tidak boleh lebih besar dari chairs_max untuk ukuran {$display}.");
                }
                if ($burnMin > $burnMax) {
                    throw new \RuntimeException("burn_barrels_min tidak boleh lebih besar dari burn_barrels_max untuk ukuran {$display}.");
                }

                return [
                    'normalized_size' => $key,
                    'display_size' => $display,
                    'chairs_min' => $chairsMin,
                    'chairs_max' => $chairsMax,
                    'burn_barrels_min' => $burnMin,
                    'burn_barrels_max' => $burnMax,
                    'tent_allowed' => (bool) $r['tent_allowed'],
                    'prayer_table_allowed' => (bool) $r['prayer_table_allowed'],
                    'lamp_allowed' => (bool) $r['lamp_allowed'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            })
            ->values()
            ->all();

        DB::transaction(function () use ($rules) {
            LotSizeRule::query()->upsert(
                $rules,
                ['normalized_size'],
                [
                    'display_size',
                    'chairs_min',
                    'chairs_max',
                    'burn_barrels_min',
                    'burn_barrels_max',
                    'tent_allowed',
                    'prayer_table_allowed',
                    'lamp_allowed',
                    'updated_at',
                ],
            );
        });

        return response()->json([
            'ok' => true,
            'rules' => $this->rules->allRules(),
        ]);
    }
}
