<?php

namespace App\Http\Controllers\Disaster;

use App\Http\Controllers\Controller;
use App\Models\Disaster\TcissMasterlistRecord;
use App\Models\Disaster\UploadedDocument;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TcissMasterlistController extends Controller
{
    public function index(Request $request)
    {
        $records = TcissMasterlistRecord::query()
            ->with(['barangay', 'evacuationCenter', 'affectedFamily'])
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = '%'.$request->string('search')->trim().'%';
                $query->where(function ($query) use ($search) {
                    $query->where('household_head_full_name', 'like', $search)
                        ->orWhere('address', 'like', $search)
                        ->orWhere('source_reference', 'like', $search);
                });
            })
            ->when($request->filled('barangay_id'), fn ($query) => $query->where('barangay_id', $request->integer('barangay_id')))
            ->when($request->filled('evacuation_center_id'), fn ($query) => $query->where('evacuation_center_id', $request->integer('evacuation_center_id')))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('disaster.tciss', [
            'page_title' => 'TCISS / Masterlist Verification',
            'page_description' => 'Verify affected families before DAFAC intake.',
            'records' => $records,
            'barangays' => \App\Models\Cms\Barangay::query()->where('is_active', true)->orderBy('name')->get(),
            'evacuationCenters' => \App\Models\Disaster\EvacuationCenter::query()->where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function fullDetails(TcissMasterlistRecord $record): JsonResponse
    {
        $record->load([
            'barangay', 'evacuationCenter', 'verifier',
            'affectedFamily.disaster', 'affectedFamily.barangay', 'affectedFamily.evacuationCenter',
            'affectedFamily.familyMembers', 'affectedFamily.dafacRecord.interviewer', 'affectedFamily.dafacRecord.validator',
            'affectedFamily.validationRecords.validator', 'affectedFamily.validationRecords.documents',
            'affectedFamily.duplicateChecks.possibleDuplicateFamily', 'affectedFamily.duplicateChecks.resolver',
            'affectedFamily.assistanceRecords.releaser', 'affectedFamily.payoutReleases.payoutSchedule',
            'affectedFamily.payoutReleases.releaser', 'affectedFamily.postPayoutRequirement.documents',
        ]);

        $family = $record->affectedFamily;
        $dafac = $family?->dafacRecord;
        $validation = $family?->validationRecords->sortByDesc('validated_at')->first();
        $documents = collect($family?->validationRecords ?? [])->flatMap->documents
            ->merge($family?->postPayoutRequirement?->documents ?? collect());

        return response()->json(['success' => true, 'data' => [
            'masterlist' => [
                'id' => $record->id, 'reference_number' => $record->source_reference,
                'verification_status' => $record->verification_status,
                'verified_by' => $record->verifier?->name, 'verified_at' => $record->verified_at?->toIso8601String(),
                'created_at' => $record->created_at?->toIso8601String(), 'updated_at' => $record->updated_at?->toIso8601String(),
            ],
            'affected_family' => $family ? [
                'full_name' => $family->household_head_full_name, 'surname' => $family->household_head_surname,
                'given_name' => $family->household_head_given_name, 'middle_name' => $family->household_head_middle_name,
                'birthdate' => $family->birthdate?->format('Y-m-d'), 'age' => $family->birthdate?->age,
                'sex' => null, 'occupation' => $family->occupation, 'monthly_income' => $family->monthly_income,
                'contact_number' => null, 'complete_address' => $family->complete_address,
                'house_ownership' => $family->house_ownership, 'housing_condition' => $family->housing_condition,
                'health_condition' => $family->health_condition, 'status' => $family->status?->value ?? $family->status,
                'member_count' => $family->familyMembers->count(),
                'disaster_name' => $family->disaster?->name, 'disaster_type' => $family->disaster?->type,
                'incident_date' => $family->disaster?->incident_date?->format('Y-m-d'),
                'barangay' => $family->barangay?->name, 'evacuation_center' => $family->evacuationCenter?->name,
            ] : null,
            'dafac' => $dafac ? [
                'reference_number' => $record->source_reference ? str_replace('TCISS-', 'DAFAC-', $record->source_reference) : 'DAFAC-'.str_pad((string) $dafac->id, 4, '0', STR_PAD_LEFT),
                'interview_date' => $dafac->interview_date?->format('Y-m-d'),
                'interviewed_by' => $dafac->interviewer?->name, 'validated_by' => $dafac->validator?->name,
            ] : null,
            'family_members' => $family?->familyMembers->map(fn ($member) => [
                'name' => $member->name, 'birthdate' => $member->birthdate?->format('Y-m-d'),
                'age' => $member->birthdate?->age, 'relationship' => $member->relationship_to_head,
                'sex' => $member->sex, 'occupation' => $member->occupation, 'monthly_income' => $member->monthly_income,
                'health_condition' => $member->health_condition, 'remarks_code' => $member->remarks_codes,
            ])->values() ?? [],
            'validation' => $validation ? [
                'status' => $validation->status, 'house_ownership' => $validation->validated_house_ownership,
                'housing_condition' => $validation->validated_housing_condition, 'notes' => $validation->notes,
                'validated_by' => $validation->validator?->name, 'validated_at' => $validation->validated_at?->toIso8601String(),
            ] : null,
            'duplicate_checks' => $family?->duplicateChecks->map(fn ($check) => [
                'possible_duplicate_found' => (bool) $check->possible_duplicate_family_id, 'match_score' => $check->match_score,
                'match_reason' => collect($check->matched_fields)->implode(', '),
                'matched_household' => $check->possibleDuplicateFamily?->household_head_full_name,
                'resolution_status' => $check->resolution, 'resolved_by' => $check->resolver?->name,
                'resolved_at' => $check->resolved_at?->toIso8601String(),
            ])->values() ?? [],
            'attachments' => $documents->map(fn ($document) => [
                'type' => $document->document_type, 'name' => $document->original_name ?: 'Attachment',
                'mime_type' => $document->mime_type,
                'url' => URL::temporarySignedRoute('disaster.tciss.documents.show', now()->addMinutes(10), $document),
            ])->values(),
            'assistance_history' => $family?->assistanceRecords->map(fn ($assistance) => [
                'date' => $assistance->date_assistance_provided?->format('Y-m-d'), 'kind' => $assistance->assistance_kind,
                'quantity_amount' => $assistance->quantity_amount, 'provider' => $assistance->provider,
                'released_by' => $assistance->releaser?->name,
            ])->values() ?? [],
            'payout' => $family?->payoutReleases->map(fn ($payout) => [
                'schedule' => $payout->payoutSchedule?->title, 'scheduled_date' => $payout->payoutSchedule?->scheduled_date?->format('Y-m-d'),
                'status' => $payout->status, 'released_by' => $payout->releaser?->name,
                'released_at' => $payout->released_at?->toIso8601String(),
            ])->values() ?? [],
        ]]);
    }

    public function document(UploadedDocument $document): StreamedResponse
    {
        abort_unless(Storage::disk('local')->exists($document->file_path), 404);

        return Storage::disk('local')->download($document->file_path, $document->original_name ?: basename($document->file_path));
    }
}
