<?php

namespace App\Models\PaymentGateway;
use Illuminate\Database\Eloquent\Model;

class PaymentGateway extends Model {
    protected $table='payment_gateway';
    public $timestamps = false;
}
