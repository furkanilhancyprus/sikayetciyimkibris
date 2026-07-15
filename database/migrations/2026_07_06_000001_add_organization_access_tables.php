<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->foreignId('entity_id')
                ->nullable()
                ->after('phone')
                ->constrained()
                ->nullOnDelete();
        });

        Schema::create('organization_invitations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('entity_id')->constrained()->cascadeOnDelete();
            $table->text('invited_email');
            $table->string('invited_email_hash', 64)->index();
            $table->string('contact_name')->nullable();
            $table->string('token', 80)->unique();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('accepted_at')->nullable();
            $table->foreignId('accepted_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::table('report_messages', function (Blueprint $table): void {
            $table->foreignId('user_id')
                ->nullable()
                ->after('sender_type')
                ->constrained()
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('report_messages', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('user_id');
        });

        Schema::dropIfExists('organization_invitations');

        Schema::table('users', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('entity_id');
        });
    }
};
