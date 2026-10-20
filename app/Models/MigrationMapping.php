<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MigrationMapping extends Model
{
    protected $fillable = [
        'source_system',
        'source_type',
        'source_id',
        'target_type',
        'target_id',
        'source_url',
        'target_url',
        'status',
        'notes',
    ];
}
