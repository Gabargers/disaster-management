<?php
namespace App\Models\Disaster; use Illuminate\Database\Eloquent\Model;
class PayrollRecord extends Model {protected $fillable=['payroll_batch_id','affected_family_id','dafac_record_id','amount','status'];protected function casts():array{return ['amount'=>'decimal:2'];}public function family(){return $this->belongsTo(AffectedFamily::class,'affected_family_id');}public function dafacRecord(){return $this->belongsTo(DafacRecord::class);}}
