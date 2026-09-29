<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['person_affecteds', 'person_affected_family_members'] as $table) {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'sex')) {
                continue;
            }

            foreach ([
                'Male' => ['male', 'm', 'lalaki'],
                'Female' => ['female', 'f', 'babae'],
            ] as $canonical => $values) {
                DB::table($table)
                    ->whereIn(DB::raw('LOWER(TRIM(sex))'), $values)
                    ->update(['sex' => $canonical]);
            }
        }

        if (! Schema::hasTable('family_members')
            || ! Schema::hasTable('person_affecteds')
            || ! Schema::hasTable('person_affected_family_members')) {
            return;
        }

        DB::table('person_affecteds')
            ->whereNotNull('affected_family_id')
            ->whereNotNull('control_number')
            ->select(['id', 'affected_family_id', 'control_number'])
            ->orderBy('id')
            ->chunkById(100, function ($heads) {
                foreach ($heads as $head) {
                    $residentMembers = DB::table('person_affecteds')
                        ->where('family_head_control_number', $head->control_number)
                        ->where('control_number', '!=', $head->control_number)
                        ->get(['full_name', 'relationship', 'age', 'sex', 'code']);
                    $snapshotMembers = DB::table('person_affected_family_members')
                        ->where('person_affected_id', $head->id)
                        ->where('control_number', '!=', $head->control_number)
                        ->get(['full_name', 'relationship', 'age', 'sex', 'code']);

                    $sourceByName = $snapshotMembers->concat($residentMembers)
                        ->filter(fn ($member) => filled($member->full_name))
                        ->keyBy(fn ($member) => $this->nameKey($member->full_name));

                    if ($sourceByName->isEmpty()) {
                        continue;
                    }

                    $existingByName = DB::table('family_members')
                        ->where('affected_family_id', $head->affected_family_id)
                        ->get(['id', 'name', 'sex'])
                        ->keyBy(fn ($member) => $this->nameKey($member->name));

                    $sourceByName->each(function ($source, string $nameKey) use ($existingByName, $head) {
                        $sex = $this->normalizeSex($source->sex);
                        $existing = $existingByName->get($nameKey);

                        if ($existing) {
                            if ($sex && blank($existing->sex)) {
                                DB::table('family_members')->where('id', $existing->id)->update([
                                    'sex' => $sex,
                                    'updated_at' => now(),
                                ]);
                            }

                            return;
                        }

                        DB::table('family_members')->insert([
                            'uuid' => (string) Str::uuid(),
                            'affected_family_id' => $head->affected_family_id,
                            'name' => trim($source->full_name),
                            'age' => $source->age,
                            'relationship_to_head' => filled($source->relationship) ? $source->relationship : 'Member',
                            'sex' => $sex,
                            'remarks_codes' => $source->code,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    });
                }
            });
    }

    public function down(): void
    {
        // Canonicalized and recovered demographic data must not be discarded.
    }

    private function normalizeSex(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        return match (mb_strtolower(trim($value))) {
            'm', 'male', 'lalaki' => 'Male',
            'f', 'female', 'babae' => 'Female',
            default => null,
        };
    }

    private function nameKey(mixed $value): string
    {
        return mb_strtolower(trim(preg_replace('/\s+/', ' ', (string) $value) ?? ''));
    }
};
