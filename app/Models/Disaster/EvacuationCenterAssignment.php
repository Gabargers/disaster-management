<?php
namespace App\Models\Disaster;
use App\Models\Auth\User;
use App\Models\Disaster\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class EvacuationCenterAssignment extends Model {
    use HasUuid;
    protected $fillable=['uuid','evacuation_center_id','affected_family_id','disaster_id','assigned_by','assigned_at','unassigned_at','status','remarks'];
    protected function casts(): array { return ['assigned_at'=>'datetime','unassigned_at'=>'datetime']; }
    public function center(): BelongsTo { return $this->belongsTo(EvacuationCenter::class,'evacuation_center_id'); }
    public function family(): BelongsTo { return $this->belongsTo(AffectedFamily::class,'affected_family_id'); }
    public function disaster(): BelongsTo { return $this->belongsTo(Disaster::class); }
    public function assigner(): BelongsTo { return $this->belongsTo(User::class,'assigned_by'); }
}
