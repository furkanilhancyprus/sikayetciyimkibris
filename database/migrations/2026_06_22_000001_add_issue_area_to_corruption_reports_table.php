<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('corruption_reports', function (Blueprint $table): void {
            $table->string('issue_area')->nullable()->after('intake_type')->index();
        });
    }

    public function down(): void
    {
        Schema::table('corruption_reports', function (Blueprint $table): void {
            $table->dropColumn('issue_area');
        });
    }
};
