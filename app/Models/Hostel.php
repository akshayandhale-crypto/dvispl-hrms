<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Hostel extends Model
{
    use SoftDeletes;

    protected $fillable = ['name', 'code', 'address', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->withPivot('assignment_role')
            ->withTimestamps();
    }

    public function employeeAssignments(): HasMany
    {
        return $this->hasMany(EmployeeHostelAssignment::class);
    }
}
