<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ImportJobError extends Model
{
    protected $fillable = [
        'import_job_id',
        'row_number',
        'raw_data_json',
        'error_message',
    ];

    protected $casts = [
        'raw_data_json' => 'array',
    ];

    public function importJob(): BelongsTo
    {
        return $this->belongsTo(ImportJob::class);
    }
}

