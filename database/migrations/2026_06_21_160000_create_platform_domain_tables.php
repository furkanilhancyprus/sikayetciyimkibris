<?php

use App\Enums\CorruptionReportStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->text('phone')->nullable()->after('email');
        });

        Schema::create('regions', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->enum('type', ['il', 'ilce', 'belde', 'koy']);
            $table->foreignId('parent_id')->nullable()->constrained('regions')->nullOnDelete();
            $table->timestamps();
            $table->index(['type', 'parent_id']);
        });

        Schema::create('entities', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('category');
            $table->foreignId('region_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
            $table->index(['category', 'region_id']);
        });

        Schema::create('complaints', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('region_id')->constrained();
            $table->foreignId('entity_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('type', ['complaint', 'suggestion', 'request']);
            $table->string('title');
            $table->text('body');
            $table->enum('status', ['pending', 'visible', 'hidden'])->default('pending');
            $table->timestamps();
            $table->index(['status', 'type', 'region_id']);
        });

        if (in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            Schema::table('complaints', function (Blueprint $table): void {
                $table->fullText(['title', 'body']);
            });
        }

        Schema::create('complaint_votes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('complaint_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['complaint_id', 'user_id']);
        });

        Schema::create('surveys', function (Blueprint $table): void {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->foreignId('region_scope_id')->nullable()->constrained('regions')->nullOnDelete();
            $table->boolean('is_active')->default(false);
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->timestamps();
        });

        Schema::create('survey_questions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('survey_id')->constrained()->cascadeOnDelete();
            $table->text('question_text');
            $table->enum('type', ['single_choice', 'multi_choice', 'scale', 'text']);
            $table->unsignedInteger('order')->default(0);
            $table->timestamps();
            $table->index(['survey_id', 'order']);
        });

        Schema::create('survey_options', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('question_id')->constrained('survey_questions')->cascadeOnDelete();
            $table->string('option_text');
            $table->timestamps();
        });

        Schema::create('survey_responses', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('survey_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('visitor_fingerprint')->nullable();
            $table->timestamp('submitted_at');
            $table->timestamps();
            $table->unique(['survey_id', 'user_id']);
            $table->index(['survey_id', 'visitor_fingerprint']);
        });

        Schema::create('survey_answers', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('response_id')->constrained('survey_responses')->cascadeOnDelete();
            $table->foreignId('question_id')->constrained('survey_questions')->cascadeOnDelete();
            $table->foreignId('option_id')->nullable()->constrained('survey_options')->nullOnDelete();
            $table->text('free_text')->nullable();
            $table->unsignedTinyInteger('scale_value')->nullable();
            $table->timestamps();
        });

        Schema::create('corruption_reports', function (Blueprint $table): void {
            $table->id();
            $table->string('tracking_code', 32)->unique();
            $table->foreignId('entity_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('region_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->text('body');
            $table->text('reporter_name')->nullable();
            $table->text('reporter_contact')->nullable();
            $table->boolean('identity_disclosed')->default(false);
            $table->timestamp('disclosure_consent_at')->nullable();
            $table->text('disclosure_consent_text')->nullable();
            $table->enum('status', array_column(CorruptionReportStatus::cases(), 'value'))
                ->default(CorruptionReportStatus::Submitted->value);
            $table->foreignId('assigned_reporter_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('editor_approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('editor_approved_at')->nullable();
            $table->foreignId('legal_approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('legal_approved_at')->nullable();
            $table->text('rejected_reason')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            $table->index(['status', 'created_at']);
        });

        Schema::create('evidence_files', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('corruption_report_id')->constrained()->cascadeOnDelete();
            $table->string('original_filename');
            $table->text('encrypted_storage_path');
            $table->string('mime_type');
            $table->unsignedBigInteger('size_bytes');
            $table->timestamp('uploaded_at');
            $table->timestamps();
        });

        Schema::create('report_messages', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('corruption_report_id')->constrained()->cascadeOnDelete();
            $table->enum('sender_type', ['reporter', 'team']);
            $table->text('body');
            $table->timestamps();
        });

        Schema::create('moderation_logs', function (Blueprint $table): void {
            $table->id();
            $table->morphs('loggable');
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action');
            $table->text('reason');
            $table->timestamps();
            $table->index(['action', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('moderation_logs');
        Schema::dropIfExists('report_messages');
        Schema::dropIfExists('evidence_files');
        Schema::dropIfExists('corruption_reports');
        Schema::dropIfExists('survey_answers');
        Schema::dropIfExists('survey_responses');
        Schema::dropIfExists('survey_options');
        Schema::dropIfExists('survey_questions');
        Schema::dropIfExists('surveys');
        Schema::dropIfExists('complaint_votes');
        Schema::dropIfExists('complaints');
        Schema::dropIfExists('entities');
        Schema::dropIfExists('regions');

        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn('phone');
        });
    }
};
