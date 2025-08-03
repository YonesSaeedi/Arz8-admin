<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class CryptoAutoTrade extends Model {
    protected $table='cryptocurrency_auto_trade';

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        CryptoAutoTrade::where('created_at','<',date('Y-m-d H:i:s',strtotime('- 2 hour')))->where('status','unsuccessful')->delete();
    }
}
