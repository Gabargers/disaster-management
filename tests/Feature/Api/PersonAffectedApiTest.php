<?php

namespace Tests\Feature\Api;

use App\Models\Integration\ApiAuditLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class PersonAffectedApiTest extends TestCase
{
    use RefreshDatabase;

    private const TOKEN = 'system-b-issued-test-token';

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.system_a.api_token' => self::TOKEN]);

        $credentialKey = 'legacy:'.hash('sha256', self::TOKEN);
        RateLimiter::clear('minute:'.$credentialKey);
        RateLimiter::clear('second:'.$credentialKey);
    }

    public function test_valid_affected_event_is_stored(): void
    {
        $this->postAffected([
            'control_number' => 'CN-10001',
            'status' => 'affected',
            'date_tagged' => '2025-08-17T14:35:26+08:00',
        ])->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.person_created', true)
            ->assertJsonPath('data.event_created', true);

        $this->assertDatabaseHas('person_affecteds', ['control_number' => 'CN-10001']);
        $this->assertDatabaseHas('person_affected_statuses', ['status' => 'affected']);
    }

    public function test_same_event_can_be_retried_idempotently(): void
    {
        $payload = [
            'control_number' => 'CN-10001',
            'status' => 'affected',
            'date_tagged' => '2025-08-17 14:35:26Z',
        ];

        $this->postAffected($payload)->assertCreated();
        $this->postAffected($payload)
            ->assertCreated()
            ->assertHeader('Idempotency-Replayed', 'true')
            ->assertJsonPath('data.event_created', true);

        $this->assertDatabaseCount('person_affecteds', 1);
        $this->assertDatabaseCount('person_affected_statuses', 1);
    }

    public function test_different_times_on_the_same_utc_date_do_not_create_another_status(): void
    {
        $this->postAffected([
            'control_number' => 'CN-SAME-DAY',
            'status' => 'affected',
            'date_tagged' => '2026-08-03T08:00:00Z',
        ])->assertCreated();

        $this->postAffected([
            'control_number' => 'CN-SAME-DAY',
            'status' => 'affected',
            'date_tagged' => '2026-08-03T20:45:00Z',
        ])->assertOk()
            ->assertJsonPath('data.person_created', false)
            ->assertJsonPath('data.event_created', false)
            ->assertJsonPath('data.date_tagged', '2026-08-03T08:00:00.000000+00:00');

        $this->assertDatabaseCount('person_affecteds', 1);
        $this->assertDatabaseCount('person_affected_statuses', 1);
    }

    public function test_fractional_seconds_are_preserved(): void
    {
        $this->postAffected([
            'control_number' => 'CN-PRECISE',
            'status' => 'affected',
            'date_tagged' => '2025-08-17T14:35:26.123456Z',
        ])->assertCreated()->assertJsonPath('data.date_tagged', '2025-08-17T14:35:26.123456+00:00');

        $this->assertDatabaseHas('person_affected_statuses', [
            'date_tagged' => '2025-08-17 14:35:26.123456',
        ]);
    }

    public function test_one_person_can_have_affected_events_on_different_dates(): void
    {
        foreach (['2022-01-10T08:00:00Z', '2025-06-20T09:30:00Z'] as $dateTagged) {
            $this->postAffected([
                'control_number' => 'CN-REPEAT',
                'status' => 'affected',
                'date_tagged' => $dateTagged,
            ])->assertCreated();
        }

        $this->assertDatabaseCount('person_affecteds', 1);
        $this->assertDatabaseCount('person_affected_statuses', 2);
    }

    public function test_only_affected_status_is_accepted(): void
    {
        $this->postAffected([
            'control_number' => 'CN-10001',
            'status' => 'unaffected',
            'date_tagged' => '2025-08-17T14:35:26+08:00',
        ])->assertUnprocessable()->assertJsonValidationErrors('status');

        $this->assertDatabaseCount('person_affecteds', 0);
    }

    public function test_json_content_type_is_required_and_body_size_is_limited(): void
    {
        $payload = [
            'control_number' => 'CN-TRANSPORT',
            'status' => 'affected',
            'date_tagged' => '2026-08-04T10:00:00Z',
        ];

        $this->withToken(self::TOKEN)
            ->withHeader('Idempotency-Key', 'transport-event-001')
            ->post('/api/person-affecteds', $payload)
            ->assertUnsupportedMediaType();

        config(['services.system_a.max_body_bytes' => 50]);
        $this->postAffected($payload, 'transport-event-002')
            ->assertStatus(413);

        $this->assertDatabaseCount('person_affecteds', 0);
    }

    public function test_timestamp_timezone_clock_skew_birthdate_and_unknown_fields_are_validated(): void
    {
        $base = [
            'control_number' => 'CN-STRICT',
            'status' => 'affected',
        ];

        $this->postAffected($base + ['date_tagged' => '2026-08-04T10:00:00'], 'strict-event-001')
            ->assertUnprocessable()
            ->assertJsonValidationErrors('date_tagged');

        $this->postAffected($base + [
            'date_tagged' => now()->addMinutes(10)->format('Y-m-d\\TH:i:sP'),
        ], 'strict-event-002')->assertUnprocessable()->assertJsonValidationErrors('date_tagged');

        $this->postAffected($base + [
            'date_tagged' => now()->format('Y-m-d\\TH:i:sP'),
            'birthdate' => now()->addDay()->toDateString(),
        ], 'strict-event-003')->assertUnprocessable()->assertJsonValidationErrors('birthdate');

        $this->postAffected($base + [
            'date_tagged' => now()->format('Y-m-d\\TH:i:sP'),
            'unexpected_field' => 'not allowed',
            'family_members' => [[
                'control_number' => 'FM-STRICT',
                'full_name' => 'Member',
                'private_note' => 'not allowed',
            ]],
        ], 'strict-event-004')->assertUnprocessable()
            ->assertJsonValidationErrors(['unexpected_field', 'family_members.0.private_note']);

        $this->assertDatabaseCount('person_affecteds', 0);
    }

    public function test_control_numbers_are_normalized_before_duplicate_checks(): void
    {
        $this->postAffected([
            'control_number' => ' cn- 10001 ',
            'status' => 'affected',
            'date_tagged' => '2026-08-02T08:00:00Z',
        ], 'normalize-event-001')->assertCreated();

        $this->postAffected([
            'control_number' => 'CN-10001',
            'status' => 'affected',
            'date_tagged' => '2026-08-03T08:00:00Z',
        ], 'normalize-event-002')->assertCreated();

        $this->assertDatabaseHas('person_affecteds', ['control_number' => 'CN-10001']);
        $this->assertDatabaseCount('person_affecteds', 1);
        $this->assertDatabaseCount('person_affected_statuses', 2);
    }

    public function test_api_requires_the_system_b_token(): void
    {
        $this->postJson('/api/person-affecteds', [
            'control_number' => 'CN-10001',
            'status' => 'affected',
            'date_tagged' => '2025-08-17T14:35:26+08:00',
        ])->assertUnauthorized();
    }

    public function test_idempotency_key_is_required(): void
    {
        $this->withToken(self::TOKEN)->postJson('/api/person-affecteds', [
            'control_number' => 'CN-NO-KEY',
            'status' => 'affected',
            'date_tagged' => '2026-08-03T10:00:00Z',
        ])->assertUnprocessable()
            ->assertJsonPath('success', false);

        $this->assertDatabaseCount('person_affecteds', 0);
        $this->assertDatabaseCount('api_idempotency_records', 0);
    }

    public function test_reusing_a_key_with_modified_content_is_rejected_without_overwriting_data(): void
    {
        $key = 'source-event-00001';
        $payload = [
            'control_number' => 'CN-IMMUTABLE',
            'status' => 'affected',
            'date_tagged' => '2026-08-03T10:00:00Z',
            'full_name' => 'Original Name',
            'family_members' => [
                ['control_number' => 'FM-1', 'full_name' => 'First Member'],
                ['control_number' => 'FM-2', 'full_name' => 'Second Member'],
            ],
        ];

        $this->postAffected($payload, $key)->assertCreated();

        $payload['full_name'] = 'Modified Name';
        $payload['family_members'] = [$payload['family_members'][0]];

        $this->postAffected($payload, $key)
            ->assertConflict()
            ->assertJsonPath('success', false);

        $this->assertDatabaseHas('person_affecteds', [
            'control_number' => 'CN-IMMUTABLE',
            'full_name' => 'Original Name',
        ]);
        $this->assertDatabaseCount('person_affected_family_members', 2);
        $this->assertDatabaseCount('person_affected_statuses', 1);
        $this->assertDatabaseCount('api_idempotency_records', 1);
    }

    public function test_api_requests_are_audited_without_secrets_or_request_bodies(): void
    {
        $key = 'audit-source-event-001';
        $payload = [
            'control_number' => 'CN-AUDITED',
            'status' => 'affected',
            'date_tagged' => '2026-08-03T12:00:00Z',
            'full_name' => 'Must Not Be In Audit Log',
        ];

        $this->withToken(self::TOKEN)
            ->withHeaders(['Idempotency-Key' => $key, 'X-Request-ID' => 'request-created-001'])
            ->postJson('/api/person-affecteds', $payload)
            ->assertCreated()
            ->assertHeader('X-Request-ID', 'request-created-001');

        $this->withToken(self::TOKEN)
            ->withHeaders(['Idempotency-Key' => $key, 'X-Request-ID' => 'request-retried-001'])
            ->postJson('/api/person-affecteds', $payload)
            ->assertCreated();

        $payload['full_name'] = 'Changed Name';
        $this->withToken(self::TOKEN)
            ->withHeaders(['Idempotency-Key' => $key, 'X-Request-ID' => 'request-conflict-001'])
            ->postJson('/api/person-affecteds', $payload)
            ->assertConflict();

        $clientId = 'legacy:'.hash('sha256', self::TOKEN);
        foreach ([
            ['request-created-001', 'created', 201],
            ['request-retried-001', 'retried', 201],
            ['request-conflict-001', 'conflicted', 409],
        ] as [$requestId, $outcome, $status]) {
            $this->assertDatabaseHas('api_audit_logs', [
                'client_id' => $clientId,
                'request_id' => $requestId,
                'event_reference' => $key,
                'control_number' => 'CN-AUDITED',
                'response_status' => $status,
                'outcome' => $outcome,
            ]);
        }

        $auditJson = json_encode(ApiAuditLog::all()->toArray(), JSON_THROW_ON_ERROR);
        $this->assertStringNotContainsString(self::TOKEN, $auditJson);
        $this->assertStringNotContainsString('Must Not Be In Audit Log', $auditJson);
        $this->assertStringNotContainsString('Changed Name', $auditJson);
    }

    public function test_authenticated_client_is_rate_limited(): void
    {
        config([
            'services.system_a.rate_limit_per_minute' => 2,
            'services.system_a.rate_limit_burst_per_second' => 100,
        ]);

        foreach (range(1, 2) as $attempt) {
            $this->postAffected([
                'control_number' => 'CN-RATE-LIMIT',
                'status' => 'affected',
                'date_tagged' => '2026-08-0'.$attempt.'T10:00:00Z',
            ])->assertCreated();
        }

        $this->postAffected([
            'control_number' => 'CN-RATE-LIMIT',
            'status' => 'affected',
            'date_tagged' => '2026-08-03T10:00:00Z',
        ])->assertTooManyRequests()
            ->assertHeader('Retry-After');

        $this->assertDatabaseCount('person_affected_statuses', 2);
    }

    public function test_complete_resident_snapshot_and_family_are_synchronized(): void
    {
        $payload = [
            'control_number' => 'CN-0001-00A1', 'status' => 'affected',
            'date_tagged' => '2026-07-22T08:30:00+08:00', 'full_name' => 'Walker, Ella Considine',
            'birthdate' => '2002-10-25', 'age' => 23, 'sex' => 'Female', 'occupation' => 'Septic Tank Servicer',
            'monthly_income' => 58905, 'health_condition' => 'Arthritis', 'district' => 'District 2',
            'barangay' => 'South Daang Hari', 'street' => '356 Concepcion Plains', 'city' => 'Taguig',
            'family_head_name' => 'Walker, Ella Considine', 'family_head_control_number' => 'CN-0001-00A1',
            'relationship' => 'Self (Family Head)', 'housing' => 'Sharer',
            'family_members' => [
                ['control_number' => 'CN-0001-00A1', 'full_name' => 'Walker, Ella Considine', 'relationship' => 'Self (Family Head)', 'age' => 23, 'sex' => 'Female', 'code' => 'Not specified', 'housing' => 'Sharer'],
                ['control_number' => 'CN-0001-00A2', 'full_name' => 'Walker, Rolando Bernier', 'relationship' => 'Parent', 'age' => 26, 'sex' => 'Male', 'code' => 'Not specified', 'housing' => 'Sharer'],
            ],
        ];

        $this->postAffected($payload)->assertCreated();
        $this->assertDatabaseHas('person_affecteds', ['control_number' => 'CN-0001-00A1', 'barangay' => 'South Daang Hari']);
        $this->assertDatabaseCount('person_affected_family_members', 2);

        $payload['family_members'] = [array_pop($payload['family_members'])];
        $this->postAffected($payload)->assertOk();
        $this->assertDatabaseCount('person_affected_family_members', 2);
    }

    private function postAffected(array $payload, ?string $idempotencyKey = null)
    {
        $idempotencyKey ??= 'test-'.hash('sha256', json_encode($payload, JSON_THROW_ON_ERROR));

        return $this->withToken(self::TOKEN)
            ->withHeader('Idempotency-Key', $idempotencyKey)
            ->postJson('/api/person-affecteds', $payload);
    }
}
