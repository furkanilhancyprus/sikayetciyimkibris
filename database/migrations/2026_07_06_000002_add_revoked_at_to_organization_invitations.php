<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('organization_invitations', function (Blueprint $table): void {
            $table->timestamp('revoked_at')->nullable()->after('accepted_at');
            $table->timestamp('last_sent_at')->nullable()->after('revoked_at');
        });
    }

    public function down(): void
    {
        Schema::table('organization_invitations', function (Blueprint $table): void {
            $table->dropColumn(['revoked_at', 'last_sent_at']);
        });
    }
};
