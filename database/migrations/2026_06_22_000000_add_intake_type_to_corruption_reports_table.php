<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('corruption_reports', function (Blueprint $table): void {
            $table->enum('intake_type', ['complaint', 'report'])
                ->default('report')
                ->after('tracking_code')
                ->index();
        });
    }

    public function down(): void
    {
        Schema::table('corruption_reports', function (Blueprint $table): void {
            $table->dropColumn('intake_type');
        });
    }
};
