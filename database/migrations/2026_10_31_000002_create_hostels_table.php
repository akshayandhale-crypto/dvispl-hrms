<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hostels', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('code')->unique();
            $table->string('address')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('hostel_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hostel_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('assignment_role', 40);
            $table->timestamps();
            $table->unique(['hostel_id', 'user_id']);
            $table->index(['user_id', 'assignment_role']);
        });

        Schema::create('employee_hostel_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hostel_id')->constrained()->restrictOnDelete();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->string('notes')->nullable();
            $table->timestamps();
            $table->index(['employee_id', 'start_date', 'end_date'], 'employee_hostel_dates_idx');
            $table->index(['hostel_id', 'start_date', 'end_date'], 'hostel_employee_dates_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_hostel_assignments');
        Schema::dropIfExists('hostel_user');
        Schema::dropIfExists('hostels');
    }
};
