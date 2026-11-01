<?php

use Illuminate\\Database\\Migrations\\Migration;
use Illuminate\\Database\\Schema\\Blueprint;
use Illuminate\\Support\\Facades\\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->foreignId('contract_id')->nullable()->change();
            $table->string('father_name')->nullable()->change();
            $table->string('last_name')->nullable()->change();
            $table->string('mother_name')->nullable()->change();
            $table->string('birth_and_place')->nullable()->change();
            $table->string('national_number')->nullable()->change();
            $table->string('mobile_number')->nullable()->change();
            $table->string('degree')->nullable()->change();
            $table->boolean('gender')->nullable()->change();
            $table->string('address')->nullable()->change();
            $table->string('profile_photo_path')->nullable()->change();

            $table->string('staff_department', 40)->default('Housekeeping')->after('address');
            $table->boolean('is_reliever')->default(false)->after('staff_department');
            $table->string('reliever_type', 40)->nullable()->after('is_reliever');
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn(['staff_department', 'is_reliever', 'reliever_type']);
            $table->foreignId('contract_id')->nullable(false)->change();
            $table->string('father_name')->nullable(false)->change();
            $table->string('last_name')->nullable(false)->change();
            $table->string('mother_name')->nullable(false)->change();
            $table->string('birth_and_place')->nullable(false)->change();
            $table->string('national_number')->nullable(false)->change();
            $table->string('mobile_number')->nullable(false)->change();
            $table->string('degree')->nullable(false)->change();
            $table->boolean('gender')->nullable(false)->change();
            $table->string('address')->nullable(false)->change();
            $table->string('profile_photo_path')->nullable(false)->change();
        });
    }
};
