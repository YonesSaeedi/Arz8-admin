<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class TransactionCrypto extends Model {
    protected $table='users_transaction_crypto';

    public function user()
    {
        return $this->belongsTo(User::class, 'id_user', 'id');
    }

    public function crypto()
    {
        return $this->belongsTo(Cryptocurrency::class, 'id_crypto', 'id');
    }

    public function order()
    {
        return $this->belongsTo(Orders::class, 'id_order', 'id');
    }

    public function trade()
    {
        return $this->belongsTo(Trades::class, 'id_trade', 'id');
    }
}
