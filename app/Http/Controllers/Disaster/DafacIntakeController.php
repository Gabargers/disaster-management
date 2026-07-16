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
use App\Services\Disaster\DafacIntakeIntegrationService;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class DafacIntakeController extends Controller
{
    public function __construct(private DafacIntakeIntegrationService $integration) {}

    public function index()
    {
        return view('disaster.dafac', [
            'page_title' => 'DAFAC Intake', 'page_description' => 'Encode household details and family composition.',
            'barangays' => Barangay::where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'disasters' => Disaster::where('is_active', true)->latest('incident_date')->get(['id', 'name', 'type']),
            'evacuationCenters' => EvacuationCenter::where('is_active', true)->orderBy('name')->get(['id', 'barangay_id', 'name']),
            'recentIntakes' => DafacRecord::with(['affectedFamily.barangay','affectedFamily.evacuationCenter','affectedFamily.tcissMasterlistRecord'])->latest()->limit(20)->get(),
        ]);
    }

    public function store(StoreDafacIntakeRequest $request): JsonResponse
    {
        $data = $request->validated();
        try {
            $record = $this->integration->save($data, $request);
        } catch (QueryException $e) {
            if (in_array($e->getCode(), ['23000', '23505'])) return response()->json(['success' => false, 'message' => 'A matching DAFAC intake was saved already.'], 409);
            throw $e;
        }
        $family=$record->affectedFamily; $tciss=$family->tcissMasterlistRecord;
        return response()->json(['success'=>true,'message'=>'DAFAC intake saved successfully.','data'=>[
            'id'=>$record->id,'affected_family_id'=>$family->id,'dafac_id'=>$record->id,'dafac_reference'=>$record->reference_number,
            'tciss_masterlist_id'=>$tciss->id,'tciss_reference'=>$tciss->source_reference,'barangay'=>$family->barangay,
            'evacuation_center'=>$family->evacuationCenter,'assignment'=>$family->activeEvacuationCenterAssignment,
            'status'=>FamilyStatus::DUPLICATE_CHECK_PENDING->value,'url'=>route('disaster.families.show',$family),
        ]],201);
    }

    public function show(DafacRecord $dafacRecord)
    {
        $dafacRecord->load(['affectedFamily.barangay', 'affectedFamily.disaster', 'affectedFamily.evacuationCenter', 'affectedFamily.familyMembers', 'documents']);
        return view('disaster.dafac-show', ['page_title' => $dafacRecord->reference_number, 'page_description' => 'Saved DAFAC intake record.', 'record' => $dafacRecord]);
    }

}
