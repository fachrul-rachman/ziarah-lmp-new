<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LotSizeRule extends Model
{
    protected $table = 'lot_size_rules';

    protected $primaryKey = 'normalized_size';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'normalized_size',
        'display_size',
        'chairs_min',
        'chairs_max',
        'burn_barrels_min',
        'burn_barrels_max',
        'tent_allowed',
        'prayer_table_allowed',
        'lamp_allowed',
    ];

    protected $casts = [
        'chairs_min' => 'integer',
        'chairs_max' => 'integer',
        'burn_barrels_min' => 'integer',
        'burn_barrels_max' => 'integer',
        'tent_allowed' => 'boolean',
        'prayer_table_allowed' => 'boolean',
        'lamp_allowed' => 'boolean',
    ];
}

