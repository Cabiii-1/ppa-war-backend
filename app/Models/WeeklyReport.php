<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WeeklyReport extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'period_start',
        'period_end',
        'submitted_at',
        'status',
    ];

    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
        'submitted_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function entries(): HasMany
    {
        return $this->hasMany(Entry::class);
    }

    public function employee()
    {
        // Instead of using a relationship that might fail,
        // we'll handle this in the service layer if needed
        return null;
    }
}
