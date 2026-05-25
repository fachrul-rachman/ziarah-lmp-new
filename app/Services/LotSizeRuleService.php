<?php

namespace App\Services;

use App\Models\LotSizeRule;
use App\Support\Normalization;

class LotSizeRuleService
{
    /**
     * @return array{
     *  normalized_size:string,
     *  display_size:string,
     *  chairs_min:int,
     *  chairs_max:int,
     *  burn_barrels_min:int,
     *  burn_barrels_max:int,
     *  tent_allowed:bool,
     *  prayer_table_allowed:bool,
     *  lamp_allowed:bool
     * }
     */
    public function ruleForSize(?string $size): array
    {
        $key = Normalization::normalizeSizeKey($size ?? '');

        if ($key !== '') {
            $rule = LotSizeRule::query()->whereKey($key)->first();
            if ($rule) {
                return [
                    'normalized_size' => (string) $rule->normalized_size,
                    'display_size' => (string) $rule->display_size,
                    'chairs_min' => (int) $rule->chairs_min,
                    'chairs_max' => (int) $rule->chairs_max,
                    'burn_barrels_min' => (int) $rule->burn_barrels_min,
                    'burn_barrels_max' => (int) $rule->burn_barrels_max,
                    'tent_allowed' => (bool) $rule->tent_allowed,
                    'prayer_table_allowed' => (bool) $rule->prayer_table_allowed,
                    'lamp_allowed' => (bool) $rule->lamp_allowed,
                ];
            }
        }

        return $this->defaultRule($key !== '' ? $key : 'global');
    }

    /**
     * @return array<string,mixed>
     */
    public function defaultRule(string $normalizedSize): array
    {
        return [
            'normalized_size' => $normalizedSize,
            'display_size' => Normalization::normalizeSizeDisplay($normalizedSize),
            'chairs_min' => 5,
            'chairs_max' => 10,
            'burn_barrels_min' => 0,
            'burn_barrels_max' => 2,
            'tent_allowed' => true,
            'prayer_table_allowed' => true,
            'lamp_allowed' => true,
        ];
    }

    /**
     * @return array<string,array<string,mixed>>
     */
    public function allRules(): array
    {
        $rules = LotSizeRule::query()
            ->orderBy('display_size')
            ->get()
            ->mapWithKeys(fn (LotSizeRule $r) => [
                (string) $r->normalized_size => [
                    'normalized_size' => (string) $r->normalized_size,
                    'display_size' => (string) $r->display_size,
                    'chairs_min' => (int) $r->chairs_min,
                    'chairs_max' => (int) $r->chairs_max,
                    'burn_barrels_min' => (int) $r->burn_barrels_min,
                    'burn_barrels_max' => (int) $r->burn_barrels_max,
                    'tent_allowed' => (bool) $r->tent_allowed,
                    'prayer_table_allowed' => (bool) $r->prayer_table_allowed,
                    'lamp_allowed' => (bool) $r->lamp_allowed,
                ],
            ])
            ->all();

        return $rules;
    }
}

