<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ImportJob extends Model
{
    protected $fillable = [
        'filename',
        'status',
        'total_rows',
        'processed_rows',
        'success_rows',
        'failed_rows',
        'started_at',
        'finished_at',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
    ];

    public function errors(): HasMany
    {
        return $this->hasMany(ImportJobError::class);
    }
}

