<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('id_number', 50)->nullable()->unique()->after('uuid');
        });

        DB::table('users')->select('id')->orderBy('id')->get()->each(function (object $user): void {
            DB::table('users')->where('id', $user->id)->update([
                'id_number' => 'USR-'.str_pad((string) $user->id, 6, '0', STR_PAD_LEFT),
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['id_number']);
            $table->dropColumn('id_number');
        });
    }
};
