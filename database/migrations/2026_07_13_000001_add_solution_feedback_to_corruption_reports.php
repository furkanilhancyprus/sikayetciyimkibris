<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('corruption_reports', function (Blueprint $table): void {
            $table->string('solution_status')->nullable()->after('published_at');
            $table->text('solution_feedback')->nullable()->after('solution_status');
            $table->foreignId('solution_feedback_by')->nullable()->after('solution_feedback')->constrained('users')->nullOnDelete();
            $table->timestamp('solution_feedback_at')->nullable()->after('solution_feedback_by');
            $table->index(['solution_status', 'solution_feedback_at']);
        });
    }

    public function down(): void
    {
        Schema::table('corruption_reports', function (Blueprint $table): void {
            $table->dropIndex(['solution_status', 'solution_feedback_at']);
            $table->dropConstrainedForeignId('solution_feedback_by');
            $table->dropColumn(['solution_status', 'solution_feedback', 'solution_feedback_at']);
        });
    }
};
