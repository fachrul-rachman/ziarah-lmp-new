<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExportJob extends Model
{
    protected $fillable = [
        'status',
        'format',
        'disk',
        'filters_json',
        'file_path',
        'error_message',
        'started_at',
        'finished_at',
    ];

    protected $casts = [
        'filters_json' => 'array',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
    ];
}
