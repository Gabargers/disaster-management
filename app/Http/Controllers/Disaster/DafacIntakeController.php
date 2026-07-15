<?php

namespace App\Http\Controllers\Disaster;

use App\Enums\FamilyStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Disaster\StoreDafacIntakeRequest;
use App\Models\Cms\Barangay;
use App\Models\Disaster\AffectedFamily;
use App\Models\Disaster\AuditLog;
use App\Models\Disaster\DafacRecord;
use App\Models\Disaster\Disaster;
use App\Models\Disaster\EvacuationCenter;
use App\Models\Disaster\UploadedDocument;
use App\Services\Disaster\DisasterAssistanceWorkflowService;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class DafacIntakeController extends Controller
{
    public function __construct(private DisasterAssistanceWorkflowService $workflow) {}

    public function index()
    {
        return view('disaster.dafac', [
            'page_title' => 'DAFAC Intake', 'page_description' => 'Encode household details and family composition.',
            'barangays' => Barangay::where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'disasters' => Disaster::where('is_active', true)->latest('incident_date')->get(['id', 'name', 'type']),
            'evacuationCenters' => EvacuationCenter::where('is_active', true)->orderBy('name')->get(['id', 'barangay_id', 'name']),
        ]);
    }

    public function store(StoreDafacIntakeRequest $request): JsonResponse
    {
        $data = $request->validated();
        try {
            $record = DB::transaction(function () use ($data, $request) {
                $head = $data['household_head'];
                $candidate = new AffectedFamily([
                    'disaster_id' => $data['disaster_id'], 'barangay_id' => $data['barangay_id'],
                    'evacuation_center_id' => $data['evacuation_center_id'] ?? null,
                    'household_head_surname' => $head['surname'], 'household_head_given_name' => $head['given_name'],
                    'household_head_middle_name' => $head['middle_name'] ?? null, 'birthdate' => $head['birthdate'],
                    'occupation' => $head['occupation'] ?? null, 'monthly_income' => $head['monthly_income'] ?? null,
                    'contact_number' => $head['contact_number'] ?? null, 'complete_address' => $head['complete_address'],
                    'house_ownership' => $data['house_ownership'], 'housing_condition' => $data['housing_condition'],
                    'health_condition' => $data['health_condition'] ?? null, 'status' => FamilyStatus::DRAFT,
                    'created_by' => $request->user()->id, 'updated_by' => $request->user()->id,
                ]);
                $hash = $candidate->buildHouseholdHash();
                if (AffectedFamily::where('exact_household_hash', $hash)->lockForUpdate()->exists()) {
                    abort(409, 'A DAFAC intake already exists for this household, barangay, and disaster.');
                }
                $candidate->save();
                foreach ($data['family_members'] ?? [] as $member) {
                    $candidate->familyMembers()->create([
                        'name' => $member['full_name'], 'birthdate' => $member['birthdate'],
                        'relationship_to_head' => $member['relationship_to_head'], 'sex' => $member['sex'],
                        'occupation' => $member['occupation'] ?? null, 'monthly_income' => $member['monthly_income'] ?? null,
                        'health_condition' => $member['health_condition'] ?? null, 'remarks_codes' => $member['remarks_code'] ?? null,
                    ]);
                }
                $record = $candidate->dafacRecord()->create([
                    'reference_number' => $this->nextReference(), 'interview_date' => $data['intake_date'],
                    'interviewed_by' => $request->user()->id, 'interviewed_by_name' => $data['interviewed_by'],
                    'attestation_confirmed' => true,
                ]);
                foreach (['signature', 'thumbmark'] as $type) {
                    if (! $request->hasFile($type)) continue;
                    $file = $request->file($type); $path = $file->store("dafac/{$record->uuid}", 'local');
                    $record->documents()->create(['document_type' => $type, 'file_path' => $path, 'original_name' => $file->getClientOriginalName(), 'mime_type' => $file->getMimeType(), 'file_size' => $file->getSize(), 'uploaded_by' => $request->user()->id]);
                }
                AuditLog::create(['user_id' => $request->user()->id, 'auditable_type' => DafacRecord::class, 'auditable_id' => $record->id, 'action' => 'dafac_intake_created', 'new_values' => ['reference_number' => $record->reference_number, 'affected_family_id' => $candidate->id, 'family_member_count' => count($data['family_members'] ?? [])], 'ip_address' => $request->ip(), 'user_agent' => substr((string) $request->userAgent(), 0, 1000)]);
                $candidate = $this->workflow->transition($candidate, FamilyStatus::DAFAC_INTAKE_COMPLETED, $request->user(), 'dafac_intake_completed');
                $possible = AffectedFamily::whereKeyNot($candidate->id)->where('disaster_id', $candidate->disaster_id)->where('barangay_id', $candidate->barangay_id)->where('birthdate', $candidate->birthdate)->where(function ($q) use ($candidate) { $q->where('household_head_surname', $candidate->household_head_surname)->orWhere('household_head_given_name', $candidate->household_head_given_name); })->first();
                $candidate->duplicateChecks()->create(['possible_duplicate_family_id' => $possible?->id, 'match_score' => $possible ? 80 : 0, 'matched_fields' => $possible ? ['disaster', 'barangay', 'birthdate', 'household head name'] : [], 'resolution' => 'Pending']);
                $this->workflow->transition($candidate, FamilyStatus::DUPLICATE_CHECK_PENDING, $request->user(), 'duplicate_check_queued', null, ['possible_duplicate_family_id' => $possible?->id]);
                return $record;
            }, 3);
        } catch (QueryException $e) {
            if (in_array($e->getCode(), ['23000', '23505'])) return response()->json(['success' => false, 'message' => 'A matching DAFAC intake was saved already.'], 409);
            throw $e;
        }
        return response()->json(['success' => true, 'message' => 'DAFAC intake saved and queued for duplicate checking.', 'data' => ['id' => $record->id, 'dafac_reference' => $record->reference_number, 'affected_family_id' => $record->affected_family_id, 'status' => FamilyStatus::DUPLICATE_CHECK_PENDING->value, 'url' => route('disaster.families.show', $record->affected_family_id)]], 201);
    }

    public function show(DafacRecord $dafacRecord)
    {
        $dafacRecord->load(['affectedFamily.barangay', 'affectedFamily.disaster', 'affectedFamily.evacuationCenter', 'affectedFamily.familyMembers', 'documents']);
        return view('disaster.dafac-show', ['page_title' => $dafacRecord->reference_number, 'page_description' => 'Saved DAFAC intake record.', 'record' => $dafacRecord]);
    }

    private function nextReference(): string
    {
        $year = now()->year; $last = DafacRecord::where('reference_number', 'like', "DAFAC-{$year}-%")->lockForUpdate()->orderByDesc('reference_number')->value('reference_number');
        return sprintf('DAFAC-%d-%04d', $year, $last ? ((int) substr($last, -4)) + 1 : 1);
    }
}
