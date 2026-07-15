<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('corruption_reports', function (Blueprint $table): void {
            $table->text('public_body')->nullable()->after('body');
        });
    }

    public function down(): void
    {
        Schema::table('corruption_reports', function (Blueprint $table): void {
            $table->dropColumn('public_body');
        });
    }
};
