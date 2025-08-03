<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class UserCardBank extends Model {
    protected $table='users_cardbank';

    public function user()
    {
        return $this->belongsTo(User::class, 'id_user', 'id');
    }

    public function userDepositIds()
    {
        return $this->hasMany(UserDepositId::class, 'id_cardbank', 'id');
    }
}
