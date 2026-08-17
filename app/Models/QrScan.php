<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QrScan extends Model
{
    protected $fillable = ['scanned_at', 'user_agent', 'utm_source', 'table_number'];

    protected $casts = ['scanned_at' => 'datetime'];
}
