<?php

namespace App\Http\Controllers\Disaster;

use App\Http\Controllers\Controller;
use App\Models\Integration\PersonAffected;
use App\Models\Integration\PersonAffectedStatus;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class PersonAffectedController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->query('search'));
        $status = trim((string) $request->query('status'));

        $people = PersonAffected::query()
            ->with('latestStatus')
            ->withCount(['statuses', 'familyMembers'])
            ->when($search !== '', fn ($query) => $query->where(function ($query) use ($search) {
                $query->where('control_number', 'like', '%'.$search.'%')
                    ->orWhere('full_name', 'like', '%'.$search.'%');
            }))
            ->when($status !== '', fn ($query) => $query->whereHas(
                'latestStatus',
                fn ($statusQuery) => $statusQuery->where('status', $status)
            ))
            ->orderByDesc(
                PersonAffectedStatus::select('date_tagged')
                    ->whereColumn('person_affected_id', 'person_affecteds.id')
                    ->latest('date_tagged')
                    ->limit(1)
            )
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        return view('disaster.person-affecteds', [
            'page_title' => 'TCISS Person Affected',
            'page_description' => 'People received from the TCISS API integration.',
            'people' => $people,
            'totalPeople' => PersonAffected::count(),
            'totalEvents' => PersonAffectedStatus::count(),
            'statuses' => PersonAffectedStatus::query()->distinct()->orderBy('status')->pluck('status'),
            'latestReceivedAt' => PersonAffectedStatus::max('date_tagged'),
        ]);
    }

    public function show(PersonAffected $personAffected): JsonResponse
    {
        $personAffected->load(['latestStatus', 'statuses' => fn ($query) => $query->latest('date_tagged'), 'familyMembers']);

        return response()->json(['data' => [
            'id' => $personAffected->id, 'control_number' => $personAffected->control_number,
            'full_name' => $personAffected->full_name, 'birthdate' => $personAffected->birthdate?->format('F d, Y'),
            'age' => $personAffected->age, 'sex' => $personAffected->sex, 'code' => $personAffected->code,
            'occupation' => $personAffected->occupation, 'monthly_income' => $personAffected->monthly_income,
            'health_condition' => $personAffected->health_condition, 'district' => $personAffected->district,
            'barangay' => $personAffected->barangay, 'street' => $personAffected->street, 'city' => $personAffected->city,
            'family_head_name' => $personAffected->family_head_name,
            'family_head_control_number' => $personAffected->family_head_control_number,
            'relationship' => $personAffected->relationship, 'housing' => $personAffected->housing,
            'latest_status' => $personAffected->latestStatus?->status,
            'date_tagged' => $personAffected->latestStatus?->date_tagged?->toIso8601String(),
            'family_members' => $personAffected->familyMembers->map(fn ($member) => [
                'control_number' => $member->control_number, 'full_name' => $member->full_name,
                'relationship' => $member->relationship, 'age' => $member->age, 'sex' => $member->sex,
                'code' => $member->code, 'housing' => $member->housing,
            ])->values(),
            'status_history' => $personAffected->statuses->map(fn ($event) => [
                'status' => $event->status, 'date_tagged' => $event->date_tagged?->toIso8601String(),
            ])->values(),
        ]]);
    }
}
