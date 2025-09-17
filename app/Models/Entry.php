<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Entry extends Model
{
    use HasFactory;

    protected $fillable = [
        'weekly_report_id',
        'employee_id',
        'entry_date',
        'ppa',
        'kpi',
        'status',
        'remarks',
    ];

    protected $casts = [
        'entry_date' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function weeklyReport(): BelongsTo
    {
        return $this->belongsTo(WeeklyReport::class);
    }
}
