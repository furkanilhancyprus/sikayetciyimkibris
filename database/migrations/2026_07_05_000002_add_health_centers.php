<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $districts = DB::table('regions')
            ->whereIn('name', ['Lefkoşa', 'Gazimağusa', 'Girne', 'Güzelyurt', 'İskele', 'Lefke'])
            ->pluck('id', 'name');

        foreach ($districts as $name => $id) {
            DB::table('entities')->updateOrInsert(
                ['name' => "Sağlık Ocağı - {$name}"],
                ['category' => 'sağlık', 'region_id' => $id, 'created_at' => now(), 'updated_at' => now()]
            );
        }
    }

    public function down(): void
    {
        DB::table('entities')
            ->whereIn('name', [
                'Sağlık Ocağı - Lefkoşa',
                'Sağlık Ocağı - Gazimağusa',
                'Sağlık Ocağı - Girne',
                'Sağlık Ocağı - Güzelyurt',
                'Sağlık Ocağı - İskele',
                'Sağlık Ocağı - Lefke',
            ])
            ->delete();
    }
};
