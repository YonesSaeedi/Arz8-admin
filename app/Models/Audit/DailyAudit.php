<?php

namespace App\Models\Audit;
use Illuminate\Database\Eloquent\Model;

class DailyAudit extends Model {
    protected $table = "audit_daily";
    public $timestamps = false;
}
