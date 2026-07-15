<?php
namespace App\Models\Disaster; use App\Models\Auth\User; use Illuminate\Database\Eloquent\Model;
class WorkflowHistory extends Model {protected $fillable=['affected_family_id','from_status','to_status','action','remarks','performed_by','performed_at','metadata'];protected function casts():array{return ['performed_at'=>'datetime','metadata'=>'array'];}public function performer(){return $this->belongsTo(User::class,'performed_by');}}
