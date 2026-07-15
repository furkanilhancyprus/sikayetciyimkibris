<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('entities')
            ->where('name', 'Karpaz Belediyesi')
            ->update(['name' => 'Yenierenköy-Dipkarpaz Belediyesi']);
    }

    public function down(): void
    {
        DB::table('entities')
            ->where('name', 'Yenierenköy-Dipkarpaz Belediyesi')
            ->update(['name' => 'Karpaz Belediyesi']);
    }
};
