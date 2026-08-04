<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StorePersonAffectedRequest;
use App\Models\Integration\ApiIdempotencyRecord;
use App\Models\Integration\PersonAffected;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class PersonAffectedController extends Controller
{
    public function __invoke(StorePersonAffectedRequest $request): JsonResponse
    {
        $data = $request->validated();
        $idempotencyKey = $request->header('Idempotency-Key');
        $clientId = (string) $request->attributes->get('api_client_id');
        $requestHash = hash('sha256', $request->getContent());

        if (! is_string($idempotencyKey) ||
            preg_match('/\A[A-Za-z0-9][A-Za-z0-9._:-]{7,254}\z/', $idempotencyKey) !== 1) {
            return response()->json([
                'success' => false,
                'message' => 'The Idempotency-Key header is required and must be 8-255 characters using letters, numbers, dot, underscore, colon, or hyphen.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $existing = ApiIdempotencyRecord::query()
            ->where('client_id', $clientId)
            ->where('idempotency_key', $idempotencyKey)
            ->first();

        if ($existing) {
            return $this->replayOrReject($existing, $requestHash);
        }

        try {
            return DB::transaction(function () use ($clientId, $data, $idempotencyKey, $requestHash) {
                [$person, $event, $personCreated, $eventCreated] = $this->storeEvent($data);
                $status = $eventCreated ? Response::HTTP_CREATED : Response::HTTP_OK;
                $body = [
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
                ];

                ApiIdempotencyRecord::create([
                    'client_id' => $clientId,
                    'idempotency_key' => $idempotencyKey,
                    'request_hash' => $requestHash,
                    'response_status' => $status,
                    'response_body' => $body,
                ]);

                return response()->json($body, $status);
            });
        } catch (UniqueConstraintViolationException) {
            $existing = ApiIdempotencyRecord::query()
                ->where('client_id', $clientId)
                ->where('idempotency_key', $idempotencyKey)
                ->firstOrFail();

            return $this->replayOrReject($existing, $requestHash);
        }
    }

    private function storeEvent(array $data): array
    {
        $dateTagged = Carbon::parse($data['date_tagged'])->utc();
        $eventDate = $dateTagged->toDateString();
        $person = PersonAffected::firstOrCreate(['control_number' => $data['control_number']]);
        $personCreated = $person->wasRecentlyCreated;
        $event = $person->statuses()->firstOrCreate([
            'event_date' => $eventDate,
        ], [
            'status' => $data['status'],
            'date_tagged' => $dateTagged->format('Y-m-d H:i:s.u'),
        ]);
        $eventCreated = $event->wasRecentlyCreated;

        if ($eventCreated) {
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
        }

        return [$person, $event, $personCreated, $eventCreated];
    }

    private function replayOrReject(ApiIdempotencyRecord $record, string $requestHash): JsonResponse
    {
        if (! hash_equals($record->request_hash, $requestHash)) {
            return response()->json([
                'success' => false,
                'message' => 'The Idempotency-Key has already been used with a different request body.',
            ], Response::HTTP_CONFLICT);
        }

        return response()->json($record->response_body, $record->response_status)
            ->header('Idempotency-Replayed', 'true');
    }
}
