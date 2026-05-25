<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Location extends Model
{
    protected $fillable = [
        'name',
    ];

    public function zones(): HasMany
    {
        return $this->hasMany(Zone::class);
    }

    public function lots(): HasMany
    {
        return $this->hasMany(Lot::class);
    }
}

