<?php
namespace App\Models\Disaster; use App\Models\Disaster\Concerns\HasUuid; use Illuminate\Database\Eloquent\Model;
class PayrollBatch extends Model {use HasUuid;protected $fillable=['uuid','reference_number','payroll_date','total_amount','status','prepared_by','submitted_at'];protected function casts():array{return ['payroll_date'=>'date','submitted_at'=>'datetime','total_amount'=>'decimal:2'];}public function records(){return $this->hasMany(PayrollRecord::class);}}
