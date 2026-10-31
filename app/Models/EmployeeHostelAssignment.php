<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeHostelAssignment extends Model
{
    protected $fillable = ['hostel_id', 'employee_id', 'start_date', 'end_date', 'notes'];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function hostel(): BelongsTo
    {
        return $this->belongsTo(Hostel::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
