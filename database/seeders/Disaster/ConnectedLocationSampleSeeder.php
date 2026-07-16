<?php

namespace Database\Seeders\Disaster;

use App\Enums\FamilyStatus;
use App\Models\Auth\User;
use App\Models\Cms\Barangay;
use App\Models\Disaster\AffectedFamily;
use App\Models\Disaster\DafacRecord;
use App\Models\Disaster\Disaster;
use App\Models\Disaster\EvacuationCenter;
use App\Models\Disaster\EvacuationCenterAssignment;
use App\Models\Disaster\FamilyMember;
use App\Models\Disaster\TcissMasterlistRecord;
use App\Models\Disaster\WorkflowHistory;
use App\Models\Disaster\PayoutSchedule;
use App\Models\Disaster\PayoutRelease;
use Illuminate\Database\Seeder;

class ConnectedLocationSampleSeeder extends Seeder
{
    public function run(): void
    {
        $locations=[
            'Bagumbayan'=>'Bagumbayan Multi-Purpose Hall','Bambang'=>'Bambang Covered Court','Calzada'=>'Calzada Barangay Hall Evacuation Area',
            'Central Bicutan'=>'Central Bicutan Multi-Purpose Hall','Central Signal Village'=>'Central Signal Covered Court','Fort Bonifacio'=>'Fort Bonifacio Evacuation Center',
            'Hagonoy'=>'Hagonoy Covered Court','Ibayo-Tipas'=>'Ibayo-Tipas Multi-Purpose Hall','Katuparan'=>'Katuparan Covered Court','Ligid-Tipas'=>'Ligid-Tipas Barangay Hall',
            'Lower Bicutan'=>'Lower Bicutan Multi-Purpose Hall','Maharlika Village'=>'Maharlika Village Covered Court','Napindan'=>'Napindan Evacuation Center',
            'New Lower Bicutan'=>'New Lower Bicutan Covered Court','North Daang Hari'=>'North Daang Hari Multi-Purpose Hall','North Signal Village'=>'North Signal Village Covered Court',
            'Palingon'=>'Palingon Barangay Hall Evacuation Area','Pinagsama'=>'Pinagsama Multi-Purpose Hall','San Miguel'=>'San Miguel Covered Court','Santa Ana'=>'Santa Ana Multi-Purpose Hall',
            'South Daang Hari'=>'South Daang Hari Covered Court','South Signal Village'=>'South Signal Village Multi-Purpose Hall','Tanyag'=>'Tanyag Covered Court',
            'Tuktukan'=>'Tuktukan Barangay Hall Evacuation Area','Upper Bicutan'=>'Upper Bicutan Multi-Purpose Hall','Ususan'=>'Ususan Elementary School Evacuation Area',
            'Wawa'=>'Wawa Covered Court','Western Bicutan'=>'Western Bicutan Multi-Purpose Hall',
        ];
        $statuses=[FamilyStatus::DUPLICATE_CHECK_PENDING,FamilyStatus::DUPLICATE_CLEARED,FamilyStatus::VALIDATION_PENDING,FamilyStatus::VALIDATED,
            FamilyStatus::PAYROLL_READY,FamilyStatus::SUBMITTED_FOR_PAYROLL,FamilyStatus::PAYOUT_PENDING,FamilyStatus::PAYOUT_SCHEDULED,
            FamilyStatus::ASSISTANCE_RELEASED,FamilyStatus::REQUIREMENTS_PENDING,FamilyStatus::REQUIREMENTS_COMPLETED];
        $user=User::where('email','superadmin@gmail.com')->firstOrFail();
        $disaster=Disaster::firstOrCreate(['name'=>'Taguig Connected Data Demonstration'],['type'=>'Flood','incident_date'=>'2026-07-15','description'=>'Connected location sample data.','is_active'=>true]);
        $schedule=PayoutSchedule::firstOrCreate(['disaster_id'=>$disaster->id,'title'=>'Connected Sample Payout'],['scheduled_date'=>'2026-07-20','venue'=>'Assigned evacuation center','notes'=>'Deterministic connected sample payout.','created_by'=>$user->id]);
        $sequence=1000;
        foreach($locations as $barangayName=>$centerName){
            $barangay=Barangay::firstOrCreate(['name'=>$barangayName],['code'=>'TG-'.str_pad((string)($sequence-999),2,'0',STR_PAD_LEFT),'district'=>'Taguig','is_active'=>true]);
            $center=EvacuationCenter::firstOrCreate(['barangay_id'=>$barangay->id,'name'=>$centerName],[
                'disaster_id'=>$disaster->id,'address'=>$centerName.', '.$barangayName.', Taguig City','capacity'=>100+(($sequence%5)*50),
                'status'=>'ACTIVE','payout_availability'=>'NOT_AVAILABLE','is_active'=>true,'created_by'=>$user->id,'updated_by'=>$user->id,
            ]);
            if (!$center->disaster_id) $center->update(['disaster_id'=>$disaster->id]);
            $count=$barangayName==='Bagumbayan'?5:2;
            $bagumbayan=[['Joel','Ramos',3,FamilyStatus::PAYOUT_PENDING],['Ana','Villanueva',2,FamilyStatus::PAYOUT_SCHEDULED],
                ['Roberto','Santos',5,FamilyStatus::ASSISTANCE_RELEASED],['Liza','Mendoza',4,FamilyStatus::VALIDATED],['Carlo','Reyes',1,FamilyStatus::SUBMITTED_FOR_PAYROLL]];
            for($i=0;$i<$count;$i++){
                $sequence++; $status=$barangayName==='Bagumbayan'?$bagumbayan[$i][3]:$statuses[$sequence%count($statuses)];
                $given=$barangayName==='Bagumbayan'?$bagumbayan[$i][0]:['Maria','Jose'][$i]; $surname=$barangayName==='Bagumbayan'?$bagumbayan[$i][1]:preg_replace('/[^A-Za-z]/','',$barangayName).($i+1);
                $members=$barangayName==='Bagumbayan'?$bagumbayan[$i][2]:$i+2;
                $family=AffectedFamily::updateOrCreate(['disaster_id'=>$disaster->id,'barangay_id'=>$barangay->id,'household_head_surname'=>$surname,'household_head_given_name'=>$given],[
                    'birthdate'=>sprintf('19%02d-01-%02d',70+($sequence%20),($i+1)*3),'evacuation_center_id'=>$center->id,'complete_address'=>(10+$i).' Sample Street, '.$barangayName.', Taguig City','house_ownership'=>$i%2?'Owner':'Renter',
                    'housing_condition'=>$i%2?'Partially Damaged':'Water Damage','status'=>$status,'created_by'=>$user->id,'updated_by'=>$user->id,
                ]);
                $dafac=DafacRecord::updateOrCreate(['affected_family_id'=>$family->id],['reference_number'=>sprintf('DAFAC-2026-SEED-%04d',$sequence),'interview_date'=>'2026-07-15','interviewed_by'=>$user->id,'interviewed_by_name'=>$user->name,'attestation_confirmed'=>true]);
                TcissMasterlistRecord::updateOrCreate(['affected_family_id'=>$family->id],['dafac_record_id'=>$dafac->id,'barangay_id'=>$barangay->id,'evacuation_center_id'=>$center->id,
                    'household_head_full_name'=>$family->household_head_full_name,'birthdate'=>$family->birthdate,'address'=>$family->complete_address,
                    'source_reference'=>sprintf('TCISS-2026-SEED-%04d',$sequence),'source'=>'DAFAC_INTAKE','verification_status'=>'Verified','verified_by'=>$user->id,'verified_at'=>now()]);
                EvacuationCenterAssignment::updateOrCreate(['affected_family_id'=>$family->id,'disaster_id'=>$disaster->id,'status'=>'ACTIVE'],['evacuation_center_id'=>$center->id,'assigned_by'=>$user->id,'assigned_at'=>'2026-07-15 08:00:00','remarks'=>'Connected sample data.']);
                for($m=1;$m<=$members;$m++) FamilyMember::updateOrCreate(['affected_family_id'=>$family->id,'name'=>"Member {$m} {$surname}"],['birthdate'=>sprintf('20%02d-02-02',5+$m),'relationship_to_head'=>$m===1?'Spouse':'Child','sex'=>$m%2?'Female':'Male']);
                WorkflowHistory::updateOrCreate(['affected_family_id'=>$family->id,'action'=>'connected_sample_seeded'],['from_status'=>null,'to_status'=>$status->value,'remarks'=>'Idempotent connected sample record.','performed_by'=>$user->id,'performed_at'=>now(),'metadata'=>['source'=>'connected_sample_seeder']]);
                $payoutStatus=in_array($status,[FamilyStatus::ASSISTANCE_RELEASED,FamilyStatus::REQUIREMENTS_PENDING,FamilyStatus::REQUIREMENTS_COMPLETED],true)?'Released':($status===FamilyStatus::PAYOUT_SCHEDULED?'Scheduled':'Pending');
                PayoutRelease::updateOrCreate(['payout_schedule_id'=>$schedule->id,'affected_family_id'=>$family->id],[
                    'evacuation_center_id'=>$center->id,'status'=>$payoutStatus,'assistance_kind'=>'Emergency Cash Assistance','quantity'=>1,'amount'=>10000,
                    'provider'=>'City Social Welfare and Development Office','released_by'=>$payoutStatus==='Released'?$user->id:null,'released_at'=>$payoutStatus==='Released'?'2026-07-20 10:00:00':null,
                ]);
            }
        }
    }
}
