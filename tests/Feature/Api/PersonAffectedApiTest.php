<?php

namespace Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PersonAffectedApiTest extends TestCase
{
    use RefreshDatabase;

    private const TOKEN = 'system-b-issued-test-token';

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.system_a.api_token' => self::TOKEN]);
    }

    public function test_valid_affected_event_is_stored(): void
    {
        $this->withToken(self::TOKEN)->postJson('/api/person-affecteds', [
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
            'date_tagged' => '2025-08-17 14:35:26',
        ];

        $this->withToken(self::TOKEN)->postJson('/api/person-affecteds', $payload)->assertCreated();
        $this->withToken(self::TOKEN)->postJson('/api/person-affecteds', $payload)
            ->assertOk()->assertJsonPath('data.event_created', false);

        $this->assertDatabaseCount('person_affecteds', 1);
        $this->assertDatabaseCount('person_affected_statuses', 1);
    }

    public function test_fractional_seconds_are_preserved(): void
    {
        $this->withToken(self::TOKEN)->postJson('/api/person-affecteds', [
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
            $this->withToken(self::TOKEN)->postJson('/api/person-affecteds', [
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
        $this->withToken(self::TOKEN)->postJson('/api/person-affecteds', [
            'control_number' => 'CN-10001',
            'status' => 'unaffected',
            'date_tagged' => '2025-08-17T14:35:26+08:00',
        ])->assertUnprocessable()->assertJsonValidationErrors('status');

        $this->assertDatabaseCount('person_affecteds', 0);
    }

    public function test_api_requires_the_system_b_token(): void
    {
        $this->postJson('/api/person-affecteds', [
            'control_number' => 'CN-10001',
            'status' => 'affected',
            'date_tagged' => '2025-08-17T14:35:26+08:00',
        ])->assertUnauthorized();
    }
}
