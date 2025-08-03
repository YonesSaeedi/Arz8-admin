<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Orders extends Model {
    protected $table='orders';

    public function user()
    {
        return $this->belongsTo(User::class, 'id_user', 'id');
    }

    public function crypto()
    {
        return $this->belongsTo(Cryptocurrency::class, 'id_crypto', 'id');
    }
}
