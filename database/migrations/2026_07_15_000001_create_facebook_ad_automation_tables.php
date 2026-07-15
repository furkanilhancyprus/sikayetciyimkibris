<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('facebook_ad_creatives', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->text('comment_text');
            $table->string('target_url')->nullable();
            $table->string('image_url')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('weight')->default(1);
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();
            $table->index(['is_active', 'weight']);
        });

        Schema::create('facebook_automation_settings', function (Blueprint $table): void {
            $table->id();
            $table->string('page_id')->nullable();
            $table->string('page_name')->default('Haberler KKTC');
            $table->boolean('is_enabled')->default(false);
            $table->boolean('approval_required')->default(true);
            $table->unsignedInteger('check_interval_minutes')->default(15);
            $table->unsignedInteger('min_delay_minutes')->default(5);
            $table->unsignedInteger('max_delay_minutes')->default(20);
            $table->unsignedInteger('max_comments_per_hour')->default(4);
            $table->unsignedInteger('max_comments_per_day')->default(25);
            $table->unsignedInteger('same_creative_cooldown_hours')->default(12);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('facebook_comment_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('facebook_ad_creative_id')->nullable()->constrained()->nullOnDelete();
            $table->string('facebook_post_id');
            $table->string('facebook_comment_id')->nullable();
            $table->string('status')->default('pending');
            $table->text('message');
            $table->text('error_message')->nullable();
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('posted_at')->nullable();
            $table->timestamps();
            $table->unique('facebook_post_id');
            $table->index(['status', 'scheduled_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('facebook_comment_logs');
        Schema::dropIfExists('facebook_automation_settings');
        Schema::dropIfExists('facebook_ad_creatives');
    }
};
