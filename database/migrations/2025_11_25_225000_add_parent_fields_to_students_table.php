<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->string('father_name')->nullable()->after('pincode');
            $table->string('father_phone', 15)->nullable()->after('father_name');
            $table->string('father_occupation')->nullable()->after('father_phone');
            $table->string('mother_name')->nullable()->after('father_occupation');
            $table->string('mother_phone', 15)->nullable()->after('mother_name');
            $table->string('mother_occupation')->nullable()->after('mother_phone');
            $table->string('guardian_name')->nullable()->after('mother_occupation');
            $table->string('guardian_phone', 15)->nullable()->after('guardian_name');
            $table->string('guardian_relation')->nullable()->after('guardian_phone');
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn([
                'father_name',
                'father_phone',
                'father_occupation',
                'mother_name',
                'mother_phone',
                'mother_occupation',
                'guardian_name',
                'guardian_phone',
                'guardian_relation',
            ]);
        });
    }
};
