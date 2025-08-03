<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class TransactionInternal extends Model {
    protected $table='users_transaction_internal';

    public function user()
    {
        return $this->belongsTo(User::class, 'id_user', 'id');
    }

    public function cardBank()
    {
        return $this->belongsTo(UserCardBank::class, 'id_cardbank', 'id');
    }

    public function order()
    {
        return $this->belongsTo(Orders::class, 'id_order', 'id');
    }

}
