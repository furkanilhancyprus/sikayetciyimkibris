<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'email_hash')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->string('email_hash', 64)->nullable()->unique()->after('email');
            });
        }

        DB::table('users')
            ->whereNull('email_hash')
            ->orderBy('id')
            ->get(['id', 'email'])
            ->each(function ($user): void {
                DB::table('users')
                    ->where('id', $user->id)
                    ->update(['email_hash' => hash('sha256', mb_strtolower((string) $user->email))]);
            });
    }

    public function down(): void
    {
        if (Schema::hasColumn('users', 'email_hash')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->dropColumn('email_hash');
            });
        }
    }
};
