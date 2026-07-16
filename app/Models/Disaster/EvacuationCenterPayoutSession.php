<?php
namespace App\Models\Disaster;
use App\Models\Auth\User;
use App\Models\Disaster\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
class EvacuationCenterPayoutSession extends Model {
    use HasUuid;
    protected $fillable=['uuid','evacuation_center_id','disaster_id','payout_date','start_time','end_time','payout_area','assigned_officer_id','assistance_type','default_quantity','default_amount','provider','status','notes','created_by'];
    protected function casts(): array { return ['payout_date'=>'date','default_quantity'=>'decimal:2','default_amount'=>'decimal:2']; }
    public function center(): BelongsTo { return $this->belongsTo(EvacuationCenter::class,'evacuation_center_id'); }
    public function releases(): HasMany { return $this->hasMany(PayoutRelease::class,'payout_session_id'); }
    public function officer(): BelongsTo { return $this->belongsTo(User::class,'assigned_officer_id'); }
}
