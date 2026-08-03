<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StorePersonAffectedRequest;
use App\Models\Integration\PersonAffected;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class PersonAffectedController extends Controller
{
    public function __invoke(StorePersonAffectedRequest $request): JsonResponse
    {
        $data = $request->validated();

        [$person, $event, $personCreated, $eventCreated] = DB::transaction(function () use ($data) {
            $dateTagged = Carbon::parse($data['date_tagged'])->utc();
            $eventDate = $dateTagged->toDateString();
            $person = PersonAffected::firstOrCreate(['control_number' => $data['control_number']]);
            $personCreated = $person->wasRecentlyCreated;

            $profile = collect($data)->only([
                'full_name', 'birthdate', 'age', 'sex', 'code', 'occupation', 'monthly_income',
                'health_condition', 'district', 'barangay', 'street', 'city', 'family_head_name',
                'family_head_control_number', 'relationship', 'housing',
            ])->all();
            if ($profile !== []) {
                $person->update($profile);
            }

            if (array_key_exists('family_members', $data)) {
                $controlNumbers = collect($data['family_members'])->pluck('control_number');
                $person->familyMembers()->whereNotIn('control_number', $controlNumbers)->delete();
                foreach ($data['family_members'] as $member) {
                    $person->familyMembers()->updateOrCreate(
                        ['control_number' => $member['control_number']],
                        $member
                    );
                }
            }

            $event = $person->statuses()->firstOrCreate([
                'event_date' => $eventDate,
            ], [
                'status' => $data['status'],
                'date_tagged' => $dateTagged->format('Y-m-d H:i:s.u'),
            ]);

            return [$person, $event, $personCreated, $event->wasRecentlyCreated];
        });

        return response()->json([
            'success' => true,
            'message' => $eventCreated ? 'Affected event recorded.' : 'Affected event already recorded.',
            'data' => [
                'person_affected_id' => $person->id,
                'person_affected_status_id' => $event->id,
                'control_number' => $person->control_number,
                'status' => $event->status,
                'date_tagged' => $event->date_tagged->format('Y-m-d\\TH:i:s.uP'),
                'person_created' => $personCreated,
                'event_created' => $eventCreated,
            ],
        ], $eventCreated ? Response::HTTP_CREATED : Response::HTTP_OK);
    }
}
