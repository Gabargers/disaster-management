<?php

namespace App\Http\Controllers\Disaster;

use App\Http\Controllers\Controller;
use App\Models\Integration\PersonAffected;
use App\Models\Integration\PersonAffectedStatus;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\Disaster\EvacuationCenter;
use Illuminate\Validation\ValidationException;

class PersonAffectedController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->query('search'));
        $status = trim((string) $request->query('status'));

        $families = PersonAffected::query()
            ->with(['latestStatus', 'evacuationCenter'])
            ->withCount(['statuses', 'familyMembers'])
            ->familyHeads()
            ->when($search !== '', fn ($query) => $query->where(function ($query) use ($search) {
                foreach ([
                    'control_number', 'full_name', 'sex', 'code', 'occupation', 'health_condition',
                    'district', 'barangay', 'street', 'city', 'family_head_name',
                    'family_head_control_number', 'relationship', 'housing',
                ] as $column) {
                    $query->orWhere($column, 'like', '%'.$search.'%');
                }
                $query->orWhereHas('familyMembers', fn ($memberQuery) => $memberQuery
                    ->where('control_number', 'like', '%'.$search.'%')
                    ->orWhere('full_name', 'like', '%'.$search.'%'));
            }))
            ->when($status === 'assigned', fn ($query) => $query->whereNotNull('evacuation_center_id'))
            ->when($status === 'unassigned', fn ($query) => $query->whereNull('evacuation_center_id'))
            ->when($status !== '' && ! in_array($status, ['assigned', 'unassigned'], true), fn ($query) => $query->whereHas(
                'latestStatus', fn ($statusQuery) => $statusQuery->where('status', $status)
            ))
            ->orderByDesc(
                PersonAffectedStatus::select('date_tagged')
                    ->whereColumn('person_affected_id', 'person_affecteds.id')
                    ->latest('date_tagged')
                    ->limit(1)
            )
            ->orderByDesc('id')
            ->orderByDesc('id');

        $totalFamilies = (clone $families)->reorder()->count();
        $people = $families->paginate(15)->withQueryString();

        return view('disaster.person-affecteds', [
            'page_title' => 'TCISS Family Affected',
            'page_description' => 'Families received from the TCISS API integration.',
            'people' => $people,
            'totalPeople' => $totalFamilies,
            'totalAssigned' => PersonAffected::familyHeads()->whereNotNull('evacuation_center_id')->count(),
            'statuses' => PersonAffectedStatus::query()->distinct()->orderBy('status')->pluck('status')->prepend('unassigned')->prepend('assigned'),
            'latestReceivedAt' => PersonAffectedStatus::max('date_tagged'),
        ]);
    }

    public function show(PersonAffected $personAffected): JsonResponse
    {
        $personAffected->load(['latestStatus', 'statuses' => fn ($query) => $query->latest('date_tagged'), 'familyMembers', 'evacuationCenter.barangay', 'evacuationCenterAssigner']);
        $centers = EvacuationCenter::query()->with('barangay')->withCount(['activeAssignments', 'unlinkedPersonAffecteds'])
            ->where('is_active', true)->where('status', 'ACTIVE')->orderBy('name')->get();

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
            'evacuation_center' => $personAffected->evacuationCenter ? [
                'id' => $personAffected->evacuationCenter->id,
                'name' => $personAffected->evacuationCenter->name,
                'barangay' => $personAffected->evacuationCenter->barangay?->name,
                'assigned_by' => $personAffected->evacuationCenterAssigner?->name,
                'assigned_at' => $personAffected->evacuation_center_assigned_at?->toIso8601String(),
            ] : null,
            'evacuation_center_assignment' => [
                'can_assign' => request()->user()->can('evacuation_center.assign_family') && $centers->isNotEmpty(),
                'has_centers' => $centers->isNotEmpty(),
                'assign_url' => route('disaster.person-affecteds.assign-evacuation-center', $personAffected),
                'create_url' => request()->user()->can('manage payout schedules') ? route('disaster.payouts.index') : null,
                'centers' => $centers->map(fn ($center) => [
                    'id' => $center->id, 'name' => $center->name,
                    'barangay' => $center->barangay?->name, 'capacity' => $center->capacity,
                    'occupied' => $center->active_assignments_count + $center->unlinked_person_affecteds_count,
                    'is_full' => ($center->active_assignments_count + $center->unlinked_person_affecteds_count) >= $center->capacity,
                ])->values(),
            ],
            'family_members' => $personAffected->familyMembers->map(fn ($member) => [
                'control_number' => $member->control_number, 'full_name' => $member->full_name,
                'relationship' => $member->relationship, 'age' => $member->age, 'sex' => $member->sex,
                'code' => $member->code, 'housing' => $member->housing,
                'district' => $personAffected->district, 'barangay' => $personAffected->barangay,
                'street' => $personAffected->street, 'city' => $personAffected->city,
            ])->values(),
            'status_history' => $personAffected->statuses->map(fn ($event) => [
                'status' => $event->status, 'date_tagged' => $event->date_tagged?->toIso8601String(),
            ])->values(),
        ]]);
    }

    public function assignEvacuationCenter(Request $request, PersonAffected $personAffected): JsonResponse
    {
        $data = $request->validate(['evacuation_center_id' => ['required', 'integer', 'exists:evacuation_centers,id']]);
        $center = EvacuationCenter::query()->whereKey($data['evacuation_center_id'])
            ->where('is_active', true)->where('status', 'ACTIVE')->withCount(['activeAssignments', 'unlinkedPersonAffecteds'])->first();

        if (! $center) {
            throw ValidationException::withMessages(['evacuation_center_id' => 'Select an active evacuation center.']);
        }
        $occupied = $center->active_assignments_count + $center->unlinked_person_affecteds_count;
        $alreadyAssignedHere = $personAffected->evacuation_center_id === $center->id;
        if (! $alreadyAssignedHere && $occupied >= $center->capacity && ! $request->user()->can('evacuation_center.capacity_override')) {
            throw ValidationException::withMessages(['evacuation_center_id' => 'This evacuation center is already at full capacity.']);
        }

        $personAffected->update([
            'evacuation_center_id' => $center->id,
            'evacuation_center_assigned_by' => $request->user()->id,
            'evacuation_center_assigned_at' => now(),
        ]);

        $center->loadCount(['activeAssignments', 'unlinkedPersonAffecteds']);

        return response()->json(['success' => true, 'message' => 'Evacuation center assigned successfully.', 'data' => [
            'person_affected_id' => $personAffected->id,
            'center' => [
                'id' => $center->id,
                'name' => $center->name,
                'families_count' => $center->active_assignments_count + $center->unlinked_person_affecteds_count,
            ],
            'assigned_families_count' => PersonAffected::familyHeads()->whereNotNull('evacuation_center_id')->count(),
        ]]);
    }
}
