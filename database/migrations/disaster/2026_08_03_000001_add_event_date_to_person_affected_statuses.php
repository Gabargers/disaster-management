<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('person_affected_statuses', function (Blueprint $table) {
            $table->date('event_date')->nullable()->after('date_tagged');
        });

        DB::table('person_affected_statuses')
            ->select(['id', 'date_tagged'])
            ->orderBy('id')
            ->chunkById(500, function ($statuses): void {
                foreach ($statuses as $status) {
                    DB::table('person_affected_statuses')
                        ->where('id', $status->id)
                        ->update(['event_date' => substr((string) $status->date_tagged, 0, 10)]);
                }
            });

        Schema::table('person_affected_statuses', function (Blueprint $table) {
            $table->unique(
                ['person_affected_id', 'event_date'],
                'person_affected_event_date_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::table('person_affected_statuses', function (Blueprint $table) {
            $table->dropUnique('person_affected_event_date_unique');
            $table->dropColumn('event_date');
        });
    }
};
